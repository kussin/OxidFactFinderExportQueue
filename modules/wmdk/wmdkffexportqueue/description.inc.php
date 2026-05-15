<p>Bereitet die Produkte für den Export vor und führt den Export aus. (Es wird auch
    <a href="https://spotler.com/" target="_blank">Spotler (ehem. Sooqr)</a> (XML),
    <a href="https://www.doofinder.com/" target="_blank">Doofinder</a> (CSV) und
    <a href="https://www.flour.io/" target="_blank">flour</a> (CSV) unterstützt.)</p>
<h2>Folgende Exporte/Cronjobs können z.Zt. ausgeführt werden</h2>
<ul>
    <li><a href="/index.php?cl=wmdkffexport_queue" target="_blank">Queue Prozess</a></li>
    <li><a href="/index.php?cl=wmdkffexport_queue" target="_blank">Reset Data</a></li>
    <li>FACT Finder Export</li>
    <li>Spotler (ehem. Sooqr) Export</li>
    <li>Doofinder Export</li>
    <li><span style="color: red">NEU:</span> flour Export</li>
    <li>Trusted Shops Import</li>
</ul>
<h2><span style="color: red">NEU:</span> &quot;Cloned Attributes&quot;</h2>
<p>&quot;Cloned Attributes&quot; bieten die Möglichkeit bestehende Attribute (z.B. Fraben oder Größen) zu Mappen und
    automatisch in einem neuen Feld zu exportieren. (Es können alle Funtionen wie beim Attribute &quot;Attributes&quot;
    genutzt werden.</p>
<p><a href="/index.php?cl=wmdkffexport_mapping" target="_blank"><button type="button">CSV Vorlage generieren</button></a></p>
<h2><span style="color: red">NEU:</span> Product Name Builder</h2>
<p>Unter den Moduleinstellungen (Tab: "Einstellungen") gibt es jetzt den Bereich Product Name Builder-Einstellungen unter
    dem man den Product Name Builder aktivieren und konfigurieren kann.<br>
    Der Product Name Builder bietet die Möglichkeit, den OXID 6 Standard Produktnamen (<code class="sql">oxarticle__title</code>)
    durch einen zusammengesetzten Produktnamen (z.B. Marke + Produktnamen) zu ersetzen.</p>
<p>Wenn der Product Name Builder aktiviert wurde, dann muss noch ein Namensmuster erstellt werden, dass aus einfachem
    HTML-Markup und Datenbankfeldern der Datenbanktabelle <code class="sql">wmdk_ff_export_queue</code> bestehen darf,
    wobei die Datenbankfelder mit eckigen Klammer umschlossen werden müssen (z.B. <code>[Title]</code>).<br>
    Des Weiteren kann auch auf die Attribute in den Datenbankfelder <code class="sql">Attributes</code> und
    <code class="sql">ClonedAttributes</code>, sowie auf die Variante zugegriffen werden.</p>
<h3>Musterbeispiel für Marke, Produktname & Variante</h3>
<pre class="text">&#x3C;b&#x3E;[Marke]&#x3C;/b&#x3E; [Title] [Attributes(Jahr)]&#x3C;br&#x3E;&#x3C;span&#x3E;[Variante]&#x3C;/span&#x3E;</pre>
<p><b>HINWEIS:</b> Der Tag <code>[Variante]</code> fügt immer die Felder <code class="sql">oxarticle__oxvarname</code>
    und <code class="sql">oxarticle__oxvarselect</code> als Tupel hinzu.</p>