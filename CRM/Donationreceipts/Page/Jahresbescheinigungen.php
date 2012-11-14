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

require_once 'CRM/Core/Page.php';

class CRM_Donationreceipts_Page_Jahresbescheinigungen extends CRM_Core_Page {
  function run() {
    if (!file_exists("/usr/bin/pdftk")) {
      echo "'pdftk' nicht installiert, Erstellung des Sammel-PDF nicht moeglich";
      exit;
    }

    require_once 'backend.php';

    $year = @$_GET["year"];

    if (!$year) $year = date("Y") - 1; // Vorjahr

    $from_date = "$year-01-01 00:00:00";

    if ($year == date("Y")) {
      $to_date = date("Y-m-d");
    } else {
      $to_date = "$year-12-31";
    }
    $to_date .= " 23:59:59";

    $params = array(
      "from_date"  => $from_date,
      "to_date"    => $to_date,
      "comment"    => "Jahresbescheinigung $year"
    );

    // Creating a lot of documents can take quite long...
    set_time_limit(0);

    $result = generate_receipts($params);

    if (empty($result)) {
      $this->assign("have_result", false);
      $this->assign("from_date", $from_date);
      $this->assign("to_date", $to_date);
      parent::run();
    } else {
      $this->assign("have_result", true);

      // $result is already sorted by contact_id
      $docs = array_flip(array_map(function ($elem) { return $elem['filename']; }, $result));

      $tmp_name = tempnam("/tmp", "civicrm");

      $config =& CRM_Core_Config::singleton( );

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
        parent::run();    /* Generate error page... */
      }
    }    /* !empty($result) */

  }
}
