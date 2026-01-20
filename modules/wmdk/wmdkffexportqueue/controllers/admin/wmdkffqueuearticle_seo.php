<?php


use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

/**
 * Admin controller extension for SEO updates with queue integration.
 */
class wmdkFfQueueArticle_Seo extends wmdkFfQueueArticle_Seo_parent
{
    use QueueArticleSaveTrait;

    /**
     * Save SEO changes and update the export queue.
     */
    public function save()
    {
        // Persist SEO changes first.
        parent::save();

        // Queue the updated article for export processing.
        $this->saveQueueArticleFromRequest();
    }
}
