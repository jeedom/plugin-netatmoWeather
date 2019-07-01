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
require_once __DIR__ . '/../../../../core/php/core.inc.php';
if (!class_exists('netatmoApi')) {
	require_once __DIR__ . '/netatmoApi.class.php';
}
class netatmoWeather extends eqLogic {
	/*     * *************************Attributs****************************** */
	
	private static $_client = null;
	public static $_widgetPossibility = array('custom' => true);
	
	/*     * ***********************Methode static*************************** */
	
	public static function getClient() {
		if (self::$_client == null) {
			self::$_client = new netatmoApi(array(
				'client_id' => config::byKey('client_id', 'netatmoWeather'),
				'client_secret' => config::byKey('client_secret', 'netatmoWeather'),
				'username' => config::byKey('username', 'netatmoWeather'),
				'password' => config::byKey('password', 'netatmoWeather'),
				'scope' => 'read_station',
			));
			self::$_client->getAccessToken();
		}
		return self::$_client;
	}
	
	public static function getFromWelcome() {
		$client_id = config::byKey('client_id', 'netatmoWelcome');
		$client_secret = config::byKey('client_secret', 'netatmoWelcome');
		$username = config::byKey('username', 'netatmoWelcome');
		$password = config::byKey('password', 'netatmoWelcome');
		return (array($client_id,$client_secret,$username,$password));
	}
	
	public static function getFromThermostat() {
		$client_id = config::byKey('client_id', 'netatmoThermostat');
		$client_secret = config::byKey('client_secret', 'netatmoThermostat');
		$username = config::byKey('username', 'netatmoThermostat');
		$password = config::byKey('password', 'netatmoThermostat');
		return (array($client_id,$client_secret,$username,$password));
	}
	
	public static function syncWithNetatmo() {
		$getFriends = config::byKey('getFriendsDevices', 'netatmoWeather', 0);
		$devicelist = self::getClient()->api("devicelist", "POST", array("app_type" => 'app_station'));
		log::add('netatmoWeather', 'debug', json_encode($devicelist));
		foreach ($devicelist['devices'] as $device) {
			$eqLogic = eqLogic::byLogicalId($device['_id'], 'netatmoWeather');
			if (isset($device['read_only']) && $device['read_only'] === true && ($getFriends == '' || $getFriends == 0)) {
				continue;
			}
			if (!is_object($eqLogic)) {
				$eqLogic = new netatmoWeather();
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
			}
			$eqLogic->setEqType_name('netatmoWeather');
			$eqLogic->setName($device['station_name']);
			$eqLogic->setLogicalId($device['_id']);
			$eqLogic->setConfiguration('type', 'station');
			$eqLogic->setCategory('heating', 1);
			$eqLogic->save();
			foreach ($device['modules'] as $module) {
				$battery_type = '';
				if ($module['type'] == "NAModule1") {
					$type = 'module_ext';
					$battery_type = '4x1.5V AAA';
				} elseif ($module['type'] == "NAModule4") {
					$type = 'module_int';
					$battery_type = '4x1.5V AAA';
				} elseif ($module['type'] == "NAModule3") {
					$type = 'module_rain';
					$battery_type = '2x1.5V AAA';
				} elseif ($module['type'] == "NAModule2") {
					$type = 'module_wind';
					$battery_type = '4x1.5V AA';
				}
				$eqLogic = eqLogic::byLogicalId($module['_id'], 'netatmoWeather');
				if (!is_object($eqLogic)) {
					$eqLogic = new netatmoWeather();
				}
				$eqLogic->setConfiguration('battery_type', $battery_type);
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
			try {
				$devicelist = self::getClient()->api("devicelist", "POST", array("app_type" => 'app_station'));
				if (config::byKey('numberFailed', 'netatmoWeather', 0) > 0) {
					config::save('numberFailed', 0, 'netatmoWeather');
				}
			} catch (Exception $ex) {
				if (config::byKey('numberFailed', 'netatmoWeather', 0) > 3) {
					log::add('netatmoWeather', 'error', __('Erreur sur synchro netatmo weather ', __FILE__) . ' (' . config::byKey('numberFailed', 'netatmoWeather', 0) . ') ' . $ex->getMessage());
				} else {
					config::save('numberFailed', config::byKey('numberFailed', 'netatmoWeather', 0) + 1, 'netatmoWeather');
				}
				return;
			}
			foreach ($devicelist['devices'] as $device) {
				$eqLogic = eqLogic::byLogicalId($device["_id"], 'netatmoWeather');
				if (!is_object($eqLogic)) {
					continue;
				}
				if ($eqLogic->getConfiguration('firmware') != $device['firmware']) {
					$eqLogic->setConfiguration('firmware', $device['firmware']);
				}
				if ($eqLogic->getConfiguration('wifi_status') != $device['wifi_status']) {
					$eqLogic->setConfiguration('wifi_status', $device['wifi_status']);
				}
				$eqLogic->save();
				foreach ($device['dashboard_data'] as $key => $value) {
					if ($key == 'max_temp') {
						$collectDate = date('Y-m-d H:i:s', $device['dashboard_data']['date_max_temp']);
					} else if ($key == 'min_temp') {
						$collectDate = date('Y-m-d H:i:s', $device['dashboard_data']['date_min_temp']);
					} else if ($key == 'max_wind_str') {
						$collectDate = date('Y-m-d H:i:s', $device['dashboard_data']['date_max_wind_str']);
					} else {
						$collectDate = date('Y-m-d H:i:s', $device['dashboard_data']['time_utc']);
					}
					$eqLogic->checkAndUpdateCmd(strtolower($key),$value,$collectDate);
				}
			}
			if(count($devicelist['modules']) > 0){
				foreach ($devicelist['modules'] as $module) {
					$eqLogic = eqLogic::byLogicalId($module["_id"], 'netatmoWeather');
					if ($eqLogic->getConfiguration('rf_status') != $module['rf_status']) {
						$eqLogic->setConfiguration('rf_status', $module['rf_status']);
					}
					if ($eqLogic->getConfiguration('firmware') != $module['firmware']) {
						$eqLogic->setConfiguration('firmware', $module['firmware']);
					}
					$eqLogic->save();
					$battery_max = null;
					$battery_min = null;
					if ($module['type'] == 'NAModule1') {
						$battery_max = 6000;
						$battery_min = 3600;
					}else if ($module['type'] == 'NAModule4') {
						$battery_max = 6000;
						$battery_min = 4200;
					}else if ($module['type'] == 'NAModule3') {
						$battery_max = 6000;
						$battery_min = 3600;
					}else if ($module['type'] == 'NAModule2') {
						$battery_max = 6000;
						$battery_min = 3950;
					}
					if ($battery_max != null && $battery_min != null) {
						$battery = round(($module['battery_vp'] - $battery_min) / ($battery_max - $battery_min) * 100, 0);
					}
					$eqLogic->batteryStatus($battery);
					
					foreach ($module['dashboard_data'] as $key => $value) {
						if ($key == 'max_temp') {
							$collectDate = date('Y-m-d H:i:s', $module['dashboard_data']['date_max_temp']);
						} else if ($key == 'min_temp') {
							$collectDate = date('Y-m-d H:i:s', $module['dashboard_data']['date_min_temp']);
						} else if ($key == 'max_wind_str') {
							$collectDate = date('Y-m-d H:i:s', $module['dashboard_data']['date_max_wind_str']);
						} else {
							$collectDate = date('Y-m-d H:i:s', $module['dashboard_data']['time_utc']);
						}
						$eqLogic->checkAndUpdateCmd(strtolower($key),$value,$collectDate);
					}
				}
			}
		} catch (Exception $e) {
			return '';
		}
	}
	
	/*     * *********************Methode d'instance************************* */
	
	public function postSave() {
		if (in_array($this->getConfiguration('type'), array('station'))) {
			$cmd = $this->getCmd(null, 'pressure');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Pression', __FILE__));
				$cmd->setIsHistorized(1);
				$cmd->setConfiguration('minValue',0);
				$cmd->setConfiguration('maxValue',1500);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('pressure');
			$cmd->setUnite('Pa');
			$cmd->setType('info');
			$cmd->setSubType('numeric');
			$cmd->setGeneric_type('PRESSURE');
			$cmd->save();
		} else {
			$cmd = $this->getCmd(null, 'pressure');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}
		
		if (in_array($this->getConfiguration('type'), array('station'))) {
			$cmd = $this->getCmd(null, 'absolutepressure');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Pression Absolue', __FILE__));
				$cmd->setIsHistorized(1);
				$cmd->setConfiguration('minValue',0);
				$cmd->setConfiguration('maxValue',1500);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('absolutepressure');
			$cmd->setUnite('Pa');
			$cmd->setType('info');
			$cmd->setSubType('numeric');
			$cmd->setGeneric_type('PRESSURE');
			$cmd->save();
		} else {
			$cmd = $this->getCmd(null, 'absolutepressure');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}
		
		if (in_array($this->getConfiguration('type'), array('module_int', 'station'))) {
			$cmd = $this->getCmd(null, 'co2');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('CO2', __FILE__));
				$cmd->setIsHistorized(1);
				$cmd->setConfiguration('minValue',0);
				$cmd->setConfiguration('maxValue',1000);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('co2');
			$cmd->setUnite('ppm');
			$cmd->setType('info');
			$cmd->setSubType('numeric');
			$cmd->setGeneric_type('CO2');
			$cmd->save();
		} else {
			$cmd = $this->getCmd(null, 'co2');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}
		
		if (in_array($this->getConfiguration('type'), array('station'))) {
			$cmd = $this->getCmd(null, 'noise');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Noise', __FILE__));
				$cmd->setConfiguration('minValue',0);
				$cmd->setConfiguration('maxValue',80);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('noise');
			$cmd->setUnite('db');
			$cmd->setType('info');
			$cmd->setGeneric_type('NOISE');
			$cmd->setSubType('numeric');
			$cmd->setIsHistorized(1);
			$cmd->save();
		} else {
			$cmd = $this->getCmd(null, 'noise');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}
		
		if (in_array($this->getConfiguration('type'), array('module_ext', 'module_int', 'station'))) {
			$cmd = $this->getCmd(null, 'temperature');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Température', __FILE__));
				$cmd->setIsHistorized(1);
				$cmd->setConfiguration('minValue',0);
				$cmd->setConfiguration('maxValue',80);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('temperature');
			$cmd->setUnite('°C');
			$cmd->setType('info');
			$cmd->setGeneric_type('TEMPERATURE');
			$cmd->setSubType('numeric');
			$cmd->save();
		} else {
			$cmd = $this->getCmd(null, 'temperature');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}
		
		if (in_array($this->getConfiguration('type'), array('module_ext', 'module_int', 'station'))) {
			$cmd = $this->getCmd(null, 'min_temp');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Température min', __FILE__));
				$cmd->setIsHistorized(1);
				$cmd->setConfiguration('minValue',0);
				$cmd->setConfiguration('maxValue',80);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('min_temp');
			$cmd->setUnite('°C');
			$cmd->setType('info');
			$cmd->setGeneric_type('TEMPERATURE');
			$cmd->setSubType('numeric');
			$cmd->save();
		} else {
			$cmd = $this->getCmd(null, 'min_temp');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}
		
		if (in_array($this->getConfiguration('type'), array('module_ext', 'module_int', 'station'))) {
			$cmd = $this->getCmd(null, 'max_temp');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Température max', __FILE__));
				$cmd->setIsHistorized(1);
				$cmd->setConfiguration('minValue',0);
				$cmd->setConfiguration('maxValue',80);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('max_temp');
			$cmd->setUnite('°C');
			$cmd->setType('info');
			$cmd->setGeneric_type('TEMPERATURE');
			$cmd->setSubType('numeric');
			$cmd->save();
		} else {
			$cmd = $this->getCmd(null, 'max_temp');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}
		
		if (in_array($this->getConfiguration('type'), array('module_ext', 'module_int', 'station'))) {
			$cmd = $this->getCmd(null, 'humidity');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Humidité', __FILE__));
				$cmd->setIsHistorized(1);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('humidity');
			$cmd->setUnite('%');
			$cmd->setType('info');
			$cmd->setGeneric_type('HUMIDITY');
			$cmd->setSubType('numeric');
			$cmd->save();
		} else {
			$cmd = $this->getCmd(null, 'humidity');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}
		
		if (in_array($this->getConfiguration('type'), array('module_rain'))) {
			$cmd = $this->getCmd(null, 'rain');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Pluie', __FILE__));
				$cmd->setIsHistorized(1);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('rain');
			$cmd->setUnite('mm');
			$cmd->setType('info');
			$cmd->setGeneric_type('RAIN_TOTAL');
			$cmd->setSubType('numeric');
			$cmd->save();
			
			$cmd = $this->getCmd(null, 'sum_rain_24');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Pluie 24H', __FILE__));
				$cmd->setIsHistorized(0);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('sum_rain_24');
			$cmd->setUnite('mm');
			$cmd->setType('info');
			$cmd->setGeneric_type('RAIN_TOTAL');
			$cmd->setSubType('numeric');
			$cmd->save();
			
			$cmd = $this->getCmd(null, 'sum_rain_1');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Pluie 1H', __FILE__));
				$cmd->setIsHistorized(0);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('sum_rain_1');
			$cmd->setUnite('mm');
			$cmd->setType('info');
			$cmd->setGeneric_type('RAIN_TOTAL');
			$cmd->setSubType('numeric');
			$cmd->save();
		} else {
			$cmd = $this->getCmd(null, 'rain');
			if (is_object($cmd)) {
				$cmd->remove();
			}
			$cmd = $this->getCmd(null, 'sum_rain_24');
			if (is_object($cmd)) {
				$cmd->remove();
			}
			$cmd = $this->getCmd(null, 'sum_rain_1');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}
		
		if (in_array($this->getConfiguration('type'), array('module_wind'))) {
			$cmd = $this->getCmd(null, 'windangle');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Direction Vent', __FILE__));
				$cmd->setIsHistorized(1);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('windangle');
			$cmd->setUnite('°');
			$cmd->setType('info');
			$cmd->setGeneric_type('WIND_DIRECTION');
			$cmd->setSubType('numeric');
			$cmd->save();
			
			$cmd = $this->getCmd(null, 'windstrength');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Vitesse Vent', __FILE__));
				$cmd->setIsHistorized(1);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('windstrength');
			$cmd->setUnite('km/h');
			$cmd->setType('info');
			$cmd->setGeneric_type('WIND_SPEED');
			$cmd->setSubType('numeric');
			$cmd->save();
			
			$cmd = $this->getCmd(null, 'gustangle');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Direction rafale', __FILE__));
				$cmd->setIsHistorized(0);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('gustangle');
			$cmd->setUnite('°');
			$cmd->setType('info');
			$cmd->setSubType('numeric');
			$cmd->save();
			
			$cmd = $this->getCmd(null, 'guststrength');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Vitesse rafale', __FILE__));
				$cmd->setIsHistorized(0);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('guststrength');
			$cmd->setUnite('km/h');
			$cmd->setType('info');
			$cmd->setSubType('numeric');
			$cmd->save();
			
			$cmd = $this->getCmd(null, 'max_wind_str');
			if (!is_object($cmd)) {
				$cmd = new netatmoWeatherCmd();
				$cmd->setName(__('Vitesse Max', __FILE__));
				$cmd->setIsHistorized(0);
			}
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId('max_wind_str');
			$cmd->setUnite('km/h');
			$cmd->setType('info');
			$cmd->setSubType('numeric');
			$cmd->save();
		} else {
			$cmd = $this->getCmd(null, 'windangle');
			if (is_object($cmd)) {
				$cmd->remove();
			}
			$cmd = $this->getCmd(null, 'windstrength');
			if (is_object($cmd)) {
				$cmd->remove();
			}
			$cmd = $this->getCmd(null, 'gustangle');
			if (is_object($cmd)) {
				$cmd->remove();
			}
			$cmd = $this->getCmd(null, 'guststrength');
			if (is_object($cmd)) {
				$cmd->remove();
			}
			$cmd = $this->getCmd(null, 'max_wind_str');
			if (is_object($cmd)) {
				$cmd->remove();
			}
		}
		
		$cmd = $this->getCmd(null, 'refresh');
		if (!is_object($cmd)) {
			$cmd = new netatmoWeatherCmd();
			$cmd->setName(__('Rafraichir', __FILE__));
		}
		$cmd->setEqLogic_id($this->getId());
		$cmd->setLogicalId('refresh');
		$cmd->setType('action');
		$cmd->setSubType('other');
		$cmd->save();
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
		if ($this->getLogicalId() == 'refresh') {
			netatmoWeather::cron15();
		}
	}
	
	/*     * **********************Getteur Setteur*************************** */
}

?>
