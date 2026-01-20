<?php

namespace Wmdk\FactFinderQueue\Traits\Ajax;

use OxidEsales\Eshop\Core\DatabaseProvider;

/**
 * Handles AJAX reset requests for queue entries.
 */
trait ResetTrait
{
    /**
     * Reset queue timestamps for the requested article family.
     *
     * @return bool
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

        $sArticles = 'SELECT DISTINCT 
            OXID,
            ProductNumber,
            MasterProductNumber
        FROM
            wmdk_ff_export_queue
        WHERE 
            ProductNumber IN (
                SELECT DISTINCT
                    ProductNumber
                FROM 
                    wmdk_ff_export_queue
                WHERE 
                    (OXID = "' . $sOxid .'")
            )
            OR 
            MasterProductNumber IN (
                SELECT DISTINCT
                    ProductNumber
                FROM 
                    wmdk_ff_export_queue
                WHERE 
                    (OXID = "' . $sOxid .'")
            )
            OR MasterProductNumber = (
                SELECT DISTINCT 
                    MasterProductNumber
                FROM 
                    wmdk_ff_export_queue
                WHERE 
                    (OXID = "' . $sOxid .'")
            );';

        $oResult = DatabaseProvider::getDb(FALSE)->select($sArticles);

        if ($oResult != FALSE && $oResult->count() > 0) {
            $this->_aResponse['reseted'] = array();

            while (!$oResult->EOF) {
                $aData = $oResult->getFields();

                $sQuery = 'UPDATE
                    wmdk_ff_export_queue
                SET
                    LASTSYNC = "0000-00-00 00:00:00",
                    ProcessIp = "' . $this->_getProcessIp() . '",
                    OXTIMESTAMP = "0000-00-00 00:00:00"
                WHERE
                    (OXID = "' . $aData['OXID'] . '")';

                try {
                    DatabaseProvider::getDb()->execute($sQuery);

                    // LOG
                    $this->_aResponse['reseted'][] = $aData['OXID'];

                } catch (Exception $oException) {
                    // ERROR
                    $this->_aResponse['success'] = FALSE;
                    $this->_aResponse['system_errors'][] = 'ERROR_COULD_NOT_RESET: ' . $aData['OXID'];
                }

                $oResult->fetchRow();
            }
        }

        return FALSE;
    }
}
