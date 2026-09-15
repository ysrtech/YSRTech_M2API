<?php
// app/code/local/YSRTech/M2API/Model/Adapter/Order.php

/**
 * Magento\Sales\Api\Data\OrderInterface, including the shipping_assignments
 * and payment_additional_info extension attributes.
 */
class YSRTech_M2API_Model_Adapter_Order extends YSRTech_M2API_Model_Adapter_Abstract
{
    public function toArray(Mage_Sales_Model_Order $order)
    {
        $data = $this->export($order, $this->orderFields(), $this->hiddenTaxAliases());

        $items = array();
        foreach ($order->getAllItems() as $item) {
            $items[] = $this->orderItemToArray($item);
        }
        $data['items'] = $items;

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress) {
            $data['billing_address'] = $this->addressToArray($billingAddress);
        }

        $payment = $order->getPayment();
        if ($payment) {
            $data['payment'] = $this->paymentToArray($payment);
        }

        $histories = array();
        foreach ($order->getAllStatusHistory() as $history) {
            $histories[] = $this->export($history, $this->statusHistoryFields());
        }
        $data['status_histories'] = $histories;

        $extension = array();
        $shippingAddress = $order->getShippingAddress();
        if ($shippingAddress) {
            $extension['shipping_assignments'] = array(array(
                'shipping' => array(
                    'address' => $this->addressToArray($shippingAddress),
                    'method'  => $order->getShippingMethod(),
                    'total'   => $this->export($order, $this->shippingTotalFields(), $this->hiddenTaxAliases()),
                ),
                'items'    => $items,
                'stock_id' => 1,
            ));
        }
        if ($payment) {
            $extension['payment_additional_info'] = $this->paymentAdditionalInfo($payment);
        }
        $data['extension_attributes'] = $extension;

        return $data;
    }

    /**
     * M2's GET /V1/orders returns the full OrderInterface per row.
     */
    public function toSimpleArray(Mage_Sales_Model_Order $order)
    {
        return $this->toArray($order);
    }

    protected function orderItemToArray(Mage_Sales_Model_Order_Item $item)
    {
        return $this->export($item, $this->orderItemFields(), $this->hiddenTaxAliases());
    }

    protected function addressToArray(Mage_Sales_Model_Order_Address $address)
    {
        $data = $this->export($address, $this->addressFields());
        $data['region_code'] = $address->getRegionCode();
        $street = $address->getStreet();
        $data['street'] = is_array($street) ? array_values($street) : array((string)$street);
        return $data;
    }

    protected function paymentToArray(Mage_Sales_Model_Order_Payment $payment)
    {
        $data = $this->export($payment, $this->paymentFields());
        // M2 types this as string[]: the values only
        $values = array();
        $info = $payment->getAdditionalInformation();
        if (is_array($info)) {
            foreach ($info as $value) {
                if (is_scalar($value)) {
                    $values[] = (string)$value;
                }
            }
        }
        $data['additional_information'] = $values;
        return $data;
    }

    /**
     * Magento\Payment\Api\Data\PaymentAdditionalInfoInterface[]: the
     * additional-information pairs, plus the method title when it isn't
     * already among them.
     */
    protected function paymentAdditionalInfo(Mage_Sales_Model_Order_Payment $payment)
    {
        $pairs = array();
        $info = $payment->getAdditionalInformation();
        $hasTitle = false;
        if (is_array($info)) {
            foreach ($info as $key => $value) {
                if (!is_scalar($value)) {
                    continue;
                }
                $pairs[] = array('key' => (string)$key, 'value' => (string)$value);
                if ($key === 'method_title') {
                    $hasTitle = true;
                }
            }
        }
        if (!$hasTitle) {
            try {
                $title = $payment->getMethodInstance()->getTitle();
            } catch (Exception $e) {
                $title = $payment->getMethod();
            }
            $pairs[] = array('key' => 'method_title', 'value' => (string)$title);
        }
        return $pairs;
    }

    // --- Field lists (Magento\Sales\Api\Data\*) ---

    protected function orderFields()
    {
        return $this->typed(
            array(
                'state', 'status', 'coupon_code', 'protect_code', 'shipping_description', 'increment_id',
                'applied_rule_ids', 'base_currency_code', 'customer_email', 'customer_firstname',
                'customer_lastname', 'customer_middlename', 'customer_prefix', 'customer_suffix',
                'customer_taxvat', 'discount_description', 'ext_customer_id', 'ext_order_id',
                'global_currency_code', 'hold_before_state', 'hold_before_status', 'order_currency_code',
                'original_increment_id', 'relation_child_real_id', 'relation_parent_real_id', 'remote_ip',
                'store_currency_code', 'store_name', 'x_forwarded_for', 'customer_note', 'created_at',
                'updated_at', 'customer_dob',
            ),
            array(
                'entity_id', 'is_virtual', 'store_id', 'customer_id', 'can_ship_partially',
                'can_ship_partially_item', 'customer_is_guest', 'customer_note_notify', 'billing_address_id',
                'customer_group_id', 'edit_increment', 'email_sent', 'forced_shipment_with_invoice',
                'payment_auth_expiration', 'quote_address_id', 'quote_id', 'relation_child_id',
                'relation_parent_id', 'total_item_count', 'customer_gender',
            ),
            array(
                'base_discount_amount', 'base_discount_canceled', 'base_discount_invoiced',
                'base_discount_refunded', 'base_grand_total', 'base_shipping_amount', 'base_shipping_canceled',
                'base_shipping_invoiced', 'base_shipping_refunded', 'base_shipping_tax_amount',
                'base_shipping_tax_refunded', 'base_subtotal', 'base_subtotal_canceled', 'base_subtotal_invoiced',
                'base_subtotal_refunded', 'base_tax_amount', 'base_tax_canceled', 'base_tax_invoiced',
                'base_tax_refunded', 'base_to_global_rate', 'base_to_order_rate', 'base_total_canceled',
                'base_total_invoiced', 'base_total_invoiced_cost', 'base_total_offline_refunded',
                'base_total_online_refunded', 'base_total_paid', 'base_total_qty_ordered', 'base_total_refunded',
                'discount_amount', 'discount_canceled', 'discount_invoiced', 'discount_refunded', 'grand_total',
                'shipping_amount', 'shipping_canceled', 'shipping_invoiced', 'shipping_refunded',
                'shipping_tax_amount', 'shipping_tax_refunded', 'store_to_base_rate', 'store_to_order_rate',
                'subtotal', 'subtotal_canceled', 'subtotal_invoiced', 'subtotal_refunded', 'tax_amount',
                'tax_canceled', 'tax_invoiced', 'tax_refunded', 'total_canceled', 'total_invoiced',
                'total_offline_refunded', 'total_online_refunded', 'total_paid', 'total_qty_ordered',
                'total_refunded', 'adjustment_negative', 'adjustment_positive', 'base_adjustment_negative',
                'base_adjustment_positive', 'base_shipping_discount_amount', 'base_subtotal_incl_tax',
                'base_total_due', 'payment_authorization_amount', 'shipping_discount_amount',
                'subtotal_incl_tax', 'total_due', 'weight', 'discount_tax_compensation_amount',
                'base_discount_tax_compensation_amount', 'shipping_discount_tax_compensation_amount',
                'base_shipping_discount_tax_compensation_amnt', 'discount_tax_compensation_invoiced',
                'base_discount_tax_compensation_invoiced', 'discount_tax_compensation_refunded',
                'base_discount_tax_compensation_refunded', 'shipping_incl_tax', 'base_shipping_incl_tax',
            )
        );
    }

    protected function orderItemFields()
    {
        return $this->typed(
            array(
                'created_at', 'updated_at', 'product_type', 'sku', 'name', 'description', 'applied_rule_ids',
                'additional_data', 'ext_order_item_id', 'weee_tax_applied',
            ),
            array(
                'item_id', 'order_id', 'parent_item_id', 'quote_item_id', 'store_id', 'product_id', 'is_virtual',
                'is_qty_decimal', 'no_discount', 'locked_do_invoice', 'locked_do_ship', 'free_shipping',
                'event_id', 'gw_id',
            ),
            array(
                'weight', 'qty_backordered', 'qty_canceled', 'qty_invoiced', 'qty_ordered', 'qty_refunded',
                'qty_shipped', 'base_cost', 'price', 'base_price', 'original_price', 'base_original_price',
                'tax_percent', 'tax_amount', 'base_tax_amount', 'tax_invoiced', 'base_tax_invoiced',
                'discount_percent', 'discount_amount', 'base_discount_amount', 'discount_invoiced',
                'base_discount_invoiced', 'amount_refunded', 'base_amount_refunded', 'row_total', 'base_row_total',
                'row_invoiced', 'base_row_invoiced', 'row_weight', 'base_tax_before_discount',
                'tax_before_discount', 'price_incl_tax', 'base_price_incl_tax', 'row_total_incl_tax',
                'base_row_total_incl_tax', 'discount_tax_compensation_amount',
                'base_discount_tax_compensation_amount', 'discount_tax_compensation_invoiced',
                'base_discount_tax_compensation_invoiced', 'discount_tax_compensation_refunded',
                'base_discount_tax_compensation_refunded', 'tax_canceled', 'discount_tax_compensation_canceled',
                'tax_refunded', 'base_tax_refunded', 'discount_refunded', 'base_discount_refunded',
                'gw_base_price', 'gw_price', 'gw_base_tax_amount', 'gw_tax_amount', 'gw_base_price_invoiced',
                'gw_price_invoiced', 'gw_base_tax_amount_invoiced', 'gw_tax_amount_invoiced',
                'gw_base_price_refunded', 'gw_price_refunded', 'gw_base_tax_amount_refunded',
                'gw_tax_amount_refunded', 'qty_returned', 'base_weee_tax_applied_amount',
                'base_weee_tax_applied_row_amnt', 'weee_tax_applied_amount', 'weee_tax_applied_row_amount',
                'weee_tax_disposition', 'weee_tax_row_disposition', 'base_weee_tax_disposition',
                'base_weee_tax_row_disposition',
            )
        );
    }

    protected function addressFields()
    {
        return $this->typed(
            array(
                'fax', 'region', 'postcode', 'lastname', 'city', 'email', 'telephone', 'country_id',
                'firstname', 'address_type', 'prefix', 'middlename', 'suffix', 'company', 'vat_id',
                'vat_request_id', 'vat_request_date',
            ),
            array(
                'entity_id', 'parent_id', 'customer_address_id', 'region_id', 'customer_id', 'vat_is_valid',
                'vat_request_success',
            )
        );
    }

    /**
     * OrderPaymentInterface minus the encrypted card number and gateway debug
     * dumps, which have no business leaving the server.
     */
    protected function paymentFields()
    {
        return $this->typed(
            array(
                'additional_data', 'cc_exp_month', 'cc_ss_start_year', 'echeck_bank_name', 'method',
                'cc_secure_verify', 'protection_eligibility', 'cc_approval', 'cc_last_4',
                'cc_status_description', 'echeck_type', 'cc_ss_start_month', 'echeck_account_type',
                'last_trans_id', 'cc_cid_status', 'cc_owner', 'cc_type', 'po_number', 'cc_exp_year', 'cc_status',
                'echeck_routing_number', 'account_status', 'anet_trans_method', 'cc_ss_issue',
                'echeck_account_name', 'cc_avs_status', 'cc_trans_id', 'address_status',
            ),
            array('entity_id', 'parent_id', 'quote_payment_id'),
            array(
                'base_shipping_captured', 'shipping_captured', 'amount_refunded', 'base_amount_paid',
                'amount_canceled', 'base_amount_authorized', 'base_amount_paid_online',
                'base_amount_refunded_online', 'base_shipping_amount', 'shipping_amount', 'amount_paid',
                'amount_authorized', 'base_amount_ordered', 'base_shipping_refunded', 'shipping_refunded',
                'base_amount_refunded', 'amount_ordered', 'base_amount_canceled',
            )
        );
    }

    protected function statusHistoryFields()
    {
        return $this->typed(
            array('comment', 'status', 'created_at', 'entity_name'),
            array('entity_id', 'parent_id', 'is_customer_notified', 'is_visible_on_front')
        );
    }

    /** Magento\Sales\Api\Data\TotalInterface */
    protected function shippingTotalFields()
    {
        return $this->typed(array(), array(), array(
            'base_shipping_amount', 'base_shipping_canceled', 'base_shipping_invoiced', 'base_shipping_refunded',
            'base_shipping_tax_amount', 'base_shipping_tax_refunded', 'base_shipping_discount_amount',
            'base_shipping_discount_tax_compensation_amnt', 'base_shipping_incl_tax', 'shipping_amount',
            'shipping_canceled', 'shipping_discount_amount', 'shipping_discount_tax_compensation_amount',
            'shipping_incl_tax', 'shipping_invoiced', 'shipping_refunded', 'shipping_tax_amount',
            'shipping_tax_refunded',
        ));
    }
}
