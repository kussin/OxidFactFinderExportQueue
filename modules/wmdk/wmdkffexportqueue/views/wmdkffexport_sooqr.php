<?php

use OxidEsales\Eshop\Core\Registry;
use Spatie\ArrayToXml\ArrayToXml;
use Wmdk\FactFinderQueue\Traits\ExportTrait;
use Wmdk\FactFinderQueue\Traits\ThirdPartyConverterTrait;
use Wmdk\FactFinderQueue\Traits\XmlValidatorTrait;

/**
 * Class wmdkffexport_sooqr
 */
class wmdkffexport_sooqr extends oxubase
{
    use ExportTrait;
    use ThirdPartyConverterTrait;
    use XmlValidatorTrait;

    const PROCESS_CODE = 'SOOQR';

    const EXPORT_ADDITIONAL_ESCAPING = '';
    const EXPORT_DELIMITER = '|';
    const EXPORT_CATEGORY_DELIMITER = '###';

    private $_aExportData = NULL;
    private $_sTemplate = 'wmdkffexport_sooqr.tpl';

    private function _exportData() {
        // CONFIG
        $sExportFile = Registry::getConfig()->getShopConfVar('sShopDir') . Registry::getConfig()->getConfigParam('sWmdkFFExportDirectory') . $this->_sChannel . '.sooqr.xml';

        // INIT
        $this->_initThirdPartyConverter(
            Registry::getConfig()->getConfigParam('sWmdkFFSooqrMapping'),
            Registry::getConfig()->getConfigParam('sWmdkFFSooqrCDataFields'),
            Registry::getConfig()->getConfigParam('sWmdkFFSooqrNumberFields'),
            Registry::getConfig()->getConfigParam('sWmdkFFSooqrBooleanFields'),
            Registry::getConfig()->getConfigParam('sWmdkFFSooqrDateFields')
        );
        
        // EXPORT
        try {
            // XML
            $oXML = new ArrayToXml(array(
                'products' => [
                    'product' => $this->_getData(),
                ]
            ), array(
                'rootElementName' => 'sooqr',
            ));

            // EXPORT
            $rXmlFile = fopen($sExportFile, 'w');

            fwrite($rXmlFile, $oXML->toXml());

            fclose($rXmlFile);
            
        } catch (Exception $oException) {
            // ERROR
            $this->_aResponse['validation_errors'][] = 'XML Creation Exception: ' . $oException->getMessage();
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

                $aValues = explode($this->_getTmpExportDelimiter(), $sRow);

                if (count($aKeys) == count($aValues)) {
                    $aNode = $this->_convertData(array_combine($aKeys, $aValues));

                    if ($this->_validateXmlNode($aNode)) {
                        $aData[] = $aNode;
                    }
                } else {
                    // ERROR
                    var_dump(array(
                        'delimiter' => self::EXPORT_DELIMITER,
                        'tmp_delimiter' => $this->_getTmpExportDelimiter(),
                        'row' => $sRow,
                        'keys' => $aKeys,
                        'values' => $aValues,
                    ));
                    die();
                }
            }

            $this->_aExportData = $aData;
        }

        return $this->_aExportData;
    }

}