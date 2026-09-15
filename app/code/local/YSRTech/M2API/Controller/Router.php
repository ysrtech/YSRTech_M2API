<?php

class YSRTech_M2API_Controller_Router extends Mage_Core_Controller_Varien_Router_Abstract
{
    public function match(Zend_Controller_Request_Http $request)
    {
        // Log ALL requests to see what ShipStation is trying
        $logEntry = array(
            'timestamp' => date('c'),
            'method' => $request->getMethod(),
            'url' => $request->getRequestUri(),
            'path' => $request->getPathInfo(),
            'matched' => false
        );

        if (!Mage::isInstalled()) {
            Mage::app()->getFrontController()->getResponse()
                ->setRedirect(Mage::getUrl('install'))
                ->sendResponse();
            exit;
        }

        $path = trim((string)$request->getPathInfo(), '/');
        $segments = explode('/', $path);

        if (count($segments) < 2 || $segments[0] !== 'rest' || $segments[1] !== 'V1') {
            Mage::log(json_encode($logEntry, JSON_PRETTY_PRINT), null, 'm2api_all_requests.log', true);
            return false;
        }
        
        $logEntry['matched'] = true;
        $logEntry['segments'] = $segments;
        Mage::log(json_encode($logEntry, JSON_PRETTY_PRINT), null, 'm2api_all_requests.log', true);
        
        $restPath = array_slice($segments, 2);
        
        // Set the request to use our controller
        $request->setModuleName('ysrtech_m2api')
                ->setControllerName('rest')
                ->setActionName('dispatch')
                ->setParam('rest_path', $restPath);
        
        return true;
    }
    
    protected function _getModuleFromConfig($moduleName)
    {
        return 'YSRTech_M2API';
    }
}
