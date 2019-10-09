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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function netatmoWeather_update() {
	$cron = cron::byClassAndFunction('netatmoWeather', 'pull');
	if (is_object($cron)) {
		$cron->remove();
	}
	
	foreach (netatmoWeather::byType('netatmoWeather') as $eqLogic) {
		foreach ($eqLogic->getCmd() as $cmd) {
			if ($cmd->getLogicalId() != '') {
				continue;
			}
			$key = strtolower($cmd->getConfiguration('data'));
			if ($key == 'temp') {
				$key = 'temperature';
			}
			if ($key == '') {
				continue;
			}
			$cmd->setLogicalId($key);
			$cmd->save();
		}
		$eqLogic->setConfiguration('type', strtolower($eqLogic->getConfiguration('type')));
		if ($eqLogic->getLogicalId() == '') {
			$eqLogic->setLogicalId($eqLogic->getConfiguration('station_id'));
		}
		$eqLogic->save();
		if (config::byKey('client_id', 'netatmoWeather') == '') {
			config::save('client_id', $eqLogic->getConfiguration('client_id'), 'netatmoWeather');
			config::save('client_secret', $eqLogic->getConfiguration('client_secret'), 'netatmoWeather');
			config::save('username', $eqLogic->getConfiguration('username'), 'netatmoWeather');
			config::save('password', $eqLogic->getConfiguration('password'), 'netatmoWeather');
		}
	}
	try {
		netatmoWeather::syncWithNetatmo();
	} catch (\Exception $e) {
		
	}
}
?>
