<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Controller;

/**
 * HTTP entry point `index.php?cl=wmdkffexport_doofinder` — Write the Doofinder product feed.
 *
 * Production schedule: not scheduled in production. CLI equivalent: `oe-console wmdkffexport:export:doofinder --channel=...`.
 */
class DoofinderController extends AbstractLegacyViewController
{
    protected string $viewClass = 'wmdkffexport_doofinder';
    protected string $viewFile = 'views/wmdkffexport_doofinder.php';
}
