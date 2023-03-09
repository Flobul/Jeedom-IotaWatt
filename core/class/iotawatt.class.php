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
require_once __DIR__ . "/../../../../core/php/core.inc.php";

class iotawatt extends eqLogic
{
    /*     * *************************Attributs****************************** */
    public static $_widgetPossibility   = array('custom' => true, 'custom::layout' => false);
    public static $_pluginVersion = '0.30';

    /*     * ***********************Methode statique*************************** */
    private function getParamUnits($_unit, $_param) {
        $array = array(
            'Volts' => array(
                'name' => __('Tension', __FILE__),
                'unit' => __('V', __FILE__),
                'decimals' => 1,
                'minValue' => 0,
                'generic' => 'VOLTAGE'
            ),
            'Watts' => array(
                'name' => __('Puissance', __FILE__),
                'unit' => __('W', __FILE__),
                'decimals' => 1,
                'generic' => 'POWER'
            ),
            'Wh' => array(
                'name' => __('Consommation', __FILE__),
                'unit' => __('Wh', __FILE__),
                'decimals' => 2,
                'minValue' => 0,
                'generic' => 'CONSUMPTION'
            ),
            'Amps' => array(
                'name' => __('Intensité', __FILE__),
                'unit' => __('A', __FILE__),
                'decimals' => 1
            ),
            'VA' => array(
                'name' => __('Puissance active', __FILE__),
                'unit' => __('VA', __FILE__),
                'decimals' => 1
            ),
            'Hz' => array(
                'name' => __('Fréquence', __FILE__),
                'unit' => __('Hz', __FILE__),
                'decimals' => 2
            ),
            'PF' => array(
                'name' => __('Facteur de puissance', __FILE__),
                'unit' => '%',
                'decimals' => 1,
                'minValue' => 0,
                'minValue' => 100
            ),
            'VAR' => array(
                'name' => __('Puissance réactive', __FILE__),
                'unit' => __('VAr', __FILE__),
                'decimals' => 1
            ),
            'VARh' => array(
                'name' => __('Énergie réactive', __FILE__),
                'unit' => __('VAhr', __FILE__),
                'decimals' => 0
            )
        );
        return isset($array[$_unit]) ? $array[$_unit][$_param] : null;
    }

    /**
     * Renvoie la requete en une url
     *
     * @return	 		string		URL contenant la requête
     */
    protected static function buildQueryString(array $params)
    {
        return http_build_query($params, null, '&', PHP_QUERY_RFC3986);
    }

    public static function convertCrontabToMinutes($_crontab)
    {
        return str_replace('*/', '', explode(' ', $_crontab)[0]);
    }

    public static function update()
    {
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('début', __FILE__));
        $autorefresh = config::byKey('autorefresh', 'iotawatt', '');
        if ($autorefresh != '') {
            try {
                $c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
                if ($c->isDue()) {
                    try {
                        foreach (eqLogic::byType('iotawatt', true) as $iotawatt) {
                            //$iotawatt->getSeries();
                            //$iotawatt->getInfos();
                            $iotawatt->getSensors();
                        }
                    } catch (Exception $exc) {
                        log::add('iotawatt', 'error', __('Erreur : ', __FILE__) . $exc->getMessage());
                    }
                }
            } catch (Exception $exc) {
                log::add('iotawatt', 'error', __('Expression cron non valide : ', __FILE__) . $autorefresh);
            }
        }
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('fin', __FILE__));
    }


	public function getUrl() {
        $id = $this->getConfiguration('id', false);
        $password = $this->getConfiguration('password', false);
        $url = 'http://' . ($id && $password ? "$id:$password@" : '') . ($this->getConfiguration('ip') ?: 'iotawatt.local') . '/';
        return $url;
	}

    /**
     * Méthode appellée avant la création de l'objet
     * Active et affiche l'objet
     */
    public function preInsert()
    {
        $this->setIsEnable(1);
        $this->setIsVisible(1);
    }

    public function preUpdate()
    {
        $this->getSeries();
        //$this->getInfos();
        //$this->getSensors();
        //$this->updateStatus($this->getIotaWattStatus(array('passwords' => true, 'stats' => true, 'wifi' => true, 'inputs' => true, 'outputs' => true)));
    }

    public function postUpdate()
    {
        $this->updateStatus($this->getIotaWattStatus(array('passwords' => true, 'stats' => true, 'wifi' => true, 'inputs' => true, 'outputs' => true)));
    }

    public function decrypt() {
		$this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
	}

	public function encrypt() {
		$this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
	}

    public function getIotaWattStatus($_param)
    {
        if (is_array($_param)) {
            $_param = self::buildQueryString($_param);
        }
        $status = $this->request('status?' . $_param);
        if (is_array($status)) {
            return $status;
        }
        return false;
    }

    public function updateStatus($_status)
    {
        log::add('iotawatt', 'debug', __('TEST0000 =: ', __FILE__) . is_array($_status));
        if (!is_array($_status))  return false;
        if (isset($_status['device'])) {
            $this->setConfiguration('name', $_status['device']['name']);
            $this->setConfiguration('timediff', $_status['device']['timediff']);
            $this->setConfiguration('update', $_status['device']['update']);
        }
        if (isset($_status['stats'])) {
            $this->setConfiguration('lastUpdateTime', date('Y-m-d H:i:s', $_status['stats']['currenttime']));
            $this->setConfiguration('startTime', date('Y-m-d H:i:s', $_status['stats']['starttime']));
            $this->setConfiguration('runSeconds', $_status['stats']['runseconds']);
            $this->setConfiguration('firmwareVersion', $_status['stats']['version']);
            $this->setStatus('lowbat', $_status['stats']['lowbat']);
        }
        if (isset($_status['wifi'])) {
            $this->setConfiguration('mac', $_status['wifi']['mac']);
            $this->setConfiguration('SSID', $_status['wifi']['SSID']);
            $this->setStatus('RSSI', $_status['wifi']['RSSI']);
            $this->setStatus('connecttime', $_status['wifi']['connecttime']);
        }
        if (isset($_status['passwords'])) {
            $this->setConfiguration('admin', $_status['passwords']['admin']);
            $this->setConfiguration('user', $_status['passwords']['user']);
            $this->setConfiguration('localAccess', $_status['passwords']['localAccess']);
        }
        if (isset($_status['state'])) {
            //???
        }
        if (isset($_status['datalogs'])) {
        }
        if (isset($_status['influx1'])) {
            //{"influx1":{"state":"not running"}}
        }
        if (isset($_status['influx2'])) {
            //{"influx2":{"state":"not running"}}
        }
        if (isset($_status['emoncms'])) {
            //{"emoncms":{"state":"not running"}}
        }
        if (isset($_status['pvoutput'])) {
            //{"pvoutput":{"state":"not running"}}
        }
        if (isset($_status['inputs'])) {
            $series = $this->getSeries();
            $this->setConfiguration('nbInputs', count($_status['inputs']));
            for ($i = 0; $i < count($_status['inputs']); $i++) {
                $this->createCmdInfo($_status['inputs'][$i], 'input', $series[$i]);
            }
        }
        if (isset($_status['outputs'])) {
            $this->setConfiguration('nbOutputs', count($_status['outputs']));
            for ($i = 0; $i < count($_status['outputs']); $i++) {
                $this->createCmdInfo($_status['outputs'][$i], 'output');
            }
        }
    }

    public function createCmdInfo($_IO, $_type, $_serie = array())
    {
        //CREATEINFO IO: input: {"channel":0,"Vrms":246.323,"Hz":50.01826,"phase":2.53} => series: {"name":"Tension","unit":"Volts"}
        //CREATEINFO IO: output : {"name":"Tension","units":"Volts","value":246.4418} => series: []
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('début', __FILE__) . ' type=' . $_type . ' IO=' . json_encode($_IO) . ' serie=' . json_encode($_serie));

        if ($_type == 'input') {
            $serie = $_serie['name'];
            $unit = $_serie['unit'];
            $name = self::getParamUnits($unit, 'name') . ' I' . sprintf("%02d", $_IO['channel']) . ' ' . $serie;
            $logicalId = $_type . '_' . $_IO['channel'] . '_' . $unit;
        } else {
            $serie = $_IO['name'];
            $unit = $_IO['units'];
            $name = self::getParamUnits($unit, 'name') . ' ' . $serie;
            $logicalId = $_type . '_' . $_IO['name'] . '_' . $unit;
        }
        $order = isset($_IO['channel']) ? $_IO['channel'] : (count($this->getCmd('info')));

        $cmd = $this->getCmd('info', $logicalId);
        if (!is_object($cmd)) {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('Création de la commande', __FILE__));
            $cmd = new iotawattCmd();
            $cmd->setOrder($order);
            $cmd->setEqLogic_id($this->getId());
            $cmd->setLogicalId($logicalId);
            $cmd->setName($name);
            $cmd->setType('info');
            $cmd->setSubType('numeric');
            $cmd->setUnite(self::getParamUnits($unit, 'unit'));
            $cmd->setIsHistorized(1);
            $cmd->setIsVisible(1);
            $cmd->setConfiguration('channel', $_IO['channel'] ?? 'N/A');
            $cmd->setConfiguration('type', $_type);
            $cmd->setConfiguration('totalConsumption', $_IO['manual']?true:null);
            $cmd->setConfiguration('serie', $serie);
            $cmd->setConfiguration('valueType', strtolower($unit));
            $cmd->setConfiguration('round', self::getParamUnits($unit, 'decimals'));
            $cmd->setConfiguration('minValue', self::getParamUnits($unit, 'minValue'));
            $cmd->setConfiguration('maxValue', self::getParamUnits($unit, 'maxValue'));
            $cmd->setConfiguration('manualGroup', array('value' => '5', 'unit' => 'm'));
            $cmd->setConfiguration('group', 'auto');
            $cmd->setTemplate('dashboard', 'core::tile');
            $cmd->setTemplate('mobile', 'core::tile');
            $cmd->setGeneric_type(self::getParamUnits($unit, 'generic'));
            $cmd->save();

            log::add(__CLASS__, 'debug', 'CREATEINFO IO7: ' .json_encode(utils::o2a($cmd)));
            if ($unit == 'Watts') { // création d'une commande de consommation
                if ($_type == 'input') {
                    $_IO['manual'] = true;
                    $_serie['unit'] = 'Wh';
                } else {
                    $_IO['manual'] = true;
                    $_IO['units'] = 'Wh';
                }
                $this->createCmdInfo($_IO, $_type, $_serie);
            }
        } else {
            log::add(__CLASS__, 'debug', __FUNCTION__ . ' ' . __('Commande déjà existante', __FILE__) . $cmd->getLogicalId());
            if ($cmd->getConfiguration('serie') != $serie) {
                $cmd->setConfiguration('serie', $serie);
            }
            if ($cmd->getConfiguration('minValue') != self::getParamUnits($unit, 'minValue')) {
                $cmd->setConfiguration('minValue', self::getParamUnits($unit, 'minValue'));
            }
            if ($cmd->getConfiguration('maxValue') != self::getParamUnits($unit, 'maxValue')) {
                $cmd->setConfiguration('maxValue', self::getParamUnits($unit, 'maxValue'));
            }
            if ($cmd->getUnite() != self::getParamUnits($unit, 'unit')) {
                $cmd->setUnite(self::getParamUnits($unit, 'unit'));
            }
            if ($cmd->getLogicalId() != $logicalId) {
                $cmd->setLogicalId($logicalId);
            }
        }
        return $cmd;
    }

    public function getSeries()
    {
        $server = $this->request('query?show=series');
                log::add('iotawatt', 'debug', __('TEST333 v$server : ', __FILE__) . json_encode($server));
        if (is_array($server) && isset($server['series'])) {
            $this->setConfiguration('series', $server['series']);
            return $server['series'];
        }
        return false;
    }

    public function getSensors()
    {
        $allCmds = $this->getCmd('info');
        //make an array with $logicalId=>url_to_send to
        $cmds = array_filter(array_map(function($cmd) {
            //if ($cmd->getConfiguration('totalConsumption', false) !== true) {
                return array($cmd->getLogicalId() => $cmd->getConfiguration('serie') . '.' . $cmd->getConfiguration('valueType') . '.d' . $cmd->getConfiguration('round'));
            //}
        }, $allCmds));

        $url = implode(',', array_map(function($item) {
            return current($item);
        }, $cmds));

        $value = $this->getStatus('lastValueUpdate', '') != '' ? $this->getStatus('lastValueUpdate') : 's-' . self::convertCrontabToMinutes(config::byKey('autorefresh', 'iotawatt', '*/1 * * * *')) . 'm';
        $group = $this->getConfiguration('group', 'auto');
        if ($group == 'manual') {
           $group = implode('', array_map(fn($item) => $item ?? '', $this->getConfiguration('manualGroup', '5m')));
        }
        $resolution = $this->getConfiguration('resolution', 'high');

        $params = array(
            'select' => '[time.local.iso,' . $url . ']',
            //'begin'  => 's-' . self::convertCrontabToMinutes(config::byKey('autorefresh', 'iotawatt', '*/1 * * * *')) . 'm', //register last time value ('from range') and start from there for next time ?
            'begin'  => $value,
            'end'    => 's',
            'group'  => $group, //{ *auto | all | <n> {s | m | h | d | w | M | y}}
            'format' => 'json', //{ *json | csv}
            'header' => 'yes', //{ *no | yes }
            'missing' => 'zero', //{ null | *skip | zero}'
            'resolution' => $resolution, //{ low | high }
            'limit' => 'none' //{n | none | *1000}
        );
        $seriesValues = $this->request('query?' . self::buildQueryString($params));

        if (is_array($seriesValues) && isset($seriesValues['data'])) {
            foreach ($seriesValues['data'] as $datas){
                $nb = 0;
                $nbUpdated = 0;
                $resultat = array_map(function($elem) use ($datas, &$nb, &$nbUpdated) {
                    $key = key($elem);
                    $value = current($elem);
                    $cmdInfo = $this->getCmd('info', $key);
                    if (is_object($cmdInfo)) {
                        //if ($cmdInfo->getConfiguration('totalConsumption', false) !== true) {
                            if ($cmdInfo->getConfiguration('valueType') == 'pf') {
                                $cmdInfo->event(floatval($datas[$nb+1]) * 100, str_replace('T', ' ', $datas[0]));
                            } elseif ($cmdInfo->getConfiguration('valueType') == 'wh') {
                                if ($cmdInfo->getUnite() == 'kWh') {
                                    $cmdInfo->event($cmdInfo->execCmd()+($datas[$nb+1]/1000), str_replace('T', ' ', $datas[0]));
                                } else {
                                    $cmdInfo->event($cmdInfo->execCmd()+$datas[$nb+1], str_replace('T', ' ', $datas[0])); // penser à demander l'historique depuis le tout début ?
                                }
                            } else {
                                $cmdInfo->event($datas[$nb+1], str_replace('T', ' ', $datas[0]));
                            }
                            $nbUpdated++;
                        //}
                    }
                    $nb++;
                    return $nbUpdated;
                }, $cmds);
            }
            $this->setStatus('lastValueUpdate', $resultat > 0 ? $seriesValues['range'][1] : 0);
        }
    }

    public function getCmdbySerieValueTypeRoundConfiguration($_logicalId)
    {
        $parts = explode('.', $_logicalId);
        foreach ($this->getCmd('info') as $cmd) {
            if ($cmd->getConfiguration('serie') == $parts[0] && $cmd->getConfiguration('valueType') == $parts[1] && $cmd->getConfiguration('round') == substr($parts[2],1,1)) {
                return $cmd;
            }
        }
        log::add('iotawatt', 'debug', __FUNCTION__ . ' ' . __(' commande non trouvée : ', __FILE__) . $_logicalId);
        return false;
    }

    public function getCmdbySerieValueTypeConfiguration($_logicalId)
    {
        foreach ($this->getCmd('info') as $cmd) {
            if ($cmd->getConfiguration('serie') == $parts[0] && $cmd->getConfiguration('valueType') == $parts[1]) {
                return $cmd;
            }
        }
        return false;
    }

    public function getCmdbyInputChannel($_channel)
    {
        foreach ($this->getCmd('info') as $cmd) {
            if ($cmd->getConfiguration('channel') == $_channel) {
                return $cmd;
            }
        }
        return false;
    }

    public function getInfos()
    {
        $infos = $this->request('status?state&inputs&outputs&stats&wifi&datalogs&influx1&influx2&emoncms&pvoutput');
                log::add('iotawatt', 'debug', __('TEST333 : ', __FILE__) . json_encode($infos));
        return false;
    }

    public function request($_path, $_payload = array(), $_method = 'GET', $_timeout = 6)
    {
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('début requête url=[', __FILE__) . $this->getUrl() . $_path . '] ; payload=[' . json_encode($_payload) . '] ; method=[' . $_method . ']');
        try {
            $id =  $this->getConfiguration('id', false);
            $password =  $this->getConfiguration('password', false);
            $http = new com_http($this->getUrl() . $_path, $id, $password);
            if ($id && $password) {
                $http->setCURLOPT_HTTPAUTH(CURLAUTH_DIGEST);
            }
            if ($_method == 'POST') {
                $http->setPost(json_encode($_payload));
            }
            if ($_method == 'PUT') {
                $http->setPut($_payload);
            }
            $header = array(
                'Accept: application/json, text/plain, */*',
                'Content-Type: application/json',
            );

            log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('début header ', __FILE__) . json_encode($header));
            $http->setHeader($header);

        } catch (Exception $e) {
            log::add(__CLASS__, 'debug', "L." . __LINE__ . " F." . __FUNCTION__ . __(" Erreur d'authentification : ", __FILE__) . $http);
        }
        try {
            $response = $http->exec($_timeout);
            if (!isset($response['error'])) {
                log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('fin (true)', __FILE__) . $response);
                if ($response == 'IoTaWatt-Login') {
                    log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('Vérifiez vos identifiants de connexion ', __FILE__) . $response);
                    return false;
                }
                $response = json_decode($response, true);
                return $response;
            }
        } catch (Exception $e) {
            log::add(__CLASS__, 'debug', "L." . __LINE__ . " F." . __FUNCTION__ . __(" Erreur de connexion : ", __FILE__) . json_encode(utils::o2a($e)));
        }
        log::add(__CLASS__, 'debug', __FUNCTION__ . ' : ' . __('fin  (false)', __FILE__) . json_encode($response));
        return false;
    }

    public function getImage()
    {
        return 'plugins/iotawatt/plugin_info/iotawatt_icon.png';
    }

    public function toHtml($_version = 'dashboard')
    {
        if ($this->getConfiguration('widgetTemplate') != 1) {
            return parent::toHtml($_version);
        }
        $replace = $this->preToHtml($_version);
        if (!is_array($replace)) {
            return $replace;
        }
        $_version = jeedom::versionAlias($_version);

        $replace['#lastDbm_value#'] = $this->getStatus('lastDbm');
        $replace['#lastCommunication_value#'] = $this->getStatus('lastCommunication');
        $replace['#lastProduction_value#'] = $this->getStatus('lastProduction');
        $replace['#lastAlive_value#'] = $this->getStatus('lastAlive');
        $replace['#createdAt_value#'] = $this->getStatus('createdAt');

        $replace['#orientation_value#'] = $this->getConfiguration('orientation');
        $replace['#tilt_value#'] = $this->getConfiguration('tilt');
        $replace['#nbModules_value#'] = $this->getConfiguration('numberOfPanels');
        $replace['#puissance_value#'] = $this->getConfiguration('power');

        foreach ($this->getCmd('info', null) as $cmd) {
            $replace['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
            $replace['#cmd_' . $cmd->getLogicalId() . '_name#'] = $cmd->getName();
            $replace['#cmd_' . $cmd->getLogicalId() . '_value#'] = $cmd->execCmd();
            $replace['#cmd_' . $cmd->getLogicalId() . '_icon#'] = $cmd->getDisplay('icon', '');
            if ($cmd->getConfiguration('maxValue', '') != '') {
                $replace['#cmd_' . $cmd->getLogicalId() . '_maxValue#'] = $cmd->getConfiguration('maxValue');
            }
            $replace['#cmd_' . $cmd->getLogicalId() . '_unit#'] = $cmd->getUnite();
            $replace['#cmd_' . $cmd->getLogicalId() . '_collectDate#'] = $cmd->getCollectDate();
            $replace['#cmd_' . $cmd->getLogicalId() . '_valueDate#'] = $cmd->getValueDate();
        }
        foreach ($this->getCmd('action', null) as $cmd) {
            $replace['#cmd_' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
        }
        $html = template_replace($replace, getTemplate('core', $_version, 'iotawatt.template',__CLASS__));
        $html = translate::exec($html, 'plugins/iotawatt/core/template/' . $version . '/iotawatt.tempate.html');
        return $html;
    }
}

class iotawattCmd extends cmd
{
    public static $_widgetPossibility = array('custom' => true);

    public function execute($_options = array())
    {
        $eqLogic = $this->getEqLogic();
        log::add('iotawatt', 'debug', __("Action sur ", __FILE__) . $this->getLogicalId() . __(" avec options ", __FILE__) . json_encode($_options));

    }
}
