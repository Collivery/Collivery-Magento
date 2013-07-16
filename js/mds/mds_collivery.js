var setShipping = false;

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

    var isZA_B, isZA_S;

    function setFields(shipto) {
        if (shipto === 'billing') {
            $$("label[for='" + shipto + ":region_id']")[0].addClassName('required');
            $$("label[for='" + shipto + ":region_id']")[0].update('<em>*</em>Town');
        } else {
            $$("label[for='" + shipto + ":region']")[0].addClassName('required');
            $$("label[for='" + shipto + ":region']")[0].update('<em>*</em>Town');
        }

        var suburb_html =
        '<div class="mds-' + shipto + ' field">' +
        '	<label class="required" for="' + shipto + ':mds_suburb"><em>*</em>Suburb</label>' +
        '	<div class="input-box">' +
        '		<select onchange="mds_suburb_change (this, \'' + shipto + '\')" class="required-entry" title="Suburb" name="' + shipto + '[mds_suburb]" id="' + shipto + ':mds_suburb" defaultvalue="">' +
        '			<option value="">Please select a Town first</option>' +
        '		</select>' +
        '	</div>' +
        '</div>';
        $(shipto + ":city").up(2).insert({top: suburb_html});
        $(shipto + ":city").up(1).hide();

        var building_html =
        '<li class="mds-' + shipto + ' wide">' +
        '	<label for="' + shipto + ':mds_building" class="required"><em>*</em>Building Details</label>' +
        '	<div class="input-box">' +
        '		<input title="Building Details" name="' + shipto + '[mds_building]" id="' + shipto + ':mds_building" value="" class="input-text required-entry" type="text">' +
        '	</div>' +
        '</li>';
        $(shipto + ":street1").up(1).insert({before: building_html});

        var cptypes_html = 
        '<div class="mds-' + shipto + ' field">' +
        '	<label class="required" for="' + shipto + ':mds_cptypes"><em>*</em>Location Type</label>' +
        '	<div class="input-box">' +
        '		<select onchange="mds_cptypes_change (this, \'' + shipto + '\')" class="required-entry" title="Location Type" name="' + shipto + '[mds_cptype]" id="' + shipto + ':mds_cptypes" defaultvalue="">' +
        '			<option value="">Loading...</option>' +
        '		</select>' +
        '	</div>' +
        '</div>';
        $(shipto + ":city").up(2).insert({bottom: cptypes_html});

        var towns_html = 
        '<div class="mds-' + shipto + ' field" style="display: none;">' +
        '	<div class="input-box">' +
        '		<input name="' + shipto + '[mds_town]" id="' + shipto + ':mds_town" value="" class="input-text required-entry" type="text">' +
        '	</div>' +
        '</div>';
        $(shipto + ":city").up(2).insert({bottom: towns_html});
    }

    function getSuburbs(shipto) {

        var shipto_r = shipto;

        if (shipto === 'billship') {
            shipto = 'shipping';
            shipto_r = 'billing';
        }
        $(shipto + ":mds_suburb").update("<option value=\"\">Loading...</option>");

        var data = {
            town : $(shipto_r + ":region_id").getSelectedOptionHTML(),
        };

        new Ajax.Request(BASE_URL + 'collivery/ajax/suburb', {
            method: 'post',
            parameters: data,
            onSuccess: function (transport) {
                var response = transport.responseText || "<option>Error, Please try again</option>";
                $(shipto + ":mds_suburb").update(response);
                mds_suburb_change($(shipto + ":mds_suburb"), shipto);
            },
            onFailure: function () { $(shipto + ":mds_suburb").update("<option>Error, Please try again</option>"); }
        });

    }

    function getCPTypes(shipto) {

        $(shipto + ":mds_cptypes").update("<option value=\"\">Loading...</option>");

        new Ajax.Request(BASE_URL + 'collivery/ajax/cptypes', {
            method: 'get',
            onSuccess: function (transport) {
                var response = transport.responseText || "<option value=\"\">Error, Please try again</option>";
                $(shipto + ":mds_cptypes").update(response);
            },
            onFailure: function () { $(shipto + ":mds_cptypes").update("<option value=\"\">Error, Please try again</option>"); }
        });
    }

    function setZA(shipto) {
        setFields(shipto);
        getCPTypes(shipto);
    }

    function unSetZA(shipto) {
        $$('.mds-' + shipto).invoke('remove');
        $(shipto + ":city").up(1).show();
        $$("label[for='" + shipto + ":region_id']")[0].removeClassName('required');
        $$("label[for='" + shipto + ":region_id']")[0].update('<em style="display: none;">*</em>State/Province');
    }

    $("billing:country_id").observe('change', function () {
        if ($F("billing:country_id") === "ZA") {
            setZA('billing');
            isZA_B = true;
            if (!setShipping) {
                setZA('shipping');
                Form.Element.setValue("shipping:country_id", 'ZA');
                isZA_S = true;
            }
        } else {
            if (isZA_B) {
                unSetZA('billing');
                isZA_B = false;
            }
        }
    });

    $("shipping:country_id").observe('change', function () {
        if ($F("shipping:country_id") === "ZA") {
            setZA('shipping');
            isZA_S = true;
            setShipping = true;
        } else {
            if (isZA_S) {
                unSetZA('shipping');
                isZA_S = false;
                setShipping = true;
            }
        }
    });

    $("billing:region_id").observe('change', function () {
        if ($F("billing:country_id") === "ZA") {
            getSuburbs('billing');
            if (!setShipping) {
                getSuburbs('billship');
            }
        }
    });

    $("shipping:region_id").observe('change', function () {
        if ($F("shipping:country_id") === "ZA") {
            getSuburbs('shipping');
            setShipping = true;
        }
    });

    if ($F("shipping:country_id") === "ZA") {
        setZA('billing');
        isZA_B = true;
        if (!setShipping) {
            setZA('shipping');
            isZA_S = true;
        }
    }

});

function mds_suburb_change(sel, shipto) {
    var suburb = $(sel).getSelectedOptionHTML();
    Form.Element.setValue(shipto + ":city", suburb);
    if (shipto === 'billing' && !setShipping) {
        Form.Element.setValue("shipping:city", suburb);
        Form.Element.setValue("shipping:mds_suburb", $F(sel));
    }
}

function mds_cptypes_change(sel, shipto) {
    if (shipto === 'billing' && !setShipping) {
        Form.Element.setValue("shipping:mds_cptypes", $F(sel));
    }
}