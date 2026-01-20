<?php


use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

/**
 * Admin controller extension for variant management with queue updates.
 */
class wmdkFfQueueArticle_Variant extends wmdkFfQueueArticle_Variant_parent
{
    use QueueArticleSaveTrait;

    /**
     * Save variant changes and update the export queue.
     */
    public function savevariants()
    {
        // Persist variant changes first.
        parent::savevariants();

        // Queue the updated article for export processing.
        $this->saveQueueArticleFromRequest();
    }

    /**
     * Return the variant mapping options from configuration.
     *
     * @return array
     */
    public function getMappingOptions()
    {
        return Registry::getConfig()->getConfigParam('aWmdkFFClonedAttributeOxvarselectMapping');
    }
}
