<?php

namespace Wmdk\FactFinderQueue\Command;

class ExportFactFinderCommand extends AbstractLegacyViewCommand
{
    protected string $commandName = 'wmdkffexport:export:factfinder';
    protected string $viewClass = 'wmdkffexport_export';
    protected string $viewFile = 'views/wmdkffexport_export.php';
    protected string $description = 'Exports FACT Finder product data from the queue.';
    protected bool $requiresChannel = true;
    protected bool $requiresShopContext = true;

    public function __construct()
    {
        parent::__construct();
    }
}
