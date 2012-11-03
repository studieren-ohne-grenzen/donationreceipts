<?php 
ini_set("display_errors", 1);

require_once __DIR__ . "/../config.php";
require_once $_GET["conf_path"]."/civicrm.settings.php";
require_once 'CRM/Core/Config.php';
$config =& CRM_Core_Config::singleton( );
require_once 'CRM/Core/Smarty.php';

$modules_url = dirname($config->resourceBase);

$template = CRM_Core_Smarty::singleton( );
array_unshift($template->template_dir, getcwd()."/../templates");

$url = $modules_url . "/foebud_civicrm/scripts/zuwendungsbescheinigung.php?contact_id=".$_GET["contact_id"]."&conf_path=".urlencode($_GET["conf_path"]);

$bescheinigungen = array();
for ($year = 2010; $year <= date("Y"); $year++) {
  $bescheinigungen["$url&year=$year"] = "$year&nbsp;&nbsp;";
}

$template->assign("bescheinigungen", $bescheinigungen);

$jahr = $modules_url . "/foebud_civicrm/scripts/jahresbescheinigungen.php?conf_path=".urlencode($_GET["conf_path"]);
$template->assign("jahr", $jahr);

echo $template->fetch("contact_tab.tpl");



