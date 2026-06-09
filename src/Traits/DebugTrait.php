<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Service\LogFilePathResolver;

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
            $resolver = new LogFilePathResolver();
            $sFilename = $resolver->resolveFromSetting($sLogFileConfigParam, 'log/KUSSIN_FACTFINDER_DEBUG.log');
            $resolver->ensureDirectoryForFile($sFilename);

            $rFile = fopen($sFilename, 'a');

            if ($rFile) {
                fputs($rFile, date('Y-m-d H:i:s') . ' ' . $sMessage . PHP_EOL);
                fclose($rFile);
            }
        }
    }
}
