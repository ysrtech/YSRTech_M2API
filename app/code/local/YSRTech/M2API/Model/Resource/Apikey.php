<?php
// app/code/local/YSRTech/M2API/Model/Resource/Apikey.php

class YSRTech_M2API_Model_Resource_Apikey extends Mage_Core_Model_Resource_Db_Abstract
{
    protected function _construct()
    {
        $this->_init('ysrtech_m2api/apikey', 'key_id');
    }

    protected function _beforeSave(Mage_Core_Model_Abstract $object)
    {
        $now = Varien_Date::now();
        if (!$object->getId()) {
            $object->setCreatedAt($now);
        }
        $object->setUpdatedAt($now);
        return parent::_beforeSave($object);
    }

    public function updateLastUsed($keyId)
    {
        $this->_getWriteAdapter()->update(
            $this->getMainTable(),
            array('last_used_at' => Varien_Date::now()),
            array('key_id = ?' => (int)$keyId)
        );
        return $this;
    }
}
