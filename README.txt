== Installation ==

Das gesamte "foebud_civicrm" Verzeichnis muss in den Drupal "modules" Ordner
kopiert werden. (So dass es direkt neben dem Haupt-Modul von CiviCRM liegt.)
Danach muss das Modul in der Drupal-Verwaltung aktiviert werden (Drupal Menü
-> Verwaltung -> Strukturierung -> Module)

== Konfiguration ==

=== Custom Felder für die Dokumentablage ===

Damit die erstellten Bescheinigungen bei den einzelnen Kontakten 
hinterlegt werden können und um bei unterjährigen Bescheinigungen
feststellen zu können für welche Zeiträume schon Bescheinigungen
erstellt wurden müssen ein paar zusätzliche benutzerdefinierte
Felder angelegt werden.

Dazu muss zunächst unter 

  Verwalten -> Einstellungen -> Zusätzliche Daten

eine neue Feldgruppe angelegt werden mit

  Gruppenname: "Bescheinigungen"
  Benutzt für: "Contacts" (oder "Kontakte")
  Reihenfolge: egal
  Erlaubt diese benutzerdefinierte Feldgruppe mehrere Einträge?: [x] (ja)
  Darstellungsart: Reiter
  Diese Gruppe beim Seitenaufbau einklappen?: [ ] (nein)
  Diese Gruppe in der erweiterten Suche einklappen?: egal
  Ist das benutzerdefinierte Daten-Set aktiv? [x] (ja)
  ... restliche egal ...

Anschließend müssen in dieser Gruppe Felder mit folgenden Namen 
und Datentypen angelet werden:

  Z_Dateityp  / Alphanumerisch / Text
  Z_Datei     / Datei
  Z_Datum     / Datum
  Z_Datum_Von / Datum
  Z_Datum_Bis / Datum
  Z_Kommentar / Notiz

Für nicht angegebene Eingabefelder einfach die Vorgabewerte
übernehmen. (Der 'Z_' Prefix ist nötig weil CiviCRM eindeutige
Feldnamen für Benutzerdefinierte Felder erzwingt, auch wenn 
es sich um Felder in unterschiedlichen Feldgruppen handelt.
Der Prefix dient also dazu Namenskollisionen mit evtl. schon
vorhandenen Feldern zu vermeiden).

=== Bescheinigungs-Templates ===

Die Templates für Einzel- und Sammel-Zuwendungsbescheinigungen
befinden sich im Ordner foebud_civicrm/templates/bescheinigungen
im FoeBuD Drupal Modul.

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

In der Kontaktansicht wird automatisch ein neuer Reiter "FoeBuD" 
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
