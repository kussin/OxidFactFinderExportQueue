<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Extension\Controller\Admin;

class ArticleFiles extends ArticleFiles_parent
{
    use QueueArticleMarkerTrait;

    public function save()
    {
        parent::save();
        $this->markCurrentArticleForExportQueue();
    }
}
