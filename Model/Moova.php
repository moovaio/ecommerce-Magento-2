<?php

namespace Improntus\Moova\Model;

use Improntus\Moova\Helper\Data as HelperMoova;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use \Magento\Directory\Model\Country;
use Improntus\Moova\Helper\Data;
use Improntus\Moova\Helper\Log;

/**
 * Class Moova
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Model
 */
class Moova
{
    protected $_helper;

    /**
     * @var \Magento\Shipping\Model\ShipmentNotifier
     */
    protected $_shipmentNotifier;

    /**
     * @var Webservice
     */
    protected $_webService;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    protected $_shipmentFactory;

    /**
     * Order converter.
     *
     * @var \Magento\Sales\Model\Convert\Order
     */
    protected $_converter;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $_trackFactory;

    /**
     * @var Country
     */
    protected $_country;

    /**
     * Moova constructor.
     * @param Webservice $webservice
     * @param ConvertOrder $convertOrder
     * @param \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory
     * @param HelperMoova $helperMoova
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Shipping\Model\CarrierFactory $carrierFactory
     * @param \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory
     * @param Country $country
     */
    public function __construct(
        Webservice $webservice,
        ConvertOrder $convertOrder,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        HelperMoova $helperMoova,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Shipping\Model\ShipmentNotifier $shipmentNotifier,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        Country  $country
    ) {
        $this->_orderRepository = $orderRepository;
        $this->_shipmentFactory = $shipmentFactory;
        $this->_shipmentNotifier = $shipmentNotifier;
        $this->_carrierFactory = $carrierFactory;
        $this->_trackFactory = $trackFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_converter = $convertOrder;
        $this->_helper = $helperMoova;
        $this->_webService = $webservice;
        $this->_country = $country;
    }


    public function doShipmentWithOrderId($orderId)
    {
        $order = $this->_orderRepository->get($orderId);
        return $this->doShipment($order);
    }

    /**
     * @param $orderId
     * @return bool|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function doShipment($order)
    {
        if (!$order->canShip()) {
            return false;
        }

        $shippingAddress = $order->getShippingAddress();
        $shipment = $this->_shipmentFactory->create($order);

        try {
            $valorTotal = $pesoTotal = 0;
            $itemsArray = [];
            $itemsWsMoova = [];

            foreach ($order->getAllItems() as $orderItem) {
                if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                    continue;
                }

                $_product = $orderItem->getProduct();

                $moovaAlto = (int) $_product->getResource()
                    ->getAttributeRawValue($_product->getId(), 'moova_alto', $_product->getStoreId()) * $orderItem->getQty();

                $moovaLargo = (int) $_product->getResource()
                    ->getAttributeRawValue($_product->getId(), 'moova_largo', $_product->getStoreId()) * $orderItem->getQty();

                $moovaAncho = (int) $_product->getResource()
                    ->getAttributeRawValue($_product->getId(), 'moova_ancho', $_product->getStoreId()) * $orderItem->getQty();

                $qtyShipped = $orderItem->getQtyToShip();
                $shipmentItem = $this->_converter->itemToShipmentItem($orderItem)->setQty($qtyShipped);

                $valorTotal += $qtyShipped * $orderItem->getPrice();
                $pesoTotal  += $qtyShipped * $orderItem->getWeight();

                $itemsArray[$orderItem->getId()] =
                    [
                        'qty'           => $qtyShipped,
                        'customs_value' => $orderItem->getPrice(),
                        'price'         => $orderItem->getPrice(),
                        'name'          => $orderItem->getName(),
                        'weight'        => $orderItem->getWeight(),
                        'product_id'    => $orderItem->getProductId(),
                        'order_item_id' => $orderItem->getId()
                    ];

                $itemsWsMoova[] = [
                    'description' => $orderItem->getName(),
                    'price'     => $orderItem->getPrice(),
                    'quantity'  => $qtyShipped,
                    'weight'    => ($orderItem->getWeight() * 1000), //gramos
                    'length'    => $moovaAlto,
                    'width'     => $moovaLargo,
                    'height'    => $moovaAncho
                ];

                $shipment->addItem($shipmentItem);
            }

            $shipment->setPackages(
                [
                    1 => [
                        'items' => $itemsArray,
                        'params' => [
                            'weight' => $pesoTotal,
                            'container' => 1,
                            'customs_value' => $valorTotal
                        ]
                    ]
                ]
            );

            $shipment->register();
            $shipment->getOrder()->setIsInProcess(true);

            $countryId = $shippingAddress->getCountryId();
            $countryInfo = $this->_country->loadByCode($countryId);

            $shippingParams = [
                'currency'      => $order->getStoreCurrencyCode(),
                'type'          => 'magento_2_24_horas_max',
                'flow'          => 'manual',
                'from'          =>
                [
                    'googlePlaceId' => '',
                    'country'       => $countryInfo->getData('iso3_code'),
                    'street'        => $this->_scopeConfig->getValue('shipping/moova_webservice/from/street', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'number'        => $this->_scopeConfig->getValue('shipping/moova_webservice/from/number', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'floor'         => $this->_scopeConfig->getValue('shipping/moova_webservice/from/floor', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'apartment'     => $this->_scopeConfig->getValue('shipping/moova_webservice/from/apartment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'city'          => $this->_scopeConfig->getValue('shipping/moova_webservice/from/city', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'state'         => $this->_scopeConfig->getValue('shipping/moova_webservice/from/state', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'postalCode'    => $this->_scopeConfig->getValue('shipping/moova_webservice/from/postcode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'instructions'  => $this->_scopeConfig->getValue('shipping/moova_webservice/from/instructions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                    'contact'       =>
                    [
                        'firstName' => '',
                        'lastName'  => '',
                        'email'     => '',
                        'phone'     => ''
                    ],
                    'message' => ''
                ],
                'to' => $this->getDestination($shippingAddress, $countryInfo),
                'internalCode'  => $order->getIncrementId(),
                'comments'      => '',
                'extra'         => [],
                'conf' =>
                [
                    'assurance' => false,
                    'items'     => $itemsWsMoova,
                    'shipping_type_id' => 1
                ]
            ];

            $shipmentMoova = $this->_webService->newShipment($shippingParams);
            Log::info("doShipment - shipmentMoova" . json_encode($shipmentMoova));
            if ($shipmentMoova === false) {
                return false;
            }

            $trackingMoova = $shipmentMoova['id'];
            $mensajeEstado = __('La solicitud de retiro moova fue creada correctamente. Código de seguimiento Moova: %1', $trackingMoova);

            $history = $order->addStatusHistoryComment($mensajeEstado);
            $history->setIsVisibleOnFront(true);
            $history->setIsCustomerNotified(true);
           

            $carrier = $this->_carrierFactory->create($order->getShippingMethod(true)->getCarrierCode());
            $carrierCode = $carrier->getCarrierCode();
            $carrierTitle = $this->_scopeConfig->getValue(
                'carriers/' . $carrierCode . '/title',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $shipment->getStoreId()
            );

            $this->addTrackingNumbersToShipment($shipment, [$trackingMoova], $carrierCode, $carrierTitle);

            $shipment->setCustomerNote(\Zend_Json::encode($shipmentMoova));
            $shipment->setCustomerNoteNotify(false);
            $shipment->save();
            $shipment->getOrder()->save();
            $history->save();
            $this->_shipmentNotifier->notify($shipment);

            return $mensajeEstado;
        } catch (\Exception $e) {
            Log::info($e);
            throw new \Magento\Framework\Exception\LocalizedException(
                __($e->getMessage())
            );
        }
    }

    private function getDestination($shippingAddress, $countryInfo)
    {
        return array_merge(
            [
                'contact' => [
                    'firstName' => $shippingAddress->getFirstname(),
                    'lastName'  => $shippingAddress->getLastname(),
                    'email'     => $shippingAddress->getEmail(),
                    'phone'     => $shippingAddress->getTelephone()
                ],
                'message'       => ''
            ],
            Data::getDestination($shippingAddress->getData(), $countryInfo, $this->_scopeConfig)
        );
    }

    /**
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $trackingNumbers
     * @param $carrierCode
     * @param $carrierTitle
     * @return \Magento\Sales\Model\Order\Shipment
     */
    private function addTrackingNumbersToShipment(\Magento\Sales\Model\Order\Shipment $shipment, $trackingNumbers, $carrierCode, $carrierTitle)
    {
        foreach ($trackingNumbers as $number) {
            if (is_array($number)) {
                $this->addTrackingNumbersToShipment($shipment, $number, $carrierCode, $carrierTitle);
            } else {
                $shipment->addTrack(
                    $this->_trackFactory->create()
                        ->setNumber($number)
                        ->setCarrierCode($carrierCode)
                        ->setTitle($carrierTitle)
                );
            }
        }

        return $shipment;
    }

    /**
     * @param string $shipmentIdMoova
     * @return bool|mixed
     */
    public function getShippingLabel($shipmentIdMoova)
    {
        return $this->_webService->getShipmentLabel($shipmentIdMoova);
    }

    /**
     * @param string $shipmentIdMoova
     * @return bool|mixed
     */
    public function sendStatusShipment($shipmentIdMoova, $status)
    {
        return $this->_webService->sendStatusShipment($shipmentIdMoova, $status);
    }
}
