$(document).ready(function(){
    // Reset Font Size
    var originalFontSize = $('body').css('font-size');
    
    var original_head1 = $('h1').css('font-size');
    var original_head2 = $('h2').css('font-size');
    var original_head3 = $('h3').css('font-size');
    var original_head4 = $('h4').css('font-size');
    var original_head5 = $('h5').css('font-size');
    var original_head6 = $('h9').css('font-size');
    
    
    $(".reset_font").click(function() {
        $('body').css('font-size', originalFontSize);
        $('h1').css('font-size', original_head1);
        $('h2').css('font-size', original_head2);
        $('h3').css('font-size', original_head3);
        $('h4').css('font-size', original_head4);
        $('h5').css('font-size', original_head5);
        $('h6').css('font-size', original_head6);        
    });
    
    // Increase Font Size
    $(".increase_font").click(function(){
        var currentFontSize = $('body').css('font-size');
        var currentFontSizeNum = parseFloat(currentFontSize, 10);
        var newFontSize = currentFontSizeNum*1.2;
        $('body').css('font-size', newFontSize);
        $('h1, h2, h3, h4, h5, h6').css('font-size', newFontSize);
        return false;
    });
    
    // Decrease Font Size
    $(".decrease_font").click(function(){
        var currentFontSize = $('body').css('font-size');
        var currentFontSizeNum = parseFloat(currentFontSize, 10);
        var newFontSize = currentFontSizeNum*0.8;
        $('body').css('font-size', newFontSize);
        $('h1, h2, h3, h4, h5, h6').css('font-size', newFontSize);
        return false;
    });
});