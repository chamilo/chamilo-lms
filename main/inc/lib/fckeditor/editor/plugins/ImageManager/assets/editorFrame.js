/**
 * Javascript used by the editorFrame.php, it basically initializes the frame.
 * @author $Author: Wei Zhuo $
 * @author $Author: Paul Moers <mail@saulmade.nl> $ - watermarking and replace code + several small enhancements <http://www.saulmade.nl/FCKeditor/FCKPlugins.php>
 * @version $Id: editorFrame.js 26 2004-03-31 02:35:21Z Wei Zhuo $
 * @package ImageManager
 */

//var topDoc = window.top.document;
var topDoc = window.parent.document;

var t_cx = topDoc.getElementById('cx');
var t_cy = topDoc.getElementById('cy');
var t_cw = topDoc.getElementById('cw');
var t_ch = topDoc.getElementById('ch');

var m_sx = topDoc.getElementById('sx');
var m_sy = topDoc.getElementById('sy');
var m_w = topDoc.getElementById('mw');
var m_h = topDoc.getElementById('mh');
var m_a = topDoc.getElementById('ma');
var m_d = topDoc.getElementById('md');

var s_sw = topDoc.getElementById('sw');
var s_sh = topDoc.getElementById('sh');

var r_ra = topDoc.getElementById('ra');

var pattern = "img/2x2.gif";

function doSubmit(action)
{
	// hiding action buttons
	var buttons = parent.document.getElementById('buttons');
	buttons.style.display = 'none';
	// hiding current action's controls
	var tools = parent.document.getElementById('tools_' + action);
	tools.style.display = 'none';

    if (action == 'watermark')
    {
		//show message
		parent.showMessage('watermarking');

		var watermarkX = dd.elements.floater.x - dd.elements.background.x;
		var watermarkY = dd.elements.floater.y - dd.elements.background.y;

		var opacity = parseInt(topDoc.getElementById('sliderfieldwatermark').value);
		var watermarkFullPath = topDoc.getElementById('watermark_file').options[topDoc.getElementById('watermark_file').selectedIndex].getAttribute("fullPath");
        var url = "editorFrame.php?img="+currentImageFile+"&action=watermark&opacity=" + opacity + '&watermarkFullPath=' + watermarkFullPath + '&watermarkX=' + watermarkX + '&watermarkY=' + watermarkY;

        location.href = url;
    }   
    else if (action == 'crop')
    {
		//show message
		parent.showMessage('cropping');

		var url = "editorFrame.php?img="+currentImageFile+"&action=crop&params="+parseInt(t_cx.value)+','+parseInt(t_cy.value)+','+ parseInt(t_cw.value)+','+parseInt(t_ch.value);

        location.href = url;
    }   
    else if (action == 'scale')
    {
		//show message
		parent.showMessage('scaling');

        var url = "editorFrame.php?img="+currentImageFile+"&action=scale&params="+parseInt(s_sw.value)+','+parseInt(s_sh.value);

        location.href = url;
    }
    else if (action == 'rotate')
    {
		//show message
		parent.showMessage('rotating');

        var flip = topDoc.getElementById('flip');

        if(flip.value == 'hoz' || flip.value == 'ver') 
            location.href = "editorFrame.php?img="+currentImageFile+"&action=flip&params="+flip.value;
        else if (isNaN(parseFloat(r_ra.value))==false)
            location.href = "editorFrame.php?img="+currentImageFile+"&action=rotate&params="+parseFloat(r_ra.value);
    }
    else if(action == 'save')
	{
		//show message
		parent.showMessage('saving');

        var s_file = topDoc.getElementById('save_filename');
        var s_format = topDoc.getElementById('save_format');
        var s_quality = topDoc.getElementById('sliderfieldsave');

        var format = s_format.value.split(",");
        if(s_file.value.length <= 0) 
		{
            alert(i18n('Please enter a filename to save.'));
        }
        else
        {
            var filename = encodeURI(s_file.value);
            var quality = parseInt(s_quality.value);
            var url = "editorFrame.php?img="+currentImageFile+"&action=save&params="+format[0]+","+quality+"&file="+filename;

            location.href = url;
        }
    }
}


function addEvent(obj, evType, fn)
{ 
	if (obj.addEventListener) { obj.addEventListener(evType, fn, true); return true; } 
	else if (obj.attachEvent) {  var r = obj.attachEvent("on"+evType, fn);  return r;  } 
	else {  return false; } 
} 

var jg_doc;

function init()
{
	jg_doc = new jsGraphics("imgCanvas"); // draw directly into document
	jg_doc.setColor("#000000"); // black

	initEditor();
}

addEvent(window, 'load', init);
