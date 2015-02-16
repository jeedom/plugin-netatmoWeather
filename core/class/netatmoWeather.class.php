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

    public static function getDevicesList($_options) {

		$client = new NAApiClient(array("client_id" => $_POST['client_id'], "client_secret" => $_POST['client_secret'], "username" => $_POST['username'], "password" => $_POST['password'], "scope" => NAScopes::SCOPE_READ_STATION));
        $helper = new NAApiHelper($client);
		try {
			$tokens = $client->getAccessToken();
		} catch(NAClientException $ex) {
 			echo "An error happend while trying to retrieve your tokens\n";
 			exit(-1);
		}
		$user = $helper->api("getuser", "POST");
        $devicelist = $helper->simplifyDeviceList();
		return $devicelist;

    }
    
	public function saveDevicesList($_options) {

		$client = new NAApiClient(array("client_id" => $_POST['client_id'], "client_secret" => $_POST['client_secret'], "username" => $_POST['username'], "password" => $_POST['password'], "scope" => NAScopes::SCOPE_READ_STATION));
        $helper = new NAApiHelper($client);
		try {
			$tokens = $client->getAccessToken();
		} catch(NAClientException $ex) {
 			echo "An error happend while trying to retrieve your tokens\n";
 			exit(-1);
		}
		$user = $helper->api("getuser", "POST");
        $devicelist = $helper->simplifyDeviceList();
		$eqLogics = eqLogic::byType('netatmoWeather');
		foreach ($eqLogics as $eqLogic) {
			if($eqLogic->getConfiguration('station_id')==""){
				$newEq=$eqLogic;
			}
		}
		foreach ($devicelist['devices'] as $device) {
			$eqLogics = eqLogic::byTypeAndSearhConfiguration('netatmoWeather',$device['_id']);
			if(count($eqLogics) == 0){
				log::add('netatmoWeather', 'info', 'Station non trouvée, création', 'config');
				if($newEq){
					$eqLogic = $newEq;
				}else{
				$eqLogic = new eqLogic();
				}
	            $eqLogic->setEqType_name('netatmoWeather');
	            $eqLogic->setIsEnable(1);
	            $eqLogic->setName($device['station_name']);
	            $eqLogic->setConfiguration('client_id',$_POST['client_id']);
				$eqLogic->setConfiguration('client_secret',$_POST['client_secret']);
				$eqLogic->setConfiguration('username',$_POST['username']);
	            $eqLogic->setConfiguration('password',$_POST['password']);
	            $eqLogic->setConfiguration('station_id',$device['_id']);
				$eqLogic->setConfiguration('type','Station');
				$eqLogic->setCategory('heating', 1);
	            $eqLogic->setIsVisible(1);
	            $eqLogic->save();
	            $eqLogic = self::byId($eqLogic->getId());
	            $include_device = $eqLogic->getId();
				
				$netatmoWeatherCmd = new netatmoWeatherCmd();
		        $netatmoWeatherCmd->setName(__('Température', __FILE__));
		        $netatmoWeatherCmd->setEqLogic_id($include_device);
		        $netatmoWeatherCmd->setConfiguration('data', 'temp');
		        $netatmoWeatherCmd->setUnite('°C');
		        $netatmoWeatherCmd->setType('info');
		        $netatmoWeatherCmd->setSubType('numeric');
				$netatmoWeatherCmd->setIsHistorized(1);
		        $netatmoWeatherCmd->save();
		
		        $netatmoWeatherCmd = new netatmoWeatherCmd();
		        $netatmoWeatherCmd->setName(__('Humidité', __FILE__));
		        $netatmoWeatherCmd->setEqLogic_id($include_device);
		        $netatmoWeatherCmd->setConfiguration('data', 'humidity');
		        $netatmoWeatherCmd->setUnite('%');
		        $netatmoWeatherCmd->setType('info');
		        $netatmoWeatherCmd->setSubType('numeric');
				$netatmoWeatherCmd->setIsHistorized(1);
		        $netatmoWeatherCmd->save();
		        
		        $netatmoWeatherCmd = new netatmoWeatherCmd();
		        $netatmoWeatherCmd->setName(__('Pression', __FILE__));
		        $netatmoWeatherCmd->setEqLogic_id($include_device);
		        $netatmoWeatherCmd->setConfiguration('data', 'pressure');
				$netatmoWeatherCmd->setUnite('Pa');
		        $netatmoWeatherCmd->setType('info');
		        $netatmoWeatherCmd->setSubType('numeric');
				$netatmoWeatherCmd->setIsHistorized(1);
		        $netatmoWeatherCmd->save();
		
		        $netatmoWeatherCmd = new netatmoWeatherCmd();
		        $netatmoWeatherCmd->setName(__('CO2', __FILE__));
		        $netatmoWeatherCmd->setEqLogic_id($include_device);
		        $netatmoWeatherCmd->setConfiguration('data', 'CO2');
				$netatmoWeatherCmd->setUnite('ppm');
		        $netatmoWeatherCmd->setType('info');
		        $netatmoWeatherCmd->setSubType('numeric');
				$netatmoWeatherCmd->setIsHistorized(1);
		        $netatmoWeatherCmd->save();        
		
		        $netatmoWeatherCmd = new netatmoWeatherCmd();
		        $netatmoWeatherCmd->setName(__('Noise', __FILE__));
		        $netatmoWeatherCmd->setEqLogic_id($include_device);
		        $netatmoWeatherCmd->setConfiguration('data', 'noise');
				$netatmoWeatherCmd->setUnite('db');
		        $netatmoWeatherCmd->setType('info');
		        $netatmoWeatherCmd->setSubType('numeric');
				$netatmoWeatherCmd->setIsHistorized(1);
		        $netatmoWeatherCmd->save();
			}
			
			//log::add('netatmoWeather', 'info', $device['_id'], 'config');
			foreach ($device['modules'] as $module) {
				if($module['type']=="NAModule1"){
					$type='module_ext';
				}elseif($module['type']=="NAModule4"){
					$type='module_int';
				}elseif($module['type']=="NAModule3"){
					$type='module_rain';
				}
				$eqLogics = eqLogic::byTypeAndSearhConfiguration('netatmoWeather',$module['_id']);
				if(count($eqLogics) == 0){
					log::add('netatmoWeather', 'info', 'Module non trouvé, création', 'config');
					$eqLogic = new eqLogic();
					$eqLogic->setEqType_name('netatmoWeather');
		            $eqLogic->setIsEnable(1);
		            $eqLogic->setName($module['module_name']);
		            $eqLogic->setConfiguration('client_id',$_POST['client_id']);
					$eqLogic->setConfiguration('client_secret',$_POST['client_secret']);
					$eqLogic->setConfiguration('username',$_POST['username']);
		            $eqLogic->setConfiguration('password',$_POST['password']);
		            $eqLogic->setConfiguration('station_id',$module['_id']);
					$eqLogic->setConfiguration('type',$type);
					$eqLogic->setCategory('heating', 1);
		            $eqLogic->setIsVisible(1);
		            $eqLogic->save();
		            $eqLogic = self::byId($eqLogic->getId());
		            $include_device = $eqLogic->getId();
					
					if(in_array($type, array('module_ext','module_int'))){
						$netatmoWeatherCmd = new netatmoWeatherCmd();
				        $netatmoWeatherCmd->setName(__('Température', __FILE__));
				        $netatmoWeatherCmd->setEqLogic_id($include_device);
				        $netatmoWeatherCmd->setConfiguration('data', 'temp');
				        $netatmoWeatherCmd->setUnite('°C');
				        $netatmoWeatherCmd->setType('info');
				        $netatmoWeatherCmd->setSubType('numeric');
						$netatmoWeatherCmd->setIsHistorized(1);
				        $netatmoWeatherCmd->save();
					}
					
					if(in_array($type, array('module_ext','module_int'))){
				        $netatmoWeatherCmd = new netatmoWeatherCmd();
				        $netatmoWeatherCmd->setName(__('Humidité', __FILE__));
				        $netatmoWeatherCmd->setEqLogic_id($include_device);
				        $netatmoWeatherCmd->setConfiguration('data', 'humidity');
				        $netatmoWeatherCmd->setUnite('%');
				        $netatmoWeatherCmd->setType('info');
				        $netatmoWeatherCmd->setSubType('numeric');
						$netatmoWeatherCmd->setIsHistorized(1);
				        $netatmoWeatherCmd->save();
					}
			        
					if(in_array($type, array('module_int'))){
				        $netatmoWeatherCmd = new netatmoWeatherCmd();
				        $netatmoWeatherCmd->setName(__('CO2', __FILE__));
				        $netatmoWeatherCmd->setEqLogic_id($include_device);
				        $netatmoWeatherCmd->setConfiguration('data', 'CO2');
						$netatmoWeatherCmd->setUnite('ppm');
				        $netatmoWeatherCmd->setType('info');
				        $netatmoWeatherCmd->setSubType('numeric');
						$netatmoWeatherCmd->setIsHistorized(1);
				        $netatmoWeatherCmd->save();        
					}
					
					if(in_array($type, array('module_rain'))){
			        	$netatmoWeatherCmd = new netatmoWeatherCmd();
				        $netatmoWeatherCmd->setName(__('Rain', __FILE__));
				        $netatmoWeatherCmd->setEqLogic_id($include_device);
				        $netatmoWeatherCmd->setConfiguration('data', 'rain');
						$netatmoWeatherCmd->setUnite('mm');
				        $netatmoWeatherCmd->setType('info');
				        $netatmoWeatherCmd->setSubType('numeric');
						$netatmoWeatherCmd->setIsHistorized(1);
				        $netatmoWeatherCmd->save();
						
						$netatmoWeatherCmd = new netatmoWeatherCmd();
				        $netatmoWeatherCmd->setName(__('Rain_24', __FILE__));
				        $netatmoWeatherCmd->setEqLogic_id($include_device);
				        $netatmoWeatherCmd->setConfiguration('data', 'sum_rain_24');
						$netatmoWeatherCmd->setUnite('mm');
				        $netatmoWeatherCmd->setType('info');
				        $netatmoWeatherCmd->setSubType('numeric');
						$netatmoWeatherCmd->setIsHistorized(0);
				        $netatmoWeatherCmd->save();
						
						$netatmoWeatherCmd = new netatmoWeatherCmd();
				        $netatmoWeatherCmd->setName(__('Rain_1', __FILE__));
				        $netatmoWeatherCmd->setEqLogic_id($include_device);
				        $netatmoWeatherCmd->setConfiguration('data', 'sum_rain_1');
						$netatmoWeatherCmd->setUnite('mm');
				        $netatmoWeatherCmd->setType('info');
				        $netatmoWeatherCmd->setSubType('numeric');
						$netatmoWeatherCmd->setIsHistorized(0);
				        $netatmoWeatherCmd->save();
					}
				}
				//log::add('netatmoWeather', 'info', $type, 'config');
			}
		}
		
		return $devicelist;

    }
	
	
    public static function pull($_options) {
    	foreach (eqLogic::byType('netatmoWeather') as $weather) {
			//log::add('netatmoWeather', 'info', $weather->getName(), 'config');	
			if (is_object($weather)) {
				foreach ($weather->getCmd() as $cmd) {
                	$cmd->event($cmd->execute());
            	}
        	}			
		}
    }


    /*     * *********************Methode d'instance************************* */

    public function preUpdate() {
        if ($this->getConfiguration('client_id') == '') {
            throw new Exception(__('Le client id ne peut être vide', __FILE__));
        }
        $this->setCategory('heating', 1);		
	}
	
	public function postUpdate() {
		if ($this->getConfiguration('type') == 'Station') {
			foreach ($this->getCmd() as $cmd) {
				if($cmd->getConfiguration('data') == 'rain' || $cmd->getConfiguration('data') == 'sum_rain_24' || $cmd->getConfiguration('data') == 'sum_rain_1'){
					$cmd->remove();	
				}
        	}
    	}else if ($this->getConfiguration('type') == 'module_int') {
			foreach ($this->getCmd() as $cmd) {
				if($cmd->getConfiguration('data') == 'pressure' || $cmd->getConfiguration('data') == 'noise' || $cmd->getConfiguration('data') == 'rain' || $cmd->getConfiguration('data') == 'sum_rain_24' || $cmd->getConfiguration('data') == 'sum_rain_1'){
					$cmd->remove();	
				}
        	}
    	}else if ($this->getConfiguration('type') == 'module_rain') {
			foreach ($this->getCmd() as $cmd) {
				if($cmd->getConfiguration('data') == 'pressure' || $cmd->getConfiguration('data') == 'CO2' || $cmd->getConfiguration('data') == 'noise' || $cmd->getConfiguration('data') == 'temp' || $cmd->getConfiguration('data') == 'humidity'){
					$cmd->remove();	
				}
        	}
    	}else if ($this->getConfiguration('type') == 'module_ext') {
			foreach ($this->getCmd() as $cmd) {
				if($cmd->getConfiguration('data') == 'pressure' || $cmd->getConfiguration('data') == 'CO2' || $cmd->getConfiguration('data') == 'noise' || $cmd->getConfiguration('data') == 'rain' || $cmd->getConfiguration('data') == 'sum_rain_24' || $cmd->getConfiguration('data') == 'sum_rain_1'){
					$cmd->remove();	
				}
        	}
    	}
	}

    public function postInsert() {
    	if(1==2){
        $netatmoWeatherCmd = new netatmoWeatherCmd();
        $netatmoWeatherCmd->setName(__('Température', __FILE__));
        $netatmoWeatherCmd->setEqLogic_id($this->id);
        $netatmoWeatherCmd->setConfiguration('data', 'temp');
        $netatmoWeatherCmd->setUnite('°C');
        $netatmoWeatherCmd->setType('info');
        $netatmoWeatherCmd->setSubType('numeric');
		$netatmoWeatherCmd->setIsHistorized(1);
        $netatmoWeatherCmd->save();

        $netatmoWeatherCmd = new netatmoWeatherCmd();
        $netatmoWeatherCmd->setName(__('Humidité', __FILE__));
        $netatmoWeatherCmd->setEqLogic_id($this->id);
        $netatmoWeatherCmd->setConfiguration('data', 'humidity');
        $netatmoWeatherCmd->setUnite('%');
        $netatmoWeatherCmd->setType('info');
        $netatmoWeatherCmd->setSubType('numeric');
		$netatmoWeatherCmd->setIsHistorized(1);
        $netatmoWeatherCmd->save();
        
        $netatmoWeatherCmd = new netatmoWeatherCmd();
        $netatmoWeatherCmd->setName(__('Pression', __FILE__));
        $netatmoWeatherCmd->setEqLogic_id($this->id);
        $netatmoWeatherCmd->setConfiguration('data', 'pressure');
		$netatmoWeatherCmd->setUnite('Pa');
        $netatmoWeatherCmd->setType('info');
        $netatmoWeatherCmd->setSubType('numeric');
		$netatmoWeatherCmd->setIsHistorized(1);
        $netatmoWeatherCmd->save();

        $netatmoWeatherCmd = new netatmoWeatherCmd();
        $netatmoWeatherCmd->setName(__('CO2', __FILE__));
        $netatmoWeatherCmd->setEqLogic_id($this->id);
        $netatmoWeatherCmd->setConfiguration('data', 'CO2');
		$netatmoWeatherCmd->setUnite('ppm');
        $netatmoWeatherCmd->setType('info');
        $netatmoWeatherCmd->setSubType('numeric');
		$netatmoWeatherCmd->setIsHistorized(1);
        $netatmoWeatherCmd->save();        

        $netatmoWeatherCmd = new netatmoWeatherCmd();
        $netatmoWeatherCmd->setName(__('Noise', __FILE__));
        $netatmoWeatherCmd->setEqLogic_id($this->id);
        $netatmoWeatherCmd->setConfiguration('data', 'noise');
		$netatmoWeatherCmd->setUnite('db');
        $netatmoWeatherCmd->setType('info');
        $netatmoWeatherCmd->setSubType('numeric');
		$netatmoWeatherCmd->setIsHistorized(1);
        $netatmoWeatherCmd->save();
		
		$netatmoWeatherCmd = new netatmoWeatherCmd();
        $netatmoWeatherCmd->setName(__('Rain', __FILE__));
        $netatmoWeatherCmd->setEqLogic_id($this->id);
        $netatmoWeatherCmd->setConfiguration('data', 'rain');
		$netatmoWeatherCmd->setUnite('mm');
        $netatmoWeatherCmd->setType('info');
        $netatmoWeatherCmd->setSubType('numeric');
		$netatmoWeatherCmd->setIsHistorized(1);
        $netatmoWeatherCmd->save();
		
		$netatmoWeatherCmd = new netatmoWeatherCmd();
        $netatmoWeatherCmd->setName(__('Rain_24', __FILE__));
        $netatmoWeatherCmd->setEqLogic_id($this->id);
        $netatmoWeatherCmd->setConfiguration('data', 'sum_rain_24');
		$netatmoWeatherCmd->setUnite('mm');
        $netatmoWeatherCmd->setType('info');
        $netatmoWeatherCmd->setSubType('numeric');
		$netatmoWeatherCmd->setIsHistorized(0);
        $netatmoWeatherCmd->save();
		
		$netatmoWeatherCmd = new netatmoWeatherCmd();
        $netatmoWeatherCmd->setName(__('Rain_1', __FILE__));
        $netatmoWeatherCmd->setEqLogic_id($this->id);
        $netatmoWeatherCmd->setConfiguration('data', 'sum_rain_1');
		$netatmoWeatherCmd->setUnite('mm');
        $netatmoWeatherCmd->setType('info');
        $netatmoWeatherCmd->setSubType('numeric');
		$netatmoWeatherCmd->setIsHistorized(0);
        $netatmoWeatherCmd->save();
        }    
    }

    public function toHtml($_version = 'dashboard') {
        if ($this->getIsEnable() != 1) {
            return '';
        }
		$alternativeDesign = 0;
	    if (config::byKey('alternativeDesign', 'netatmoWeather', 0) == 1) {
	        $alternativeDesign = 1;
	    }
        $weather = $this->getWeatherFromNetatmo();
		if (!is_array($weather)) {
            $replace = array(
                '#temperature#' => '',
                '#temp_id#' => '',
                '#humidity#' => '',
                '#humidity_id#' => '',
                '#pressure#' => '',
                '#pressure_id#' =>  '',
                '#CO2#' => '',
                '#CO2_id#' => '',
                '#noise#' => '',
                '#noise_id#' =>  '',
                '#rain#' => '',
                '#sum_rain_24#' => '',
            	'#sum_rain_1#' => '',
            	'#id#' => $this->getId(),
            	'#collectDate#' => $this->getCollectDate(),
                '#background_color#' => $this->getBackgroundColor(jeedom::versionAlias($_version)),
                '#eqLink#' => $this->getLinkToConfiguration(),
            );


            if($alternativeDesign==1){
            	return template_replace($replace, getTemplate('core', jeedom::versionAlias($_version), 'station_alt', 'netatmoWeather'));
            }else{
            	return template_replace($replace, getTemplate('core', jeedom::versionAlias($_version), 'station', 'netatmoWeather'));	
            }
 
        }
		$id=array();
		foreach($this->getCmd() as $cmd){
			$type_cmd=$cmd->getConfiguration('data');
			$id[$type_cmd]=$cmd->getId();
		}
		if ($this->getConfiguration('type') == 'Station') {
        	$type='station';
			$replace = array(
        		'#name#' => $this->getName(),
            	'#temperature#' => $weather['Temperature'],
            	'#temp_id#' => $id['temp'],
            	'#humidity#' => $weather['Humidity'],
            	'#humidity_id#' => $id['humidity'],
            	'#pressure#' => $weather['Pressure'],
            	'#pressure_id#' => $id['pressure'],
            	'#CO2#' => $weather['CO2'],
            	'#CO2_id#' => $id['CO2'],
            	'#noise#' => $weather['Noise'],
            	'#noise_id#' => $id['noise'],
            	'#id#' => $this->getId(),
            	'#collectDate#' => $this->getCollectDate(),
            	'#background_color#' => $this->getBackgroundColor(jeedom::versionAlias($_version)),
            	'#eqLink#' => $this->getLinkToConfiguration(),
        	);
        }else if ($this->getConfiguration('type') == 'module_int') {
        	$type='module_int';
			$replace = array(
        		'#name#' => $this->getName(),
            	'#temperature#' => $weather['Temperature'],
            	'#temp_id#' => $id['temp'],
            	'#humidity#' => $weather['Humidity'],
            	'#humidity_id#' => $id['humidity'],
            	'#CO2#' => $weather['CO2'],
            	'#CO2_id#' => $id['CO2'],
            	'#id#' => $this->getId(),
            	'#collectDate#' => $this->getCollectDate(),
            	'#background_color#' => $this->getBackgroundColor(jeedom::versionAlias($_version)),
            	'#eqLink#' => $this->getLinkToConfiguration(),
        	);
        }else if ($this->getConfiguration('type') == 'module_rain') {
        	$type='module_rain';
			$replace = array(
        		'#name#' => $this->getName(),
            	'#rain#' => round($weather['Rain'],2),
            	'#rain_id#' => $id['rain'],
            	'#sum_rain_24#' => round($weather['sum_rain_24'],2),
            	'#sum_rain_1#' => round($weather['sum_rain_1'],2),
            	'#id#' => $this->getId(),
            	'#collectDate#' => $this->getCollectDate(),
            	'#background_color#' => $this->getBackgroundColor(jeedom::versionAlias($_version)),
            	'#eqLink#' => $this->getLinkToConfiguration(),
        	);
        }else{
        	$type='module_ext';
			$replace = array(
        		'#name#' => $this->getName(),
            	'#temperature#' => $weather['Temperature'],
            	'#temp_id#' => $id['temp'],
            	'#humidity#' => $weather['Humidity'],
            	'#humidity_id#' => $id['humidity'],
            	'#id#' => $this->getId(),
            	'#collectDate#' => $this->getCollectDate(),
            	'#background_color#' => $this->getBackgroundColor(jeedom::versionAlias($_version)),
            	'#eqLink#' => $this->getLinkToConfiguration(),
        	);
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
        if($alternativeDesign==1){
        	return template_replace($replace, getTemplate('core', jeedom::versionAlias($_version), $type.'_alt', 'netatmoWeather'));
        }else{
            return template_replace($replace, getTemplate('core', jeedom::versionAlias($_version), $type, 'netatmoWeather'));	
        }
    }

    public function getShowOnChild() {
        return true;
    }

    /*     * **********************Getteur Setteur*************************** */

    public function getWeatherFromNetatmo() {
    	$enable_logging = 0;
        if (config::byKey('enableLogging', 'netatmoWeather', 0) == 1) {
            $enable_logging = 1;
        }
		if($enable_logging==1){
    		log::add('netatmoWeather', 'info', "starting the update for the module ".$this->getConfiguration('type')." - id =".$this->getConfiguration('station_id'), 'config');
		}
        if ($this->getConfiguration('client_id') == '') {
            return false;
        }
		  //  	log::add('netatmoWeather', 'info', 'récuperation 2', 'config');
		if ($this->getConfiguration('type') == 'Station') {
			foreach ($this->getCmd() as $cmd) {
				if($cmd->getConfiguration('data') == 'rain' || $cmd->getConfiguration('data') == 'sum_rain_24' || $cmd->getConfiguration('data') == 'sum_rain_1' ){
					$cmd->remove();	
				}
        	}
    	}else if ($this->getConfiguration('type') == 'module_int') {
			foreach ($this->getCmd() as $cmd) {
				if($cmd->getConfiguration('data') == 'pressure' || $cmd->getConfiguration('data') == 'noise' || $cmd->getConfiguration('data') == 'rain' || $cmd->getConfiguration('data') == 'sum_rain_24' || $cmd->getConfiguration('data') == 'sum_rain_1'){
					$cmd->remove();	
				}
        	}
    	}else if ($this->getConfiguration('type') == 'module_rain') {
			foreach ($this->getCmd() as $cmd) {
				if($cmd->getConfiguration('data') == 'pressure' || $cmd->getConfiguration('data') == 'CO2' || $cmd->getConfiguration('data') == 'noise' || $cmd->getConfiguration('data') == 'temp' || $cmd->getConfiguration('data') == 'humidity'){
					$cmd->remove();	
				}
        	}
    	}else if ($this->getConfiguration('type') == 'module_ext') {
			foreach ($this->getCmd() as $cmd) {
				if($cmd->getConfiguration('data') == 'pressure' || $cmd->getConfiguration('data') == 'CO2' || $cmd->getConfiguration('data') == 'noise' || $cmd->getConfiguration('data') == 'rain' || $cmd->getConfiguration('data') == 'sum_rain_24' || $cmd->getConfiguration('data') == 'sum_rain_1'){
					$cmd->remove();	
				}
        	}
    	}
		//log::add('netatmoWeather', 'info', 'récuperation 3', 'config');
		$cache = cache::byKey('netatmoWeather::' . $this->getConfiguration('station_id'));
		if ($cache->getValue() === '' || $cache->getValue() == 'false') {
			$this->setCollectDate(date('Y-m-d H:i:s'));
			
			if($enable_logging==1){
    			log::add('netatmoWeather', 'info', "cache too old for the module ".$this->getConfiguration('type')." - id =".$this->getConfiguration('station_id'), 'config');
			}
			try{
				//log::add('netatmoWeather', 'info', 'récuperation 5', 'config');
            	$client = new NAApiClient(array("client_id" => $this->getConfiguration('client_id'), "client_secret" => $this->getConfiguration('client_secret'), "username" => $this->getConfiguration('username'), "password" => $this->getConfiguration('password'), "scope" => NAScopes::SCOPE_READ_STATION));
        		$helper = new NAApiHelper($client);
				try {
					$tokens = $client->getAccessToken();
				} catch(NAClientException $ex) {
 					log::add('netatmoWeather', 'error', 'error 1 : An error happend while trying to retrieve your tokens', 'config');
 					echo "An error happend while trying to retrieve your tokens\n";
 					exit(-1);
				}
			$user = $helper->api("getuser", "POST");
			$devicelist = $helper->simplifyDeviceList();
			$mesures = $helper->getLastMeasures($client,$devicelist);
			foreach ($mesures[0]['modules'] as $mesure) {
                if($this->getConfiguration('station_id')==$mesure["_id"]){
                	$module=json_encode($mesure);
					if($enable_logging==1){
			    		log::add('netatmoWeather', 'info', "infos from netatmo for the module ".$this->getConfiguration('type')." - id =".$this->getConfiguration('station_id')." - values :".$module, 'config');
					}
                }
					
			}
		} catch (Exception $e) {
				if($enable_logging == 1){
					log::add('netatmoWeather', 'error', 'error 2', 'config');	
				}
				return '';
            }
            if (strlen($module) < 5000) {
                cache::set('netatmoWeather::' . $this->getConfiguration('station_id'), $module, 120);
				if($enable_logging==1){
    				log::add('netatmoWeather', 'info', "saving data to the cache for the module ".$this->getConfiguration('type')." - id =".$this->getConfiguration('station_id')." - values :".$module, 'config');
				}
            }
        } else {
            $module = $cache->getValue();
			$this->setCollectDate($cache->getDatetime());
			if($enable_logging==1){
    			log::add('netatmoWeather', 'info', "getting value from the cache for the module ".$this->getConfiguration('type')." - id =".$this->getConfiguration('station_id')." - values :".$module, 'config');
			}
        }

        return json_decode($module, true);
    }

	public function getCollectDate() {
        return $this->_collectDate;
    }

    public function setCollectDate($_collectDate) {
        $this->_collectDate = $_collectDate;
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
    	$enable_logging = 0;
        if (config::byKey('enableLogging', 'netatmoWeather', 0) == 1) {
            $enable_logging = 1;
        }
        $eqLogic_weather = $this->getEqLogic();
        $weather = $eqLogic_weather->getWeatherFromNetatmo();
		if (!is_array($weather)) {
            sleep(1);
            $weather = $eqLogic_weather->getWeatherFromNetatmo();
            if (!is_array($weather)) {
                return false;
            }
        }
		if($enable_logging==1){
			log::add('netatmoWeather', 'info', "execute cmd for the module ".$eqLogic_weather->getConfiguration('type')." - id =".$eqLogic_weather->getConfiguration('station_id')." - cmd data :".$this->getConfiguration('data')." with data : ".json_encode($weather), 'config');
		}
            if ($this->getConfiguration('data') == 'temp') {
                return $weather['Temperature'];
            }
            if ($this->getConfiguration('data') == 'humidity') {
                return $weather['Humidity'];
            }
            if ($this->getConfiguration('data') == 'pressure') {
                return $weather['Pressure'];
            }
            if ($this->getConfiguration('data') == 'CO2') {
                return $weather['CO2'];
            }
            if ($this->getConfiguration('data') == 'noise') {
                return $weather['Noise'];
            }
			if ($this->getConfiguration('data') == 'rain') {
                return $weather['Rain'];
            }
			if ($this->getConfiguration('data') == 'sum_rain_24') {
                return $weather['sum_rain_24'];
            }
			if ($this->getConfiguration('data') == 'sum_rain_1') {
                return $weather['sum_rain_1'];
            }
        
        return false;
    }

    /*     * **********************Getteur Setteur*************************** */
}

?>