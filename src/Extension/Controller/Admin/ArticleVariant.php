<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Extension\Controller\Admin;

use OxidEsales\Eshop\Core\Registry;

class ArticleVariant extends ArticleVariant_parent
{
    use QueueArticleMarkerTrait;

    public function savevariants()
    {
        parent::savevariants();
        $this->markCurrentArticleForExportQueue();
    }

    public function getMappingOptions()
    {
        return Registry::getConfig()->getConfigParam('aWmdkFFClonedAttributeOxvarselectMapping');
    }
}
