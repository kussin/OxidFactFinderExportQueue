<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Controller;

/**
 * HTTP entry point `index.php?cl=wmdkffexport_flour` — Write the flour POS partner feed.
 *
 * Production schedule: every 2 hours. CLI equivalent: `oe-console wmdkffexport:export:flour --channel=... --flour-id=...`.
 */
class FlourController extends AbstractLegacyViewController
{
    protected string $viewClass = 'wmdkffexport_flour';
    protected string $viewFile = 'views/wmdkffexport_flour.php';
}
