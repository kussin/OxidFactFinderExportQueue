<?php


use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

class wmdkFfQueueArticle_Extend extends wmdkFfQueueArticle_Extend_parent
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
