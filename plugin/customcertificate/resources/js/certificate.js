function dateCertificateSwitchRadioButton2(){
    var inputDateStart = document.getElementById("date_start");
    var inputDateEnd = document.getElementById("date_end");
    inputDateStart.value = "";
    inputDateEnd.value = "";
    inputDateStart.setAttribute("disabled","disabled");
    inputDateEnd.setAttribute("disabled","disabled");
}

function dateCertificateSwitchRadioButton1(){
    var inputDateStart = document.getElementById("date_start");
    var inputDateEnd = document.getElementById("date_end");
    inputDateStart.removeAttribute("disabled");
    inputDateEnd.removeAttribute("disabled");
}

function dateCertificateSwitchRadioButton0(){
    var inputDateStart = document.getElementById("date_start");
    var inputDateEnd = document.getElementById("date_end");
    inputDateStart.value = "";
    inputDateEnd.value = "";
    inputDateStart.setAttribute("disabled","disabled");
    inputDateEnd.setAttribute("disabled","disabled");
}

function typeDateExpedictionSwitchRadioButton(){
    var inputType = document.getElementsByName("type_date_expediction");
    var type;
    for (var i=0;i<inputType.length;i++){
        if ( inputType[i].checked ) {
            type = parseInt(inputType[i].value);
        }
    }
    var inputDay = document.getElementById("day");
    var inputMonth = document.getElementById("month");
    var inputYear = document.getElementById("year");
    if (type === 2) {
        inputDay.removeAttribute("disabled");
        inputMonth.removeAttribute("disabled");
        inputYear.removeAttribute("disabled");
    } else {
        inputDay.setAttribute("disabled","disabled");
        inputMonth.setAttribute("disabled","disabled");
        inputYear.setAttribute("disabled","disabled");
    }
}

function contentsTypeSwitchRadioButton(){
    var inputType = document.getElementsByName("contents_type");
    var type;
    for (var i=0;i<inputType.length;i++){
        if ( inputType[i].checked ) {
          type = parseInt(inputType[i].value);
        }
    }
    var inputContents = document.getElementById("contents");
    if (type === 2) {
        inputContents.removeAttribute("disabled");
    } else {
        inputContents.setAttribute("disabled","disabled");
    }
}

$(document).ready(function() {
    $( ".datepicker" ).datepicker();
});
