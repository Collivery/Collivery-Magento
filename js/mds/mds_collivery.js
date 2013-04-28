var $j = jQuery.noConflict();

$j(document).ready(function() {

	var isZA;

	jQuery('select#billing\\:country_id').live('change', function() {
		//alert('Handler for .change() called.');
		if ($j("#billing\\:country_id").val() == "ZA") {
			switchSelect();
		} else {
			if (isZA){
				switchText();
			}
		}
	});

	if ($j("#billing\\:country_id").val() == "ZA") {
		alert('US Selected');
		switchSelect();
	}

	function switchSelect () {
		jQuery.post("collivery/ajax/index", {neweval: "value"}, function(data){
			$j("#billing\\:region").remove();
			$j("#billing\\:region_id").after('<select title="Town" class="validate-select" name="billing[country_id]" id="billing:region"></select>');
			$j("#billing\\:region").append();
			isZA = true;
		});
		
	}
	
	function switchText () {
		var display = "";
		if ($j("#billing\\:region").is(':visible'))
			var display = 'style="display:none;"';
		$j("#billing\\:region").remove();
		$j("#billing\\:region_id").after('<input id="billing:region" ' + display + ' class="input-text required-entry" type="text" title="State/Province" value="" name="billing[region]">');
		isZA = false;
	}
});
