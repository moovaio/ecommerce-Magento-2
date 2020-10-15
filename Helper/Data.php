<?php

namespace Improntus\Moova\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\Region;
use Magento\Shipping\Helper\Data as ShippingData;
use Improntus\Moova\Helper\Log;

/**
 * Class Data
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Helper
 */
class Data extends AbstractHelper
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManagerInterface;

    /**
     * @var Region
     */
    protected $_region;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Framework\DataObject\Copy\Config
     */
    protected $fieldsetConfig;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Shipping\Helper\Data
     */
    protected $_shippingData;

    /**
     * Data constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManagerInterface
     * @param Region $region
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\DataObject\Copy\Config $fieldsetConfig
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManagerInterface,
        Region $region,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\DataObject\Copy\Config $fieldsetConfig,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        ShippingData $shippingHelper
    ) {
        $this->_scopeConfig             = $scopeConfig;
        $this->_storeManagerInterface   = $storeManagerInterface;
        $this->_region                  = $region;
        $this->_checkoutSession         = $checkoutSession;
        $this->fieldsetConfig           = $fieldsetConfig;
        $this->logger                   = $logger;
        $this->quoteRepository          = $cartRepository;
        $this->_shippingData            = $shippingHelper;
    }

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->_scopeConfig->getValue('shipping/moova_webservice/app_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return $this->_scopeConfig->getValue('shipping/moova_webservice/secret_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->_scopeConfig->getValue('shipping/moova_webservice/url', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $path
     * @param $params
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreUrl($path, $params)
    {
        return $this->_storeManagerInterface->getStore()->getUrl($path, $params);
    }

    /**
     * @return float
     */
    public function getMaxWeight()
    {
        return (float)$this->_scopeConfig->getValue("carriers/moova/max_package_weight", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $regionId
     * @return string
     */
    public function getProvincia($regionId)
    {
        if (is_int($regionId)) {
            $provincia = $this->_region->load($regionId);

            $regionId = $provincia->getDefaultName() ? $provincia->getDefaultName() : $regionId;
        }

        return $regionId;
    }

    /**
     * @param $mensaje String
     * @param $archivo String
     */
    public static function log($mensaje, $archivo)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/' . $archivo);
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($mensaje);
    }

    /**
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_checkoutSession->getQuote();
    }

    /**
     * @param int $moovaQuoteId
     */
    public function setMoovaQuoteId($moovaQuoteId)
    {
        $this->_checkoutSession->setMoovaQuoteId($moovaQuoteId);
    }

    public function getStatusFromUrlTracking($order)
    {
        $url = $this->_shippingData->getTrackingPopupUrlBySalesModel($order);

        $query = parse_url($url, PHP_URL_QUERY);
        $queries = array();
        $shipmentId = null;
        parse_str($query, $queries);

        if (isset($queries['id'])) {
            $shipmentId = $queries['id'];
        }

        return $shipmentId;
    }

    /**
     * @return boolean
     */
    public function getHabilitadoMostrarEstadoEnvio()
    {
        return $this->_scopeConfig->getValue('shipping/moova_webservice/tracking/enable_status', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public static function getDestination($shippingAddress, $countryInfo, $_scopeConfig, $input = null)
    {
        Log::info('getDestination - parameters received:' . json_encode($shippingAddress));
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

        foreach ($checkoutFields as $field) {
            $value = self::getCheckoutField($shippingAddress, $_scopeConfig, $field, $input);
            $response[$field] = empty($value) ? '' : array_shift($value);
        }

        Log::info('getDestination - response' . json_encode($response));

        $response['country'] = $countryInfo->getData('iso3_code');
        if (empty($response['address'])) {
            if (empty($response['street']) || empty($response['number'])) {
                unset($response['street']);
                unset($response['number']);
            }
            unset($response['address']);
            return $response;
        }

        $toAppend = ['city', 'state'];
        foreach ($toAppend as $item) {
            $value = $response[$item];
            if (!empty($value)) {
                $response['address'] .= ",$value";
            }
        }

        $countryName = $countryInfo->getName();
        $response['address'] .= ",$countryName";
        return $response;
    }

    private static function getCheckoutField($shippingAddress, $_scopeConfig, $field, $input = null)
    {
        $key = $_scopeConfig->getValue("shipping/moova_webservice/moova_checkout/$field", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $value = isset($shippingAddress[$key]) ? $shippingAddress[$key] : '';
        if (!empty($value)) {
            return is_string($value) ? explode("\n", $value) : $value;
        }
        if (!empty($shippingAddress[$key])) {
            $value = $shippingAddress[$key];
        } elseif (!empty($input['extension_attributes'][$key])) {
            $value = $input['extension_attributes'][$key];
        } elseif (!empty($input['custom_attributes'])) {
            $value = self::findByCode($input['custom_attributes'], $key);
        } elseif (!empty($input['customAttributes'])) {
            $value = self::findByCode($input['customAttributes'], $key);
        }
        return is_string($value) ? explode("\n", $value) : $value;
    }


    private static function findByCode($attributes, $key)
    {
        foreach ($attributes as $attribute) {
            if ($attribute['attribute_code'] == $key) {
                return explode("\n", $attribute['value']);
            }
        }
    }
}
