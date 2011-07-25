// Set the initial height
var sliderHeight = "80px";

$(document).ready(function(){
    // Show the slider content
    $('.slider').show();

    $('.slider').each(function () {
        var current = $(this);
        current.attr("box_h", current.height());
    });

    $(".slider").css("height", sliderHeight);
});

function sliderGetHeight(foo_var)
{
        var current = $(foo_var);
        if (current.height() <= 80){
            return current.height()+30;
        }
        else {
            return current.height();
        }
}

function sliderSetHeight(foo_var, foo_height)
{
        $(foo_var).each(function () {
        var current = $(this);
        current.attr("box_h", foo_height);
    });
}

function controlSliderMenu(foo_height_a)
{
        if (foo_height_a <= 80){
                sliderOpen();
        slider_state = "open"
        $(".slider_menu").empty();

    }
}
// Set the initial slider state
var slider_state = "close";

function getSliderState()
{
   return  slider_state;
}

function setSliderState(foo_slider_state)
{
   slider_state = foo_slider_state;
}
function sliderAction()
{
    if (slider_state == "close")
    {
        sliderOpen();
        slider_state = "open"
        $(".slider_menu").html('<a href="#" onclick="return sliderAction();">Cerrar</a>');
    }
    else if (slider_state == "open")
    {
        sliderClose();
        slider_state = "close";
        $(".slider_menu").html('<a href="#" onclick="return sliderAction();">M&aacute;s...</a>');
    }

    return false;
}

function sliderOpen()
{
    var open_height = $(".slider").attr("box_h") + "px";
    $(".slider").animate({"height": open_height}, {duration: "slow" });
}

function sliderClose()
{
    $(".slider").animate({"height": "0px"}, {duration: "fast" });
        $(".slider").animate({"height": sliderHeight}, {duration: "fast" });
}