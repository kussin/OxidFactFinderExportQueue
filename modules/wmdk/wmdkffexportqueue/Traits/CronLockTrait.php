<?php

namespace Wmdk\FactFinderQueue\Traits;

/**
 * Manages filesystem-based cron lock flags.
 */
trait CronLockTrait
{
    /**
     * Cached cron flag path.
     *
     * @var string|null
     */
    protected $_sCronjobFlagname = null;

    /**
     * Build the absolute cron flag path.
     *
     * @param string $sFlagname Flag file name.
     * @return string
     */
    protected function _getCronjobFlagPath(string $sFlagname = '/tmp/wmdk_ff_cron.flag'): string
    {
        $this->_sCronjobFlagname = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . $sFlagname);

        return $this->_sCronjobFlagname;
    }

    /**
     * Determine whether the cron flag exists.
     *
     * @param string $sFlagname Flag file name.
     * @return bool
     */
    protected function _hasCronjobFlag(string $sFlagname = '/tmp/wmdk_ff_cron.flag'): bool
    {
        return file_exists($this->_getCronjobFlagPath($sFlagname));
    }

    /**
     * Create the cron flag file.
     *
     * @param string $sFlagname Flag file name.
     */
    protected function _setCronjobFlag(string $sFlagname = '/tmp/wmdk_ff_cron.flag'): void
    {
        $sFlagPath = $this->_getCronjobFlagPath($sFlagname);
        file_put_contents($sFlagPath, '');
    }

    /**
     * Remove the cron flag file if present.
     *
     * @return bool
     */
    protected function _removeCronjobFlag(): bool
    {
        if ($this->_sCronjobFlagname === null || !file_exists($this->_sCronjobFlagname)) {
            return false;
        }

        return unlink($this->_sCronjobFlagname);
    }
}
