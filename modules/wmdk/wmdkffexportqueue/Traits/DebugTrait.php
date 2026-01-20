<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

/**
 * Provides debug logging helpers for the module.
 */
trait DebugTrait
{
    /**
     * Write a debug message to the module log file.
     *
     * @param string $sMessage Log message.
     */
    public function log($sMessage)
    {
        $bDebug = (bool) Registry::getConfig()->getConfigParam('sWmdkFFDebugMode');

        if ($bDebug) {
            Registry::getUtils()->writeToLog($sMessage, 'wmdkffexportqueue.debug.log');
        }
    }
}
