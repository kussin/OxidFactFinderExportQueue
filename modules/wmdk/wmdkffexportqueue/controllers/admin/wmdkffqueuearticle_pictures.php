<?php


class wmdkFfQueueArticle_Pictures extends wmdkFfQueueArticle_Pictures_parent
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