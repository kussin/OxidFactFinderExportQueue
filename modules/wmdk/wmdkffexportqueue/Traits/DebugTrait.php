<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

trait DebugTrait
{
/**
     * @param string $sMessage
     */
    public function log($sMessage)
    {
        $bDebug = (bool) Registry::getConfig()->getConfigParam('sWmdkFFDebugMode');

        if ($bDebug) {
            Registry::getUtils()->writeToLog($sMessage, 'wmdkffexportqueue.debug.log');
        }
    }
}