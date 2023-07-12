<?php

/**
 * Class wmdkffexport_reset
 */
class wmdkffexport_reset extends oxubase
{    
    protected $_aResponse = array(
        'success' => TRUE,

        'template' => NULL,
        
        'reseted_products' => 0, 
        'reseted_varname' => 0,        
        'missing_products' => 0,       
        'added_products' => 0,
        
        'process_ip' => '',

        'validation_errors' => array(),
        'system_errors' => array(),
    );
    
    protected $_sProcessIp = NULL;
    
    protected $_sTemplate = 'wmdkffexport_reset.tpl';

    
    public function render() {
        // SET LIMITS
        ini_set('max_execution_time', (int) oxRegistry::getConfig()->getConfigParam('sWmdkFFQueuePhpLimitTimeout'));
        ini_set('memory_limit', oxRegistry::getConfig()->getConfigParam('sWmdkFFQueuePhpLimitMemory'));
        
        $this->_cleanErrors();
        
        // FILE LOG
        $this->_log();

        // OUTPUT
        if (!$this->_isCron()) {
            $this->_aViewData['sResponse'] = json_encode($this->_aResponse);
        }

        return $this->_sTemplate;
    }
    
    
    private function _cleanErrors() {
        $this->_resetExistingProducts();
        
        $this->_resetMissingProducts();
        $this->_resetVariantsWithoutVarname();
        $this->_addMissingProducts();
        
        /* wmdk_dkussin (Ticket: 43734) */
        $this->_parentStockCorrection();
        
        /* wmdk_dkussin (Ticket: 36784) */
        $this->_updateStatus();
        $this->_updateStock();
        
        /* wmdk_dkussin (Ticket: 41008) */
        $this->_resetVariantsWithParentsModifiedWithinTheLastHour();
    }
    
    
    private function _resetExistingProducts() {
        $sQuery = 'UPDATE
            oxarticles a,
            wmdk_ff_export_queue b
        SET
            b.LASTSYNC = "0000-00-00 00:00:00",
            b.ProcessIp = "' . $this->_getProcessIp() . '",
            b.OXTIMESTAMP = "0000-00-00 00:00:00"
        WHERE
            (a.OXID = b.OXID)
            AND (a.OXTIMESTAMP > b.OXTIMESTAMP)
            AND (a.OXTIMESTAMP > "' . date('Y-m-d H:i:s', strtotime('-2 days')) . '")
            AND (b.OXTIMESTAMP != "0000-00-00 00:00:00");';
        
        $iReseted = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
        
        // LOG
        $this->_aResponse['reseted_products'] = $iReseted;
    }
    
    
    private function _resetVariantsWithoutVarname() {
        $sQuery = 'UPDATE
            wmdk_ff_export_queue
        SET
            LASTSYNC = "0000-00-00 00:00:00",
            ProcessIp = "' . $this->_getProcessIp() . '",
            OXTIMESTAMP = "0000-00-00 00:00:00"
        WHERE
            (Attributes LIKE "=%");';
        
        $iVarname = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
        
        // LOG
        $this->_aResponse['reseted_varname'] = $iVarname;
    }
    
    
    private function _resetMissingProducts() {
        $aChannelList = wmdkffexport_helper::getChannelList();
        
        $sQuery = 'UPDATE 
            oxarticles 
        SET 
            OXTIMESTAMP = OXTIMESTAMP, 
            WMDK_FFQUEUE = "0" 
        WHERE 
            OXID NOT IN (SELECT OXID FROM wmdk_ff_export_queue WHERE Channel LIKE "' . $aChannelList[0]['code'] . '");';
        
        $iMissing = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
        
        // LOG
        $this->_aResponse['missing_products'] = $iMissing;
    }
    
    
    private function _addMissingProducts() {
        $aSqlQueries = array();
        
        // GET CHANNEL LIST
        $aChannelList = wmdkffexport_helper::getChannelList();
        
        $sQuery = 'SELECT 
            OXID
        FROM 
            oxarticles
        WHERE
            WMDK_FFQUEUE = "0"
        LIMIT ' . (int) oxRegistry::getConfig()->getConfigParam('sWmdkFFQueueResetLimit');
        
        $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(FALSE)->select($sQuery);

        if ($oResult != FALSE && $oResult->count() > 0) {
            while (!$oResult->EOF) {
                
                foreach ($aChannelList as $aChannel) {
                    $aSqlQueries[] = 'REPLACE INTO 
                        `wmdk_ff_export_queue` 
                    ( 
                        `OXID`,
                        `Channel`,
                        `OXSHOPID`,
                        `LANG`,
                        `LASTSYNC`,
                        `ProcessIp`,  
                        `OXTIMESTAMP`          
                    ) 
                    VALUES
                    (
                        "' . $oResult->fields[0] . '",
                        "' . $aChannel['code'] . '",
                        "' . $aChannel['shop_id'] . '",
                        "' . $aChannel['lang_id'] . '",
                        "0000-00-00 00:00:00",
                        "' . $this->_getProcessIp() . '",
                        "0000-00-00 00:00:00"
                    );';
                }
                
                $aSqlQueries[] = 'UPDATE
                    oxarticles
                SET
                    WMDK_FFQUEUE = "1"
                WHERE
                    OXID = "' . $oResult->fields[0] . '";';
                
                // NEXT
                $oResult->fetchRow();
            }
        }
        
        if (count($aSqlQueries) > 0) {
            $iReseted = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute( implode(PHP_EOL, $aSqlQueries) );
        
            // LOG
            $this->_aResponse['added_products'] = floor( count($aSqlQueries) / 3 * 2 );
        }
    }
    
    
    private function _parentStockCorrection() {
        $sQuery = 'UPDATE
            oxarticles,
            (
                SELECT
                    oxarticles.OXPARENTID AS PARENT_ID,
                    SUM(oxarticles.OXSTOCK) AS STOCK
                FROM
                    oxarticles
                WHERE
                    (oxarticles.OXPARENTID != "")
                    AND (oxarticles.OXSTOCK >= 0)
                GROUP BY
                    oxarticles.OXPARENTID
            ) AS VARIANTS
        SET    
            oxarticles.OXVARSTOCK = VARIANTS.STOCK,
            oxarticles.OXTIMESTAMP = NOW()
        WHERE
            (oxarticles.OXID = VARIANTS.PARENT_ID)
            AND (oxarticles.OXVARCOUNT > 0)
            AND (oxarticles.OXVARSTOCK != VARIANTS.STOCK)';
        
        try {
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
            
            // LOG
            $this->_aResponse['correct_parent_stock'] = TRUE;
            
        } catch (Exception $oException) {
            // ERROR
            
            // LOG
            $this->_aResponse['correct_parent_stock'] = FALSE;
        }
    }
    
    
    private function _updateStatus() {
        $sQuery = 'UPDATE
            oxarticles a,
            wmdk_ff_export_queue b
        SET
            b.LASTSYNC = "0000-00-00 00:00:00",
            b.ProcessIp = "' . $this->_getProcessIp() . '",
            b.OXTIMESTAMP = "0000-00-00 00:00:00",
            b.OXACTIVE = a.OXACTIVE
        WHERE
            (a.OXID = b.OXID)
            AND (a.OXVARCOUNT = 0)
            AND (a.OXACTIVE != b.OXACTIVE)';
        
        try {
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
            
            // LOG
            $this->_aResponse['update_status'] = TRUE;
            
        } catch (Exception $oException) {
            // ERROR
            
            // LOG
            $this->_aResponse['update_status'] = FALSE;
        }
    }
    
    
    private function _updateStock() {
        $sArticles = 'UPDATE
            oxarticles a,
            wmdk_ff_export_queue b
        SET
            b.LASTSYNC = "0000-00-00 00:00:00",
            b.ProcessIp = "' . $this->_getProcessIp() . '",
            b.OXTIMESTAMP = "0000-00-00 00:00:00",
            b.Stock = a.OXSTOCK
        WHERE
            (a.OXID = b.OXID)
            AND (a.OXVARCOUNT = 0)
            AND (a.OXSTOCK != b.Stock)';
        
        $sParents = 'UPDATE
            oxarticles a,
            wmdk_ff_export_queue b
        SET
            b.LASTSYNC = "0000-00-00 00:00:00",
            b.ProcessIp = "' . $this->_getProcessIp() . '",
            b.OXTIMESTAMP = "0000-00-00 00:00:00",
            b.Stock = a.OXVARSTOCK
        WHERE
            (a.OXID = b.OXID)
            AND (a.OXVARCOUNT > 0)
            AND (a.OXVARSTOCK != b.Stock)';
        
        try {
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sArticles);
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sParents);
            
            // LOG
            $this->_aResponse['update_stock'] = TRUE;
            
        } catch (Exception $oException) {
            // ERROR
            
            // LOG
            $this->_aResponse['update_stock'] = FALSE;
        }
    }
    
    
    private function _resetVariantsWithParentsModifiedWithinTheLastHour($sTimeBack = '-90 minutes') {
        $sArticles = 'UPDATE
            oxarticles a,
            wmdk_ff_export_queue b
        SET
            b.LASTSYNC = "0000-00-00 00:00:00",
            b.ProcessIp = "' . $this->_getProcessIp() . '",
            b.OXTIMESTAMP = "0000-00-00 00:00:00"
        WHERE
            (a.OXARTNUM = b.MasterProductNumber)
            AND (a.OXTIMESTAMP >= "' . date('Y-m-d H:i:s', strtotime($sTimeBack)) . '")
            AND (a.OXVARSTOCK > 0)';
        
        try {
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sArticles);
            
            // LOG
            $this->_aResponse['reset_variants'] = TRUE;
            
        } catch (Exception $oException) {
            // ERROR
            
            // LOG
            $this->_aResponse['reset_variants'] = FALSE;
        }
    }
    
    
    private function _getProcessIp($sIp = FALSE) {
        if ($this->_sProcessIp == NULL) {
            
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $this->_sProcessIp = $_SERVER['HTTP_CLIENT_IP'];
                
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $this->_sProcessIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
                
            } else {
                $this->_sProcessIp = $_SERVER['REMOTE_ADDR'];
            }
            
        }
        
        return ($sIp != FALSE) ? $sIp : $this->_sProcessIp;
    }
    
    
    private function _isCron() {
        $sIsCronjobOrg = in_array( $this->_getProcessIp(), explode(',', oxRegistry::getConfig()->getConfigParam('sWmdkFFDebugCronjobIpList') ) );
        
        return ( (php_sapi_name() == 'cli') || $sIsCronjobOrg);
    }
    
    
    private function _log() {
        $sFilename  = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . oxRegistry::getConfig()->getConfigParam('sWmdkFFDebugLogFileQueue'));
        
        // SET ADDITIONAL DATA
        $this->_aResponse['template'] = $this->_sTemplate;
        $this->_aResponse['process_ip'] = $this->_getProcessIp();
        $this->_aResponse['timestamp'] = date('Y-m-d H:i:s');
        $this->_aResponse['cronjob'] = $this->_isCron();
                
		$rFile = fopen($sFilename, 'a');
		fputs($rFile, json_encode($this->_aResponse) . PHP_EOL);			
		return fclose($rFile);
    }

}