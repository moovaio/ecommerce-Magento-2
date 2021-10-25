<?php

namespace Improntus\Moova\Observer;

use Magento\Framework\Event\ObserverInterface;
use Improntus\Moova\Helper\Log;

/**
 * Class SalesEventQuoteSubmitBeforeObserver
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Ids\Andreani\Observer
 */
class CheckoutSubmitAllAfter implements ObserverInterface
{

    /**
     *@var \Improntus\Moova\Model\Moova
     */
    protected $_moova;


    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * SalesOrderPlaceBefore constructor.
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Improntus\Moova\Model\Moova $moova
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_moova = $moova;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if (
            $order->getShippingMethod() == \Improntus\Moova\Model\Carrier\Moova::CARRIER_CODE . '_'
            . \Improntus\Moova\Model\Carrier\Moova::CARRIER_CODE
        ) {
            $orderId = $order->getId();
            Log::info("Creating the shipping automatically $orderId ");
            $this->_moova->doShipment($order);
        }

        return $this;
    }
}
