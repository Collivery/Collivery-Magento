define([
  'jquery',
  'mage/url',
  'Magento_Customer/js/customer-data'
], function ($, url, customerData) {
  'use strict';

  $( document ).ajaxComplete(function() {
    $('body').trigger('processStop');
  });
  $( document ).ajaxStart(function() {
    $('body').trigger('processStart');
  });

  window.getSuburbs = function(suburbFieldClass, townId) {
    $.ajax({
      url: url.build('rest/V1/mds-collivery/suburbs'),
      type: "GET",
      data: { param: townId },
      dataType: 'json',
      success : function(result) {
        $(suburbFieldClass+' option').remove();
        $.each(result, function(index, option) {
          $(suburbFieldClass).append("<option value="+option.value+">"+option.label+"</option>");
        });
        $(suburbFieldClass).trigger('change');
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
  };

  window.setCustomAttribute = function(params, quote, rateRegistry, reloadEstimatesPrice = false) {
    if (params.indexOf(undefined) === -1) {
      $.ajax({
        url: url.build('rest/V1/custom-attributes?'+params),
        type: "POST",
        contentType: "application/json",
        success : () => {
          if (reloadEstimatesPrice){
            let address = quote.shippingAddress();
            rateRegistry.set(address.getKey(), null);
            rateRegistry.set(address.getCacheKey(), null);
            quote.shippingAddress(address);
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
      })
    }
  };

  window.setDefaultAddress = function(addressId, customerId, quote, rateRegistry) {
    if (typeof addressId != 'undefined' && typeof customerId != 'undefined') {
      $.ajax({
        url: url.build('rest/V1/set-default-shipping-address?address_id='+addressId+'&customer_id='+customerId),
        type: "POST",
        contentType: "application/json",
        success : () => {
          let address = quote.shippingAddress();
          rateRegistry.set(address.getKey(), null);
          rateRegistry.set(address.getCacheKey(), null);
          quote.shippingAddress(address);
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
      })
    }
  };
});