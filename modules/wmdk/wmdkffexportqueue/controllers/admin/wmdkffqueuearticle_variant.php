<?php


use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

class wmdkFfQueueArticle_Variant extends wmdkFfQueueArticle_Variant_parent
{
    use QueueArticleSaveTrait;

    /**
     * Saves changes of article parameters.
     */
    public function savevariants()
    {
        parent::savevariants();
        
        // ACTIVE OXID
        $this->saveQueueArticleFromRequest();
    }

    public function getMappingOptions()
    {
        return Registry::getConfig()->getConfigParam('aWmdkFFClonedAttributeOxvarselectMapping');
    }
}
