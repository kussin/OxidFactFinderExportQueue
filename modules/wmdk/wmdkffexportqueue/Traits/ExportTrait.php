<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

trait ExportTrait
{
    use ConverterTrait;

    protected $_sChannel = NULL;
    protected $_iShopId = 1;
    protected $_iLang = 0;
    protected $_bActive = TRUE;
    protected $_bHidden = FALSE;
    protected $_iStockMin = 0;

    protected $_aExportFields = NULL;
    protected $_aExportHtmlFields = NULL;

    protected $_aCsvData = array();


    protected $_sTmpExportDelimiter = '#%#%#';

    protected $_bSkipCSVDataRow = FALSE;

    protected $_aResponse = array(
        'success' => TRUE,

        'channel' => NULL,
        'selected' => 0,
        'products' => 0,
        'skipped' => 0,

        'template' => NULL,

        'validation_errors' => array(),
        'system_errors' => array(),
    );


    /**
     * @return string
     */
    public function render() {
        // Variablendeklaration
        $this->_aResponse['template'] = $this->_sTemplate;

        // LOAD DATA
        $this->_loadData();

        // SAVE DATA
        $this->_exportData();

        $this->_aViewData['sResponse'] = json_encode($this->_aResponse);

        return $this->_sTemplate;
    }

    private function _getCSVDataRow($aFields, $sDelimiter = self::EXPORT_DELIMITER) {
        $aTmpCsvData = array();

        foreach ($aFields as $iKey => $sValue) {
            /* CategoryPath (Ticket: #35896) */
            if (
                ($this->_aExportFields[$iKey] == 'CategoryPath')
                && ($sValue != 'CategoryPath')
            ) {
                $sValue = $this->_checkAllowedCategoies($sValue);
            }

            /* Attributes (Ticket: #62637) */
            if ($this->_aExportFields[$iKey] == 'Attributes') {
                $sValue = $this->_convertAttributes($sValue);
            }

            $sExportData = in_array($this->_aExportFields[$iKey], $this->_aExportHtmlFields) ? $sValue : $this->_removeHtmlAndBlankLines($sValue);

            $aTmpCsvData[] = self::EXPORT_ADDITIONAL_ESCAPING . $this->_excapeString( $sExportData ) . self::EXPORT_ADDITIONAL_ESCAPING;
        }
        return implode($sDelimiter, $aTmpCsvData);
    }

    private function _loadData() {
        // CONFIG
        $this->_bActive = (bool) Registry::getConfig()->getConfigParam('sWmdkFFExportOnlyActive');
        $this->_bHidden = (bool) Registry::getConfig()->getConfigParam('sWmdkFFExportHidden');
        $this->_iStockMin = (int) Registry::getConfig()->getConfigParam('sWmdkFFExportStockMin');

        $iCsvLengthMax = (int) Registry::getConfig()->getConfigParam('sWmdkFFExportDataLengthMax');
        $iCsvLengthMin = (int) Registry::getConfig()->getConfigParam('sWmdkFFExportDataLengthMin');

        $this->_aExportFields = explode(',', 'OXID,' . Registry::getConfig()->getConfigParam('sWmdkFFExportFields'));
        $this->_aExportHtmlFields = explode(',', Registry::getConfig()->getConfigParam('sWmdkFFExportHtmlFields'));

        // GET DATA
        $this->_sChannel = Registry::getConfig()->getRequestParameter('channel');
        $this->_iShopId = Registry::getConfig()->getRequestParameter('shop_id');
        $this->_iLang = Registry::getConfig()->getRequestParameter('lang');

        // LOAD PRODUCTS
        $sQuery = 'SELECT 
                        `' . implode('`, `', $this->_aExportFields) . '`
                    FROM 
                        `wmdk_ff_export_queue`
                    WHERE
                        (`Channel` = "' . $this->_sChannel . '")
                        AND (`OXSHOPID` = "' . $this->_iShopId . '")
                        AND (`LANG` = "' . $this->_iLang . '")
                        AND (`OXACTIVE` = "' . (($this->_bActive) ? '1' : '0') . '")
                        AND (`OXHIDDEN` = "' . (($this->_bHidden) ? '1' : '0') . '")
                        AND (`Stock` >= ' . $this->_iStockMin . ');';

        $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->select($sQuery);

        if ($oResult != FALSE && $oResult->count() > 0) {

            // LOG
            $this->_aResponse['selected'] = $oResult->count();

            // ADD CSV Header
            array_shift($this->_aExportFields);
            $this->_aCsvData[] = $this->_getCSVDataRow($this->_aExportFields);

            while (!$oResult->EOF) {
                $aData = $oResult->getFields();

                // PREPARE DATA
                $sOxid = array_shift($aData);

                // CLEAN DATA
                $sCSVDataRow = $this->_getCSVDataRow($aData, $this->_getTmpExportDelimiter());

                if (!$this->_bSkipCSVDataRow) {
                    $sCsvData = $sCSVDataRow;

                    if (
                        ($iCsvLengthMin <= strlen($sCsvData))
                        && (strlen($sCsvData) <= $iCsvLengthMax)
                    ) {
                        $this->_aCsvData[] = $sCsvData;

                    } else {
                        // ERROR
                        $this->_aResponse['validation_errors'][] = 'OXID ' . $sOxid . ' has wrong limits.';
                    }

                } else {
                    // SKIPPED
                    $this->_bSkipCSVDataRow = FALSE;

                    // LOG
                    $this->_aResponse['skipped'] += 1;
                }


                // NEXT
                $oResult->fetchRow();
            }
        }
    }


    private function _excapeString($sString) {
        return str_replace(array(
            '"',
        ), array (
            '\"',
        ), $sString);
    }


    private function _removeHtmlAndBlankLines($sHtmlText) {
        $sHtmlText = str_replace(array("\n\r", "\n", "\r"), array(' ', ' ', ' '), $sHtmlText);

        return strip_tags($sHtmlText, Registry::getConfig()->getConfigParam('sWmdkFFQueueAllowableTags'));
    }

    private function _checkAllowedCategoies($sCategoryPath, $sDelimiter = ',') {
        // HACK
        $aCategoryPath = explode(self::EXPORT_CATEGORY_DELIMITER, $sCategoryPath);
        $aCleanedPath = array();
        // TODO: Move to Settings
        $aSkipKeys = array(
            'Sale',
            '2nd-Artikel',
            '2nd-Items',
            'More Fun/Smith Optics G 3',
            'More Fun/Smith Optics Special',
            'More Fun/Smith Optics G 2',
            'More Fun/Smith Optics Lifestyle',
            'More Fun/Smith Optics G 1',
            'More Fun/Smith Optics Sport',
        );

        foreach ($aCategoryPath as $iKey => $sCategory) {
            if (!in_array($sCategory, $aSkipKeys)) {
                $aCleanedPath[] = $sCategory;
            }
        }

        $sCategoryPath = implode(self::EXPORT_CATEGORY_DELIMITER, $aCleanedPath);
        // HACK

        $aSearch = array('&amp;',);
        $aReplace = array('&',);

        $sCleanedCategoryPath = str_replace($aSearch, $aReplace, $sCategoryPath);

        $aSkipCategories = explode($sDelimiter, Registry::getConfig()->getConfigParam('sWmdkFFExportRemoveCategoriesByName'));

        foreach ($aSkipCategories as $iKey => $sCategoryName) {
            $sFindIn = strtolower( trim($sCleanedCategoryPath) );
            $sNeedle = strtolower( trim($sCategoryName) );

            if (strpos($sFindIn, $sNeedle) !== FALSE) {
                $this->_bSkipCSVDataRow = TRUE;
            }
        }

        return $sCleanedCategoryPath;
    }

    protected function _getTmpExportDelimiter()
    {
        // LOAD DELIMITER
        $sTmpExportDelimiter = trim(Registry::getConfig()->getConfigParam('sWmdkFFExportTmpDelimiter'));

        if (empty($sTmpExportDelimiter)) {
            return $this->_sTmpExportDelimiter;
        }

        return $sTmpExportDelimiter;
    }
}