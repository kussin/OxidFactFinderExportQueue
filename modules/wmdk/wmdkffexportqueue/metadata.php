<?php
/**
 * Metadata version
 */
$sMetadataVersion = '1.1';
 
/**
 * Module information
 */
$aModule = array(
    'id'           => 'wmdkffexportqueue',
    'title'        => 'Kussin | OXID 6 FACT Finder Export Queue',
    'description'  => 'Bereitet die Produkte für den Export vor und führt den Export aus. (Es wird auch Sooqr (XML) und Doofinder (CSV) unterstützt.)',
    'thumbnail'    => 'module.png',
    'version'      => '1.10.3',
    'author'       => 'Daniel Kussin',
    'url'          => 'https://www.kussin.de',
    'email'        => 'daniel.kussin@kussin.de',
	
    'files'        => array(
        'wmdkffexport_queue' => 'wmdk/wmdkffexportqueue/views/wmdkffexport_queue.php',
        'wmdkffexport_export' => 'wmdk/wmdkffexportqueue/views/wmdkffexport_export.php',
        'wmdkffexport_sooqr' => 'wmdk/wmdkffexportqueue/views/wmdkffexport_sooqr.php',
        'wmdkffexport_doofinder' => 'wmdk/wmdkffexportqueue/views/wmdkffexport_doofinder.php',
        'wmdkffexport_reset' => 'wmdk/wmdkffexportqueue/views/wmdkffexport_reset.php',
        'wmdkffexport_ts' => 'wmdk/wmdkffexportqueue/views/wmdkffexport_ts.php',
        
        'wmdkffexport_helper' => 'wmdk/wmdkffexportqueue/core/wmdkffexport_helper.php',
        'wmdkffexport_compressor' => 'wmdk/wmdkffexportqueue/core/wmdkffexport_compressor.php',
	),
    
    'extend'       => array(
        'article_extend' => 'wmdk/wmdkffexportqueue/controllers/admin/wmdkffqueuearticle_extend',
        'article_files' => 'wmdk/wmdkffexportqueue/controllers/admin/wmdkffqueuearticle_files',
        'article_main' => 'wmdk/wmdkffexportqueue/controllers/admin/wmdkffqueuearticle_main',
        'article_pictures' => 'wmdk/wmdkffexportqueue/controllers/admin/wmdkffqueuearticle_pictures',
        'article_seo' => 'wmdk/wmdkffexportqueue/controllers/admin/wmdkffqueuearticle_seo',
        'article_stock' => 'wmdk/wmdkffexportqueue/controllers/admin/wmdkffqueuearticle_stock',
        'article_variant' => 'wmdk/wmdkffexportqueue/controllers/admin/wmdkffqueuearticle_variant',
    ),

	'settings' => array(
        // GENERAL
		array('group' => 'sWmdkFFGeneralSettings', 'name' => 'sWmdkFFGeneralChannelList', 'type' => 'str', 'value' => 'demo::1::0'),
        
        // EXPORT
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportDirectory', 'type' => 'str', 'value' => 'export/factfinder/productData/'),
        
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportFields', 'type' => 'str', 'value' => 'ProductNumber,MasterProductNumber,Title,Short,HasProductImage,ImageURL,SuggestPictureURL,HasFromPrice,Price,MSRP,BasePrice,Stock,Description,Deeplink,Marke,CategoryPath,HasCustomAsnRestrictions,Attributes,NumericalAttributes,SearchAttributes,SearchKeywords,EAN,MPN,DISTEAN,Weight,Rating,RatingCnt,HasNewFlag,HasTopFlag,HasSaleFlag,SaleAmount,HasVariantsSizelist,VariantsSizelistMarkup,SoldAmount,DateInsert,DateModified,TrustedShopsRating,TrustedShopsRatingCnt,TrustedShopsRatingPercentage'),
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportHtmlFields', 'type' => 'str', 'value' => 'VariantsSizelistMarkup'),
		
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportOnlyActive', 'type' => 'bool', 'value' => 1),
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportHidden', 'type' => 'bool', 'value' => 0),
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportStockMin', 'type' => 'str', 'value' => 1),
        
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportDataLengthMax', 'type' => 'str', 'value' => 50000),
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportDataLengthMin', 'type' => 'str', 'value' => 475),
        
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportCsvDelimiter', 'type' => 'str', 'value' => '|'),
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportCsvEnclosure', 'type' => 'str', 'value' => '"'),
        
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportParentDisableQuery', 'type' => 'str', 'value' => 'UPDATE wmdk_ff_export_queue, oxarticles SET	wmdk_ff_export_queue.OXACTIVE = 0 WHERE	(wmdk_ff_export_queue.OXID = oxarticles.OXID) AND ((wmdk_ff_export_queue.OXACTIVE = 1) AND (wmdk_ff_export_queue.MasterProductNumber = "")) AND ((oxarticles.OXVARCOUNT > 0) AND (oxarticles.OXVARSTOCK = 0) AND ((oxarticles.OXTITLE LIKE "%2010%") OR (oxarticles.OXTITLE LIKE "%2011%") OR (oxarticles.OXTITLE LIKE "%2012%") OR (oxarticles.OXTITLE LIKE "%2013%") OR (oxarticles.OXTITLE LIKE "%2014%") OR (oxarticles.OXTITLE LIKE "%2015%") OR (oxarticles.OXTITLE LIKE "%2016%") OR (oxarticles.OXTITLE LIKE "%2017%")));'),
                
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportRemovePrefixAttributes', 'type' => 'str', 'value' => 'FF_'),
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportRemovePrefixCategories', 'type' => 'str', 'value' => '__OPT'), 
        
		array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportRemoveCategoriesByName', 'type' => 'str', 'value' => 'Sale'),

        array('group' => 'sWmdkFFExportSettings', 'name' => 'sWmdkFFExportCsvAttributes', 'type' => 'str', 'value' => 'Terrain,Schwung,Speed'),

        // QUEUE
		array('group' => 'sWmdkFFQueueSettings', 'name' => 'sWmdkFFQueueLimit', 'type' => 'str', 'value' => 150),
		array('group' => 'sWmdkFFQueueSettings', 'name' => 'iArticleStatus', 'type' => 'str', 'value' => 1),
		array('group' => 'sWmdkFFQueueSettings', 'name' => 'iArticleMinStock', 'type' => 'str', 'value' => 0),
		array('group' => 'sWmdkFFQueueSettings', 'name' => 'sWmdkFFQueueAttributeGlue', 'type' => 'str', 'value' => '|'),
		array('group' => 'sWmdkFFQueueSettings', 'name' => 'sWmdkFFQueueAllowableTags', 'type' => 'str', 'value' => ''),
        
		array('group' => 'sWmdkFFQueueSettings', 'name' => 'sWmdkFFQueueFlagTopseller', 'type' => 'str', 'value' => 10),
        
		array('group' => 'sWmdkFFQueueSettings', 'name' => 'sWmdkFFQueuePhpLimitTimeout', 'type' => 'str', 'value' => 900),
		array('group' => 'sWmdkFFQueueSettings', 'name' => 'sWmdkFFQueuePhpLimitMemory', 'type' => 'str', 'value' => '512M'),
		array('group' => 'sWmdkFFQueueSettings', 'name' => 'sWmdkFFQueueResetLimit', 'type' => 'str', 'value' => 75),
		
		array('group' => 'sWmdkFFQueueSettings', 'name' => 'bWmdkFFQueueUseCategoryPath', 'type' => 'bool', 'value' => 0),

        // CONVERTER
        array('group' => 'sWmdkFFConverterSettings', 'name' => 'sWmdkFFConverterFieldlistDouble', 'type' => 'str', 'value' => 'Terrain,Schwung,Speed'),
        array('group' => 'sWmdkFFConverterSettings', 'name' => 'aWmdkFFConverterRenameAttributes', 'type' => 'aarr', 'value' => array(
            'Step On Größe' => 'Größe',
            'Step On Size' => 'Size',
        )),

        // Sooqr
        array('group' => 'sWmdkFFSooqrSettings', 'name' => 'sWmdkFFSooqrMapping', 'type' => 'aarr', 'value' => array(
            'ProductNumber' => 'id',
            'MasterProductNumber' => 'parent',
            'Title' => 'title',
            'Marke' => 'brand',
            'Deeplink' => 'link',
            'ImageURL' => 'image_link',
            'Description' => 'description',
            'Price' => 'price',
            'MSRP' => 'normal_price',
            'CategoryPath' => 'category',
        )),
        array('group' => 'sWmdkFFSooqrSettings', 'name' => 'sWmdkFFSooqrCDataFields', 'type' => 'str', 'value' => 'Title,ImageURL,SuggestPictureURL,Short,Description,Deeplink,Marke,CategoryPath,Attributes,NumericalAttributes,SearchAttributes,SearchKeywords,VariantsSizelistMarkup'),
        array('group' => 'sWmdkFFSooqrSettings', 'name' => 'sWmdkFFSooqrNumberFields', 'type' => 'str', 'value' => 'Price,MSRP,BasePrice,Stock,Weight,Rating,RatingCnt,SaleAmount,SoldAmount,TrustedShopsRating,TrustedShopsRatingCnt,TrustedShopsRatingPercentage'),
        array('group' => 'sWmdkFFSooqrSettings', 'name' => 'sWmdkFFSooqrBooleanFields', 'type' => 'str', 'value' => 'HasProductImage,HasCustomAsnRestrictions,HasNewFlag,HasTopFlag,HasSaleFlag,HasVariantsSizelist'),
        array('group' => 'sWmdkFFSooqrSettings', 'name' => 'sWmdkFFSooqrDateFields', 'type' => 'str', 'value' => 'DateInsert,DateModified'),

        // Doofinder
        array('group' => 'sWmdkFFDoofinderSettings', 'name' => 'sWmdkFFDoofinderMapping', 'type' => 'aarr', 'value' => array(
            'ProductNumber' => 'id',
            'MasterProductNumber' => 'parent',
            'Title' => 'title',
            'Marke' => 'brand',
            'MPN' => 'mpn',
            'Price' => 'price',
            'Deeplink' => 'link',
            'ImageURL' => 'image_link',
            'Description' => 'description',
            'MSRP' => 'normal_price',
            'CategoryPath' => 'category',
        )),
        array('group' => 'sWmdkFFDoofinderSettings', 'name' => 'sWmdkFFDoofinderCDataFields', 'type' => 'str', 'value' => 'Title,ImageURL,SuggestPictureURL,Short,Description,Deeplink,Marke,CategoryPath,Attributes,NumericalAttributes,SearchAttributes,SearchKeywords,VariantsSizelistMarkup'),
        array('group' => 'sWmdkFFDoofinderSettings', 'name' => 'sWmdkFFDoofinderNumberFields', 'type' => 'str', 'value' => 'Price,MSRP,BasePrice,Stock,Weight,Rating,RatingCnt,SaleAmount,SoldAmount,TrustedShopsRating,TrustedShopsRatingCnt,TrustedShopsRatingPercentage'),
        array('group' => 'sWmdkFFDoofinderSettings', 'name' => 'sWmdkFFDoofinderBooleanFields', 'type' => 'str', 'value' => 'HasProductImage,HasCustomAsnRestrictions,HasNewFlag,HasTopFlag,HasSaleFlag,HasVariantsSizelist'),
        array('group' => 'sWmdkFFDoofinderSettings', 'name' => 'sWmdkFFDoofinderDateFields', 'type' => 'str', 'value' => 'DateInsert,DateModified'),
        
        // CRON TIMINGS
        array('group' => 'sWmdkFFCronSettings', 'name' => 'sWmdkFFCronResetExistingArticlesSinceDays', 'type' => 'str', 'value' => '-2 days'),
        array('group' => 'sWmdkFFCronSettings', 'name' => 'sWmdkFFCronResetExistingVariantsDays', 'type' => 'str', 'value' => '3,6'),
		array('group' => 'sWmdkFFCronSettings', 'name' => 'sWmdkFFCronResetArticlesWithNoPicFrom', 'type' => 'str', 'value' => '02:05:00'),
        array('group' => 'sWmdkFFCronSettings', 'name' => 'sWmdkFFCronResetArticlesWithNoPicTo', 'type' => 'str', 'value' => '03:15:00'),

        // TRUSTED SHOPS
		array('group' => 'sWmdkFFImportTSSettings', 'name' => 'sWmdkFFImportTSApiUrl', 'type' => 'str', 'value' => 'https://cdn1.api.trustedshops.com/shops/XEB1234567898D431456F97193879F/products/public/v1/feed.json'),
        
        // DEBUG
		array('group' => 'sWmdkFFDebugSettings', 'name' => 'sWmdkFFDebugMode', 'type' => 'bool', 'value' => '0'),
        
		array('group' => 'sWmdkFFDebugSettings', 'name' => 'sWmdkFFDebugCronjobIpList', 'type' => 'str', 'value' => '195.201.26.157'),
		
		array('group' => 'sWmdkFFDebugSettings', 'name' => 'sWmdkFFDebugLogFileQueue', 'type' => 'str', 'value' => 'log/WMDK_FF_QUEUE.log'),
		array('group' => 'sWmdkFFDebugSettings', 'name' => 'sWmdkFFDebugLogFileExport', 'type' => 'str', 'value' => 'log/WMDK_FF_EXPORT.log'),
		array('group' => 'sWmdkFFDebugSettings', 'name' => 'sWmdkFFDebugLogFileStock', 'type' => 'str', 'value' => 'log/WMDK_FF_STOCK.log'),
    ),
	
    'templates' => array(
        'wmdkffexport_queue.tpl' => 'wmdk/wmdkffexportqueue/views/tpl/wmdkffexport_queue.tpl',
        'wmdkffexport_export.tpl' => 'wmdk/wmdkffexportqueue/views/tpl/wmdkffexport_export.tpl',
        'wmdkffexport_sooqr.tpl' => 'wmdk/wmdkffexportqueue/views/tpl/wmdkffexport_sooqr.tpl',
        'wmdkffexport_doofinder.tpl' => 'wmdk/wmdkffexportqueue/views/tpl/wmdkffexport_doofinder.tpl',
        'wmdkffexport_reset.tpl' => 'wmdk/wmdkffexportqueue/views/tpl/wmdkffexport_reset.tpl',
        'wmdkffexport_ts.tpl' => 'wmdk/wmdkffexportqueue/views/tpl/wmdkffexport_ts.tpl',
    ),
);