<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Extension\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Service\QueueArticleMarker;

trait QueueArticleMarkerTrait
{
    protected function markCurrentArticleForExportQueue(): void
    {
        (new QueueArticleMarker())->markArticle(
            (string) Registry::getRequest()->getRequestParameter('oxid')
        );
    }
}
