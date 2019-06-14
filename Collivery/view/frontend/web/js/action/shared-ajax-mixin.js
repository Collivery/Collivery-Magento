define([
  'jquery',
  'mage/url',
], function ($, url) {
  'use strict';

    window.getShippingAddress = function(element) {
      $.ajax({
        url: url.build('rest/V1/fetch-shipping-address'),
        type: "GET",
        dataType: 'json',
        showLoader: true,
        success : function(result) {
          $(element).html(result);
        }
      });
    };

    window.getBillingAddress = function(element) {
      $.ajax({
        url: url.build('rest/V1/fetch-billing-address'),
        type: "GET",
        dataType: 'json',
        showLoader: true,
        cache: false,
        success : function(result) {
          $(element).empty().html(result);
        }
      });
    };

    window.getSuburbs = function(suburbField, townId) {
      $.ajax({
        url: url.build('rest/V1/mds-collivery/suburbs'),
        type: "GET",
        data: { param: townId },
        dataType: 'json',
        showLoader: true,
        success : function(result) {
          suburbField.empty();
          $.each(result, function( index) {
            suburbField.append("<option value="+result[index].value+">"+result[index].label+"</option>");
          });
          suburbField.trigger('change');
        }
      });
    };

    window.setCustomAttribute = function(params, quote, rateRegistry, reloadEstimatesPrice = false) {
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
        }
      });
    };

    window.getCustomAttribute = function(paramAddressType, updateAttributes = false, addressType = '', element = '') {
      $.ajax({
        url: url.build('rest/V1/get-custom-attributes'),
        type: "GET",
        data: { param: paramAddressType},
        dataType: 'json',
        success : function(result) {
          if (updateAttributes){
            $.ajax({
              url: url.build('rest/V1/custom-attributes?address_type='+addressType+'&'+$.param(JSON.parse(result))),
              type: "POST",
              contentType: "application/json",
              success : setTimeout(() => window.getBillingAddress(element), 200)
            });
          }
        }
      });
    }
});