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


