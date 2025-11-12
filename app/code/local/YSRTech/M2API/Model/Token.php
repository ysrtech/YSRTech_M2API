<?php
// ============================================
// FILE: app/code/local/YSRTech/M2API/Model/Token.php
// ============================================

class YSRTech_M2API_Model_Token extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('m2api/token');
    }
}