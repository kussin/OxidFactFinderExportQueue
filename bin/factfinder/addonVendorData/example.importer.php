<?php
// IMPORT FILE NAME
define('IMPORT_PATH', realpath(dirname(__FILE__) . '/../../../import/factfinder/addonVendorData'));
define('IMPORT_FILE', IMPORT_PATH . '/example.csv');

// MAPPING
define('OXARTNUM', 'Artikelnummer');
define('OXEAN', 'EAN');

// CONFIG
define('CSV_LENGTH', 0);
define('CSV_SEPARATOR', ';');
define('CSV_ENCLOSURE', '"');

// ----------------- ### NO CHANGES BEHIND THIS LINE ### -----------------

// CHECK IMPORT FILE
if (!file_exists(IMPORT_FILE)) {
    die('Import file not found: ' . IMPORT_FILE);
}

// LOAD CSV
$aCsvData = wmdkffexport_vendor::getCsvData(IMPORT_FILE, CSV_LENGTH, CSV_SEPARATOR, CSV_ENCLOSURE);
if (count($aCsvData) < 1) {
    die('No data found in import file: ' . IMPORT_FILE);
}

// INIT
error_reporting (E_ALL);
ini_set ('display_errors', 'On');

// INIT OXID
require_once '../../../bootstrap.php';

// LOOP THROUGH CSV DATA
foreach ($aCsvData as $aData) {
    // GET OXID
    $sSku = $aRow[OXARTNUM];
    $sEan = $aRow[OXEAN];
    $sJsonData = json_encode($aData);

    // SQL STATEMENT
    //$sQuery = "INSERT INTO `wmdkffexport_vendor` (`sku`, `ean`, `json_data`) VALUES ('$sSku', '$sEan', '$sJsonData') ON DUPLICATE KEY UPDATE `json_data` = '$sJsonData';";
}