<?php

/**
 * Class wmdkffexport_vendor
 */
class wmdkffexport_vendor
{

    public function getCsvData($sCsvFile, $iLength = 0, $sSeparator = ';', $sEnclosure = '"', $sEscape = '\\')
    {
        $aCsvData = [];

        $oFile = fopen($sCsvFile, 'r');

        if ($oFile) {
            // Get the first row and use it as headers (array keys)
            $aHeaders = fgetcsv($oFile, $iLength, $sSeparator, $sEnclosure);

            // Loop through each subsequent row of the file
            while (($aLine = fgetcsv($oFile, $iLength, $sSeparator, $sEnclosure)) !== FALSE) {
                // Initialize an associative array for the current row
                $aAssociativeLine = [];

                // Loop through each column in the row
                foreach ($aHeaders as $iKey => $sKey) {
                    // Assign each value to the corresponding header in the associative array
                    $aAssociativeLine[$sKey] = $aLine[$iKey];
                }

                // Add the associative array for the current row to the main array
                $aCsvData[] = $aAssociativeLine;
            }

            // Close the file
            fclose($oFile);
        }

        return $aCsvData;
    }
    
}