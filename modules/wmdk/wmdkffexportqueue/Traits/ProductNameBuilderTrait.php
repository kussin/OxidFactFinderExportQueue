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
        // TODO: Add logic to replace the placeholders in the pattern with actual data
//        $aData[$this->_aProductNameBuilderColumns[$sFieldName]] = implode(' ', [
//            $aData[$this->_aProductNameBuilderColumns['Marke']],
//            $aData[$this->_aProductNameBuilderColumns[$sFieldName]],
//        ]);

        return $aData;
    }
}