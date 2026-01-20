<?php

use OxidEsales\Eshop\Application\Model\SeoEncoderArticle;
use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Traits\ClonedAttributesTrait;
use Wmdk\FactFinderQueue\Traits\ConverterTrait;
use Wmdk\FactFinderQueue\Traits\CronLockTrait;
use Wmdk\FactFinderQueue\Traits\FlourTrait;
use Wmdk\FactFinderQueue\Traits\ProcessIpTrait;

/**
 * Builds and updates the export queue for FactFinder products.
 */
class wmdkffexport_queue extends oxubase
{
    use ClonedAttributesTrait;
    use ConverterTrait;
    use CronLockTrait;
    use FlourTrait;
    use ProcessIpTrait;

    CONST DEFAULT_TAX_RATE = 19;

    protected $_sOxid = NULL;
    protected $_sChannel = NULL;
    protected $_iShopId = NULL;
    protected $_iLang = NULL;    
    protected $_iLastSync = NULL;
    
    protected $_oViewConfig = NULL; 
    
    protected $_bIsVariant = FALSE;
    protected $_bIsParent = FALSE;
    protected $_oProduct = NULL;
    protected $_oParent = NULL;
    protected $_oFirstActiveVariant = NULL;
    
    protected $_aUpdateData = array();
    
    protected $_aPreparedUpdateQueries = array();
    
    protected $_sLanguageSuffix = ''; 
    
    protected $_sBaseUrl = NULL;
    
    protected $_aCleanAttributeTitleSearchKeys = NULL;
    protected $_aCleanAttributeTitleReplaceKeys = NULL;
    
    protected $_aResponse = array(
        'success' => TRUE,

        'template' => NULL,
        
        'queued_products' => array(),
        
        'process_ip' => '',

        'validation_errors' => array(),
        'system_errors' => array(),
    );
    
    protected $_sTemplate = 'wmdkffexport_queue.tpl';
    
    
    /**
     * Loads queued products, prepares updates, and returns the template name.
     *
     * @return string
     */
    public function render() {        
        if (!$this->_hasCronjobFlag()) {

            // SET FLAG
            $this->_setCronjobFlag();

            // Settings
            $iQueueLimit = (int) Registry::getConfig()->getConfigParam('sWmdkFFQueueLimit');
            $iArticleStatus = Registry::getConfig()->getConfigParam('iArticleStatus');
            $iArticleMinStock = (int) Registry::getConfig()->getConfigParam('iArticleMinStock');

            // LOAD PRODUCTS
            $aWhere = array(
                '(`wmdk_ff_export_queue`.`OXID` = `oxarticles`.`OXID`)',
            );
            $aParams = array();

            if ($iArticleStatus !== '') {
                $aWhere[] = '(`wmdk_ff_export_queue`.`OXACTIVE` = ?)';
                $aParams[] = (int) $iArticleStatus;
            }

            $aWhere[] = '(`wmdk_ff_export_queue`.`Stock` >= ?)';
            $aParams[] = $iArticleMinStock;

            $sQuery = 'SELECT 
                `wmdk_ff_export_queue`.`OXID`, 
                `wmdk_ff_export_queue`.`Channel`, 
                `wmdk_ff_export_queue`.`OXSHOPID`, 
                `wmdk_ff_export_queue`.`LANG`, 
                `wmdk_ff_export_queue`.`LASTSYNC` 
            FROM 
                `wmdk_ff_export_queue`,
                `oxarticles`
            WHERE
                ' . implode(' AND ', $aWhere) . '
            ORDER BY 
                `wmdk_ff_export_queue`.`LASTSYNC` ASC, 
                `wmdk_ff_export_queue`.`Stock` DESC
            LIMIT ' . $iQueueLimit . ';';
            $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(FALSE)->select($sQuery, $aParams);

            if ($oResult != FALSE && $oResult->count() > 0) {
                while (!$oResult->EOF) {
                    $aData = $oResult->getFields();
                    
                    // SET BASE DATA
                    $this->_sOxid = $aData['OXID'];
                    $this->_sChannel = $aData['Channel'];
                    $this->_iShopId = (int) $aData['OXSHOPID'];
                    $this->_iLang = (int) $aData['LANG'];  
                    $this->_iLastSync = strtotime($aData['LASTSYNC']);

                    // SET LANGUAGE
    //                $this->_oViewConfig->setViewConfigParam('lang', $this->_iLang);
                    $this->_sLanguageSuffix = ($this->_iLang > 0) ? '_' . $this->_iLang : '';

                    // LOAD Product
                    $this->_oProduct = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
                    $this->_oProduct->loadInLang($this->_iLang, $this->_sOxid);

                    // LOAD Parent
                    if ($this->_oProduct->oxarticles__oxparentid->value != '') {
                        $this->_bIsVariant = TRUE;
                        $this->_oParent = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
                        $this->_oParent->loadInLang($this->_iLang, $this->_oProduct->oxarticles__oxparentid->value);
                    }


                    $this->_bIsParent = (((int) $this->_oProduct->oxarticles__oxvarcount->value) > 0) ? TRUE : FALSE;

                    // UPDATE Product
                    $this->_updateQueueData();

                    // PREPARE Product Update
                    $this->_prepareUpdateQuery();

                    // RESET
                    $this->_sOxid = NULL;
                    $this->_sChannel = NULL;
                    $this->_iShopId = NULL;
                    $this->_iLang = NULL;    
                    $this->_iLastSync = NULL;

                    $this->_bIsVariant = FALSE;                
                    $this->_bIsParent = FALSE;
                    $this->_oProduct = NULL;
                    $this->_oParent = NULL;
                    $this->_oFirstActiveVariant = NULL;

                    $this->_aUpdateData = array();

                    // NEXT
                    $oResult->fetchRow();
                }
            }

            // SAVE DATA
            $this->_saveQueueData();

            // REMOVE FLAG
            $this->_removeCronjobFlag();
            
        } else {
            // ERROR
            $this->_aResponse['validation_errors'][] = 'ERROR_CRON_STILL_RUNNING';
        }
        
        // FILE LOG
        $this->_log();

        // OUTPUT
        if (!$this->_isCron()) {
            $this->_aViewData['sResponse'] = json_encode($this->_aResponse);
        }

        return $this->_sTemplate;
    }
    
    /**
     * Populate update data for the current product/variant.
     */
    private function _updateQueueData() {       

        if (
            (strtotime($this->_oProduct->oxarticles__oxtimestamp->value) > $this->_iLastSync)
            || (
                $this->_bIsVariant
                && (strtotime($this->_oParent->oxarticles__oxtimestamp->value) > $this->_iLastSync)
            )
        ) {
            
            $sManufacturer = ($this->_bIsVariant) ? $this->_oParent->getManufacturer()->oxmanufacturers__oxtitle->value : $this->_oProduct->getManufacturer()->oxmanufacturers__oxtitle->value;
        
            $this->_aUpdateData['OXACTIVE'] = ($this->_bIsParent) ? 0 : $this->_oProduct->oxarticles__oxactive->value;
            $this->_aUpdateData['OXHIDDEN'] = $this->_getHidden(); 
            $this->_aUpdateData['OXTIMESTAMP'] = $this->_oProduct->oxarticles__oxtimestamp->value; 

            $this->_aUpdateData['ProductNumber'] = $this->_oProduct->oxarticles__oxartnum->value;
            $this->_aUpdateData['MasterProductNumber'] = $this->_oProduct->oxarticles__oxartnum->value;
            
            $this->_aUpdateData['Title'] = preg_replace('/20[0-9][0-9]/','', $this->_oProduct->oxarticles__oxtitle->value) ; // wmdk_dkussin (Ticket: #343619)
            
            $this->_aUpdateData['Short'] = $this->_oProduct->oxarticles__oxshortdesc->value;
            
            $sThumbnail = $this->_oProduct->getThumbnailUrl();
            $this->_aUpdateData['HasProductImage'] = (strpos($sThumbnail, 'nopic.jpg') !== FALSE) ? '' : 1;
            $this->_aUpdateData['ImageURL'] = $sThumbnail;
            $this->_aUpdateData['SuggestPictureURL'] = $this->_oProduct->getIconUrl();
            
            $this->_aUpdateData['HasFromPrice'] = $this->_getHasFromPrice();
            $this->_aUpdateData['Price'] = $this->_getPrice();
            $this->_aUpdateData['FromPrice'] = $this->_getPrice(true);
            $this->_aUpdateData['MSRP'] = $this->_getMsrp();
            $this->_aUpdateData['BasePrice'] = $this->_getBasePrice();
            $this->_aUpdateData['Tax'] = $this->_getTaxRate();
            
            $this->_aUpdateData['Stock'] = $this->_getStock();

            $this->_aUpdateData['Description'] = $this->_getDescription();
            
            $this->_aUpdateData['Deeplink'] = $this->_getDeeplink();
            
            $this->_aUpdateData['Marke'] = $sManufacturer;

            $this->_aUpdateData['CategoryPath'] = $this->_getCategoryPath();
            
            $this->_aUpdateData['Attributes'] = $this->_getAttributes();
            $this->_aUpdateData['ClonedAttributes'] = $this->_cloneAttributes(
                $this->_aUpdateData['Attributes'],
                $this->_oProduct->oxarticles__wmdkvarselectmapping->value
            );
            $this->_aUpdateData['NumericalAttributes'] = $this->_getNumericalAttributes();
            $this->_aUpdateData['SearchAttributes'] = $this->_getSearchAttributes();
            
            $this->_aUpdateData['SearchKeywords'] = $this->_oProduct->oxarticles__oxsearchkeys->value;
            
            $this->_aUpdateData['EAN'] = $this->_oProduct->oxarticles__oxean->value;
            $this->_aUpdateData['MPN'] = $this->_oProduct->oxarticles__oxmpn->value;
            $this->_aUpdateData['DISTEAN'] = $this->_oProduct->oxarticles__oxdistean->value;
            
            $this->_aUpdateData['Weight'] = $this->_oProduct->oxarticles__oxdistean->value;
            
            $this->_aUpdateData['Rating'] = $this->_oProduct->oxarticles_oxrating->value;
            $this->_aUpdateData['RatingCnt'] = $this->_oProduct->oxarticles__oxratingcnt->value;
            
            $this->_aUpdateData['HasNewFlag'] = $this->_hasHasNewFlag('');
            $this->_aUpdateData['HasTopFlag'] = $this->_hasHasTopFlag('');
            
            $sSaleAmount = $this->_getSaleAmount();
            $this->_aUpdateData['HasSaleFlag'] = ($sSaleAmount != '') ? 1 : '';
            $this->_aUpdateData['SaleAmount'] = $sSaleAmount;
            
            $sVariantsSizelistMarkup = $this->_getVariantsSizelistMarkup();
            $this->_aUpdateData['HasVariantsSizelist'] = ($sVariantsSizelistMarkup != '') ? 1 : '';
            $this->_aUpdateData['VariantsSizelistMarkup'] = $sVariantsSizelistMarkup;

            // flour POS
            $this->_aUpdateData['FlourId'] = $this->_getFlourId();
            $this->_aUpdateData['FlourActive'] = $this->_getFlourActive();
            $this->_aUpdateData['FlourPrice'] = $this->_getFlourPrice();
            $this->_aUpdateData['FlourSaleAmount'] = $this->_getFlourSaleAmount();
            $this->_aUpdateData['FlourShortUrl'] = $this->_getFlourShortUrl();
            
            $this->_aUpdateData['SoldAmount'] = $this->_oProduct->oxarticles__oxsoldamount->value;
            
            $this->_aUpdateData['DateInsert'] = $this->_oProduct->oxarticles__oxinsert->value;
            $this->_aUpdateData['DateModified '] = ($this->_oProduct->oxarticles__wmdkmodified->value != '0000-00-00') ? $this->_oProduct->oxarticles__wmdkmodified->value : $this->_oProduct->oxarticles__oxinsert->value;
            
            if ($this->_bIsVariant) {
                // SET PARENT DATA
                $this->_aUpdateData['MasterProductNumber'] = $this->_oParent->oxarticles__oxartnum->value;
            }
            
            // DB LOG
            $this->_aUpdateData['ProcessIp'] = $this->_getProcessIp();
            
            // FILE LOG
            $this->_aResponse['queued_products'][] = $this->_oProduct->oxarticles__oxartnum->value;
        }
    }
    
    
    /**
     * Determine the hidden flag for the current product.
     *
     * @return int
     */
    private function _getHidden() {
        $oArticle = ($this->_bIsVariant) ? $this->_oParent : $this->_oProduct;
        
        return $oArticle->oxarticles__oxhidden->value;
    }
    
    
    /**
     * Check whether the product has a from-price value.
     *
     * @return int
     */
    private function _getHasFromPrice() {
        $bWmdkFFQueueEnableFromPrice = (int) Registry::getConfig()->getConfigParam('bWmdkFFQueueEnableFromPrice');

        if ($this->_bIsVariant && !$bWmdkFFQueueEnableFromPrice) {
            return '';
        }

        $oArticle = ($this->_bIsVariant) ? $this->_oParent : $this->_oProduct;
        
        $dVarMinPrice = (double) $oArticle->oxarticles__oxvarminprice->value;
        $dVarMaxPrice = (double) $oArticle->oxarticles__oxvarmaxprice->value;
        
        return ($dVarMinPrice < $dVarMaxPrice) ? 1 : '';
    }
    
    
    /**
     * Resolve the active price for the current product.
     *
     * @param bool $bWmdkFFQueueEnableFromPrice Whether to allow from-price logic.
     * @return float
     */
    private function _getPrice($bWmdkFFQueueEnableFromPrice = false) {
        if ($this->_bIsParent || $this->_bIsVariant) {

            //$bWmdkFFQueueEnableFromPrice = (int) Registry::getConfig()->getConfigParam('bWmdkFFQueueEnableFromPrice');

            if ($this->_bIsParent || $bWmdkFFQueueEnableFromPrice) {
                $oFirstActiveVariant = ($this->_bIsVariant) ? $this->_getFirstActiveVariant($this->_oProduct->oxarticles__oxparentid->value) : $this->_getFirstActiveVariant();

                if ($oFirstActiveVariant != FALSE) {
                    return (double)$oFirstActiveVariant->oxarticles__oxprice->value;
                }

                return (double)$this->_oProduct->oxarticles__oxvarminprice->value;
            }
        }
        
		return (double) $this->_oProduct->oxarticles__oxprice->value;
    }
	
    
    /**
     * Get the MSRP value for the current product.
     *
     * @return float
     */
    private function _getMsrp() {
        if ($this->_bIsParent || $this->_bIsVariant) {

            $bWmdkFFQueueEnableFromPrice = (int) Registry::getConfig()->getConfigParam('bWmdkFFQueueEnableFromPrice');

            if ($this->_bIsParent || $bWmdkFFQueueEnableFromPrice) {
                $oFirstActiveVariant = ($this->_bIsVariant) ? $this->_getFirstActiveVariant($this->_oProduct->oxarticles__oxparentid->value) : $this->_getFirstActiveVariant();

                if ($oFirstActiveVariant != FALSE) {
                    return (double)$oFirstActiveVariant->oxarticles__oxtprice->value;
                }
            }
        }
        
		return (double) $this->_oProduct->oxarticles__oxtprice->value;
    }
	
    
    /**
     * Build a formatted base price string for the current product.
     *
     * @param string $sCurrenySign Currency sign to append.
     * @return string
     */
    private function _getBasePrice($sCurrenySign = '€') {
        $oUnitPrice = $this->_oProduct->getUnitPrice();

        if (is_object($oUnitPrice)) {
            $dUnitPrice = (double) $oUnitPrice->getPrice();
            $sUnitName = $this->_oProduct->getUnitName();
            
            return number_format($dUnitPrice, 2, ',', '.') . $sCurrenySign . '/' . $sUnitName;
        }
        
		return '';
    }


    /**
     * Resolve the tax rate for the current product.
     *
     * @return float
     */
    private function _getTaxRate() {
        $dTaxRate = (double) $this->_oProduct->oxarticles__oxvat->value;

        return ($dTaxRate > 0) ? $dTaxRate : self::DEFAULT_TAX_RATE;
    }
	
    
    /**
     * Get the stock value for the current product.
     *
     * @return int
     */
    private function _getStock() {
        return  ($this->_bIsParent) ? $this->_oProduct->oxarticles__oxvarstock->value : $this->_oProduct->oxarticles__oxstock->value;
    }
    
    
    /**
     * Build a combined description for the current product.
     *
     * @return string
     */
    private function _getDescription() {
        $sDescription = ($this->_bIsVariant) ? $this->_oParent->getLongDescription() : $this->_oProduct->getLongDescription();
        
        return $this->_removeHtml($sDescription);
    }
    
    /**
     * Resolve and cache the base shop URL for link building.
     *
     * @return string
     */
    private function _getBaseUrl() {
//        if ($this->_sBaseUrl == NULL) {
//            $aBaseUrl = explode('?', \OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('sShopURL'));
//            $this->_sBaseUrl = trim($aBaseUrl[0]);
//        }
//
//        return $this->_sBaseUrl;
        return '';
    }    
    
    /**
     * Build the product detail URL.
     *
     * @return string
     */
    private function _getDeeplink() {
        $bUseCategoryPath = (bool) Registry::getConfig()->getConfigParam('bWmdkFFQueueUseCategoryPath');

        return ($bUseCategoryPath) ? $this->_getMainCategoryLink() : $this->_getManufacturerLink();
    }

    /**
     * Build a link to the manufacturer listing.
     *
     * @return string
     */
    private function _getManufacturerLink() {
        $oSeoEncoderArticle = oxNew(SeoEncoderArticle::class);

        $sManufacturerLink = trim($oSeoEncoderArticle->getArticleManufacturerUri($this->_oProduct, $this->_iLang, TRUE));

        return strtolower($this->_getBaseUrl() . $sManufacturerLink);
    }

    /**
     * Build a link to the main category for the product.
     *
     * @return string
     */
    private function _getMainCategoryLink() {
        $oSeoEncoderArticle = oxNew(SeoEncoderArticle::class);

        $sMainCategoryLink = trim($oSeoEncoderArticle->getArticleMainUri($this->_oProduct, $this->_iLang));

        return strtolower($this->_getBaseUrl() . $sMainCategoryLink);
    }
    
    
    /**
     * Build a link to a subcategory.
     *
     * @param string $sCatId Category ID.
     * @return string
     */
    private function _getSubCategory($sCatId) {
        $sSubCategory = '';
        
        // LOAD CATEGORY
        $oCategory = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);
        $oCategory->load($sCatId);
            
        if ($oCategory->oxcategories__oxparentid->value != 'oxrootid') {
            $sSubCategory .= $this->_getSubCategory($oCategory->oxcategories__oxparentid->value);
            
            // PLACEHOLDER
            $sSubCategory .= '/';
        }

        $sSubCategory .= str_replace('/', '%2f', $this->_translateString($oCategory, 'oxcategories__oxtitle'));      
        
        return $sSubCategory;
    }
    
    
    /**
     * Build the category path string for the product.
     *
     * @return string
     */
    private function _getCategoryPath() {
        $aCategoryPathes = array();
        
        $oArticle = ($this->_bIsVariant) ? $this->_oParent : $this->_oProduct;
        
        foreach($oArticle->getCategoryIds() as $sCategoryId) {
            $aCategoryPath = '';
            
            // LOAD CATEGORY
            $oCategory = oxNew(\OxidEsales\Eshop\Application\Model\Category::class);
            $oCategory->load($sCategoryId);
            
            if ($oCategory->oxcategories__oxparentid->value != 'oxrootid') {                
                $aCategoryPath .= $this->_getSubCategory($oCategory->oxcategories__oxparentid->value);

                // PLACEHOLDER
                $aCategoryPath .= '/';
            }

            $aCategoryPath .= str_replace('/', '%2f', $this->_translateString($oCategory, 'oxcategories__oxtitle'));            
            
            $bSkip = strpos($aCategoryPath, trim(Registry::getConfig()->getConfigParam('sWmdkFFExportRemovePrefixCategories')));
            
            if (
                ($bSkip == FALSE) 
                && (!is_int($bSkip)) 
            ) {
                $aCategoryPathes[] = $aCategoryPath;
                
            }
        }
        
        return implode(Registry::getConfig()->getConfigParam('sWmdkFFQueueAttributeGlue'), $aCategoryPathes);
    }
    
    
    /**
     * Collect product attributes for export.
     *
     * @return string
     */
    private function _getAttributes() {
        $aAttributes = array();
        
        // VARIANTS
        if ($this->_oProduct->oxarticles__oxvarselect->value != '') {
            $aAttributes[] = $this->_getVariantsAttributes();
        }
        
        // ATTRIBUTES
        $aCsvAttributes = explode(',', Registry::getConfig()->getConfigParam('sWmdkFFExportCsvAttributes'));

        foreach($this->_oProduct->getAttributes() as $oAttribute) {
            $sCleanAttributeName = $this->_cleanAttributeTitle($this->_translateString($oAttribute, 'oxattribute__oxtitle'));

            if (in_array($oAttribute->oxattribute__oxtitle->value, $aCsvAttributes)) {
                // CSV
                $aCsvAttributeValues = explode(',', $this->_translateAttributeValue($oAttribute->oxattribute__oxid->value, $this->_bIsVariant));

                foreach ($aCsvAttributeValues as $iKey => $sValue) {
                    $aAttributes[] = $sCleanAttributeName . '=' . $this->_converter($sCleanAttributeName, trim($sValue));
                }

            } else {
                $sValue = $this->_converter($sCleanAttributeName, $this->_translateAttributeValue($oAttribute->oxattribute__oxid->value, $this->_bIsVariant));

                $aAttributes[] = $sCleanAttributeName . '=' . $sValue;
            }
        }
        
        // GENDER
        $aAttributes = $this->_hackAttributeGender($aAttributes);

        // SALE
        if (($this->_getSaleAmount() != '')) {
            $oLang = Registry::getLang();

            $sSaleLabel = $oLang->translateString( 'SALE', $this->_iLang);
            $sSaleValue = $oLang->translateString( 'REDUCED_ARTICLES', $this->_iLang);

            $aAttributes[] = $sSaleLabel . '=' . $sSaleValue;
        }
        
        return implode(Registry::getConfig()->getConfigParam('sWmdkFFQueueAttributeGlue'), $aAttributes);
    }


    /**
     * Collect variant attributes for export.
     *
     * @return string
     */
    private function _getVariantsAttributes() {
        $aAttributes = array();

        $aVarnames = explode('|', $this->_oProduct->oxarticles__oxvarname->value);
        $aVarselects = explode('|', $this->_oProduct->oxarticles__oxvarselect->value);

        foreach ($aVarnames as $iKey => $sAttribute) {
            $sAttributeName = trim($sAttribute);
            $sAttributeValue = isset($aVarselects[$iKey]) ? html_entity_decode(trim($aVarselects[$iKey]), ENT_QUOTES) : '';

            // HOTFIX #67324
            $oLang = Registry::getLang();
            $sAttributeName = $sAttributeName == "" ? $oLang->translateString( 'VARINAT', $this->_iLang) : $sAttributeName;

            $aAttributes[] = $sAttributeName . '=' . $this->_converter($sAttributeName, $sAttributeValue);
        }

        return implode(Registry::getConfig()->getConfigParam('sWmdkFFQueueAttributeGlue'), $aAttributes);
    }
    
    
    /**
     * Collect numerical attributes to export.
     *
     * @return string
     */
    private function _getNumericalAttributes() {
        return '';
    }
    
    
    /**
     * Collect searchable attributes for export.
     *
     * @return string
     */
    private function _getSearchAttributes() {
        $aAttributes = array();
        
        foreach($this->_oProduct->getAttributes() as $oAttribute) {
            $aAttributes[] = $oAttribute->oxattribute__oxvalue->value;
        }
        
        /* wmdk_dkussin (Ticket: #48108) */
        if ($this->_bIsParent || $this->_bIsVariant) {
            $oArticle = ($this->_bIsVariant) ? $this->_oParent : $this->_oProduct;
            
            // LOAD VARIANTS
            $sQuery = 'SELECT OXVARSELECT, OXSTOCK FROM `oxarticles` WHERE OXPARENTID = "' . $oArticle->oxarticles__oxid->value . '" ORDER BY OXSORT ASC;';  
            $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(FALSE)->select($sQuery);

            if ($oResult != FALSE && $oResult->count() > 0) {
                
                while (!$oResult->EOF) {
                    $aData = $oResult->getFields();           
                    $aAttributes[] = $aData['OXVARSELECT'];
                
                    // NEXT
                    $oResult->fetchRow();
                }
            }
        }
        /* END wmdk_dkussin (Ticket: #48108) */
        
        return implode(Registry::getConfig()->getConfigParam('sWmdkFFQueueAttributeGlue'), $aAttributes);
    }
    
    
    /**
     * Normalize gender-related attributes for export.
     *
     * @param array $aAttributes Attributes to inspect.
     * @return array
     */
    private function _hackAttributeGender($aAttributes) {
        $bHasNoGender = TRUE;
        
        foreach ($aAttributes as $iKey => $sValue) {
            if (strpos($sValue, 'Gender') !== FALSE) {
                $bHasNoGender = FALSE;
            } 
        }
        
        if ($bHasNoGender) {
            $aAttributes[] = 'Gender=Unisex';
        }
        
        return $aAttributes;
    }
    
    
    /**
     * Resolve the "new" flag value.
     *
     * @param int $bFalse Default value when not set.
     * @return int
     */
    private function _hasHasNewFlag($bFalse = 0) {
        $oArticle = ($this->_bIsVariant) ? $this->_oParent : $this->_oProduct;

        // TODO: Replace with general Setting
        $iNewUntill = strtotime($oArticle->oxarticles__wmdknewlabel->value);
        
        return ($iNewUntill >= time()) ? 1 : $bFalse;
    }
    
    
    /**
     * Resolve the "top" flag value.
     *
     * @param int $bFalse Default value when not set.
     * @return int
     */
    private function _hasHasTopFlag($bFalse = 0) {
        $oArticle = ($this->_bIsVariant) ? $this->_oParent : $this->_oProduct;
        
        $iMinSoldAmount = (int) Registry::getConfig()->getConfigParam('sWmdkFFQueueFlagTopseller');
        
        $iSoldAmount = (int) $oArticle->oxarticles__oxsoldamount->value;
        
        return ($iSoldAmount >= $iMinSoldAmount) ? 1 : $bFalse;
    }
    
    
    /**
     * Calculate the sale discount amount.
     *
     * @param string $sSign Percentage sign to append.
     * @return string
     */
    private function _getSaleAmount($sSign = '%') {
        $bWmdkFFQueueEnableFromPrice = (int) Registry::getConfig()->getConfigParam('bWmdkFFQueueEnableFromPrice');

        $dOxPrice = $this->_getPrice($bWmdkFFQueueEnableFromPrice == 1);
        $dOxTPrice = $this->_getMsrp();
        
        if ($dOxPrice < $dOxTPrice) {
            $dDiscount = 100 - ( ($dOxPrice * 100) / $dOxTPrice );
            
            return floor($dDiscount) . $sSign;
        }
        
        return '';
    }
    
    
    /**
     * Build the size list markup for variants.
     *
     * @param string $sGlue Glue string between entries.
     * @return string
     */
    private function _getVariantsSizelistMarkup($sGlue = '') {
        $sMarkup = '';
        $aDesktopMarkup = array();
        $aMobileMarkup = array();
        
        if ($this->_bIsParent || $this->_bIsVariant) {
            $oArticle = ($this->_bIsVariant) ? $this->_oParent : $this->_oProduct;
            
            // LOAD VARIANTS
            $sQuery = 'SELECT OXVARSELECT, OXSTOCK FROM `oxarticles` WHERE OXPARENTID = "' . $oArticle->oxarticles__oxid->value . '" ORDER BY OXSORT ASC;';  
            $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(FALSE)->select($sQuery);

            if ($oResult != FALSE && $oResult->count() > 0) {

                $aDesktopMarkup[] = '<ul class="sizeselection">';
                $aMobileMarkup[] = '<select class="sizeselection">';

                $aDuplicateCheck = array();

                $aOptionsWithStock = array();

                while (!$oResult->EOF) {
                    $aData = $oResult->getFields();

                    // MULTI-OPTIONS TO ARRAY
                    $aOption = explode('|', $aData['OXVARSELECT']);
                    $sOption = (string) isset($aOption[0]) ? trim($aOption[0]) : $aData['OXVARSELECT'];
                    $dStock = (float) $aData['OXSTOCK'];

                    if (
                        isset($aOptionsWithStock[$sOption])
                        && ($aOptionsWithStock[$sOption] < $dStock)
                    ) {
                        $aOptionsWithStock[$sOption] = $dStock;
                        
                    } elseif (!isset($aOptionsWithStock[$sOption])) {
                        // DEFAULT
                        $aOptionsWithStock[$sOption] = $dStock;
                    }

                    // NEXT
                    $oResult->fetchRow();
                }

                foreach ($aOptionsWithStock as $sOption => $dStock) {
                    $aDesktopMarkup[] = '<li' . (($dStock > 0) ? '' : ' class="sold-out"') . '><span>' . $sOption . '</span></li>';
                    $aMobileMarkup[] = '<option>' . $sOption . '</option>';
                }

                $aDesktopMarkup[] = '</ul>';
                $aMobileMarkup[] = '</select>';

                $sMarkup = implode($sGlue, $aDesktopMarkup) . implode($sGlue, $aMobileMarkup);

                // ESCAPE
                $sMarkup = str_replace(array("'", '"'), array("\'", "'"), $sMarkup);
            }
        }
        
        return $sMarkup;
    }
    
    

    /**
     * Load the first active variant for a product.
     *
     * @param string|null $sArticleId Parent article ID.
     * @param int $iActive Active flag filter.
     * @param int $iHidden Hidden flag filter.
     * @param int $iMinStock Minimum stock filter.
     * @return \OxidEsales\Eshop\Application\Model\Article|null
     */
	private function _getFirstActiveVariant($sArticleId = NULL, $iActive = 1, $iHidden = 0, $iMinStock = 0) {
        if ($this->_oFirstActiveVariant == NULL) {
            
            if ($sArticleId == NULL) {
                $sArticleId = $this->_oProduct->oxarticles__oxid->value;
            }
            
            $sQuery = 'SELECT OXID FROM oxarticles WHERE OXPARENTID = "' . $sArticleId . '" AND OXACTIVE = "' . $iActive . '" AND OXHIDDEN = "' . $iHidden . '" AND OXSTOCK >= "' . $iMinStock . '" ORDER BY OXPRICE ASC LIMIT 1';
            $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(FALSE)->select($sQuery);

            if ($oResult != FALSE && $oResult->count() > 0) {
                $sOxid = $oResult->fields[0];

                // LOAD PRODUCT
                $oProduct = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
                $oProduct->load($sOxid);

                $this->_oFirstActiveVariant =  ( ($oProduct->oxarticles__oxid->value != '') && ($oProduct->oxarticles__oxid->value != $sObjectId) ) ? $oProduct : FALSE;
            }
        }
        
        return ($this->_oFirstActiveVariant != NULL) ? $this->_oFirstActiveVariant : FALSE;
	}
    
    
    /**
     * Clean attribute titles for export.
     *
     * @param string $sTitle Raw title.
     * @return string
     */
    private function _cleanAttributeTitle($sTitle) { 
        if (
            ($this->_aCleanAttributeTitleSearchKeys == NULL)
            || ($this->_aCleanAttributeTitleReplaceKeys == NULL)
        ) {
            $this->_aCleanAttributeTitleSearchKeys = explode(',', Registry::getConfig()->getConfigParam('sWmdkFFExportRemovePrefixAttributes'));
            $this->_aCleanAttributeTitleReplaceKeys = array_fill(0, count($this->_aCleanAttributeTitleSearchKeys), '');
        }        
        
        return trim( str_replace($this->_aCleanAttributeTitleSearchKeys, $this->_aCleanAttributeTitleReplaceKeys, $sTitle) );
    }
    
    
    /**
     * Translate a localized field value for the current language.
     *
     * @param object $oObject Source object with language fields.
     * @param string $sKey Field key.
     * @return string
     */
    private function _translateString($oObject, $sKey) {
        /* HACK (Ticket: #33333) */
        $sDbTableFieldName = $sKey . $this->_sLanguageSuffix;
        $aDbKeys = explode('__', $sDbTableFieldName);

        // LOAD TRANSLATION
        $sQuery = 'SELECT `' . $aDbKeys[1] . '` FROM `' . $aDbKeys[0] . '` WHERE OXID = "' . $oObject->{$aDbKeys[0] . '__oxid'}->value . '" LIMIT 1;';  
        $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(FALSE)->select($sQuery);

        if ($oResult != FALSE && $oResult->count() > 0) {
            $sTranslatedString = $oResult->fields[$aDbKeys[1]];
        }            
        /* HACK (Ticket: #33333) */

        return (trim($sTranslatedString) != '') ? $sTranslatedString : $oObject->{$sKey}->value;
    }
    
    
    /**
     * Translate attribute values for the current language.
     *
     * @param string $sAttrId Attribute ID.
     * @param bool $bVariant Whether to use variant values.
     * @return string
     */
    private function _translateAttributeValue($sAttrId, $bVariant = FALSE) {
        $sTranslatedString = '';
        $sObjectId = ($bVariant) ? $this->_oParent->oxarticles__oxid->value : $this->_oProduct->oxarticles__oxid->value;
        
        // LOAD TRANSLATION
        $sKey = 'oxvalue' . $this->_sLanguageSuffix;
        
        $sQuery = 'SELECT `' . $sKey . '` FROM `oxobject2attribute` WHERE OXATTRID = "' . $sAttrId . '" AND OXOBJECTID = "' . $sObjectId . '" LIMIT 1;';  
        $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(FALSE)->select($sQuery);

        if ($oResult != FALSE && $oResult->count() > 0) {
            $sTranslatedString = $oResult->fields[$sKey];
        }

        return ( (trim($sTranslatedString) != '') || !$bVariant) ? trim($sTranslatedString) : $this->_translateAttributeValue($sAttrId);
    }
    
    
    /**
     * Escape a string for CSV output.
     *
     * @param string $sString Raw value.
     * @return string
     */
    private function _excapeString($sString) {
        return str_replace(array(
            '"',
        ), array (
            '\"',
        ), $sString);
    }
    
    
    /**
     * Format a price with a fixed number of decimals.
     *
     * @param float $dPrice Price value.
     * @param int $iDecimals Number of decimals.
     * @return string
     */
    private function _formatPrice($dPrice, $iDecimals = 2) {
        if ($dPrice > 0) {
            return number_format($dPrice, $iDecimals, '.' , '');
        }
        
        return '0';
    }
    
    
    /**
     * Remove HTML tags from a string for export.
     *
     * @param string $sHtmlText Input HTML.
     * @return string
     */
    private function _removeHtml($sHtmlText) {
        return strip_tags($sHtmlText, Registry::getConfig()->getConfigParam('sWmdkFFQueueAllowableTags'));
    }
    
    
    /**
     * Prepare the SQL update statement for queue updates.
     */
    private function _prepareUpdateQuery() {
        if (
            count($this->_aUpdateData) > 0
        ) {
            $aSetFragments = array();
            $aParams = array();

            foreach ($this->_aUpdateData as $sAttribute => $sValue) {
                $aSetFragments[] = $sAttribute . ' = ?';
                $aParams[] = $sValue;
            }

            $aParams[] = $this->_sOxid;
            $aParams[] = $this->_sChannel;
            $aParams[] = (int) $this->_iShopId;
            $aParams[] = (int) $this->_iLang;

            $this->_aPreparedUpdateQueries[] = array(
                'sql' => 'UPDATE IGNORE
                    `wmdk_ff_export_queue` 
                SET 
                    ' . implode(', ', $aSetFragments) . '
                WHERE
                    (`OXID` = ?)
                    AND (`Channel` = ?)
                    AND (`OXSHOPID` = ?)
                    AND (`LANG` = ?);',
                'params' => $aParams,
            );
        }
    }
    
    
    /**
     * Execute queued update statements.
     */
    private function _saveQueueData() {
        if (
            count($this->_aPreparedUpdateQueries) > 0
        ) {
            try {
                $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
                $iBatchSize = 100;
                $iCount = count($this->_aPreparedUpdateQueries);

                for ($i = 0; $i < $iCount; $i += $iBatchSize) {
                    $aBatch = array_slice($this->_aPreparedUpdateQueries, $i, $iBatchSize);

                    foreach ($aBatch as $aQueryData) {
                        $oDb->execute($aQueryData['sql'], $aQueryData['params']);
                    }
                }
                
            } catch (Exception $oException) {
                // ERROR
                $this->_aResponse['system_errors'][] = 'ERROR: ' . $oException->getMessage();
            }
        }
    }
    
    
    /**
     * Log the queue run to file if configured.
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
     * Diagnostic method for test runs.
     */
    public function test() {
        $bIsVariant = FALSE;
        
        // PARAMS
        $sOxid = Registry::getConfig()->getRequestParameter('oxid');
        $iLang = Registry::getConfig()->getRequestParameter('lang');
        
        // LOAD Product
        $oProduct = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
        $oProduct->loadInLang($iLang, $sOxid);

        // LOAD Parent
        if ($oProduct->oxarticles__oxparentid->value != '') {
            $bIsVariant = TRUE;
            $oParent = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
            $oParent->loadInLang($iLang, $oProduct->oxarticles__oxparentid->value);
        }
        
        $oArticle = ($bIsVariant) ? $oParent : $oProduct;
        
        /* --------------------- START TEST --------------------- */
                
        $oUtilsUrl = Registry::getUtilsUrl();
        
        if (Registry::getUtils()->seoIsActive()) {
            $sDeeplink = $oUtilsUrl->prepareCanonicalUrl($oArticle->getBaseSeoLink($iLang, TRUE));
        } else {
            $sDeeplink = $oUtilsUrl->prepareCanonicalUrl($oArticle->getBaseStdLink($iLang));
        }
        
        echo strtolower($sDeeplink);
        exit;
    }

}
