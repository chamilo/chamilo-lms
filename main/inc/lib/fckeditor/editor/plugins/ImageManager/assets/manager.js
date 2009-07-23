/**
 * Functions for the ImageManager, used by manager.php only	
 * @author $Author: Wei Zhuo $
 * @version $Id: manager.js 26 2004-03-31 02:35:21Z Wei Zhuo $
 * @package ImageManager
 */

	if ( window.opener )
	{
		var FCK = window.opener.FCK ;
	}
	else if ( window.parent )
	{
		var FCK = oEditor.FCK ;
	}
	
	//Translation
	function i18n(str) {
		if(I18N)
		  return (I18N[str] || str);
		else
			return str;
	};


	//set the alignment options
	function setAlign(align) 
	{
		var selection = document.getElementById('f_align');
		for(var i = 0; i < selection.length; i++)
		{
			if(selection.options[i].value == align)
			{
				selection.selectedIndex = i;
				break;
			}
		}
	}

	//initialise the form
	init = function () 
	{
		__dlg_init();

		if(I18N)
			__dlg_translate(I18N);

		var uploadForm = document.getElementById('uploadForm');
		if(uploadForm) uploadForm.target = 'imgManager';

		var param = window.dialogArguments;
		if (param) 
		{
			// strip the extra url details off the passed url. make sure the url still starts with a /
			param["f_url"] = stripBaseURL(param["f_url"]);
			if (param["f_url"].indexOf("/") != 0) param["f_url"] = '/'+param["f_url"];

			document.getElementById("f_url").value = param["f_url"];
			document.getElementById("f_url_alt").value = param["f_url_alt"];
			
			document.getElementById("f_alt").value = param["f_alt"];
			document.getElementById("f_border").value = param["f_border"];
			document.getElementById("f_vert").value = param["f_vert"];
			document.getElementById("f_horiz").value = param["f_horiz"];
			document.getElementById("f_width").value = param["f_width"];
			document.getElementById("f_height").value = param["f_height"];
			setAlign(param["f_align"]);
		}

		document.getElementById("f_url").focus();
	}

	// Need to strip the base url (and any appended http://... stuff if editing an image
    function stripBaseURL (string) {
		// strip off the server name if it exists
		if (base_url.indexOf('://') == -1) {
			string = string.replace('https://'+server_name,'');
			string = string.replace('http://'+server_name,'');
		}

        // strip off the base url if it exists
        string = string.replace(base_url, '');
		return string;
    
	};


	function onCancel() 
	{
		__dlg_close(null);
		return false;
	};

	function onOK() 
	{
		// pass data back to the calling window
		var fields = ["f_url", "f_alt", "f_align", "f_border", "f_horiz", "f_vert", "f_height", "f_width","f_file"];
		var param = new Object();

		for (var i in fields) 
		{
			var id = fields[i];
			var el = document.getElementById(id);
			
			// A modification by Ivan Tcholakov
			/*
			if(id == "f_url" && el.value.indexOf('://') < 0 )
			{
				param[id] = makeURL(base_url_alt,el.value);				
				var str = el.value;
				var str2 = str;		
				var len = str.length;			
				if(str.substring(0,1) == '/')
				{					
					str2 = str2.substring(1,len);
				}						
				param['f_url_alt']=base_url+str2;				
			}
			else 
			{
				param[id] = el.value;
			}
			*/
			if (id == "f_url")
			{
				str = el.value.toString().Trim();
				if ( str.indexOf('://') < 0 )
				{
					if ( !isSemiAbsoluteUrl( str ) ) // A semi-absolute URL could be entered manually.
					{
						str = makeURL( base_url, str ); // Converting temporarily the URL to be absolute.
					}
					else if (str.charAt(0) == '/')
					{
						str = str.substring(1);
					}
				}
				param["f_url"] = FCK.GetSelectedUrl(str);
				param['f_url_alt'] = FCK.GetSelectedUrl( str ); // Accepting the URL with conversion to a relative one if it is applicable.
			}
			else 
			{
				param[id] = el.value;
			}

		}
		__dlg_close(param);
		return false;
	};

	// A special check: This function tries to distinguish semi-absolute URLs.
	// Reason to introduce: The Image manager is designed to use relative URLs with '/' at their beginning.
	function isSemiAbsoluteUrl( url )
	{
        if ( url.indexOf( '/' ) == -1 ) return false;

        base = base_url;
		if ( base_url.indexOf( '://' ) == -1 )
		{
			var serverBase = FCK.GetServerBase() ;
			if ( serverBase == FCK.GetServerBase( base ) )
			{
				base = '/' + base.substr( serverBase.length ) ;
			}
		}

        if ( url.indexOf( base ) == 0 ) return true ;
        return false;
	}

	// This method has been taken from the FCKEditor's source.
	String.prototype.Trim = function()
	{
		return this.replace( /(^[ \t\n\r]*)|([ \t\n\r]*$)/g, '' ) ;
	}

	//similar to the Files::makeFile() in Files.php
	function makeURL(pathA, pathB) 
	{
		if(pathA.substring(pathA.length-1) != '/')
			pathA += '/';

		if(pathB.charAt(0) == '/');	
			pathB = pathB.substring(1);

		return pathA+pathB;
	}


	function updateDir(selection) 
	{
		var newDir = selection.options[selection.selectedIndex].value;
		changeDir(newDir);
	}

	function goUpDir() 
	{
		var selection = document.getElementById('dirPath');
		var currentDir = selection.options[selection.selectedIndex].text;
		if(currentDir.length < 2)
			return false;
		var dirs = currentDir.split('/');
		
		var search = '';

		for(var i = 0; i < dirs.length - 2; i++)
		{
			search += dirs[i]+'/';
		}

		for(var i = 0; i < selection.length; i++)
		{
			var thisDir = selection.options[i].text;
			if(thisDir == search)
			{
				selection.selectedIndex = i;
				var newDir = selection.options[i].value;
				changeDir(newDir);
				break;
			}
		}
	}

	function changeDir(newDir) 
	{
		if(typeof imgManager != 'undefined')
			imgManager.changeDir(newDir);
	}

	function toggleConstrains(constrains) 
	{
		var lockImage = document.getElementById('imgLock');
		var constrains = document.getElementById('constrain_prop');

		if(constrains.checked) 
		{
			lockImage.src = "img/locked.gif";	
			checkConstrains('width') 
		}
		else
		{
			lockImage.src = "img/unlocked.gif";	
		}
	}

	function checkConstrains(changed) 
	{
		//alert(document.form1.constrain_prop);
		var constrains = document.getElementById('constrain_prop');
		
		if(constrains.checked) 
		{
			var obj = document.getElementById('orginal_width');
			var orginal_width = parseInt(obj.value);
			var obj = document.getElementById('orginal_height');
			var orginal_height = parseInt(obj.value);

			var widthObj = document.getElementById('f_width');
			var heightObj = document.getElementById('f_height');
			
			var width = parseInt(widthObj.value);
			var height = parseInt(heightObj.value);

			if(orginal_width > 0 && orginal_height > 0) 
			{
				if(changed == 'width' && width > 0) {
					heightObj.value = parseInt((width/orginal_width)*orginal_height);
				}

				if(changed == 'height' && height > 0) {
					widthObj.value = parseInt((height/orginal_height)*orginal_width);
				}
			}			
		}
	}

	function showMessage(newMessage) 
	{
		var message = document.getElementById('message');
		var messages = document.getElementById('messages');
		if(message.firstChild)
			message.removeChild(message.firstChild);

		message.appendChild(document.createTextNode(i18n(newMessage)));
		
		messages.style.display = "block";
	}

	function addEvent(obj, evType, fn)
	{ 
		if (obj.addEventListener) { obj.addEventListener(evType, fn, true); return true; } 
		else if (obj.attachEvent) {  var r = obj.attachEvent("on"+evType, fn);  return r;  } 
		else {  return false; } 
	} 

	function doUpload() 
	{
		
		var uploadForm = document.getElementById('uploadForm');
		if(uploadForm)
			showMessage('Uploading');
	}

	function refresh()
	{
		var selection = document.getElementById('dirPath');
		updateDir(selection);
	}


	function newFolder() 
	{
		var selection = document.getElementById('dirPath');
		var dir = selection.options[selection.selectedIndex].value;

		Dialog("newFolder.html", function(param) 
		{
			if (!param) // user must have pressed Cancel
				return false;
			else
			{
				var folder = param['f_foldername'];
				if(folder == thumbdir)
				{
					alert(i18n('Invalid folder name, please choose another folder name.'));
					return false;
				}

				if (folder && folder != '' && typeof imgManager != 'undefined') 
					imgManager.newFolder(dir, encodeURI(folder)); 
			}
		}, null);
	}

	addEvent(window, 'load', init);
