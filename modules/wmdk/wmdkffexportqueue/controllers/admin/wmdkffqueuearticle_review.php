<?php


use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

/**
 * Admin controller extension for review updates with queue integration.
 */
class wmdkFfQueueArticle_Review extends wmdkFfQueueArticle_Review_parent
{
    use QueueArticleSaveTrait;

    /**
     * Save review changes and update the export queue.
     */
    public function save()
    {
        // Persist review changes first.
        parent::save();

        // Queue the updated article for export processing.
        $this->saveQueueArticleFromRequest();
    }
}
