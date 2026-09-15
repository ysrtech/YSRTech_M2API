<?php

class YSRTech_M2API_Model_Observer
{
    public function initCustomRouter(Varien_Event_Observer $observer)
    {
        $front = $observer->getEvent()->getFront();
        
        // Add Auctane API router FIRST - must be before REST API router
        $front->addRouter('ysrtech_m2api_auctane', new YSRTech_M2API_Controller_AuctaneRouter());
        
        // Add REST API router before standard routers
        $front->addRouter('ysrtech_m2api', new YSRTech_M2API_Controller_Router());
    }
}
