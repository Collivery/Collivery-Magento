var $j = jQuery.noConflict();

$j(document).ready(function() {

	var isZA;

	jQuery('select#billing\\:country_id').live('change', function() {
		if ($j("#billing\\:country_id").val() == "ZA") {
			setZA();
		} else {
			if (isZA){
				unSetZA();
			}
		}
	});

	jQuery('select#billing\\:region_id').live('change', function() {
		if ($j("#billing\\:country_id").val() == "ZA") {
			getSuburbs();
		}
	});

	if ($j("#billing\\:country_id").val() == "ZA") {
		setZA();
	}
	
	function getSuburbs () {
		
		$j('#mds\\:billing_suburb').empty();
		$j('#mds\\:billing_suburb').append('<option value="">Loading...</option>');
		
		var data = {
			town		: 'Pretoria',
		};
		jQuery.ajax({
			type : 'POST',
			url : "http://localhost/magento/index.php/collivery/ajax/suburb",
			data : data,
			complete : function(response){
				$j('#mds\\:billing_suburb').empty();
				$j("#mds\\:billing_suburb").append(response['responseText']);
			}
		});
		
	}
	
	function getSuburbLayout () {
		jQuery.ajax({
			type : 'POST',
			url : "http://localhost/magento/index.php/collivery/ajax/suburbLayout",
			complete : function(response){
				$j("#billing\\:city").parent().parent().parent().prepend(response['responseText']);
				$j('#billing\\:city').parent().parent().hide();
			}
		});
		
	}
	
	function setZA () {
		getSuburbLayout();
	}
	
	function unSetZA () {
		
	}

});
