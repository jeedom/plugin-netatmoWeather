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
	private static $_globalConfig = null;
	public static $_widgetPossibility = array('custom' => true);
	public static $_encryptConfigKey = array('password','client_secret');
	
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
		}
		return self::$_client;
	}
	
	public static function getGConfig($_key){
		$keys = explode('::',$_key);
		if(self::$_globalConfig == null){
			self::$_globalConfig = json_decode(file_get_contents(__DIR__.'/../config/config.json'),true);
		}
		$return = self::$_globalConfig;
		foreach ($keys as $key) {
			if(!isset($return[$key])){
				return '';
			}
			$return = $return[$key];
		}
		return $return;
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
		foreach ($devicelist['devices'] as &$device) {
			$eqLogic = eqLogic::byLogicalId($device['_id'], 'netatmoWeather');
			if (isset($device['read_only']) && $device['read_only'] === true && ($getFriends == '' || $getFriends == 0)) {
				continue;
			}
			if(!isset($device['station_name']) || $device['station_name'] == ''){
				$device['station_name'] = $device['_id'];
			}
			if (!is_object($eqLogic)) {
				$eqLogic = new netatmoWeather();
				$eqLogic->setIsVisible(1);
				$eqLogic->setIsEnable(1);
				$eqLogic->setName($device['station_name']);
				$eqLogic->setCategory('heating', 1);
			}
			$eqLogic->setEqType_name('netatmoWeather');
			$eqLogic->setLogicalId($device['_id']);
			$eqLogic->setConfiguration('type', $device['type']);
			$eqLogic->save();
		}
		foreach ($devicelist['modules'] as &$module) {
			$eqLogic = eqLogic::byLogicalId($module['_id'], 'netatmoWeather');
			if(!isset($module['module_name']) || $module['module_name'] == ''){
				$module['module_name'] = $module['_id'];
			}
			if (!is_object($eqLogic)) {
				$eqLogic = new netatmoWeather();
				$eqLogic->setName($module['module_name']);
				$eqLogic->setIsEnable(1);
				$eqLogic->setCategory('heating', 1);
				$eqLogic->setIsVisible(1);
			}
			$eqLogic->setConfiguration('battery_type', self::getGConfig($module['type'].'::bat_type'));
			$eqLogic->setEqType_name('netatmoWeather');
			$eqLogic->setLogicalId($module['_id']);
			$eqLogic->setConfiguration('type', $module['type']);
			$eqLogic->save();
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
					return;
				}
				config::save('numberFailed', config::byKey('numberFailed', 'netatmoWeather', 0) + 1, 'netatmoWeather');
				return;
			}
			foreach ($devicelist['devices'] as $device) {
				$eqLogic = eqLogic::byLogicalId($device["_id"], 'netatmoWeather');
				if (!is_object($eqLogic)) {
					continue;
				}
				$eqLogic->setConfiguration('firmware', $device['firmware']);
				$eqLogic->setConfiguration('wifi_status', $device['wifi_status']);
				$eqLogic->save(true);
				if(isset($device['dashboard_data']) && count($device['dashboard_data']) > 0){
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
			}
			if(isset($devicelist['modules']) &&  count($devicelist['modules']) > 0){
				foreach ($devicelist['modules'] as $module) {
					$eqLogic = eqLogic::byLogicalId($module["_id"], 'netatmoWeather');
					if(!is_object($eqLogic)){
						continue;
					}
					$eqLogic->setConfiguration('rf_status', $module['rf_status']);
					$eqLogic->setConfiguration('firmware', $module['firmware']);
					$eqLogic->save(true);
					$eqLogic->batteryStatus(round(($module['battery_vp'] - self::getGConfig($module['type'].'::bat_min')) / (self::getGConfig($module['type'].'::bat_max') - self::getGConfig($module['type'].'::bat_min')) * 100, 0));
					
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
		if ($this->getConfiguration('applyType') != $this->getConfiguration('type')) {
			$this->applyType();
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
	
	public function applyType(){
		$this->setConfiguration('applyType', $this->getConfiguration('type'));
		$supported_commands = self::getGConfig($this->getConfiguration('type').'::cmd');
		$commands = array('commands');
		foreach ($supported_commands as $supported_command) {
			$commands['commands'][] = self::getGConfig('commands::'.$supported_command);
		}
		$this->import($commands);
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
