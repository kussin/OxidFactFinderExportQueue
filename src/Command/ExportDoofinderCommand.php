<?php

namespace Wmdk\FactFinderQueue\Command;

class ExportDoofinderCommand extends AbstractLegacyViewCommand
{
    protected string $commandName = 'wmdkffexport:export:doofinder';
    protected string $viewClass = 'wmdkffexport_doofinder';
    protected string $viewFile = 'views/wmdkffexport_doofinder.php';
    protected string $description = 'Exports Doofinder product data from the queue.';
    protected bool $requiresChannel = true;
    protected bool $requiresShopContext = true;

    public function __construct()
    {
        parent::__construct();
    }
}
