
$(window).resize(function(){
    var widthScreen = $(window).width();
    if(widthScreen >= 1400){
        $('body').addClass('sidebar-lg-show');
    } else {
        $('body').removeClass('sidebar-lg-show');
    }
});

$(function () {

    //Width calculation for Sidebar
    var widthScreen = $(window).width();

    if(widthScreen >= 1400){
        $('body').addClass('sidebar-lg-show');
    }

    //Elevator Scroll
    $(window).scroll(function () {
        if ($(this).scrollTop() > 50) {
            $('.app-elevator').fadeIn();
        } else {
            $('.app-elevator').fadeOut();
        }
    });
    // scroll body to 0px on click
    $('#back-to-top').click(function () {
        $('#back-to-top').tooltip('hide');
        $('body,html').animate({
            scrollTop: 0
        }, 800);
        return false;
    });

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
