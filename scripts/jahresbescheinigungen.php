<?php
ini_set("display_errors", 1);

if (!file_exists("/usr/bin/pdftk")) {
  echo "'pdftk' nicht installiert, Erstellung des Sammel-PDF nicht moeglich";
  exit;
}

require_once $_GET["conf_path"]."/civicrm.settings.php";

require_once $civicrm_root . '/CRM/Core/Config.php';
$config =& CRM_Core_Config::singleton();

require_once $civicrm_root . '/api/v2/Foebud.php';

$year = @$_GET["year"];

if (!$year) $year = date("Y") - 1; // Vorjahr

$from_date = "$year-01-01 00:00:00";

if ($year == date("Y")) {
  $to_date = date("Y-m-d");
} else {
  $to_date = "$year-12-31";
}
$to_date .= " 23:59:59";

$params = array("from_date"  => $from_date,
		"to_date"    => $to_date
		);

// Creating a lot of documents can take quite long...
set_time_limit(0);

$result = civicrm_foebud_zuwendungsbescheinigung($params);

if (empty($result)) {
  die("Keine Bescheinigungen fuer diesen Zeitraum ($from_date - $to_date)<br/>oder Bescheinigungen wurden bereits erstellt");
}

$docs = array();

foreach ($result as $contact_id => $data) {
  $docs[$data['filename']] = $data['total_amount'];
}

asort($docs);
$docs = array_reverse($docs, true);

$tmp_name = tempnam("/tmp", "civicrm");

$cmd = "cd " . $config->customFileUploadDir . "; pdftk " . join(" ", array_keys($docs)) . " cat output $tmp_name";

system($cmd);

if (file_exists($tmp_name)) {
   header("Content-type: application/pdf");
   header("Content-Disposition: attachment; filename='jahresbescheinigungen-$year.pdf'");
   header('Content-Transfer-Encoding: binary');
   header('Accept-Ranges: bytes');

   header('Cache-Control: private');
   header('Pragma: private');
   header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

   echo file_get_contents($tmp_name);
   unlink($tmp_name);
} else {
  echo "PDF creation failed";
}

