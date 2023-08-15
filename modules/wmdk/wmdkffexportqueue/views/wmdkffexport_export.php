<?php

use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Traits\ExportTrait;

/**
 * Class wmdkffexport_export
 */
class wmdkffexport_export extends oxubase
{
    use ExportTrait;

    const EXPORT_ADDITIONAL_ESCAPING = '"';
    const EXPORT_DELIMITER = '|';
    const EXPORT_CATEGORY_DELIMITER = '|';
    
    private $_sTemplate = 'wmdkffexport_export.tpl';

    private function _exportData() {
        // CONFIG
        $sExportFile = Registry::getConfig()->getShopConfVar('sShopDir') . Registry::getConfig()->getConfigParam('sWmdkFFExportDirectory') . $this->_sChannel . '.csv';

        // EXPORT
        try {
            $rCsvFile = fopen($sExportFile, 'w');

            foreach ($this->_aCsvData as $sRow) {
                fwrite($rCsvFile, $sRow . PHP_EOL);
            }

            fclose($rCsvFile);

        } catch (Exception $oException) {
            // ERROR
            $this->_aResponse['validation_errors'][] = 'Exception ' . $oException->getMessage();
        }

        // LOG
        $this->_aResponse['channel'] = $this->_sChannel;
        $this->_aResponse['products'] = count($this->_aCsvData) - 1;


        // DEBUG
//        echo implode("\n", $this->_aCsvData);
    }

}