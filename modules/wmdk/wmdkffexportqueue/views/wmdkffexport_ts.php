<?php

use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Traits\ProcessIpTrait;

/**
 * Class wmdkffexport_ts
 */
class wmdkffexport_ts extends oxubase
{    
    use ProcessIpTrait;

    protected $_aResponse = array(
        'success' => TRUE,

        'template' => NULL,
        
        'reviews_imported' => 0,
        'reviews_combined' => 0,
        'reviews_copied' => 0,
        
        'process_ip' => '',

        'validation_errors' => array(),
        'system_errors' => array(),
    );
    
    private $_sChannel = 'wh1_live_de';
    
    protected $_sApiUrl = NULL;
    
    protected $_dTSProductReviewStarsMax = 5;
    protected $_aTSProductReviews = array();
    
    protected $_sTemplate = 'wmdkffexport_ts.tpl';

    
    public function render() {
        // SET LIMITS
        ini_set('max_execution_time', (int) Registry::getConfig()->getConfigParam('sWmdkFFQueuePhpLimitTimeout'));
        ini_set('memory_limit', Registry::getConfig()->getConfigParam('sWmdkFFQueuePhpLimitMemory'));
        
        // GET DATA
        $this->_sChannel = Registry::getConfig()->getRequestParameter('channel');
        
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
            $this->_copyReviews();
        }
    }
    
    
    private function _loadReviews() {
        $this->_sApiUrl = Registry::getConfig()->getConfigParam('sWmdkFFImportTSApiUrl');
        
        $oJson = json_decode( file_get_contents($this->_sApiUrl) );
        
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
            $sQuery = 'UPDATE
                wmdk_ff_export_queue
            SET
                TrustedShopsRating = ' . $aData['rating'] . ',
                TrustedShopsRatingCnt = ' . $aData['rating_count'] . ',
                TrustedShopsRatingPercentage = ' . $aData['rating_percentage'] . ',
                LASTSYNC = LASTSYNC,
                OXTIMESTAMP = OXTIMESTAMP
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
                
                $sQuery = 'UPDATE
                    wmdk_ff_export_queue
                SET
                    TrustedShopsRating = "' . $aRow['TrustedShopsRating'] . '",
                    TrustedShopsRatingCnt = "' . $aRow['TrustedShopsRatingCnt'] . '",
                    TrustedShopsRatingPercentage = "' . $aRow['TrustedShopsRatingPercentage'] . '",
                    LASTSYNC = LASTSYNC,
                    OXTIMESTAMP = OXTIMESTAMP
                WHERE
                    (ProductNumber = "' . $aRow['ProductNumber'] . '")';
        
                $iCombined += \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
                
                $oResult->fetchRow();
            }
        }
        
        // LOG
        $this->_aResponse['reviews_combined'] = $iCombined;
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
        $sQuery = 'UPDATE 
            wmdk_ff_export_queue
        SET
            TrustedShopsRatingCnt = "",
            LASTSYNC = LASTSYNC,
            OXTIMESTAMP = OXTIMESTAMP
        WHERE
            TrustedShopsRatingCnt != "";';

        $iReseted = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
        
        // LOG
        $this->_aResponse['reviews_reseted_in_queue'] = $iReseted;
    }
    
    private function _copyReviews() {
        $sQuery = 'UPDATE
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
            a.LASTSYNC = a.LASTSYNC,
            a.OXTIMESTAMP = a.OXTIMESTAMP
        WHERE
            (a.MasterProductNumber = b.ProductNumber)
            AND (
                 (a.TrustedShopsRating != b.TrustedShopsRating)
                 OR (a.TrustedShopsRatingCnt != b.TrustedShopsRating)
                 OR (a.TrustedShopsRatingPercentage != b.TrustedShopsRating)
             );';
        
        $iCopied = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
        
        // LOG
        $this->_aResponse['reviews_copied'] = $iCopied;
    }
    
    
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

}
