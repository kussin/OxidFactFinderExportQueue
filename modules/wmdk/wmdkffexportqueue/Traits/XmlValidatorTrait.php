<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;
use Spatie\ArrayToXml\ArrayToXml;

trait XmlValidatorTrait
{
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

            return false;
        }

        return true;
    }

}