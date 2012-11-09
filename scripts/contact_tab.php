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

ini_set("display_errors", 1);

require_once __DIR__ . "/../config.php";
require_once $_GET["conf_path"]."/civicrm.settings.php";
require_once 'CRM/Core/Config.php';
$config =& CRM_Core_Config::singleton( );
require_once 'CRM/Core/Smarty.php';

$template = CRM_Core_Smarty::singleton( );
array_unshift($template->template_dir, getcwd()."/../templates");

$url = $config->extensionsURL . "/sfe.donationreceipts/scripts/zuwendungsbescheinigung.php?contact_id=".$_GET["contact_id"]."&conf_path=".urlencode($_GET["conf_path"]);

$bescheinigungen = array();
for ($year = date("Y") - 1; $year <= date("Y"); $year++) {
  $bescheinigungen["$url&year=$year"] = "$year&nbsp;&nbsp;";
}

$template->assign("bescheinigungen", $bescheinigungen);

$jahr = $config->extensionsURL . "/sfe.donationreceipts/scripts/jahresbescheinigungen.php?conf_path=".urlencode($_GET["conf_path"]);
$template->assign("jahr", $jahr);

echo $template->fetch("contact_tab.tpl");



