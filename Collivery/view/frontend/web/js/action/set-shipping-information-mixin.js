define([
  'jquery',
  'mage/utils/wrapper',
  'mage/url',
  'Magento_Checkout/js/model/quote',
  'Magento_Checkout/js/model/shipping-rate-registry',
], function ($, wrapper, url, quote, rateRegistry) {
  'use strict';

  setTimeout(() => $('[name="country_id"]').trigger('change'), 1200);
  $(document).on('change', ".form-shipping-address select[name='town']", function () {
    var elementClass = '.form-shipping-address select[name="suburb"]';
    setTimeout(() => window.getSuburbs(elementClass ,$(this).val()), 200);
  });

  function setCustomAttribute()
  {
      var locationType = $('#co-shipping-form select[name="location"]').val();
      var town = $('#co-shipping-form select[name="town"]').val();
      var suburb = $('#co-shipping-form select[name="suburb"]').val();
      var city = $('#co-shipping-form select[name="town"] option:selected').text();
      $(document).find('#co-shipping-form input[name="city"]').val(city).trigger('focus').trigger('keyup');
      window.setCustomAttribute('town='+town+'&suburb='+suburb+'&location='+locationType, quote, rateRegistry, true)
  }

  $(document).on('change', '.form-shipping-address select[name="suburb"]', function() {
    $(document).find('#co-shipping-form input[name="street[1]').val($('option:selected',this).text()).trigger('focus').trigger('keyup');
    setCustomAttribute();
  });

  $(document).on('change', '.form-shipping-address select[name="location"]', function() {
    setCustomAttribute();
  });

  $(document).on('change', '[name="country_id"]', function() {
    var customAttributes =  $(document).find('div[name="shippingAddress.town"], div[name="shippingAddress.suburb"], div[name="shippingAddress.location"]');
    var magentoAttributes = $(document).find('div[name="shippingAddress.city"], #co-shipping-form input[name="street[1]"], #co-shipping-form input[name="street[2]"]');
    if($(this).val() === 'ZA'){
      magentoAttributes.hide();
      customAttributes.show();
    }else{
      magentoAttributes.show();
      customAttributes.hide();
    }
  });

  return function (targetModule) {
    targetModule.setTrue = 'yes';
    return targetModule;
  }
});