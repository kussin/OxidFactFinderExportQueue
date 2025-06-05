<?php

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;

/**
 * Class wmdkffexport_mapping
 */
class wmdkffexport_mapping extends oxubase
{

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

    private function _getValue($sAttributeName, $sData)
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

    private function _getAttributeValue($sAttributeName, $sData)
    {
        return $this->_getValue($sAttributeName, $sData);
    }

    private function _getClonedAttributeValue($sAttributeName, $sData)
    {
        return $this->_getValue($sAttributeName, $sData);
    }
}