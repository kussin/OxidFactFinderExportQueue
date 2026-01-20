<?php


use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

/**
 * Admin controller extension for article files with queue updates.
 */
class wmdkFfQueueArticle_Files extends wmdkFfQueueArticle_Files_parent
{
    use QueueArticleSaveTrait;

    /**
     * Save file changes and update the export queue.
     */
    public function save()
    {
        // Persist file changes first.
        parent::save();

        // Queue the updated article for export processing.
        $this->saveQueueArticleFromRequest();
    }
}
