<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

trait DebugTrait
{
    /**
     * @param string $sMessage
     * @param string $sLogFileConfigParam
     */
    public function log($sMessage, $sLogFileConfigParam = 'sWmdkFFDebugLogFileExport')
    {
        $bDebug = (bool) Registry::getConfig()->getConfigParam('sWmdkFFDebugMode');

        if ($bDebug) {
            $sLogFile = Registry::getConfig()->getConfigParam($sLogFileConfigParam);

            if (empty($sLogFile)) {
                $sLogFile = 'log/WMDKFFEXPORTQUEUE.DEBUG.log';
            }

            $sFilename = str_replace('//', '/', Registry::getConfig()->getShopConfVar('sShopDir') . $sLogFile);
            $sLogDirectory = dirname($sFilename);

            if (!is_dir($sLogDirectory)) {
                mkdir($sLogDirectory, 0775, true);
            }

            $rFile = fopen($sFilename, 'a');

            if ($rFile) {
                fputs($rFile, date('Y-m-d H:i:s') . ' ' . $sMessage . PHP_EOL);
                fclose($rFile);
            }
        }
    }
}
