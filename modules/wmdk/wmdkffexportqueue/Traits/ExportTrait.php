<?php

namespace Wmdk\FactFinderQueue\Traits;

trait ExportTrait
{
    protected $_sChannel = NULL;
    protected $_iShopId = 1;
    protected $_iLang = 0;
    protected $_bActive = TRUE;
    protected $_bHidden = FALSE;
    protected $_iStockMin = 0;

    protected $_aExportFields = NULL;
    protected $_aExportHtmlFields = NULL;

    protected $_aCsvData = array();

    protected $_bSkipCSVDataRow = FALSE;

    protected $_aResponse = array(
        'success' => TRUE,

        'channel' => NULL,
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


    private function _excapeString($sString) {
        return str_replace(array(
            '"',
        ), array (
            '\"',
        ), $sString);
    }


    private function _removeHtmlAndBlankLines($sHtmlText) {
        $sHtmlText = str_replace(array("\n\r", "\n", "\r"), array(' ', ' ', ' '), $sHtmlText);

        return strip_tags($sHtmlText, oxRegistry::getConfig()->getConfigParam('sWmdkFFQueueAllowableTags'));
    }

    private function _checkAllowedCategoies($sCategoryPath, $sDelimiter = ',') {
        // HACK
        $aCategoryPath = explode('|', $sCategoryPath);
        $aCleanedPath = array();
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

        $sCategoryPath = implode('|', $aCleanedPath);
        // HACK

        $aSearch = array('&amp;',);
        $aReplace = array('&',);

        $sCleanedCategoryPath = str_replace($aSearch, $aReplace, $sCategoryPath);

        $aSkipCategories = explode($sDelimiter, oxRegistry::getConfig()->getConfigParam('sWmdkFFExportRemoveCategoriesByName'));

        foreach ($aSkipCategories as $iKey => $sCategoryName) {
            $sFindIn = strtolower( trim($sCleanedCategoryPath) );
            $sNeedle = strtolower( trim($sCategoryName) );

            if (strpos($sFindIn, $sNeedle) !== FALSE) {
                $this->_bSkipCSVDataRow = TRUE;
            }
        }

        return $sCleanedCategoryPath;
    }
}