<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Extension\Controller\Admin;

use Kussin\OxidBase\Service\AdminUrlBuilder;
use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Service\ArticleFamilyOxidResolver;

class ArticleMain extends ArticleMain_parent
{
    use QueueArticleMarkerTrait;

    public function save()
    {
        parent::save();
        $this->markCurrentArticleForExportQueue();
    }

    public function getFactFinderMonitorUrl(): string
    {
        $articleId = Registry::getRequest()->getRequestParameter('oxid');
        $articleIds = (new ArticleFamilyOxidResolver())->resolve(
            is_scalar($articleId) ? (string) $articleId : ''
        );

        if ($articleIds === []) {
            return '';
        }

        return (new AdminUrlBuilder())->build('wmdkffexport_monitor', [
            'monitorFilter' => [
                'OXID' => implode(',', $articleIds),
            ],
            'monitorPage' => 1,
        ]);
    }
}
