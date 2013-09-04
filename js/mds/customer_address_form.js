Element.addMethods("SELECT", (function () {
    function getSelectedOptionHTML(element) {
        if (!(element = $(element))) return;
        var index = element.selectedIndex;
        return index >= 0 ? element.options[index].innerHTML : undefined;
    }
    return {
        getSelectedOptionHTML: getSelectedOptionHTML
    };
})());

document.observe('dom:loaded', function() {

    var isZA;

    function setFields() {
        $$("label[for='region_id']")[0].addClassName('required');
        $$("label[for='region_id']")[0].update('<em>*</em>Town');

        var suburb_html =
        '<div class="mds field">' +
        '	<label class="required" for="mds_suburb"><em>*</em>Suburb</label>' +
        '	<div class="input-box">' +
        '		<select onchange="mds_suburb_change (this)" class="required-entry" title="Suburb" name="mds_suburb" id="mds_suburb" defaultvalue="">' +
        '			<option value="">Please select a Town first</option>' +
        '		</select>' +
        '	</div>' +
        '</div>';
        $("city").up(2).insert({top: suburb_html});
        $("city").up(1).hide();

        var building_html =
        '<li class="mds wide">' +
        '	<label for="mds_building" class="required"><em>*</em>Building Details</label>' +
        '	<div class="input-box">' +
        '		<input title="Building Details" name="mds_building" id="mds_building" value="" class="input-text required-entry" type="text">' +
        '	</div>' +
        '</li>';
        $("street_1").up(1).insert({before: building_html});

        var cptypes_html = 
        '<div class="mds field">' +
        '	<label class="required" for="mds_cptypes"><em>*</em>Location Type</label>' +
        '	<div class="input-box">' +
        '		<select class="required-entry" title="Location Type" name="mds_cptype" id="mds_cptypes" defaultvalue="">' +
        '			<option value="">Loading...</option>' +
        '		</select>' +
        '	</div>' +
        '</div>';
        $("city").up(2).insert({bottom: cptypes_html});

        var towns_html = 
        '<div class="mds field" style="display: none;">' +
        '	<div class="input-box">' +
        '		<input name="mds_town" id="mds_town" value="" class="input-text required-entry" type="text">' +
        '	</div>' +
        '</div>';
        $("city").up(2).insert({bottom: towns_html});
    }

    function getSuburbs() {
        
        if ($("region_id").value == ''){
            $("mds_suburb").update("<option value=\"\">Please select a Town first</option>");
        } else {
        
            $("mds_suburb").update("<option value=\"\">Loading...</option>");

            var data = {
                town : $("region_id").getSelectedOptionHTML(),
            };

            new Ajax.Request(BASE_URL + 'collivery/ajax/suburb', {
                method: 'post',
                parameters: data,
                onSuccess: function (transport) {
                    var response = transport.responseText || "<option>Error, Please try again</option>";
                    $("mds_suburb").update(response);
                    mds_suburb_change($("mds_suburb"));
                },
                onFailure: function () { $("mds_suburb").update("<option>Error, Please try again</option>"); }
            });
        }
    }

    function getCPTypes() {

        $("mds_cptypes").update("<option value=\"\">Loading...</option>");

        new Ajax.Request(BASE_URL + 'collivery/ajax/cptypes', {
            method: 'get',
            onSuccess: function (transport) {
                var response = transport.responseText || "<option value=\"\">Error, Please try again</option>";
                $("mds_cptypes").update(response);
            },
            onFailure: function () { $("mds_cptypes").update("<option value=\"\">Error, Please try again</option>"); }
        });
    }

    function setZA() {
        setFields();
        getSuburbs();
        getCPTypes();
    }

    function unSetZA() {
        $$('.mds').invoke('remove');
        $("city").up(1).show();
        $$("label[for='region_id']")[0].removeClassName('required');
        $$("label[for='region_id']")[0].update('<em style="display: none;">*</em>State/Province');
    }

    $("country").observe('change', function () {
        if ($("country").value === "ZA") {
            setZA();
            isZA = true;
        } else {
            if (isZA) {
                unSetZA();
                isZA = false;
            }
        }
    });

    $("region_id").observe('change', function () {
        if ($("country").value === "ZA") {
            getSuburbs();
        }
    });

    if ($("country").value === "ZA") {
        setZA();
        isZA = true;
    }

});

function mds_suburb_change(sel) {
    var suburb = $(sel).getSelectedOptionHTML();
    Form.Element.setValue("city", suburb);
}