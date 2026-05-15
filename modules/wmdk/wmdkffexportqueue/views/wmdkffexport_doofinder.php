<?php

use OxidEsales\Eshop\Core\Registry;
use Spatie\ArrayToXml\ArrayToXml;
use Wmdk\FactFinderQueue\Traits\ExportTrait;
use Wmdk\FactFinderQueue\Traits\ThirdPartyConverterTrait;
use Wmdk\FactFinderQueue\Traits\XmlValidatorTrait;

/**
 * Class wmdkffexport_doofinder
 */
class wmdkffexport_doofinder extends oxubase
{
    use ExportTrait;
    use ThirdPartyConverterTrait;
    use XmlValidatorTrait;

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
            $aProducts = $this->_getData();
            $this->_validateDoofinderContent($aProducts);

            // XML
            $oXML = new ArrayToXml(array(
                'products' => [
                    'product' => $aProducts,
                ]
            ), array(
                'rootElementName' => 'doofinder',
            ));

            if ((bool) Registry::getConfig()->getConfigParam('bWmdkFFExportPrettifyXml')) {
                $oXML->prettify();
            }

            // EXPORT
            $rXmlFile = fopen($sExportFile, 'w');

            fwrite($rXmlFile, $oXML->toXml());

            fclose($rXmlFile);

            if ($this->_validateXmlFile($sExportFile)) {
                // COMPRESS (.gz)
                wmdkffexport_compressor::gzcompressfile($sExportFile);

            } else {
                $this->_aResponse['success'] = FALSE;
            }
            
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

                    // REMOVE SPECIAL CHARACTERS FROM NODES
//                    $aNode = $this->_replaceSpecialChars($aNode);

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

    private function _validateDoofinderContent($aProducts)
    {
        $aValidation = array(
            'checked_products' => count($aProducts),
            'warning_count' => 0,
            'warnings' => array(),
        );

        foreach ($aProducts as $aProduct) {
            $sProductId = isset($aProduct['id']) ? $this->_getScalarDiagnosticValue($aProduct['id']) : '';
            $this->_collectDoofinderContentWarnings($aProduct, $sProductId, '', $aValidation);
        }

        $this->_aResponse['doofinder_content_validation'] = $aValidation;
    }

    private function _collectDoofinderContentWarnings($mValue, $sProductId, $sPath, &$aValidation)
    {
        if (is_array($mValue)) {
            foreach ($mValue as $sKey => $mChildValue) {
                $sChildPath = ($sPath == '') ? $sKey : $sPath . '.' . $sKey;
                $this->_collectDoofinderContentWarnings($mChildValue, $sProductId, $sChildPath, $aValidation);
            }

            return;
        }

        $sValue = (string) $mValue;
        $aWarnings = array();

        if (strpos($sValue, '\\"') !== FALSE) {
            $aWarnings[] = 'escaped_quote';
        }

        if (substr($sValue, -1) === '\\') {
            $aWarnings[] = 'trailing_backslash';
        }

        if (empty($aWarnings)) {
            return;
        }

        $aValidation['warning_count'] += count($aWarnings);

        if (count($aValidation['warnings']) >= 50) {
            return;
        }

        $aValidation['warnings'][] = array(
            'product_id' => $sProductId,
            'field' => $sPath,
            'warnings' => $aWarnings,
            'sample' => substr($sValue, 0, 200),
        );
    }

    private function _getScalarDiagnosticValue($mValue)
    {
        if (is_array($mValue)) {
            if (isset($mValue['_cdata'])) {
                return (string) $mValue['_cdata'];
            }

            return '';
        }

        return (string) $mValue;
    }

}
