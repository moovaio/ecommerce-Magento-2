<?php

namespace Improntus\Moova\Block\Tracking;

use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;

/**
 * Class Popup
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Block\Tracking
 */
class Popup extends \Magento\Shipping\Block\Tracking\Popup
{
    /**
     * @var \Improntus\Moova\Model\Webservice
     */
    protected $_moovaWs;

    /**
     * Popup constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param DateTimeFormatterInterface $dateTimeFormatter
     * @param \Improntus\Moova\Model\Webservice $webservice
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        DateTimeFormatterInterface $dateTimeFormatter,
        \Improntus\Moova\Model\Webservice $webservice,
        array $data = []
    )
    {
        $this->_moovaWs = $webservice;

        parent::__construct($context, $registry, $dateTimeFormatter, $data);
    }

    /**
     * @return string|null
     */
    public function getShipmentMoovaInfo()
    {
        $ws = null;

        foreach ($this->getTrackingInfo() as $_track)
        {
            foreach ($_track as $counter => $track)
            {
                $shipmentId = $track->getArguments()[0];
            }
        }

        if(isset($shipmentId))
        {
            $ws = $this->_moovaWs->trackShipment($shipmentId);
        }

        return $ws;
    }
}
