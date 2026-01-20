<?php


use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

/**
 * Admin controller extension for extended article data with queue updates.
 */
class wmdkFfQueueArticle_Extend extends wmdkFfQueueArticle_Extend_parent
{
    use QueueArticleSaveTrait;

    /**
     * Save extended article data and update the export queue.
     */
    public function save()
    {
        // Persist extended data changes first.
        parent::save();

        // Queue the updated article for export processing.
        $this->saveQueueArticleFromRequest();
    }
}
