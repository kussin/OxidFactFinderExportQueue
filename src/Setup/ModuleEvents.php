<?php

namespace Wmdk\FactFinderQueue\Setup;

use Wmdk\FactFinderQueue\Service\ExportDirectoryManager;

class ModuleEvents
{
    public static function onActivate(): void
    {
        (new DatabaseInstaller())->install();
        (new ExportDirectoryManager())->ensureDefaultDirectoryTree();
        (new ExportDirectoryManager())->ensureConfiguredExportDirectory();
    }
}
