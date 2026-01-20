<?php


use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

class wmdkFfQueueArticle_Files extends wmdkFfQueueArticle_Files_parent
{
    use QueueArticleSaveTrait;

    /**
     * Saves changes of article parameters.
     */
    public function save()
    {
        parent::save();
        
        // ACTIVE OXID
        $this->saveQueueArticleFromRequest();
    }
}
