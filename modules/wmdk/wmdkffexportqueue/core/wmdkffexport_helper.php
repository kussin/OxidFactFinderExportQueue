<?php
declare(strict_types=1);

/**
 * Class wmdkffexport_helper
 */
class wmdkffexport_helper
{
    
    public static function saveArticle(string $sOxid): void
    {
        $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
        $oArticle->load($sOxid);
        
        self::touchArticle($oArticle->oxarticles__oxid->value, (int) $oArticle->oxarticles__oxactive->value);
        
        if ((int) $oArticle->oxarticles__oxvarcount->value > 0) {
            self::touchVariants($oArticle->oxarticles__oxid->value);
        }
    }
    
    
    public static function touchArticle(string $sOxid, int $iActive = 1): void
    {
        
        // GET CHANNEL LIST
        $aChannelList = self::getChannelList();
        
        foreach ($aChannelList as $aChannel) {            
            if (self::isInQueue($sOxid, $aChannel['code'], $aChannel['shop_id'], $aChannel['lang_id'])) {
                // UPDATE
                self::updateArticle($sOxid, $aChannel['code'], $aChannel['shop_id'], $aChannel['lang_id'], $iActive);

            } else {
                // INSERT
                self::insertArticle($sOxid, $aChannel['code'], $aChannel['shop_id'], $aChannel['lang_id']);

            }
        }
    }
    
    
    public static function touchVariants(string $sOxid): void
    {
        // LOAD VARIANTS
        $sQuery = 'SELECT OXID, OXACTIVE FROM `oxarticles` WHERE OXPARENTID = "' . $sOxid . '" ORDER BY OXVARSELECT ASC;';  
        $oResult = \OxidEsales\Eshop\Core\DatabaseProvider::getDb(false)->select($sQuery);

        if ($oResult !== false && $oResult->count() > 0) {

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
    public static function getChannelList(): array
    {
        $aChannelList = array();

        $aChannels = array_filter(
            array_map(
                'trim',
                explode(',', (string) \OxidEsales\Eshop\Core\Registry::getConfig()->getConfigParam('sWmdkFFGeneralChannelList'))
            ),
            static function ($channel): bool {
                return $channel !== '';
            }
        );

        foreach ($aChannels as $sChannel) {
            $aParams = array_pad(explode('::', $sChannel), 3, null);

            if ($aParams[0] === null || $aParams[0] === '') {
                continue;
            }
            $aChannelList[] = array(
                'code' => $aParams[0],
                'shop_id' => (int) ($aParams[1] ?? 0),
                'lang_id' => (int) ($aParams[2] ?? 0),
            );
        }
        
        return $aChannelList;
    }
    
    
    private static function isInQueue(string $sOxid, string $sChannel, int $iShopId = 1, int $iLang = 0): bool
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sQuery = 'SELECT 
            count(*)
        FROM 
            `wmdk_ff_export_queue`
        WHERE
            (`OXID` = ' . $oDb->quote($sOxid) . ')
            AND (`Channel` = ' . $oDb->quote($sChannel) . ')
            AND (`OXSHOPID` = ' . (int) $iShopId . ')
            AND (`LANG` = ' . (int) $iLang . ');';
        
        $oResult = $oDb->select($sQuery);
        
        if ($oResult !== false && $oResult->count() > 0) {
            return ((int) $oResult->fields[0] > 0);
        }
        
        return false;
    }
    
    
    private static function insertArticle(string $sOxid, string $sChannel, int $iShopId = 1, int $iLang = 0): void
    {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
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
            ' . $oDb->quote($sOxid) . ',
            ' . $oDb->quote($sChannel) . ',
            ' . (int) $iShopId . ',
            ' . (int) $iLang . ',
            "0000-00-00 00:00:00",
            ' . $oDb->quote(self::getClientIp()) . ',
            "0000-00-00 00:00:00",
            "1"
        );';
        
        // UPDATE oxarticles.WMDK_FFQUEUE   
        $sQuery .= 'UPDATE
            oxarticles
        SET
            WMDK_FFQUEUE = "1"
        WHERE
            OXID = ' . $oDb->quote($sOxid) . ';';
        
        $oDb->execute($sQuery);
    }
    
    
    private static function updateArticle(
        string $sOxid,
        string $sChannel,
        int $iShopId = 1,
        int $iLang = 0,
        int $iActive = 1
    ): void {
        $oDb = \OxidEsales\Eshop\Core\DatabaseProvider::getDb();
        $sQuery = 'UPDATE 
            `wmdk_ff_export_queue` 
        SET 
            `LASTSYNC` = "0000-00-00 00:00:00",
            `ProcessIp` = ' . $oDb->quote(self::getClientIp()) . ',
            `OXTIMESTAMP` = "0000-00-00 00:00:00",
            `OXACTIVE` = "' . (int) $iActive . '"
        WHERE 
            (`OXID` = ' . $oDb->quote($sOxid) . ')
            AND (`Channel` = ' . $oDb->quote($sChannel) . ')
            AND (`OXSHOPID` = ' . (int) $iShopId . ')
            AND (`LANG` = ' . (int) $iLang . ');';
        
        $oDb->execute($sQuery);
    }
    
    
    public static function getClientIp(?string $sIp = null): string
    {
        if ($sIp !== null) {
            return $sIp;
        }

        $aServer = $_SERVER ?? array();
        $sClientIp = $aServer['HTTP_CLIENT_IP']
            ?? $aServer['HTTP_X_FORWARDED_FOR']
            ?? $aServer['REMOTE_ADDR']
            ?? '';

        if (strpos($sClientIp, ',') !== false) {
            $sClientIp = trim(explode(',', $sClientIp)[0]);
        }
        
        return $sClientIp;
    }
    
}
