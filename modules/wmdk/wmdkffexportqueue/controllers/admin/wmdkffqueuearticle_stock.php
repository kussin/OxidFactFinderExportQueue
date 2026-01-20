<?php


use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

/**
 * Admin controller extension for stock updates with queue integration.
 */
class wmdkFfQueueArticle_Stock extends wmdkFfQueueArticle_Stock_parent
{
    use QueueArticleSaveTrait;

    /**
     * Save stock changes and update the export queue.
     */
    public function save()
    {
        // Persist stock changes first.
        parent::save();

        // Queue the updated article for export processing.
        $this->saveQueueArticleFromRequest();
    }
}
