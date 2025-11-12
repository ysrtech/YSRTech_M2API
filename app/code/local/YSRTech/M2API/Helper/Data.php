<?php
// ============================================
// FILE: app/code/local/YSRTech/M2API/Helper/Data.php
// ============================================

class YSRTech_M2API_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Send JSON response with proper headers
     */
    public function sendJsonResponse($data, $httpCode = 200)
    {
        $response = Mage::app()->getResponse();
        $response->clearHeaders()
            ->setHeader('Content-Type', 'application/json', true)
            ->setHttpResponseCode($httpCode)
            ->setBody(json_encode($data))
            ->sendResponse();
        exit;
    }

    /**
     * Send error response
     */
    public function sendErrorResponse($message, $httpCode = 400, $parameters = array())
    {
        $error = array(
            'message' => $message,
            'parameters' => $parameters
        );
        $this->sendJsonResponse($error, $httpCode);
    }

    /**
     * Get bearer token from request
     */
    public function getBearerToken()
    {
        $headers = $this->getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Get authorization header
     */
    public function getAuthorizationHeader()
    {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER["Authorization"]);
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)), 
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * Validate token and return customer/admin ID
     */
    public function validateToken($token)
    {
        if (!$token) {
            return false;
        }

        $tokenModel = Mage::getModel('m2api/token')
            ->getCollection()
            ->addFieldToFilter('token', $token)
            ->addFieldToFilter('expires_at', array('gt' => now()))
            ->getFirstItem();

        if (!$tokenModel->getId()) {
            return false;
        }

        return array(
            'customer_id' => $tokenModel->getCustomerId(),
            'admin_id' => $tokenModel->getAdminId(),
            'type' => $tokenModel->getCustomerId() ? 'customer' : 'admin'
        );
    }

    /**
     * Get request body as array
     */
    public function getRequestBody()
    {
        $body = file_get_contents('php://input');
        return json_decode($body, true);
    }

    /**
     * Format product data for M2 API response
     */
    public function formatProduct($product)
    {
        $data = array(
            'id' => (int)$product->getId(),
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'attribute_set_id' => (int)$product->getAttributeSetId(),
            'price' => (float)$product->getPrice(),
            'status' => (int)$product->getStatus(),
            'visibility' => (int)$product->getVisibility(),
            'type_id' => $product->getTypeId(),
            'created_at' => $product->getCreatedAt(),
            'updated_at' => $product->getUpdatedAt(),
            'weight' => (float)$product->getWeight(),
            'extension_attributes' => array(),
            'custom_attributes' => array()
        );

        // Add stock data
        $stockItem = $product->getStockItem();
        if ($stockItem) {
            $data['extension_attributes']['stock_item'] = array(
                'item_id' => (int)$stockItem->getId(),
                'product_id' => (int)$stockItem->getProductId(),
                'stock_id' => (int)$stockItem->getStockId(),
                'qty' => (float)$stockItem->getQty(),
                'is_in_stock' => (bool)$stockItem->getIsInStock(),
                'manage_stock' => (bool)$stockItem->getManageStock(),
            );
        }

        // Add category IDs
        $categoryIds = $product->getCategoryIds();
        if ($categoryIds) {
            $data['extension_attributes']['category_links'] = array();
            foreach ($categoryIds as $categoryId) {
                $data['extension_attributes']['category_links'][] = array(
                    'category_id' => $categoryId,
                    'position' => 0
                );
            }
        }

        return $data;
    }

    /**
     * Format customer data for M2 API response
     */
    public function formatCustomer($customer)
    {
        $data = array(
            'id' => (int)$customer->getId(),
            'email' => $customer->getEmail(),
            'firstname' => $customer->getFirstname(),
            'lastname' => $customer->getLastname(),
            'store_id' => (int)$customer->getStoreId(),
            'website_id' => (int)$customer->getWebsiteId(),
            'created_at' => $customer->getCreatedAt(),
            'updated_at' => $customer->getUpdatedAt(),
            'group_id' => (int)$customer->getGroupId(),
            'extension_attributes' => array(),
            'custom_attributes' => array()
        );

        // Add addresses
        $addresses = $customer->getAddresses();
        if ($addresses) {
            $data['addresses'] = array();
            foreach ($addresses as $address) {
                $data['addresses'][] = $this->formatAddress($address);
            }
        }

        return $data;
    }

    /**
     * Format address data
     */
    public function formatAddress($address)
    {
        return array(
            'id' => (int)$address->getId(),
            'customer_id' => (int)$address->getCustomerId(),
            'region' => array(
                'region_code' => $address->getRegionCode(),
                'region' => $address->getRegion(),
                'region_id' => (int)$address->getRegionId()
            ),
            'country_id' => $address->getCountryId(),
            'street' => $address->getStreet(),
            'telephone' => $address->getTelephone(),
            'postcode' => $address->getPostcode(),
            'city' => $address->getCity(),
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'default_shipping' => (bool)$address->getIsDefaultShipping(),
            'default_billing' => (bool)$address->getIsDefaultBilling()
        );
    }

    /**
     * Format order data for M2 API response
     */
    public function formatOrder($order)
    {
        $data = array(
            'entity_id' => (int)$order->getId(),
            'increment_id' => $order->getIncrementId(),
            'status' => $order->getStatus(),
            'state' => $order->getState(),
            'store_id' => (int)$order->getStoreId(),
            'customer_id' => (int)$order->getCustomerId(),
            'customer_email' => $order->getCustomerEmail(),
            'customer_firstname' => $order->getCustomerFirstname(),
            'customer_lastname' => $order->getCustomerLastname(),
            'base_currency_code' => $order->getBaseCurrencyCode(),
            'order_currency_code' => $order->getOrderCurrencyCode(),
            'grand_total' => (float)$order->getGrandTotal(),
            'base_grand_total' => (float)$order->getBaseGrandTotal(),
            'subtotal' => (float)$order->getSubtotal(),
            'base_subtotal' => (float)$order->getBaseSubtotal(),
            'shipping_amount' => (float)$order->getShippingAmount(),
            'base_shipping_amount' => (float)$order->getBaseShippingAmount(),
            'tax_amount' => (float)$order->getTaxAmount(),
            'base_tax_amount' => (float)$order->getBaseTaxAmount(),
            'discount_amount' => (float)$order->getDiscountAmount(),
            'base_discount_amount' => (float)$order->getBaseDiscountAmount(),
            'created_at' => $order->getCreatedAt(),
            'updated_at' => $order->getUpdatedAt(),
            'items' => array(),
            'extension_attributes' => array()
        );

        // Add billing address
        if ($order->getBillingAddress()) {
            $data['billing_address'] = $this->formatOrderAddress($order->getBillingAddress());
        }

        // Add shipping address
        if ($order->getShippingAddress()) {
            $data['extension_attributes']['shipping_assignments'] = array(
                array(
                    'shipping' => array(
                        'address' => $this->formatOrderAddress($order->getShippingAddress()),
                        'method' => $order->getShippingMethod()
                    )
                )
            );
        }

        // Add items
        foreach ($order->getAllItems() as $item) {
            $data['items'][] = array(
                'item_id' => (int)$item->getId(),
                'order_id' => (int)$item->getOrderId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'price' => (float)$item->getPrice(),
                'base_price' => (float)$item->getBasePrice(),
                'qty_ordered' => (float)$item->getQtyOrdered(),
                'row_total' => (float)$item->getRowTotal(),
                'base_row_total' => (float)$item->getBaseRowTotal(),
                'product_type' => $item->getProductType()
            );
        }

        return $data;
    }

    /**
     * Format order address
     */
    public function formatOrderAddress($address)
    {
        return array(
            'address_type' => $address->getAddressType(),
            'city' => $address->getCity(),
            'country_id' => $address->getCountryId(),
            'email' => $address->getEmail(),
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'postcode' => $address->getPostcode(),
            'region' => $address->getRegion(),
            'region_id' => (int)$address->getRegionId(),
            'street' => $address->getStreet(),
            'telephone' => $address->getTelephone()
        );
    }
}