<p>
    Bereitet OXID-Artikeldaten für externe Produktdatenexporte vor und verarbeitet die Export-Queue.
    Das Modul unterstützt aktuell <a href="https://www.fact-finder.com/" target="_blank">FACT Finder</a> (CSV),
    <a href="https://spotler.com/" target="_blank">Spotler (ehem. Sooqr)</a> (XML),
    <a href="https://www.doofinder.com/" target="_blank">Doofinder</a> (CSV),
    <a href="https://www.flour.io/" target="_blank">flour POS</a> (CSV) sowie den Import von
    Trusted-Shops-Bewertungen.
</p>

<h3>Console Commands</h3>
<p>
    Die Cron-Verarbeitung in OXID 7 erfolgt über OXID Console Commands. Die Befehle werden im
    OXID-Composer-Projektroot ausgeführt und liefern strukturiertes JSON zurück.
</p>
<pre>vendor/bin/oe-console wmdkffexport:install:db
vendor/bin/oe-console wmdkffexport:cron:queue --cron
vendor/bin/oe-console wmdkffexport:cron:reset --cron
vendor/bin/oe-console wmdkffexport:export:factfinder --channel=wh1_live_de --shop-id=1 --lang=0 --cron
vendor/bin/oe-console wmdkffexport:export:sooqr --channel=wh1_live_de --shop-id=1 --lang=0 --cron
vendor/bin/oe-console wmdkffexport:export:doofinder --channel=wh1_live_de --shop-id=1 --lang=0 --cron
vendor/bin/oe-console wmdkffexport:export:flour --channel=wh1_live_de --shop-id=1 --lang=0 --cron
vendor/bin/oe-console wmdkffexport:import:trusted-shops --channel=wh1_live_de --cron
vendor/bin/oe-console wmdkffexport:maintenance:sync-flour --dry-run
vendor/bin/oe-console wmdkffexport:maintenance:cleanup
vendor/bin/oe-console wmdkffexport:maintenance:reset --help</pre>

<h3>Queue Maintenance</h3>
<ul>
    <li><code>wmdkffexport:install:db</code> installiert oder aktualisiert das benötigte Queue-Datenbankschema und die Flour-Artikelspalten.</li>
    <li><code>wmdkffexport:maintenance:sync-flour</code> synchronisiert die Flour-POS-Felder aus <code>oxarticles</code> und markiert geänderte Queue-Datensätze zur erneuten Verarbeitung.</li>
    <li><code>wmdkffexport:maintenance:cleanup</code> entfernt verwaiste Queue-Datensätze und deaktiviert Datensätze ohne Artikelnummer.</li>
    <li><code>wmdkffexport:maintenance:reset</code> setzt gezielt gefilterte Queue-Datensätze für eine erneute Verarbeitung zurück.</li>
</ul>

<h3>KUSSIN | FACT Finder Export Queue - Monitor</h3>
<p>
    Unter <strong>KUSSIN | FACT Finder Export Queue - Monitor</strong> steht ein sortierbares und filterbares Queue-Grid
    mit 100 Datensätzen pro Seite bereit. Der CSV-Export enthält alle Datenbankfelder der gefilterten
    Treffer. Manuell ausgewählte Artikel werden anhand ihrer OXID vollständig über alle Channels,
    Shops und Sprachen zurückgesetzt. Die sortierbare, nicht filterbare Spalte <code>FlourMSRP</code>
    entspricht dem Exportwert: UVP, beziehungsweise Verkaufspreis, falls keine UVP vorhanden ist.
    Die Spalte <strong>Vorschau</strong> öffnet relative Deeplinks über die aktuelle Shop-URL direkt
    auf der Produktdetailseite. Die letzte Filterauswahl bleibt während der Admin-Sitzung erhalten
    und kann über <strong>Filter zurücksetzen</strong> gelöscht werden. Das Design orientiert sich an
    den weiteren Kussin-Backend-Modulen; eine Ladeanzeige gibt bei Filterung, Sortierung, Seitenwechsel,
    Export und manuellem Zurücksetzen unmittelbar Rückmeldung. Eine Schätzung zeigt außerdem die
    voraussichtliche Fertigstellung und Restdauer der Queue auf Basis des Queue-Limits und des
    Zwei-Minuten-Intervalls an. Das Aktualisierungsintervall der Prozessanzeige kann in den
    Modul-Einstellungen auf 5, 10, 15, 20 oder 30 Sekunden gesetzt werden. Ein Klick auf die OXID
    öffnet den zugehörigen Artikel-Stamm in einem neuen OXID-Admin-Tab. Bei ausgewähltem Artikel führt
    ein Gegenlink in der unteren Actions-Leiste in einen neuen, auf Elternartikel und Varianten
    vorgefilterten Monitor-Tab.
</p>

<h3>Flour POS</h3>
<p>
    <code>FlourSaleAmount</code> verwendet die OXID-6-Rabattlogik
    <code>100 - (FlourPrice * 100 / Referenzpreis)</code>. Fehlt die UVP, wird der Verkaufspreis als
    Referenz verwendet.
</p>

<h3>Cloned Attributes</h3>
<p>
    Cloned Attributes können bestehende Attribute, zum Beispiel Farben oder Größen, in zusätzliche Exportfelder
    mappen. Die OXID-7-Admin-Mapping-UI ist noch Teil des Migrations-Backlogs und gilt noch nicht als vollständig
    migriert.
</p>

<h3>Product Name Builder</h3>
<p>
    Der Product Name Builder kann Export-Produktnamen aus Feldern der Queue-Tabelle und ausgewählten Attributwerten
    erzeugen. Feld-Platzhalter werden in eckigen Klammern angegeben, zum Beispiel <code>[Title]</code> oder
    <code>[Attributes(Jahr)]</code>.
</p>
<pre>&lt;b&gt;[Marke]&lt;/b&gt; [Title] [Attributes(Jahr)]&lt;br&gt;&lt;span&gt;[Variante]&lt;/span&gt;</pre>
<p>
    Der Platzhalter <code>[Variante]</code> kombiniert den Variantennamen und den gewählten Variantenwert des Artikels.
</p>
