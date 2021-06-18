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
    /*     * *************************Attributs****************************** */
    
  /*
   * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
   * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
	public static $_widgetPossibility = array();
   */
    
    /*     * ***********************Methode static*************************** */

    public static function snif($_eqlogic) {
        log::add('lite_site_monitoring', 'debug', 'snif :. Lancement');
        
        $url = $_eqlogic->getConfiguration("url");
        $curl = array();
        $curl[1] = self::getCurlLatence($url);
        $curl[1]["online"] = self::getCurl($url);
        $curl[1]["url"] = $url;
        
        log::add('lite_site_monitoring', 'debug', 'snif :. checkAndUpdateCmd');
        $_eqlogic->checkAndUpdateCmd('online', $curl[1]["online"]);
        $_eqlogic->checkAndUpdateCmd('dns_resolution', $curl[1]["dns_resolution"]);
        $_eqlogic->checkAndUpdateCmd('tcp_established', $curl[1]["tcp_established"]);
        $_eqlogic->checkAndUpdateCmd('ssl_handshake_done', $curl[1]["ssl_handshake_done"]);
        $_eqlogic->checkAndUpdateCmd('TTFB', $curl[1]["TTFB"]);
        $_eqlogic->checkAndUpdateCmd('latence', $curl[1]["latence"]);
        
    }

    public static function getCurl($_url){
        log::add('lite_site_monitoring', 'debug', 'getCurl :. Lancement');
        
        exec("curl -Ik ".$_url, $output);
        
        foreach ($output as $search) {
            if(preg_match("[200]", $search)) { return TRUE; }
            elseif(preg_match("[302]", $search)) { return TRUE; }
            else{ return FALSE; }
        }    
        
        return FALSE;
    }
    
    public static function getCurlLatence($_url){
        log::add('lite_site_monitoring', 'debug', 'getCurlLatence :. Lancement');
        exec('curl -w "dns_resolution|%{time_namelookup}\ntcp_established|%{time_connect}\nssl_handshake_done|%{time_appconnect}\nTTFB|%{time_starttransfer}\n" -o /dev/null -s '.$_url, $output);
        $return = array();
        $cpt = 0;
        foreach ($output as $search) {
            $tmp = explode("|", $search);
            $return[$tmp[0]] = $tmp[1];
            $cpt = floatval($tmp[1]) + $cpt;
        }
        $return["latence"] = strval($cpt);
        return $return;
    }
   
    public static function cron5() {
        log::add('lite_site_monitoring', 'debug', 'cron5 :. Lancement');
        $eqLogics = eqLogic::byType('lite_site_monitoring');
        foreach ($eqLogics as $eqlogic) {
            if ($eqlogic->getIsEnable() == 1) {
                log::add('lite_site_monitoring', 'debug', 'cron5 :. #ID#' . $eqlogic->getId());
                self::snif($eqlogic);
            }
        }
    }

    /*     * *********************Méthodes d'instance************************* */
    

    public function postSave() {
        log::add('lite_site_monitoring', 'debug', 'preSave :. Lancement');
        
        $cmd = $this->getCmd(null, 'refresh');
        if (!is_object($cmd)) {
            $cmd = new lite_site_monitoringCmd();
            $cmd->setLogicalId('refresh');
            $cmd->setName(__('Rafraîchir', __FILE__));
            $cmd->setIsVisible(1);
        }
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
        
        $cmd = $this->getCmd(null, 'online');
        if (!is_object($cmd)) {
            $cmd = new lite_site_monitoringCmd();
            $cmd->setLogicalId('online');
            $cmd->setName(__('OnLine', __FILE__));
            $cmd->setIsVisible(1);
        }
        $cmd->setType('info');
        $cmd->setSubType('binary');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
        
        $cmd = $this->getCmd(null, 'dns_resolution');
        if (!is_object($cmd)) {
            $cmd = new lite_site_monitoringCmd();
            $cmd->setLogicalId('dns_resolution');
            $cmd->setName(__('DNS Resolution', __FILE__));
            $cmd->setIsVisible(0);
        }
        $cmd->setType('info');
        $cmd->setSubType('string');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
        
        $cmd = $this->getCmd(null, 'tcp_established');
        if (!is_object($cmd)) {
            $cmd = new lite_site_monitoringCmd();
            $cmd->setLogicalId('tcp_established');
            $cmd->setName(__('TCP', __FILE__));
            $cmd->setIsVisible(0);
        }
        $cmd->setType('info');
        $cmd->setSubType('string');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
        
        $cmd = $this->getCmd(null, 'ssl_handshake_done');
        if (!is_object($cmd)) {
            $cmd = new lite_site_monitoringCmd();
            $cmd->setLogicalId('ssl_handshake_done');
            $cmd->setName(__('SSL', __FILE__));
            $cmd->setIsVisible(0);
        }
        $cmd->setType('info');
        $cmd->setSubType('string');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
        
        $cmd = $this->getCmd(null, 'TTFB');
        if (!is_object($cmd)) {
            $cmd = new lite_site_monitoringCmd();
            $cmd->setLogicalId('TTFB');
            $cmd->setName(__('TTFB', __FILE__));
            $cmd->setIsVisible(0);
        }
        $cmd->setType('info');
        $cmd->setSubType('string');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
        
        $cmd = $this->getCmd(null, 'latence');
        if (!is_object($cmd)) {
            $cmd = new lite_site_monitoringCmd();
            $cmd->setLogicalId('latence');
            $cmd->setName(__('Latence', __FILE__));
            $cmd->setIsVisible(1);
        }
        $cmd->setType('info');
        $cmd->setSubType('string');
        $cmd->setEqLogic_id($this->getId());
        $cmd->save();
    }

    /*     * **********************Getteur Setteur*************************** */
}

class lite_site_monitoringCmd extends cmd {
    /*     * *************************Attributs****************************** */
    
    /*
      public static $_widgetPossibility = array();
    */
    
    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /*
     * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }
     */

  // Exécution d'une commande  
     public function execute($_options = array()) {
        $eqlogic = $this->getEqLogic();
        switch ($this->getLogicalId()) { //vérifie le logicalid de la commande 			
            case 'refresh': // LogicalId de la commande rafraîchir que l’on a créé dans la méthode Postsave 
                log::add('lite_site_monitoring', 'debug', 'Commande :. Lancement : #ID#' . $eqlogic->getId());
                lite_site_monitoring::snif($eqlogic);
                log::add('lite_site_monitoring', 'debug', '---------------------------------------------------------------------------------------');
                break;
        }
     }

    /*     * **********************Getteur Setteur*************************** */
}


