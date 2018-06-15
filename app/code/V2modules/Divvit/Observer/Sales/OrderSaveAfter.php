<?php

namespace V2modules\Divvit\Observer\Sales;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Message\ManagerInterface;

class OrderSaveAfter implements ObserverInterface {
	
	public function __construct(
		ObjectManagerInterface $objectManager,
		ManagerInterface $messageManager
	) {
		 $this->objectManager = $objectManager;
		 $this->messageManager = $messageManager;		 
	}
	
    public function execute(Observer $observer) {		
		$order = $observer->getEvent()->getOrder();
		if ($order) {

			$currency_code = $order->getOrderCurrencyCode();
			$order_products = array();
			foreach ($order->getAllItems() as $product) {
				$order_products[] = $this->buildProductArray($product, array(), $currency_code);
			}

			$this->messageManager->addNotice( '<script>
				divvit.orderPlaced({
					order: {
						products: '.json_encode($order_products).',
						orderId: "'.$order->getId().'",
						total: "'.$order->getGrandTotal().'",
						currency: "'.$currency_code.'",
						totalProductsNet: "'.count($order_products).'",
						shipping: "'.$order->getBaseShippingInclTax().'",
						customer:{
							idFields: {
								email: "'.$order->getCustomerEmail().'"
							},
							name: "'.$order->getCustomerName().'"
						}
					}
				});
			</script>');

		}       
    }
	public function buildProductArray($product, $extras, $currency_code = '')
    {
        $products = '';
        $product_id = $product->getProductId();
        // product QTY
        $product_qty = 1;
        if (isset($extras['qty'])) {
            $product_qty = $extras['qty'];
        } else {
            $product_qty = $product->getQtyOrdered();
        }
		$_product = $this->objectManager->get('\Magento\Catalog\Model\ProductRepository')->getById($product_id);

        // build product array
        $products = array(
            'id' => $product_id,
            'name' => $product->getName(),
            'category' => $_product->getCategoryIds(),
            'quantity' => $product_qty,
            'price' => number_format($product->getPrice(), '2'),
            'currency' => $currency_code
        );
        return $products;
    }
}