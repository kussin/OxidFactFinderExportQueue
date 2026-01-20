<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

/**
 * Determines process IP addresses and cron execution context.
 */
trait ProcessIpTrait
{
    /**
     * Cached process IP address.
     *
     * @var string|null
     */
    protected $_sProcessIp = null;

    /**
     * Resolve the process IP address, optionally overriding it.
     *
     * @param string|false $sIp Explicit IP override.
     * @return string
     */
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

    /**
     * Check whether the current execution is a cron context.
     *
     * @return bool
     */
    protected function _isCron(): bool
    {
        $sCronjobIpList = (string) Registry::getConfig()->getConfigParam('sWmdkFFDebugCronjobIpList');
        $aCronjobIps = array_filter(array_map('trim', explode(',', $sCronjobIpList)));
        $bCronjobIp = in_array($this->_getProcessIp(), $aCronjobIps, true);

        return (php_sapi_name() === 'cli') || $bCronjobIp;
    }
}
