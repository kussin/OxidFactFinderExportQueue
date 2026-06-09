<p>
    Bereitet OXID Artikeldaten fuer externe Produktdatenexporte vor und verarbeitet die Export Queue.
    Das Modul unterstuetzt aktuell <a href="https://www.fact-finder.com/" target="_blank">Doofinder</a> (CSV),
    <a href="https://spotler.com/" target="_blank">Spotler (ehem. Sooqr)</a> (XML),
    <a href="https://www.doofinder.com/" target="_blank">Doofinder</a> (CSV),
    <a href="https://www.flour.io/" target="_blank">flour POS</a> (CSV) und den Import von Trusted Shops
    Bewertungen.
</p>

<h3>Console Commands</h3>
<p>
    Die Cron-Verarbeitung in OXID 7 erfolgt ueber OXID Console Commands. Die Befehle werden im OXID Composer-Projektroot
    ausgefuehrt.
</p>
<pre>vendor/bin/oe-console wmdkffexport:install:db
vendor/bin/oe-console wmdkffexport:cron:queue --cron
vendor/bin/oe-console wmdkffexport:cron:reset --cron
vendor/bin/oe-console wmdkffexport:export:factfinder --cron
vendor/bin/oe-console wmdkffexport:export:sooqr --cron
vendor/bin/oe-console wmdkffexport:export:doofinder --cron
vendor/bin/oe-console wmdkffexport:export:flour --cron
vendor/bin/oe-console wmdkffexport:import:trusted-shops --cron
vendor/bin/oe-console wmdkffexport:maintenance:cleanup
vendor/bin/oe-console wmdkffexport:maintenance:reset --help</pre>

<h3>Queue Maintenance</h3>
<ul>
    <li><code>wmdkffexport:install:db</code> installiert oder aktualisiert das benoetigte Queue-Datenbankschema.</li>
    <li><code>wmdkffexport:maintenance:cleanup</code> entfernt verwaiste Queue-Datensaetze und deaktiviert Datensaetze ohne Artikelnummer.</li>
    <li><code>wmdkffexport:maintenance:reset</code> setzt gezielt gefilterte Queue-Datensaetze fuer eine erneute Verarbeitung zurueck.</li>
</ul>

<h3>Cloned Attributes</h3>
<p>
    Cloned Attributes koennen bestehende Attribute, zum Beispiel Farben oder Groessen, in zusaetzliche Exportfelder
    mappen. Die OXID 7 Admin-Mapping-UI ist noch Teil des Migrations-Backlogs und gilt noch nicht als vollstaendig
    migriert.
</p>

<h3>Product Name Builder</h3>
<p>
    Der Product Name Builder kann Export-Produktnamen aus Feldern der Queue-Tabelle und ausgewaehlten Attributwerten
    erzeugen. Feld-Platzhalter werden in eckigen Klammern angegeben, zum Beispiel <code>[Title]</code> oder
    <code>[Attributes(Jahr)]</code>.
</p>
<pre>&lt;b&gt;[Marke]&lt;/b&gt; [Title] [Attributes(Jahr)]&lt;br&gt;&lt;span&gt;[Variante]&lt;/span&gt;</pre>
<p>
    Der Platzhalter <code>[Variante]</code> kombiniert den Variantennamen und den gewaehlten Variantenwert des Artikels.
</p>
