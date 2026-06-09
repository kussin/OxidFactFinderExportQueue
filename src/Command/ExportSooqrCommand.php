<?php

namespace Wmdk\FactFinderQueue\Command;

class ExportSooqrCommand extends AbstractLegacyViewCommand
{
    protected string $commandName = 'wmdkffexport:export:sooqr';
    protected string $viewClass = 'wmdkffexport_sooqr';
    protected string $viewFile = 'views/wmdkffexport_sooqr.php';
    protected string $description = 'Exports Spotler/Sooqr product data from the queue.';
    protected bool $requiresChannel = true;
    protected bool $requiresShopContext = true;

    public function __construct()
    {
        parent::__construct();
    }
}
