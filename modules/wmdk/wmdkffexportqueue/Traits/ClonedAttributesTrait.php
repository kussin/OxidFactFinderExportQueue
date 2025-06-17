<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

trait ClonedAttributesTrait
{
    private $_aClonedAttributesMapping = null;

    private function _cloneAttributes($aAttributes, $sVarSelectMapping = '')
    {
        if (!Registry::getConfig()->getConfigParam('bWmdkFFClonedAttributeEnabled')) {
            // ERROR: FEATURE DISABLED
            //TODO: Add logging
            return null;
        }

        $sVarNameMappingAttribute = Registry::getConfig()->getConfigParam('sWmdkFFClonedAttributeOxvarnameAttribute');

        if (
            (trim($sVarSelectMapping) != '')
            && (trim($sVarNameMappingAttribute) != '')
        ) {
            return trim($sVarSelectMapping);
        }

        $sFilePath  = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . Registry::getConfig()->getConfigParam('sWmdkFFClonedAttributeMappingFile'));

        if (!file_exists($sFilePath)) {
            // ERROR: NO MAPPING FILE
            //TODO: Add logging
            return null;
        }

        if ($this->_aClonedAttributesMapping === null) {
            // GENERATE MAPPING DATA
            if (($rCsv = fopen($sFilePath, 'r')) !== false) {
                $bCsvColumnHeaders = true;

                while (($aRow = fgetcsv($rCsv, 1000, ';')) !== false) {

                    // SKIP HEADERS
                    if ($bCsvColumnHeaders) {
                        $bCsvColumnHeaders = false;
                        continue;
                    }

                    if (isset($aRow[0])) {
                        $sKey = trim($aRow[0]);
                        $sValue = trim($aRow[2]);
                        $sClonedAttribute = [
                            'label' => trim($aRow[1]),
                            'value' => trim($aRow[3]),
                        ];

                        // INIT MAPPING ARRAY
                        if (!isset($this->_aClonedAttributesMapping[$sKey])) {
                            $this->_aClonedAttributesMapping[$sKey] = [];
                        }

                        if (
                            (strlen($sValue) > 0)
                            && (strlen($sClonedAttribute['value']) > 0)
                        ) {
                            $this->_aClonedAttributesMapping[$sKey][base64_encode($sValue)] = $sClonedAttribute;
                        }
                    }
                }

                fclose($rCsv);

            } else {
                // ERROR: NO MAPPING DATA
                //TODO: Add logging
                return null;
            }
        }

        // GENERATE CLONED ATTRIBUTES
        $aClonedAttributes = [];

        foreach ($this->_aClonedAttributesMapping as $sAttribute => $aClonedAttributesMapping) {
            // GET ATTRIBUTE VALUE
            $sAttributeValue = $this->_getAttributeValueFromTupleString($sAttribute, $aAttributes);
            $sAttributeHash = base64_encode($sAttributeValue);

            if (isset($aClonedAttributesMapping[$sAttributeHash])) {
                $aClonedAttributes[] = implode('=', $aClonedAttributesMapping[$sAttributeHash]);
            }
        }

        return (count($aClonedAttributes) > 0) ? implode('|', $aClonedAttributes) : null;
    }

    private function _getAttributeValueFromTupleString($sAttributeName, $sData)
    {
        $aAttributeTuples = explode('|', $sData);

        foreach ($aAttributeTuples as $sTuple) {
            $aTuple = explode('=', $sTuple);
            if (count($aTuple) == 2 && $aTuple[0] == $sAttributeName) {
                return trim($aTuple[1]);
            }
        }

        return '';
    }
}