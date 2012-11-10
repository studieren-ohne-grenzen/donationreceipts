== Installation ==

Zunaechst muessen die Pfade fuer Erweiterungen eingerichtet werden, falls sie
das noch nicht sind. Dazu muss der Dateisystm-Pfad fuer ein
Extension-Verzeichnis unter

   Administration->System Settings->Directories->CiviCRM Extensions Directory

eingestellt werden, zum Beispiel auf "/var/www/civicrm-extensions"; und die
Resource-URL muss unter

   Administration->System Settings->Resource URLs->Extension Resource URL

entsprechend gesetzt werden, zum Beispiel auf "https://<domain>/civicrm-extensions".

Die Extension kann nun installiert werden. Dazu wird das gesammte Verzeichnis
"sfe.donationreceipts" in das Extension-Verzeichnis kopiert. Danach ist die
Extension unter

   Administration->Customize Data and Screens->Manage Extensions

verfuegbar, und kann mit "Install" aktiviert werden.

== Konfiguration ==

=== Custom Felder für die Dokumentablage ===

Bei der Installation wird automatisch eine Benutzerdefinierte Feldgruppe
"Bescheinigungen" angelegt.

Dies kann eventuell fehlschlagen, falls es Namenskonflikte mit bereits
vorhandenen benutzerdefinierten Feldern gibt. In dem Fall sind wahrscheinlich
manuelle Eingriffe in der Datenbank nötig.

(Aufgrund einer etwas seltsamen Handhabung von Custom-Feldern in CiviCRM reicht
es derzeit *nicht* aus, die problematischen Felder oder Feldgruppen über die
Bedienoberfläche umzubenennen! Einzig das Löschen der betroffenen Felder oder
Feldgruppen -- mitsamt aller dort gespeicherten Daten -- würde den Konflikt
beheben...)

=== Zuwendungsarten ===

Zuwendungen werden auf den Bescheinigungen -- je nach Zuwendungsart -- entweder
als "Mitgliedsbeitrag" oder als "Geldzuwendung" ausgewiesen. Die Entscheidung
erfolgt anhand einer einfachen Heuristik: Wenn in der Bezeichnung der
Zuwendungsart das "Mitgliedsbeitrag" (oder "mitgliedsbeitrag") vorkommt -- auch
in Kombinationen wie "Mitgliedsbeitrag ermaeszigt", oder
"Foerdermitgliedsbeitrag" -- wird es als "Mitgliedsbeitrag" ausgewiesen; in
allen anderen Faellen -- zum Beispiel bei "Spenden" -- hingegen als
"Geldzuwendung".

Falls Zuwendungsarten konfiguriert sind, die mit dieser Heuristik nicht richtig
zugeordnet werden, muessen diese entsprechend umbenannt werden...

Die Verwaltung von Zuwendungsarten erfolgt unter:

   Administration->CiviContribute->Contribution Types

=== Bescheinigungs-Templates ===

Die Templates für Einzel- und Sammel-Zuwendungsbescheinigungen
befinden sich im Ordner "templates" innerhalb der Extension,
also beispielsweise "/var/www/civicrm-extensions/sfe.donationreceipts/templates/".

Beide Vorlagen sind als einfache HTML-Vorlagen angelegt und
werden durch den in CiviCRM enthaltenen HTML->PDF Konverter
in PDF-Bescheinigungen umgewandelt nachdem die jeweils 
passenden Werte eingetragen sind.

Die Texte in diesen beiden Vorlagen müssen natürlich der
jeweiligen Organisation angepasst werden. 

Der von CiviCRM genutzte HTML->PDF Konverter ist leider nicht
besonders gut, bei der Verwendung unterschiedlicher Schriftgrößen
oder kursiver Schrift gerät er zum Teil beim Zeilenumbruch 
durcheinander. Bis man die Templates soweit angepasst hat das
das Layout der ersten Bescheinigungsseite passt sind also leider
etwas HTML-Kenntnisse und Fummelarbeit nötig. :/

== Bedienung ==

=== Einzelbescheinigung unterjährig ===

In der Kontaktansicht wird automatisch ein neuer Reiter "Bescheinigungen erstellen" 
eingefügt, über diesen kann die Erstellung einer Bescheinigung
für den aktuellen Kontakt angestoßen werden. Der erfasste 
Bescheinigungszeitraum reicht dabei vom Enddatum der letzten
erstellten Bescheinigung bzw. vom Jahresanfang (der spätere
Wert von beiden gewinnt) bis zum aktuellen Datum.

Weiterhin ist es möglich eine Bescheinigung für das Vorjahr
zu erstellen.

In beiden Fällen ist sichergestellt das Zahlungen die in
schon erstellten Bescheinigungen erfasst sind nicht noch
ein zweites mal in neue Bescheinigungen aufgenommen werden.

Soll also eine Bescheinigung für einen eigentlich schon
bescheinigten Zeitraum, zB. nach Korrekturen an den
erfassten Zahlungen, noch einmal erstellt werden so sind
zunächst eventuell schon existierende Bescheinigungen für
den gewünschten Zeitraum manuell zu löschen (siehe nächsten 
Abschnitt).

=== Einzelne Bescheinigungen löschen ===

Entsprechende CiviCRM-Rechte vorausgesetzt können einzelne
Bescheinigungen gelöscht werden. Hierzu wird öben in jedem
Bescheinigungseintrag im neuen Reiter "Bescheinigungen"
ein "Löschen" Button eingeblendet wenn der aktuelle CiviCRM
Benutzer über das Recht zum Löschen von Kontaktdaten verfügt.

=== Gesammelte Jahresbescheinigungen ===

Ein Batchlauf zur Erstellung aller noch anstehenden 
Zuwendungsbescheinigungen für das Vorjahr können
über den Link "Jahresbescheinigungen" im "FoeBuD" Reiter
eines beliebigen Kontaktes angestoßen werden.

(Eigentlich sollte es hierfür einen Menüpunkt im CiviCRM
Menü geben statt des Umweges über einen konkreten Kontakt,
wegen eines Fehlers in der Menüverwaltung von CiviCRM ist
dies allerdings zur Zeit nicht zuverlässig möglich, daher
der aktuelle nicht wirklich logische Umweg ... ):

Die Erstellung jeder einzelnen Bescheinigung nimmt dabei
jeweils etwa eine Sekunde in Anspruch (hängt natürlich
von der genauen Ausstattung des Webservers ab, der
Schätzwert von einer Bescheinigung pro Sekunde hilft
aber die ungefähre Dauer des gesamten Laufes abzuschätzen).

Die erstellten Bescheinigungen werden dabei direkt bei
dem jeweiligen Kontakt hinterlegt und am Ende des Vorgangs
gesammelt als ein kombiniertes PDF-Dokument übertragen.
Innerhalb dieses Dokumentes sind die einzelnen Bescheinigungen
nach der CiviCRM-ID der jeweiligen Kontakte sortiert. Da diese
Sortierung garantiert zuverlässig ist, können die so gedruckten
Bescheinigungen beispielsweise bei einem Lettershop automatisch
mit ebenfalls aus CiviCRM exportierten Adressdaten zusammengeführt
werden.

Sollte die Erstellung der PDFs zu lange dauern und der
Browser mit einem Timeout abbrechen so kann zZ. das 
PDF-Dokument mit den gesammelten und sortierten Bescheinigungen
nach Abschluß der Generierung im /tmp Ordner als 
jahresbescheinigungen.pdf gefunden werden. 

In Zukunft möchte ich versuchen die erzeugte Sammeldatei statt
dessen bei den Bescheinigungen des aufrufenden Benutzers 
abzulegen, bin aber noch nicht so weit gekommen ...
