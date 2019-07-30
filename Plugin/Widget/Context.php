<?php
namespace Improntus\Moova\Plugin\Widget;

use Magento\Backend\Block\Widget\Context AS Subject;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\Url;
use Magento\Framework\UrlInterface;

/**
 * Class Context
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Plugin\Widget
 */
class Context
{
    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var UrlInterface
     */
    protected $_backendUrl;

    /**
     * Context constructor.
     * @param StoreManagerInterface $storeManagerInterface
     * @param Order $order
     * @param Url $frontendUrl
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        StoreManagerInterface $storeManagerInterface,
        Order $order,
        Url $frontendUrl,
        UrlInterface $urlInterface
    )
    {
        $this->_storeManagerInterface  = $storeManagerInterface;
        $this->_order                  = $order;
        $this->_frontendUrl            = $frontendUrl;
        $this->_backendUrl             = $urlInterface;
    }

    /**
     * @param Subject $subject
     * @param $buttonList
     * @return mixed
     */
    public function afterGetButtonList(
        Subject $subject,
        $buttonList
    )
    {
        $orderId    = $subject->getRequest()->getParam('order_id');
        $order      = $this->_order->load($orderId);

        $baseUrl = $this->_backendUrl->getUrl('moova/retiro/solicitar',['order_id' =>$orderId,'rk'=>uniqid()]);

        if($subject->getRequest()->getFullActionName() == 'sales_order_view' && !$order->hasShipments() &&
            $order->getShippingMethod() == 'moova_moova')
        {
            $buttonList->add(
                'solicitar_moova',
                [
                    'label'     => __('Generar envío MOOVA'),
                    'onclick' => "confirmSetLocation('¿Esta seguro que desea generar el envío de sus productos?', '{$baseUrl}')",
                    'class'     => 'primary'
                ]
            );
        }

        if($subject->getRequest()->getFullActionName() == 'sales_order_view' && $order->hasShipments() &&
            $order->getShippingMethod() == 'moova_moova')
        {
            $shipmentMoova = isset($order->getShipmentsCollection()->getData()[0]) ? $order->getShipmentsCollection()->getData()[0] : false;

            if($shipmentMoova !== false)
            {
                $shipmentMoovaInfo = \Zend_Json::decode($shipmentMoova['customer_note']);

                if(isset($shipmentMoovaInfo['id']))
                {
                    $baseUrl = $this->_backendUrl->getUrl('moova/retiro/descargar',['moova_id' =>$shipmentMoovaInfo['id']]);

                    $buttonList->add(
                        'descargar_etiqueta_moova',
                        [
                            'label'     => __('Descargar etiqueta MOOVA'),
                            'onclick' => "setLocation('{$baseUrl}')",
                            'class'     => 'primary'
                        ]
                    );

                }
            }
        }

        return $buttonList;
    }
}