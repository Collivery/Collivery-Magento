define([
  'jquery',
  'mage/utils/wrapper',
  'Magento_Checkout/js/model/quote'
], function ($, wrapper,quote) {
  'use strict';

  return function (setBillingAddressAction) {
    return wrapper.wrap(setBillingAddressAction, function (originalAction) {

      var billingAddress = quote.billingAddress();

      if(billingAddress != undefined) {

        if (billingAddress['extension_attributes'] === undefined) {
          billingAddress['extension_attributes'] = {};
        }

        if (billingAddress.customAttributes !== undefined) {
          $.each(billingAddress.customAttributes, function (key, value) {

            billingAddress['extension_attributes'][value['attribute_code']] = value['value'];
          });
        }

      }

      return originalAction();
    });
  };
});