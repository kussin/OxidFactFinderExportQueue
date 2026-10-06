<?php

declare(strict_types=1);

namespace Wmdk\FactFinderQueue\Service;

use OxidEsales\Eshop\Core\Registry;

/**
 * Makes this module's settings visible to the legacy view classes.
 *
 * The views in views/wmdkffexport_*.php read their configuration with
 * Registry::getConfig()->getConfigParam(), which under OXID 7 returns null for
 * *module* settings — they only exist in the module setting service. Without
 * this bridge the queue would run with a limit of 0, the export would write to
 * an unresolved directory, and so on.
 *
 * Both surfaces a job can be invoked on (the `wmdkffexport:*` console commands
 * and the `cl=wmdkffexport_*` controllers) call this first, so they see exactly
 * the same configuration.
 */
class LegacyConfigBridge
{
    /**
     * Fallbacks for settings the legacy views require but that may not be set
     * yet on a given shop. Only applied when the resolved value is empty.
     */
    private const DEFAULTS = [
        'sWmdkFFQueueLimit' => '150',
        'iArticleStatus' => '1',
        'iArticleMinStock' => '0',
        'sWmdkFFQueueAttributeGlue' => '|',
        'sWmdkFFQueueFlagTopseller' => '10',
        'sWmdkFFQueuePhpLimitTimeout' => '900',
        'sWmdkFFQueuePhpLimitMemory' => '512M',
        'sWmdkFFQueueResetLimit' => '75',
        'bWmdkFFQueueEnableFromPrice' => '1',
        'bWmdkFFQueueUpdateSiblings' => '0',
        'bWmdkFFQueueUseCategoryPath' => '0',
        'sWmdkFFDebugCronjobIpList' => '',
        'sWmdkFFDebugLogFileQueue' => 'log/KUSSIN_FACTFINDER_QUEUE.log',
        'sWmdkFFDebugLogFileExport' => 'log/KUSSIN_FACTFINDER_EXPORT.log',
        'sWmdkFFDebugLogFileStock' => 'log/KUSSIN_FACTFINDER_STOCK.log',
        'sWmdkFFDebugLogFileClonedAttributes' => 'log/KUSSIN_FACTFINDER_CLONED_ATTRIBUTES.log',
        'sWmdkFFDebugLogFileCleanup' => 'log/KUSSIN_FACTFINDER_CLEANUP.log',
    ];

    public function apply(): void
    {
        $config = Registry::getConfig();

        foreach ((new ModuleSettingsReader())->getAllSettings() as $name => $value) {
            $config->setConfigParam($name, $value);
        }

        foreach (self::DEFAULTS as $name => $value) {
            if ((string) $config->getConfigParam($name) === '') {
                $config->setConfigParam($name, $value);
            }
        }
    }
}
