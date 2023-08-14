<?php

use Spatie\ArrayToXml\ArrayToXml;
use Wmdk\FactFinderQueue\Traits\ExportTrait;

/**
 * Class wmdkffexport_sooqr
 */
class wmdkffexport_sooqr extends oxubase
{
    use ExportTrait;
    
    private $_sTemplate = 'wmdkffexport_sooqr.tpl';
    
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
        $sExportFile = oxRegistry::getConfig()->getShopConfVar('sShopDir') . oxRegistry::getConfig()->getConfigParam('sWmdkFFExportDirectory') . $this->_sChannel . '.sooqr.xml';
        
        // EXPORT
        try {
            $rXmlFile = fopen($sExportFile, 'w');

            fwrite($rXmlFile, ArrayToXml::convert($this->_aCsvData));

            fclose($rXmlFile);
            
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

}