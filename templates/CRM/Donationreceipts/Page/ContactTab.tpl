{*
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
*}
<ul>
  <li>Zuwendungsbescheinigung erstellen für: {foreach from=$bescheinigungen key=url item=label}<a target="_blank" href='{$url}'>{$label}</a>&nbsp;&nbsp;{/foreach}</li>
</ul>
<hr/>
<h2>Achtung: die folgenden Links führen Aktionen auf <b>allen</b> Kontakten aus, nicht nur auf dem aktuell ausgewählten Kontakt</h2>
(Workaround für die Probleme mit der Erweiterung der CiviCRM Menüleiste)
<ul>
  <li><a target='_blank' href='{$jahr}'>Jahresbescheinigungen</a></li>
</ul>
