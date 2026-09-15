<?php

class YSRTech_M2API_AuctaneController extends Mage_Core_Controller_Front_Action
{
    public function preDispatch()
    {
        parent::preDispatch();
        
        // Authenticate with Bearer token (like M2 ShipStation API)
        if (!$this->_authenticate()) {
            $this->getResponse()
                ->setHttpResponseCode(401)
                ->setHeader('Content-Type', 'application/json')
                ->setBody(json_encode(['message' => 'Unauthorized']));
            $this->setFlag('', self::FLAG_NO_DISPATCH, true);
        }
    }
    
    protected function _authenticate()
    {
        // ShipStation sends token in custom header: Shipstation-Access-Token
        $token = null;
        
        // Check for ShipStation custom header
        if (isset($_SERVER['HTTP_SHIPSTATION_ACCESS_TOKEN'])) {
            $token = $_SERVER['HTTP_SHIPSTATION_ACCESS_TOKEN'];
        }
        // Fall back to standard Authorization header
        elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            if (stripos($authHeader, 'Bearer ') === 0) {
                $token = trim(substr($authHeader, 7));
            }
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            if (stripos($authHeader, 'Bearer ') === 0) {
                $token = trim(substr($authHeader, 7));
            }
        }
        
        if (!$token) {
            return false;
        }
        
        // Validate token using our Auth model
        $validationResult = Mage::getModel('ysrtech_m2api/auth')->validateToken($token);
        
        if (!$validationResult || !isset($validationResult['user_id'])) {
            return false;
        }
        
        // Only allow admin tokens for ShipStation API
        if ($validationResult['type'] !== 'admin') {
            return false;
        }
        
        return true;
    }
    
    public function indexAction()
    {
        $action = $this->getRequest()->getParam('action');
        
        // Log incoming request
        $requestLog = array(
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $this->getRequest()->getMethod(),
            'uri' => $this->getRequest()->getRequestUri(),
            'action' => $action,
            'params' => $this->getRequest()->getParams(),
            'ip' => $this->getRequest()->getClientIp()
        );
        
        // If no action provided, return success (ping test)
        if (!$action) {
            $this->getResponse()
                ->setHeader('Content-Type', 'text/plain')
                ->setBody('ShipStation API is active');
            $this->_logRequestResponse($requestLog, 'ShipStation API is active', 200);
            return;
        }
        
        switch ($action) {
            case 'export':
                $this->exportAction();
                break;
            case 'shipnotify':
                $this->shipNotifyAction();
                break;
            default:
                $this->getResponse()
                    ->setHttpResponseCode(400)
                    ->setBody('Invalid action');
                $this->_logRequestResponse($requestLog, 'Invalid action', 400);
        }
    }
    
    protected function _logRequestResponse($request, $responseBody, $statusCode = 200)
    {
        $logData = array(
            'request' => $request,
            'response' => array(
                'status' => $statusCode,
                'body_preview' => substr($responseBody, 0, 500) . (strlen($responseBody) > 500 ? '...[truncated]' : '')
            )
        );
        
        Mage::log(print_r($logData, true), null, 'm2api_auctane.log', true);
    }
    
    protected function exportAction()
    {
        // Get parameters
        $startDate = $this->getRequest()->getParam('start_date');
        $endDate = $this->getRequest()->getParam('end_date');
        $page = max(1, (int)$this->getRequest()->getParam('page', 1));
        $pageSize = 100; // ShipStation typically uses 100 per page
        
        // Convert date format from m/d/Y H:i to MySQL format
        if ($startDate) {
            $startDate = date('Y-m-d H:i:s', strtotime($startDate));
        }
        if ($endDate) {
            $endDate = date('Y-m-d H:i:s', strtotime($endDate));
        }
        
        // Get orders (exclude wholesale customer group)
        $collection = Mage::getModel('sales/order')->getCollection()
            ->addAttributeToSelect('*')
            ->setPageSize($pageSize)
            ->setCurPage($page)
            ->setOrder('created_at', 'DESC');
        
        if ($startDate) {
            $collection->addFieldToFilter('updated_at', array('gteq' => $startDate));
        }
        if ($endDate) {
            $collection->addFieldToFilter('updated_at', array('lteq' => $endDate));
        }
        
        // Exclude wholesale orders (group ID 2)
        $collection->addFieldToFilter('customer_group_id', array('neq' => 2));
        
        // Build XML response
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><Orders></Orders>');
        
        foreach ($collection as $order) {
            $this->addOrderToXml($xml, $order);
        }
        
        $xmlBody = $xml->asXML();
        
        $this->getResponse()
            ->setHeader('Content-Type', 'application/xml; charset=utf-8')
            ->setBody($xmlBody);
        
        // Log request/response
        $requestLog = array(
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => 'GET',
            'uri' => $this->getRequest()->getRequestUri(),
            'action' => 'export',
            'params' => array(
                'start_date' => $startDate,
                'end_date' => $endDate,
                'page' => $page
            ),
            'orders_count' => $collection->getSize()
        );
        $this->_logRequestResponse($requestLog, $xmlBody, 200);
    }
    
    protected function addOrderToXml($xml, $order)
    {
        $orderNode = $xml->addChild('Order');
        
        // Basic order info
        $orderNode->addChild('OrderNumber', $order->getIncrementId());
        $orderNode->addChild('OrderDate', date('c', strtotime($order->getCreatedAt())));
        $orderNode->addChild('OrderStatus', $order->getStatus());
        $orderNode->addChild('LastModified', date('c', strtotime($order->getUpdatedAt())));
        
        $orderNode->addChild('ShippingMethod', $order->getShippingDescription());
        $orderNode->addChild('PaymentMethod', $order->getPayment()->getMethodInstance()->getTitle());
        
        $orderNode->addChild('OrderTotal', number_format($order->getGrandTotal(), 2, '.', ''));
        $orderNode->addChild('TaxAmount', number_format($order->getTaxAmount(), 2, '.', ''));
        $orderNode->addChild('ShippingAmount', number_format($order->getShippingAmount(), 2, '.', ''));
        $orderNode->addChild('CustomerNotes', $order->getCustomerNote());
        $orderNode->addChild('InternalNotes', '');
        
        // Customer info
        $customer = $orderNode->addChild('Customer');
        $customer->addChild('CustomerCode', $order->getCustomerId() ?: 'guest');
        
        $billTo = $customer->addChild('BillTo');
        $this->addAddressToXml($billTo, $order->getBillingAddress());
        
        $shipTo = $customer->addChild('ShipTo');
        $this->addAddressToXml($shipTo, $order->getShippingAddress());
        
        // Items
        $items = $orderNode->addChild('Items');
        foreach ($order->getAllVisibleItems() as $item) {
            // Exclude refunded/canceled quantity so ShipStation only sees what's still owed
            $netQty = $item->getQtyOrdered() - $item->getQtyRefunded() - $item->getQtyCanceled();
            if ($netQty <= 0) {
                continue;
            }

            $itemNode = $items->addChild('Item');
            $itemNode->addChild('SKU', $item->getSku());
            $itemNode->addChild('Name', htmlspecialchars($item->getName()));
            $itemNode->addChild('ImageUrl', '');
            $itemNode->addChild('Weight', $item->getWeight() ?: 0);
            $itemNode->addChild('WeightUnits', 'Pounds');
            $itemNode->addChild('Quantity', (int)$netQty);
            $itemNode->addChild('UnitPrice', number_format($item->getPrice(), 2, '.', ''));

            $options = $itemNode->addChild('Options');
            // Add product options if needed
        }
        
        return $orderNode;
    }
    
    protected function addAddressToXml($node, $address)
    {
        if (!$address) {
            return;
        }
        
        $node->addChild('Name', htmlspecialchars($address->getName()));
        $node->addChild('Company', htmlspecialchars($address->getCompany()));
        $node->addChild('Phone', $address->getTelephone());
        $node->addChild('Email', $address->getEmail());
        
        $street = $address->getStreet();
        $node->addChild('Address1', isset($street[0]) ? htmlspecialchars($street[0]) : '');
        $node->addChild('Address2', isset($street[1]) ? htmlspecialchars($street[1]) : '');
        $node->addChild('City', htmlspecialchars($address->getCity()));
        $node->addChild('State', $address->getRegionCode());
        $node->addChild('PostalCode', $address->getPostcode());
        $node->addChild('Country', $address->getCountryId());
    }
    
    protected function shipNotifyAction()
    {
        $xmlString = $this->getRequest()->getRawBody();
        Mage::log("shipNotify raw body: " . $xmlString, null, 'm2api_auctane.log', true);

        try {
            $xml = new SimpleXMLElement($xmlString);
            
            $orderNumber = (string)$xml->OrderNumber;
            $carrier = (string)$xml->Carrier;
            $service = (string)$xml->Service;
            $trackingNumber = (string)$xml->TrackingNumber;
            $notifyCustomer = (string)$xml->NotifyCustomer === 'true';
            
            // Load order
            $order = Mage::getModel('sales/order')->loadByIncrementId($orderNumber);
            
            if (!$order->getId()) {
                throw new Exception('Order not found');
            }
            
            // Build qty array from ShipStation's Items list (partial shipment support)
            // ShipStation sends <Items><Item><SKU>...</SKU><Quantity>N</Quantity></Item></Items>
            $qtys = array();
            $shippedSkus = array();
            if (isset($xml->Items) && $xml->Items->Item) {
                foreach ($xml->Items->Item as $xmlItem) {
                    $sku = trim((string)$xmlItem->SKU);
                    $qty = (int)$xmlItem->Quantity;
                    if ($sku && $qty > 0) {
                        $shippedSkus[$sku] = $qty;
                    }
                }
            }

            foreach ($order->getAllItems() as $item) {
                if ($item->getParentItemId()) {
                    continue;
                }
                $qtyToShip = $item->getQtyToShip();
                if ($qtyToShip <= 0) {
                    continue;
                }
                if (!empty($shippedSkus)) {
                    // Partial shipment: only ship what ShipStation says
                    $sku = $item->getSku();
                    if (isset($shippedSkus[$sku])) {
                        $qtys[$item->getId()] = min($shippedSkus[$sku], $qtyToShip);
                    }
                } else {
                    // No items in XML (legacy/full shipment): ship everything remaining
                    $qtys[$item->getId()] = $qtyToShip;
                }
            }

            $canShip = $order->canShip();
            $existingShipments = $order->getShipmentsCollection()->getSize();

            Mage::log(
                "shipNotify order={$orderNumber} canShip=" . ($canShip ? 'true' : 'false')
                . " existingShipments={$existingShipments} qtyItems=" . count($qtys)
                . " partial=" . (!empty($shippedSkus) ? 'true skus=' . json_encode($shippedSkus) : 'false'),
                null, 'm2api_auctane.log', true
            );

            if ($canShip && !empty($qtys)) {
                // Auto-invoice if needed so shipment can be created
                if ($order->canInvoice()) {
                    $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice($qtys);
                    $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE);
                    $invoice->register();
                    $invoice->getOrder()->setIsInProcess(true);
                    Mage::getModel('core/resource_transaction')
                        ->addObject($invoice)
                        ->addObject($invoice->getOrder())
                        ->save();
                    Mage::log("shipNotify order={$orderNumber} partial invoice created offline capture qtys=" . json_encode($qtys), null, 'm2api_auctane.log', true);
                }

                $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($qtys);

                if ($trackingNumber) {
                    $track = Mage::getModel('sales/order_shipment_track')
                        ->setNumber($trackingNumber)
                        ->setCarrierCode(strtolower($carrier))
                        ->setTitle($service ?: $carrier);
                    $shipment->addTrack($track);
                }

                $shipment->register();
                if ($notifyCustomer) {
                    $shipment->setEmailSent(1);
                }
                $shipment->getOrder()->setIsInProcess(true);

                // Set partial status if items remain unshipped after this shipment
                // Use qty_ordered vs qty_shipped (after register() has updated qty_to_ship)
                $remainingQty = 0;
                foreach ($order->getAllItems() as $item) {
                    if (!$item->getParentItemId()) {
                        $remainingQty += max(0, $item->getQtyOrdered() - $item->getQtyShipped());
                    }
                }
                Mage::log("shipNotify order={$orderNumber} remainingQty={$remainingQty}", null, 'm2api_auctane.log', true);
                if ($remainingQty > 0) {
                    $order->setStatus('partially_shipped');
                    $order->addStatusHistoryComment('Order partially shipped by ShipStation', 'partially_shipped');
                }

                Mage::getModel('core/resource_transaction')
                    ->addObject($shipment)
                    ->addObject($shipment->getOrder())
                    ->save();

                if ($notifyCustomer) {
                    $shipment->sendEmail(true);
                }

                Mage::log("shipNotify order={$orderNumber} shipment created successfully, notifyCustomer=" . ($notifyCustomer ? 'true' : 'false'), null, 'm2api_auctane.log', true);

            } elseif ($existingShipments > 0) {
                // Order already shipped â€” add tracking to existing shipment
                $shipment = $order->getShipmentsCollection()->getFirstItem();

                if ($trackingNumber) {
                    Mage::getModel('sales/order_shipment_track')
                        ->setShipment($shipment)
                        ->setData('title', $carrier)
                        ->setData('number', $trackingNumber)
                        ->setData('carrier_code', strtolower($carrier))
                        ->setData('order_id', $shipment->getData('order_id'))
                        ->save();
                    Mage::log("shipNotify order={$orderNumber} tracking added to existing shipment", null, 'm2api_auctane.log', true);
                }
            } else {
                Mage::log("shipNotify order={$orderNumber} SKIPPED: canShip={$canShip} qtys=" . json_encode($qtys), null, 'm2api_auctane.log', true);
                throw new Exception("Cannot ship order {$orderNumber}: canShip=" . ($canShip ? 'true' : 'false') . ", shippable items=" . count($qtys));
            }
            
            $responseBody = '<?xml version="1.0" encoding="utf-8"?><ShipNotifyResponse><Success>true</Success></ShipNotifyResponse>';
            
            $this->getResponse()
                ->setHeader('Content-Type', 'application/xml; charset=utf-8')
                ->setBody($responseBody);
            
            // Log request/response
            $requestLog = array(
                'timestamp' => date('Y-m-d H:i:s'),
                'method' => 'POST',
                'uri' => $this->getRequest()->getRequestUri(),
                'action' => 'shipnotify',
                'order' => $orderNumber,
                'tracking' => $trackingNumber
            );
            $this->_logRequestResponse($requestLog, $responseBody, 200);
                
        } catch (Exception $e) {
            $responseBody = '<?xml version="1.0" encoding="utf-8"?><ShipNotifyResponse><Success>false</Success><Message>' . htmlspecialchars($e->getMessage()) . '</Message></ShipNotifyResponse>';
            
            $this->getResponse()
                ->setHeader('Content-Type', 'application/xml; charset=utf-8')
                ->setBody($responseBody);
            
            // Log request/response with error
            $requestLog = array(
                'timestamp' => date('Y-m-d H:i:s'),
                'method' => 'POST',
                'uri' => $this->getRequest()->getRequestUri(),
                'action' => 'shipnotify',
                'error' => $e->getMessage()
            );
            $this->_logRequestResponse($requestLog, $responseBody, 500);
        }
    }
}
