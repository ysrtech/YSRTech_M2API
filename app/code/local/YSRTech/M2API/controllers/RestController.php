<?php

class YSRTech_M2API_RestController extends Mage_Core_Controller_Front_Action
{
    protected $_publicPaths = array(
        array('integration','admin','token'),
        array('integration','customer','token'),
        array('store'), // allow health check without auth
        array('store','storeConfigs'), // allow store configs without auth
        array('store','storeViews'), // allow store views without auth
    );

    /** @var array|null  result of Auth::validateToken() for the current request */
    protected $_authData = null;

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
            return;
        }
        $this->_authData = $validationResult;
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

        // Route: /rest/V1/order/:id/invoice (POST) - admin only
        if (count($restPath) == 3 && $restPath[0] === 'order' && $restPath[2] === 'invoice' && $method === 'POST') {
            return $this->orderInvoiceAction($restPath[1]);
        }

        // Route: /rest/V1/order/:id/ship (POST) - admin only
        if (count($restPath) == 3 && $restPath[0] === 'order' && $restPath[2] === 'ship' && $method === 'POST') {
            return $this->orderShipAction($restPath[1]);
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
                return $this->json($token); // M2 returns the token as a JSON string, i.e. quoted
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
                    return $this->json($token);
                }
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
        try {
            $collection = Mage::getModel('customer/customer')->getCollection()
                ->addAttributeToSelect('*');
            $searchCriteria = $this->applySearchCriteria($collection, array('entity_id', 'ASC'));

            $items = array();
            foreach ($collection as $customer) {
                $items[] = Mage::getModel('ysrtech_m2api/adapter_customer')->toSimpleArray($customer);
            }

            return $this->json(array(
                'items' => $items,
                'search_criteria' => $searchCriteria,
                'total_count' => $collection->getSize()
            ));
        } catch (Exception $e) {
            return $this->searchError($e);
        }
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
        try {
            $collection = Mage::getModel('catalog/product')->getCollection()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('status', 1);
            $searchCriteria = $this->applySearchCriteria($collection, array('entity_id', 'ASC'));

            $items = array();
            foreach ($collection as $product) {
                $items[] = Mage::getModel('ysrtech_m2api/adapter_product')->toSimpleArray($product);
            }

            return $this->json(array(
                'items' => $items,
                'search_criteria' => $searchCriteria,
                'total_count' => $collection->getSize()
            ));
        } catch (Exception $e) {
            return $this->searchError($e);
        }
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
        try {
            $collection = Mage::getModel('sales/order')->getCollection();
            $searchCriteria = $this->applySearchCriteria($collection, array('created_at', 'DESC'));

            $items = array();
            foreach ($collection as $order) {
                $items[] = Mage::getModel('ysrtech_m2api/adapter_order')->toSimpleArray($order);
            }

            return $this->json(array(
                'items' => $items,
                'search_criteria' => $searchCriteria,
                'total_count' => $collection->getSize()
            ));
        } catch (Exception $e) {
            return $this->searchError($e);
        }
    }

    public function orderGetAction($orderId)
    {
        // M2 addresses orders by entity_id; increment_id accepted as a fallback
        $order = $this->loadOrder($orderId);

        if (!$order) {
            return $this->jsonError(404, 'Order not found');
        }

        $payload = Mage::getModel('ysrtech_m2api/adapter_order')->toArray($order);
        return $this->json($payload);
    }

    /**
     * POST /rest/V1/order/:orderId/invoice  (Magento\Sales\Api\InvoiceOrderInterface)
     *
     * Body, all optional, same semantics as Magento 2:
     *   capture        bool  default false. true = capture online through the
     *                        payment gateway; false = mark paid offline. Either
     *                        way the invoice ends up "paid". Offline methods
     *                        (check/money order, COD) ignore the flag.
     *   items          [{order_item_id, qty}]  omit to invoice everything remaining
     *   notify         bool  email the invoice to the customer
     *   appendComment  bool  include the comment in that email
     *   comment        {comment, is_visible_on_front}
     *
     * Returns the new invoice's entity_id as a bare integer, like M2.
     */
    public function orderInvoiceAction($orderId)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        $order = $this->loadOrder($orderId);
        if (!$order) {
            return $this->jsonError(404, "The entity that was requested doesn't exist. Verify the entity and try again.");
        }

        $data = $this->getJsonBody();
        $capture = isset($data['capture']) && $this->toBool($data['capture']);
        $notify = isset($data['notify']) && $this->toBool($data['notify']);
        $appendComment = isset($data['appendComment']) && $this->toBool($data['appendComment']);
        list($commentText, $commentVisible) = $this->parseComment($data);

        try {
            $errors = array();
            if (!$order->canInvoice()) {
                $errors[] = 'The order does not allow an invoice to be created.';
            }
            $qtys = $this->buildQtys($order, isset($data['items']) ? $data['items'] : array(), 'invoice', $errors);
            if ($errors) {
                return $this->jsonError(400, "Invoice Document Validation Error(s):\n" . implode("\n", $errors));
            }

            /** @var Mage_Sales_Model_Order_Invoice $invoice */
            $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($qtys);
            if (!$invoice->getTotalQty()) {
                return $this->jsonError(400, "Invoice Document Validation Error(s):\nThe invoice can't be created without products. Add products and try again.");
            }

            // Mirrors M2's PayOperation: capture-capable methods either hit the
            // gateway or are paid offline; everything else register() pays itself.
            if ($invoice->canCapture()) {
                $invoice->setRequestedCaptureCase($capture
                    ? Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE
                    : Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
            }

            $invoice->register();
            if ($commentText !== '') {
                $invoice->addComment($commentText, $appendComment && $notify, $commentVisible);
            }
            if ($notify) {
                $invoice->setEmailSent(true);
            }
            $invoice->getOrder()->setCustomerNoteNotify($appendComment && $notify);
            $invoice->getOrder()->setIsInProcess(true);

            Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder())
                ->save();

            if ($notify) {
                $invoice->sendEmail(true, $appendComment ? $commentText : '');
            }

            return $this->json((int)$invoice->getId());
        } catch (Mage_Core_Exception $e) {
            // Includes gateway declines during online capture
            return $this->jsonError(400, $e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            return $this->jsonError(500, 'Could not save an invoice, see error log for details');
        }
    }

    /**
     * POST /rest/V1/order/:orderId/ship  (Magento\Sales\Api\ShipOrderInterface)
     *
     * Body, all optional, same semantics as Magento 2:
     *   items          [{order_item_id, qty}]  omit to ship everything remaining
     *   tracks         [{track_number, title, carrier_code}]
     *   notify         bool  email the shipment to the customer
     *   appendComment  bool  include the comment in that email
     *   comment        {comment, is_visible_on_front}
     *   packages       accepted and ignored (M2 ignores them too)
     *
     * Returns the new shipment's entity_id as a bare integer, like M2.
     */
    public function orderShipAction($orderId)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        $order = $this->loadOrder($orderId);
        if (!$order) {
            return $this->jsonError(404, "The entity that was requested doesn't exist. Verify the entity and try again.");
        }

        $data = $this->getJsonBody();
        $notify = isset($data['notify']) && $this->toBool($data['notify']);
        $appendComment = isset($data['appendComment']) && $this->toBool($data['appendComment']);
        list($commentText, $commentVisible) = $this->parseComment($data);

        try {
            $errors = array();
            if (!$order->canShip()) {
                $errors[] = sprintf('A shipment cannot be created when an order has a status of %s', $order->getStatus());
            }
            $qtys = $this->buildQtys($order, isset($data['items']) ? $data['items'] : array(), 'ship', $errors);
            if ($errors) {
                return $this->jsonError(400, "Shipment Document Validation Error(s):\n" . implode("\n", $errors));
            }

            /** @var Mage_Sales_Model_Order_Shipment $shipment */
            $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($qtys);
            if (!$shipment->getTotalQty()) {
                return $this->jsonError(400, "Shipment Document Validation Error(s):\nYou can't create a shipment without products.");
            }

            if (!empty($data['tracks']) && is_array($data['tracks'])) {
                foreach ($data['tracks'] as $trackData) {
                    $number = isset($trackData['track_number']) ? trim((string)$trackData['track_number']) : '';
                    if ($number === '') {
                        continue;
                    }
                    $carrier = isset($trackData['carrier_code']) ? strtolower(trim((string)$trackData['carrier_code'])) : 'custom';
                    $title = isset($trackData['title']) && $trackData['title'] !== '' ? $trackData['title'] : $carrier;
                    $shipment->addTrack(
                        Mage::getModel('sales/order_shipment_track')
                            ->setNumber($number)
                            ->setCarrierCode($carrier ?: 'custom')
                            ->setTitle($title)
                    );
                }
            }

            $shipment->register();
            if ($commentText !== '') {
                $shipment->addComment($commentText, $appendComment && $notify, $commentVisible);
                if ($appendComment) {
                    $shipment->setCustomerNote($commentText)->setCustomerNoteNotify($notify);
                }
            }
            if ($notify) {
                $shipment->setEmailSent(true);
            }
            $shipment->getOrder()->setCustomerNoteNotify($appendComment && $notify);
            $shipment->getOrder()->setIsInProcess(true);

            Mage::getModel('core/resource_transaction')
                ->addObject($shipment)
                ->addObject($shipment->getOrder())
                ->save();

            if ($notify) {
                $shipment->sendEmail(true, $appendComment ? $commentText : '');
            }

            return $this->json((int)$shipment->getId());
        } catch (Mage_Core_Exception $e) {
            return $this->jsonError(400, $e->getMessage());
        } catch (Exception $e) {
            Mage::logException($e);
            return $this->jsonError(500, 'Could not save a shipment, see error log for details');
        }
    }

    public function shipmentsSearchAction()
    {
        try {
            $collection = Mage::getModel('sales/order_shipment')->getCollection();
            $searchCriteria = $this->applySearchCriteria(
                $collection,
                array('created_at', 'DESC'),
                array('order_id' => $this->shipmentOrderIdMapper())
            );

            $items = array();
            foreach ($collection as $shipment) {
                $items[] = Mage::getModel('ysrtech_m2api/adapter_shipment')->toSimpleArray($shipment);
            }

            return $this->json(array(
                'items' => $items,
                'search_criteria' => $searchCriteria,
                'total_count' => $collection->getSize()
            ));
        } catch (Exception $e) {
            return $this->searchError($e);
        }
    }

    public function shipmentGetAction($shipmentId)
    {
        // M2 addresses shipments by entity_id; increment_id accepted as a fallback
        $shipment = null;
        if (ctype_digit((string)$shipmentId)) {
            $shipment = Mage::getModel('sales/order_shipment')->load((int)$shipmentId);
        }
        if (!$shipment || !$shipment->getId()) {
            $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);
        }

        if (!$shipment->getId()) {
            return $this->jsonError(404, 'Shipment not found');
        }

        $payload = Mage::getModel('ysrtech_m2api/adapter_shipment')->toArray($shipment);
        return $this->json($payload);
    }

    // --- Utilities ---

    /**
     * Write endpoints are admin-only; customer tokens get a 403.
     */
    protected function requireAdmin()
    {
        if (!$this->_authData || $this->_authData['type'] !== 'admin') {
            $this->jsonError(403, 'Admin token required');
            return false;
        }
        return true;
    }

    /**
     * M2's :orderId is the entity_id. As a convenience an increment_id is
     * accepted too when no order has that entity_id.
     *
     * @return Mage_Sales_Model_Order|null
     */
    protected function loadOrder($orderId)
    {
        $order = null;
        if (ctype_digit((string)$orderId)) {
            $order = Mage::getModel('sales/order')->load((int)$orderId);
        }
        if (!$order || !$order->getId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        }
        return $order->getId() ? $order : null;
    }

    /**
     * Turn an M2-style items list into Magento 1's [order_item_id => qty]
     * map. An empty list means "everything that's still pending". Problems
     * are appended to $errors using M2's validator wording.
     *
     * @param string $mode 'invoice'|'ship'
     */
    protected function buildQtys(Mage_Sales_Model_Order $order, $items, $mode, array &$errors)
    {
        $qtys = array();
        if (empty($items) || !is_array($items)) {
            return $qtys; // service model treats an empty array as "all remaining"
        }

        $orderItems = array();
        foreach ($order->getAllItems() as $item) {
            $orderItems[$item->getId()] = $item;
        }

        $totalQty = 0;
        foreach ($items as $itemData) {
            $itemId = isset($itemData['order_item_id']) ? (int)$itemData['order_item_id'] : 0;
            $qty = isset($itemData['qty']) ? (float)$itemData['qty'] : 0;
            if (!isset($orderItems[$itemId])) {
                $errors[] = $mode === 'invoice'
                    ? 'The invoice contains one or more items that are not part of the original order.'
                    : sprintf('The shipment contains product SKU "%s" that is not part of the original order.', $itemId);
                continue;
            }
            $item = $orderItems[$itemId];
            $available = $mode === 'invoice' ? $item->getQtyToInvoice() : $item->getQtyToShip();
            if ($qty > $available && !$item->isDummy()) {
                $errors[] = $mode === 'invoice'
                    ? sprintf('The quantity to invoice must not be greater than the uninvoiced quantity for product SKU "%s".', $item->getSku())
                    : sprintf('The quantity to ship must not be greater than the unshipped quantity for product SKU "%s".', $item->getSku());
                continue;
            }
            $qtys[$itemId] = $qty;
            $totalQty += $qty;
        }

        if ($totalQty <= 0) {
            $errors[] = $mode === 'invoice'
                ? "The invoice can't be created without products. Add products and try again."
                : "You can't create a shipment without products.";
        }
        return $qtys;
    }

    /**
     * Apply M2-style searchCriteria (filter_groups / sortOrders / pageSize /
     * currentPage) to a collection. Groups are ANDed, filters inside a group
     * are ORed, exactly like Magento 2.
     *
     * @param  Varien_Data_Collection_Db $collection
     * @param  array|null $defaultSort  [field, direction] when no sortOrders given
     * @param  array $valueMappers  field => callable($value) returning [field, value]
     * @return array  the search_criteria block to echo back in the response
     * @throws Mage_Core_Exception on an unusable filter
     */
    protected function applySearchCriteria($collection, $defaultSort = null, array $valueMappers = array())
    {
        $criteria = $this->getRequest()->getParam('searchCriteria', array());
        if (!is_array($criteria)) {
            $criteria = array();
        }

        // No upper cap, like M2: the client decides the page size
        $pageSize = isset($criteria['pageSize']) ? (int)$criteria['pageSize']
            : (isset($criteria['page_size']) ? (int)$criteria['page_size'] : 20);
        $pageSize = max(1, $pageSize);
        $page = isset($criteria['currentPage']) ? (int)$criteria['currentPage']
            : (isset($criteria['current_page']) ? (int)$criteria['current_page'] : 1);
        $page = max(1, $page);

        $isEav = $collection instanceof Mage_Eav_Model_Entity_Collection_Abstract;
        $filterGroups = isset($criteria['filter_groups']) && is_array($criteria['filter_groups'])
            ? $criteria['filter_groups'] : array();

        foreach ($filterGroups as $group) {
            if (empty($group['filters']) || !is_array($group['filters'])) {
                continue;
            }
            $fields = array();
            $conditions = array();
            foreach ($group['filters'] as $filter) {
                if (!is_array($filter) || empty($filter['field'])) {
                    continue;
                }
                $field = (string)$filter['field'];
                $value = isset($filter['value']) ? $filter['value'] : null;
                if (isset($valueMappers[$field])) {
                    list($field, $value) = call_user_func($valueMappers[$field], $value);
                }
                $type = !empty($filter['condition_type']) ? strtolower((string)$filter['condition_type']) : 'eq';
                $condition = $this->buildFilterCondition($type, $value);
                if ($condition === null) {
                    Mage::throwException(sprintf('Unsupported condition_type "%s" for field "%s"', $type, $field));
                }
                $fields[] = $field;
                $conditions[] = $condition;
            }
            if (!$fields) {
                continue;
            }
            if (count($fields) === 1) {
                $collection->addFieldToFilter($fields[0], $conditions[0]);
            } elseif ($isEav) {
                // EAV collections express OR as a list of attribute+condition arrays
                $or = array();
                foreach ($fields as $i => $field) {
                    $or[] = array_merge(array('attribute' => $field), $conditions[$i]);
                }
                $collection->addAttributeToFilter($or);
            } else {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }

        $sorted = false;
        $sortOrders = isset($criteria['sortOrders']) && is_array($criteria['sortOrders'])
            ? $criteria['sortOrders']
            : (isset($criteria['sort_orders']) && is_array($criteria['sort_orders']) ? $criteria['sort_orders'] : array());
        $appliedSorts = array();
        foreach ($sortOrders as $sort) {
            if (!is_array($sort) || empty($sort['field'])) {
                continue;
            }
            $direction = isset($sort['direction']) && strtoupper($sort['direction']) === 'ASC' ? 'ASC' : 'DESC';
            $collection->setOrder((string)$sort['field'], $direction);
            $appliedSorts[] = array('field' => (string)$sort['field'], 'direction' => $direction);
            $sorted = true;
        }
        if (!$sorted && $defaultSort) {
            $collection->setOrder($defaultSort[0], $defaultSort[1]);
        }

        $collection->setPageSize($pageSize)->setCurPage($page);

        return array(
            'filter_groups' => $filterGroups,
            'sort_orders'   => $appliedSorts,
            'page_size'     => $pageSize,
            'current_page'  => $page,
        );
    }

    /**
     * Map an M2 condition_type onto a Magento 1 collection condition.
     *
     * @return array|null  null when the type isn't supported
     */
    protected function buildFilterCondition($type, $value)
    {
        switch ($type) {
            case 'eq': case 'neq': case 'gt': case 'gteq': case 'lt': case 'lteq':
            case 'like': case 'nlike': case 'from': case 'to': case 'finset':
                return array($type => $value);
            case 'moreq':
                return array('gteq' => $value);
            case 'in': case 'nin':
                return array($type => is_array($value) ? $value : explode(',', (string)$value));
            case 'null':
                return array('null' => true);
            case 'notnull':
                return array('notnull' => true);
        }
        return null;
    }

    /**
     * Shipments are filtered by order entity_id in M2, but callers have been
     * seen passing increment_ids; translate those (9+ digits or non-numeric).
     */
    protected function shipmentOrderIdMapper()
    {
        return function ($value) {
            if (is_array($value) || (ctype_digit((string)$value) && strlen((string)$value) < 9)) {
                return array('order_id', $value);
            }
            $order = Mage::getModel('sales/order')->loadByIncrementId($value);
            return array('order_id', $order->getId() ? $order->getId() : 0);
        };
    }

    /**
     * @return array [comment text, is_visible_on_front]
     */
    protected function parseComment(array $data)
    {
        if (empty($data['comment']) || !is_array($data['comment'])) {
            return array('', false);
        }
        $text = isset($data['comment']['comment']) ? trim((string)$data['comment']['comment']) : '';
        $visible = isset($data['comment']['is_visible_on_front']) && $this->toBool($data['comment']['is_visible_on_front']);
        return array($text, $visible);
    }

    protected function toBool($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * A bad filter (unknown field/attribute, unsupported condition) is the
     * caller's fault; anything else is ours.
     */
    protected function searchError(Exception $e)
    {
        if ($e instanceof Mage_Core_Exception || $e instanceof Zend_Db_Statement_Exception) {
            return $this->jsonError(400, $e->getMessage());
        }
        Mage::logException($e);
        return $this->jsonError(500, 'Internal Error. Details are available in Magento log file.');
    }

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
