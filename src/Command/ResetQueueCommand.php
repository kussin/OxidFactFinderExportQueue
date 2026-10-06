<?php

namespace Wmdk\FactFinderQueue\Command;

class ResetQueueCommand extends AbstractLegacyViewCommand
{
    protected string $commandName = 'wmdkffexport:cron:reset';
    protected string $viewClass = 'wmdkffexport_reset';
    protected string $viewFile = 'views/wmdkffexport_reset.php';
    protected string $description = 'Resets FACT Finder export queue data.';

    public function __construct()
    {
        parent::__construct();
    }
}
