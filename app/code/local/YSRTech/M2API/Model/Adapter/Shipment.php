<?php
// app/code/local/YSRTech/M2API/Model/Adapter/Shipment.php

/**
 * Magento\Sales\Api\Data\ShipmentInterface with items, tracks and comments.
 */
class YSRTech_M2API_Model_Adapter_Shipment extends YSRTech_M2API_Model_Adapter_Abstract
{
    public function toArray(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $data = $this->export($shipment, $this->shipmentFields());

        // M1 keeps these on the order; M2 denormalises them onto the shipment
        $order = $shipment->getOrder();
        if ($order) {
            foreach (array('customer_id', 'billing_address_id', 'shipping_address_id') as $key) {
                if (!isset($data[$key]) && $order->getData($key) !== null) {
                    $data[$key] = (int)$order->getData($key);
                }
            }
        }

        $items = array();
        foreach ($shipment->getAllItems() as $item) {
            $items[] = $this->export($item, $this->itemFields());
        }
        $data['items'] = $items;

        $tracks = array();
        foreach ($shipment->getAllTracks() as $track) {
            $tracks[] = $this->export($track, $this->trackFields());
        }
        $data['tracks'] = $tracks;

        $comments = array();
        foreach ($shipment->getCommentsCollection() as $comment) {
            $comments[] = $this->export($comment, $this->commentFields());
        }
        $data['comments'] = $comments;

        return $data;
    }

    /**
     * M2's GET /V1/shipments returns the full ShipmentInterface per row.
     */
    public function toSimpleArray(Mage_Sales_Model_Order_Shipment $shipment)
    {
        return $this->toArray($shipment);
    }

    protected function shipmentFields()
    {
        return $this->typed(
            array('increment_id', 'created_at', 'updated_at'),
            array(
                'entity_id', 'store_id', 'email_sent', 'order_id', 'customer_id', 'shipping_address_id',
                'billing_address_id', 'shipment_status',
            ),
            array('total_weight', 'total_qty')
        );
    }

    protected function itemFields()
    {
        return $this->typed(
            array('additional_data', 'description', 'name', 'sku'),
            array('entity_id', 'parent_id', 'product_id', 'order_item_id'),
            array('row_total', 'price', 'weight', 'qty')
        );
    }

    protected function trackFields()
    {
        return $this->typed(
            array('track_number', 'description', 'title', 'carrier_code', 'created_at', 'updated_at'),
            array('entity_id', 'parent_id', 'order_id'),
            array('weight', 'qty')
        );
    }

    protected function commentFields()
    {
        return $this->typed(
            array('comment', 'created_at'),
            array('entity_id', 'parent_id', 'is_customer_notified', 'is_visible_on_front')
        );
    }
}
