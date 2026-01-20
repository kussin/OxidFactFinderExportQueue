<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

/**
 * Helpers for converting export attribute values to numeric/boolean formats.
 */
trait ConverterTrait
{
    /**
     * Convert a single attribute based on configured field lists.
     *
     * @param string $sAttributeName Attribute key.
     * @param string $sAttributeValue Attribute value.
     * @return string|float|int
     */
    private function _converter($sAttributeName, $sAttributeValue)
    {
        $aAttributesToDouble = explode(',', Registry::getConfig()->getConfigParam('sWmdkFFConverterFieldlistDouble'));

        if (in_array($sAttributeName, $aAttributesToDouble)) {
            $sAttributeValue = $this->_convertToDouble($sAttributeValue);
        }

        return $sAttributeValue;
    }

    /**
     * Convert a value to double using float conversion rules.
     *
     * @param string $sValue Raw input.
     * @return float|string
     */
    private function _convertToDouble($sValue)
    {
        return $this->_convertToFloat($sValue);
    }

    /**
     * Convert a string value into a float if possible.
     *
     * @param string $sValue Raw input.
     * @return float|string
     */
    private function _convertToFloat($sValue)
    {
        $dExtractedNumber = $this->_extractNumber($sValue);
        
        return ($dExtractedNumber != FALSE) ? $dExtractedNumber : $sValue;
    }

    /**
     * Extract the first numeric token from a string.
     *
     * @param string $sString Raw input.
     * @param int $iPosition Index of the numeric match.
     * @return float|false
     */
    private function _extractNumber($sString, $iPosition = 0)
    {
        // REPLACE COMMA
        $sString = str_replace(array('.', ',',), array('', '.',), $sString);

        preg_match_all('!\d+!', $sString, $aNumbers);

        return ( ($aNumbers != FALSE) && (count($aNumbers[0]) > 0) ) ? (float) $aNumbers[0][$iPosition] : FALSE;
    }

    /**
     * Convert a value to a boolean or integer flag.
     *
     * @param mixed $sValue Raw input.
     * @param bool $bBoolean Whether to return a boolean instead of int.
     * @return bool|int
     */
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

    /**
     * Rename attribute labels based on configuration.
     *
     * @param string $sAttributes Tuple string of attributes.
     * @return string
     */
    private function _convertAttributes($sAttributes)
    {
        $aAttributeList = Registry::getConfig()->getConfigParam('aWmdkFFConverterRenameAttributes');

        foreach ($aAttributeList as $sSearch => $sReplace) {
            $sAttributes = str_replace($sSearch, $sReplace, $sAttributes);
        }

        return $sAttributes;
    }
}
