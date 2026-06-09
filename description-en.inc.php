<p>
    Prepares OXID article data for external product data exports and processes the export queue.
    The module currently supports <a href="https://www.fact-finder.com/" target="_blank">Doofinder</a> (CSV),
    <a href="https://spotler.com/" target="_blank">Spotler (ehem. Sooqr)</a> (XML),
    <a href="https://www.doofinder.com/" target="_blank">Doofinder</a> (CSV),
    <a href="https://www.flour.io/" target="_blank">flour POS</a> (CSV), and Trusted Shops rating imports.
</p>

<h3>Console commands</h3>
<p>
    OXID 7 cron processing is handled through OXID console commands. Use the commands from the OXID Composer project
    root.
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

<h3>Queue maintenance</h3>
<ul>
    <li><code>wmdkffexport:install:db</code> installs or updates the required queue database schema.</li>
    <li><code>wmdkffexport:maintenance:cleanup</code> removes orphaned queue records and marks records without product numbers as inactive.</li>
    <li><code>wmdkffexport:maintenance:reset</code> resets selected queue records for reprocessing by explicit filter options.</li>
</ul>

<h3>Cloned attributes</h3>
<p>
    Cloned attributes can map existing attributes, for example colors or sizes, into additional export fields.
    The OXID 7 admin mapping UI is still part of the migration backlog and must not be treated as fully migrated yet.
</p>

<h3>Product Name Builder</h3>
<p>
    The Product Name Builder can create export product names from queue table fields and selected attribute values.
    Field placeholders use square brackets, for example <code>[Title]</code> or <code>[Attributes(Year)]</code>.
</p>
<pre>&lt;b&gt;[Marke]&lt;/b&gt; [Title] [Attributes(Jahr)]&lt;br&gt;&lt;span&gt;[Variante]&lt;/span&gt;</pre>
<p>
    The <code>[Variante]</code> placeholder combines the article variant name and selected variant value.
</p>