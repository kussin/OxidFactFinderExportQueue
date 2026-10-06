<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Controller;

/**
 * HTTP entry point `index.php?cl=wmdkffexport_export` — Dump the queue into the pipe-delimited FACT-Finder CSV.
 *
 * Production schedule: twice every 30 minutes, per channel. CLI equivalent: `oe-console wmdkffexport:export:factfinder --channel=... --lang=...`.
 */
class ExportController extends AbstractLegacyViewController
{
    protected string $viewClass = 'wmdkffexport_export';
    protected string $viewFile = 'views/wmdkffexport_export.php';
}
