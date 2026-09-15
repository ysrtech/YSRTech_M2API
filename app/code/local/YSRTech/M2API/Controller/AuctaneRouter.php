<?php

class YSRTech_M2api_Controller_AuctaneRouter extends Mage_Core_Controller_Varien_Router_Abstract
{
    public function match(Zend_Controller_Request_Http $request)
    {
        if (!Mage::isInstalled()) {
            Mage::app()->getFrontController()->getResponse()
                ->setRedirect(Mage::getUrl('install'))
                ->sendResponse();
            exit;
        }

        $path = trim((string)$request->getPathInfo(), '/');
        $segments = explode('/', $path);

        // Only match /api/auctane paths
        if (count($segments) < 2 || $segments[0] !== 'api' || $segments[1] !== 'auctane') {
            return false;
        }
        
        // Set the request to use our Auctane controller
        $request->setModuleName('ysrtech_m2api')
                ->setControllerName('auctane')
                ->setActionName('index');
        
        return true;
    }
    
    protected function _getModuleFromConfig($moduleName)
    {
        return 'YSRTech_M2api';
    }
}
