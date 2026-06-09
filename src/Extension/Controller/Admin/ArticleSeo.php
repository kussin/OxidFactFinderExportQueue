<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Extension\Controller\Admin;

class ArticleSeo extends ArticleSeo_parent
{
    use QueueArticleMarkerTrait;

    public function save()
    {
        parent::save();
        $this->markCurrentArticleForExportQueue();
    }
}
