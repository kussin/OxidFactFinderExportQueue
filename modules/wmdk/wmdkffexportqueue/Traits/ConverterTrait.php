<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

trait ConverterTrait
{
    private function _converter($sAttributeName, $sAttributeValue)
    {
        $aAttributesToDouble = explode(',', Registry::getConfig()->getConfigParam('sWmdkFFConverterFieldlistDouble'));

        if (in_array($sAttributeName, $aAttributesToDouble)) {
            $sAttributeValue = $this->_convertToDouble($sAttributeValue);
        }

        return $sAttributeValue;
    }

    private function _convertToDouble($sValue)
    {
        return $this->_convertToFloat($sValue);
    }

    private function _convertToFloat($sValue)
    {
        $dExtractedNumber = $this->_extractNumber($sValue);
        
        return ($dExtractedNumber != FALSE) ? $dExtractedNumber : $sValue;
    }

    private function _extractNumber($sString, $iPosition = 0)
    {
        // REPLACE COMMA
        $sString = str_replace(array('.', ',',), array('', '.',), $sString);

        preg_match_all('!\d+!', $sString, $aNumbers);

        return ( ($aNumbers != FALSE) && (count($aNumbers[0]) > 0) ) ? (float) $aNumbers[0][$iPosition] : FALSE;
    }

    private function _convertToBoolean($sValue, $bBoolean = FALSE)
    {
        $bConvertedValue = (bool) $sValue;

        if (is_numeric($sValue)) {
            $bConvertedValue = $sValue > 0;
        }

        if (!is_bool($sValue)) {
            $bConvertedValue = $sValue == 'true';
        }

        if ($bBoolean) {
            return $bConvertedValue;
        }

        return $bConvertedValue ? 1 : 0;
    }

    private function _convertAttributes($sAttributes)
    {
        $aAttributeList = Registry::getConfig()->getConfigParam('aWmdkFFConverterRenameAttributes');

        foreach ($aAttributeList as $sSearch => $sReplace) {
            $sAttributes = str_replace($sSearch, $sReplace, $sAttributes);
        }

        return $sAttributes;
    }
}