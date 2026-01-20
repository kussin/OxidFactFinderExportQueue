<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

/**
 * Converts export rows into third-party specific formats and structures.
 */
trait ThirdPartyConverterTrait
{
    use ConverterTrait;

    private $_aMapping = NULL;
    private $_aCDataFields = NULL;
    private $_aNumberFields = NULL;
    private $_aBooleanFields = NULL;
    private $_aDateFields = NULL;

    private $_sBaseUrl = NULL;

    /**
     * Initialize conversion rules for the third-party export.
     *
     * @param array $aMapping Field mapping array.
     * @param string $sCDataFields Comma-separated CDATA field list.
     * @param string $sNumberFields Comma-separated numeric field list.
     * @param string $sBooleanFields Comma-separated boolean field list.
     * @param string $sDateFields Comma-separated date field list.
     */
    private function _initThirdPartyConverter($aMapping, $sCDataFields, $sNumberFields, $sBooleanFields, $sDateFields)
    {
        $this->_aMapping = $aMapping;
        $this->_aCDataFields = explode(',', $sCDataFields);
        $this->_aNumberFields = explode(',', $sNumberFields);
        $this->_aBooleanFields = explode(',', $sBooleanFields);
        $this->_aDateFields = explode(',', $sDateFields);
    }

    /**
     * Convert header keys using the mapping list.
     *
     * @param array $Keys Raw header keys.
     * @param bool $bQuoted Whether the keys are quoted.
     * @return array
     */
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
    /**
     * Convert a product data array into third-party specific fields.
     *
     * @param array $aData Raw export data.
     * @return array
     */
    private function _convertData($aData)
    {
        $aConvertedData = array();

        foreach ($aData as $sKey => $sValue) {
            // HOTFIX #67324
            if (
                ($sKey == 'MSRP')
                && ($sValue == 0)
            ) {
                $sValue = $aData['Price'];
            }

            $aConvertedData[$this->_mapKey($sKey)] = $this->_convertValue($sKey, $sValue);
        }

        // ADD ATTRIBUTES AS NODES
        $aConvertedData = $this->_addAttributesAsNodes($aConvertedData);

        // ADD CLONED ATTRIBUTES AS NODES
        $aConvertedData = $this->_addAttributesAsNodes($aConvertedData, 'ClonedAttributes');

        return $aConvertedData;
    }

    /**
     * Map a single key to its configured output name.
     *
     * @param string $sKey Raw field key.
     * @return string
     */
    private function _mapKey($sKey)
    {
        // HOTFIX #66954
        if (self::PROCESS_CODE == 'FLOUR') {
            $sField = strtoupper($sKey);

            if (
                ($sField == 'DATEMODIFIED')
                || ($sField == 'OXTIMESTAMP')
            ) {
                $oLang = Registry::getLang();
                return $oLang->translateString(strtoupper(str_replace('`', '',$sField)), $this->_iLang);
            }
        }

        return (isset($this->_aMapping[$sKey])) ? $this->_aMapping[$sKey] : $sKey;
    }

    /**
     * Convert a field value based on type configuration.
     *
     * @param string $sKey Field key.
     * @param mixed $sValue Field value.
     * @return mixed
     */
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

    /**
     * Wrap a value in a CDATA structure.
     *
     * @param string $sValue Raw value.
     * @return array
     */
    private function _convertToCData($sValue)
    {
        return array(
            '_cdata' => $sValue,
        );
    }

    /**
     * Extract the raw value from a CDATA wrapper.
     *
     * @param mixed $sValue Value or CDATA wrapper.
     * @return mixed
     */
    private function _revertFromCData($sValue)
    {
        return (is_array($sValue) && isset($sValue['_cdata'])) ? $sValue['_cdata'] : $sValue;
    }

    /**
     * Normalize escaped CDATA blocks in the export output.
     *
     * @param string $sExportData XML export data.
     * @return string
     */
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

    /**
     * Convert a date string to the required export format.
     *
     * @param string $sValue Raw date value.
     * @return string
     */
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

    /**
     * Ensure deeplinks include base URL when required.
     *
     * @param string $sDeeplink Relative deeplink.
     * @return string
     */
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

    /**
     * Convert category paths for third-party formats.
     *
     * @param string $sCategoryPath Raw category path.
     * @return string
     */
    private function _setCategories($sCategoryPath)
    {
        switch (self::PROCESS_CODE) {
            case 'DOOFINDER':
                return str_replace(array(
                    '/',
                    '|',
                ), array(
                    ' > ',
                    ' %% ',
                ), $sCategoryPath);

            case 'FACTFINDER':
            default:
                return $sCategoryPath;
        }
    }

    /**
     * Expand attribute tuples into separate XML nodes.
     *
     * @param array $aProductData Export data array.
     * @param string $sFieldName Attribute field name to expand.
     * @return array
     */
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
                        // HOTFIX #67324
                        $oLang = Registry::getLang();
                        $sKey = trim($sKey) == "" ? $oLang->translateString( 'VARINAT', $this->_iLang) : $sKey;

                        // CLEAN KEY FIX #67324
                        $sKey = str_replace(['/'], '|', $sKey);
                        $aKey = explode('|', $sKey);
                        $sKey = $aKey[0];

                        if (is_array($sValue)) {
                            if (self::PROCESS_CODE == 'DOOFINDER') {
                                $sCsvDelimiter = '/';
                            }

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
            && ($sFieldName == 'Attributes')
        ) {
            $dPrice = (double) $aProductData['price'];
            $dNormalPrice = (double) $aProductData['normal_price'];

            $dSalePrice = ($dPrice < $dNormalPrice) ? $dPrice : '';

            if (
                ($dSalePrice < $dNormalPrice)
            ) {
                // SET PRICES
                $aProductData['price'] = $dNormalPrice;
                $aProductData['sale_price'] = $dSalePrice;
            }
        }

        return $aProductData;
    }

    /**
     * Parse a tuple string into a structured attribute array.
     *
     * @param string $sAttributes Tuple string of attributes.
     * @return array
     */
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
