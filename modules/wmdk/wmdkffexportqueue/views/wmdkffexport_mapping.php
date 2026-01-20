<?php

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Traits\ClonedAttributesTrait;

/**
 * Export controller that renders cloned attribute mapping CSV files.
 */
class wmdkffexport_mapping extends oxubase
{
    use ClonedAttributesTrait;

    /**
     * Build and stream the mapping CSV file.
     *
     * @return void
     */
    public function render() {
        // SET LIMITS
        ini_set('max_execution_time', (int) Registry::getConfig()->getConfigParam('sWmdkFFQueuePhpLimitTimeout'));
        ini_set('memory_limit', Registry::getConfig()->getConfigParam('sWmdkFFQueuePhpLimitMemory'));

        // GET MAPPING DATA
        $aMappings = Registry::getConfig()->getConfigParam('aWmdkFFClonedAttributeMapping');

        // INIT CSV DATA
        $aCsvData = [
            ['Original Attribute', 'Cloned Attribute', 'Original Value', 'Mapped Value'],
        ];

        if (count($aMappings) > 0) {
            foreach ($aMappings as $sAttribute => $sClonedAttribute) {
                $sQuery = 'SELECT DISTINCT
	Attributes,
	ClonedAttributes
FROM
	wmdk_ff_export_queue
WHERE
	(Attributes LIKE "%' . $sAttribute . '=%")
	AND (
		(ClonedAttributes IS NULL)
		OR (ClonedAttributes LIKE "%' . $sClonedAttribute . '=%")
	);';

                $oResult = DatabaseProvider::getDb(FALSE)->select($sQuery);

                if ($oResult != FALSE && $oResult->count() > 0) {
                    while (!$oResult->EOF) {
                        // DATA
                        $aData = $oResult->fields;

                        $aCsvData[] = [
                            $sAttribute,
                            $sClonedAttribute,
                            $this->_getAttributeValue($sAttribute, $aData['Attributes']),
                            $this->_getClonedAttributeValue($sClonedAttribute, $aData['ClonedAttributes']),
                        ];

                        // NEXT
                        $oResult->fetchRow();
                    }
                }
            }
        }

        // DOWNLOAD CSV DATA
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="clonedattributesmapping-' . time() . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $rBuffer = fopen('php://output', 'w');

        foreach ($aCsvData as $aRow) {
            fputcsv($rBuffer, $aRow, ';');
        }

        fclose($rBuffer);
        exit;
    }

    /**
     * Extract the value for a specific attribute from a tuple string.
     *
     * @param string $sAttributeName Attribute to locate.
     * @param string $sData Tuple string data.
     * @return string
     */
    private function _getAttributeValue($sAttributeName, $sData)
    {
        return $this->_getAttributeValueFromTupleString($sAttributeName, $sData);
    }

    /**
     * Extract the value for a specific cloned attribute from a tuple string.
     *
     * @param string $sAttributeName Attribute to locate.
     * @param string $sData Tuple string data.
     * @return string
     */
    private function _getClonedAttributeValue($sAttributeName, $sData)
    {
        return $this->_getAttributeValueFromTupleString($sAttributeName, $sData);
    }
}
