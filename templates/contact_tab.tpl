<ul>
  <li>Zuwendungsbescheinigung erstellen für: {foreach from=$bescheinigungen key=url item=label}<a target="_blank" href='{$url}'>{$label}</a>&nbsp;&nbsp;{/foreach}</li>
</ul>
<hr/>
<h2>Achtung: die folgenden Links führen Aktionen auf <b>allen</b> Kontakten aus, nicht nur auf dem aktuell ausgewählten Kontakt</h2>
(Workaround für die Probleme mit der Erweiterung der CiviCRM Menüleiste)
<ul>
  <li><a target='_blank' href='{$jahr}'>Jahresbescheinigungen</a></li>
</ul>
