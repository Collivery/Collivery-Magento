require([
  'jquery',
  'domReady!',
], function ($) {
  'use strict';

    $(document).on('change', 'select[name="town"]', function(){

      setTimeout(() => getSuburbs($(this).val(), 200));

      function getSuburbs(townId) {
        var base_url = window.location.origin;
        $.ajax({
          url: base_url+'/rest/V1/mds-collivery/suburbs',
          type: "GET",
          data: { param: townId },
          dataType: 'json',
          showLoader: true,
          success : function(result) {
            var suburbField = $('select[name="suburb"]');
            var suburbId = suburbField.val();
            suburbField.empty();
            $.each(result, function( index) {
              var selected = suburbId == result[index].value ? 'selected="selected"' : '';
              suburbField.append("<option value="+result[index].value+" "+selected+">"+result[index].label+"</option>");
            });
          }
        });
      }
    });
});