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
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class lite_site_monitoring extends eqLogic {

    const LOG = 'lite_site_monitoring';

    /** Délai maximal d'établissement de connexion (secondes). */
    const CONNECT_TIMEOUT = 10;

    /** Délai maximal d'une requête complète (secondes). */
    const REQUEST_TIMEOUT = 20;

    /** Taille maximale de page conservée pour la recherche du mot-clé (octets). */
    const MAX_BODY_SIZE = 1048576;

    /** Fréquence de contrôle par défaut. */
    const DEFAULT_CRON = '*/5 * * * *';

    /** Hôtes interrogés pour vérifier l'accès à Internet. */
    const INTERNET_PROBES = array('https://1.1.1.1', 'https://8.8.8.8');

    /** Mesures de temps : logicalId de la commande => option curl_getinfo (secondes, converties en ms). */
    const TIMINGS = array(
        'dns_resolution'     => CURLINFO_NAMELOOKUP_TIME,
        'tcp_established'    => CURLINFO_CONNECT_TIME,
        'ssl_handshake_done' => CURLINFO_APPCONNECT_TIME,
        'TTFB'               => CURLINFO_STARTTRANSFER_TIME,
        'latence'            => CURLINFO_TOTAL_TIME,
    );

    /*     * ***********************Méthodes statiques*************************** */

    public static function cron() {
        $due = array_filter(eqLogic::byType(self::LOG, true), function ($eqLogic) {
            return $eqLogic->isDue();
        });
        if (count($due) > 0) {
            self::checkAll($due);
        }
    }

    /**
     * Contrôle plusieurs équipements en parallèle et met à jour leurs commandes.
     *
     * @param lite_site_monitoring[] $_eqLogics
     */
    public static function checkAll(array $_eqLogics) {
        $targets = array();
        $internetAccess = null;

        foreach ($_eqLogics as $eqLogic) {
            if ($eqLogic->getConfiguration('url') == '') {
                log::add(self::LOG, 'warning', 'Aucune URL configurée pour ' . $eqLogic->getHumanName());
                continue;
            }
            if ($eqLogic->getConfiguration('internal') != 1) {
                // Un seul test d'accès à Internet par passage, et seulement si nécessaire
                if ($internetAccess === null) {
                    $internetAccess = self::checkInternetAccess();
                }
                $eqLogic->checkAndUpdateCmd('internet', $internetAccess ? 1 : 0);
                if (!$internetAccess) {
                    // Sans Internet, le résultat ne dirait rien du site : on garde le dernier état connu
                    log::add(self::LOG, 'warning', 'Pas d\'accès à Internet, contrôle de ' . $eqLogic->getHumanName() . ' reporté');
                    continue;
                }
            }
            $targets[$eqLogic->getId()] = $eqLogic;
        }
        if (count($targets) == 0) {
            return;
        }

        $results = self::probeAll($targets);

        // Nouvel essai immédiat des échecs, pour écarter les erreurs passagères
        $failed = array_filter($results, function ($result) {
            return !$result['ok'];
        });
        if (count($failed) > 0) {
            log::add(self::LOG, 'debug', 'checkAll :. Nouvel essai pour ' . count($failed) . ' site(s)');
            $results = array_replace($results, self::probeAll(array_intersect_key($targets, $failed)));
        }

        foreach ($results as $id => $result) {
            $targets[$id]->applyResult($result);
        }
    }

    /**
     * Vérifie que Jeedom a accès à Internet en interrogeant des hôtes publics connus.
     */
    public static function checkInternetAccess() {
        foreach (self::INTERNET_PROBES as $probe) {
            $ch = curl_init($probe);
            curl_setopt_array($ch, array(
                CURLOPT_NOBODY         => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ));
            if (curl_exec($ch) !== false) {
                log::add(self::LOG, 'debug', 'checkInternetAccess :. Accès à Internet confirmé via ' . $probe);
                return true;
            }
        }
        log::add(self::LOG, 'debug', 'checkInternetAccess :. Pas d\'accès à Internet détecté');
        return false;
    }

    /**
     * Interroge les URL des équipements en parallèle.
     *
     * @param lite_site_monitoring[] $_eqLogics indexés par id
     * @return array résultats indexés par id d'équipement
     */
    private static function probeAll(array $_eqLogics) {
        $multi = curl_multi_init();
        $handles = array();
        $bodies = array();

        foreach ($_eqLogics as $id => $eqLogic) {
            $bodies[$id] = '';
            $keepBody = $eqLogic->getConfiguration('keyword') != '';
            $ch = curl_init($eqLogic->getConfiguration('url'));
            curl_setopt_array($ch, array(
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS      => 5,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT        => self::REQUEST_TIMEOUT,
                CURLOPT_ENCODING       => '',
                CURLOPT_USERAGENT      => 'Jeedom lite_site_monitoring',
                // Certificats non vérifiés : on surveille la disponibilité, l'expiration est suivie à part
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_CERTINFO       => true,
                // Le contenu n'est conservé que si un mot-clé doit y être cherché
                CURLOPT_WRITEFUNCTION  => function ($ch, $data) use ($id, $keepBody, &$bodies) {
                    if ($keepBody && strlen($bodies[$id]) < self::MAX_BODY_SIZE) {
                        $bodies[$id] .= $data;
                    }
                    return strlen($data);
                },
            ));
            curl_multi_add_handle($multi, $ch);
            $handles[$id] = $ch;
        }

        do {
            $status = curl_multi_exec($multi, $running);
            if ($running && curl_multi_select($multi, 1.0) === -1) {
                usleep(100000);
            }
        } while ($running && $status == CURLM_OK);

        $curlCodes = array();
        while (($info = curl_multi_info_read($multi)) !== false) {
            $curlCodes[array_search($info['handle'], $handles, true)] = $info['result'];
        }

        $results = array();
        foreach ($handles as $id => $ch) {
            $curlCode = isset($curlCodes[$id]) ? $curlCodes[$id] : CURLE_OPERATION_TIMEOUTED;
            $results[$id] = self::analyse($_eqLogics[$id], $ch, $curlCode, $bodies[$id]);
            curl_multi_remove_handle($multi, $ch);
        }
        curl_multi_close($multi);
        return $results;
    }

    /**
     * Interprète la réponse d'une requête terminée.
     */
    private static function analyse($_eqLogic, $_ch, $_curlCode, $_body) {
        $httpCode = (int) curl_getinfo($_ch, CURLINFO_RESPONSE_CODE);
        $result = array(
            'ok'        => false,
            'http_code' => $httpCode,
            'error'     => '',
            'ssl_days'  => self::sslDaysLeft($_ch),
        );
        foreach (self::TIMINGS as $logicalId => $info) {
            $result[$logicalId] = round(curl_getinfo($_ch, $info) * 1000, 1);
        }

        $keyword = $_eqLogic->getConfiguration('keyword');
        if ($_curlCode !== CURLE_OK) {
            $result['error'] = curl_strerror($_curlCode);
        } elseif (!$_eqLogic->isExpectedCode($httpCode)) {
            $result['error'] = sprintf(__('Code HTTP %d inattendu', __FILE__), $httpCode);
        } elseif ($keyword != '' && stripos($_body, $keyword) === false) {
            $result['error'] = sprintf(__('Mot-clé « %s » absent de la page', __FILE__), $keyword);
        } else {
            $result['ok'] = true;
        }

        log::add(self::LOG, 'debug', 'analyse :. ' . $_eqLogic->getHumanName() . ' : ' . json_encode($result));
        return $result;
    }

    /**
     * Nombre de jours avant l'expiration du certificat du site, ou null hors HTTPS.
     */
    private static function sslDaysLeft($_ch) {
        $certs = curl_getinfo($_ch, CURLINFO_CERTINFO);
        if (empty($certs[0]['Expire date'])) {
            return null;
        }
        $expire = strtotime($certs[0]['Expire date']);
        return $expire === false ? null : (int) floor(($expire - time()) / 86400);
    }

    /**
     * Découpe une liste de codes HTTP saisie par l'utilisateur ("200, 301").
     */
    private static function parseCodes($_codes) {
        return preg_split('/[\s,;]+/', trim($_codes), -1, PREG_SPLIT_NO_EMPTY);
    }

    /*     * *********************Méthodes d'instance************************* */

    /**
     * Nom d'hôte de l'URL surveillée, pour l'affichage.
     */
    public function getHost() {
        $host = parse_url($this->getConfiguration('url'), PHP_URL_HOST);
        return $host ? $host : $this->getConfiguration('url');
    }

    /**
     * État en ligne connu (1, 0 ou '' si jamais contrôlé), pour l'affichage.
     */
    public function getOnlineState() {
        $cmd = $this->getCmd('info', 'online');
        return is_object($cmd) ? $cmd->execCmd() : '';
    }

    /**
     * Indique si le contrôle de l'équipement doit être lancé cette minute.
     */
    public function isDue() {
        $expression = $this->getConfiguration('autorefresh', self::DEFAULT_CRON);
        try {
            $cron = new Cron\CronExpression(checkAndFixCron($expression), new Cron\FieldFactory);
            return $cron->isDue();
        } catch (Exception $e) {
            log::add(self::LOG, 'error', 'Expression cron invalide pour ' . $this->getHumanName() . ' : ' . $expression);
            return false;
        }
    }

    /**
     * Indique si le code HTTP reçu correspond à ce qui est attendu (2xx ou 3xx par défaut).
     */
    public function isExpectedCode($_httpCode) {
        $expected = self::parseCodes($this->getConfiguration('expectedCode'));
        if (count($expected) == 0) {
            return $_httpCode >= 200 && $_httpCode < 400;
        }
        return in_array($_httpCode, array_map('intval', $expected), true);
    }

    /**
     * Met à jour les commandes à partir du résultat d'un contrôle.
     * Le site ne passe hors ligne qu'après le nombre d'échecs consécutifs configuré.
     */
    public function applyResult(array $_result) {
        $failures = $_result['ok'] ? 0 : (int) $this->getCache('failures', 0) + 1;
        $this->setCache('failures', $failures);
        $threshold = max(1, (int) $this->getConfiguration('failThreshold', 1));

        if ($_result['ok']) {
            $this->checkAndUpdateCmd('online', 1);
        } elseif ($failures >= $threshold) {
            $this->checkAndUpdateCmd('online', 0);
            log::add(self::LOG, 'info', $this->getHumanName() . ' hors ligne : ' . $_result['error']);
        } else {
            log::add(self::LOG, 'info', $this->getHumanName() . ' échec ' . $failures . '/' . $threshold . ' (' . $_result['error'] . '), état conservé');
        }

        foreach (array_keys(self::TIMINGS) as $logicalId) {
            $this->checkAndUpdateCmd($logicalId, $_result[$logicalId]);
        }
        $this->checkAndUpdateCmd('http_code', $_result['http_code']);
        $this->checkAndUpdateCmd('error', $_result['error']);
        if ($_result['ssl_days'] !== null) {
            $this->checkAndUpdateCmd('ssl_days', $_result['ssl_days']);
        }
        $this->checkAndUpdateCmd('last_snif', date('d/m/Y H:i:s'));
    }

    public function preSave() {
        $url = trim($this->getConfiguration('url'));
        if ($url != '') {
            // Comme curl, on suppose http:// quand le schéma est omis
            if (strpos($url, '://') === false) {
                $url = 'http://' . $url;
            }
            $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
            if (!in_array($scheme, array('http', 'https')) || parse_url($url, PHP_URL_HOST) == '') {
                throw new Exception(__('URL invalide, elle doit être de la forme https://www.exemple.fr :', __FILE__) . ' ' . $url);
            }
        }
        $this->setConfiguration('url', $url);

        if (trim($this->getConfiguration('autorefresh')) == '') {
            $this->setConfiguration('autorefresh', self::DEFAULT_CRON);
        }
        try {
            new Cron\CronExpression(checkAndFixCron($this->getConfiguration('autorefresh')), new Cron\FieldFactory);
        } catch (Exception $e) {
            throw new Exception(__('Expression cron d\'auto-actualisation invalide :', __FILE__) . ' ' . $this->getConfiguration('autorefresh'));
        }

        foreach (self::parseCodes($this->getConfiguration('expectedCode')) as $code) {
            if (!preg_match('/^[1-5][0-9]{2}$/', $code)) {
                throw new Exception(__('Code HTTP attendu invalide :', __FILE__) . ' ' . $code);
            }
        }

        $this->setConfiguration('failThreshold', max(1, (int) $this->getConfiguration('failThreshold', 1)));
    }

    public function postSave() {
        $commands = array(
            // logicalId => [nom, type, sous-type, unité, visible, historisée]
            'refresh'            => array(__('Rafraîchir', __FILE__), 'action', 'other', '', 1, 0),
            'online'             => array(__('En ligne', __FILE__), 'info', 'binary', '', 1, 1),
            'latence'            => array(__('Latence', __FILE__), 'info', 'numeric', 'ms', 1, 1),
            'dns_resolution'     => array(__('Résolution DNS', __FILE__), 'info', 'numeric', 'ms', 0, 0),
            'tcp_established'    => array(__('Connexion TCP', __FILE__), 'info', 'numeric', 'ms', 0, 0),
            'ssl_handshake_done' => array(__('Négociation SSL', __FILE__), 'info', 'numeric', 'ms', 0, 0),
            'TTFB'               => array(__('TTFB', __FILE__), 'info', 'numeric', 'ms', 0, 0),
            'http_code'          => array(__('Code HTTP', __FILE__), 'info', 'numeric', '', 0, 0),
            'error'              => array(__('Erreur', __FILE__), 'info', 'string', '', 0, 0),
            'ssl_days'           => array(__('Expiration SSL', __FILE__), 'info', 'numeric', 'j', 0, 0),
            'internet'           => array(__('Accès Internet', __FILE__), 'info', 'binary', '', 0, 0),
            'last_snif'          => array(__('Dernier contrôle', __FILE__), 'info', 'string', '', 1, 0),
        );

        foreach ($commands as $logicalId => list($name, $type, $subType, $unit, $isVisible, $isHistorized)) {
            $cmd = $this->getCmd(null, $logicalId);
            if (!is_object($cmd)) {
                $cmd = new lite_site_monitoringCmd();
                $cmd->setLogicalId($logicalId);
                $cmd->setName($name);
                $cmd->setIsVisible($isVisible);
                $cmd->setIsHistorized($isHistorized);
            }
            $cmd->setType($type);
            $cmd->setSubType($subType);
            $cmd->setUnite($unit);
            $cmd->setEqLogic_id($this->getId());
            $cmd->save();
        }
    }

    /**
     * Widget dédié (voyant d'état, latence, certificat), sauf si le widget standard est demandé.
     */
    public function toHtml($_version = 'dashboard') {
        if ($this->getConfiguration('defaultWidget') == 1) {
            return parent::toHtml($_version);
        }
        $replace = $this->preToHtml($_version);
        if (!is_array($replace)) {
            return $replace;
        }
        $version = jeedom::versionAlias($_version);

        $ids = array();
        $state = array();
        foreach ($this->getCmd() as $cmd) {
            $ids[$cmd->getLogicalId()] = $cmd->getId();
            if ($cmd->getType() == 'info') {
                $state[$cmd->getLogicalId()] = $cmd->execCmd();
            }
        }
        $jsonFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP;
        $replace['#lsm_ids#'] = json_encode($ids, $jsonFlags);
        $replace['#lsm_state#'] = json_encode($state, $jsonFlags);
        $replace['#lsm_url#'] = htmlspecialchars($this->getConfiguration('url'), ENT_QUOTES);
        $replace['#lsm_host#'] = htmlspecialchars($this->getHost(), ENT_QUOTES);

        // getTemplate() ne traduit pas : les {{textes}} du template passent par translate::exec()
        $template = translate::exec(getTemplate('core', $version, 'lite_site_monitoring', self::LOG), 'plugins/lite_site_monitoring/core/template/' . $version . '/lite_site_monitoring.html');
        $html = template_replace($replace, $template);
        return $this->postToHtml($_version, $html);
    }
}

class lite_site_monitoringCmd extends cmd {

    public function execute($_options = array()) {
        if ($this->getLogicalId() == 'refresh') {
            $eqLogic = $this->getEqLogic();
            log::add(lite_site_monitoring::LOG, 'debug', 'Commande refresh :. #' . $eqLogic->getId());
            lite_site_monitoring::checkAll(array($eqLogic));
        }
    }
}
