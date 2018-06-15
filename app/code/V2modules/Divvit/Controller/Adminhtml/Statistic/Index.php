<?php
/**
 *
 * Copyright © 2015 V2modulescommerce. All rights reserved.
 */
namespace V2modules\Divvit\Controller\Adminhtml\Statistic;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Config\Storage\WriterInterface;
use V2modules\Divvit\Block\Adminhtml\Statistic\Index as Divvit;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Backend\Model\UrlInterface;

class Index extends \Magento\Backend\App\Action
{	
	protected $scope=\Magento\Store\Model\ScopeInterface::SCOPE_STORE;

	/**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
		WriterInterface $configWriter,
		ScopeConfigInterface $scopeConfig,
		UrlInterface $backendUrl
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
		$this->_configWriter = $configWriter;
		$this->_scopeConfig = $scopeConfig;
		$this->backendUrl=$backendUrl->getUrl('divvit/statistic/index');
    }
    /**
     * Check the permission to run it
     *
     * @return bool
     */
   /*  protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magento_Cms::page');
    } */

    /**
     * Index action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
		$post = $this->getRequest()->getPostValue();
		if (isset($post['action']) and isset($post['secure_key']) and $post['secure_key']==md5('divvit')) {
			switch ($post['action']){
				case 'updateFrontendId' : return $this->updateFrontendId();
				case 'resetFrontendId' : return $this->resetFrontendId();
				default : /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
						$resultPage = $this->resultPageFactory->create();
						return $resultPage;
			}
		}
		/** @var \Magento\Backend\Model\View\Result\Page $resultPage */
		$resultPage = $this->resultPageFactory->create();
		return $resultPage;

    }
	
	protected function updateFrontendId(){
		$post = $this->getRequest()->getPostValue();
		if (isset($post['frontendId']))
			$this->_configWriter->save('divvit/general/merchan_id', $post['frontendId'], 'default', 0);
		$this->getDivvitAuthToken();
		
		$resultPage = $this->resultPageFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
		return $resultPage;
	}
	
	protected function resetFrontendId(){
		$this->_configWriter->save('divvit/general/merchan_id', '', 'default', 0);
		$this->_configWriter->save('divvit/general/token', '', 'default', 0);
		$resultPage = $this->resultPageFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_JSON);
		return $resultPage;
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
}
