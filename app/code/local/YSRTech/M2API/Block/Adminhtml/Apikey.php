<?php
// app/code/local/YSRTech/M2API/Block/Adminhtml/Apikey.php

class YSRTech_M2API_Block_Adminhtml_Apikey extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_blockGroup     = 'ysrtech_m2api';
        $this->_controller     = 'adminhtml_apikey';
        $this->_headerText     = Mage::helper('ysrtech_m2api')->__('M2 API Keys');
        $this->_addButtonLabel = Mage::helper('ysrtech_m2api')->__('Create API Key');
        parent::__construct();
    }
}
