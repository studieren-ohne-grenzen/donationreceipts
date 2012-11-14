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

class CRM_Donationreceipts_Page_ContactTab extends CRM_Core_Page {
  function run() {
    require_once "config.php";
    CRM_Utils_System::setTitle(FOEBUD_MENU_NAME);

    $bescheinigungen = array();
    for ($year = date("Y") - 1; $year <= date("Y"); $year++) {
      $url = CRM_Utils_System::url("civicrm/donationreceipts/zuwendungsbescheinigung", "snippet=1&contact_id={$_GET['contact_id']}&year=$year");
      $bescheinigungen[$url] = "$year&nbsp;&nbsp;";
    }

    $this->assign("bescheinigungen", $bescheinigungen);

    parent::run();
  }
}
