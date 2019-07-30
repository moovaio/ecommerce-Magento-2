<?php

namespace Improntus\Moova\Plugin\Magento\Quote\Model;

/**
 * Class BillingAddressManagement
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Plugin\Magento\Quote\Model
 */
class BillingAddressManagement
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * BillingAddressManagement constructor.
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * @param \Magento\Quote\Model\BillingAddressManagement $subject
     * @param $cartId
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @param bool $useForShipping
     */
    public function beforeAssign(
        \Magento\Quote\Model\BillingAddressManagement $subject,
        $cartId,
        \Magento\Quote\Api\Data\AddressInterface $address,
        $useForShipping = false
    ) {

        $extAttributes = $address->getExtensionAttributes();

        if (!empty($extAttributes))
        {
            try
            {
                $address->setAltura($extAttributes->getAltura());
                $address->setPiso($extAttributes->getPiso());
                $address->setDepartamento($extAttributes->getDepartamento());
                $address->setObservaciones($extAttributes->getObservaciones());
            }
            catch (\Exception $e)
            {
                $this->logger->critical($e->getMessage());
            }
        }
    }
}
