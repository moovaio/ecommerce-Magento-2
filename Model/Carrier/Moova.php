<?php

namespace Improntus\Moova\Model\Carrier;

use Magento\Directory\Model\Country;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Psr\Log\LoggerInterface;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Improntus\Moova\Helper\Data as MoovaHelper;
use Improntus\Moova\Model\Webservice;
use Magento\Framework\Xml\Security;
use Improntus\Moova\Helper\Log;

/**
 * Class Moova
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Model\Carrier
 */
class Moova extends AbstractCarrierOnline implements CarrierInterface
{
    const CARRIER_CODE = 'moova';

    /**
     * @var string
     */
    protected $_code = self::CARRIER_CODE;

    /**
     * @var
     */
    protected $_webservice;

    /**
     * @var MoovaHelper
     */
    protected $_helper;

    /**
     * @var RateRequest
     */
    protected $_rateRequest;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    protected $_rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    protected $_rateMethodFactory;

    /**
     * @var RequestInterface
     */
    protected $_request;

    /**
     * @var AddressFactory
     */
    protected $_addressFactory;

    /**
     * @var TarifaFactory
     */
    protected $_tarifaFactory;

    /**
     * Rate result data
     *
     * @var Result
     */
    protected $_result;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $_addressRepository;

    /**
     * @var Country
     */
    protected $_country;

    /**
     * Moova constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     * @param ResultFactory $rateFactory
     * @param MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Magento\Framework\App\RequestInterface $request
     * @param Webservice $webservice
     * @param Country $country
     * @param MoovaHelper $moovaHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        Security $xmlSecurity,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Framework\App\RequestInterface $request,
        Webservice $webservice,
        Country  $country,
        MoovaHelper $moovaHelper,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_helper            = $moovaHelper;
        $this->_webservice        = $webservice;
        $this->_addressRepository = $addressRepository;
        $this->_country           = $country;
        $this->_request           = $request;

        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );
    }
    /**
     * @return bool
     */
    public function isTrackingAvailable()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isCityRequired()
    {
        return true;
    }

    /**
     * @param null $countryId
     * @return bool
     */
    public function isZipCodeRequired($countryId = null)
    {
        if ($countryId != null) {
            return !$this->_directoryData->isZipCodeOptional($countryId);
        }
        return true;
    }

    /**
     * Is state province required
     *
     * @return bool
     */
    public function isStateProvinceRequired()
    {
        return true;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['moova' => $this->getConfigData('title')];
    }

    /**
     * @param RateRequest $request
     * @return bool|Result
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $helper = $this->_helper;
        $result = $this->_rateResultFactory->create();
        $method = $this->_rateMethodFactory->create();

        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($this->_code);
        $method->setMethodTitle($this->getConfigData('description'));

        $webservice = $this->_webservice;

        $itemsWsMoova = [];
        $totalPrice = 0;

        foreach ($request->getAllItems() as $_item) {
            if ($_item->getProductType() == 'configurable')
                continue;

            $_product = $_item->getProduct();

            if ($_item->getParentItem())
                $_item = $_item->getParentItem();

            $moovaAlto = (int) $_product->getResource()
                ->getAttributeRawValue($_product->getId(), 'moova_alto', $_product->getStoreId());

            $moovaLargo = (int) $_product->getResource()
                ->getAttributeRawValue($_product->getId(), 'moova_largo', $_product->getStoreId());

            $moovaAncho = (int) $_product->getResource()
                ->getAttributeRawValue($_product->getId(), 'moova_ancho', $_product->getStoreId());

            $totalPrice += $_product->getFinalPrice();

            $itemsWsMoova[] = [
                'quantity' => $_item->getQty(),
                'description' => $_item->getName(),
                'price'     => $_item->getPrice(),
                'weight'    => $_product->getWeight() * 1000, //Peso en unidad de kg a gramos
                'length'    => $moovaAlto,
                'width'     => $moovaLargo,
                'height'    => $moovaAncho
            ];
        }

        $pesoTotal  = $request->getPackageWeight(); //Peso en unidad de kg

        if ($pesoTotal > (int)$helper->getMaxWeight()) {
            $error = $this->_rateErrorFactory->create();
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage(__('Su pedido supera el peso máximo permitido por Moova. Por favor divida su orden en más pedidos o consulte al administrador de la tienda.'));

            return $error;
        }

        if ($request->getFreeShipping() === true) {
            $method->setPrice(0);
            $method->setCost(0);

            $result->append($method);
            return $result;
        }

        $isOrderCreate  = $this->_request->getControllerName() == 'order_create'
            && is_array($this->_request->getParam('order'))
            && isset($this->_request->getParam('order')['shipping_address']);

        if ($this->_request->getControllerName() !== 'order_create') {
            $shippingAddress = $helper->getQuote()->getShippingAddress();
        } elseif ($isOrderCreate) {
            $shippingAddress = $this->_request->getParam('order')['shipping_address'];
        } else {
            return $result;
        }

        $countryId = $shippingAddress['country_id'] ? $shippingAddress['country_id'] : $request->getDestCountryId();
        $countryInfo = $this->_country->loadByCode($countryId);

        $shipping = [
            'from' => [
                'street' => $this->_scopeConfig->getValue('shipping/moova_webservice/from/street', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'number' => $this->_scopeConfig->getValue('shipping/moova_webservice/from/number', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'floor'  => $this->_scopeConfig->getValue('shipping/moova_webservice/from/floor', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'apartment' => $this->_scopeConfig->getValue('shipping/moova_webservice/from/apartment', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'city'      => $this->_scopeConfig->getValue('shipping/moova_webservice/from/city', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'state'      => $this->_scopeConfig->getValue('shipping/moova_webservice/from/state', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'postalCode' => $this->_scopeConfig->getValue('shipping/moova_webservice/from/postcode', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'country' => $countryInfo->getData('iso3_code')
            ],
            'to' => $this->getDestination($shippingAddress->getData(), $countryInfo),
            'conf' => [
                'assurance' => false,
                'items'     => $itemsWsMoova
            ]
        ];
        Log::info('collectRates - sending to Moova: ' . json_encode($shipping));
        $costoEnvio = $webservice->getBudget($shipping, 1);
        Log::info('collectRates - received from Moova' . json_encode($costoEnvio));
        if ($costoEnvio !== false) {
            $method->setPrice($costoEnvio);
            $method->setCost($costoEnvio);

            $result->append($method);
            return $result;
        }
        $error = $this->_rateErrorFactory->create();
        $error->setCarrier($this->_code);
        $error->setCarrierTitle($this->getConfigData('title'));
        $error->setErrorMessage(__('No existen cotizaciones para la dirección ingresada'));

        $result->append($error);

        return $result;
    }

    private function getDestination($shippingAddress, $countryInfo)
    {
        $checkoutFields = [
            'address',
            'street',
            'number',
            'floor',
            'city',
            'state',
            'postalCode',
            'floor'
        ];
        $response = [];

        foreach ($checkoutFields as $field) {
            $key = $this->_scopeConfig->getValue("shipping/moova_webservice/moova_checkout/$field", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $value = isset($shippingAddress[$key]) ? $shippingAddress[$key] : '';
            $value = explode("\n", $value);
            $response[$field] = array_shift($value);
            $response['apartment'] = implode('', $value);
        }

        if (!empty($response['address'])) {
            $countryName = $countryInfo->getName();
            $toAppend = ['city', 'state'];
            foreach ($toAppend as $item) {
                $value = $response[$item];
                if (!empty($value)) {
                    $response['address'] .= ",$value";
                }
            }
            $response['address'] .= ",$countryName";
            unset($response['city']);
            unset($response['state']);
        }

        $response['country'] = $countryInfo->getData('iso3_code');
        Log::info('getDestination - parameters received:' . json_encode($shippingAddress));
        Log::info('getDestination - destination fields:' . json_encode($response));
        return $response;
    }

    /**
     * Do shipment request to carrier web service, obtain Print Shipping Labels and process errors in response
     *
     * @param \Magento\Framework\DataObject $request
     * @return \Magento\Framework\DataObject
     * @throws \Exception
     */
    protected function _doShipmentRequest(\Magento\Framework\DataObject $request)
    {
        $this->_prepareShipmentRequest($request);
        $xmlRequest = $this->_formShipmentRequest($request);
        $xmlResponse = $this->_getCachedQuotes($xmlRequest);

        if ($xmlResponse === null) {
            $url = $this->getShipConfirmUrl();

            $debugData = ['request' => $xmlRequest];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlRequest);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, (bool)$this->getConfigFlag('mode_xml'));
            $xmlResponse = curl_exec($ch);
            if ($xmlResponse === false) {
                throw new \Exception(curl_error($ch));
            } else {
                $debugData['result'] = $xmlResponse;
                $this->_setCachedQuotes($xmlRequest, $xmlResponse);
            }
        }
    }

    /**
     * Processing additional validation to check if carrier applicable.
     *
     * @param \Magento\Framework\DataObject $request
     * @return $this|bool|\Magento\Framework\DataObject
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function proccessAdditionalValidation(\Magento\Framework\DataObject $request)
    {
        return $this;
    }
}
