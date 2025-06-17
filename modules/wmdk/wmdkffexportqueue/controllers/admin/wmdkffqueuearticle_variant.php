<?php


use OxidEsales\Eshop\Core\Registry;

class wmdkFfQueueArticle_Variant extends wmdkFfQueueArticle_Variant_parent
{
    /**
     * Saves changes of article parameters.
     */
    public function savevariants()
    {
        parent::savevariants();
        
        // ACTIVE OXID
        wmdkffexport_helper::saveArticle(Registry::getConfig()->getRequestParameter('oxid'));
    }

    public function getMappingOptions()
    {
        return Registry::getConfig()->getConfigParam('aWmdkFFClonedAttributeOxvarselectMapping');
    }
}