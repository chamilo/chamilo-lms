// HEADER

/*$(window).resize(function(){
    var widthScreen = $(window).width();
    if(widthScreen >= 1400){
        $('body').addClass('sidebar-lg-show');
    } else {
        $('body').removeClass('sidebar-lg-show');
    }
    var main = $('#app-main');
    var cardMain = $('#card-container');
    var highAverage = main.height() - cardMain.height()-100;

    cardMain.css("height", highAverage+cardMain.height()+"px");
    if(cardMain.height()>850){
        cardMain.css("height", "auto");
    }
});*/

$(function () {

    //Width calculation for Sidebar
    var widthScreen = $(window).width();

    if(widthScreen >= 1400){
        $('body').addClass('sidebar-lg-show');
    }
    /*
    // Calculation of Width for Card Container

    var main = $('#app-main');
    var cardMain = $('#card-container');
    var highAverage = main.height() - cardMain.height()-100;

    //cardMain.css("height", highAverage+cardMain.height()+"px");
    cardMain.css("height", highAverage+cardMain.height()+"px");
    if(cardMain.height()>850 || widthScreen <= 600){
        cardMain.css("height", "auto");
    }
*/

    var $inputTitle = $("#add_course_title");
    $inputTitle.keyup(function () {
        var value = $(this).val();
        var titleDefault = "Course Title";
        if (value.length > 0) {
            $("#title_course_card").text(value);
        } else {
            $("#title_course_card").text(titleDefault);
        }
    }).keyup();

    $("select[name=category_code]").change(function () {
        $(".category").show();
        var $category = $('select[name=category_code] option:selected').html();
        $(".category").text($category);
    });

});
