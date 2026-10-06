<?php

namespace Wmdk\FactFinderQueue\Command;

class ImportTrustedShopsCommand extends AbstractLegacyViewCommand
{
    protected string $commandName = 'wmdkffexport:import:trusted-shops';
    protected string $viewClass = 'wmdkffexport_ts';
    protected string $viewFile = 'views/wmdkffexport_ts.php';
    protected string $description = 'Imports Trusted Shops product ratings into the export queue.';

    public function __construct()
    {
        parent::__construct();
    }
}
