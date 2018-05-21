function date_certificate_switch_radio_button_2(){
    var input_date_start = document.getElementById("date_start");
    var input_date_end = document.getElementById("date_end");
    input_date_start.value = "";
    input_date_end.value = "";
    input_date_start.setAttribute("disabled","disabled");
    input_date_end.setAttribute("disabled","disabled");
}

function date_certificate_switch_radio_button_1(){
    var input_date_start = document.getElementById("date_start");
    var input_date_end = document.getElementById("date_end");
    input_date_start.removeAttribute("disabled");
    input_date_end.removeAttribute("disabled");
}

function date_certificate_switch_radio_button_0(){
    var input_date_start = document.getElementById("date_start");
    var input_date_end = document.getElementById("date_end");
    input_date_start.value = "";
    input_date_end.value = "";
    input_date_start.setAttribute("disabled","disabled");
    input_date_end.setAttribute("disabled","disabled");
}

function type_date_expediction_switch_radio_button(){
    var input_type = document.getElementsByName("type_date_expediction");
    var type;
    for (var i=0;i<input_type.length;i++){
        if ( input_type[i].checked ) {
            type = input_type[i].value;
        }
    }
    var input_day = document.getElementById("day");
    var input_month = document.getElementById("month");
    var input_year = document.getElementById("year");
    if (type == 2) {
        input_day.removeAttribute("disabled");
        input_month.removeAttribute("disabled");
        input_year.removeAttribute("disabled");
    } else {
        input_day.setAttribute("disabled","disabled");
        input_month.setAttribute("disabled","disabled");
        input_year.setAttribute("disabled","disabled");
    }
}

function contents_type_switch_radio_button(){
    var input_type = document.getElementsByName("contents_type");
    var type;
    for (var i=0;i<input_type.length;i++){
        if ( input_type[i].checked ) {
          type = input_type[i].value;
        }
    }
    var input_contents = document.getElementById("contents");
    if (type == 2) {
        input_contents.removeAttribute("disabled");
    } else {
        input_contents.setAttribute("disabled","disabled");
    }
}

$(document).ready(function() {
    CKEDITOR.on("instanceReady", function (e) {
        showTemplates();
    });
                
    $( ".datepicker" ).datepicker();
});
