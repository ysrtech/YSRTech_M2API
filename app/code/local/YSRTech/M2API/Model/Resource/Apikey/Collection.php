<?php
// app/code/local/YSRTech/M2API/Model/Resource/Apikey/Collection.php

class YSRTech_M2API_Model_Resource_Apikey_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('ysrtech_m2api/apikey');
    }
}
