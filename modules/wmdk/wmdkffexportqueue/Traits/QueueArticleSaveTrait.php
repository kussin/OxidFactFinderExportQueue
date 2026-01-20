<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

trait QueueArticleSaveTrait
{
    protected function saveQueueArticleFromRequest(): void
    {
        $sOxid = Registry::getConfig()->getRequestParameter('oxid');

        if ($sOxid !== null && $sOxid !== '') {
            \wmdkffexport_helper::saveArticle((string) $sOxid);
        }
    }
}
