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

  window.getSuburbs = function(suburbField, townId) {
    $.ajax({
      url: url.build('rest/V1/mds-collivery/suburbs'),
      type: "GET",
      data: { param: townId },
      dataType: 'json',
      success : function(result) {
        suburbField.empty();
        $.each(result, function( index) {
          suburbField.append("<option value="+result[index].value+">"+result[index].label+"</option>");
        });
        suburbField.trigger('change');
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
});