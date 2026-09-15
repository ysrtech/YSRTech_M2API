<?php

class YSRTech_M2api_Model_Adapter_Shipment
{
    public function toArray(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $items = array();
        foreach ($shipment->getAllItems() as $item) {
            $items[] = $this->shipmentItemToArray($item);
        }

        $tracks = array();
        foreach ($shipment->getAllTracks() as $track) {
            $tracks[] = $this->trackToArray($track);
        }

        $order = $shipment->getOrder();

        return array(
            'entity_id' => (int)$shipment->getId(),
            'increment_id' => $shipment->getIncrementId(),
            'order_id' => (int)$shipment->getOrderId(),
            'store_id' => (int)$shipment->getStoreId(),
            'total_qty' => (float)$shipment->getTotalQty(),
            'total_weight' => (float)$shipment->getTotalWeight(),
            'created_at' => $shipment->getCreatedAt(),
            'updated_at' => $shipment->getUpdatedAt(),
            'customer_id' => (int)$order->getCustomerId(),
            'billing_address_id' => (int)$order->getBillingAddressId(),
            'shipping_address_id' => (int)$order->getShippingAddressId(),
            'items' => $items,
            'tracks' => $tracks,
            'extension_attributes' => new stdClass()
        );
    }

    protected function shipmentItemToArray(Mage_Sales_Model_Order_Shipment_Item $item)
    {
        return array(
            'entity_id' => (int)$item->getId(),
            'parent_id' => (int)$item->getParentId(),
            'order_item_id' => (int)$item->getOrderItemId(),
            'product_id' => (int)$item->getProductId(),
            'sku' => $item->getSku(),
            'name' => $item->getName(),
            'price' => (float)$item->getPrice(),
            'weight' => (float)$item->getWeight(),
            'qty' => (float)$item->getQty()
        );
    }

    protected function trackToArray(Mage_Sales_Model_Order_Shipment_Track $track)
    {
        return array(
            'entity_id' => (int)$track->getId(),
            'parent_id' => (int)$track->getParentId(),
            'order_id' => (int)$track->getOrderId(),
            'track_number' => $track->getTrackNumber(),
            'title' => $track->getTitle(),
            'carrier_code' => $track->getCarrierCode(),
            'created_at' => $track->getCreatedAt(),
            'updated_at' => $track->getUpdatedAt()
        );
    }

    public function toSimpleArray(Mage_Sales_Model_Order_Shipment $shipment)
    {
        return array(
            'entity_id' => (int)$shipment->getId(),
            'increment_id' => $shipment->getIncrementId(),
            'order_id' => (int)$shipment->getOrderId(),
            'total_qty' => (float)$shipment->getTotalQty(),
            'created_at' => $shipment->getCreatedAt()
        );
    }
}
