<?php

use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Service\LogFilePathResolver;
use Wmdk\FactFinderQueue\Service\ModuleSettingsReader;

/**
 * Class wmdkffexport_ts
 */
class wmdkffexport_ts extends oxubase
{    
    protected $_aResponse = array(
        'success' => TRUE,

        'template' => NULL,
        
        'reviews_imported' => 0,
        'reviews_combined' => 0,
        'reviews_combined_siblings' => 0,
        'reviews_copied' => 0,
        
        'process_ip' => '',

        'validation_errors' => array(),
        'system_errors' => array(),
    );
    
    private $_sChannel = NULL;
    
    protected $_sProcessIp = NULL;
    
    protected $_sApiUrl = NULL;
    
    protected $_dTSProductReviewStarsMax = 5;
    protected $_aTSProductReviews = array();
    
    protected $_sTemplate = 'wmdkffexport_ts.tpl';

    
    public function render() {
        // SET LIMITS
        ini_set('max_execution_time', (int) Registry::getConfig()->getConfigParam('sWmdkFFQueuePhpLimitTimeout'));
        ini_set('memory_limit', Registry::getConfig()->getConfigParam('sWmdkFFQueuePhpLimitMemory'));
        
        // GET DATA
        $this->_sChannel = $this->_getImportChannel();
        
        $this->_startImport();
        
        // FILE LOG
        $this->_log();

        // OUTPUT
        if (!$this->_isCron()) {
            $this->_aViewData['sResponse'] = json_encode($this->_aResponse);
        }

        return $this->_sTemplate;
    }
    
    
    private function _startImport() {
        if ($this->_loadReviews()) {
            $this->_importReviews();
            $this->_combineReviews();
            $this->_combineSiblingReviews();
            $this->_copyReviews();
        }
    }
    
    
    private function _loadReviews() {
        $this->_sApiUrl = Registry::getConfig()->getConfigParam('sWmdkFFImportTSApiUrl');

        $sJson = @file_get_contents($this->_sApiUrl);

        if ($sJson === FALSE) {
            $this->_aResponse['success'] = FALSE;
            $this->_aResponse['validation_errors'] = array('ERROR_TS_API_REQUEST_FAILED');
            $this->_aResponse['system_errors'][] = 'Trusted Shops API request failed for URL: ' . $this->_sApiUrl;

            return FALSE;
        }

        $oJson = json_decode($sJson);
        
        if (
            isset($oJson->response->code)
            && ($oJson->response->code == 200)
        ) {
            foreach ($oJson->response->data->shop->products as $iKey => $oProduct) {
                
                // CALC PERCENTAGE
                $dRatingPercentage = ( (double) $oProduct->qualityIndicators->reviewIndicator->overallMark ) / $this->_dTSProductReviewStarsMax * 100;
                
                $this->_aTSProductReviews[(string) $oProduct->sku] = array(
                    'rating' => (string) number_format((double) $oProduct->qualityIndicators->reviewIndicator->overallMark, 2, '.', ''),
                    'rating_count' => (string) $oProduct->qualityIndicators->reviewIndicator->totalReviewCount,
                    'rating_percentage' => (string) number_format((double) $dRatingPercentage, 0, '.', ''),
                );
            }
            
            return count($this->_aTSProductReviews) > 0;
            
        } else {
            // ERROR
            $this->_aResponse['success'] = FALSE;
            $this->_aResponse['validation_errors'] = array('ERROR_TS_API_REQUEST_FAILED');
        }
        
        return FALSE;
    }
    
    
    private function _importReviews() { 
        $aReviewedArticles = array();

        // FIX #51521
        $this->_resetReviewsInQueue();
        
        foreach ($this->_aTSProductReviews as $sSku => $aData) {
            $sQuery = 'UPDATE IGNORE
                wmdk_ff_export_queue
            SET
                TrustedShopsRating = ' . $aData['rating'] . ',
                TrustedShopsRatingCnt = ' . $aData['rating_count'] . ',
                TrustedShopsRatingPercentage = ' . $aData['rating_percentage'] . ',
                ' . $this->_keepSyncTimestamps() . '
            WHERE
                (ProductNumber LIKE "' . $sSku .'")
                AND (
                    (TrustedShopsRating != ' . $aData['rating'] . ')
                    OR (TrustedShopsRatingCnt != ' . $aData['rating_count'] . ')
                    OR (TrustedShopsRatingPercentage != ' . $aData['rating_percentage'] . ')
                );';
            
            \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
            
            // LOG
            $aReviewedArticles[] = $sSku;
        }
        
        // LOG
        $this->_aResponse['reviews_imported'] = count($aReviewedArticles);
        if (!$this->_isCron()) {
            $this->_aResponse['imported_product_reviews'] = $aReviewedArticles;
        }
    }
    
    
    private function _combineReviews() {
        $this->_createTmpReviewData();
        $iCombined = 0;
        
        $sQuery = 'SELECT
            ProductNumber,
            TrustedShopsRating,
            TrustedShopsRatingCnt,
            TrustedShopsRatingPercentage
        FROM
            wmdk_ff_export_queue_tmp_ts
        WHERE
            (TrustedShopsRating > 0)
            AND (
                (RelatedProductNumbers IS NOT NULL)
                AND (RelatedProductNumbers != "")
            )';
        
        $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(FALSE)->select($sQuery);
        
        //Fetch the results row by row
        if ($oResult != FALSE && $oResult->count() > 0) {
            while (!$oResult->EOF) {
                $aRow = $oResult->getFields();
                
                $sProductNumber = $aRow['ProductNumber'];
                
                $sQuery = 'UPDATE IGNORE
                    wmdk_ff_export_queue
                SET
                    TrustedShopsRating = "' . $aRow['TrustedShopsRating'] . '",
                    TrustedShopsRatingCnt = "' . $aRow['TrustedShopsRatingCnt'] . '",
                    TrustedShopsRatingPercentage = "' . $aRow['TrustedShopsRatingPercentage'] . '",
                    ' . $this->_keepSyncTimestamps() . '
                WHERE
                    (ProductNumber = "' . $aRow['ProductNumber'] . '")';
        
                $iCombined += \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
                
                $oResult->fetchRow();
            }
        }
        
        // LOG
        $this->_aResponse['reviews_combined'] = $iCombined;
    }
    
    /**
     * Keep both automatic timestamp columns unchanged while importing ratings. UPDATE IGNORE lets
     * legacy zero markers be assigned back to themselves under the strict OXID 7 database mode.
     */
    private function _keepSyncTimestamps($sAlias = '') {
        $sPrefix = ($sAlias !== '') ? ($sAlias . '.') : '';

        return $sPrefix . 'LASTSYNC = ' . $sPrefix . 'LASTSYNC,
            ' . $sPrefix . 'OXTIMESTAMP = ' . $sPrefix . 'OXTIMESTAMP';
    }

    /**
     * Redmine #69552 - a rating that Trusted Shops returned for one variant never reached the
     * variant's siblings.
     *
     * _combineReviews() only aggregates products whose oxarticles.WMDKTRUSTEDSHOPSRELATEDPRODUCTS
     * column is maintained by hand, and _copyReviews() below fans a rating out from the row where
     * ProductNumber = MasterProductNumber. So when Trusted Shops attached the review to a variant
     * and the master row itself had none - the ordinary case, since a reviewer buys a size, not a
     * master - there was nothing on the master to fan out, and every sibling stayed unrated while
     * the product detail page (which reads Trusted Shops directly) showed the stars.
     *
     * This step fills that gap: for every master row still without a rating, the ratings its own
     * siblings carry are aggregated onto it, using the same arithmetic _createTmpReviewData()
     * already uses for the hand-maintained groups. _copyReviews() then distributes it as before.
     *
     * Masters that already carry a rating are left untouched, so neither a rating Trusted Shops
     * gave the master directly nor the hand-maintained aggregate above is ever overwritten.
     */
    private function _combineSiblingReviews() {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sChannel = $oDb->quote($this->_sChannel);

        $sQuery = 'UPDATE IGNORE
            wmdk_ff_export_queue master
            INNER JOIN (
                SELECT
                    `CHANNEL`,
                    MasterProductNumber,
                    FORMAT(SUM(TrustedShopsRating) / COUNT(*), 2) AS SiblingRating,
                    SUM(TrustedShopsRatingCnt) AS SiblingRatingCnt,
                    (SUM(TrustedShopsRating) / COUNT(*)) / ' . (float) $this->_dTSProductReviewStarsMax . ' * 100 AS SiblingRatingPercentage
                FROM
                    wmdk_ff_export_queue
                WHERE
                    (`CHANNEL` = ' . $sChannel . ')
                    AND (TrustedShopsRating > 0)
                    AND (TrustedShopsRatingCnt > 0)
                GROUP BY
                    `CHANNEL`,
                    MasterProductNumber
            ) siblings
                ON (siblings.`CHANNEL` = master.`CHANNEL`)
                AND (siblings.MasterProductNumber = master.MasterProductNumber)
        SET
            master.TrustedShopsRating = siblings.SiblingRating,
            master.TrustedShopsRatingCnt = siblings.SiblingRatingCnt,
            master.TrustedShopsRatingPercentage = siblings.SiblingRatingPercentage,
            ' . $this->_keepSyncTimestamps('master') . '
        WHERE
            (master.`CHANNEL` = ' . $sChannel . ')
            AND (master.ProductNumber = master.MasterProductNumber)
            AND (
                (master.TrustedShopsRatingCnt IS NULL)
                OR (master.TrustedShopsRatingCnt = "")
                OR (master.TrustedShopsRatingCnt = 0)
            );';

        $iCombined = $oDb->execute($sQuery);

        // LOG
        $this->_aResponse['reviews_combined_siblings'] = $iCombined;
    }

    private function _createTmpReviewData() {
        // TRUNCATE
        \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute('TRUNCATE `wmdk_ff_export_queue_tmp_ts`;');
        
        $sQuery = 'SELECT
            oxarticles.OXARTNUM AS ProductNumber,
            oxarticles.WMDKTRUSTEDSHOPSRELATEDPRODUCTS
        FROM
            oxarticles
        WHERE
            (oxarticles.WMDKTRUSTEDSHOPSRELATEDPRODUCTS IS NOT NULL)
            AND (oxarticles.WMDKTRUSTEDSHOPSRELATEDPRODUCTS != "")';
        
        $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(FALSE)->select($sQuery);
        
        //Fetch the results row by row
        if ($oResult != FALSE && $oResult->count() > 0) {
            while (!$oResult->EOF) {
                $aRow = $oResult->getFields();
                
                $sProductNumber = $aRow['ProductNumber'];
                $aRelatedProducts = array();
                
                foreach (explode(',', $aRow['WMDKTRUSTEDSHOPSRELATEDPRODUCTS']) as $iKey => $sRelatedProductNumber) {
                    $aRelatedProducts[] = '(ProductNumber = "' . $sRelatedProductNumber . '")';
                }
                
                $sQuery = 'INSERT IGNORE INTO wmdk_ff_export_queue_tmp_ts(ProductNumber, TrustedShopsRating, TrustedShopsRatingCnt, TrustedShopsRatingPercentage, RelatedProductNumbers)
                    SELECT DISTINCT
                        "' . $sProductNumber . '",
                        FORMAT(SUM(TrustedShopsRating) / COUNT(*), 2),
                        SUM(TrustedShopsRatingCnt),
                        (SUM(TrustedShopsRating) / COUNT(*)) / 5 * 100,
                        "' . $aRow['WMDKTRUSTEDSHOPSRELATEDPRODUCTS'] . '"
                    FROM
                        wmdk_ff_export_queue
                    WHERE
                        (`CHANNEL` = "' . $this->_sChannel . '")
                        AND (
                            ' . implode(' OR ', $aRelatedProducts) . '
                            OR (ProductNumber = "' . $sProductNumber . '")
                        )
                        AND (TrustedShopsRatingCnt > 0)';
        
                \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
                
                $oResult->fetchRow();
            }
        }
    }

    private function _resetReviewsInQueue() {
        $sQuery = 'UPDATE IGNORE
            wmdk_ff_export_queue
        SET
            TrustedShopsRatingCnt = "",
            ' . $this->_keepSyncTimestamps() . '
        WHERE
            TrustedShopsRatingCnt != "";';

        $iReseted = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
        
        // LOG
        $this->_aResponse['reviews_reseted_in_queue'] = $iReseted;
    }
    
    private function _copyReviews() {
        $sQuery = 'UPDATE IGNORE
            wmdk_ff_export_queue a,
            (
                SELECT
                    ProductNumber,
                    TrustedShopsRating,
                    TrustedShopsRatingCnt,
                    TrustedShopsRatingPercentage
                FROM
                    wmdk_ff_export_queue
                WHERE
                    (MasterProductNumber = ProductNumber)
                    AND (
                        (TrustedShopsRating > 0)
                        AND (TrustedShopsRatingCnt > 0)
                        AND (TrustedShopsRatingPercentage > 0)
                    )
            ) b
        SET
            a.TrustedShopsRating = b.TrustedShopsRating,
            a.TrustedShopsRatingCnt = b.TrustedShopsRatingCnt,
            a.TrustedShopsRatingPercentage = b.TrustedShopsRatingPercentage,
            ' . $this->_keepSyncTimestamps('a') . '
        WHERE
            (a.MasterProductNumber = b.ProductNumber)
            AND (
                 (a.TrustedShopsRating != b.TrustedShopsRating)
                 OR (a.TrustedShopsRatingCnt != b.TrustedShopsRatingCnt)
                 OR (a.TrustedShopsRatingPercentage != b.TrustedShopsRatingPercentage)
             );';
        
        $iCopied = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
        
        // LOG
        $this->_aResponse['reviews_copied'] = $iCopied;
    }
    
    
    private function _getProcessIp($sIp = FALSE) {
        if ($this->_sProcessIp == NULL) {
            
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $this->_sProcessIp = $_SERVER['HTTP_CLIENT_IP'];
                
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $this->_sProcessIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
                
            } else {
                $this->_sProcessIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            }
            
        }
        
        return ($sIp != FALSE) ? $sIp : $this->_sProcessIp;
    }
    
    
    private function _isCron() {
        $sIsCronjobOrg = in_array( $this->_getProcessIp(), explode(',', Registry::getConfig()->getConfigParam('sWmdkFFDebugCronjobIpList') ) );
        
        return (($_SERVER['WMDKFFEXPORT_IS_CRON'] ?? '0') === '1') || $sIsCronjobOrg;
    }
    
    
    private function _log() {
        $resolver = new LogFilePathResolver();
        $sFilename = $resolver->resolveFromSetting('sWmdkFFDebugLogFileQueue', 'log/KUSSIN_FACTFINDER_QUEUE.log');
        $resolver->ensureDirectoryForFile($sFilename);
        
        // SET ADDITIONAL DATA
        $this->_aResponse['template'] = $this->_sTemplate;
        $this->_aResponse['process_ip'] = $this->_getProcessIp();
        $this->_aResponse['timestamp'] = date('Y-m-d H:i:s');
        $this->_aResponse['cronjob'] = $this->_isCron();
                
		$rFile = fopen($sFilename, 'a');
        if (!$rFile) {
            return false;
        }

		fputs($rFile, json_encode($this->_aResponse) . PHP_EOL);			
		return fclose($rFile);
    }

    private function _getImportChannel()
    {
        $sChannel = (string) Registry::getRequest()->getRequestParameter('channel');

        if ($sChannel !== '') {
            return $sChannel;
        }

        $sChannelConfig = (new ModuleSettingsReader())->getString('sWmdkFFGeneralChannelList');

        foreach (explode(',', $sChannelConfig) as $sConfiguredChannel) {
            $aParams = explode('::', trim($sConfiguredChannel));

            if (($aParams[0] ?? '') !== '') {
                return $aParams[0];
            }
        }

        return 'wh1_live_de';
    }

}
