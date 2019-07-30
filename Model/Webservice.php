<?php

namespace Improntus\Moova\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\Region;
use Improntus\Moova\Helper\Data as HelperMoova;

/**
 * Class Webservice
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Model
 */
class Webservice
{
    const BUDGET_GOOGLE_PLACE_ID  = 1;
    const BUDGET_ADDRESS_COMPLETE = 2;
    const BUDGET_ADDRESS_PARTS    = 3;

    /**
     * @var string
     */
    protected $_appId;

    /**
     * @var string
     */
    protected $_secretKey;

    /**
     * @var string
     */
    protected $_apiUrl;

    /**
     * @var CheckoutSession
     */
    protected $_checkoutSession;

    /**
     * @var HelperMoova
     */
    protected $_helper;

    /**
     * @var array
     */
    protected $_token;

    /**
     * Webservice constructor.
     * @param CheckoutSession $checkoutSession
     * @param Region $region
     * @param HelperMoova $helperMoova
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Region $region,
        HelperMoova $helperMoova
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_region = $region;
        $this->_helper = $helperMoova;

        $this->_appId = $helperMoova->getAppId();
        $this->_secretKey = $helperMoova->getSecretKey();
        $this->_apiUrl = $helperMoova->getApiUrl();
    }

    public function getBudget($shippingParams,$type)
    {
        $curl = curl_init();

        curl_setopt_array($curl,
        [
            CURLOPT_URL => "{$this->_apiUrl}b2b/v2/budgets?appId={$this->_appId}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($shippingParams),
            CURLOPT_HTTPHEADER => [
                "Authorization: {$this->_secretKey}",
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);

        \Improntus\Moova\Helper\Data::log(print_r($shippingParams,true) ,'debug_moova_'.date('m_Y').'.log');

        if(curl_error($curl))
        {
            $error = 'Se produjo un error al solicitar cotización: '. curl_error($curl);
            \Improntus\Moova\Helper\Data::log($error ,'error_moova_'.date('m_Y').'.log');

            return false;
        }

        try{
            $cotizacion = \Zend_Json::decode($response);

            if(isset($cotizacion['status']))
            {
                if($cotizacion['code'] != 404)
                {
                    $error = 'Se produjo un error al solicitar cotización: '. $cotizacion['message'];
                    \Improntus\Moova\Helper\Data::log($error ,'error_moova_'.date('m_Y').'.log');
                }

                return false;
            }
            else{
                $this->_helper->setMoovaQuoteId($cotizacion['quote_id']);

                return $cotizacion['price'];
            }
        }
        catch (\Exception $e)
        {
            $error = 'Se produjo un error al solicitar cotización: '. $e->getMessage() . ' Response: '. print_r($response,true);
            \Improntus\Moova\Helper\Data::log($error ,'error_moova_'.date('m_Y').'.log');

            return null;
        }
    }

    public function newShipment($shippingParams)
    {
        $curl = curl_init();

        curl_setopt_array($curl,
            [
                CURLOPT_URL => "{$this->_apiUrl}b2b/shippings?appId={$this->_appId}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => \Zend_Json::encode($shippingParams),
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_secretKey}",
                    "Content-Type: application/json"
                ],
            ]);

        $response = curl_exec($curl);

        if(curl_error($curl))
        {
            $error = 'Se produjo un error al solicitar cotización: '. curl_error($curl);
            \Improntus\Moova\Helper\Data::log($error ,'error_moova_'.date('m_Y').'.log');

            return false;
        }

        try{
            $shipment = \Zend_Json::decode($response);

            if(!isset($shipment['id']) && isset($shipment['errors']))
            {
                $error = 'Se produjo un error al solicitar cotización. Response: '. print_r($response,true);
                \Improntus\Moova\Helper\Data::log($error ,'error_moova_'.date('m_Y').'.log');

                return false;
            }

            return $shipment;
        }
        catch (\Exception $e)
        {
            $error = 'Se produjo un error al solicitar cotización: '. $e->getMessage() . ' Response: '. print_r($response,true);
            \Improntus\Moova\Helper\Data::log($error ,'error_moova_'.date('m_Y').'.log');

            return false;
        }
    }

    /**
     * @param $shipmentIdMoova
     * @return bool|mixed
     */
    public function getShipmentLabel($shipmentIdMoova)
    {
        $curl = curl_init();

        curl_setopt_array($curl,
            [
                CURLOPT_URL => "{$this->_apiUrl}b2b/shippings/$shipmentIdMoova/label?appId={$this->_appId}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => [
                    "Authorization: {$this->_secretKey}",
                    "Content-Type: application/json"
                ],
            ]);

        $response = curl_exec($curl);

        if(curl_error($curl))
        {
            $error = 'Se produjo un error al solicitar cotización: '. curl_error($curl);
            \Improntus\Moova\Helper\Data::log($error ,'error_moova_'.date('m_Y').'.log');

            return false;
        }

        try{
            $shipment = \Zend_Json::decode($response);

            return $shipment;
        }
        catch (\Exception $e)
        {
            $error = 'Se produjo un error al solicitar cotización: '. $e->getMessage() . ' Response: '. print_r($response,true);
            \Improntus\Moova\Helper\Data::log($error ,'error_moova_'.date('m_Y').'.log');

            return false;
        }
    }

    public function trackShipment($shipmentId)
    {
        $curl = curl_init();

    }
}