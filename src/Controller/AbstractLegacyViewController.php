<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Controller;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Service\ExportDirectoryManager;
use Wmdk\FactFinderQueue\Service\LegacyConfigBridge;

/**
 * HTTP surface for the legacy export-queue views (`index.php?cl=wmdkffexport_*`).
 *
 * These jobs became console commands during the OXID 7 port and lost their `cl=`
 * keys. That would have broken the production crontab at cutover: OXID 6 has no
 * CLI entry points, so its scheduler calls these URLs over curl — the queue
 * every 2 minutes, the FACT-Finder export twice every half hour, flour every
 * 2 hours and the reset every 3. The keys are restored here as a compatibility
 * shim; `oe-console wmdkffexport:*` remains the target runtime.
 *
 * The job itself is untouched: this class performs exactly the same steps as
 * Command\AbstractLegacyViewCommand — bridge the module settings into config
 * params, ensure the export directory, load the legacy view class, render it —
 * and then echoes the response instead of writing it to stdout.
 */
abstract class AbstractLegacyViewController extends FrontendController
{
    /** Legacy (global-namespace) view class, e.g. `wmdkffexport_queue`. */
    protected string $viewClass = '';

    /** Path of the file declaring it, relative to the module root. */
    protected string $viewFile = '';

    public function render()
    {
        $this->assertCronAccessOrExit();

        (new LegacyConfigBridge())->apply();
        (new ExportDirectoryManager())->ensureConfiguredExportDirectory();

        $this->loadViewClass();

        $view = \oxNew($this->viewClass);
        $view->render();

        $aResponse = $this->getResponse($view);

        Registry::getUtils()->setHeader('Content-Type: application/json');
        echo json_encode($aResponse);
        exit;
    }

    /**
     * OXID 6 left these endpoints wide open. They rebuild and delete rows in
     * `wmdk_ff_export_queue` and write export files, so the HTTP surface is
     * gated: CLI, an IP in `sWmdkFFDebugCronjobIpList`, or localhost.
     *
     * The allowlist matches the same forwarded-header-aware IP the views log,
     * and is therefore spoofable — it is a guard rail, not authentication. Only
     * the localhost bypass (REMOTE_ADDR) cannot be forged.
     */
    protected function assertCronAccessOrExit(): void
    {
        if (php_sapi_name() === 'cli') {
            return;
        }

        if (in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
            return;
        }

        $sIpList = (string) Registry::getConfig()->getConfigParam('sWmdkFFDebugCronjobIpList');

        if ($sIpList !== ''
            && in_array($this->getProcessIp(), array_map('trim', explode(',', $sIpList)), true)
        ) {
            return;
        }

        Registry::getUtils()->setHeader('HTTP/1.1 403 Forbidden');
        exit;
    }

    private function getProcessIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /**
     * The legacy views are global-namespace classes that composer does not
     * autoload, so they are required on demand — same as the console commands.
     */
    private function loadViewClass(): void
    {
        if (class_exists($this->viewClass, false)) {
            return;
        }

        $sViewPath = dirname(__DIR__, 2) . '/' . $this->viewFile;

        if (!is_readable($sViewPath)) {
            throw new \RuntimeException(sprintf('Legacy view file is not readable: %s', $sViewPath));
        }

        require_once $sViewPath;

        if (!class_exists($this->viewClass, false)) {
            throw new \RuntimeException(sprintf('Legacy view class was not loaded: %s', $this->viewClass));
        }
    }

    /**
     * The views build their result in a protected `_aResponse` and render a
     * template that echoes it; read it directly instead of rendering.
     */
    private function getResponse(object $view): array
    {
        $reflection = new \ReflectionObject($view);

        while ($reflection !== false) {
            if ($reflection->hasProperty('_aResponse')) {
                $property = $reflection->getProperty('_aResponse');
                $property->setAccessible(true);

                return (array) $property->getValue($view);
            }

            $reflection = $reflection->getParentClass();
        }

        return [];
    }
}
