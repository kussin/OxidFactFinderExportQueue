<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

trait ThirdPartyConverterTrait
{
    use ConverterTrait;

    private $_aMapping = NULL;
    private $_aCDataFields = NULL;
    private $_aNumberFields = NULL;
    private $_aBooleanFields = NULL;
    private $_aDateFields = NULL;

    private $_sBaseUrl = NULL;

    private function _initThirdPartyConverter($aMapping, $sCDataFields, $sNumberFields, $sBooleanFields, $sDateFields)
    {
        $this->_aMapping = $aMapping;
        $this->_aCDataFields = explode(',', $sCDataFields);
        $this->_aNumberFields = explode(',', $sNumberFields);
        $this->_aBooleanFields = explode(',', $sBooleanFields);
        $this->_aDateFields = explode(',', $sDateFields);
    }

    private function _convertData($aData)
    {
        $aConvertedData = array();

        foreach ($aData as $sKey => $sValue) {
            $aConvertedData[$this->_mapKey($sKey)] = $this->_convertValue($sKey, $sValue);
        }

        return $aConvertedData;
    }

    private function _mapKey($sKey)
    {
        return (isset($this->_aMapping[$sKey])) ? $this->_aMapping[$sKey] : $sKey;
    }

    private function _convertValue($sKey, $sValue)
    {
        // URL
        if ($sKey == 'Deeplink') {
            $sValue = $this->_setDeeplink($sValue);
        }

        if (in_array($sKey, $this->_aCDataFields)) {
            return $this->_convertToCData($sValue);
        }

        if (in_array($sKey, $this->_aNumberFields)) {
            return (double) $sValue;
        }

        if (in_array($sKey, $this->_aBooleanFields)) {
            return $this->_convertToBoolean($sValue);
        }

        if (in_array($sKey, $this->_aDateFields)) {
            return $this->_convertToDate($sValue);
        }

        return $sValue;
    }

    public function _convertToCData($sValue)
    {
        return "<![CDATA['" . $sValue . "']]>";
    }

    public function _convertToDate($sValue)
    {
        $iTimestamp = strtotime($sValue);

        switch (self::PROCESS_CODE) {
            case 'SOOQR':
                return date('Y-m-d\TH:i:sP', $iTimestamp);
                break;

            default:
                return $sValue;
        }
    }

    public function _setDeeplink($sDeeplink)
    {
        if ($this->_sBaseUrl == NULL) {
            $aBaseUrl = explode('?', Registry::getConfig()->getConfigParam('sShopURL'));
            $this->_sBaseUrl = trim($aBaseUrl[0]);
        }

        switch (self::PROCESS_CODE) {
            case 'FACTFINDER':
                return $sDeeplink;
                break;

            case 'SOOQR':
            default:
                return $this->_sBaseUrl . $sDeeplink;
        }
    }
}