/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-rates-validation-rules',
    'Improntus_Moova/js/model/shipping-rates-validator',
    'Improntus_Moova/js/model/shipping-rates-validation-rules'
], function (
    Component,
    defaultShippingRatesValidator,
    defaultShippingRatesValidationRules,
    moovaShippingRatesValidator,
    moovaShippingRatesValidationRules
) {
    'use strict';

    defaultShippingRatesValidator.registerValidator('moova', moovaShippingRatesValidator);
    defaultShippingRatesValidationRules.registerRules('moova', moovaShippingRatesValidationRules);

    return Component;
});
