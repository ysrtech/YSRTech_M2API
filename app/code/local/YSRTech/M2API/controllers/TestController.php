<?php

class YSRTech_M2api_TestController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $this->getResponse()
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode(array(
                'status' => 'Module is loaded',
                'observer_test' => Mage::getConfig()->getNode('global/events/controller_front_init_routers/observers/ysrtech_m2api') ? 'Observer configured' : 'Observer NOT found',
                'router_class_exists' => class_exists('YSRTech_M2api_Controller_Router') ? 'Yes' : 'No'
            )));
    }
}
