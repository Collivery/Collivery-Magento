define([
  'jquery',
  'mage/utils/wrapper',
  'mage/url',
  'Magento_Checkout/js/model/quote',
  'Magento_Checkout/js/model/shipping-rate-registry'
], function ($, wrapper, url, quote, rateRegistry) {
  'use strict';

  $(document).on('change', "[name='town']", function () {
    setTimeout(() => getSuburbs($(this).val(), 200));
    function getSuburbs(townId){
      $.ajax({
        url: url.build('rest/V1/mds-collivery/suburbs'),
        type: "GET",
        data: { param: townId },
        dataType: 'json',
        showLoader: true,
        success : function(result) {
          var suburbField = $('select[name="suburb"]');
          suburbField.empty();
          $.each(result, function( index) {
            suburbField.append("<option value="+result[index].value+">"+result[index].label+"</option>");
          });
        }
      });
      setCustomAttribute();
    }
  });

  function setCustomAttribute()
  {
    var locationType = $('[name="location"]').val();
    var town = $('[name="town"]').val();
    var suburb = $('[name="suburb"]').val();
    $.ajax({
      url: url.build('rest/V1/custom-attributes?town='+town+'&suburb='+suburb+'&location_type='+locationType),
      type: "POST",
      contentType: "application/json",
      showLoader: true,
      success : () => {
        var address = quote.shippingAddress();
        rateRegistry.set(address.getKey(), null);
        rateRegistry.set(address.getCacheKey(), null);
        quote.shippingAddress(address);
      }
    });
  }

  $(document).on('change', '[name="suburb"]', function() {
    setCustomAttribute();
  });

  $(document).on('change', '[name="location"]', function() {
    setCustomAttribute();
  });

  return function (targetModule) {
    targetModule.crazyPropertyAddedHere = 'yes';
    return targetModule;
  }
});