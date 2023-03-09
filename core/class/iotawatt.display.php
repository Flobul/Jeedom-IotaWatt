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

class iotawatt_display extends eqLogic
{
    public static function displayActionCard($action_name, $fa_icon, $attr = '', $class = '') {
        $actionCard = '<div class="eqLogicDisplayAction eqLogicAction cursor ' . $class . '" ';
        if ($attr != '') $actionCard .= $attr;
        $actionCard .= '>';
        $actionCard .= '    <i class="fas ' . $fa_icon . '"></i><br>';
        $actionCard .= '    <span>' . $action_name . '</span>';
        $actionCard .= '</div>';
        echo $actionCard;
    }

    public static function displayBtnAction($class, $action, $title, $logo, $text, $display = FALSE) {
        $btn = '<a class="eqLogicAction btn btn-sm ' . $class . '"';
        $btn .= '    data-action="' . $action . '"';
        $btn .= '    title="' . $title . '"';
        if ($display) $btn .= '    style="display:none"';
        $btn .= '>';
        $btn .= '  <i class="fas ' . $logo . '"></i> ';
        $btn .= $text;
        $btn .= '</a>';
        echo $btn;
    }

    public static function displayEqLogicThumbnailContainer($eqLogics) {
        echo '<div class="panel panel-default">';
        echo '    <h3 class="panel-title">';
        echo '        <a class="accordion-toggle" data-toggle="collapse" data-parent="" href="#iotawattBox"><i class=""></i> </a>';
        echo '    </h3>';
        echo '    <div id="iotawatti_'.$_type.'" class="panel-collapse collapse in">';
        echo '        <div class="eqLogicThumbnailContainer">';
        foreach ($eqLogics as $eqLogic) {
            $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
            echo '            <div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
            echo '                <img src="' . $eqLogic->getImage() . '"/>';
            echo '                <br>';
            echo '                <span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
            echo '            </div>';
        }
        echo '            </div>';
        echo '        </div>';
        echo '    </div>';
    }
}