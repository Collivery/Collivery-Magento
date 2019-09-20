define([
  'jquery',
  'mage/url',
  'Magento_Checkout/js/model/quote',
  'Magento_Checkout/js/model/shipping-rate-registry',
  'Magento_Checkout/js/checkout-data',
], function ($, url, quote, rateRegistry, checkoutData) {
  'use strict';

  $(document).on('click', '.action-select-shipping-item', function() {
    var address = quote.shippingAddress();

    if (typeof address.customerAddressId != 'undefined') {
      window.setDefaultAddress(address.customerAddressId, address.customerId, quote, rateRegistry)
    }
  });

  $(document).on('click', '.action-save-address span', function() {
    var newAddress = checkoutData.getNewCustomerShippingAddress();
    var storage = $.parseJSON(localStorage.getItem('mage-cache-storage'));
    $.ajax({
      url: url.build('rest/V1/add-new-shipping-address?'+$.param(newAddress)),
      type: "POST",
      contentType: "application/json",
      success : (result) => {
        if (result) {
          delete storage['checkout-data'].newCustomerShippingAddress;
          localStorage.setItem('mage-cache-storage', JSON.stringify(storage));
          location.reload();
        }

      },
      error: (xhr, status, errorThrown) => {
        var errorResponse = $.parseJSON( xhr.responseText );
        customerData.set('messages', {
          messages: [{
            type: 'error',
            text: errorResponse.message
          }]
        });
      }
    });

  });
  return function (targetModule) {
    targetModule.crazyPropertyAddedHere = 'yes';
    return targetModule;
  }
});