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
$eqLogics = iotawatt::byType('iotawatt');
?>
<style>
    .scanHender{
        cursor: pointer !important;
        width: 100%;
    }
</style>

<table class="table table-condensed tablesorter" id="table_healthiotawatt">
	<thead>
		<tr>
			<th>{{Appareil}}</th>
			<th>{{Id}}</th>
			<th>{{Puissance max}}</th>
			<th>{{RSSI}}</th>
			<th>{{Dernière production}}</th>
			<th>{{Dernière activité}}</th>
			<th>{{Dernière communication}}</th>
			<th>{{Date d'ajout}}</th>
			<th>{{Date création}}</th>
		</tr>
	</thead>
	<tbody>
      <?php

        foreach ($eqLogics as $eqLogic) {
          echo '<tr><td><a href="' . $eqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogic->getHumanName(true) . '</a></td>';

          echo '<td>' . $eqLogic->getLogicalId() . '</td>';

          echo '<td><span class="label label-success" style="font-size : 1em; cursor : default;">'.$eqLogic->getConfiguration('power', 'N/A').' W</span></td>';

          $rssi = $eqLogic->getStatus('lastDbm');
          $couche = '<span class="label label-danger" style="font-size : 1em; cursor : default;">{{N/A}}</span>';
          if (isset($rssi)) {
              $couche = '<span class="label label-info" style="font-size : 1em; cursor : default;">' . $rssi . ' dBm</span>';
          }
          echo '<td>' . $couche . '</td>';

          $prod = $eqLogic->getStatus('lastProduction');
          $lastProd = '<span class="label label-danger" style="font-size : 1em; cursor : default;">{{N/A}}</span>';
          if (isset($prod)) {
              $lastProd = '<span class="label label-info" style="font-size : 1em; cursor : default;">' . $prod . '</span>';
          }
          echo '<td>' . $lastProd . '</td>';
          
          $alive = $eqLogic->getStatus('lastAlive');
          $lastAlive = '<span class="label label-danger" style="font-size : 1em; cursor : default;">{{N/A}}</span>';
          if (isset($alive)) {
              $lastAlive = '<span class="label label-info" style="font-size : 1em; cursor : default;">' . $alive . '</span>';
          }
          echo '<td>' . $lastAlive . '</td>';

          $created = $eqLogic->getStatus('createdAt');
          $createdAt = '<span class="label label-danger" style="font-size : 1em; cursor : default;">{{N/A}}</span>';
          if (isset($created)) {
              $createdAt = '<span class="label label-info" style="font-size : 1em; cursor : default;">' . $created . '</span>';
          }
          echo '<td>' . $createdAt . '</td>';

          echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getStatus('lastCommunication') . '</span></td>';
          echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('createtime') . '</span></td></tr>';
        }
      ?>
	</tbody>
</table>