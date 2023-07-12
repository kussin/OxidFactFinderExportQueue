<?php


class wmdkFfQueueArticle_Seo extends wmdkFfQueueArticle_Seo_parent
{
    /**
     * Saves changes of article parameters.
     */
    public function save()
    {
        parent::save();
        
        // ACTIVE OXID
        wmdkffexport_helper::saveArticle(oxRegistry::getConfig()->getRequestParameter('oxid'));
    }
}