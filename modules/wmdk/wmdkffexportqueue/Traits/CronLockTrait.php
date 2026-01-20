<?php

namespace Wmdk\FactFinderQueue\Traits;

trait CronLockTrait
{
    protected $_sCronjobFlagname = null;

    protected function _getCronjobFlagPath(string $sFlagname = '/tmp/wmdk_ff_cron.flag'): string
    {
        $this->_sCronjobFlagname = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . $sFlagname);

        return $this->_sCronjobFlagname;
    }

    protected function _hasCronjobFlag(string $sFlagname = '/tmp/wmdk_ff_cron.flag'): bool
    {
        return file_exists($this->_getCronjobFlagPath($sFlagname));
    }

    protected function _setCronjobFlag(string $sFlagname = '/tmp/wmdk_ff_cron.flag'): void
    {
        $sFlagPath = $this->_getCronjobFlagPath($sFlagname);
        file_put_contents($sFlagPath, '');
    }

    protected function _removeCronjobFlag(): bool
    {
        if ($this->_sCronjobFlagname === null || !file_exists($this->_sCronjobFlagname)) {
            return false;
        }

        return unlink($this->_sCronjobFlagname);
    }
}
