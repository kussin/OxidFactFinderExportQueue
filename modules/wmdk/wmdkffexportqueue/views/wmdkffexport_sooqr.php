<?php

use OxidEsales\Eshop\Core\Registry;
use Spatie\ArrayToXml\ArrayToXml;
use Wmdk\FactFinderQueue\Traits\ExportTrait;

/**
 * Class wmdkffexport_sooqr
 */
class wmdkffexport_sooqr extends oxubase
{
    use ExportTrait;

    const EXPORT_ADDITIONAL_ESCAPING = '';
    const EXPORT_DELIMITER = '|';
    const EXPORT_CATEGORY_DELIMITER = '###';

    private $_aExportData = NULL;
    private $_sTemplate = 'wmdkffexport_sooqr.tpl';
    
    private function _exportData() {
        // CONFIG
        $sExportFile = Registry::getConfig()->getShopConfVar('sShopDir') . Registry::getConfig()->getConfigParam('sWmdkFFExportDirectory') . $this->_sChannel . '.sooqr.xml';
        
        // EXPORT
        try {
            $rXmlFile = fopen($sExportFile, 'w');

            fwrite($rXmlFile, ArrayToXml::convert(array(
                    'products' => [
                        'product' => $this->_getData(),
                    ]
            ), array(
                'rootElementName' => 'sooqr',
            ), TRUE, 'UTF-8'));

            fclose($rXmlFile);
            
        } catch (Exception $oException) {
            // ERROR
            $this->_aResponse['validation_errors'][] = 'Exception ' . $oException->getMessage();
        }
        
        // LOG
        $this->_aResponse['channel'] = $this->_sChannel;
        $this->_aResponse['products'] = count($this->_aExportData);
    }

    protected function _getData() {
        if ($this->_aExportData === NULL) {
            $aKeys = NULL;
            $aData = array();

            foreach ($this->_aCsvData as $sRow) {
                if ($aKeys === NULL) {
                    $aKeys = explode(self::EXPORT_DELIMITER, $sRow);
                    continue;
                }

                $aValues = explode(self::EXPORT_DELIMITER, $sRow);

                if (count($aKeys) == count($aValues)) {
                    $aData[] = array_combine($aKeys, $aValues);
                }
            }

            $this->_aExportData = $aData;
        }

        return $this->_aExportData;
    }

}