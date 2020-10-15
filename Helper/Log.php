<?php

namespace Improntus\Moova\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Directory\Model\Region;

/**
 * Class Data
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Helper
 */
class Log extends AbstractHelper
{

    protected $logger;

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
    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger                   = $logger;
    }


    /**
     * @param $mensaje String
     * @param $archivo String
     */
    public static function info($message)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $isLogEnabled = $objectManager->get('Magento\Framework\App\Config\ScopeConfigInterface')
            ->getValue('shipping/moova_webservice/enable_log');
        if (!$isLogEnabled) {
            return true;
        }

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/info_moova_' . date('m_Y') . '.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info($message);
    }
}
