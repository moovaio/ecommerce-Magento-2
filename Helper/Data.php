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
        $moovaCheckoutFields = [ 'address', 'street', 'number', 'floor', 'city', 'state', 'postalCode','floor' ];
        $destination = [];

        foreach ($moovaCheckoutFields as $field) {
            $value = self::getCheckoutValue($shippingAddress, $_scopeConfig, $field, $input);
            $destination[$field] = str_replace("\n", ' ', $value);
        }

        $destination['country'] = $countryInfo->getCountryId();

        if(!empty($destination['address'])){

            if(!empty($destination['city'])){
                $destination['address'] .= ','. $destination['city'];
            }

            if(!empty($destination['state'])){
                $destination['address'] .= ','. $destination['state'];
            }
            
            $destination['address'] .= ',' . $countryInfo->getName();
        }

        Log::info('getDestination - response' . json_encode($destination));
        return $destination;
    }

    private static function getCheckoutValue($shippingAddress, $_scopeConfig, $field, $input = null)
    {
        $value = self::getFieldFromMagentoDefaultCheckout($shippingAddress, $_scopeConfig, $field);
        if(empty($value)){
            return self::getFieldFromCustomCheckout($shippingAddress, $_scopeConfig, $field, $input);
        }
        return $value;
        
    }

    private static function getFieldFromMagentoDefaultCheckout($shippingAddress, $_scopeConfig, $field){
        $keySpecialMapping = $_scopeConfig->getValue("shipping/moova_webservice/moova_checkout/$field", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $value = isset($shippingAddress[$keySpecialMapping]) ?  $shippingAddress[$keySpecialMapping] : null;
        return $value;
    }

    //Returns in case the development team changed a name in the checkout or created a custom prop in it
    private static function getFieldFromCustomCheckout($shippingAddress, $_scopeConfig, $field, $input){
        $key = $_scopeConfig->getValue("shipping/moova_webservice/moova_checkout/$field", \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $value = null;
        if (!empty($input['extension_attributes'][$key])) {
            $value = $input['extension_attributes'][$key];
        } elseif (!empty($input['custom_attributes'])) {
            $value = self::findByCode($input['custom_attributes'], $key);
        } elseif (!empty($input['customAttributes'])) {
            $value = self::findByCode($input['customAttributes'], $key);
        }
        return $value;
    }


    private static function findByCode($attributes, $key)
    {
        foreach ($attributes as $attribute) {
            if ($attribute['attribute_code'] == $key) {
                return str_replace("\n", ' ',$attribute['value']);
            }
        }
    }
}
