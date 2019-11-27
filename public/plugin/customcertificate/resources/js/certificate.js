function dateCertificateSwitchRadioButton2(){
    $("#date_start").prop("disabled", true).val("");
    $("#date_end").prop("disabled", true).val("");
}

function dateCertificateSwitchRadioButton1(){
    $("#date_start").prop("disabled", false);
    $("#date_end").prop("disabled", false);
}

function dateCertificateSwitchRadioButton0(){
    $("#date_start").prop("disabled", true).val("");
    $("#date_end").prop("disabled", true).val("");
}

function typeDateExpedictionSwitchRadioButton(){
    $("[name=\"type_date_expediction\"]").each(function( index ) {
        if ( $(this).is(":checked") && $(this).val() === "2") {
            $("#day, #month, #year").prop("disabled", false);
        } else {
            $("#day, #month, #year").prop("disabled", true);
        }
    });
}

function contentsTypeSwitchRadioButton(){
    $("[name=\"contents_type\"]").each(function( index ) {
        if ( $(this).is(":checked") && $(this).val() === "2") {
            $("#contents").prop("disabled", false);
        } else {
            $("#contents").prop("disabled", true);
        }
    });
}

$(document).ready(function() {
    $( ".datepicker" ).datepicker();
});
