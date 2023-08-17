<?php

use OxidEsales\Eshop\Core\Registry;
use Spatie\ArrayToXml\ArrayToXml;
use Wmdk\FactFinderQueue\Traits\ExportTrait;
use Wmdk\FactFinderQueue\Traits\ThirdPartyConverterTrait;

/**
 * Class wmdkffexport_doofinder
 */
class wmdkffexport_doofinder extends oxubase
{
    use ExportTrait;
    use ThirdPartyConverterTrait;

    const PROCESS_CODE = 'DOOFINDER';

    const EXPORT_ADDITIONAL_ESCAPING = '';
    const EXPORT_DELIMITER = ',';
    const EXPORT_CATEGORY_DELIMITER = '|';

    private $_aExportData = NULL;
    private $_sTemplate = 'wmdkffexport_doofinder.tpl';

    private function _exportData() {
        // CONFIG
        $sExportFile = Registry::getConfig()->getShopConfVar('sShopDir') . Registry::getConfig()->getConfigParam('sWmdkFFExportDirectory') . $this->_sChannel . '.doofinder.xml';

        // INIT
        $this->_initThirdPartyConverter(
            Registry::getConfig()->getConfigParam('sWmdkFFDoofinderMapping'),
            Registry::getConfig()->getConfigParam('sWmdkFFDoofinderCDataFields'),
            Registry::getConfig()->getConfigParam('sWmdkFFDoofinderNumberFields'),
            Registry::getConfig()->getConfigParam('sWmdkFFDoofinderBooleanFields'),
            Registry::getConfig()->getConfigParam('sWmdkFFDoofinderDateFields')
        );
        
        // EXPORT
        try {
            // XML
            $oXML = new ArrayToXml(array(
                'products' => [
                    'product' => $this->_getData(),
                ]
            ), array(
                'rootElementName' => 'doofinder',
            ));

            // EXPORT
            $rXmlFile = fopen($sExportFile, 'w');

            fwrite($rXmlFile, $this->_fixCDataWrapper($oXML->prettify()->toXml()));

            fclose($rXmlFile);

            // COMPRESS (.gz)
            wmdkffexport_compressor::gzcompressfile($sExportFile);
            
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
                    $aData[] = $this->_convertData(array_combine($aKeys, $aValues));
                }
            }

            $this->_aExportData = $aData;
        }

        return $this->_aExportData;
    }

}