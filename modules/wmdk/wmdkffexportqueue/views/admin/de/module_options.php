<?php
$aLang = array(
    'charset' => 'utf-8',
	
    'SHOP_MODULE_GROUP_sWmdkFFGeneralSettings'   	    => 'Grund-Einstellungen',
    'SHOP_MODULE_GROUP_sWmdkFFExportSettings'   	    => 'Export-Einstellungen',
    'SHOP_MODULE_GROUP_sWmdkFFQueueSettings'	        => 'Queue-Einstellungen',
    'SHOP_MODULE_GROUP_sWmdkFFConverterSettings'	    => 'Queue Data Converter',
    'SHOP_MODULE_GROUP_sWmdkFFSooqrSettings'	        => 'Sooqr-Einstellungen',
    'SHOP_MODULE_GROUP_sWmdkFFDoofinderSettings'	    => 'Doofinder-Einstellungen',
    'SHOP_MODULE_GROUP_sWmdkFFCronSettings'	            => 'Cronjob Einstellungen',
    'SHOP_MODULE_GROUP_sWmdkFFImportTSSettings'	        => 'Trusted Shops Einstellungen',
    'SHOP_MODULE_GROUP_sWmdkFFDebugSettings'    	    => 'Debug-Einstellungen',

    // GENERAL
    'SHOP_MODULE_sWmdkFFGeneralChannelList'             => 'Channelliste',

    // EXPORT
    'SHOP_MODULE_sWmdkFFExportDirectory'                => 'Exportverzeichnis',
    'SHOP_MODULE_sWmdkFFExportFields'                   => 'Export-Attributfelderliste',
    'SHOP_MODULE_sWmdkFFExportHtmlFields'               => 'HTML Export-Attributfelderliste',
    'SHOP_MODULE_sWmdkFFExportOnlyActive'               => 'Nur aktive Produkte exportieren',
    'SHOP_MODULE_sWmdkFFExportHidden'                   => 'Versteckten Produkte exportieren',
    'SHOP_MODULE_sWmdkFFExportStockMin'                 => 'Mindestbestand für den Produktexport',
    'SHOP_MODULE_sWmdkFFExportDataLengthMax'	        => 'Max. Datensatzlänge inkl. Trennzeichen und Text-Wrapper',
    'SHOP_MODULE_sWmdkFFExportDataLengthMin'	        => 'Min. Datensatzlänge inkl. Trennzeichen und Text-Wrapper',
    'SHOP_MODULE_sWmdkFFExportCsvDelimiter' 	        => 'CSV Trennzeichen',
    'SHOP_MODULE_sWmdkFFExportCsvEnclosure' 	        => 'CSV Text-Wrapper',
    'SHOP_MODULE_sWmdkFFExportParentDisableQuery'       => 'SQL Query zur Deaktiverung von Elternprodukten ohne Varianten',
    'SHOP_MODULE_sWmdkFFExportRemovePrefixAttributes'   => 'Diese Prefixe werden beim Export von Atrributen entfernt',
    'SHOP_MODULE_sWmdkFFExportRemovePrefixCategories'   => 'Diese Prefixe werden beim Export von Kategorien entfernt',
    'SHOP_MODULE_sWmdkFFExportRemoveCategoriesByName'   => 'Diese Kategorien nicht exportieren (ggf. alle Bezeichner)',
    'SHOP_MODULE_sWmdkFFExportCsvAttributes'            => 'Diese Attribute enthalten CSV Werte',
    
    // QUEUE
    'SHOP_MODULE_sWmdkFFQueueLimit'	                    => 'Max. Produkt-Updates pro Run',
    'SHOP_MODULE_iArticleStatus'	                    => 'Produktstatus für Selektion',
    'SHOP_MODULE_iArticleMinStock'	                    => 'Min. Produktbestand für Selektion',
    'SHOP_MODULE_sWmdkFFQueueAttributeGlue' 	        => 'Attribut-Trennzeichen',
    'SHOP_MODULE_sWmdkFFQueueAllowableTags' 	        => 'Erlaubte HTML Tags',
    'SHOP_MODULE_sWmdkFFQueueFlagTopseller' 	        => 'Min. Verkäufe für Topseller-Flag',
    'SHOP_MODULE_sWmdkFFQueuePhpLimitTimeout' 	        => 'PHP Timeout',
    'SHOP_MODULE_sWmdkFFQueuePhpLimitMemory' 	        => 'PHP Memory Limit',
    'SHOP_MODULE_sWmdkFFQueueResetLimit' 	            => 'Max. Produkt-Resets pro Run',
    'SHOP_MODULE_bWmdkFFQueueUpdateSiblings' 	        => 'Update der Geschwister-Varianten',
    'SHOP_MODULE_bWmdkFFQueueUseCategoryPath' 	        => 'Benutze die Hauptkategorie-URL anstelle der Marken-URL',

    // CONVERTER
    'SHOP_MODULE_sWmdkFFConverterFieldlistDouble'       => 'Attribute deren Werte in Fließkommazahlen (Double) umgewandelt werden sollen.',
    'SHOP_MODULE_aWmdkFFConverterRenameAttributes'      => 'Umbenennung von Attributnamen und -werten',

    // SOOQR
    'SHOP_MODULE_sWmdkFFSooqrMapping'	                => 'Mapping',
    'SHOP_MODULE_sWmdkFFSooqrCDataFields'	            => 'CDATA Werte',
    'SHOP_MODULE_sWmdkFFSooqrNumberFields'	            => 'Numerische Werte',
    'SHOP_MODULE_sWmdkFFSooqrBooleanFields'	            => 'Boolische Werte',
    'SHOP_MODULE_sWmdkFFSooqrDateFields'	            => 'Datumswerte',

    // DOOFINDER
    'SHOP_MODULE_sWmdkFFDoofinderMapping'	            => 'Mapping',
    'SHOP_MODULE_sWmdkFFDoofinderCDataFields'	        => 'CDATA Werte',
    'SHOP_MODULE_sWmdkFFDoofinderNumberFields'	        => 'Numerische Werte',
    'SHOP_MODULE_sWmdkFFDoofinderBooleanFields'	        => 'Boolische Werte',
    'SHOP_MODULE_sWmdkFFDoofinderDateFields'	        => 'Datumswerte',

    // CRON TIMINGS
    'SHOP_MODULE_sWmdkFFCronResetExistingArticlesSinceDays'   => 'Wie viele Tage sollten für den Reset berücksichtigt werden?',
    'SHOP_MODULE_sWmdkFFCronResetExistingVariantsDays'        => 'An welchen Wochentagen sollen die Varianten zurückgesetzt werden?',
    'SHOP_MODULE_sWmdkFFCronResetArticlesWithNoPicFrom'       => 'Um wie viel Uhr sollte das Zurücksetzen der Artikelbilder beginnen?',
    'SHOP_MODULE_sWmdkFFCronResetArticlesWithNoPicTo'         => 'Um wie viel Uhr sollte das Zurücksetzen der Artikelbilder aufhören?',

    // TRUSTED SHOPS
    'SHOP_MODULE_sWmdkFFImportTSApiUrl'	                => 'JSON Webservice URL',
    
    // DEBUG
    'SHOP_MODULE_sWmdkFFDebugMode'                      => 'Debugmodus',
    'SHOP_MODULE_sWmdkFFDebugCronjobIpList'             => 'cron-job.org IP Liste (werden geloggt als Cronjob)',
    'SHOP_MODULE_sWmdkFFDebugLogFileQueue'              => 'Log-Datei der Queue',
    'SHOP_MODULE_sWmdkFFDebugLogFileExport' 	        => 'Log-Datei des Produktexports',
    'SHOP_MODULE_sWmdkFFDebugLogFileStock'  	        => 'Log-Datei des Bestandsexports',
);
