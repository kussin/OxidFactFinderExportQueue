<?php

use Wmdk\FactFinderQueue\Traits\ConverterTrait;

/**
 * Class wmdkffexport_queue
 */
class wmdkffexport_queue extends oxubase
{
    use ConverterTrait;

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
    
    protected $_sProcessIp = NULL;
    
    protected $_sTemplate = 'wmdkffexport_queue.tpl';
    
    protected $_sCronjobFlagname = NULL;

    
    /**
     * @return string
     */
    public function render() {        
        if (!$this->_hasCronjobFlag()) {

            // SET FLAG
            $this->_setCronjobFlag();

            // Settings
            $iQueueLimit = (int) oxRegistry::getConfig()->getConfigParam('sWmdkFFQueueLimit');
            $iArticleStatus = oxRegistry::getConfig()->getConfigParam('iArticleStatus');
            $iArticleMinStock = (int) oxRegistry::getConfig()->getConfigParam('iArticleMinStock');

            // LOAD PRODUCTS
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
                (`wmdk_ff_export_queue`.`OXID` = `oxarticles`.`OXID`)
                ' . ( ($iArticleStatus != '') ? 'AND (`wmdk_ff_export_queue`.`OXACTIVE` = ' . $iArticleStatus . ')' : '' ) . '
				AND (`wmdk_ff_export_queue`.`Stock` >= ' . $iArticleMinStock . ')
            ORDER BY 
                `wmdk_ff_export_queue`.`LASTSYNC` ASC, 
                `wmdk_ff_export_queue`.`Stock` DESC
            LIMIT ' . $iQueueLimit . ';';
            $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(FALSE)->select($sQuery);

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
            $this->_aUpdateData['MSRP'] = $this->_getMsrp();
            $this->_aUpdateData['BasePrice'] = $this->_getBasePrice();
            
            $this->_aUpdateData['Stock'] = $this->_getStock();
            
            $this->_aUpdateData['Description'] = $this->_getDescription();
            
            $this->_aUpdateData['Deeplink'] = $this->_getDeeplink();
            
            $this->_aUpdateData['Marke'] = $sManufacturer;

            $this->_aUpdateData['CategoryPath'] = $this->_getCategoryPath();
            
            $this->_aUpdateData['Attributes'] = $this->_getAttributes();
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
    
    
    private function _getHidden() {
        $oArticle = ($this->_bIsVariant) ? $this->_oParent : $this->_oProduct;
        
        return $oArticle->oxarticles__oxhidden->value;
    }
    
    
    private function _getHasFromPrice() {
        $oArticle = ($this->_bIsVariant) ? $this->_oParent : $this->_oProduct;
        
        $dVarMinPrice = (double) $oArticle->oxarticles__oxvarminprice->value;
        $dVarMaxPrice = (double) $oArticle->oxarticles__oxvarmaxprice->value;
        
        return ($dVarMinPrice < $dVarMaxPrice) ? 1 : '';
    }
    
    
    private function _getPrice() {
        if ($this->_bIsParent || $this->_bIsVariant) {
            $oFirstActiveVariant = ($this->_bIsVariant) ? $this->_getFirstActiveVariant($this->_oProduct->oxarticles__oxparentid->value) : $this->_getFirstActiveVariant();
            
            if ($oFirstActiveVariant != FALSE) {
                return (double) $oFirstActiveVariant->oxarticles__oxprice->value;
            }
            
            return (double) $this->_oProduct->oxarticles__oxvarminprice->value;
        }
        
		return (double) $this->_oProduct->oxarticles__oxprice->value;
    }
	
    
    private function _getMsrp() {
        if ($this->_bIsParent || $this->_bIsVariant) {
            $oFirstActiveVariant = ($this->_bIsVariant) ? $this->_getFirstActiveVariant($this->_oProduct->oxarticles__oxparentid->value) : $this->_getFirstActiveVariant();
            
            if ($oFirstActiveVariant != FALSE) {
                return (double) $oFirstActiveVariant->oxarticles__oxtprice->value;
            }
        }
        
		return (double) $this->_oProduct->oxarticles__oxtprice->value;
    }
	
    
    private function _getBasePrice($sCurrenySign = '€') {
        $oUnitPrice = $this->_oProduct->getUnitPrice();

        if (is_object($oUnitPrice)) {
            $dUnitPrice = (double) $oUnitPrice->getPrice();
            $sUnitName = $this->_oProduct->getUnitName();
            
            return number_format($dUnitPrice, 2, ',', '.') . $sCurrenySign . '/' . $sUnitName;
        }
        
		return '';
    }
	
    
    private function _getStock() {
        return  ($this->_bIsParent) ? $this->_oProduct->oxarticles__oxvarstock->value : $this->_oProduct->oxarticles__oxstock->value;
    }
    
    
    private function _getDescription() {
        $sDescription = ($this->_bIsVariant) ? $this->_oParent->getLongDescription() : $this->_oProduct->getLongDescription();
        
        return $this->_removeHtml($sDescription);
    }
    
    private function _getBaseUrl() {
//        if ($this->_sBaseUrl == NULL) {
//            $aBaseUrl = explode('?', oxRegistry::getConfig()->getConfigParam('sShopURL'));
//            $this->_sBaseUrl = trim($aBaseUrl[0]);
//        }
//
//        return $this->_sBaseUrl;
        return '';
    }    
    
    private function _getDeeplink() {
        $oSeoEncoderArticle = oxNew(\OxidEsales\Eshop\Application\Model\SeoEncoderArticle::class);
        
        $sManufacturerLink = trim($oSeoEncoderArticle->getArticleManufacturerUri($this->_oProduct, $this->_iLang, TRUE));
        
        return strtolower($this->_getBaseUrl() . $sManufacturerLink);
    }
    
    
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
            
            $bSkip = strpos($aCategoryPath, trim(oxRegistry::getConfig()->getConfigParam('sWmdkFFExportRemovePrefixCategories')));
            
            if (
                ($bSkip == FALSE) 
                && (!is_int($bSkip)) 
            ) {
                $aCategoryPathes[] = $aCategoryPath;
                
            }
        }
        
        return implode(oxRegistry::getConfig()->getConfigParam('sWmdkFFQueueAttributeGlue'), $aCategoryPathes);
    }
    
    
    private function _getAttributes() {
        $aAttributes = array();
        
        // VARIANTS
        if ($this->_oProduct->oxarticles__oxvarselect->value != '') {
            $aAttributes[] = $this->_getVariantsAttributes();
        }
        
        // ATTRIBUTES
        $aCsvAttributes = explode(',', oxRegistry::getConfig()->getConfigParam('sWmdkFFExportCsvAttributes'));

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
            $oLang = oxRegistry::getLang();

            $sSaleLabel = $oLang->translateString( 'SALE', $this->_iLang);
            $sSaleValue = $oLang->translateString( 'REDUCED_ARTICLES', $this->_iLang);

            $aAttributes[] = $sSaleLabel . '=' . $sSaleValue;
        }
        
        return implode(oxRegistry::getConfig()->getConfigParam('sWmdkFFQueueAttributeGlue'), $aAttributes);
    }


    private function _getVariantsAttributes() {
        $aAttributes = array();

        $aVarnames = explode('|', $this->_oProduct->oxarticles__oxvarname->value);
        $aVarselects = explode('|', $this->_oProduct->oxarticles__oxvarselect->value);

        foreach ($aVarnames as $iKey => $sAttribute) {
            $sAttributeName = trim($sAttribute);
            $sAttributeValue = isset($aVarselects[$iKey]) ? html_entity_decode(trim($aVarselects[$iKey]), ENT_QUOTES) : '';

            $aAttributes[] = $sAttributeName . '=' . $this->_converter($sAttributeName, $sAttributeValue);
        }

        return implode(oxRegistry::getConfig()->getConfigParam('sWmdkFFQueueAttributeGlue'), $aAttributes);
    }
    
    
    private function _getNumericalAttributes() {
        return '';
    }
    
    
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
        
        return implode(oxRegistry::getConfig()->getConfigParam('sWmdkFFQueueAttributeGlue'), $aAttributes);
    }
    
    
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
    
    
    private function _hasHasNewFlag($bFalse = 0) {
        $oArticle = ($this->_bIsVariant) ? $this->_oParent : $this->_oProduct;
        
        $iNewUntill = strtotime($oArticle->oxarticles__wmdknewlabel->value);
        
        return ($iNewUntill >= time()) ? 1 : $bFalse;
    }
    
    
    private function _hasHasTopFlag($bFalse = 0) {
        $oArticle = ($this->_bIsVariant) ? $this->_oParent : $this->_oProduct;
        
        $iMinSoldAmount = (int) oxRegistry::getConfig()->getConfigParam('sWmdkFFQueueFlagTopseller');
        
        $iSoldAmount = (int) $oArticle->oxarticles__oxsoldamount->value;
        
        return ($iSoldAmount >= $iMinSoldAmount) ? 1 : $bFalse;
    }
    
    
    private function _getSaleAmount($sSign = '%') {
        $dOxPrice = $this->_getPrice();
        $dOxTPrice = $this->_getMsrp();
        
        if ($dOxPrice < $dOxTPrice) {
            $dDiscount = 100 - ( ($dOxPrice * 100) / $dOxTPrice );
            
            return floor($dDiscount) . $sSign;
        }
        
        return '';
    }
    
    
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

                while (!$oResult->EOF) {
                    $aData = $oResult->getFields();

                    // MULTI-OPTIONS TO ARRAY
                    $aOption = explode('|', $aData['OXVARSELECT']);
                    $sOption = isset($aOption[0]) ? trim($aOption[0]) : $aData['OXVARSELECT'];

                    if (!in_array($sOption, $aDuplicateCheck)) {
                        $aDesktopMarkup[] = '<li' . (((float)$aData['OXSTOCK'] > 0) ? '' : ' class="sold-out"') . '><span>' . $sOption . '</span></li>';
                        $aMobileMarkup[] = '<option>' . $sOption . '</option>';

                        $aDuplicateCheck[] = $sOption;
                    }

                    // NEXT
                    $oResult->fetchRow();
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
    
    
    private function _cleanAttributeTitle($sTitle) { 
        if (
            ($this->_aCleanAttributeTitleSearchKeys == NULL)
            || ($this->_aCleanAttributeTitleReplaceKeys == NULL)
        ) {
            $this->_aCleanAttributeTitleSearchKeys = explode(',', oxRegistry::getConfig()->getConfigParam('sWmdkFFExportRemovePrefixAttributes'));
            $this->_aCleanAttributeTitleReplaceKeys = array_fill(0, count($this->_aCleanAttributeTitleSearchKeys), '');
        }        
        
        return trim( str_replace($this->_aCleanAttributeTitleSearchKeys, $this->_aCleanAttributeTitleReplaceKeys, $sTitle) );
    }
    
    
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
    
    
    private function _excapeString($sString) {
        return str_replace(array(
            '"',
        ), array (
            '\"',
        ), $sString);
    }
    
    
    private function _formatPrice($dPrice, $iDecimals = 2) {
        if ($dPrice > 0) {
            return number_format($dPrice, $iDecimals, '.' , '');
        }
        
        return '0';
    }
    
    
    private function _removeHtml($sHtmlText) {
        return strip_tags($sHtmlText, oxRegistry::getConfig()->getConfigParam('sWmdkFFQueueAllowableTags'));
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
    
    
    private function _getSqlUpdateString() {
        $aAttributes = array();
        
        foreach ($this->_aUpdateData as $sAttribute => $sValue) {
            $aAttributes[] = $sAttribute . '="' . $this->_excapeString($sValue) . '"';
        }
        
        return implode(', ', $aAttributes);
    }
    
    
    private function _prepareUpdateQuery() {
        if (
            count($this->_aUpdateData) > 0
        ) {
            $this->_aPreparedUpdateQueries[] = 'UPDATE IGNORE
                `wmdk_ff_export_queue` 
            SET 
                ' . $this->_getSqlUpdateString() . '
            WHERE
                (`OXID` = "' . $this->_sOxid . '")
                AND (`Channel` = "' . $this->_sChannel . '")
                AND (`OXSHOPID` = "' . $this->_iShopId . '")
                AND (`LANG` = "' . $this->_iLang . '");';
        }
    }
    
    
    private function _saveQueueData() {
        if (
            count($this->_aPreparedUpdateQueries) > 0
        ) {
            try {
                $sQuery = implode("\n", $this->_aPreparedUpdateQueries);
                \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->Execute($sQuery);
                
            } catch (Exception $oException) {
                // ERROR
                $this->_aResponse['system_errors'][] = 'ERROR: ' . $oException->getMessage();
            }
        }
    }
    
    
    private function _hasCronjobFlag($sFlagname = '/tmp/wmdk_ff_cron.flag') {
        $this->_sCronjobFlagname = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . $sFlagname);
        
        return file_exists($this->_sCronjobFlagname);
    }
    
    
    private function _setCronjobFlag() {
        $rFile = fopen($this->_sCronjobFlagname, 'w'); 
        fclose($rFile);
    }
    
    
    private function _removeCronjobFlag() {
        return unlink($this->_sCronjobFlagname);
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
    
    
    public function test() {
        $bIsVariant = FALSE;
        
        // PARAMS
        $sOxid = oxRegistry::getConfig()->getRequestParameter('oxid');
        $iLang = oxRegistry::getConfig()->getRequestParameter('lang');
        
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
                
        $oUtilsUrl = \OxidEsales\Eshop\Core\Registry::getUtilsUrl();
        
        if (\OxidEsales\Eshop\Core\Registry::getUtils()->seoIsActive()) {
            $sDeeplink = $oUtilsUrl->prepareCanonicalUrl($oArticle->getBaseSeoLink($iLang, TRUE));
        } else {
            $sDeeplink = $oUtilsUrl->prepareCanonicalUrl($oArticle->getBaseStdLink($iLang));
        }
        
        echo strtolower($sDeeplink);
        exit;
    }

}