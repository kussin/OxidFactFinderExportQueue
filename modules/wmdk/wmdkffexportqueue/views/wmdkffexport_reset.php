<?php

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Traits\ProcessIpTrait;

/**
 * Reset controller for queue maintenance and cleanup.
 */
class wmdkffexport_reset extends oxubase
{
    use ProcessIpTrait;

    protected $_aResponse = array(
        'success' => TRUE,

        'template' => NULL,
        
        'reseted_products' => 0,
        'reseted_variants' => 0,
        'reseted_siblings' => 0,
        'reseted_varname' => 0,        
        'missing_products' => 0,       
        'added_products' => 0,
        
        'process_ip' => '',

        'validation_errors' => array(),
        'system_errors' => array(),
    );
    
    protected $_sTemplate = 'wmdkffexport_reset.tpl';

    private $_iCurrentHour = NULL;
    private $_iCurrentMinute = NULL;

    
    /**
     * Run reset routines and return the template name.
     *
     * @return string
     */
    public function render() {
        // SET LIMITS
        ini_set('max_execution_time', (int) Registry::getConfig()->getConfigParam('sWmdkFFQueuePhpLimitTimeout'));
        ini_set('memory_limit', Registry::getConfig()->getConfigParam('sWmdkFFQueuePhpLimitMemory'));

        // Time Vars
        $this->_iCurrentHour = (int) date('H');
        $this->_iCurrentMinute = (int) date('i');
        
        $this->_cleanErrors();
        
        // FILE LOG
        $this->_log();

        // OUTPUT
        if (!$this->_isCron()) {
            $this->_aViewData['sResponse'] = json_encode($this->_aResponse);
        }

        return $this->_sTemplate;
    }
    
    
    /**
     * Execute the full reset sequence for queue data.
     */
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

        /* wmdk_dkussin (Ticket: 62853) */
        $this->_resetExistingVariants();

        /* wmdk_dkussin (Ticket: 41008) */
        $this->_resetVariantsWithParentsModifiedWithinTheLastHour();

        /* wmdk_dkussin (Ticket: 64094) */
        $this->_resetSiblings();

        /* wmdk_dkussin (Ticket: 61736) */
        $this->_resetArticlesWithNoPic();

        /* wmdk_dkussin (Ticket: 67491) */
        $this->_disableMissingOriginOxid();
    }
    
    
    /**
     * Reset queue entries for products updated since last sync.
     */
    private function _resetExistingProducts() {
        $sResetExistingArticlesSinceDays = Registry::getConfig()->getConfigParam('sWmdkFFCronResetExistingArticlesSinceDays');

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
            AND (a.OXTIMESTAMP > "' . date('Y-m-d H:i:s', strtotime($sResetExistingArticlesSinceDays)) . '")
            AND (b.OXTIMESTAMP != "0000-00-00 00:00:00");';
        
        $iReseted = DatabaseProvider::getDb()->execute($sQuery);
        
        // LOG
        $this->_aResponse['reseted_products'] = $iReseted;
    }

    /**
     * Reset variants within a scheduled time window.
     *
     * @param string $sFrom Start time window.
     * @param string $sTo End time window.
     */
    private function _resetExistingVariants($sFrom = '02:05:00', $sTo = '03:15:00') {
        $sResetExistingArticlesSinceDays = Registry::getConfig()->getConfigParam('sWmdkFFCronResetExistingArticlesSinceDays');
        $aResetExistingVariantsDays = explode(',', Registry::getConfig()->getConfigParam('sWmdkFFCronResetExistingVariantsDays'));

        if (
            (in_array(date('N'), $aResetExistingVariantsDays))
            && (
                (
                    ($this->_iCurrentHour >= $this->_getTimePart($sFrom, 'hour'))
                    && ($this->_iCurrentHour <= $this->_getTimePart($sTo, 'hour'))
                )
                && (
                    ($this->_iCurrentHour >= $this->_getTimePart($sFrom, 'hour'))
                    && ($this->_iCurrentHour <= $this->_getTimePart($sTo, 'hour'))
                )
            )
        ) {
            $sQuery = 'UPDATE
                oxarticles a,
                wmdk_ff_export_queue b
            SET
                b.LASTSYNC = "0000-00-00 00:00:00",
                b.ProcessIp = "' . $this->_getProcessIp() . '",
                b.OXTIMESTAMP = "0000-00-00 00:00:00"
            WHERE
                (a.OXARTNUM = b.MasterProductNumber)
                AND (b.ProductNumber != b.MasterProductNumber)
                AND (a.OXTIMESTAMP > b.OXTIMESTAMP)
                AND (a.OXTIMESTAMP > "' . date('Y-m-d H:i:s', strtotime($sResetExistingArticlesSinceDays)) . '")
                AND (b.OXTIMESTAMP != "0000-00-00 00:00:00");';

            $iReseted = DatabaseProvider::getDb()->execute($sQuery);

            // LOG
            $this->_aResponse['reseted_variants'] = $iReseted;
        }
    }
    
    
    /**
     * Reset variants that are missing variant name attributes.
     */
    private function _resetVariantsWithoutVarname() {
        $sQuery = 'UPDATE
            wmdk_ff_export_queue
        SET
            LASTSYNC = "0000-00-00 00:00:00",
            ProcessIp = "' . $this->_getProcessIp() . '",
            OXTIMESTAMP = "0000-00-00 00:00:00"
        WHERE
            (Attributes LIKE "=%");';
        
        $iVarname = DatabaseProvider::getDb()->execute($sQuery);
        
        // LOG
        $this->_aResponse['reseted_varname'] = $iVarname;
    }
    
    
    /**
     * Reset products that are missing from the queue.
     */
    private function _resetMissingProducts() {
        $aChannelList = wmdkffexport_helper::getChannelList();
        
        $sQuery = 'UPDATE 
            oxarticles 
        SET 
            OXTIMESTAMP = OXTIMESTAMP, 
            WMDK_FFQUEUE = "0" 
        WHERE 
            OXID NOT IN (SELECT OXID FROM wmdk_ff_export_queue WHERE Channel LIKE "' . $aChannelList[0]['code'] . '");';
        
        $iMissing = DatabaseProvider::getDb()->execute($sQuery);
        
        // LOG
        $this->_aResponse['missing_products'] = $iMissing;
    }
    
    
    /**
     * Add missing products into the queue.
     */
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
        LIMIT ' . (int) Registry::getConfig()->getConfigParam('sWmdkFFQueueResetLimit');
        
        $oResult = DatabaseProvider::getDb(FALSE)->select($sQuery);

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
            $iReseted = DatabaseProvider::getDb()->execute( implode(PHP_EOL, $aSqlQueries) );
        
            // LOG
            $this->_aResponse['added_products'] = floor( count($aSqlQueries) / 3 * 2 );
        }
    }
    
    
    /**
     * Correct parent stock values after queue updates.
     */
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
            DatabaseProvider::getDb()->execute($sQuery);
            
            // LOG
            $this->_aResponse['correct_parent_stock'] = TRUE;
            
        } catch (Exception $oException) {
            // ERROR
            
            // LOG
            $this->_aResponse['correct_parent_stock'] = FALSE;
        }
    }
    
    
    /**
     * Update queue status flags based on article activity.
     */
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
            DatabaseProvider::getDb()->execute($sQuery);
            
            // LOG
            $this->_aResponse['update_status'] = TRUE;
            
        } catch (Exception $oException) {
            // ERROR
            
            // LOG
            $this->_aResponse['update_status'] = FALSE;
        }
    }
    
    
    /**
     * Synchronize stock values in the queue.
     */
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
            DatabaseProvider::getDb()->execute($sArticles);
            DatabaseProvider::getDb()->execute($sParents);
            
            // LOG
            $this->_aResponse['update_stock'] = TRUE;
            
        } catch (Exception $oException) {
            // ERROR
            
            // LOG
            $this->_aResponse['update_stock'] = FALSE;
        }
    }
    
    
    /**
     * Reset variants whose parents changed recently.
     *
     * @param string $sTimeBack Relative time window.
     */
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
            DatabaseProvider::getDb()->execute($sArticles);
            
            // LOG
            $this->_aResponse['reset_variants'] = TRUE;
            
        } catch (Exception $oException) {
            // ERROR
            
            // LOG
            $this->_aResponse['reset_variants'] = FALSE;
        }
    }


    /**
     * Reset sibling variants for recently modified parents.
     *
     * @param string $sTimeBack Relative time window.
     */
    private function _resetSiblings($sTimeBack = '-90 minutes') {
        $bUpdateSiblings = Registry::getConfig()->getConfigParam('bWmdkFFQueueUpdateSiblings');

        if ($bUpdateSiblings) {
            $sQuery = 'UPDATE 
                wmdk_ff_export_queue AS a
            SET
                a.LASTSYNC = "0000-00-00 00:00:00",
                a.ProcessIp = "' . $this->_getProcessIp() . '",
                a.OXTIMESTAMP = "0000-00-00 00:00:00"
            WHERE
                (a.MasterProductNumber IN (
                    SELECT MasterProductNumber FROM (
                        SELECT DISTINCT
                            c.MasterProductNumber
                        FROM
                            oxarticles AS b
                        LEFT JOIN
                            wmdk_ff_export_queue AS c
                        ON
                            (b.OXID = c.OXID)
                        WHERE
                            (c.MasterProductNumber != "")
                            AND (b.OXPARENTID != "")
                            AND (b.OXTIMESTAMP >= "' . date('Y-m-d H:i:s', strtotime($sTimeBack)) . '")
                    ) AS valid_numbers
                ))';

            $iMissing = DatabaseProvider::getDb()->execute($sQuery);

            // LOG
            $this->_aResponse['reseted_siblings'] = $iMissing;
        }
    }


    /**
     * Reset articles that still have no product picture.
     */
    private function _resetArticlesWithNoPic() {
        $sFrom = Registry::getConfig()->getConfigParam('sWmdkFFCronResetArticlesWithNoPicFrom');
        $sTo = Registry::getConfig()->getConfigParam('sWmdkFFCronResetArticlesWithNoPicTo');

        if (
            (
                ($this->_iCurrentHour >= $this->_getTimePart($sFrom, 'hour'))
                && ($this->_iCurrentHour <= $this->_getTimePart($sTo, 'hour'))
            )
            && (
                ($this->_iCurrentHour >= $this->_getTimePart($sFrom, 'hour'))
                && ($this->_iCurrentHour <= $this->_getTimePart($sTo, 'hour'))
            )
        ) {
            $sArticles = 'UPDATE
                wmdk_ff_export_queue b
            SET
                b.LASTSYNC = "0000-00-00 00:00:00",
                b.ProcessIp = "' . $this->_getProcessIp() . '",
                b.OXTIMESTAMP = "0000-00-00 00:00:00"
            WHERE
                (b.ImageURL LIKE "%nopic.jpg%")
                AND (b.OXACTIVE = 1) 
                AND (b.Stock > 0)';

            try {
                DatabaseProvider::getDb()->execute($sArticles);

                // LOG
                $this->_aResponse['fixed_nopic'] = TRUE;

            } catch (Exception $oException) {
                // ERROR

                // LOG
                $this->_aResponse['fixed_nopic'] = FALSE;
            }

        }
    }


    /**
     * Disable queue entries with missing origin OXIDs.
     */
    private function _disableMissingOriginOxid() {
        $sArticles = 'UPDATE
            wmdk_ff_export_queue b
        SET
            b.LASTSYNC = "0000-00-00 00:00:00",
            b.ProcessIp = "' . $this->_getProcessIp() . '",
            b.OXACTIVE = 0,
            b.OXHIDDEN = 1,
            b.OXTIMESTAMP = "0000-00-00 00:00:00"
        WHERE
            (b.OXACTIVE != 0)
            AND b.OXID NOT IN (
                SELECT OXID FROM (
                    SELECT OXID FROM oxarticles
                ) AS tmp
            );';

        try {
            DatabaseProvider::getDb()->execute($sArticles);

            // LOG
            $this->_aResponse['disable_missing_oxids'] = TRUE;

        } catch (Exception $oException) {
            // ERROR

            // LOG
            $this->_aResponse['disable_missing_oxids'] = FALSE;
        }
    }
    
    
    /**
     * Append the reset run response to the debug log file.
     */
    private function _log() {
        $sFilename  = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . Registry::getConfig()->getConfigParam('sWmdkFFDebugLogFileQueue'));
        
        // SET ADDITIONAL DATA
        $this->_aResponse['template'] = $this->_sTemplate;
        $this->_aResponse['process_ip'] = $this->_getProcessIp();
        $this->_aResponse['timestamp'] = date('Y-m-d H:i:s');
        $this->_aResponse['cronjob'] = $this->_isCron();
                
		$rFile = fopen($sFilename, 'a');
		fputs($rFile, json_encode($this->_aResponse) . PHP_EOL);			
		return fclose($rFile);
    }

    /**
     * Extract a time component from a formatted time string.
     *
     * @param string $sTime Time string.
     * @param string $sPart Part to extract (hour/minute/second).
     * @return int
     */
    private function _getTimePart($sTime, $sPart = 'hour') {
        $aTime = explode(':', $sTime);

        switch (trim(strtolower($sPart))) {
            case 1:
            case 'minute':
            case 'm':
                return (int) $aTime[1];
                break;

            case 2:
            case 'second':
            case 's':
                return (int) $aTime[2];
                break;

            default:
            case 0:
            case 'hour':
            case 'h':
                return (int) $aTime[0];
                break;
        }
    }

}
