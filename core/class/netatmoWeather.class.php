<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../3rdparty/Netatmo-API-PHP/NAApiClient.php';

class netatmoWeather extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	public function syncWithNetatmo() {
		$client = new NAApiClient(array(
			'client_id' => config::byKey('client_id', 'netatmoWeather'),
			'client_secret' => config::byKey('client_secret', 'netatmoWeather'),
			'username' => config::byKey('username', 'netatmoWeather'),
			'password' => config::byKey('password', 'netatmoWeather'),
			'scope' => NAScopes::SCOPE_READ_STATION,
		));
		$helper = new NAApiHelper($client);
		$tokens = $client->getAccessToken();
		$user = $helper->api("getuser", "POST");
		$devicelist = $helper->simplifyDeviceList();
		log::add('netatmoWeather', 'debug', print_r($devicelist, true));
		foreach ($devicelist['devices'] as $device) {
			$eqLogic = eqLogic::byLogicalId($device['_id'], 'netatmoWeather');
			if (!is_object($eqLogic)) {
				$eqLogic = new netatmoWeather();
			}
			$eqLogic->setEqType_name('netatmoWeather');
			$eqLogic->setIsEnable(1);
			$eqLogic->setName($device['station_name']);
			$eqLogic->setLogicalId($device['_id']);
			$eqLogic->setConfiguration('type', 'station');
			$eqLogic->setCategory('heating', 1);
			$eqLogic->setIsVisible(1);
			$eqLogic->save();
			foreach ($device['modules'] as $module) {
				if ($module['type'] == "NAModule1") {
					$type = 'module_ext';
				} elseif ($module['type'] == "NAModule4") {
					$type = 'module_int';
				} elseif ($module['type'] == "NAModule3") {
					$type = 'module_rain';
				}
				$eqLogic = eqLogic::byLogicalId($module['_id'], 'netatmoWeather');
				if (!is_object($eqLogic)) {
					$eqLogic = new netatmoWeather();
				}
				$eqLogic->setEqType_name('netatmoWeather');
				$eqLogic->setIsEnable(1);
				$eqLogic->setName($module['module_name']);
				$eqLogic->setLogicalId($module['_id']);
				$eqLogic->setConfiguration('type', $type);
				$eqLogic->setCategory('heating', 1);
				$eqLogic->setIsVisible(1);
				$eqLogic->save();
			}
		}
	}

	public static function cron15() {
		try {
			$client = new NAApiClient(array(
				'client_id' => config::byKey('client_id', 'netatmoWeather'),
				'client_secret' => config::byKey('client_secret', 'netatmoWeather'),
				'username' => config::byKey('username', 'netatmoWeather'),
				'password' => config::byKey('password', 'netatmoWeather'),
				'scope' => NAScopes::SCOPE_READ_STATION,
			));
			$helper = new NAApiHelper($client);

			try {
				$tokens = $client->getAccessToken();
				if (config::byKey('numberFailed', 'netatmoWeather', 0) > 0) {
					config::save('numberFailed', 0, 'netatmoWeather');
				}
			} catch (NAClientException $ex) {
				if (config::byKey('numberFailed', 'netatmoWeather', 0) > 3) {
					log::add('netatmoWeather', 'error', __('Erreur sur synchro netatmo weather ', __FILE__) . '(' . config::byKey('numberFailed', 'netatmoWeather', 0) . ')' . $e->getMessage());
				} else {
					config::save('numberFailed', config::byKey('numberFailed', 'netatmoWeather', 0) + 1, 'netatmoWeather');
				}
				return;
			}
			$user = $helper->api("getuser", "POST");
			$devicelist = $helper->simplifyDeviceList();
			$mesures = $helper->getLastMeasures($client, $devicelist);
			foreach ($mesures[0]['modules'] as $mesure) {
				$eqLogic = eqLogic::byLogicalId($mesure["_id"], 'netatmoWeather');
				if (!is_object($eqLogic)) {
					continue;
				}
				foreach ($mesure as $key => $value) {
					$cmd = $eqLogic->getCmd(null, strtolower($key));
					if (is_object($cmd)) {
						if ($key == 'max_temp') {
							$cmd->setCollectDate(date('Y-m-d H:i:s', $mesure['date_max_temp']));
						} else if ($key == 'min_temp') {
							$cmd->setCollectDate(date('Y-m-d H:i:s', $mesure['date_min_temp']));
						} else {
							$cmd->setCollectDate(date('Y-m-d H:i:s', $mesure['time_utc']));
						}
						$cmd->event($value);
					}
				}
				$mc = cache::byKey('netatmoWeatherWidgetmobile' . $eqLogic->getId());
				$mc->remove();
				$mc = cache::byKey('netatmoWeatherWidgetdashboard' . $eqLogic->getId());
				$mc->remove();
				$eqLogic->toHtml('mobile');
				$eqLogic->toHtml('dashboard');
				$eqLogic->refreshWidget();
			}
		} catch (Exception $e) {
			return '';
		}
	}

	/*     * *********************Methode d'instance************************* */

	public function postSave() {
		if (in_array($this->getConfiguration('type'), array('station'))) {
			$netatmoWeatherCmd = $this->getCmd(null, 'pressure');
			if (!is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd = new netatmoWeatherCmd();
				$netatmoWeatherCmd->setName(__('Pression', __FILE__));
				$netatmoWeatherCmd->setIsHistorized(1);
			}
			$netatmoWeatherCmd->setEqLogic_id($this->getId());
			$netatmoWeatherCmd->setLogicalId('pressure');
			$netatmoWeatherCmd->setUnite('Pa');
			$netatmoWeatherCmd->setType('info');
			$netatmoWeatherCmd->setSubType('numeric');
			$netatmoWeatherCmd->setEventOnly(1);
			$netatmoWeatherCmd->save();
		} else {
			$netatmoWeatherCmd = $this->getCmd(null, 'pressure');
			if (is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd->remove();
			}
		}

		if (in_array($this->getConfiguration('type'), array('station'))) {
			$netatmoWeatherCmd = $this->getCmd(null, 'absolutepressure');
			if (!is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd = new netatmoWeatherCmd();
				$netatmoWeatherCmd->setName(__('Pression Absolue', __FILE__));
				$netatmoWeatherCmd->setIsHistorized(1);
			}
			$netatmoWeatherCmd->setEqLogic_id($this->getId());
			$netatmoWeatherCmd->setLogicalId('absolutepressure');
			$netatmoWeatherCmd->setUnite('Pa');
			$netatmoWeatherCmd->setType('info');
			$netatmoWeatherCmd->setSubType('numeric');
			$netatmoWeatherCmd->setEventOnly(1);
			$netatmoWeatherCmd->save();
		} else {
			$netatmoWeatherCmd = $this->getCmd(null, 'absolutepressure');
			if (is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd->remove();
			}
		}

		if (in_array($this->getConfiguration('type'), array('module_int', 'station'))) {
			$netatmoWeatherCmd = $this->getCmd(null, 'co2');
			if (!is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd = new netatmoWeatherCmd();
				$netatmoWeatherCmd->setName(__('CO2', __FILE__));
				$netatmoWeatherCmd->setIsHistorized(1);
			}
			$netatmoWeatherCmd->setEqLogic_id($this->getId());
			$netatmoWeatherCmd->setLogicalId('co2');
			$netatmoWeatherCmd->setUnite('ppm');
			$netatmoWeatherCmd->setType('info');
			$netatmoWeatherCmd->setSubType('numeric');
			$netatmoWeatherCmd->setEventOnly(1);
			$netatmoWeatherCmd->save();
		} else {
			$netatmoWeatherCmd = $this->getCmd(null, 'co2');
			if (is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd->remove();
			}
		}

		if (in_array($this->getConfiguration('type'), array('station'))) {
			$netatmoWeatherCmd = $this->getCmd(null, 'noise');
			if (!is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd = new netatmoWeatherCmd();
				$netatmoWeatherCmd->setName(__('Noise', __FILE__));
			}
			$netatmoWeatherCmd->setEqLogic_id($this->getId());
			$netatmoWeatherCmd->setLogicalId('noise');
			$netatmoWeatherCmd->setUnite('db');
			$netatmoWeatherCmd->setType('info');
			$netatmoWeatherCmd->setSubType('numeric');
			$netatmoWeatherCmd->setIsHistorized(1);
			$netatmoWeatherCmd->setEventOnly(1);
			$netatmoWeatherCmd->save();
		} else {
			$netatmoWeatherCmd = $this->getCmd(null, 'noise');
			if (is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd->remove();
			}
		}

		if (in_array($this->getConfiguration('type'), array('module_ext', 'module_int', 'station'))) {
			$netatmoWeatherCmd = $this->getCmd(null, 'temperature');
			if (!is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd = new netatmoWeatherCmd();
				$netatmoWeatherCmd->setName(__('Température', __FILE__));
				$netatmoWeatherCmd->setIsHistorized(1);
			}
			$netatmoWeatherCmd->setEqLogic_id($this->getId());
			$netatmoWeatherCmd->setLogicalId('temperature');
			$netatmoWeatherCmd->setUnite('°C');
			$netatmoWeatherCmd->setType('info');
			$netatmoWeatherCmd->setSubType('numeric');
			$netatmoWeatherCmd->setEventOnly(1);
			$netatmoWeatherCmd->save();
		} else {
			$netatmoWeatherCmd = $this->getCmd(null, 'temperature');
			if (is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd->remove();
			}
		}

		if (in_array($this->getConfiguration('type'), array('module_ext', 'module_int', 'station'))) {
			$netatmoWeatherCmd = $this->getCmd(null, 'min_temp');
			if (!is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd = new netatmoWeatherCmd();
				$netatmoWeatherCmd->setName(__('Température min', __FILE__));
				$netatmoWeatherCmd->setIsHistorized(1);
			}
			$netatmoWeatherCmd->setEqLogic_id($this->getId());
			$netatmoWeatherCmd->setLogicalId('min_temp');
			$netatmoWeatherCmd->setUnite('°C');
			$netatmoWeatherCmd->setType('info');
			$netatmoWeatherCmd->setSubType('numeric');
			$netatmoWeatherCmd->setEventOnly(1);
			$netatmoWeatherCmd->save();
		} else {
			$netatmoWeatherCmd = $this->getCmd(null, 'min_temp');
			if (is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd->remove();
			}
		}

		if (in_array($this->getConfiguration('type'), array('module_ext', 'module_int', 'station'))) {
			$netatmoWeatherCmd = $this->getCmd(null, 'max_temp');
			if (!is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd = new netatmoWeatherCmd();
				$netatmoWeatherCmd->setName(__('Température max', __FILE__));
				$netatmoWeatherCmd->setIsHistorized(1);
			}
			$netatmoWeatherCmd->setEqLogic_id($this->getId());
			$netatmoWeatherCmd->setLogicalId('max_temp');
			$netatmoWeatherCmd->setUnite('°C');
			$netatmoWeatherCmd->setType('info');
			$netatmoWeatherCmd->setSubType('numeric');
			$netatmoWeatherCmd->setEventOnly(1);
			$netatmoWeatherCmd->save();
		} else {
			$netatmoWeatherCmd = $this->getCmd(null, 'max_temp');
			if (is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd->remove();
			}
		}

		if (in_array($this->getConfiguration('type'), array('module_ext', 'module_int', 'station'))) {
			$netatmoWeatherCmd = $this->getCmd(null, 'humidity');
			if (!is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd = new netatmoWeatherCmd();
				$netatmoWeatherCmd->setName(__('Humidité', __FILE__));
				$netatmoWeatherCmd->setIsHistorized(1);
			}
			$netatmoWeatherCmd->setEqLogic_id($this->getId());
			$netatmoWeatherCmd->setLogicalId('humidity');
			$netatmoWeatherCmd->setUnite('%');
			$netatmoWeatherCmd->setType('info');
			$netatmoWeatherCmd->setSubType('numeric');
			$netatmoWeatherCmd->setEventOnly(1);
			$netatmoWeatherCmd->save();
		} else {
			$netatmoWeatherCmd = $this->getCmd(null, 'humidity');
			if (is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd->remove();
			}
		}

		if (in_array($this->getConfiguration('type'), array('module_rain'))) {
			$netatmoWeatherCmd = $this->getCmd(null, 'rain');
			if (!is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd = new netatmoWeatherCmd();
				$netatmoWeatherCmd->setName(__('Rain', __FILE__));
				$netatmoWeatherCmd->setIsHistorized(1);
			}
			$netatmoWeatherCmd->setEqLogic_id($this->getId());
			$netatmoWeatherCmd->setLogicalId('rain');
			$netatmoWeatherCmd->setUnite('mm');
			$netatmoWeatherCmd->setType('info');
			$netatmoWeatherCmd->setSubType('numeric');
			$netatmoWeatherCmd->setEventOnly(1);
			$netatmoWeatherCmd->save();

			$netatmoWeatherCmd = $this->getCmd(null, 'sum_rain_24');
			if (!is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd = new netatmoWeatherCmd();
				$netatmoWeatherCmd->setName(__('Rain_24', __FILE__));
				$netatmoWeatherCmd->setIsHistorized(0);
			}
			$netatmoWeatherCmd->setEqLogic_id($this->getId());
			$netatmoWeatherCmd->setLogicalId('sum_rain_24');
			$netatmoWeatherCmd->setUnite('mm');
			$netatmoWeatherCmd->setType('info');
			$netatmoWeatherCmd->setSubType('numeric');

			$netatmoWeatherCmd->setEventOnly(1);
			$netatmoWeatherCmd->save();

			$netatmoWeatherCmd = $this->getCmd(null, 'sum_rain_1');
			if (!is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd = new netatmoWeatherCmd();
				$netatmoWeatherCmd->setName(__('Rain_1', __FILE__));
				$netatmoWeatherCmd->setIsHistorized(0);
			}

			$netatmoWeatherCmd->setEqLogic_id($this->getId());
			$netatmoWeatherCmd->setLogicalId('sum_rain_1');
			$netatmoWeatherCmd->setUnite('mm');
			$netatmoWeatherCmd->setType('info');
			$netatmoWeatherCmd->setSubType('numeric');
			$netatmoWeatherCmd->setEventOnly(1);
			$netatmoWeatherCmd->save();
		} else {
			$netatmoWeatherCmd = $this->getCmd(null, 'rain');
			if (is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd->remove();
			}
			$netatmoWeatherCmd = $this->getCmd(null, 'sum_rain_24');
			if (is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd->remove();
			}
			$netatmoWeatherCmd = $this->getCmd(null, 'sum_rain_1');
			if (is_object($netatmoWeatherCmd)) {
				$netatmoWeatherCmd->remove();
			}
		}
	}

	public function toHtml($_version = 'dashboard') {
		if ($this->getIsEnable() != 1) {
			return '';
		}
		if (!$this->hasRight('r')) {
			return '';
		}
		$version = jeedom::versionAlias($_version);
		if ($this->getDisplay('hideOn' . $version) == 1) {
			return '';
		}
		$mc = cache::byKey('netatmoWeatherWidget' . jeedom::versionAlias($_version) . $this->getId());
		if ($mc->getValue() != '') {
			return $mc->getValue();
		}
		$replace = array(
			'#name#' => $this->getName(),
			'#id#' => $this->getId(),
			'#background_color#' => $this->getBackgroundColor(jeedom::versionAlias($_version)),
			'#eqLink#' => $this->getLinkToConfiguration(),
		);
		foreach ($this->getCmd() as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			$replace['#' . $cmd->getLogicalId() . '_collectDate#'] = $cmd->getCollectDate();

		}
		if (($_version == 'dview' || $_version == 'mview') && $this->getDisplay('doNotShowNameOnView') == 1) {
			$replace['#name#'] = '';
			$replace['#object_name#'] = (is_object($object)) ? $object->getName() : '';
		}
		if (($_version == 'mobile' || $_version == 'dashboard') && $this->getDisplay('doNotShowNameOnDashboard') == 1) {
			$replace['#name#'] = '<br/>';
			$replace['#object_name#'] = (is_object($object)) ? $object->getName() : '';
		}
		$parameters = $this->getDisplay('parameters');
		if (is_array($parameters)) {
			foreach ($parameters as $key => $value) {
				$replace['#' . $key . '#'] = $value;
			}
		}
		if (config::byKey('alternativeDesign', 'netatmoWeather', 0) == 1) {
			$html = template_replace($replace, getTemplate('core', jeedom::versionAlias($_version), strtolower($this->getConfiguration('type')) . '_alt', 'netatmoWeather'));
		} else {
			$html = template_replace($replace, getTemplate('core', jeedom::versionAlias($_version), strtolower($this->getConfiguration('type')), 'netatmoWeather'));
		}
		cache::set('netatmoWeatherWidget' . jeedom::versionAlias($_version) . $this->getId(), $html, 0);
		return $html;
	}

}

class netatmoWeatherCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function dontRemoveCmd() {
		return true;
	}

	public function execute($_options = array()) {

	}

	/*     * **********************Getteur Setteur*************************** */
}

?>