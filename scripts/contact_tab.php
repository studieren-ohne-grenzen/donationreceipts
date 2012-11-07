<?php 
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
for ($year = 2010; $year <= date("Y"); $year++) {
  $bescheinigungen["$url&year=$year"] = "$year&nbsp;&nbsp;";
}

$template->assign("bescheinigungen", $bescheinigungen);

$jahr = $config->extensionsURL . "/sfe.donationreceipts/scripts/jahresbescheinigungen.php?conf_path=".urlencode($_GET["conf_path"]);
$template->assign("jahr", $jahr);

echo $template->fetch("contact_tab.tpl");



