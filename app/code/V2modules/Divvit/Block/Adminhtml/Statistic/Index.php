<?php
/**
 * Copyright © 2015 V2modules . All rights reserved.
 */
namespace V2modules\Divvit\Block\Adminhtml\Statistic;

use Magento\Backend\Block\Template;
use Magento\Backend\App\Action;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Index extends Template
{
	public static $TRACKER_URL ='https://tracker.divvit.com';
    public static $APP_URL = 'https://app.divvit.com';
    public static $TAG_URL = 'https://tag.divvit.com';
	protected $scope=\Magento\Store\Model\ScopeInterface::SCOPE_STORE;
	public function __construct(
		Template\Context $context,
		Action\Context $context2, 
		TimezoneInterface $timezone,
		PriceCurrencyInterface $priceCurrency,
		UrlInterface $backendUrl,
		ScopeConfigInterface $scopeConfig,
		array $data = [])
    {
		parent::__construct($context, $data);
		$this->tracker_url=self::$TRACKER_URL ;
        $this->app_url=self::$APP_URL ;
        $this->tag_url=self::$TAG_URL ;
		$user= $context2->getAuth()->getUser();
		$this->userdata=$user->getData();
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');				
		$this->userdata['url'] = $storeManager->getStore()->getBaseUrl();
		$this->userdata['timezone'] =$timezone->getConfigTimezone();
		$this->userdata['currency']=$priceCurrency->getCurrency()->getCurrencyCode();
		$this->secure_key=md5('divvit');
		$this->backendUrl=$backendUrl->getUrl('divvit/statistic/index');
		$this->_scopeConfig = $scopeConfig;
	}
	
	public function is_accesss()
    {
		$merchant=$this->_scopeConfig->getValue('divvit/general/merchan_id',$this->scope);
		$token=$this->_scopeConfig->getValue('divvit/general/token',$this->scope);
		if ($merchant and $token){
			$this->_merchant=$merchant;
			$this->_token=$token;
			return true;
		}
		return false;
	}
	public static function getDivvitCookie()
    {
        $realCookie = ${'_COOKIE'};
        if (isset($realCookie['DV_TRACK'])) {
            return $realCookie['DV_TRACK'];
        } else {
            return false;
        }
    }
}
