<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

trait ProcessIpTrait
{
    protected $_sProcessIp = null;

    protected function _getProcessIp($sIp = false)
    {
        if ($sIp !== false) {
            return (string) $sIp;
        }

        if ($this->_sProcessIp === null) {
            $this->_sProcessIp = \wmdkffexport_helper::getClientIp();
        }

        return $this->_sProcessIp;
    }

    protected function _isCron(): bool
    {
        $sCronjobIpList = (string) Registry::getConfig()->getConfigParam('sWmdkFFDebugCronjobIpList');
        $aCronjobIps = array_filter(array_map('trim', explode(',', $sCronjobIpList)));
        $bCronjobIp = in_array($this->_getProcessIp(), $aCronjobIps, true);

        return (php_sapi_name() === 'cli') || $bCronjobIp;
    }
}
