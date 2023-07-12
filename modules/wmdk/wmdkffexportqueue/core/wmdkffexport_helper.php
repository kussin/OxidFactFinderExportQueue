<?php

/**
 * Class wmdkffexport_helper
 */
class wmdkffexport_helper
{
    
    public function saveArticle($sOxid) {
        $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
        $oArticle->load($sOxid);
        
        self::touchArticle($oArticle->oxarticles__oxid->value, (int) $oArticle->oxarticles__oxactive->value);
        
        if ((int) $oArticle->oxarticles__oxvarcount->value > 0) {
            self::touchVariants($oArticle->oxarticles__oxid->value);
        }
    }
    
    
    public function touchArticle($sOxid, $iActive = 1) {
        
        // GET CHANNEL LIST
        $aChannelList = self::getChannelList();
        
        foreach($aChannelList as $aChannel) {            
            if (self::isInQueue($sOxid, $aChannel['code'], $aChannel['shop_id'], $aChannel['lang_id'])) {
                // UPDATE
                self::updateArticle($sOxid, $aChannel['code'], $aChannel['shop_id'], $aChannel['lang_id'], $iActive);

            } else {
                // INSERT
                self::insertArticle($sOxid, $aChannel['code'], $aChannel['shop_id'], $aChannel['lang_id']);

            }
        }
    }
    
    
    public function touchVariants($sOxid) {
        // LOAD VARIANTS
        $sQuery = 'SELECT OXID, OXACTIVE FROM `oxarticles` WHERE OXPARENTID = "' . $sOxid . '" ORDER BY OXVARSELECT ASC;';  
        $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(FALSE)->select($sQuery);

        if ($oResult != FALSE && $oResult->count() > 0) {

            while (!$oResult->EOF) {
                self::touchArticle($oResult->fields[0], (int) $oResult->fields[1]);

                // NEXT
                $oResult->fetchRow();
            }
        }
    }
    

    /**
     * Saves changes of article parameters.
     */
    public function getChannelList()
    {
        $aChannelList = array();

        $aChannels = explode(',', oxRegistry::getConfig()->getConfigParam('sWmdkFFGeneralChannelList'));

        foreach ($aChannels as $sChannel) {
            $aParams = explode('::', $sChannel);

            $aChannelList[] = array(
                'code' => $aParams[0],
                'shop_id' => (int) $aParams[1],
                'lang_id' => (int) $aParams[2],
            );
        }
        
        return $aChannelList;
    }
    
    
    private function isInQueue($sOxid, $sChannel, $iShopId = 1, $iLang = 0) {
        $sQuery = 'SELECT 
            count(*)
        FROM 
            `wmdk_ff_export_queue`
        WHERE
            (`OXID` = "' . $sOxid . '")
            AND (`Channel` = "' . $sChannel . '")
            AND (`OXSHOPID` = "' . $iShopId . '")
            AND (`LANG` = "' . $iLang . '");';
        
        $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->select($sQuery);
        
        if ($oResult != FALSE && $oResult->count() > 0) {
            return ((int) $oResult->fields[0] > 0) ? TRUE : FALSE;
        }
        
        return FALSE;
    }
    
    
    private function insertArticle($sOxid, $sChannel, $iShopId = 1, $iLang = 0) {
        $sQuery = 'INSERT INTO 
            `wmdk_ff_export_queue` 
        ( 
            `OXID`,
            `Channel`,
            `OXSHOPID`,
            `LANG`,
            `LASTSYNC`,
            `ProcessIp`,  
            `OXTIMESTAMP`,  
            `OXACTIVE`         
        ) 
        VALUES
        (
            "' . $sOxid . '",
            "' . $sChannel . '",
            "' . $iShopId . '",
            "' . $iLang . '",
            "0000-00-00 00:00:00",
            "' . self::getClientIp() . '",
            "0000-00-00 00:00:00",
            "1"
        );';
        
        // UPDATE oxarticles.WMDK_FFQUEUE   
        $sQuery .= 'UPDATE
            oxarticles
        SET
            WMDK_FFQUEUE = "1"
        WHERE
            OXID = "' . $sOxid . '";';
        
        \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
    }
    
    
    private function updateArticle($sOxid, $sChannel, $iShopId = 1, $iLang = 0, $iActive = 1) {
        $sQuery = 'UPDATE 
            `wmdk_ff_export_queue` 
        SET 
            `LASTSYNC` = "0000-00-00 00:00:00",
            `ProcessIp` = "' . self::getClientIp() . '",
            `OXTIMESTAMP` = "0000-00-00 00:00:00",
            `OXACTIVE` = "' . $iActive . '"
        WHERE 
            (`OXID` = "' . $sOxid . '")
            AND (`Channel` = "' . $sChannel . '")
            AND (`OXSHOPID` = "' . $iShopId . '")
            AND (`LANG` = "' . $iLang . '");';
        
        \OxidEsales\Eshop\Core\DatabaseProvider::getDb()->execute($sQuery);
    }
    
    
    public function getClientIp($sIp = FALSE) {
        $sClientIp = NULL;
            
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $sClientIp = $_SERVER['HTTP_CLIENT_IP'];

        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $sClientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];

        } else {
            $sClientIp = $_SERVER['REMOTE_ADDR'];
        }
        
        return ($sIp != FALSE) ? $sIp : $sClientIp;
    }
    
}