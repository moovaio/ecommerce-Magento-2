<?php
namespace Improntus\Moova\Block\Adminhtml\Order\View;

/**
 * Class Info
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Block\Adminhtml\Order\View
 */
class Info extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
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
     * Popup
     *
     * @var \Improntus\Moova\Block\Tracking\Popup
     */
    private $popup;

    /**
     * Info constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Improntus\Moova\Model\Webservice $webservice
     * @param \Improntus\Moova\Helper\ShipmentSatus $shipmentSatus
     * @param \Improntus\Moova\Helper\Data $helperMoova
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Improntus\Moova\Model\Webservice $webservice,
        \Improntus\Moova\Helper\ShipmentSatus $shipmentSatus,
        \Improntus\Moova\Helper\Data $helperMoova,
        array $data = []
    ) {
        $this->_moovaWs = $webservice;
        $this->_helperShipmentSatus = $shipmentSatus;
        $this->_helperMoova = $helperMoova;
        parent::__construct($context, $registry, $adminHelper, $data);
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
