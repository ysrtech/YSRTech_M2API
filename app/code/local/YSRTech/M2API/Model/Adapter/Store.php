<?php
// app/code/local/YSRTech/M2API/Model/Adapter/Store.php
class YSRTech_M2API_Model_Adapter_Store
{
    public function toArray(Mage_Core_Model_Store $store)
    {
        return array(
            'id' => (int)$store->getId(),
            'code' => (string)$store->getCode(),
            'name' => (string)$store->getName(),
            'website_id' => (int)$store->getWebsiteId(),
            'locale' => (string)Mage::getStoreConfig('general/locale/code', $store->getId()),
            'base_currency_code' => (string)$store->getBaseCurrencyCode(),
            'default_display_currency_code' => (string)$store->getDefaultCurrencyCode(),
            'timezone' => (string)Mage::getStoreConfig('general/locale/timezone', $store->getId()),
            'base_url' => (string)$store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB)
        );
    }
}
