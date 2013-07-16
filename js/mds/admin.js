var modalbox, parcels = {};

var parcelAI = 0;
for (parcelAI; parcelAI < validation.parcels.length; parcelAI++) {
    parcels['parcel_' + parcelAI] = validation.parcels[parcelAI];
    addParcelToTable(
        validation.parcels[parcelAI].length,
        validation.parcels[parcelAI].width,
        validation.parcels[parcelAI].height,
        validation.parcels[parcelAI].weight,
        1
    );
}

function CheckForm() {
    if (!document.getElementById('override_num_packages').checked) {
        if (confirm('Confirm Collivery without any changes?')) {
            document.getElementById('validateForm').submit();
        }
    } else {
        modalbox = new lightbox('modalbox');
        modalbox.activate();
        modalbox.displayLightbox();
    }
}

function reValidate() {
    delete validation.parcels;
    var i, key, parcelQty, newArray = [];
    for (key in parcels) {
        if (typeof parcels[key].qty === "undefined" || parcels[key].qty === 1)
            newArray.push(parcels[key]);
        else {
            parcelQty = parcels[key].qty;
            for (i = 0; i < parcelQty; i++) {
                newArray.push(parcels[key]);
            }
        }
    }
    validation.parcels = newArray;
    Form.Element.setValue("hiddenValidation", Object.toJSON(validation));
    document.getElementById('validateForm').submit();
    document.getElementById('reValidateBtn').disabled = "disabled";
}

function modalClose() {
    modalbox.deactivate();
}
function deleteParcel(parcel) {
    delete parcels[parcel.up(1).id];
    parcel.up(1).remove();
    updateTotals();
}
function updateTotals() {
    var total_weight = 0,total_qty = 0;

    $$('#parcels tr.parcel').each(function (em) {
        total_weight += parseFloat($(em).select('td:nth-child(4)').first().innerHTML);
        total_qty += parseInt($(em).select('td:nth-child(5)').first().innerHTML);
    });
    $("total_weight").update(total_weight);
    $("total_qty").update(total_qty);
}

function isNumber(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}

function addParcel() {
    var length, width, height, weight, qty;
    if (isNumber($('length').value))
        length = $('length').value;
    else
        length = 0;
        
    if (isNumber($('width').value))
        width = $('width').value;
    else
        width = 0;
    
    if (isNumber($('height').value))
        height = $('height').value;
    else
        height = 0;
    
    if (isNumber($('weight').value))
        weight = $('weight').value;
    else
        weight = 0;
    
    if (isNumber($('qty').value))
        qty = $('qty').value;
    else
        qty = 0;
    
    parcelAI++;
    parcels['parcel_' + parcelAI] = {length: length, width: width, height: height, weight: weight, qty: qty}
    addParcelToTable (length, width, height, weight, qty);
}

function addParcelToTable(length, width, height, weight, qty) {
    $$('#parcels tr:nth-last-child(2)').first().insert({after:'<tr class="parcel" id="parcel_' + parcelAI +'"><td>' + 
        length + 
        '</td><td>' + 
        width + 
        '</td><td>' + 
        height + 
        '</td><td>' + 
        weight + 
        '</td><td>' + 
        qty + 
        '</td><td><button onclick=\"deleteParcel(this);\" class=\"button\">Delete</button></td></tr>'});
    updateTotals();
}