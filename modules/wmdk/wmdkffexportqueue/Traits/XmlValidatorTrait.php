<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;
use Spatie\ArrayToXml\ArrayToXml;

/**
 * Validates and cleans XML nodes for export.
 */
trait XmlValidatorTrait
{
    use DebugTrait;

    /**
     * Validate a single XML node by attempting XML generation.
     *
     * @param array $aNode Node data to validate.
     * @return bool
     */
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

    /**
     * Remove special characters from a node and optionally store the original value.
     *
     * @param array $aNode Node data.
     * @param bool $bAddOriginValueParam Whether to store original value as attributes.
     * @return array
     */
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
