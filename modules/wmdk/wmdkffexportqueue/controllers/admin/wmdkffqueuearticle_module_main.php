<?php

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;

class wmdkffqueuearticle_module_main extends wmdkffqueuearticle_module_main_parent
{

    public function render()
    {
        $sTpl   = parent::render();

        $oModule = $this->_aViewData['oModule'] ?? null;
        if (!$oModule instanceof \OxidEsales\EshopCommunity\Core\Module\Module || $oModule->getId() !== 'wmdkffexportqueue') {
            return $sTpl;
        }

        $iCount = $this->getUnprocessedArticles();
        $aData = $oModule->getModuleData();
        $oLang    = Registry::getLang();
        $iTplLang = $oLang->getTplLanguage();
        $sAbbr    = $oLang->getLanguageAbbr($iTplLang);

        if (is_array($aData['description'])) {
            if (array_key_exists($sAbbr, $aData['description'])) {
                $sWriteKey = $sAbbr;
            } else {
                $aKeys     = array_keys($aData['description']);
                $sWriteKey = reset($aKeys);
            }
            $sCurrent = (string) ($aData['description'][$sWriteKey] ?? '');
        } else {
            $sWriteKey = null;
            $sCurrent  = (string) $aData['description'];
        }

        $sPrefixHtml = '<p><strong>Unprocessed articles:</strong> <strong><span style="color:#DF0000;">' . (int) $iCount . '</span></strong></p>';
        $sUpdated    = $sPrefixHtml . $sCurrent;

        if (is_array($aData['description'])) {
            $aData['description'][$sWriteKey] = $sUpdated;
        } else {
            $aData['description'] = $sUpdated;
        }

        $oModule->setModuleData($aData);
        $this->_aViewData['oModule'] = $oModule;

        return $sTpl;
    }

    protected function getUnprocessedArticles(): int
    {
        $sSql = "
            SELECT COUNT(*)
            FROM wmdk_ff_export_queue
            WHERE `OXTIMESTAMP` = '0000-00-00 00:00:00'
              AND `Stock` > 0
              AND `OXACTIVE` = 1
        ";

        $oDb = DatabaseProvider::getDb();
        return (int) $oDb->getOne($sSql);
    }
}
