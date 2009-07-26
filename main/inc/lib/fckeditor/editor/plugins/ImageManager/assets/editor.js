/**
 * Functions for the ImageEditor interface, used by editor.php only	
 * @author $Author: Wei Zhuo $
 * @author $Author: Frédéric Klee <fklee@isuisse.com> $ - constraints toggle and check
 * @author $Author: Paul Moers <mail@saulmade.nl> $ - watermarking and replace code + several small enhancements <http://www.saulmade.nl/FCKeditor/FCKPlugins.php>
 * @version $Id: editor.js 2006-04-09 $
 * @package ImageManager
 */

	var current_action = null;
	var actions = ['crop', 'scale', 'rotate', 'measure', 'save', 'watermark', 'replace'];
	var orginal_width = null, orginal_height=null;
	function toggle(action) 
	{
		if(current_action != action)
		{
			// hiding watermark
			if (editor.window.document.getElementById("imgCanvas"))
			{
				editor.window.document.getElementById("imgCanvas").style.display = "block";
				editor.window.document.getElementById("background").style.display = "none";
				if (editor.window.watermarkingEnabled == true)
				{
					editor.window.dd.elements.floater.hide();
				}
			}

			for (var i in actions)
			{
				if(actions[i] != action)
				{
					var tools = document.getElementById('tools_'+actions[i]);
					tools.style.display = 'none';
					var icon = document.getElementById('icon_'+actions[i]);
					icon.className = '';
				}
			}

			current_action = action;
			
			var tools = document.getElementById('tools_'+action);
			tools.style.display = 'block';
			var icon = document.getElementById('icon_'+action);
			icon.className = 'iconActive';

			var indicator = document.getElementById('indicator_image');
			indicator.src = 'img/'+action+'.gif';

			editor.setMode(current_action);

			// if watermark action, show watermark
			if(action == 'watermark') 
			{
				if (editor.window.document.getElementById("imgCanvas"))
				{
					editor.window.document.getElementById("imgCanvas").style.display = "none";
					editor.window.document.getElementById("background").style.display = "block";
					if (editor.window.watermarkingEnabled == true)
					{
					editor.window.dd.elements.floater.show();
					editor.window.dd.elements.floater.moveTo(0, 0);
					editor.window.verifyBounds();
					}
				}
			}

			//constraints on the scale,
			//code by Frédéric Klee <fklee@isuisse.com>
			if(action == 'scale') 
			{
				var theImage = editor.window.document.getElementById('theImage');
				orginal_width = theImage.width ;
				orginal_height = theImage.height;

                var w = document.getElementById('sw');
				w.value = orginal_width ;
				var h = document.getElementById('sh') ;
				h.value = orginal_height ;
			}
		}
	}

	function toggleMarker() 
	{
		var marker = document.getElementById("markerImg");
		
		if(marker != null && marker.src != null) {
			if(marker.src.indexOf("t_black.gif") >= 0)
				marker.src = "img/t_white.gif";
			else
				marker.src = "img/t_black.gif";

			editor.toggleMarker();
		}
	}

	//Toggle constraints
	function toggleConstraints() 
	{
		var lock = document.getElementById("scaleConstImg");
		var checkbox = document.getElementById("constProp");
		
		if(lock != null && lock.src != null) {
			if(lock.src.indexOf("unlocked2.gif") >= 0)
			{
				lock.src = "img/islocked2.gif";
				checkbox.checked = true;
				checkConstrains('width');

			}
			else
			{
				lock.src = "img/unlocked2.gif";
				checkbox.checked = false;
			}
		}
	}
	
	//check the constraints
	function checkConstrains(changed) 
	{
		var constrained = document.getElementById('constProp');
		if(constrained.checked) 
		{
			var w = document.getElementById('sw') ;
			var width = w.value ;
			var h = document.getElementById('sh') ;
			var height = h.value ;
			
			if(orginal_width > 0 && orginal_height > 0) 
			{
				if(changed == 'width' && width > 0) 
					h.value = parseInt((width/orginal_width)*orginal_height);
				else if(changed == 'height' && height > 0) 
					w.value = parseInt((height/orginal_height)*orginal_width);
			}
		}
		
		updateMarker('scale') ;
	}


	function updateMarker(mode) 
	{
		if (mode == 'crop')
		{
			var t_cx = document.getElementById('cx');
			var t_cy = document.getElementById('cy');
			var t_cw = document.getElementById('cw');
			var t_ch = document.getElementById('ch');

			editor.setMarker(parseInt(t_cx.value), parseInt(t_cy.value), parseInt(t_cw.value), parseInt(t_ch.value));
		}
		else if(mode == 'scale') {
			var s_sw = document.getElementById('sw');
			var s_sh = document.getElementById('sh');
			editor.setMarker(0, 0, parseInt(s_sw.value), parseInt(s_sh.value));
		}
	}

	
	function rotatePreset(selection) 
	{
		var value = selection.options[selection.selectedIndex].value;
		
		if(value.length > 0 && parseInt(value) != 0) {
			var ra = document.getElementById('ra');
			ra.value = parseInt(value);
		}
	}

	function updateFormat(selection) 
	{
		var selected = selection.options[selection.selectedIndex].value;

		var values = selected.split(",");
		if(values.length >1) {
			updateSlider(parseInt(values[1]));
		}

	}

	function doUpload() 
	{
		// hiding action buttons
		var buttons = parent.document.getElementById('buttons');
		buttons.style.display = 'none';
		// hiding current action's controls
		var tools = parent.document.getElementById('tools_replace');
		tools.style.display = 'none';

		// try to restrict the user from closing the editor window when uploading
		parent.old = (parent.onbeforeunload) ? parent.onbeforeunload : function () {};
		parent.onbeforeunload = function () {parent.old(); alert(i18n("Please do not close the window while uploading a new image! If you do, the original image gets deleted!")); return false;};
		
		var uploadForm = document.getElementById('uploadForm');
		if(uploadForm)
			showMessage('Uploading');
	}

	// show processing message
	function showMessage(newMessage) 
	{
		var message = document.getElementById('message');
		var messages = document.getElementById('messages');
		if(message.firstChild)
			message.removeChild(message.firstChild);

		message.appendChild(document.createTextNode(i18n(newMessage)));
		
		messages.style.display = "block";
	}

	// hide message
	function hideMessage() 
	{
		var messages = document.getElementById('messages');
		messages.style.display = "none";
	}

	// change watermark
	function changeWatermark(source)
	{
		if (editor.window.watermarkingEnabled)
		{
			floater = editor.window.dd.elements.floater;
			floater.swapImage(eval("editor.window." + source.options[source.selectedIndex].value + "Preload.src"));
			floater.resizeTo(source.options[source.selectedIndex].getAttribute("x"), source.options[source.selectedIndex].getAttribute("y"));
			editor.window.verifyBounds();
		}
	}

	// change watermark opacity
	function changeWatermarkOpacity(opacity)
	{
		if (editor.window.watermarkingEnabled)
		{
			floater = editor.window.dd.elements.floater;

			// IE/Win
			floater.css.filter = "alpha(opacity:" + opacity + ")";
			// Safari < 1.2, Konqueror
			floater.css.KHTMLopacity = opacity / 100;
			// Older Mozilla and Firefox
			floater.css.Mozopacity = opacity / 100;
			// Safari 1.2, newer Firefox and Mozilla, CSS3
			floater.css.opacity = opacity / 100;
		}
	}

	// change watermark position
	function moveWatermark(x, y)
	{
		if (editor.window.watermarkingEnabled)
		{
			floater = editor.window.dd.elements.floater;
			background = editor.window.dd.elements.background;

			x = background.x + (background.w - floater.w) * x;
			y = background.y + (background.h - floater.h) * y;
			
			floater.moveTo(x, y);
		}
	}

	// color the background of the watermark
	function colorWatermarkBG(color)
	{
		if (background = editor.window.document.getElementById("background"))
		{
			// saving background image and removing
			if (background.style.backgroundImage != "" && background.style.backgroundImage != "url(img/backgroundGrid.gif)")
			{
				this.backgroundImage = background.style.backgroundImage;
			}
			background.style.backgroundImage = "";

			// show background image
			if (color == "")
			{
				background.style.backgroundImage = this.backgroundImage;
				background.style.backgroundColor = "";
			}
			// show grid
			else if(color == "grid")
			{
				background.style.backgroundImage = "url(img/backgroundGrid.gif)";
				background.style.backgroundColor = "";
			}
			// set color
			else
			{
				background.style.backgroundColor = color;
			}
		}
	}

	//Translation
	function i18n(str) {
		if(I18N)
		  return (I18N[str] || str);
		else
			return str;
	};

	function addEvent(obj, evType, fn)
	{ 
		if (obj.addEventListener) { obj.addEventListener(evType, fn, true); return true; } 
		else if (obj.attachEvent) {  var r = obj.attachEvent("on"+evType, fn);  return r;  } 
		else {  return false; } 
	} 

	init = function()
	{
		var bottom = document.getElementById('bottom');
		__dlg_init(bottom);

		if(I18N)
		{
			__dlg_translate(I18N);
		}
	}

	addEvent(window, 'load', init);
