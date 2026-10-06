<?php

namespace Wmdk\FactFinderQueue\Command;

class ExportFlourCommand extends AbstractLegacyViewCommand
{
    protected string $commandName = 'wmdkffexport:export:flour';
    protected string $viewClass = 'wmdkffexport_flour';
    protected string $viewFile = 'views/wmdkffexport_flour.php';
    protected string $description = 'Exports flour POS product data from the queue.';
    protected bool $requiresChannel = true;
    protected bool $requiresShopContext = true;
    protected bool $supportsFlourId = true;

    public function __construct()
    {
        parent::__construct();
    }
}
