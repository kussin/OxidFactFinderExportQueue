<?php

use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Traits\ExportTrait;

/**
 * Export controller for the standard FactFinder CSV.
 */
class wmdkffexport_export extends oxubase
{
    use ExportTrait;

    /**
     * Identifier for the export process type.
     */
    const PROCESS_CODE = 'FACTFINDER';

    /**
     * Additional escaping characters for CSV output.
     */
    const EXPORT_ADDITIONAL_ESCAPING = '"';
    /**
     * Field delimiter for CSV output.
     */
    const EXPORT_DELIMITER = '|';
    /**
     * Delimiter for category path segments.
     */
    const EXPORT_CATEGORY_DELIMITER = '|';
    
    private $_sTemplate = 'wmdkffexport_export.tpl';

    /**
     * Write the assembled CSV data to disk and update response metadata.
     */
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
