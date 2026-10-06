<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Controller;

/**
 * HTTP entry point `index.php?cl=wmdkffexport_sooqr` — Write the Spotler/Sooqr product feed.
 *
 * Production schedule: not scheduled in production. CLI equivalent: `oe-console wmdkffexport:export:sooqr --channel=...`.
 */
class SooqrController extends AbstractLegacyViewController
{
    protected string $viewClass = 'wmdkffexport_sooqr';
    protected string $viewFile = 'views/wmdkffexport_sooqr.php';
}
