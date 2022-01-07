<?php

namespace Improntus\Moova\Observer;

use Magento\Framework\Event\ObserverInterface;
use Improntus\Moova\Helper\Log;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class SalesEventQuoteSubmitBeforeObserver
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Ids\Andreani\Observer
 */
class SalesOrderSaveAfter implements ObserverInterface
{

    /**
     *@var \Improntus\Moova\Model\Moova
     */
    protected $_moova;
    protected $orderRepository;
    protected $_moovaWs;
    protected $_helperMoova;
    protected $_scopeConfig;

    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Improntus\Moova\Helper\Data $helperMoova,
        \Improntus\Moova\Model\Webservice $webservice,
        ScopeConfigInterface $scopeConfig,
        \Improntus\Moova\Model\Moova $moova
        ) {
        $this->orderRepository = $orderRepository;
        $this->_helperMoova = $helperMoova;
        $this->_moovaWs = $webservice;
        $this->_moova = $moova;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $isMoovaShipping =  $order->getShippingMethod() == \Improntus\Moova\Model\Carrier\Moova::CARRIER_CODE . '_' 
        . \Improntus\Moova\Model\Carrier\Moova::CARRIER_CODE;

        if(!$isMoovaShipping){
            return $this;
        }

        $trackingNumber = $this->_helperMoova->getStatusFromUrlTracking($order);
        if ($this->canCreateInMoova($order)) {
            Log::info("Creating the shipping automatically");
            $this->_moova->doShipment($order);
        }

        if($this->canSetStatusReady($order)){
            $this->setStatusReady($order);
        }

        return $this;
    }

    private function canCreateInMoova($order){
        $trackingNumber = $this->_helperMoova->getStatusFromUrlTracking($order);
        $currentMagentoStatus = $order->getStatus();
        $createOrderStatus = $this->_scopeConfig->getValue("shipping/moova_webservice/moova_send_status/create_order_status", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);        
        Log::info("Tracking number: $trackingNumber createOrderStatus: ". $createOrderStatus. ". currentMagentoStatus : " . $currentMagentoStatus);
        return empty($trackingNumber) && ($createOrderStatus == $currentMagentoStatus);
    }

    private function canSetStatusReady($order){
        $trackingNumber = $this->_helperMoova->getStatusFromUrlTracking($order);
        if(empty($trackingNumber)){
            return false;
        }

        $statusToReady = $this->_scopeConfig->getValue("shipping/moova_webservice/moova_send_status/send_ready_status", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);        
        $currentMagentoStatus = $order->getStatus();
        $trackingInfo = $this->_moovaWs->trackShipment($trackingNumber);
        $moovaStatus = $trackingInfo['status'];
        Log::info("Tracking number: $trackingNumber statusToReady: ". $statusToReady. ". currentMagentoStatus : " . $currentMagentoStatus);
        return $moovaStatus === 'DRAFT' && $statusToReady == $currentMagentoStatus;

    }

    private function setStatusReady($order){
        $trackingNumber = $this->_helperMoova->getStatusFromUrlTracking($order);
        Log::info("Setting to ready automatically $trackingNumber");
        $this->_moovaWs->sendStatusShipment($trackingNumber, 'READY');
    }
}
