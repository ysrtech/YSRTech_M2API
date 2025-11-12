
<?php
class YSRTech_M2API_Model_Resource_Token extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('m2api/token', 'token_id');
    }
}
