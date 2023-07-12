<?php

/**
 * Class wmdkffexport_export
 */
class wmdkffexport_export extends oxubase
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
    
    private $_sTemplate = 'wmdkffexport_export.tpl';
    
    
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
    
    
    private function _getCSVDataRow($aFields) {                
        $aTmpCsvData = array();
        
        foreach ($aFields as $iKey => $sValue) {
            /* CategoryPath (Ticket: #35896) */
            if (
                ($this->_aExportFields[$iKey] == 'CategoryPath')
                && ($sValue != 'CategoryPath')
            ) {
                $sValue = $this->_checkAllowedCategoies($sValue);
            }
            
            $sExportData = in_array($this->_aExportFields[$iKey], $this->_aExportHtmlFields) ? $sValue : $this->_removeHtmlAndBlankLines($sValue);
            
            $aTmpCsvData[] = '"' . $this->_excapeString( $sExportData ) . '"';
        }
        return implode('|', $aTmpCsvData);
    }
    
    
    private function _loadData() {
        // CONFIG
        $this->_bActive = (bool) oxRegistry::getConfig()->getConfigParam('sWmdkFFExportOnlyActive');
        $this->_bHidden = (bool) oxRegistry::getConfig()->getConfigParam('sWmdkFFExportHidden');
        $this->_iStockMin = (int) oxRegistry::getConfig()->getConfigParam('sWmdkFFExportStockMin');
        
        $iCsvLengthMax = (int) oxRegistry::getConfig()->getConfigParam('sWmdkFFExportDataLengthMax');
        $iCsvLengthMin = (int) oxRegistry::getConfig()->getConfigParam('sWmdkFFExportDataLengthMin');
        
        $this->_aExportFields = explode(',', 'OXID,' . oxRegistry::getConfig()->getConfigParam('sWmdkFFExportFields'));
        $this->_aExportHtmlFields = explode(',', oxRegistry::getConfig()->getConfigParam('sWmdkFFExportHtmlFields'));
        
        // GET DATA
        $this->_sChannel = oxRegistry::getConfig()->getRequestParameter('channel');
        $this->_iShopId = oxRegistry::getConfig()->getRequestParameter('shop_id');
        $this->_iLang = oxRegistry::getConfig()->getRequestParameter('lang');
        
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
            
            // ADD CSV Header
            array_shift($this->_aExportFields);
            $this->_aCsvData[] = $this->_getCSVDataRow($this->_aExportFields);
            
            while (!$oResult->EOF) {
                $aData = $oResult->getFields();
                
                // PREPARE DATA
                $sOxid = array_shift($aData);                
                
                // CLEAN DATA
                $sCSVDataRow = $this->_getCSVDataRow($aData);
                
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
    
    
    private function _exportData() {
        // CONFIG
        $sExportFile = oxRegistry::getConfig()->getShopConfVar('sShopDir') . oxRegistry::getConfig()->getConfigParam('sWmdkFFExportDirectory') . $this->_sChannel . '.csv';        
        
        // EXPORT
        try {
            $rCsvFile = fopen($sExportFile, 'w');

            foreach ($this->_aCsvData as $sRow) {
                fwrite($rCsvFile, $sRow . PHP_EOL);
            }

            fclose($sExportFile);
            
        } catch (Exception $oException) {
            // ERROR
            $this->_aResponse['validation_errors'][] = 'Exception ' . $oException->getMessage();
        }
        
        // LOG
        $this->_aResponse['channel'] = $this->_sChannel;
        $this->_aResponse['products'] = count($this->_aCsvData) - 1;
        
        
        // DEBUG
//        echo implode("\n", $this->_aCsvData);
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