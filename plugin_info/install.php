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

// Fonction exécutée automatiquement après l'installation du plugin
  function lite_site_monitoring_install() {

  }

// Fonction exécutée automatiquement après la mise à jour du plugin
  function lite_site_monitoring_update() {
    // Ré-enregistre chaque équipement pour créer ou mettre à jour ses commandes
    foreach (eqLogic::byType('lite_site_monitoring') as $eqLogic) {
      try {
        // Anciennes mesures de temps en texte (secondes) : historique vidé avant le passage en numérique (ms)
        foreach (array_keys(lite_site_monitoring::TIMINGS) as $logicalId) {
          $cmd = $eqLogic->getCmd('info', $logicalId);
          if (is_object($cmd) && $cmd->getSubType() == 'string') {
            $cmd->emptyHistory();
            log::add('lite_site_monitoring', 'info', $eqLogic->getHumanName() . ' : historique de ' . $cmd->getName() . ' vidé (passage en millisecondes)');
          }
        }
        $eqLogic->save();
      } catch (Exception $e) {
        log::add('lite_site_monitoring', 'error', $eqLogic->getHumanName() . ' : ' . $e->getMessage());
      }
    }
    // Premier contrôle immédiat, pour ne pas afficher les anciennes valeurs jusqu'au prochain cron
    try {
      lite_site_monitoring::checkAll(eqLogic::byType('lite_site_monitoring', true));
    } catch (Exception $e) {
      log::add('lite_site_monitoring', 'error', 'Contrôle après mise à jour : ' . $e->getMessage());
    }
  }

// Fonction exécutée automatiquement après la suppression du plugin
  function lite_site_monitoring_remove() {

  }

?>
