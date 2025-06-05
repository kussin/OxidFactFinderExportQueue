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

    private function _convertKeys($Keys, $bQuoted = false)
    {
        $aConvertedKeys = array();

        foreach ($Keys as $sKey) {
            $sCleanKey = $sKey;

            if ($bQuoted) {
                // CLEAN KEY
                $sCleanKey = str_replace([
                    '"',
                ], '', $sKey);
            }

            // CONVERT KEY
            $sConvertedKey = $this->_mapKey(trim($sCleanKey));

            $aConvertedKeys[] = $bQuoted ? '"' . $sConvertedKey . '"' : $sConvertedKey;
        }

        return $aConvertedKeys;
    }
    private function _convertData($aData)
    {
        $aConvertedData = array();

        foreach ($aData as $sKey => $sValue) {
            $aConvertedData[$this->_mapKey($sKey)] = $this->_convertValue($sKey, $sValue);
        }

        // ADD ATTRIBUTES AS NODES
        $aConvertedData = $this->_addAttributesAsNodes($aConvertedData);

        // ADD CLONED ATTRIBUTES AS NODES
        $aConvertedData = $this->_addAttributesAsNodes($aConvertedData, 'ClonedAttributes');

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

        // CATEGORY
        if ($sKey == 'CategoryPath') {
            $sValue = $this->_setCategories($sValue);
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

    private function _convertToCData($sValue)
    {
        return array(
            '_cdata' => $sValue,
        );
    }

    private function _revertFromCData($sValue)
    {
        return (is_array($sValue) && isset($sValue['_cdata'])) ? $sValue['_cdata'] : $sValue;
    }

    // TODO: Remove FIX #61380
    private function _fixCDataWrapper($sExportData) {
        return str_replace(array(
            '&lt;![CDATA[',
            ']]&gt;'
        ), array(
            '<![CDATA[',
            ']]>'
        ), $sExportData);
    }

    private function _convertToDate($sValue)
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

    private function _setDeeplink($sDeeplink)
    {
        if ($this->_sBaseUrl == NULL) {
            $aBaseUrl = explode('?', Registry::getConfig()->getConfigParam('sShopURL'));
            $this->_sBaseUrl = trim($aBaseUrl[0]);
        }

        switch (self::PROCESS_CODE) {
            case 'FACTFINDER':
                return $sDeeplink;

            case 'SOOQR':
            default:
                return $this->_sBaseUrl . $sDeeplink;
        }
    }

    private function _setCategories($sCategoryPath)
    {
        switch (self::PROCESS_CODE) {
            case 'DOOFINDER':
                return str_replace(array(
                    '/',
                    '|',
                ), array(
                    ' > ',
                    ' %%% ',
                ), $sCategoryPath);

            case 'FACTFINDER':
            default:
                return $sCategoryPath;
        }
    }

    private function _addAttributesAsNodes($aProductData, $sFieldName = 'Attributes')
    {
        $bAddAttributeNode = (bool) Registry::getConfig()->getConfigParam('blWmdkFFExportAddAttributeNode');
        $sCsvDelimiter = Registry::getConfig()->getConfigParam('sWmdkFFExportCsvDelimiter');

        if (
            (isset($aProductData[$sFieldName]))
            && (self::PROCESS_CODE != 'FACTFINDER')
        ) {
            $aAttributes = $this->_getAttributes($aProductData[$sFieldName]['_cdata']);

            if (count($aAttributes) >= 1) {

                if ($bAddAttributeNode) {
                    // ADD AS ADDITIONAL NODE
                    $aProductData[$sFieldName] = $aAttributes;
                } else {
                    // ADD AS NODES
                    foreach ($aAttributes as $sKey => $sValue) {
                        if (is_array($sValue)) {
                            $aProductData[$sKey] = $this->_convertToCData(implode($sCsvDelimiter, $sValue));
                        } else {
                            $aProductData[$sKey] = $this->_convertToCData($sValue);
                        }
                    }

                    unset($aProductData[$sFieldName]);
                }
            }
        }

        // ADD DOOFINDER SALE PRICE
        if (
            (isset($aProductData['price']))
            && (isset($aProductData['normal_price']))
            && (self::PROCESS_CODE == 'DOOFINDER')
        ) {
            $dPrice = (double) $aProductData['price'];
            $dNormalPrice = (double) $aProductData['normal_price'];

            $aProductData['sale_price'] = ($dPrice < $dNormalPrice) ? $dPrice : '';
        }

        return $aProductData;
    }

    protected function _getAttributes($sAttributes)
    {
        $aNodes = [];
        $aAttributes = explode('|', $sAttributes);
        $sCurrentKey = null;

        foreach ($aAttributes as $sAttribute) {
            if (strpos($sAttribute, '=') !== false) {
                list($sKey, $value) = explode('=', $sAttribute, 2);
                $sCurrentKey = $sKey;
                $aNodes[$sCurrentKey] = [$value];
            } elseif ($sCurrentKey !== null) {
                // Fortführung des vorherigen Attributs (mehrere Werte)
                $aNodes[$sCurrentKey][] = $sAttribute;
            }
        }

        // OPTIMIZE
        foreach ($aNodes as $sKey => $aValue) {
            if (count($aValue) == 1) {
                $aNodes[$sKey] = $aValue[0];
            }
        }

        return $aNodes;
    }
}