<?php

namespace Wmdk\FactFinderQueue\Traits;

trait FlourTrait
{
    private function _getFlourId()
    {
        return $this->_oProduct->oxarticles__wmdkflourid->value;
    }

    private function _getFlourActive()
    {
        return $this->_oProduct->oxarticles__wmdkflouractive->value;
    }

    private function _getFlourPrice()
    {
        if ($this->_bIsParent || $this->_bIsVariant) {
            $oFirstActiveVariant = ($this->_bIsVariant) ? $this->_getFirstActiveVariant($this->_oProduct->oxarticles__oxparentid->value) : $this->_getFirstActiveVariant();

            if ($oFirstActiveVariant) {
                return (double) $oFirstActiveVariant->oxarticles__wmdkflourwarehouseprice->value;
            }
        }

        return (double) $this->_oProduct->oxarticles__wmdkflourwarehouseprice->value;
    }

    private function _getFlourSaleAmount($bSign = TRUE)
    {
        $dPrice = $this->_getFlourPrice();
        $sMsrp = $this->_getMsrp();

        if ( ($dPrice > 0) && ($sMsrp > 0) ){
            $dSaleAmount = round(($dPrice / $sMsrp) * 100, 0);

            return ($bSign) ? $dSaleAmount . '%' : $dSaleAmount;
        }

        return '';
    }

    private function _getFlourShortUrl()
    {
        return $this->_oProduct->oxarticles__wmdkflourshorturl->value;
    }
}