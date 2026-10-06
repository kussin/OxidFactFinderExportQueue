<p>
    Prepares OXID article data for external product data exports and processes the export queue.
    The module currently supports <a href="https://www.fact-finder.com/" target="_blank">FACT Finder</a> (CSV),
    <a href="https://spotler.com/" target="_blank">Spotler (formerly Sooqr)</a> (XML),
    <a href="https://www.doofinder.com/" target="_blank">Doofinder</a> (CSV),
    <a href="https://www.flour.io/" target="_blank">flour POS</a> (CSV), and Trusted Shops rating imports.
</p>

<h3>Console commands</h3>
<p>
    OXID 7 cron processing is handled through OXID console commands. Use the commands from the OXID Composer project
    root. Commands return structured JSON.
</p>
<pre>vendor/bin/oe-console wmdkffexport:install:db
vendor/bin/oe-console wmdkffexport:cron:queue --cron
vendor/bin/oe-console wmdkffexport:cron:reset --cron
vendor/bin/oe-console wmdkffexport:export:factfinder --channel=wh1_live_en --shop-id=1 --lang=1 --cron
vendor/bin/oe-console wmdkffexport:export:sooqr --channel=wh1_live_en --shop-id=1 --lang=1 --cron
vendor/bin/oe-console wmdkffexport:export:doofinder --channel=wh1_live_en --shop-id=1 --lang=1 --cron
vendor/bin/oe-console wmdkffexport:export:flour --channel=wh1_live_en --shop-id=1 --lang=1 --cron
vendor/bin/oe-console wmdkffexport:import:trusted-shops --channel=wh1_live_en --cron
vendor/bin/oe-console wmdkffexport:maintenance:sync-flour --dry-run
vendor/bin/oe-console wmdkffexport:maintenance:cleanup
vendor/bin/oe-console wmdkffexport:maintenance:reset --help</pre>

<h3>Queue maintenance</h3>
<ul>
    <li><code>wmdkffexport:install:db</code> installs or updates the required queue schema and flour article columns.</li>
    <li><code>wmdkffexport:maintenance:sync-flour</code> synchronizes flour POS fields from <code>oxarticles</code> and marks changed queue records for reprocessing.</li>
    <li><code>wmdkffexport:maintenance:cleanup</code> removes orphaned queue records and marks records without product numbers as inactive.</li>
    <li><code>wmdkffexport:maintenance:reset</code> resets selected queue records for reprocessing by explicit filter options.</li>
</ul>

<h3>KUSSIN | FACT Finder Export Queue - Monitor</h3>
<p>
    <strong>KUSSIN | FACT Finder Export Queue - Monitor</strong> provides a sortable and filterable queue grid with
    100 records per page. Its CSV export contains every database field for all filtered matches.
    Manually selected articles are fully reset by OXID across all channels, shops, and languages.
    The sortable, non-filterable <code>FlourMSRP</code> column matches the export value: MSRP, or the
    selling price when no MSRP is available.
    The <strong>Preview</strong> column resolves relative deeplinks against the current shop URL and
    opens the product detail page directly. The latest filter selection is retained for the admin
    session and can be removed with <strong>Clear filters</strong>. The design follows the other Kussin
    admin modules, while a loading indicator provides immediate feedback for filtering, sorting,
    pagination, exports, and manual resets. An estimate also shows the expected queue completion time
    and remaining duration based on the queue limit and two-minute interval. The process indicator
    refresh interval can be set to 5, 10, 15, 20, or 30 seconds in the module settings. Clicking an
    OXID opens the corresponding article master-data tab in a new OXID admin browser tab. For a
    selected article, a reverse link in the bottom Actions bar opens a new monitor tab prefiltered to
    the parent article and all variants.
</p>

<h3>flour POS</h3>
<p>
    <code>FlourSaleAmount</code> follows the OXID 6 discount calculation
    <code>100 - (FlourPrice * 100 / reference price)</code>. The selling price is used as the reference
    when no MSRP is available.
</p>

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
