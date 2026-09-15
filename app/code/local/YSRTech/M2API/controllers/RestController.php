<?php

class YSRTech_M2api_RestController extends Mage_Core_Controller_Front_Action
{
    protected $_publicPaths = array(
        array('integration','admin','token'),
        array('integration','admin','token','long-lived'),
        array('integration','customer','token'),
        array('store'), // allow health check without auth
        array('store','storeConfigs'), // allow store configs without auth
        array('store','storeViews'), // allow store views without auth
    );

    public function preDispatch()
    {
        Mage::helper('ysrtech_m2api')->logRequest($this->getRequest());
        
        parent::preDispatch();

        // Skip auth for public paths
        $restPath = $this->getRequest()->getParam('rest_path', array());
        if ($this->isPublic($restPath)) {
            return;
        }

        // Enforce Bearer token
        $authHeader = $this->getRequest()->getHeader('Authorization');
        $token = null;
        if ($authHeader && stripos($authHeader, 'Bearer ') === 0) {
            $token = trim(substr($authHeader, 7));
        }

        $validationResult = Mage::getModel('ysrtech_m2api/auth')->validateToken($token);

        if (!$token || !$validationResult) {
            $this->jsonError(401, 'Unauthorized');
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }

    public function dispatchAction()
    {
        $restPath = $this->getRequest()->getParam('rest_path', array());
        $method   = strtoupper($this->getRequest()->getMethod());

        // Route: /rest/V1/integration/admin/token (POST or GET for testing)
        if ($this->match($restPath, array('integration','admin','token'))) {
            if ($method === 'GET') {
                // For browser testing - show a form or info
                return $this->json(array(
                    'endpoint' => '/rest/V1/integration/admin/token',
                    'method' => 'POST',
                    'description' => 'Admin token endpoint is working. Send POST request with {"username":"...","password":"..."}'
                ));
            }
            if ($method === 'POST') {
                return $this->adminTokenAction();
            }
        }

        // Route: /rest/V1/integration/customer/token (POST)
        if ($this->match($restPath, array('integration','customer','token')) && $method === 'POST') {
            return $this->customerTokenAction();
        }

        // Route: /rest/V1/integration/admin/token/long-lived (POST) - for integrations like ShipStation
        if ($this->match($restPath, array('integration','admin','token','long-lived')) && $method === 'POST') {
            return $this->adminLongLivedTokenAction();
        }

        // Route: /rest/V1/store (GET)
        if ($this->match($restPath, array('store')) && $method === 'GET') {
            return $this->storeAction();
        }

        // Route: /rest/V1/store/storeConfigs (GET)
        if ($this->match($restPath, array('store','storeConfigs')) && $method === 'GET') {
            return $this->storeConfigsAction();
        }

        // Route: /rest/V1/store/storeViews (GET)
        if ($this->match($restPath, array('store','storeViews')) && $method === 'GET') {
            return $this->storeViewsAction();
        }

        // Route: /rest/V1/customers/search (GET)
        if ($this->match($restPath, array('customers','search')) && $method === 'GET') {
            return $this->customersSearchAction();
        }

        // Route: /rest/V1/customers/:id (GET)
        if (count($restPath) == 2 && $restPath[0] === 'customers' && is_numeric($restPath[1]) && $method === 'GET') {
            return $this->customerGetAction($restPath[1]);
        }

        // Route: /rest/V1/customers/me (GET)
        if ($this->match($restPath, array('customers','me')) && $method === 'GET') {
            return $this->customerMeAction();
        }

        // Route: /rest/V1/products (GET)
        if ($this->match($restPath, array('products')) && $method === 'GET') {
            return $this->productsSearchAction();
        }

        // Route: /rest/V1/products/:sku (GET)
        if (count($restPath) == 2 && $restPath[0] === 'products' && $method === 'GET') {
            return $this->productGetAction($restPath[1]);
        }

        // Route: /rest/V1/categories (GET)
        if ($this->match($restPath, array('categories')) && $method === 'GET') {
            return $this->categoriesListAction();
        }

        // Route: /rest/V1/categories/:id (GET)
        if (count($restPath) == 2 && $restPath[0] === 'categories' && is_numeric($restPath[1]) && $method === 'GET') {
            return $this->categoryGetAction($restPath[1]);
        }

        // Route: /rest/V1/orders (GET)
        if ($this->match($restPath, array('orders')) && $method === 'GET') {
            return $this->ordersSearchAction();
        }

        // Route: /rest/V1/orders/:id (GET)
        if (count($restPath) == 2 && $restPath[0] === 'orders' && is_numeric($restPath[1]) && $method === 'GET') {
            return $this->orderGetAction($restPath[1]);
        }

        // Route: /rest/V1/shipments (GET)
        if ($this->match($restPath, array('shipments')) && $method === 'GET') {
            return $this->shipmentsSearchAction();
        }

        // Route: /rest/V1/shipment/:id (GET)
        if (count($restPath) == 2 && $restPath[0] === 'shipment' && is_numeric($restPath[1]) && $method === 'GET') {
            return $this->shipmentGetAction($restPath[1]);
        }

        return $this->jsonError(404, 'Endpoint not found');
    }

    public function adminTokenAction()
    {
        $payload = $this->getJsonBody();
        $username = isset($payload['username']) ? $payload['username'] : null;
        $password = isset($payload['password']) ? $payload['password'] : null;

        if (!$username || !$password) {
            return $this->jsonError(400, 'Missing credentials');
        }

        try {
            /** @var Mage_Admin_Model_User $admin */
            $admin = Mage::getModel('admin/user');
            if ($admin->authenticate($username, $password)) {
                $token = Mage::getModel('ysrtech_m2api/auth')->issueToken('admin', $admin->getId());
                return $this->rawString($token); // Magento 2 returns raw token string
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this->jsonError(401, 'Invalid credentials');
    }

    public function customerTokenAction()
    {
        $payload = $this->getJsonBody();
        $email = isset($payload['username']) ? $payload['username'] : (isset($payload['email']) ? $payload['email'] : null);
        $password = isset($payload['password']) ? $payload['password'] : null;

        if (!$email || !$password) {
            return $this->jsonError(400, 'Missing credentials');
        }

        try {
            /** @var Mage_Customer_Model_Customer $customer */
            $customer = Mage::getModel('customer/customer')
                ->setWebsiteId(Mage::app()->getStore()->getWebsiteId())
                ->loadByEmail($email);

            if ($customer && $customer->getId()) {
                // Validate password
                if (Mage::helper('core')->validateHash($password, $customer->getPasswordHash())) {
                    $token = Mage::getModel('ysrtech_m2api/auth')->issueToken('customer', $customer->getId());
                    return $this->rawString($token);
                }
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this->jsonError(401, 'Invalid credentials');
    }

    public function adminLongLivedTokenAction()
    {
        $data = $this->getJsonBody();
        $username = isset($data['username']) ? $data['username'] : '';
        $password = isset($data['password']) ? $data['password'] : '';

        if (empty($username) || empty($password)) {
            return $this->jsonError(400, 'Missing credentials');
        }

        try {
            /** @var Mage_Admin_Model_User $user */
            $user = Mage::getModel('admin/user');
            $user->login($username, $password);

            if ($user->getId()) {
                // Generate a long-lived token (10 years) for integrations
                $token = Mage::getModel('ysrtech_m2api/auth')->issueLongLivedToken('admin', $user->getId());
                return $this->rawString($token);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $this->jsonError(401, 'Invalid credentials');
    }

    public function storeAction()
    {
        $store = Mage::app()->getStore();
        $payload = Mage::getModel('ysrtech_m2api/adapter_store')->toArray($store);

        return $this->json($payload);
    }

    public function storeConfigsAction()
    {
        $stores = Mage::app()->getStores();
        $configs = array();
        
        foreach ($stores as $store) {
            $configs[] = Mage::getModel('ysrtech_m2api/adapter_store')->toArray($store);
        }

        return $this->json($configs);
    }

    public function storeViewsAction()
    {
        // Same as storeConfigs for M1
        return $this->storeConfigsAction();
    }

    public function customersSearchAction()
    {
        $request = $this->getRequest();
        $page = max(1, (int)$request->getParam('searchCriteria[currentPage]', 1));
        $pageSize = min(100, max(1, (int)$request->getParam('searchCriteria[pageSize]', 20)));

        $collection = Mage::getModel('customer/customer')->getCollection()
            ->addAttributeToSelect('*')
            ->setPageSize($pageSize)
            ->setCurPage($page);

        $items = array();
        foreach ($collection as $customer) {
            $items[] = Mage::getModel('ysrtech_m2api/adapter_customer')->toSimpleArray($customer);
        }

        return $this->json(array(
            'items' => $items,
            'search_criteria' => array(
                'page_size' => $pageSize,
                'current_page' => $page
            ),
            'total_count' => $collection->getSize()
        ));
    }

    public function customerGetAction($customerId)
    {
        $customer = Mage::getModel('customer/customer')->load($customerId);
        
        if (!$customer->getId()) {
            return $this->jsonError(404, 'Customer not found');
        }

        $payload = Mage::getModel('ysrtech_m2api/adapter_customer')->toArray($customer);
        return $this->json($payload);
    }

    public function customerMeAction()
    {
        // Get customer from token
        $authHeader = $this->getRequest()->getHeader('Authorization');
        $token = null;
        if ($authHeader && stripos($authHeader, 'Bearer ') === 0) {
            $token = trim(substr($authHeader, 7));
        }

        if (!$token) {
            return $this->jsonError(401, 'Unauthorized');
        }

        $authModel = Mage::getModel('ysrtech_m2api/auth');
        $tokenData = $authModel->validateToken($token);
        
        if (!$tokenData || $tokenData['type'] !== 'customer') {
            return $this->jsonError(401, 'Invalid customer token');
        }

        $customer = Mage::getModel('customer/customer')->load($tokenData['user_id']);
        
        if (!$customer->getId()) {
            return $this->jsonError(404, 'Customer not found');
        }

        $payload = Mage::getModel('ysrtech_m2api/adapter_customer')->toArray($customer);
        return $this->json($payload);
    }

    public function productsSearchAction()
    {
        $request = $this->getRequest();
        $page = max(1, (int)$request->getParam('searchCriteria[currentPage]', 1));
        $pageSize = min(100, max(1, (int)$request->getParam('searchCriteria[pageSize]', 20)));

        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('status', 1)
            ->setPageSize($pageSize)
            ->setCurPage($page);

        $items = array();
        foreach ($collection as $product) {
            $items[] = Mage::getModel('ysrtech_m2api/adapter_product')->toSimpleArray($product);
        }

        return $this->json(array(
            'items' => $items,
            'search_criteria' => array(
                'page_size' => $pageSize,
                'current_page' => $page
            ),
            'total_count' => $collection->getSize()
        ));
    }

    public function productGetAction($sku)
    {
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
        
        if (!$product || !$product->getId()) {
            return $this->jsonError(404, 'Product not found');
        }

        $payload = Mage::getModel('ysrtech_m2api/adapter_product')->toArray($product);
        return $this->json($payload);
    }

    public function categoriesListAction()
    {
        $request = $this->getRequest();
        $rootCategoryId = (int)$request->getParam('rootCategoryId', Mage::app()->getStore()->getRootCategoryId());

        $tree = Mage::getModel('catalog/category')->getCollection()
            ->addAttributeToSelect('*')
            ->addFieldToFilter('path', array('like' => "1/{$rootCategoryId}/%"))
            ->addIsActiveFilter();

        $items = array();
        foreach ($tree as $category) {
            $items[] = Mage::getModel('ysrtech_m2api/adapter_category')->toArray($category);
        }

        return $this->json(array(
            'items' => $items,
            'total_count' => count($items)
        ));
    }

    public function categoryGetAction($categoryId)
    {
        $category = Mage::getModel('catalog/category')->load($categoryId);
        
        if (!$category->getId()) {
            return $this->jsonError(404, 'Category not found');
        }

        $payload = Mage::getModel('ysrtech_m2api/adapter_category')->toArray($category);
        return $this->json($payload);
    }

    public function ordersSearchAction()
    {
        $request = $this->getRequest();
        $page = max(1, (int)$request->getParam('searchCriteria[currentPage]', 1));
        $pageSize = min(100, max(1, (int)$request->getParam('searchCriteria[pageSize]', 20)));

        $collection = Mage::getModel('sales/order')->getCollection()
            ->setPageSize($pageSize)
            ->setCurPage($page)
            ->setOrder('created_at', 'DESC');

        // Support filtering by customer_id
        $customerId = $request->getParam('searchCriteria[filter_groups][0][filters][0][value]');
        if ($customerId) {
            $collection->addFieldToFilter('customer_id', $customerId);
        }

        $items = array();
        foreach ($collection as $order) {
            $items[] = Mage::getModel('ysrtech_m2api/adapter_order')->toSimpleArray($order);
        }

        return $this->json(array(
            'items' => $items,
            'search_criteria' => array(
                'page_size' => $pageSize,
                'current_page' => $page
            ),
            'total_count' => $collection->getSize()
        ));
    }

    public function orderGetAction($orderId)
    {
        // Load by increment_id only (matching M2 behavior)
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        
        if (!$order->getId()) {
            return $this->jsonError(404, 'Order not found');
        }

        $payload = Mage::getModel('ysrtech_m2api/adapter_order')->toArray($order);
        return $this->json($payload);
    }

    public function shipmentsSearchAction()
    {
        $request = $this->getRequest();
        $page = max(1, (int)$request->getParam('searchCriteria[currentPage]', 1));
        $pageSize = min(100, max(1, (int)$request->getParam('searchCriteria[pageSize]', 20)));

        $collection = Mage::getModel('sales/order_shipment')->getCollection()
            ->setPageSize($pageSize)
            ->setCurPage($page)
            ->setOrder('created_at', 'DESC');
        
        // Support filtering by order_id (entity_id of the order)
        // Try different parameter formats
        $orderId = $request->getParam('searchCriteria[filter_groups][0][filters][0][value]');
        if (!$orderId) {
            // Try nested array format
            $searchCriteria = $request->getParam('searchCriteria');
            if (isset($searchCriteria['filter_groups'][0]['filters'][0]['value'])) {
                $orderId = $searchCriteria['filter_groups'][0]['filters'][0]['value'];
            }
        }
        
        if ($orderId) {
            // Check if it's numeric (entity_id) or string (increment_id)
            if (is_numeric($orderId) && $orderId == (int)$orderId && $orderId < 100000000) {
                // Likely an entity_id, use it directly
                $collection->addFieldToFilter('order_id', $orderId);
            } else {
                // Likely an increment_id, need to find the order entity_id first
                $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
                if ($order->getId()) {
                    $collection->addFieldToFilter('order_id', $order->getId());
                } else {
                    // No matching order, return empty result
                    $collection->addFieldToFilter('order_id', 0);
                }
            }
        }

        $items = array();
        foreach ($collection as $shipment) {
            $items[] = Mage::getModel('ysrtech_m2api/adapter_shipment')->toSimpleArray($shipment);
        }

        return $this->json(array(
            'items' => $items,
            'search_criteria' => array(
                'page_size' => $pageSize,
                'current_page' => $page
            ),
            'total_count' => $collection->getSize()
        ));
    }

    public function shipmentGetAction($shipmentId)
    {
        // Load by increment_id only (matching M2 behavior)
        $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);
        
        if (!$shipment->getId()) {
            return $this->jsonError(404, 'Shipment not found');
        }

        $payload = Mage::getModel('ysrtech_m2api/adapter_shipment')->toArray($shipment);
        return $this->json($payload);
    }

    // --- Utilities ---

    protected function match(array $path, array $expected)
    {
        if (count($path) !== count($expected)) return false;
        foreach ($expected as $i => $seg) {
            if (!isset($path[$i]) || $path[$i] !== $seg) return false;
        }
        return true;
    }

    protected function isPublic(array $path)
    {
        foreach ($this->_publicPaths as $pub) {
            if ($this->match($path, $pub)) return true;
        }
        return false;
    }

    protected function getJsonBody()
    {
        $raw = $this->getRequest()->getRawBody();
        $data = json_decode($raw, true);
        return is_array($data) ? $data : array();
    }

    protected function json($data, $code = 200)
    {
        $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'application/json')
            ->setHttpResponseCode($code)
            ->setBody(json_encode($data));
    }

    protected function rawString($str, $code = 200)
    {
        $this->getResponse()
            ->clearHeaders()
            ->setHeader('Content-Type', 'text/plain')
            ->setHttpResponseCode($code)
            ->setBody($str);
    }

    protected function jsonError($code, $message)
    {
        $this->json(array('message' => $message), $code);
    }
}
