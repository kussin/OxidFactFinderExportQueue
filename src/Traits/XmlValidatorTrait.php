<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;
use Spatie\ArrayToXml\ArrayToXml;

trait XmlValidatorTrait
{
    use DebugTrait;

    private function _validateXmlNode($aNode)
    {
        try {
            // XML
            $oXML = new ArrayToXml(array(
                'nodes' => [
                    'node' => $aNode,
                ]
            ), array(
                'rootElementName' => 'validate',
            ));

        } catch (\Exception $oException) {
            // ERROR
            $this->_aResponse['validation_errors'][] = 'XML Node Validation Exception (OXARTNUM: ' . $aNode['id'] . '): ' . $oException->getMessage();

            $this->log(json_encode($aNode));

            return false;
        }

        return true;
    }

    protected function _validateXmlFile($sXmlFile)
    {
        $this->_setXmlFileValidationResponse(array(
            'file' => $sXmlFile,
            'is_valid' => false,
        ));

        if (!is_readable($sXmlFile)) {
            $this->_aResponse['validation_errors'][] = 'XML File Validation Exception: File is not readable: ' . $sXmlFile;

            return false;
        }

        if (filesize($sXmlFile) === 0) {
            $this->_aResponse['validation_errors'][] = 'XML File Validation Exception: File is empty: ' . $sXmlFile;

            return false;
        }

        $bUseInternalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $oXml = new \DOMDocument();
        $bIsValid = $oXml->load($sXmlFile);

        if (!$bIsValid) {
            foreach (libxml_get_errors() as $oError) {
                $this->_aResponse['validation_errors'][] = sprintf(
                    'XML File Validation Exception: line %d, column %d: %s',
                    $oError->line,
                    $oError->column,
                    trim($oError->message)
                );
            }

            if (empty($this->_aResponse['validation_errors'])) {
                $this->_aResponse['validation_errors'][] = 'XML File Validation Exception: DOMDocument could not load XML file.';
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($bUseInternalErrors);

        $this->_aResponse['xml_file_validation']['is_valid'] = $bIsValid;
        $this->log('XML file validation: ' . json_encode($this->_aResponse['xml_file_validation']));

        return $bIsValid;
    }

    private function _setXmlFileValidationResponse($aValidation)
    {
        $aResponse = array();

        foreach ($this->_aResponse as $sKey => $mValue) {
            if ($sKey == 'xml_file_validation') {
                continue;
            }

            if ($sKey == 'validation_errors') {
                $aResponse['xml_file_validation'] = $aValidation;
            }

            $aResponse[$sKey] = $mValue;
        }

        if (!isset($aResponse['xml_file_validation'])) {
            $aResponse['xml_file_validation'] = $aValidation;
        }

        $this->_aResponse = $aResponse;
    }

    protected function _replaceSpecialChars($aNode, $bAddOriginValueParam = true)
    {
        if ($bAddOriginValueParam) {
            $aNode['_attributes'] = $aNode['name'];
        }

        $aNode['name'] = str_replace(
            array('&', '<', '>', '"', "'", '(', ')', 'ß', 'ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', '€'),
            array(''),
            $aNode['name']
        );

        return $aNode;
    }

}
