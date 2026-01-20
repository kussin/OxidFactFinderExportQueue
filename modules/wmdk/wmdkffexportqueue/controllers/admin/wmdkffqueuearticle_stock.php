<?php


use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

class wmdkFfQueueArticle_Stock extends wmdkFfQueueArticle_Stock_parent
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
