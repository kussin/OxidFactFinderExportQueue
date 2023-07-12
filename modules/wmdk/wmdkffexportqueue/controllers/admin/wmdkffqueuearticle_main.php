<?php


class wmdkFfQueueArticle_Main extends wmdkFfQueueArticle_Main_parent
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