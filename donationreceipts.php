<?php
/*
    sfe.donationreceipts extension for CiviCRM
    Copyright (C) 2011,2012 FoeBuD e.V.
    Copyright (C) 2012 Software fuer Engagierte e.V.

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once 'donationreceipts.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function donationreceipts_civicrm_config(&$config) {
  _donationreceipts_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function donationreceipts_civicrm_xmlMenu(&$files) {
  _donationreceipts_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function donationreceipts_civicrm_install() {
  return _donationreceipts_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function donationreceipts_civicrm_uninstall() {
  return _donationreceipts_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function donationreceipts_civicrm_enable() {
  return _donationreceipts_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function donationreceipts_civicrm_disable() {
  return _donationreceipts_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function donationreceipts_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _donationreceipts_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function donationreceipts_civicrm_managed(&$entities) {
  return _donationreceipts_civix_civicrm_managed($entities);
}


function donationreceipts_civicrm_tabs( &$tabs, $contactID ) {
    require_once "config.php";

    $config =& CRM_Core_Config::singleton( );

    $tabs[] = array( 'id'    => 'foebudTab',
                     'url'   => $config->extensionsURL
                              . "/sfe.donationreceipts/scripts/contact_tab.php?contact_id=$contactID&conf_path="
                              . urlencode(realpath(conf_path())),
                     'title' => FOEBUD_MENU_NAME,
                     'weight' => 300
                     );
}
