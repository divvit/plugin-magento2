<?php
/**
 * Copyright © 2015 V2modules . All rights reserved.
 */
namespace V2modules\Divvit\Block\Frontend;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use V2modules\Divvit\Block\Adminhtml\Statistic\Index as Divvit;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Customer\Model\Session;

class Allpage extends Template
{
	protected $scope=\Magento\Store\Model\ScopeInterface::SCOPE_STORE;
	public function __construct(
		Template\Context $context,
		ScopeConfigInterface $scopeConfig,
		ModuleListInterface $moduleList,
		Session $customerSession,
		array $data = []
	){
		parent::__construct($context, $data);
		$this->_scopeConfig=$scopeConfig;
		$this->merchant=$this->_scopeConfig->getValue('divvit/general/merchan_id',$this->scope);
		$this->tag_url=Divvit::$TAG_URL;
		$moduleInfo=$moduleList->getOne('V2modules_Divvit');
		$this->version=$moduleInfo['setup_version'];
		if ($userid=$customerSession->getCustomer()->getId()) {
            $this->saveCustomerCookie($userid);
        }
	}
	
	protected function saveCustomerCookie($customerId)
    {
        $cookieDivvit = Divvit::getDivvitCookie();
        if ($cookieDivvit) {
			$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
			$resource     = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
			$readAdapter  = $resource->getConnection('core_read');
			$writeAdapter = $resource->getConnection('core_read');
			$cookiestable=$resource->getTableName('v2modules_divvit_customer_cookie');
            $sql = "SELECT id FROM {$cookiestable} WHERE customer_id = ".(int)$customerId;
			$data =$readAdapter->fetchAll($sql);
            if (!$data) {			
                $query = "INSERT INTO {$cookiestable} SET "
                    ."customer_id = ".(int)$customerId.", "
                    ."cookie_data = '".$cookieDivvit."', updated_at = NOW(), created_at = NOW()";
            } else {
                $query = "UPDATE {$cookiestable} SET "
                    ."updated_at = NOW(), cookie_data = '".$cookieDivvit."' "
                    ."WHERE customer_id = ".(int)$customerId;
            }
			$writeAdapter->query($query);
        }
    }
}