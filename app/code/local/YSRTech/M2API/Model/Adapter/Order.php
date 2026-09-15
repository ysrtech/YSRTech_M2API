<?php

class YSRTech_M2api_Model_Adapter_Order
{
    public function toArray(Mage_Sales_Model_Order $order)
    {
        $items = array();
        foreach ($order->getAllItems() as $item) {
            $items[] = $this->orderItemToArray($item);
        }

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        $data = array(
            'entity_id' => (int)$order->getId(),
            'increment_id' => $order->getIncrementId(),
            'state' => $order->getState(),
            'status' => $order->getStatus(),
            'customer_email' => $order->getCustomerEmail(),
            'customer_firstname' => $order->getCustomerFirstname(),
            'customer_lastname' => $order->getCustomerLastname(),
            'customer_id' => (int)$order->getCustomerId(),
            'base_currency_code' => $order->getBaseCurrencyCode(),
            'order_currency_code' => $order->getOrderCurrencyCode(),
            'store_id' => (int)$order->getStoreId(),
            'grand_total' => (float)$order->getGrandTotal(),
            'base_grand_total' => (float)$order->getBaseGrandTotal(),
            'subtotal' => (float)$order->getSubtotal(),
            'base_subtotal' => (float)$order->getBaseSubtotal(),
            'tax_amount' => (float)$order->getTaxAmount(),
            'base_tax_amount' => (float)$order->getBaseTaxAmount(),
            'shipping_amount' => (float)$order->getShippingAmount(),
            'base_shipping_amount' => (float)$order->getBaseShippingAmount(),
            'discount_amount' => (float)$order->getDiscountAmount(),
            'base_discount_amount' => (float)$order->getBaseDiscountAmount(),
            'shipping_description' => $order->getShippingDescription(),
            'created_at' => $order->getCreatedAt(),
            'updated_at' => $order->getUpdatedAt(),
            'weight' => (float)$order->getWeight(),
            'items' => $items,
            'billing_address' => $billingAddress ? $this->addressToArray($billingAddress) : null,
            'payment' => array(
                'method' => $order->getPayment()->getMethod(),
                'additional_information' => $order->getPayment()->getAdditionalInformation()
            ),
            'extension_attributes' => array(
                'shipping_assignments' => array(
                    array(
                        'shipping' => array(
                            'address' => $shippingAddress ? $this->addressToArray($shippingAddress) : null,
                            'method' => $order->getShippingMethod()
                        )
                    )
                )
            )
        );

        return $data;
    }

    protected function orderItemToArray(Mage_Sales_Model_Order_Item $item)
    {
        return array(
            'item_id' => (int)$item->getId(),
            'order_id' => (int)$item->getOrderId(),
            'quote_item_id' => (int)$item->getQuoteItemId(),
            'product_id' => (int)$item->getProductId(),
            'product_type' => $item->getProductType(),
            'sku' => $item->getSku(),
            'name' => $item->getName(),
            'price' => (float)$item->getPrice(),
            'base_price' => (float)$item->getBasePrice(),
            'qty_ordered' => (float)$item->getQtyOrdered(),
            'qty_invoiced' => (float)$item->getQtyInvoiced(),
            'qty_shipped' => (float)$item->getQtyShipped(),
            'qty_canceled' => (float)$item->getQtyCanceled(),
            'qty_refunded' => (float)$item->getQtyRefunded(),
            'row_total' => (float)$item->getRowTotal(),
            'base_row_total' => (float)$item->getBaseRowTotal(),
            'row_total_incl_tax' => (float)$item->getRowTotalInclTax(),
            'base_row_total_incl_tax' => (float)$item->getBaseRowTotalInclTax(),
            'tax_amount' => (float)$item->getTaxAmount(),
            'base_tax_amount' => (float)$item->getBaseTaxAmount(),
            'discount_amount' => (float)$item->getDiscountAmount(),
            'base_discount_amount' => (float)$item->getBaseDiscountAmount()
        );
    }

    protected function addressToArray(Mage_Sales_Model_Order_Address $address)
    {
        $street = $address->getStreet();
        
        return array(
            'address_type' => $address->getAddressType(),
            'city' => $address->getCity(),
            'company' => $address->getCompany(),
            'country_id' => $address->getCountryId(),
            'email' => $address->getEmail(),
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'postcode' => $address->getPostcode(),
            'region' => $address->getRegion(),
            'region_code' => $address->getRegionCode(),
            'region_id' => (int)$address->getRegionId(),
            'street' => is_array($street) ? $street : array($street),
            'telephone' => $address->getTelephone()
        );
    }

    public function toSimpleArray(Mage_Sales_Model_Order $order)
    {
        return array(
            'entity_id' => (int)$order->getId(),
            'increment_id' => $order->getIncrementId(),
            'state' => $order->getState(),
            'status' => $order->getStatus(),
            'customer_email' => $order->getCustomerEmail(),
            'customer_firstname' => $order->getCustomerFirstname(),
            'customer_lastname' => $order->getCustomerLastname(),
            'grand_total' => (float)$order->getGrandTotal(),
            'created_at' => $order->getCreatedAt()
        );
    }
}
