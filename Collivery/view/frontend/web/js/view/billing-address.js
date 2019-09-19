define([
  'jquery',
], function ($) {
  'use strict';

  $(document).on('change', "fieldset[class='fieldset'] select[name='town']", function () {
    var city = $('fieldset[class="fieldset"] select[name="town"] option:selected').text();
    $(document).find('fieldset[class="fieldset"] input[name="city"]').val(city).trigger('focus').trigger('keyup');
    $(document).find('div[name="billingAddresscheckmo.city"]').hide();
    $(document).find('fieldset[class="fieldset"] input[name="street[1]"],fieldset[class="fieldset"] input[name="street[2]').hide();
    setTimeout(() => window.getSuburbs('fieldset[class="fieldset"] select[name="suburb"]' ,$(this).val()), 200);
    $('fieldset[class="fieldset"] input[name="street[1]"]').trigger('changed')
  });

  $(document).on('change', "fieldset[class='fieldset'] select[name='suburb']", function () {
    $('fieldset[class="fieldset"] input[name="street[1]"]').val($('option:selected',this).text()).trigger('focus').trigger('keyup')
  });

  return function (targetModule) {
    targetModule.crazyPropertyAddedHere = 'yes';
    return targetModule;
  }
});