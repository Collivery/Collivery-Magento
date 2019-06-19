define([
  'jquery',
  'mage/utils/wrapper',
  'mage/url',
  'Magento_Checkout/js/model/quote',
  'Magento_Checkout/js/model/shipping-rate-registry'
], function ($, wrapper, url, quote, rateRegistry) {
  'use strict';

  $(document).on('change', ".form-shipping-address select[name='town']", function () {
    quote.shippingAddress().city = 'not-required';
    var element = $('.form-shipping-address select[name="suburb"]');
    setTimeout(() => window.getSuburbs(element ,$(this).val()), 200);
    $(document).find('input[name="city"]').empty().trigger('keyup').val('not-required').attr('aria-required', false);
    $(document).find('div[name="shippingAddress.city"]').hide();
  });

  function setCustomAttribute()
  {
      var locationType = $('#co-shipping-form select[name="location"]').val();
      var town = $('#co-shipping-form select[name="town"]').val();
      var suburb = $('#co-shipping-form select[name="suburb"]').val();

      window.setCustomAttribute('town='+town+'&suburb='+suburb+'&location='+locationType, quote, rateRegistry, true)
  }

  $(document).on('change', '.form-shipping-address select[name="suburb"]', function() {
    setCustomAttribute();
  });

  $(document).on('change', '.form-shipping-address select[name="location"]', function() {
    setCustomAttribute();
  });

  return function (targetModule) {
    targetModule.crazyPropertyAddedHere = 'yes';
    return targetModule;
  }
});