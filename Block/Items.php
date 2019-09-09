<?php

namespace Improntus\Moova\Block;

/**
 * Class Items
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Block
 */
class Items extends \Magento\Shipping\Block\Items
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Improntus\Moova\Model\Webservice
     */
    protected $_moovaWs;

    /**
     * @var \Improntus\Moova\Helper\ShipmentSatus
     */
    protected $_helperShipmentSatus;

    /**
     * @var \Improntus\Moova\Helper\Data
     */
    protected $_helperMoova;

    /**
     * Items constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Improntus\Moova\Model\Webservice $webservice
     * @param \Improntus\Moova\Helper\ShipmentSatus $shipmentSatus
     * @param \Improntus\Moova\Helper\Data $helperMoova
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Improntus\Moova\Model\Webservice $webservice,
        \Improntus\Moova\Helper\ShipmentSatus $shipmentSatus,
        \Improntus\Moova\Helper\Data $helperMoova,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_moovaWs = $webservice;
        $this->_helperShipmentSatus = $shipmentSatus;
        $this->_helperMoova = $helperMoova;
        parent::__construct($context, $registry);
    }

    /**
     * @return string|null
     */
    public function getShipmentMoovaInfo()
    {
        if(!$this->_helperMoova->getHabilitadoMostrarEstadoEnvio()){
            return null;
        }

        $order = $this->getOrder();

        $shipmentId = $this->_helperMoova->getStatusFromUrlTracking($order);
        $trackingInfo = null;

        if(isset($shipmentId))
        {
            $trackingInfo = $this->_moovaWs->trackShipment($shipmentId);
            $trackingInfo = isset($trackingInfo['status']) ? $this->_helperShipmentSatus->getShipmentMessage($trackingInfo['status']) : '';
        }

        return $trackingInfo;
    }
}
