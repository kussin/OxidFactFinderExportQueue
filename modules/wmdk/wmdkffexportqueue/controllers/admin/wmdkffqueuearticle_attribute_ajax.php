<?php

use \OxidEsales\Eshop\Core\Registry;

class wmdkffqueuearticle_attribute_ajax extends wmdkffqueuearticle_attribute_ajax_parent
{

    public function saveColorMapping()
    {
        $oConfig           = Registry::getConfig();
        $sArticleOxid      = (string) $oConfig->getRequestParameter('oxid');
        $sMappingValue     = (string) $oConfig->getRequestParameter('colormapping_value');

        if (!$sArticleOxid) {
            return;
        }

        $oArticle = oxNew(\OxidEsales\Eshop\Application\Model\Article::class);
        if (!$oArticle->load($sArticleOxid)) {
            return; // article not found
        }

        if ($oArticle->isDerived()) {
            return;
        }

        $oArticle->oxarticles__wmdkvarselectmapping = new \OxidEsales\Eshop\Core\Field($sMappingValue);
        $oArticle->save();

        $this->resetContentCache();

        return;
    }

}