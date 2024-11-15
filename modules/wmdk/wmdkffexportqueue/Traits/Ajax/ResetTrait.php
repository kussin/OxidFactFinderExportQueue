<?php

namespace Wmdk\FactFinderQueue\Traits\Ajax;

trait ResetTrait
{
    /**
     * Reset
     */
    protected function _reset()
    {
        // GET OXID
        $sOxid = $_GET['oxid'] ?? FALSE;

        // ERROR
        if (!$sOxid) {
            $this->_aResponse['success'] = FALSE;
            $this->_aResponse['validation_errors'][] = 'ERROR_NO_OXID_GIVEN';

            return FALSE;
        }

        return FALSE;
    }
}