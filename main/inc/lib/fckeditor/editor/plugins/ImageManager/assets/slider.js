/***********************************************************************
** Title.........:  Simple Lite Slider for Image Editor
** Version.......:  1.1
** Author........:  Xiang Wei ZHUO <wei@zhuo.org>
** Filename......:  slider.js
** Last changed..:  31 Mar 2004
** Notes.........:  Works in IE and Mozilla
**/

var ie=document.all
var ns6=document.getElementById&&!document.all

document.onmouseup = captureStop;

var currentSlider = null,sliderField = null;
var rangeMin = null, rangeMax= null, sx = -1, sy = -1, initX=0;

function getMouseXY(e) {

    x = ns6? e.clientX: event.clientX
    y = ns6? e.clientY: event.clientY

    if (sx < 0) sx = x; if (sy < 0) sy = y;

    var dx = initX +(x-sx);

    if (dx <= rangeMin)
        dx = rangeMin;
    else if (dx >= rangeMax)
        dx = rangeMax;

    var range = (dx-rangeMin)/(rangeMax - rangeMin)*100;

    if (currentSlider !=  null)
        currentSlider.style.left = dx+"px";

    if (sliderField != null)
    {
        sliderField.value = parseInt(range);
    }

	if (currentSlider.id == "sliderbarwatermark")
	{
		changeWatermarkOpacity(dx);
	}

	return false;
}

function initSlider(action)
{
	currentSlider = document.getElementById('sliderbar' + action);
    sliderField = document.getElementById('sliderfield' + action);

    if (rangeMin == null)
        rangeMin = 3
    if (rangeMax == null)
    {
        var track = document.getElementById('slidertrack' + action);
        rangeMax = parseInt(track.style.width);
    }

}

function updateSlider(value, action)
{
	initSlider(action);

    var newValue = parseInt(value)/100*(rangeMax-rangeMin);

    if (newValue <= rangeMin)
        newValue = rangeMin;
    else if (newValue >= rangeMax)
        newValue = rangeMax;

    if (currentSlider !=  null)
        currentSlider.style.left = newValue+"px";

    var range = newValue/(rangeMax - rangeMin)*100;

    if (sliderField != null)
    {
        sliderField.value = parseInt(range);
    }

	if (action == "watermark")
	{
		changeWatermarkOpacity(newValue);
	}
}

function captureStart(action)
{
    initSlider(action);

    initX = parseInt(currentSlider.style.left);
    if (initX > rangeMax)
        initX = rangeMax;
    else if (initX < rangeMin)
        initX = rangeMin;

    document.onmousemove = getMouseXY;

    return false;
}

function captureStop()
{
    sx = -1; sy = -1;
    document.onmousemove = null;
    return false;
}