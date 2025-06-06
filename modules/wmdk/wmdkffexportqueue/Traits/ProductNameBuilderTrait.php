<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

trait ProductNameBuilderTrait
{
    private $_aProductNameBuilderColumns = null;

    private function _ProductNameBuilder($aData, $sFieldName = 'Title')
    {
        // CONFIG
        $bEnabled = Registry::getConfig()->getConfigParam('bWmdkFFProductNameBuilderEnabled');
        $sPattern = Registry::getConfig()->getConfigParam('sWmdkFFProductNameBuilderPattern');

        // FEATURE DISABLED
        if (!$bEnabled || empty($sPattern)) {
            return $aData;
        }

        // SET CSV COLUMNS
        if ($this->_aProductNameBuilderColumns === null) {
            $this->_aProductNameBuilderColumns = array_flip($aData);
            return $aData;
        }

        // PREPARE PRODUCT NAME
        // EXTACT PLACEHOLDERS
        preg_match_all('/\[([^\]]+)\]/', $sPattern, $aMatches);

        foreach ($aMatches[1] as $sPlaceholder) {
            if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\(([^)]+)\)$/', $sPlaceholder, $parts)) {
                // COMPLEX PLACEHOLDER
                $sValue = $this->_getPNBComplexData($aData, $parts[1], $parts[2]);

            } elseif (
                (($sPlaceholder === 'Variante') || ($sPlaceholder === 'Variant'))
            ) {
                // VARINATS INFORMATION
                $sValue = $this->_getPNBVariantsData($aData);

            } else {
                // SIMPLE PLACEHOLDER
                $sValue = $this->_getPNBSimpleData($aData, $sPlaceholder);
            }

            $sPattern = str_replace("[$sPlaceholder]", $sValue, $sPattern);
        }

        // SET PRODUCT NAME
        if (
            isset($this->_aProductNameBuilderColumns[$sFieldName])
            && (strlen($sPattern) > 0)
        ) {
            $aData[$this->_aProductNameBuilderColumns[$sFieldName]] = $sPattern;
        }

        return $aData;
    }

    private function _getPNBSimpleData($aData, $sAttributeName)
    {
        return isset($aData[$this->_aProductNameBuilderColumns[$sAttributeName]])
            ? $aData[$this->_aProductNameBuilderColumns[$sAttributeName]] : '';
    }

    private function _getPNBComplexData($aData, $sDataKey, $sAttributeName)
    {
        // TODO: Implement logic to retrieve complex data based on the key and attribute name
        return '';
    }

    private function _getPNBVariantsData($aData)
    {
        $sProductNumber = $aData[$this->_aProductNameBuilderColumns['ProductNumber']];
        $sMasterProductNumber = $aData[$this->_aProductNameBuilderColumns['MasterProductNumber']];

        if ($sProductNumber === $sMasterProductNumber) {
            return '';
        }

        // TODO: Implement logic to retrieve variant data
//        return implode(' ', [
//            '<label>',
//            $sMasterProductNumber,
//            ':</label>',
//            $sProductNumber,
//        ]);
    }
}