<?php

namespace Wmdk\FactFinderQueue\Traits;

use OxidEsales\Eshop\Core\Registry;

trait FlourTrait
{
    private $_sFlourTaxId19 = '10000';
    private $_sFlourTaxId7 = '20000';
    private $_sFlourTaxId0 = '30000';

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

    private function _getFlourSaleAmount($bSign = false)
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
        $sPrefix = Registry::getConfig()->getConfigParam('sWmdkFFFlourShortUrlPrefix');
        $sEan = $this->_oProduct->oxarticles__oxean->value;

        // Fallback
        $sFallbackUrl = $sUrl . '/' . $sPrefix . $sEan;

        return (strlen($sShortUrl) > 10) ? $sShortUrl : $sFallbackUrl;
    }


    private function _getFlourExportSelection()
    {
        $sPhpMemoryLimit = Registry::getConfig()->getConfigParam('sWmdkFFFlourPhpMemoryLimit');

        $bFlourId = (bool) Registry::getConfig()->getRequestParameter('flour_id');

        // WARNING: MEMORY LIMIT
        ini_set('memory_limit', $sPhpMemoryLimit);

        // GET FIELDS
        $sPreparedExportFields = $this->_getPreparedExportFields($this->_aExportFields);

        // PREPARE DEEPLINK
        $sPreparedExportFields = $this->_getFlourExportSelectionAddUtmTracking($sPreparedExportFields);

        // PREPARE MAP TAX
        $sPreparedExportFields = $this->_getFlourExportSelectionMapTax($sPreparedExportFields);

        // REMOVE % SIGN
        $sPreparedExportFields = $this->_removeFlourExportSelectionPercentageSign($sPreparedExportFields);
        $sPreparedExportFields = $this->_removeFlourExportSelectionPercentageSign($sPreparedExportFields, '`FlourSaleAmount`');

        // CONVERT STOCK TO BOOLEAN
        $sPreparedExportFields = $this->_getFlourExportSelectionStockFlag($sPreparedExportFields);

        // EXTRACT ATTRIBUTES
        $sPreparedExportFields = $this->_extractFlourExportAttributeValue($sPreparedExportFields);

        // RENAME TIMESTAMPS
        $sPreparedExportFields = $this->_renameFlourExportSelectionTimestamp($sPreparedExportFields);
        $sPreparedExportFields = $this->_renameFlourExportSelectionTimestamp($sPreparedExportFields, 'OXTIMESTAMP');

        // EXPORT MARKER
        $sExportMarker = Registry::getConfig()->getConfigParam('sWmdkFFFlourExportMarker');

        $sQuery = 'SELECT 
                ' . $sPreparedExportFields . ',
                "' . date('Y-m-d') . '_' . time() . '" AS `' . $sExportMarker . '`
            FROM 
                `wmdk_ff_export_queue`
            WHERE
                (`Channel` = "' . $this->_sChannel . '")
                AND (`OXSHOPID` = "' . $this->_iShopId . '")
                AND (`LANG` = "' . $this->_iLang . '")
                AND (`OXACTIVE` = "1")
                AND (`FlourActive` = "1")';

        if ($bFlourId) {
            $sQuery .= ' AND (
                    (`FlourId` IS NOT NULL)
                    AND (`FlourId` NOT LIKE "")
                )';
        }

        $sQuery .= ';';

        return $sQuery;
    }

    private function _getFlourExportSelectionAddUtmTracking($sPreparedExportFields)
    {
        // GET BASE URL
        $sSSLShopURL = Registry::getConfig()->getShopUrl();

        // ADD UTM TRACKING
        $sUtmKey = Registry::getConfig()->getConfigParam('sWmdkFFFlourDeeplinkUtmKey');
        $sUtmParams = Registry::getConfig()->getConfigParam('sWmdkFFFlourDeeplinkUtmParams');

        if ($sUtmKey != '' && $sUtmParams != '') {
            $sPreparedExportFields = str_replace(
                $sUtmKey,
                'CONCAT("' . $sSSLShopURL . '", ' . $sUtmKey . ', "?", "' . $sUtmParams . '") AS ' . $sUtmKey,
                $sPreparedExportFields
            );
        } elseif ($sUtmKey != '') {
            $sPreparedExportFields = str_replace(
                $sUtmKey,
                'CONCAT("' . $sSSLShopURL . '", ' . $sUtmKey . ') AS ' . $sUtmKey,
                $sPreparedExportFields
            );
        }

        return $sPreparedExportFields;
    }

    private function _getFlourExportSelectionMapTax($sPreparedExportFields, $sTaxField = '`Tax`')
    {
        return str_replace(
            $sTaxField,
            'IF(' . $sTaxField . ' > 15, ' . $this->_sFlourTaxId19 . ', IF(' . $sTaxField . ' = 0, '
                . $this->_sFlourTaxId0 . ', ' . $this->_sFlourTaxId7 . ')) AS ' . $sTaxField,
            $sPreparedExportFields
        );
    }

    private function _removeFlourExportSelectionPercentageSign($sPreparedExportFields, $sField = '`SaleAmount`')
    {
        return str_replace(
            $sField,
            'IF(' . $sField . ' = "" OR ' . $sField . ' IS NULL, "EMPTY", REPLACE(' . $sField . ', "%", "")) AS ' . $sField,
            $sPreparedExportFields
        );
    }

    protected function _extractFlourExportAttributeValue($sPreparedExportFields)
    {
        // TODO: Add to composer patch (too individual)
        $sField = "Attributes";
        $sAlias = "`Year`";
        $sSearch = "$sField AS $sAlias";
        $aReplace = [];

        // SWITCH
        $aReplace[] = "CASE";
        for ($iYear = (int) date('Y'); $iYear >= 1998; $iYear--) {
            $aReplace[] = "WHEN $sField LIKE '%=$iYear|%' THEN \"$iYear\"";
        }
        $aReplace[] = "ELSE \"\"";
        $aReplace[] = "END AS $sAlias";

        return str_replace(
            $sSearch,
            implode("\n", $aReplace),
            $sPreparedExportFields
        );
    }

    private function _renameFlourExportSelectionTimestamp($sPreparedExportFields, $sField = 'DateModified')
    {
        $oLang = Registry::getLang();
        $sLabel = $oLang->translateString(strtoupper($sField), $this->_iLang);

        return str_replace(
            $sField,
            $sField . ' AS `' . $sLabel . '`',
            $sPreparedExportFields
        );
    }

    private function _getFlourExportSelectionStockFlag($sPreparedExportFields, $sStockField = '`Stock`')
    {
        return str_replace(
            $sStockField,
            'IF(' . $sStockField . ' > 0, "1", "0") AS ' . $sStockField,
            $sPreparedExportFields
        );
    }
}