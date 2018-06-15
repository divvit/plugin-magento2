<?php
/**
 * import product to divvit
 * Copyright (C) 2017  V2modules
 * 
 * This file is part of V2modules/Divvit.
 * 
 * V2modules/Divvit is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace V2modules\Divvit\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use V2modules\Divvit\Block\Adminhtml\Statistic\Index as Divvit;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Request\Http;

class Index extends Action
{
	const LIMIT_ORDER = 100;
	protected $scope=\Magento\Store\Model\ScopeInterface::SCOPE_STORE;
	public function __construct(
		Context $context,
		ScopeConfigInterface $scopeConfig,
		WriterInterface $configWriter,
		UrlInterface $backendUrl,
		Http $request,
		array $data = []
	) {
		$this->_scopeConfig = $scopeConfig; 
		$this->_configWriter = $configWriter;
		$this->request = $request;
		$this->backendUrl=$backendUrl->getUrl('divvit/statistic/index');
		$this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		parent::__construct($context);
	}

	public function execute()
	{ 
		 $accessToken = $this->validateToken();
        if ($accessToken) {
            $afterOrderId = $this->request->getParam("after", 0);
            $orders = $this->getOrders($afterOrderId);
			$result = $this->resultJsonFactory->create();
			$result->setData($orders);
            return $result;
        } else {
			$resultPage = $this->resultPageFactory->create();
			$resultPage->setHeader('Status', 'UNAUTHORIZED');
			$resultPage->setHttpResponseCode(401);
			return $resultPage;
        }
	}
	
	private function getallheaders()
    {
        if (function_exists('getallheaders')) {
            return getallheaders();
        } else {
            $headers = array();
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $formattedText = ucwords(strtolower(str_replace('_', ' ', substr($name, 5))));
                    $key = str_replace(' ', '-', $formattedText);
                    $headers[$key] = $value;
                }
            }
            return $headers;
        }
    }
	
	private function validateToken()
    {
        $accessToken =$this->_scopeConfig->getValue('divvit/general/token',$this->scope);
        if (!$accessToken) {
            $this->getDivvitAuthToken();
            $accessToken = $this->_scopeConfig->getValue('divvit/general/token',$this->scope);
        }
        $headers = $this->getallheaders();
        if (isset($headers['Authorization'])) {
            if ($headers['Authorization'] == sprintf("token %s", $accessToken)) {
                return $accessToken;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
	protected function getDivvitAuthToken()
    {
        $url = Divvit::$TRACKER_URL.'/auth/register';
        $moduleUrl = $this->backendUrl;
        $params = array(
            'frontendId' => $this->_scopeConfig->getValue('divvit/general/merchan_id',$this->scope),
            'url' => $moduleUrl
        );
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: '.strlen(json_encode($params))
        ));

        // Disable SSL check for DEV environment
        if (getenv('DIVVIT_DEV')) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $resultStr = curl_exec($ch);

        $result = json_decode($resultStr, true);
        if ($result and isset($result['accessToken'])) {
            $this->_configWriter->save('divvit/general/token', $result['accessToken'], 'default', 0);
        } 
        curl_close($ch);
    }
	protected function getOrders($afterId) {
		$resource     = $this->objectManager->get(\Magento\Framework\App\ResourceConnection::class);
		$readAdapter   = $resource->getConnection('core_read');
		$orderstable=$resource->getTableName('sales_order');
		$sql = "SELECT  increment_id FROM {$orderstable}
            WHERE  increment_id > ".(int)$afterId." ORDER BY  increment_id DESC LIMIT ".self::LIMIT_ORDER;
        $orderArr = $readAdapter->fetchAll($sql);
        $orderIds = array();
        foreach ($orderArr as $oa) {
            $orderIds[] = $oa['increment_id'];
        }
        $dataReturn = array();
        foreach ($orderIds as $orderId) {
			$order = $this->objectManager->get('\Magento\Sales\Model\Order')->load($orderId);
            $customer = $order->getCustomerId();
            $currency = $order->getOrderCurrencyCode();
			$cookiestable=$resource->getTableName('v2modules_divvit_customer_cookie');
			$query= "SELECT * FROM {$cookiestable} WHERE customer_id = ".(int)$customer;
			$customer_cookies= $readAdapter->fetchAll($query);
			if ($customer_cookies){
				foreach ($customer_cookies as $c_cook){
					$orderData = array(
						'uid' => $c_cook['cookie_data'],
						'createdAt' => $order->getCreatedAt(),
						'orderId' => $order->getId(),
						'total' => $order->getGrandTotal(),
						'totalProductsNet' => $order->getTotalItemCount(),
						'shipping' => $order->getBaseShippingInclTax(),
						'currency' => $currency,
						'customer' => array(
							'name' => $order->getCustomerName(),
							'id' => $customer,
							'idFields' => array(
								'email' => $order->getCustomerEmail()
							)
						)
					);
				}
				$productsArr = array();
				foreach ( $order->getAllVisibleItems() as $product) {
					$productsArr[] = array(
						'id' => $product->getProductId(),
						'name' => $product->getName(),
						'price' => $product->getPrice(),
						'currency' => $currency,
						'quantity' => $product->getQtyOrdered()
					);
				}
				$orderData['products'] = $productsArr;
				$dataReturn[] = $orderData;
			}
        }
        return $dataReturn;
		
	}
}
