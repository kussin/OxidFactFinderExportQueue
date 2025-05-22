<?php
// INIT
error_reporting (E_ALL);
ini_set ('display_errors', 'On');

// INIT OXID
require_once '../../../bootstrap.php';

$sQuery = 'UPDATE wmdk_ff_export_queue t1
JOIN (
    SELECT MasterProductNumber
    FROM wmdk_ff_export_queue
    WHERE OXACTIVE = 1 AND Stock > 0
    GROUP BY MasterProductNumber
    HAVING COUNT(DISTINCT VariantsSizelistMarkup) > 1
) t2 ON t1.MasterProductNumber = t2.MasterProductNumber
SET t1.LASTSYNC = "0000-00-00 00:00:00",
    t1.ProcessIp = "' . $_SERVER['SERVER_ADDR'] . '",
    t1.OXTIMESTAMP = "0000-00-00 00:00:00"
WHERE t1.OXACTIVE = 1 AND t1.Stock > 0;';

$oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);

//var_dump($oResult);
exit();