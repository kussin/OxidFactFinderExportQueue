<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Controller;

/**
 * HTTP entry point `index.php?cl=wmdkffexport_ts` — Import Trusted Shops product reviews onto articles.
 *
 * Production schedule: rarely / on demand. CLI equivalent: `oe-console wmdkffexport:import:trusted-shops --channel=...`.
 */
class TrustedShopsController extends AbstractLegacyViewController
{
    protected string $viewClass = 'wmdkffexport_ts';
    protected string $viewFile = 'views/wmdkffexport_ts.php';
}
