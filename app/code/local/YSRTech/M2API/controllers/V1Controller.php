<?php
// ============================================
// FILE: app/code/local/YSRTech/M2API/controllers/V1Controller.php
// ============================================

class YSRTech_M2API_V1Controller extends Mage_Core_Controller_Front_Action
{
    protected $_helper;

    public function preDispatch()
    {
        parent::preDispatch();
        $this->_helper = Mage::helper('m2api');
        
        // Set response type to JSON
        $this->getResponse()->setHeader('Content-Type', 'application/json', true);
        
        // Handle OPTIONS requests for CORS
        if ($this->getRequest()->getMethod() === 'OPTIONS') {
            $this->getResponse()
                ->setHeader('Access-Control-Allow-Origin', '*', true)
                ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS', true)
                ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization', true)
                ->setHttpResponseCode(200)
                ->sendResponse();
            exit;
        }
    }

    /**
     * Integration token endpoint: POST /rest/V1/integration/admin/token
     */
    public function integrationAction()
    {
        try {
            $pathInfo = $this->getRequest()->getPathInfo();
            
            // Handle admin token
            if (strpos($pathInfo, '/integration/admin/token') !== false) {
                $this->_generateAdminToken();
                return;
            }
            
            // Handle customer token
            if (strpos($pathInfo, '/integration/customer/token') !== false) {
                $this->_generateCustomerToken();
                return;
            }

            $this->_helper->sendErrorResponse('Invalid endpoint', 404);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_helper->sendErrorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Products endpoint
     */
    public function productsAction()
    {
        try {
            $method = $this->getRequest()->getMethod();
            $pathInfo = $this->getRequest()->getPathInfo();
            
            // Extract SKU from path if present
            $sku = null;
            if (preg_match('#/products/([^/]+)#', $pathInfo, $matches)) {
                $sku = urldecode($matches[1]);
            }

            switch ($method) {
                case 'GET':
                    if ($sku) {
                        $this->_getProduct($sku);
                    } else {
                        $this->_getProducts();
                    }
                    break;
                case 'POST':
                    $this->_createProduct();
                    break;
                case 'PUT':
                    $this->_updateProduct($sku);
                    break;
                case 'DELETE':
                    $this->_deleteProduct($sku);
                    break;
                default:
                    $this->_helper->sendErrorResponse('Method not allowed', 405);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_helper->sendErrorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Categories endpoint
     */
    public function categoriesAction()
    {
        try {
            $method = $this->getRequest()->getMethod();
            $pathInfo = $this->getRequest()->getPathInfo();
            
            // Extract category ID from path
            $categoryId = null;
            if (preg_match('#/categories/(\d+)#', $pathInfo, $matches)) {
                $categoryId = $matches[1];
            }

            switch ($method) {
                case 'GET':
                    if ($categoryId) {
                        $this->_getCategory($categoryId);
                    } else {
                        $this->_getCategories();
                    }
                    break;
                case 'POST':
                    $this->_createCategory();
                    break;
                case 'PUT':
                    $this->_updateCategory($categoryId);
                    break;
                case 'DELETE':
                    $this->_deleteCategory($categoryId);
                    break;
                default:
                    $this->_helper->sendErrorResponse('Method not allowed', 405);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_helper->sendErrorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Customers endpoint
     */
    public function customersAction()
    {
        try {
            $method = $this->getRequest()->getMethod();
            $pathInfo = $this->getRequest()->getPathInfo();
            
            // Handle /customers/me
            if (strpos($pathInfo, '/customers/me') !== false) {
                $this->_getCurrentCustomer();
                return;
            }
            
            // Extract customer ID from path
            $customerId = null;
            if (preg_match('#/customers/(\d+)#', $pathInfo, $matches)) {
                $customerId = $matches[1];
            }

            switch ($method) {
                case 'GET':
                    if ($customerId) {
                        $this->_getCustomer($customerId);
                    } else {
                        $this->_getCustomers();
                    }
                    break;
                case 'POST':
                    $this->_createCustomer();
                    break;
                case 'PUT':
                    $this->_updateCustomer($customerId);
                    break;
                case 'DELETE':
                    $this->_deleteCustomer($customerId);
                    break;
                default:
                    $this->_helper->sendErrorResponse('Method not allowed', 405);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_helper->sendErrorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Orders endpoint
     */
    public function ordersAction()
    {
        try {
            $method = $this->getRequest()->getMethod();
            $pathInfo = $this->getRequest()->getPathInfo();
            
            // Extract order ID from path
            $orderId = null;
            if (preg_match('#/orders/(\d+)#', $pathInfo, $matches)) {
                $orderId = $matches[1];
            }

            switch ($method) {
                case 'GET':
                    if ($orderId) {
                        $this->_getOrder($orderId);
                    } else {
                        $this->_getOrders();
                    }
                    break;
                default:
                    $this->_helper->sendErrorResponse('Method not allowed', 405);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_helper->sendErrorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Invoices endpoint
     */
    public function invoicesAction()
    {
        try {
            $method = $this->getRequest()->getMethod();
            $pathInfo = $this->getRequest()->getPathInfo();
            
            $invoiceId = null;
            if (preg_match('#/invoices/(\d+)#', $pathInfo, $matches)) {
                $invoiceId = $matches[1];
            }

            switch ($method) {
                case 'GET':
                    if ($invoiceId) {
                        $this->_getInvoice($invoiceId);
                    } else {
                        $this->_getInvoices();
                    }
                    break;
                case 'POST':
                    $this->_createInvoice();
                    break;
                default:
                    $this->_helper->sendErrorResponse('Method not allowed', 405);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_helper->sendErrorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Shipments endpoint
     */
    public function shipmentsAction()
    {
        try {
            $method = $this->getRequest()->getMethod();
            $pathInfo = $this->getRequest()->getPathInfo();
            
            $shipmentId = null;
            if (preg_match('#/shipments/(\d+)#', $pathInfo, $matches)) {
                $shipmentId = $matches[1];
            }

            switch ($method) {
                case 'GET':
                    if ($shipmentId) {
                        $this->_getShipment($shipmentId);
                    } else {
                        $this->_getShipments();
                    }
                    break;
                case 'POST':
                    $this->_createShipment();
                    break;
                default:
                    $this->_helper->sendErrorResponse('Method not allowed', 405);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_helper->sendErrorResponse($e->getMessage(), 500);
        }
    }

    // ============================================
    // AUTHENTICATION METHODS
    // ============================================

    protected function _generateAdminToken()
    {
        if ($this->getRequest()->getMethod() !== 'POST') {
            $this->_helper->sendErrorResponse('Method not allowed', 405);
            return;
        }

        $data = $this->_helper->getRequestBody();
        $username = isset($data['username']) ? $data['username'] : null;
        $password = isset($data['password']) ? $data['password'] : null;

        if (!$username || !$password) {
            $this->_helper->sendErrorResponse('Username and password are required', 400);
            return;
        }

        try {
            $user = Mage::getModel('admin/user')
                ->loadByUsername($username);

            if (!$user->getId() || !$user->authenticate($password)) {
                $this->_helper->sendErrorResponse('Invalid credentials', 401);
                return;
            }

            // Generate token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+4 hours'));

            // Save token
            Mage::getModel('m2api/token')
                ->setAdminId($user->getId())
                ->setToken($token)
                ->setExpiresAt($expiresAt)
                ->save();

            $this->_helper->sendJsonResponse($token, 200);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_helper->sendErrorResponse('Authentication failed', 500);
        }
    }

    protected function _generateCustomerToken()
    {
        if ($this->getRequest()->getMethod() !== 'POST') {
            $this->_helper->sendErrorResponse('Method not allowed', 405);
            return;
        }

        $data = $this->_helper->getRequestBody();
        $username = isset($data['username']) ? $data['username'] : null;
        $password = isset($data['password']) ? $data['password'] : null;

        if (!$username || !$password) {
            $this->_helper->sendErrorResponse('Username and password are required', 400);
            return;
        }

        try {
            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getWebsite()->getId())
                ->loadByEmail($username);

            if (!$customer->getId() || !$customer->validatePassword($password)) {
                $this->_helper->sendErrorResponse('Invalid credentials', 401);
                return;
            }

            // Generate token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+4 hours'));

            // Save token
            Mage::getModel('m2api/token')
                ->setCustomerId($customer->getId())
                ->setToken($token)
                ->setExpiresAt($expiresAt)
                ->save();

            $this->_helper->sendJsonResponse($token, 200);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_helper->sendErrorResponse('Authentication failed', 500);
        }
    }

    protected function _validateAuth($requireAdmin = false)
    {
        $token = $this->_helper->getBearerToken();
        
        if (!$token) {
            $this->_helper->sendErrorResponse('Authentication required', 401);
            return false;
        }

        $authData = $this->_helper->validateToken($token);
        
        if (!$authData) {
            $this->_helper->sendErrorResponse('Invalid or expired token', 401);
            return false;
        }

        if ($requireAdmin && $authData['type'] !== 'admin') {
            $this->_helper->sendErrorResponse('Admin access required', 403);
            return false;
        }

        return $authData;
    }

    // ============================================
    // PRODUCT METHODS
    // ============================================

    protected function _getProducts()
    {
        $authData = $this->_validateAuth();
        if (!$authData) return;

        // Parse search criteria from query params
        $searchCriteria = $this->_parseSearchCriteria();
        
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*');

        // Apply filters
        if (isset($searchCriteria['filters'])) {
            foreach ($searchCriteria['filters'] as $filter) {
                $field = $filter['field'];
                $value = $filter['value'];
                $condition = isset($filter['condition_type']) ? $filter['condition_type'] : 'eq';
                $collection->addAttributeToFilter($field, array($condition => $value));
            }
        }

        // Apply pagination
        $pageSize = isset($searchCriteria['page_size']) ? $searchCriteria['page_size'] : 20;
        $currentPage = isset($searchCriteria['current_page']) ? $searchCriteria['current_page'] : 1;
        
        $collection->setPageSize($pageSize);
        $collection->setCurPage($currentPage);

        $items = array();
        foreach ($collection as $product) {
            $items[] = $this->_helper->formatProduct($product);
        }

        $result = array(
            'items' => $items,
            'search_criteria' => $searchCriteria,
            'total_count' => $collection->getSize()
        );

        $this->_helper->sendJsonResponse($result, 200);
    }

    protected function _getProduct($sku)
    {
        $authData = $this->_validateAuth();
        if (!$authData) return;

        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        
        if (!$product || !$product->getId()) {
            $this->_helper->sendErrorResponse('Product not found', 404);
            return;
        }

        $this->_helper->sendJsonResponse($this->_helper->formatProduct($product), 200);
    }

    protected function _createProduct()
    {
        $authData = $this->_validateAuth(true);
        if (!$authData) return;

        $data = $this->_helper->getRequestBody();
        
        if (!isset($data['product'])) {
            $this->_helper->sendErrorResponse('Product data is required', 400);
            return;
        }

        $productData = $data['product'];

        try {
            $product = Mage::getModel('catalog/product');
            $product->setSku($productData['sku']);
            $product->setName($productData['name']);
            $product->setAttributeSetId($productData['attribute_set_id']);
            $product->setTypeId(isset($productData['type_id']) ? $productData['type_id'] : 'simple');
            $product->setPrice(isset($productData['price']) ? $productData['price'] : 0);
            $product->setStatus(isset($productData['status']) ? $productData['status'] : 1);
            $product->setVisibility(isset($productData['visibility']) ? $productData['visibility'] : 4);
            $product->setWeight(isset($productData['weight']) ? $productData['weight'] : 0);
            
            // Set stock data
            if (isset($productData['extension_attributes']['stock_item'])) {
                $stockData = $productData['extension_attributes']['stock_item'];
                $product->setStockData(array(
                    'qty' => isset($stockData['qty']) ? $stockData['qty'] : 0,
                    'is_in_stock' => isset($stockData['is_in_stock']) ? $stockData['is_in_stock'] : 1,
                    'manage_stock' => isset($stockData['manage_stock']) ? $stockData['manage_stock'] : 1
                ));
            }

            $product->save();

            // Set categories
            if (isset($productData['extension_attributes']['category_links'])) {
                $categoryIds = array();
                foreach ($productData['extension_attributes']['category_links'] as $link) {
                    $categoryIds[] = $link['category_id'];
                }
                $product->setCategoryIds($categoryIds);
                $product->save();
            }

            $this->_helper->sendJsonResponse($this->_helper->formatProduct($product), 201);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_helper->sendErrorResponse($e->getMessage(), 500);
        }
    }

    protected function _updateProduct($sku)
    {
        $authData = $this->_validateAuth(true);
        if (!$authData) return;

        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        
        if (!$product || !$product->getId()) {
            $this->_helper->sendErrorResponse('Product not found', 404);
            return;
        }

        $data = $this->_helper->getRequestBody();
        
        if (!isset($data['product'])) {
            $this->_helper->sendErrorResponse('Product data is required', 400);
            return;
        }

        $productData = $data['product'];

        try {
            if (isset($productData['name'])) $product->setName($productData['name']);
            if (isset($productData['price'])) $product->setPrice($productData['price']);
            if (isset($productData['status'])) $product->setStatus($productData['status']);
            if (isset($productData['visibility'])) $product->setVisibility($productData['visibility']);
            if (isset($productData['weight'])) $product->setWeight($productData['weight']);
            
            // Update stock
            if (isset($productData['extension_attributes']['stock_item'])) {
                $stockData = $productData['extension_attributes']['stock_item'];
                $stockItem = $product->getStockItem();
                if ($stockItem) {
                    if (isset($stockData['qty'])) $stockItem->setQty($stockData['qty']);
                    if (isset($stockData['is_in_stock'])) $stockItem->setIsInStock($stockData['is_in_stock']);
                    if (isset($stockData['manage_stock'])) $stockItem->setManageStock($stockData['manage_stock']);
                    $stockItem->save();
                }
            }

            $product->save();

            $this->_helper->sendJsonResponse($this->_helper->formatProduct($product), 200);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_helper->sendErrorResponse($e->getMessage(), 500);
        }
    }

    protected function _deleteProduct($sku)
    {
        $authData = $this->_validateAuth(true);
        if (!$authData) return;

        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        
        if (!$product || !$product->getId()) {
            $this->_helper->sendErrorResponse('Product not found', 404);
            return;
        }

        try {
            $product->delete();
            $this->_helper->sendJsonResponse(true, 200);
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_helper->sendErrorResponse($e->getMessage(), 500);
        }
    }

    // Continued in next message...
    protected function _parseSearchCriteria()
    {
        $params = $this->getRequest()->getParams();
        $criteria = array();

        if (isset($params['searchCriteria'])) {
            $searchCriteria = $params['searchCriteria'];
            
            if (isset($searchCriteria['filterGroups'])) {
                $criteria['filters'] = array();
                foreach ($searchCriteria['filterGroups'] as $filterGroup) {
                    if (isset($filterGroup['filters'])) {
                        foreach ($filterGroup['filters'] as $filter) {
                            $criteria['filters'][] = $filter;
                        }
                    }
                }
            }
            
            if (isset($searchCriteria['pageSize'])) {
                $criteria['page_size'] = $searchCriteria['pageSize'];
            }
            
            if (isset($searchCriteria['currentPage'])) {
                $criteria['current_page'] = $searchCriteria['currentPage'];
            }
        }

        return $criteria;
    }
    <?php
// ============================================
// Continuation of YSRTech_M2API_V1Controller
// Add these methods to the V1Controller.php file
// ============================================

// CUSTOMER METHODS

protected function _getCustomers()
{
    $authData = $this->_validateAuth(true); // Require admin
    if (!$authData) return;

    $searchCriteria = $this->_parseSearchCriteria();
    
    $collection = Mage::getModel('customer/customer')->getCollection()
        ->addAttributeToSelect('*');

    // Apply filters
    if (isset($searchCriteria['filters'])) {
        foreach ($searchCriteria['filters'] as $filter) {
            $field = $filter['field'];
            $value = $filter['value'];
            $condition = isset($filter['condition_type']) ? $filter['condition_type'] : 'eq';
            $collection->addAttributeToFilter($field, array($condition => $value));
        }
    }

    // Apply pagination
    $pageSize = isset($searchCriteria['page_size']) ? $searchCriteria['page_size'] : 20;
    $currentPage = isset($searchCriteria['current_page']) ? $searchCriteria['current_page'] : 1;
    
    $collection->setPageSize($pageSize);
    $collection->setCurPage($currentPage);

    $items = array();
    foreach ($collection as $customer) {
        $items[] = $this->_helper->formatCustomer($customer);
    }

    $result = array(
        'items' => $items,
        'search_criteria' => $searchCriteria,
        'total_count' => $collection->getSize()
    );

    $this->_helper->sendJsonResponse($result, 200);
}

protected function _getCustomer($customerId)
{
    $authData = $this->_validateAuth();
    if (!$authData) return;

    // Check if user can access this customer
    if ($authData['type'] === 'customer' && $authData['customer_id'] != $customerId) {
        $this->_helper->sendErrorResponse('Access denied', 403);
        return;
    }

    $customer = Mage::getModel('customer/customer')->load($customerId);
    
    if (!$customer->getId()) {
        $this->_helper->sendErrorResponse('Customer not found', 404);
        return;
    }

    $this->_helper->sendJsonResponse($this->_helper->formatCustomer($customer), 200);
}

protected function _getCurrentCustomer()
{
    $authData = $this->_validateAuth();
    if (!$authData) return;

    if ($authData['type'] !== 'customer') {
        $this->_helper->sendErrorResponse('Customer token required', 403);
        return;
    }

    $customer = Mage::getModel('customer/customer')->load($authData['customer_id']);
    
    if (!$customer->getId()) {
        $this->_helper->sendErrorResponse('Customer not found', 404);
        return;
    }

    $this->_helper->sendJsonResponse($this->_helper->formatCustomer($customer), 200);
}

protected function _createCustomer()
{
    // Allow both authenticated and unauthenticated access for registration
    $data = $this->_helper->getRequestBody();
    
    if (!isset($data['customer'])) {
        $this->_helper->sendErrorResponse('Customer data is required', 400);
        return;
    }

    $customerData = $data['customer'];

    // Validate required fields
    if (!isset($customerData['email']) || !isset($customerData['firstname']) || !isset($customerData['lastname'])) {
        $this->_helper->sendErrorResponse('Email, firstname, and lastname are required', 400);
        return;
    }

    try {
        $customer = Mage::getModel('customer/customer');
        $customer->setEmail($customerData['email']);
        $customer->setFirstname($customerData['firstname']);
        $customer->setLastname($customerData['lastname']);
        
        if (isset($customerData['website_id'])) {
            $customer->setWebsiteId($customerData['website_id']);
        } else {
            $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        }
        
        if (isset($customerData['store_id'])) {
            $customer->setStoreId($customerData['store_id']);
        } else {
            $customer->setStoreId(Mage::app()->getStore()->getId());
        }
        
        if (isset($customerData['group_id'])) {
            $customer->setGroupId($customerData['group_id']);
        }

        // Set password if provided
        if (isset($data['password'])) {
            $customer->setPassword($data['password']);
        }

        $customer->save();

        // Add addresses if provided
        if (isset($customerData['addresses'])) {
            foreach ($customerData['addresses'] as $addressData) {
                $address = Mage::getModel('customer/address');
                $address->setCustomerId($customer->getId());
                
                if (isset($addressData['firstname'])) $address->setFirstname($addressData['firstname']);
                if (isset($addressData['lastname'])) $address->setLastname($addressData['lastname']);
                if (isset($addressData['street'])) $address->setStreet($addressData['street']);
                if (isset($addressData['city'])) $address->setCity($addressData['city']);
                if (isset($addressData['country_id'])) $address->setCountryId($addressData['country_id']);
                if (isset($addressData['region'])) {
                    if (isset($addressData['region']['region_id'])) {
                        $address->setRegionId($addressData['region']['region_id']);
                    }
                    if (isset($addressData['region']['region'])) {
                        $address->setRegion($addressData['region']['region']);
                    }
                }
                if (isset($addressData['postcode'])) $address->setPostcode($addressData['postcode']);
                if (isset($addressData['telephone'])) $address->setTelephone($addressData['telephone']);
                if (isset($addressData['default_billing'])) $address->setIsDefaultBilling($addressData['default_billing']);
                if (isset($addressData['default_shipping'])) $address->setIsDefaultShipping($addressData['default_shipping']);
                
                $address->save();
            }
        }

        // Reload customer to get addresses
        $customer = Mage::getModel('customer/customer')->load($customer->getId());
        
        $this->_helper->sendJsonResponse($this->_helper->formatCustomer($customer), 201);
    } catch (Exception $e) {
        Mage::logException($e);
        $this->_helper->sendErrorResponse($e->getMessage(), 500);
    }
}

protected function _updateCustomer($customerId)
{
    $authData = $this->_validateAuth();
    if (!$authData) return;

    // Check if user can update this customer
    if ($authData['type'] === 'customer' && $authData['customer_id'] != $customerId) {
        $this->_helper->sendErrorResponse('Access denied', 403);
        return;
    }

    $customer = Mage::getModel('customer/customer')->load($customerId);
    
    if (!$customer->getId()) {
        $this->_helper->sendErrorResponse('Customer not found', 404);
        return;
    }

    $data = $this->_helper->getRequestBody();
    
    if (!isset($data['customer'])) {
        $this->_helper->sendErrorResponse('Customer data is required', 400);
        return;
    }

    $customerData = $data['customer'];

    try {
        if (isset($customerData['email'])) $customer->setEmail($customerData['email']);
        if (isset($customerData['firstname'])) $customer->setFirstname($customerData['firstname']);
        if (isset($customerData['lastname'])) $customer->setLastname($customerData['lastname']);
        if (isset($customerData['group_id'])) $customer->setGroupId($customerData['group_id']);
        
        $customer->save();

        $this->_helper->sendJsonResponse($this->_helper->formatCustomer($customer), 200);
    } catch (Exception $e) {
        Mage::logException($e);
        $this->_helper->sendErrorResponse($e->getMessage(), 500);
    }
}

protected function _deleteCustomer($customerId)
{
    $authData = $this->_validateAuth(true); // Require admin
    if (!$authData) return;

    $customer = Mage::getModel('customer/customer')->load($customerId);
    
    if (!$customer->getId()) {
        $this->_helper->sendErrorResponse('Customer not found', 404);
        return;
    }

    try {
        $customer->delete();
        $this->_helper->sendJsonResponse(true, 200);
    } catch (Exception $e) {
        Mage::logException($e);
        $this->_helper->sendErrorResponse($e->getMessage(), 500);
    }
}

// ORDER METHODS

protected function _getOrders()
{
    $authData = $this->_validateAuth();
    if (!$authData) return;

    $searchCriteria = $this->_parseSearchCriteria();
    
    $collection = Mage::getModel('sales/order')->getCollection();

    // If customer token, filter by customer
    if ($authData['type'] === 'customer') {
        $collection->addFieldToFilter('customer_id', $authData['customer_id']);
    }

    // Apply filters
    if (isset($searchCriteria['filters'])) {
        foreach ($searchCriteria['filters'] as $filter) {
            $field = $filter['field'];
            $value = $filter['value'];
            $condition = isset($filter['condition_type']) ? $filter['condition_type'] : 'eq';
            $collection->addFieldToFilter($field, array($condition => $value));
        }
    }

    // Apply pagination
    $pageSize = isset($searchCriteria['page_size']) ? $searchCriteria['page_size'] : 20;
    $currentPage = isset($searchCriteria['current_page']) ? $searchCriteria['current_page'] : 1;
    
    $collection->setPageSize($pageSize);
    $collection->setCurPage($currentPage);

    $items = array();
    foreach ($collection as $order) {
        $items[] = $this->_helper->formatOrder($order);
    }

    $result = array(
        'items' => $items,
        'search_criteria' => $searchCriteria,
        'total_count' => $collection->getSize()
    );

    $this->_helper->sendJsonResponse($result, 200);
}

protected function _getOrder($orderId)
{
    $authData = $this->_validateAuth();
    if (!$authData) return;

    $order = Mage::getModel('sales/order')->load($orderId);
    
    if (!$order->getId()) {
        $this->_helper->sendErrorResponse('Order not found', 404);
        return;
    }

    // Check if customer can access this order
    if ($authData['type'] === 'customer' && $order->getCustomerId() != $authData['customer_id']) {
        $this->_helper->sendErrorResponse('Access denied', 403);
        return;
    }

    $this->_helper->sendJsonResponse($this->_helper->formatOrder($order), 200);
}

// INVOICE METHODS

protected function _getInvoices()
{
    $authData = $this->_validateAuth(true); // Require admin
    if (!$authData) return;

    $searchCriteria = $this->_parseSearchCriteria();
    
    $collection = Mage::getModel('sales/order_invoice')->getCollection();

    // Apply filters
    if (isset($searchCriteria['filters'])) {
        foreach ($searchCriteria['filters'] as $filter) {
            $field = $filter['field'];
            $value = $filter['value'];
            $condition = isset($filter['condition_type']) ? $filter['condition_type'] : 'eq';
            $collection->addFieldToFilter($field, array($condition => $value));
        }
    }

    // Apply pagination
    $pageSize = isset($searchCriteria['page_size']) ? $searchCriteria['page_size'] : 20;
    $currentPage = isset($searchCriteria['current_page']) ? $searchCriteria['current_page'] : 1;
    
    $collection->setPageSize($pageSize);
    $collection->setCurPage($currentPage);

    $items = array();
    foreach ($collection as $invoice) {
        $items[] = $this->_formatInvoice($invoice);
    }

    $result = array(
        'items' => $items,
        'search_criteria' => $searchCriteria,
        'total_count' => $collection->getSize()
    );

    $this->_helper->sendJsonResponse($result, 200);
}

protected function _getInvoice($invoiceId)
{
    $authData = $this->_validateAuth(true);
    if (!$authData) return;

    $invoice = Mage::getModel('sales/order_invoice')->load($invoiceId);
    
    if (!$invoice->getId()) {
        $this->_helper->sendErrorResponse('Invoice not found', 404);
        return;
    }

    $this->_helper->sendJsonResponse($this->_formatInvoice($invoice), 200);
}

protected function _createInvoice()
{
    $authData = $this->_validateAuth(true);
    if (!$authData) return;

    $data = $this->_helper->getRequestBody();
    
    if (!isset($data['orderId'])) {
        $this->_helper->sendErrorResponse('Order ID is required', 400);
        return;
    }

    try {
        $order = Mage::getModel('sales/order')->load($data['orderId']);
        
        if (!$order->getId()) {
            $this->_helper->sendErrorResponse('Order not found', 404);
            return;
        }

        if (!$order->canInvoice()) {
            $this->_helper->sendErrorResponse('Cannot create invoice for this order', 400);
            return;
        }

        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
        
        if (isset($data['capture']) && $data['capture']) {
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        } else {
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::NOT_CAPTURE);
        }

        $invoice->register();
        
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        
        $transactionSave->save();

        $this->_helper->sendJsonResponse($this->_formatInvoice($invoice), 201);
    } catch (Exception $e) {
        Mage::logException($e);
        $this->_helper->sendErrorResponse($e->getMessage(), 500);
    }
}

protected function _formatInvoice($invoice)
{
    $data = array(
        'entity_id' => (int)$invoice->getId(),
        'increment_id' => $invoice->getIncrementId(),
        'order_id' => (int)$invoice->getOrderId(),
        'state' => (int)$invoice->getState(),
        'store_id' => (int)$invoice->getStoreId(),
        'grand_total' => (float)$invoice->getGrandTotal(),
        'base_grand_total' => (float)$invoice->getBaseGrandTotal(),
        'subtotal' => (float)$invoice->getSubtotal(),
        'base_subtotal' => (float)$invoice->getBaseSubtotal(),
        'created_at' => $invoice->getCreatedAt(),
        'updated_at' => $invoice->getUpdatedAt(),
        'items' => array()
    );

    foreach ($invoice->getAllItems() as $item) {
        $data['items'][] = array(
            'entity_id' => (int)$item->getId(),
            'order_item_id' => (int)$item->getOrderItemId(),
            'sku' => $item->getSku(),
            'name' => $item->getName(),
            'price' => (float)$item->getPrice(),
            'qty' => (float)$item->getQty(),
            'row_total' => (float)$item->getRowTotal()
        );
    }

    return $data;
}

// SHIPMENT METHODS

protected function _getShipments()
{
    $authData = $this->_validateAuth(true);
    if (!$authData) return;

    $searchCriteria = $this->_parseSearchCriteria();
    
    $collection = Mage::getModel('sales/order_shipment')->getCollection();

    // Apply filters
    if (isset($searchCriteria['filters'])) {
        foreach ($searchCriteria['filters'] as $filter) {
            $field = $filter['field'];
            $value = $filter['value'];
            $condition = isset($filter['condition_type']) ? $filter['condition_type'] : 'eq';
            $collection->addFieldToFilter($field, array($condition => $value));
        }
    }

    // Apply pagination
    $pageSize = isset($searchCriteria['page_size']) ? $searchCriteria['page_size'] : 20;
    $currentPage = isset($searchCriteria['current_page']) ? $searchCriteria['current_page'] : 1;
    
    $collection->setPageSize($pageSize);
    $collection->setCurPage($currentPage);

    $items = array();
    foreach ($collection as $shipment) {
        $items[] = $this->_formatShipment($shipment);
    }

    $result = array(
        'items' => $items,
        'search_criteria' => $searchCriteria,
        'total_count' => $collection->getSize()
    );

    $this->_helper->sendJsonResponse($result, 200);
}

protected function _getShipment($shipmentId)
{
    $authData = $this->_validateAuth(true);
    if (!$authData) return;

    $shipment = Mage::getModel('sales/order_shipment')->load($shipmentId);
    
    if (!$shipment->getId()) {
        $this->_helper->sendErrorResponse('Shipment not found', 404);
        return;
    }

    $this->_helper->sendJsonResponse($this->_formatShipment($shipment), 200);
}

protected function _createShipment()
{
    $authData = $this->_validateAuth(true);
    if (!$authData) return;

    $data = $this->_helper->getRequestBody();
    
    if (!isset($data['orderId'])) {
        $this->_helper->sendErrorResponse('Order ID is required', 400);
        return;
    }

    try {
        $order = Mage::getModel('sales/order')->load($data['orderId']);
        
        if (!$order->getId()) {
            $this->_helper->sendErrorResponse('Order not found', 404);
            return;
        }

        if (!$order->canShip()) {
            $this->_helper->sendErrorResponse('Cannot create shipment for this order', 400);
            return;
        }

        $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment();
        $shipment->register();
        
        // Add tracking if provided
        if (isset($data['tracks']) && is_array($data['tracks'])) {
            foreach ($data['tracks'] as $trackData) {
                $track = Mage::getModel('sales/order_shipment_track')
                    ->setNumber($trackData['track_number'])
                    ->setCarrierCode($trackData['carrier_code'])
                    ->setTitle($trackData['title']);
                $shipment->addTrack($track);
            }
        }
        
        $transactionSave = Mage::getModel('core/resource_transaction')
            ->addObject($shipment)
            ->addObject($shipment->getOrder())
            ->save();

        $this->_helper->sendJsonResponse($this->_formatShipment($shipment), 201);
    } catch (Exception $e) {
        Mage::logException($e);
        $this->_helper->sendErrorResponse($e->getMessage(), 500);
    }
}

protected function _formatShipment($shipment)
{
    $data = array(
        'entity_id' => (int)$shipment->getId(),
        'increment_id' => $shipment->getIncrementId(),
        'order_id' => (int)$shipment->getOrderId(),
        'store_id' => (int)$shipment->getStoreId(),
        'total_qty' => (float)$shipment->getTotalQty(),
        'created_at' => $shipment->getCreatedAt(),
        'updated_at' => $shipment->getUpdatedAt(),
        'items' => array(),
        'tracks' => array()
    );

    foreach ($shipment->getAllItems() as $item) {
        $data['items'][] = array(
            'entity_id' => (int)$item->getId(),
            'order_item_id' => (int)$item->getOrderItemId(),
            'sku' => $item->getSku(),
            'name' => $item->getName(),
            'qty' => (float)$item->getQty()
        );
    }

    foreach ($shipment->getAllTracks() as $track) {
        $data['tracks'][] = array(
            'entity_id' => (int)$track->getId(),
            'track_number' => $track->getNumber(),
            'carrier_code' => $track->getCarrierCode(),
            'title' => $track->getTitle()
        );
    }

    return $data;
}

// CATEGORY METHODS

protected function _getCategories()
{
    $authData = $this->_validateAuth();
    if (!$authData) return;

    $searchCriteria = $this->_parseSearchCriteria();
    
    $collection = Mage::getModel('catalog/category')->getCollection()
        ->addAttributeToSelect('*');

    // Apply filters
    if (isset($searchCriteria['filters'])) {
        foreach ($searchCriteria['filters'] as $filter) {
            $field = $filter['field'];
            $value = $filter['value'];
            $condition = isset($filter['condition_type']) ? $filter['condition_type'] : 'eq';
            $collection->addAttributeToFilter($field, array($condition => $value));
        }
    }

    // Apply pagination
    $pageSize = isset($searchCriteria['page_size']) ? $searchCriteria['page_size'] : 20;
    $currentPage = isset($searchCriteria['current_page']) ? $searchCriteria['current_page'] : 1;
    
    $collection->setPageSize($pageSize);
    $collection->setCurPage($currentPage);

    $items = array();
    foreach ($collection as $category) {
        $items[] = $this->_formatCategory($category);
    }

    $result = array(
        'items' => $items,
        'search_criteria' => $searchCriteria,
        'total_count' => $collection->getSize()
    );

    $this->_helper->sendJsonResponse($result, 200);
}

protected function _getCategory($categoryId)
{
    $authData = $this->_validateAuth();
    if (!$authData) return;

    $category = Mage::getModel('catalog/category')->load($categoryId);
    
    if (!$category->getId()) {
        $this->_helper->sendErrorResponse('Category not found', 404);
        return;
    }

    $this->_helper->sendJsonResponse($this->_formatCategory($category), 200);
}

protected function _formatCategory($category)
{
    return array(
        'id' => (int)$category->getId(),
        'parent_id' => (int)$category->getParentId(),
        'name' => $category->getName(),
        'is_active' => (bool)$category->getIsActive(),
        'position' => (int)$category->getPosition(),
        'level' => (int)$category->getLevel(),
        'children' => $category->getChildren(),
        'created_at' => $category->getCreatedAt(),
        'updated_at' => $category->getUpdatedAt(),
        'path' => $category->getPath(),
        'include_in_menu' => (bool)$category->getIncludeInMenu(),
        'custom_attributes' => array()
    );
}

protected function _createCategory()
{
    $authData = $this->_validateAuth(true);
    if (!$authData) return;

    $data = $this->_helper->getRequestBody();
    
    if (!isset($data['category'])) {
        $this->_helper->sendErrorResponse('Category data is required', 400);
        return;
    }

    $categoryData = $data['category'];

    try {
        $category = Mage::getModel('catalog/category');
        $category->setName($categoryData['name']);
        $category->setIsActive(isset($categoryData['is_active']) ? $categoryData['is_active'] : 1);
        $category->setParentId(isset($categoryData['parent_id']) ? $categoryData['parent_id'] : 2);
        $category->setIncludeInMenu(isset($categoryData['include_in_menu']) ? $categoryData['include_in_menu'] : 1);
        
        if (isset($categoryData['position'])) {
            $category->setPosition($categoryData['position']);
        }
        
        $category->save();

        $this->_helper->sendJsonResponse($this->_formatCategory($category), 201);
    } catch (Exception $e) {
        Mage::logException($e);
        $this->_helper->sendErrorResponse($e->getMessage(), 500);
    }
}

protected function _updateCategory($categoryId)
{
    $authData = $this->_validateAuth(true);
    if (!$authData) return;

    $category = Mage::getModel('catalog/category')->load($categoryId);
    
    if (!$category->getId()) {
        $this->_helper->sendErrorResponse('Category not found', 404);
        return;
    }

    $data = $this->_helper->getRequestBody();
    
    if (!isset($data['category'])) {
        $this->_helper->sendErrorResponse('Category data is required', 400);
        return;
    }

    $categoryData = $data['category'];

    try {
        if (isset($categoryData['name'])) $category->setName($categoryData['name']);
        if (isset($categoryData['is_active'])) $category->setIsActive($categoryData['is_active']);
        if (isset($categoryData['position'])) $category->setPosition($categoryData['position']);
        if (isset($categoryData['include_in_menu'])) $category->setIncludeInMenu($categoryData['include_in_menu']);
        
        $category->save();

        $this->_helper->sendJsonResponse($this->_formatCategory($category), 200);
    } catch (Exception $e) {
        Mage::logException($e);
        $this->_helper->sendErrorResponse($e->getMessage(), 500);
    }
}

protected function _deleteCategory($categoryId)
{
    $authData = $this->_validateAuth(true);
    if (!$authData) return;

    $category = Mage::getModel('catalog/category')->load($categoryId);
    
    if (!$category->getId()) {
        $this->_helper->sendErrorResponse('Category not found', 404);
        return;
    }

    try {
        $category->delete();
        $this->_helper->sendJsonResponse(true, 200);
    } catch (Exception $e) {
        Mage::logException($e);
        $this->_helper->sendErrorResponse($e->getMessage(), 500);
    }
}
}