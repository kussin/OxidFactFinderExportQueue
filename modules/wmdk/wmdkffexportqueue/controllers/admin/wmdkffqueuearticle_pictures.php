<?php


use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

/**
 * Admin controller extension for picture updates with queue integration.
 */
class wmdkFfQueueArticle_Pictures extends wmdkFfQueueArticle_Pictures_parent
{
    use QueueArticleSaveTrait;

    /**
     * Save picture changes and update the export queue.
     */
    public function save()
    {
        // Persist picture changes first.
        parent::save();

        // Queue the updated article for export processing.
        $this->saveQueueArticleFromRequest();
    }
}
