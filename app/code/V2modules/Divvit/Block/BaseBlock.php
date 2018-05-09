<?php
/**
 * Copyright © 2015 V2modules . All rights reserved.
 */
namespace V2modules\Divvit\Block;
use Magento\Framework\UrlFactory;

class BaseBlock extends \Magento\Framework\View\Element\Template
{
	/**
     * @var \V2modules\Divvit\Helper\Data
     */
	 protected $_devToolHelper;
	 
	 /**
     * @var \Magento\Framework\Url
     */
	 protected $_urlApp;
	 
	 /**
     * @var \V2modules\Divvit\Model\Config
     */
    protected $_config;

    /**
     * @param \V2modules\Divvit\Block\Context $context
	 * @param \Magento\Framework\UrlFactory $urlFactory
     */
    public function __construct( \V2modules\Divvit\Block\Context $context
	)
    {
        $this->_devToolHelper = $context->getDivvitHelper();
		$this->_config = $context->getConfig();
        $this->_urlApp=$context->getUrlFactory()->create();
		parent::__construct($context);
	
    }
	
	/**
	 * Function for getting event details
	 * @return array
	 */
    public function getEventDetails()
    {
		return  $this->_devToolHelper->getEventDetails();
    }
	
	/**
     * Function for getting current url
	 * @return string
     */
	public function getCurrentUrl(){
		return $this->_urlApp->getCurrentUrl();
	}
	
	/**
     * Function for getting controller url for given router path
	 * @param string $routePath
	 * @return string
     */
	public function getControllerUrl($routePath){
		
		return $this->_urlApp->getUrl($routePath);
	}
	
	/**
     * Function for getting current url
	 * @param string $path
	 * @return string
     */
	public function getConfigValue($path){
		return $this->_config->getCurrentStoreConfigValue($path);
	}
	
	/**
     * Function canShowDivvit
	 * @return bool
     */
	public function canShowDivvit(){
		$isEnabled=$this->getConfigValue('divvit/module/is_enabled');
		if($isEnabled)
		{
			$allowedIps=$this->getConfigValue('divvit/module/allowed_ip');
			 if(is_null($allowedIps)){
				return true;
			}
			else {
				$remoteIp=$_SERVER['REMOTE_ADDR'];
				if (strpos($allowedIps,$remoteIp) !== false) {
					return true;
				}
			}
		}
		return false;
	}
	
}
