$(document).ready(function(){
 // Reset Font Size
 var originalFontSize = $('body').css('font-size');
 $(".reset_font").click(function(){
  $('body').css('font-size', originalFontSize);
 });
 // Increase Font Size
 $(".increase_font").click(function(){
  var currentFontSize = $('body').css('font-size');
  var currentFontSizeNum = parseFloat(currentFontSize, 10);
  var newFontSize = currentFontSizeNum*1.2;
  $('body').css('font-size', newFontSize);
  return false;
 });
 // Decrease Font Size
 $(".decrease_font").click(function(){
  var currentFontSize = $('body').css('font-size');
  var currentFontSizeNum = parseFloat(currentFontSize, 10);
  var newFontSize = currentFontSizeNum*0.8;
  $('body').css('font-size', newFontSize);
  return false;
 });
});