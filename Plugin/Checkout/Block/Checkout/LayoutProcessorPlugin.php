<?php

namespace Improntus\Moova\Plugin\Checkout\Block\Checkout;

/**
 * Class LayoutProcessorPlugin
 *
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @package Improntus\Moova\Plugin\Checkout\Block\Checkout
 */
class LayoutProcessorPlugin
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * LayoutProcessorPlugin constructor.
     * @param \Psr\Log\LoggerInterface $loggerInterface
     */
    public function __construct(
        \Psr\Log\LoggerInterface $loggerInterface
    ) {
        $this->logger = $loggerInterface;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject
     * @param $result
     * @return mixed
     */
    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $subject, $result)
    {
        $customAttributeCode = 'altura';
        $customField = [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'customScope' => 'shippingAddress',
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/input'
            ],
            'dataScope' => 'shippingAddress.altura',
            'label' => __('Altura'),
            'provider' => 'checkoutProvider',
            'sortOrder' => 71,
            'validation' => [
                'required-entry' => true,
                'validate-number' => true
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
        ];

        $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$customAttributeCode] = $customField;

        $customAttributeCode = 'piso';
        $customField = [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'customScope' => 'shippingAddress',
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/input'
            ],
            'dataScope' => 'shippingAddress.piso',
            'label' => __('Piso'),
            'provider' => 'checkoutProvider',
            'sortOrder' => 72,
            'validation' => [
                'required-entry' => true,
                'validate-number' => true
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
        ];

        $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$customAttributeCode] = $customField;

        $customAttributeCode = 'departamento';
        $customField = [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'customScope' => 'shippingAddress',
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/input'
            ],
            'dataScope' => 'shippingAddress.departamento',
            'label' => __('Departamento'),
            'provider' => 'checkoutProvider',
            'sortOrder' => 73,
            'validation' => [
                'required-entry' => true,
                'validate-number' => true
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
        ];

        $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$customAttributeCode] = $customField;

        $customAttributeCode = 'observaciones';
        $customField = [
            'component' => 'Magento_Ui/js/form/element/abstract',
            'config' => [
                'customScope' => 'shippingAddress',
                'template' => 'ui/form/field',
                'elementTmpl' => 'ui/form/element/input',
                'tooltip' => [
                    'description' => 'Observaciones',
                ],
            ],
            'dataScope' => 'shippingAddress.observaciones',
            'label' => __('Observaciones'),
            'provider' => 'checkoutProvider',
            'sortOrder' => 90,
            'validation' => [
                'required-entry' => false
            ],
            'options' => [],
            'filterBy' => null,
            'customEntry' => null,
            'visible' => true,
        ];

        $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']['shipping-address-fieldset']['children'][$customAttributeCode] = $customField;

       return $result;
    }
}