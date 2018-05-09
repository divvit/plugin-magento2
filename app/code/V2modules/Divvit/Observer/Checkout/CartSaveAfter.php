<?php

namespace V2modules\Divvit\Observer\Checkout;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use V2modules\Divvit\Block\Adminhtml\Statistic\Index as Divvit;
use Magento\Customer\Model\Session;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Message\ManagerInterface;

class CartSaveAfter implements ObserverInterface {
	
	public function __construct(
		Session $customerSession,
		ObjectManagerInterface $objectManager,
		ManagerInterface $messageManager
	) {
		 $this->customerSession = $customerSession;
		 $this->objectManager = $objectManager;
		 $this->messageManager = $messageManager;
	}
	
    public function execute(Observer $observer) {		
		$cart = $observer->getEvent()->getCart();		
		if ($cart and $cart->getItems()) {
			 /**
			 * start the tracking
			 */
			$cookieDivvit = Divvit::getDivvitCookie();
			if (!$cookieDivvit) {
				return;
			}		
			
			$scopeConfig = $this->objectManager->get('\Magento\Framework\App\Config\ScopeConfigInterface');
			$merchan_id =$scopeConfig->getValue('divvit/general/merchan_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

			$tracking = Divvit::$TRACKER_URL.'/track.js?i='.$merchan_id . '&e=cart&v=1.0.0&uid=' . $cookieDivvit . '';

			
			$metaInfo = '{"cartId":"' . $cart->getQuote()->getId() . '"';
			$metaInfo .= ',"products":[';
			
			$currencysymbol = $this->objectManager->get('Magento\Store\Model\StoreManagerInterface');
			$currency_code = $currencysymbol->getStore()->getCurrentCurrencyCode();
			
			$tmpArray = array();
			foreach ($cart->getItems() as $product) {
				$tmpArray[] = json_encode($this->buildProductArray($product, array(), $currency_code));
			}
			$metaInfo .= join(",", $tmpArray);

			$metaInfo .= ']}';

			$tracking .= '&m=' . urlencode($metaInfo);
			
			$this->messageManager->addNotice(file_get_contents($tracking));
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

?>