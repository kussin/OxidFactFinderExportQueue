<?php


use OxidEsales\Eshop\Core\Registry;

class wmdkFfQueueArticle_Seo extends wmdkFfQueueArticle_Seo_parent
{
    /**
     * Saves changes of article parameters.
     */
    public function save()
    {
        parent::save();
        
        // ACTIVE OXID
        wmdkffexport_helper::saveArticle(Registry::getConfig()->getRequestParameter('oxid'));
    }
}