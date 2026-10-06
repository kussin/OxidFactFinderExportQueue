<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Controller;

/**
 * HTTP entry point `index.php?cl=wmdkffexport_queue` — Process pending FACT-Finder export-queue entries.
 *
 * Production schedule: every 2 minutes. CLI equivalent: `oe-console wmdkffexport:cron:queue`.
 */
class QueueController extends AbstractLegacyViewController
{
    protected string $viewClass = 'wmdkffexport_queue';
    protected string $viewFile = 'views/wmdkffexport_queue.php';
}
