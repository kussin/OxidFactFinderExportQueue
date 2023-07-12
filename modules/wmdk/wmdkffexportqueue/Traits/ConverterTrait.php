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
}