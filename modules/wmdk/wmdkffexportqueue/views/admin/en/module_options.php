<?php
$aLang = array(
    'charset' => 'utf-8',
	
    'SHOP_MODULE_GROUP_sWmdkFFGeneralSettings'   	    => 'General settings',
    'SHOP_MODULE_GROUP_sWmdkFFExportSettings'	        => 'Export settings',
    'SHOP_MODULE_GROUP_sWmdkFFQueueSettings'	        => 'Queue settings',
    'SHOP_MODULE_GROUP_sWmdkFFConverterSettings'	    => 'Queue Data Converter',
    'SHOP_MODULE_GROUP_sWmdkFFGpsrSettings'	    => 'EU GPSR Settings',
    'SHOP_MODULE_GROUP_sWmdkFFSooqrSettings'	        => 'Sooqr Settings',
    'SHOP_MODULE_GROUP_sWmdkFFDoofinderSettings'	    => 'Doofinder Settings',
    'SHOP_MODULE_GROUP_sWmdkFFFlourSettings'	        => 'flour POS Settings',
    'SHOP_MODULE_GROUP_sWmdkFFCronSettings'	            => 'Cron settings',
    'SHOP_MODULE_GROUP_sWmdkFFImportTSSettings'	        => 'Trusted Shops settings',
    'SHOP_MODULE_GROUP_sWmdkFFDebugSettings'	        => 'Debug settings',

    // GENERAL
    'SHOP_MODULE_sWmdkFFGeneralChannelList'             => 'Channel list',

    // EXPORT
    'SHOP_MODULE_sWmdkFFExportDirectory'                => 'Export directory',
    'SHOP_MODULE_sWmdkFFExportFields'                   => 'Export attribute field list',
    'SHOP_MODULE_sWmdkFFExportHtmlFields'               => 'HTML export attribute field list',
    'SHOP_MODULE_sWmdkFFExportOnlyActive'               => 'Only export active products',
    'SHOP_MODULE_sWmdkFFExportHidden'                   => 'Export hidden products',
    'SHOP_MODULE_sWmdkFFExportStockMin'                 => 'Min. stock for product export',
    'SHOP_MODULE_sWmdkFFExportDataLengthMax'	        => 'Max. dataset length incl. delimiter and enclosure',
    'SHOP_MODULE_sWmdkFFExportDataLengthMin'	        => 'Min. dataset length incl. delimiter and enclosure',
    'SHOP_MODULE_sWmdkFFExportTmpDelimiter' 	        => 'Tmp. delimiter',
    'SHOP_MODULE_sWmdkFFExportCsvDelimiter' 	        => 'CSV delimiter',
    'SHOP_MODULE_sWmdkFFExportCsvEnclosure' 	        => 'CSV enclosure',
    'SHOP_MODULE_sWmdkFFExportParentDisableQuery'       => 'SQL query to disable parents without variants',
    'SHOP_MODULE_sWmdkFFExportRemovePrefixAttributes'   => 'This attribute prefixes will be removed before exported',
    'SHOP_MODULE_sWmdkFFExportRemovePrefixCategories'   => 'This category prefixes will be removed before exported',
    'SHOP_MODULE_sWmdkFFExportRemoveCategoriesByName'   => 'Do not export this categories',
    'SHOP_MODULE_sWmdkFFExportCsvAttributes'            => 'This attributes contain csv values.',
    
    // QUEUE
    'SHOP_MODULE_sWmdkFFQueueLimit'	                    => 'Max. product updates per run',
    'SHOP_MODULE_iArticleStatus'	                    => 'Product status for run',
    'SHOP_MODULE_iArticleMinStock'	                    => 'Min. product stock for run',
    'SHOP_MODULE_sWmdkFFQueueAttributeGlue' 	        => 'Attribute delimiter',
    'SHOP_MODULE_sWmdkFFQueueAllowableTags' 	        => 'Allowed HTML tags',
    'SHOP_MODULE_sWmdkFFQueueFlagTopseller' 	        => 'Min. sales for Topseller flag',
    'SHOP_MODULE_sWmdkFFQueuePhpLimitTimeout' 	        => 'PHP Timeout',
    'SHOP_MODULE_sWmdkFFQueuePhpLimitMemory' 	        => 'PHP Memory limit',
    'SHOP_MODULE_sWmdkFFQueueResetLimit' 	            => 'Max. product resets per run',
    'SHOP_MODULE_bWmdkFFQueueUpdateSiblings' 	        => 'Update siblings',
    'SHOP_MODULE_bWmdkFFQueueUseCategoryPath' 	        => 'Use main category url instead of manufacturer url',

    // CONVERTER
    'SHOP_MODULE_sWmdkFFConverterFieldlistDouble'       => 'Attributes whose values are to be converted to decimals (double).',
    'SHOP_MODULE_aWmdkFFConverterRenameAttributes'      => 'Rename attribute names and values.',

    // EU GPSR
    'SHOP_MODULE_bWmdkFFGpsrExportProductWithNoPic'     => 'Enable export of products without pictures',

    // SOOQR
    'SHOP_MODULE_sWmdkFFSooqrMapping'	                => 'Mapping',
    'SHOP_MODULE_sWmdkFFSooqrCDataFields'	            => 'CDATA values',
    'SHOP_MODULE_sWmdkFFSooqrNumberFields'	            => 'Numeric values',
    'SHOP_MODULE_sWmdkFFSooqrBooleanFields'	            => 'Boolean values',
    'SHOP_MODULE_sWmdkFFSooqrDateFields'	            => 'Date values',

    // DOOFINDER
    'SHOP_MODULE_sWmdkFFDoofinderMapping'	            => 'Mapping',
    'SHOP_MODULE_sWmdkFFDoofinderCDataFields'	        => 'CDATA values',
    'SHOP_MODULE_sWmdkFFDoofinderNumberFields'	        => 'Numeric values',
    'SHOP_MODULE_sWmdkFFDoofinderBooleanFields'	        => 'Boolean values',
    'SHOP_MODULE_sWmdkFFDoofinderDateFields'	        => 'Date values',

    // FLOUR POS
    'SHOP_MODULE_sWmdkFFFlourMapping'	                => 'Mapping',
    'SHOP_MODULE_sWmdkFFFlourCDataFields'	            => 'CDATA values',
    'SHOP_MODULE_sWmdkFFFlourNumberFields'	            => 'Numeric values',
    'SHOP_MODULE_sWmdkFFFlourBooleanFields'	            => 'Boolean values',
    'SHOP_MODULE_sWmdkFFFlourDateFields'	            => 'Date values',

    // CRON TIMINGS
    'SHOP_MODULE_sWmdkFFCronResetExistingArticlesSinceDays'   => 'How many days should be considered for the reset?',
    'SHOP_MODULE_sWmdkFFCronResetExistingVariantsDays'        => 'On which week days should the variants be reset?',
    'SHOP_MODULE_sWmdkFFCronResetArticlesWithNoPicFrom'       => 'What time should the article image reset start?',
    'SHOP_MODULE_sWmdkFFCronResetArticlesWithNoPicTo'         => 'What time should the article image reset stop?',
    
    // TRUSTED SHOPS
    'SHOP_MODULE_sWmdkFFImportTSApiUrl'	                => 'JSON api url',
    
    // DEBUG
    'SHOP_MODULE_sWmdkFFDebugMode'                      => 'Debug mode',
    'SHOP_MODULE_sWmdkFFDebugCronjobIpList'             => 'cron-job.org IP List',
    'SHOP_MODULE_sWmdkFFDebugLogFileQueue'              => 'Log-File for queue',
    'SHOP_MODULE_sWmdkFFDebugLogFileExport' 	        => 'Log-File for product exports',
    'SHOP_MODULE_sWmdkFFDebugLogFileStock'  	        => 'Log-File for stock update',
);
