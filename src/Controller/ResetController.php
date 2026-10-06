<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Controller;

/**
 * HTTP entry point `index.php?cl=wmdkffexport_reset` — Re-queue articles, variants and siblings for export.
 *
 * Production schedule: every 3 hours. CLI equivalent: `oe-console wmdkffexport:cron:reset`.
 */
class ResetController extends AbstractLegacyViewController
{
    protected string $viewClass = 'wmdkffexport_reset';
    protected string $viewFile = 'views/wmdkffexport_reset.php';
}
