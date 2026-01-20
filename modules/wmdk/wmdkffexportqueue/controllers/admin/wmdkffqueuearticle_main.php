<?php


use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

/**
 * Admin controller extension for article main data with queue updates.
 */
class wmdkFfQueueArticle_Main extends wmdkFfQueueArticle_Main_parent
{
    use QueueArticleSaveTrait;

    /**
     * Save article changes and update the export queue.
     */
    public function save()
    {
        // Persist the base article changes first.
        parent::save();

        // Queue the updated article for export processing.
        $this->saveQueueArticleFromRequest();
    }
}
