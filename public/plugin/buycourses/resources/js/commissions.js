/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function showSliders(maxPercentage, type, defaultValues) {
    
    defaultValues = defaultValues || "0";
    
    var sliderCounter = 1;
    var percentage = 0;
    var minPercentage = 0;

    $("#selectBox option:selected").each(function() {

        var count = $("#selectBox option:selected").length;
        
        percentage = maxPercentage / count;
        percentage = parseInt(percentage);

        verifyMaxPercentage = percentage * count;
        if (verifyMaxPercentage !== maxPercentage && sliderCounter === 1) {
            percentage = percentage + (maxPercentage - verifyMaxPercentage);
        }

        beneficiaryId = $(this).val();
        beneficiaryName = $(this).text();
        
        var verify;
      
        var slidersValue = defaultValues.toString().split(',');

        if (type === 'default') {
            
            percentage = slidersValue[sliderCounter - 1];
            percentage = parseInt(percentage);
            $("#panelSliders").append("<span id=" + beneficiaryId + ">" + beneficiaryName + "</span> - [ <span class='value' >" + percentage + "</span> % ] <div class='panelSliders'></div>");

        } else if (type === 'renew') {

            $("#panelSliders").append("<span id=" + beneficiaryId + " >" + beneficiaryName + "</span> - [ <span class='value' >" + percentage + "</span> % ] <div class='panelSliders'></div>");
            
        }
        
        verifyPaypalAccountByBeneficiary(beneficiaryId);

        sliderCounter++;
        stepSlide = count - 1;
        
        if (stepSlide === 0) {
            (type === 'default') ? minPercentage = 0 : minPercentage = 100;
            (type === 'default') ? stepSlide = 100 : stepSlide = 1;
        }

        
        $( "#panelSliders .panelSliders" ).slider({
            value: percentage,
            min: minPercentage,
            max: maxPercentage,
            step: stepSlide,
            animate: true,
            slide: function( event, ui ) {

                $(this).prev(".value").text(ui.value);

                var total = 0;
                var sliders = $( "#panelSliders .panelSliders" );

                sliders.not(this).each(function() {
                    value = $(this).slider("option", "value");
                    total += value;
                });

                total += ui.value;
                var delta = 100 - total;

                sliders.not(this).each(function() {
                    
                    var t = $(this);
                    value = t.slider("option", "value");

                    var newValue = value + (delta/stepSlide);

                    if (newValue < 0 || ui.value == 100) 
                        newValue = 0;
                    if (newValue > 100) 
                        newValue = 100;

                    t.prev('.value').text(newValue);
                    t.slider('value', newValue);
                    
                });
                
                $("[name=\'commissions\']").val(getSlidersValues());
            }
        });
    });
    
    $("[name=\'commissions\']").val(getSlidersValues());
    showCorrectSliderHandler();
};

function getSlidersValues() {
    var commissions = "";
    
    $( "#panelSliders .panelSliders" ).each(function() {
        commissions += $(this).prev(".value").text() + ',';
    });
    
    commissions = commissions.substring(0, commissions.length-1);
    
    return commissions;
}

function showCorrectSliderHandler() {
    var correctHandler = [];
    
    $("#panelSliders > span").each(function () {
        if ($(this).hasClass('value')) {
            correctHandler.push($(this).text());
        }
    });
    
    var counter = 0;
    
    $("#panelSliders .panelSliders > span").each(function () {
        if ($(this).hasClass('ui-slider-handle')) {
            $(this).css('left', correctHandler[counter]+'%');
            counter++;
        }
    });
}

function verifyPaypalAccountByBeneficiary(userId) {
    
    return $.ajax({
        data: 'id='+userId,
        url: 'buycourses.ajax.php?a=verifyPaypal',
        type: 'POST',
        success: function(response) {
            $("#"+userId).append(' '+response);
        }
    });
}
