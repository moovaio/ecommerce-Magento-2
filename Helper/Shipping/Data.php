<?php

namespace Improntus\Moova\Helper\Shipping;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Temando\Shipping\Model\Shipment;
use Improntus\Moova\Helper\Log;

/**
 * Class Data
 * @package Improntus\Moova\Helper\Shipping
 */
class Data extends \Magento\Shipping\Helper\Data
{
    /**
     * @var UrlInterface|null
     */
    private $url;

    /**
     * Data constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface|null $url
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        StoreManagerInterface $storeManager,
        UrlInterface $url = null
    ) {
        $this->url = $url ?: ObjectManager::getInstance()->get(UrlInterface::class);

        parent::__construct($context, $storeManager, $url);
    }

    /**
     * Retrieve tracking url with params
     *
     * @param  string $key
     * @param  \Magento\Sales\Model\Order|\Magento\Sales\Model\Order\Shipment|\Magento\Sales\Model\Order\Shipment\Track $model
     * @param  string $method Optional - method of a model to get id
     * @return string
     */
    protected function _getTrackingUrl($key, $model, $method = 'getId')
    {
        $urlPart = "{$key}:{$model->{$method}()}:{$model->getProtectCode()}";
        $url = 'shipping/tracking/popup';

        if ($model->getShippingMethod() == 'moova_moova') {
            $shipmentCollection = $model->getShipmentsCollection()->getData();

            if (count($shipmentCollection) == 1) {
                $shipmentInfo = json_decode($model->getShipmentsCollection()->getData()[0]['customer_note']);

                $trackingNumber = substr($shipmentInfo->id, 0, 8);

                $url = $this->scopeConfig->getValue('shipping/moova_webservice/tracking/url') . "$trackingNumber";

                return $url;
            }
        }

        if ($model->getEntityType() == 'shipment' && $model->getOrder()->getShippingMethod() == 'moova_moova') {
            $shipmentInfo = json_decode($model->getCustomerNote());

            $trackingNumber = substr($shipmentInfo->id, 0, 8);

            $url = $this->scopeConfig->getValue('shipping/moova_webservice/tracking/url') . "$trackingNumber";

            return $url;
        }

        if ($model->getTrackNumber() && $model->getCarrierCode()  == 'moova') {
            return $this->scopeConfig->getValue('shipping/moova_webservice/tracking/url') . "{$model->getTrackNumber()}";
        }

        $params = [
            '_scope' => $model->getStoreId(),
            '_nosid' => true,
            '_direct' => $url,
            '_query' => ['hash' => $this->urlEncoder->encode($urlPart)]
        ];

        return $this->url->getUrl('', $params);
    }
}
