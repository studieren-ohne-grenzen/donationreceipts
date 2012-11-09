<?php
ini_set("display_errors", 1);

require_once $_GET["conf_path"]."/civicrm.settings.php";

require_once $civicrm_root . '/CRM/Core/Config.php';
$config =& CRM_Core_Config::singleton();

require_once '../backend.php';

$year = $_GET["year"];
$contact_id = $_GET["contact_id"];

$from_date = "$year-01-01 00:00:00";

if ($year == date("Y")) {
  $to_date = date("Y-m-d");
} else {
  $to_date = "$year-12-31";
}
$to_date .= " 23:59:59";

$params = array("contact_id" => $contact_id,
		"from_date"  => $from_date,
		"to_date"    => $to_date
		);

$result = generate_receipts($params);

if (empty($result)) {
  echo "<h1>Keine unbescheinigten Zuwendungen fuer diesen Zeitraum</h1>";
} else {
  echo "Zuwendungsbescheinigung fuer den Zeitraum vom ".date("j.n.", strtotime($result[$contact_id]['from_date']));
  echo " bis ".date("j.n.Y",strtotime($result[$contact_id]['to_date']))." erstellt<br />\n";

  echo "<a href='".$result[$_GET['contact_id']]['url']."'>Bescheinigung herunterladen</a>";

  echo "<script language='javascript'>window.opener.location.reload(true);</script>";
}


