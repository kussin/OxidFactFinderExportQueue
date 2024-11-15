<?php

use OxidEsales\Eshop\Core\DatabaseProvider;
use OxidEsales\Eshop\Core\Registry;
use Wmdk\FactFinderQueue\Traits\Ajax\ResetTrait;

/**
 * Class wmdkffexport_ajax
 */
class wmdkffexport_ajax extends oxubase
{
    use ResetTrait;

    protected $_aResponse = array(
        'success' => TRUE,

//        'template' => NULL,

        'validation_errors' => array(),
        'system_errors' => array(),
    );
    
    protected $_sProcessIp = NULL;
    
    protected $_sTemplate = 'wmdkffexport_ajax.tpl';

    
    public function render() {
        // SET LIMITS
        ini_set('max_execution_time', (int) Registry::getConfig()->getConfigParam('sWmdkFFQueuePhpLimitTimeout'));
        ini_set('memory_limit', Registry::getConfig()->getConfigParam('sWmdkFFQueuePhpLimitMemory'));


        // JOBS
        $sJob = $_GET['job'] ?? FALSE;

        switch ($sJob) {
            case 'reset':
                $this->_reset();
                break;

            default:
                break;
        }
        
        // FILE LOG
        $this->_log();

        // OUTPUT
        if (!$this->_isCron()) {
            $this->_aViewData['sResponse'] = json_encode($this->_aResponse);
        }

        return $this->_sTemplate;
    }
    
    
    private function _getProcessIp($sIp = FALSE) {
        if ($this->_sProcessIp == NULL) {
            
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $this->_sProcessIp = $_SERVER['HTTP_CLIENT_IP'];
                
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $this->_sProcessIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
                
            } else {
                $this->_sProcessIp = $_SERVER['REMOTE_ADDR'];
            }
            
        }
        
        return ($sIp != FALSE) ? $sIp : $this->_sProcessIp;
    }
    
    
    private function _isCron() {
        $sIsCronjobOrg = in_array( $this->_getProcessIp(), explode(',', Registry::getConfig()->getConfigParam('sWmdkFFDebugCronjobIpList') ) );
        
        return ( (php_sapi_name() == 'cli') || $sIsCronjobOrg);
    }
    
    
    private function _log() {
        $sFilename  = str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . Registry::getConfig()->getConfigParam('sWmdkFFDebugLogFileQueue'));
        
        // SET ADDITIONAL DATA
//        $this->_aResponse['template'] = $this->_sTemplate;
//        $this->_aResponse['process_ip'] = $this->_getProcessIp();
//        $this->_aResponse['timestamp'] = date('Y-m-d H:i:s');
//        $this->_aResponse['cronjob'] = $this->_isCron();
                
		$rFile = fopen($sFilename, 'a');
		fputs($rFile, json_encode($this->_aResponse) . PHP_EOL);			
		return fclose($rFile);
    }

}