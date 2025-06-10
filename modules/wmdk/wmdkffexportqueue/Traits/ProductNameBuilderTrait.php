<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Core\DatabaseProvider;
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
        $iLang = Registry::getConfig()->getRequestParameter('lang');
        $sProductNumber = $aData[$this->_aProductNameBuilderColumns['ProductNumber']];
        $sMasterProductNumber = $aData[$this->_aProductNameBuilderColumns['MasterProductNumber']];

        if ($sProductNumber === $sMasterProductNumber) {
            return '';
        }

        // LOAD Product
        $oProduct = oxNew(Article::class);
        $oProduct->loadInLang($iLang, $this->_getArticleId($sProductNumber));

        // LOAD Parent
        $oParent = oxNew(Article::class);
        $oParent->loadInLang($iLang, $this->_getArticleId($sMasterProductNumber));

        // TODO: Implement logic to retrieve variant data
        return implode('', [
            '<label>',
            $oParent->oxarticles__oxvarname->value,
            ':</label>',
            ' ',
            $oProduct->oxarticles__oxvarselect->value,
        ]);
    }

    protected function _getArticleId($sProductNumber)
    {
        $sQuery = 'SELECT DISTINCT 
            `OXID`
        FROM 
            `oxarticles`
        WHERE
            (`OXARTNUM` LIKE "' . $sProductNumber . '")
        LIMIT 1;';

        $oResult = DatabaseProvider::getDb()->select($sQuery);

        if ($oResult != FALSE && $oResult->count() > 0) {
            return $oResult->fields[0];
        }

        return $sProductNumber;
    }
}