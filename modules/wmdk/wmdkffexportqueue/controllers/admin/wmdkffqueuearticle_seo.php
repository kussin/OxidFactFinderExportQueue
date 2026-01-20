<?php


use Wmdk\FactFinderQueue\Traits\QueueArticleSaveTrait;

class wmdkFfQueueArticle_Seo extends wmdkFfQueueArticle_Seo_parent
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
