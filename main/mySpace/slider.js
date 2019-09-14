// Set the initial height
var sliderHeight = "80px";

// Set the initial slider state
var slider_state = "close";

function sliderAction()
{
    if (slider_state == "close") {
        sliderOpen();
        slider_state = "open"
        $(".slider_menu").html('<a href="#" onclick="return sliderAction();"><img src="../img/icons/22/zoom_out.png"></a>');
    } else if (slider_state == "open") {
        sliderClose();
        slider_state = "close";
        $(".slider_menu").html('<a href="#" onclick="return sliderAction();"><img src="../img/icons/22/zoom_in.png"></a>');
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

$(function() {
    // Show the slider content
    $('.slider').show();
    $('.slider').each(function () {
        var current = $(this);
        current.attr("box_h", current.height());
    });

    $(".slider").css("height", sliderHeight);
});