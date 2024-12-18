<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

trait FlourTrait
{
    private function _getFlourId()
    {
        return ($this->_oProduct->oxarticles__wmdkflourid->value != '') ? $this->_oProduct->oxarticles__wmdkflourid->value : NULL;
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
        $sShortUrl = trim($this->_oProduct->oxarticles__wmdkflourshorturl->value);
        $sUrl = Registry::getConfig()->getConfigParam('sWmdkFFFlourShortUrlDomain');
        $sEan = $this->_oProduct->oxarticles__oxean->value;

        // Fallback
        $sFallbackUrl = $sUrl . '/' . $sEan;

        return (strlen($sShortUrl) > 10) ? $sShortUrl : $sFallbackUrl;
    }
}