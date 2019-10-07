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

if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}
$eqLogics = netatmoWeather::byType('netatmoWeather');
?>

<table class="table table-condensed tablesorter" id="table_healthNetatmoWeather">
	<thead>
		<tr>
			<th>{{Image}}</th>
			<th>{{Module}}</th>
			<th>{{ID}}</th>
			<th>{{Batterie}}</th>
			<th>{{Serial}}</th>
			<th>{{Firmware}}</th>
			<th>{{Wifi}}</th>
			<th>{{RF}}</th>
			<th>{{Date création}}</th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ($eqLogics as $eqLogic) {
			if ($eqLogic->getConfiguration('type', '') != '') {
				$img = '<img src="plugins/netatmoWeather/core/img/' . $eqLogic->getConfiguration('type', '') . '.png" height="65" width="55" />';
			} else {
				$img = '<img src="plugins/netatmoWeather/doc/images/netatmoWeather_icon.png" height="65" width="55" />';
			}
			echo '<tr><td>'.$img.'</td><td><a href="' . $eqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogic->getHumanName(true) . '</a></td>';
			echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getId() . '</span></td>';
			$battery = $eqLogic->getStatus('battery');
			if(trim($battery) == ''){
				$battery_status = '<span class="label label-primary" style="font-size : 1em;" title="{{Secteur}}"><i class="fa fa-plug"></i></span>';
			}else if ($battery < 20) {
				$battery_status = '<span class="label label-danger" style="font-size : 1em;">' . $battery . '%</span>';
			} elseif ($battery < 60) {
				$battery_status = '<span class="label label-warning" style="font-size : 1em;">' . $battery . '%</span>';
			} elseif ($battery >= 60) {
				$battery_status = '<span class="label label-success" style="font-size : 1em;">' . $battery . '%</span>';
			}
			echo '<td>' . $battery_status . '</td>';
			echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getLogicalId() . '</span></td>';
			echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getConfiguration('firmware') . '</span></td>';
			echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getConfiguration('wifi_status') . '</span></td>';
			echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getConfiguration('rf_status') . '</span></td>';
			echo '<td><span class="label label-info" style="font-size : 1em;">' . $eqLogic->getConfiguration('createtime') . '</span></td></tr>';
		}
		?>
	</tbody>
</table>
