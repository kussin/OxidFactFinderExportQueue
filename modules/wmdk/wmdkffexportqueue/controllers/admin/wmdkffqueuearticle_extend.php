<?php


class wmdkFfQueueArticle_Extend extends wmdkFfQueueArticle_Extend_parent
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