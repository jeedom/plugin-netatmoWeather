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

function netatmoWeather_install() {
	$cron = cron::byClassAndFunction('netatmoWeather', 'pull');
	if (!is_object($cron)) {
		$cron = new cron();
		$cron->setClass('netatmoWeather');
		$cron->setFunction('pull');
		$cron->setEnable(1);
		$cron->setDeamon(0);
		$cron->setSchedule('*/10 * * * *');
		$cron->save();
	}
}

function netatmoWeather_update() {
	$cron = cron::byClassAndFunction('netatmoWeather', 'pull');
	if (!is_object($cron)) {
		$cron = new cron();
		$cron->setClass('netatmoWeather');
		$cron->setFunction('pull');
		$cron->setEnable(1);
	}
	$cron->setDeamon(0);
	$cron->setSchedule('*/15 * * * *');
	$cron->save();
	$cron->stop();

	foreach (netatmoWeather::byType('netatmoWeather') as $eqLogic) {
		foreach ($eqLogic->getCmd() as $cmd) {
			$key = strtolower($cmd->getConfiguration('data'));
			if ($key == 'temp') {
				$key = 'temperature';
			}
			$cmd->setLogicalId($key);
			$cmd->save();
		}
		$eqLogic->setConfiguration('type', strtolower($eqLogic->getConfiguration('type')));
		$eqLogic->setLogicalId($eqLogic->getConfiguration('station_id'));
		$eqLogic->save();
		config::save('client_id', $eqLogic->getConfiguration('client_id'), 'netatmoWeather');
		config::save('client_secret', $eqLogic->getConfiguration('client_secret'), 'netatmoWeather');
		config::save('username', $eqLogic->getConfiguration('username'), 'netatmoWeather');
		config::save('password', $eqLogic->getConfiguration('password'), 'netatmoWeather');
	}
}

function netatmoWeather_remove() {
	$cron = cron::byClassAndFunction('netatmoWeather', 'pull');
	if (is_object($cron)) {
		$cron->remove();
	}
}
?>