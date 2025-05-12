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