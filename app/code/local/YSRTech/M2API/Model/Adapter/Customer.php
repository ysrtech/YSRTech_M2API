<?php

class YSRTech_M2API_Model_Adapter_Customer
{
    public function toArray(Mage_Customer_Model_Customer $customer)
    {
        $addresses = array();
        foreach ($customer->getAddresses() as $address) {
            $addresses[] = $this->addressToArray($address);
        }

        return array(
            'id' => (int)$customer->getId(),
            'group_id' => (int)$customer->getGroupId(),
            'created_at' => $customer->getCreatedAt(),
            'updated_at' => $customer->getUpdatedAt(),
            'created_in' => $customer->getCreatedIn(),
            'email' => $customer->getEmail(),
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'store_id' => (int)$customer->getStoreId(),
            'website_id' => (int)$customer->getWebsiteId(),
            'addresses' => $addresses,
            'disable_auto_group_change' => 0,
            'extension_attributes' => new stdClass()
        );
    }

    public function addressToArray(Mage_Customer_Model_Address $address)
    {
        $street = $address->getStreet();
        
        return array(
            'id' => (int)$address->getId(),
            'customer_id' => (int)$address->getParentId(),
            'region' => array(
                'region_code' => $address->getRegionCode(),
                'region' => $address->getRegion(),
                'region_id' => (int)$address->getRegionId()
            ),
            'region_id' => (int)$address->getRegionId(),
            'country_id' => $address->getCountryId(),
            'street' => is_array($street) ? $street : array($street),
            'telephone' => $address->getTelephone(),
            'postcode' => $address->getPostcode(),
            'city' => $address->getCity(),
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'default_shipping' => (bool)$address->getIsDefaultShipping(),
            'default_billing' => (bool)$address->getIsDefaultBilling()
        );
    }

    public function toSimpleArray(Mage_Customer_Model_Customer $customer)
    {
        return array(
            'id' => (int)$customer->getId(),
            'group_id' => (int)$customer->getGroupId(),
            'created_at' => $customer->getCreatedAt(),
            'updated_at' => $customer->getUpdatedAt(),
            'email' => $customer->getEmail(),
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'store_id' => (int)$customer->getStoreId(),
            'website_id' => (int)$customer->getWebsiteId()
        );
    }
}
