<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

/**
 * Saves queue entries from the current request context.
 */
trait QueueArticleSaveTrait
{
    /**
     * Persist the queue entry for the current request article.
     */
    protected function saveQueueArticleFromRequest(): void
    {
        $sOxid = Registry::getConfig()->getRequestParameter('oxid');

        if ($sOxid !== null && $sOxid !== '') {
            \wmdkffexport_helper::saveArticle((string) $sOxid);
        }
    }
}
