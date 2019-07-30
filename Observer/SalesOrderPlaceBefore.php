<?php
namespace Improntus\Moova\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Class SalesEventQuoteSubmitBeforeObserver
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Ids\Andreani\Observer
 */
class SalesOrderPlaceBefore implements ObserverInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * SalesOrderPlaceBefore constructor.
     * @param \Magento\Checkout\Model\Session $checkoutSession
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $moovaQuoteId = $this->_checkoutSession->getMoovaQuoteId();
        $order = $observer->getEvent()->getOrder();

        if($order->getShippingMethod() == \Improntus\Moova\Model\Carrier\Moova::CARRIER_CODE . '_'
            . \Improntus\Moova\Model\Carrier\Moova::CARRIER_CODE)
        {
            $order->setMoovaQuoteId($moovaQuoteId);
        }

        return $this;
    }
}
