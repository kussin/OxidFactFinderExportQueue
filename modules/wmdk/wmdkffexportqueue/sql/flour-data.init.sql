-- Turn off foreign key check
SET FOREIGN_KEY_CHECKS = 0;

-- Update data wmdk_ff_export_queue
UPDATE
    wmdk_ff_export_queue a
    oxarticles b,
SET    
    a.Tax = IF(b.OXVAT IS NOT NULL, b.OXVAT, 19),
        
--    a.Season = b.WMDKSEASONLABEL,
--    a.HasUsedFlag = b.WMDKUSED,
    
    a.FlourId = b.WMDKFLOURID,
    a.FlourActive = b.WMDKFLOURACTIVE,
    a.FlourPrice = b.WMDKFLOURWAREHOUSEPRICE,
    a.FlourSaleAmount = IF(b.WMDKFLOURWAREHOUSEPRICE>0, CONCAT(ROUND((b.WMDKFLOURWAREHOUSEPRICE/b.OXTPRICE)*100, 0), '%'), NULL),
    a.FlourShortUrl = b.WMDKFLOURSHORTURL,
    
    a.LASTSYNC = a.LASTSYNC,
    a.OXTIMESTAMP = a.OXTIMESTAMP
WHERE
    (a.OXID = b.OXID)
    AND (
        (b.WMDKFLOURID IS NOT NULL)
        AND (b.WMDKFLOURID NOT LIKE '')
    );

-- Turn on foreign key check
SET FOREIGN_KEY_CHECKS = 1;