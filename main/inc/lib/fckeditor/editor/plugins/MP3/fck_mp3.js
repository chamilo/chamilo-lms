var dialog		= window.parent ;
var oEditor = window.parent.InnerDialogLoaded() ;
var FCK		= oEditor.FCK ;
var FCKLang		= oEditor.FCKLang ;
var FCKConfig	= oEditor.FCKConfig ;
var FCKTools	= oEditor.FCKTools ;
var mp3_url="";

// Set the language direction.
window.document.dir = oEditor.FCKLang.Dir ;

// We have to avoid javascript errors if some language variables have not been defined.
FCKLang['UploadSelectFileFirst'] = FCKLang['UploadSelectFileFirst'] ? FCKLang['UploadSelectFileFirst'] : 'Please, select a file before pressing the upload button.' ;
FCKLang['FileSuccessfullyUploaded'] = FCKLang['FileSuccessfullyUploaded'] ? FCKLang['FileSuccessfullyUploaded'] : 'Your file has been successfully uploaded.' ;
FCKLang['FileRenamed'] = FCKLang['FileRenamed'] ? FCKLang['FileRenamed'] : 'A file with the same name is already available. The uploaded file has been renamed to ' ;
FCKLang['InvalidFileType'] = FCKLang['InvalidFileType'] ? FCKLang['InvalidFileType'] : 'Invalid file type.' ;
FCKLang['SecurityError'] = FCKLang['SecurityError'] ? FCKLang['SecurityError'] : 'Security error. You probably don\'t have enough permissions to upload. Please check your server.' ;
FCKLang['ConnectorDisabled'] = FCKLang['ConnectorDisabled'] ? FCKLang['ConnectorDisabled'] : 'The upload feature (connector) is disabled.' ;
FCKLang['UploadError'] = FCKLang['UploadError'] ? FCKLang['UploadError'] : 'Error on file upload. Error number: ' ;

// Set the dialog tabs.
window.parent.AddTab( 'Info', FCKLang.DlgMP3Tab ) ;
window.parent.AddTab( 'Upload', FCKLang.DlgMP3Upload ) ;

function OnDialogTabChange( tabCode )
{
	ShowE('divInfo'		, ( tabCode == 'Info' ) ) ;
	ShowE('divUpload'	, ( tabCode == 'Upload' ) ) ;
}

var sAgent = navigator.userAgent.toLowerCase() ;
var is_ie = (sAgent.indexOf("msie") != -1); // FCKBrowserInfo.IsIE
var is_gecko = !is_ie; // FCKBrowserInfo.IsGecko
var oMedia = null;


// Get the selected flash embed (if available).
var oFakeImage = dialog.Selection.GetSelectedElement() ;
var oEmbed ;

if ( oFakeImage )
{
	if ( oFakeImage.tagName == 'IMG' && oFakeImage.getAttribute('_fckflash') )
		oEmbed = FCK.GetRealElement( oFakeImage ) ;
	else
		oFakeImage = null ;
}

function window_onload(tab_to_select)
{
	// Translate the dialog box texts.
	oEditor.FCKLanguageManager.TranslatePage(document) ;

	if (!tab_to_select)
	{
		window.parent.SetSelectedTab( 'Info' ) ;
		//window.parent.SetSelectedTab( 'Upload' ) ;
	}
	else
	{
		window.parent.SetSelectedTab( tab_to_select ) ;
	}

	// Load the selected element information (if any).
	LoadSelection() ;

	// Show/Hide the "Browse Server" button.
	GetE('tdBrowse').style.display = FCKConfig.MP3Browser ? '' : 'none' ;

	// Set the actual uploader URL.
	if ( FCKConfig.MP3Upload )
	{
		GetE('frmUpload').action = FCKConfig.MP3UploadURL ;
	}

	window.parent.SetAutoSize( true ) ;

	// Activate the "OK" button.
	//window.parent.SetOkButton( true ) ;
}


/**
 * obtiene el elemento seleccionado
 * Gets the selected item.
 */
function getSelectedMovie()
{
	var oSel = null;

	// explorer..
	if (is_ie) {
		oSel = FCK.Selection.GetSelectedElement( 'OBJECT' );
	}

	// gecko
	else if (is_gecko) {
		var o = FCK.EditorWindow.getSelection() ;

		if ((o != null) && (o.anchorNode.tagName == 'OBJECT')) {
			oSel = o.anchorNode;
		}
	}

	// other
	else
	{
		alert ("Browser Not Supported");
	}

	return oSel;
}


function LoadSelection()
{
	oMedia = new Media();
	oMedia.setObjectElement(getSelectedMovie());
	GetE('mpUrl').value    	= getObjUrl(oMedia.url);
	updatePreview();
}

//#### The OK button was hit.
function Ok()
{
	if ( GetE('mpUrl').value.length == 0 )
	{
		window.parent.SetSelectedTab( 'Info' ) ;
		GetE('mpUrl').focus() ;
		alert( 'Please insert the URL.' ) ;
		return false ;
	}

	var oFakeImage  = null ;

	oEmbed = FCK.EditorDocument.createElement('embed');
	UpdateEmbed(oEmbed);

	//oObject = FCK.EditorDocument.createElement('object');
	//oObject.appendChild(oEmbed);

	/*SetAttribute(oObject, 'classid', 'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000');
	SetAttribute(oObject, 'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#6,0,0,0');
	SetAttribute(oObject, 'width', '300');
	SetAttribute(oObject, 'height', '20');

	oParamBgcolor = FCK.EditorDocument.createElement('param');
	SetAttribute(oParamBgcolor, 'value', '#FFFFFF');
	SetAttribute(oParamBgcolor, 'name', 'bgcolor');
	oObject.appendChild(oParamBgcolor);

	oParamMovie = FCK.EditorDocument.createElement('param');
	SetAttribute(oParamMovie, 'value', getObjData(GetE('mpUrl').value));
	SetAttribute(oParamMovie, 'name', 'movie');
	oObject.appendChild(oParamMovie);

	oParamWAllowfullscreen = FCK.EditorDocument.createElement('param');
	SetAttribute(oParamWAllowfullscreen, 'value', 'false');
	SetAttribute(oParamWAllowfullscreen, 'name', 'allowfullscreen');
	oObject.appendChild(oParamWAllowfullscreen);

	oParamScriptAccess = FCK.EditorDocument.createElement('param');
	SetAttribute(oParamScriptAccess, 'value', 'always');
	SetAttribute(oParamScriptAccess, 'name', 'allowscriptaccess');
	oObject.appendChild(oParamScriptAccess);

	oParamSRC = FCK.EditorDocument.createElement('param');
	SetAttribute(oParamSRC, 'value', 'file='+getSoundUrl()+'&autostart='+getAutostart());
	SetAttribute(oParamSRC, 'name', 'flashvars');
	oObject.appendChild(oParamSRC);*/

	oFakeImage	= oEditor.FCKDocumentProcessor_CreateFakeImage( 'FCK__MP3', oEmbed ) ;
	oFakeImage.setAttribute( '_fckmp3', 'true', 0 ) ;
	oFakeImage	= FCK.InsertElement( oFakeImage ) ;

	return true ;
}

function UpdateEmbed( e )
{
	SetAttribute( e, 'type'	, 'application/x-shockwave-flash' );
	SetAttribute( e, 'pluginspage'	, 'http://www.macromedia.com/go/getflashplayer' );
	SetAttribute( e, 'width'	, '300' );
	SetAttribute( e, 'height'	, '20' );
	SetAttribute( e, 'bgcolor'	, '#FFFFFF' );
	SetAttribute( e, 'src', getObjData(GetE('mpUrl').value));
	SetAttribute( e, 'allowfullscreen', 'false' );
	SetAttribute( e, 'allowscriptaccess', 'always' );
	SetAttribute( e, 'flashvars', 'file='+getSoundUrl()+'&autostart='+getAutostart());
}

/**
 * Obtiene los datos del form y actualiza el objeto..
 * Obtains data from the form and updates the object ...
 */
function updateMovie(e)
{
	e.url = GetE('mpUrl').value;
}

var ePreview ;
function SetPreviewElement( previewEl )
{
	ePreview = previewEl ;

	if ( GetE('mpUrl').value.length > 0 )
		updatePreview() ;
}

function updatePreview()
{
	if ( GetE('mpUrl').value.length == 0 ){
		return;
	}
	else {
		window.parent.SetSelectedTab( 'Info' ) ;
	}
}

function BrowseServer()
{
	// Set the browser window feature.
	var iWidth	= oEditor.FCKConfig.MP3BrowserWindowWidth ;
	var iHeight	= oEditor.FCKConfig.MP3BrowserWindowHeight ;

	var iLeft = (screen.width  - iWidth) / 2 ;
	var iTop  = (screen.height - iHeight) / 2 ;

	var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes" ;
	sOptions += ",width=" + iWidth ;
	sOptions += ",height=" + iHeight ;
	sOptions += ",left=" + iLeft ;
	sOptions += ",top=" + iTop ;

	// Open the browser window.
	var oWindow = window.open( oEditor.FCKConfig.MP3BrowserURL, "FCKBrowseWindow", sOptions ) ;
}

function SetUrl( url )
{
	document.getElementById('mpUrl').value = url ;
	//updatePreview();
	Ok();
	window.parent.Cancel();
}

var Media = function (o)
{
	this.url = '';
	this.width = '';
	this.height = '';
	if (o)
		this.setObjectElement(o);
};

/**
 * Toma los datos de un elemento.
 * Takes data from an item.
 */
Media.prototype.setObjectElement = function (e)
{
	if (!e) return ;
	this.width = GetAttribute( e, 'width', this.width );
	this.height = GetAttribute( e, 'height', this.height );
	this.url = GetAttribute( e, 'data', this.url );
	// params
	for (var i=0;i<e.childNodes.length;i++){
		if (e.childNodes[i].tagName == 'PARAM'){
			var paramName = GetAttribute(e.childNodes[i], 'name', '').toLowerCase();
			var paramValue = GetAttribute(e.childNodes[i], 'value', '');

			switch (paramName)
			{
				case 'movie':
					this.url = paramValue;
					break;
				case 'quality':
					this.quality = paramValue;
					break;
				case 'scale':
					this.scale = paramValue;
					break;
				case 'bgcolor':
					this.bgcolor = paramValue;
					break;
				case 'loop':
					this.loop = paramValue;
					break;
				case 'play':
					this.play = paramValue;
					break;
			}
		}
	}
};


/**
 * Devuelve el codigo HTML externo del elemento
 * Returns the HTML code of the external element
 */
Media.prototype.getOuterHTML = function (objectId){
	var s;
 	s= this.getInnerHTML(objectId);
 	return s;
};

/**
 * Devuelve el codigo HTML interno del elemento
 * Returns the HTML code inside the element
 */
Media.prototype.getInnerHTML = function (objectId)
{
	//var s = '<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" WIDTH="90" HEIGHT="25" id="test" ALIGN=""><PARAM NAME=movie VALUE="'+getObjData(this.url)+'?autostart='+getAutostart()+'&mp3file='+getSoundUrl()+'"> <PARAM NAME=quality VALUE=high> <PARAM NAME=bgcolor VALUE=#FFFFFF> <EMBED src="'+getObjData(this.url)+'?autostart='+getAutostart()+'&mp3file='+getSoundUrl()+'" quality=high bgcolor=#FFFFFF  WIDTH="90" HEIGHT="25" NAME="Streaming" ALIGN=""TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer"></EMBED></OBJECT>';
	var s=''; return s;
};

Media.prototype.createAttribute = function(n,v)
{
	return ' '+n+'="'+v+'" ';
}

function OnUploadCompleted( errorNumber, fileUrl, fileName, customMsg )
{
	switch ( errorNumber )
	{
		case 0 :	// No errors
			//alert( FCKLang['FileSuccessfullyUploaded'] ) ;
			break ;
		case 1 :	// Custom error
			alert( customMsg ) ;
			return ;
		case 101 :	// Custom warning
			alert( customMsg ) ;
			break ;
		case 201 :
			alert( FCKLang['FileRenamed'] + ' "' + fileName + '".' ) ;
			break ;
		case 202 :
			alert( FCKLang['InvalidFileType'] ) ;
			window.location.href=FCKConfig.PluginsPath + 'MP3/fck_mp3.php';
			return ;
		case 203 :
			alert( FCKLang['SecurityError'] ) ;
			window.location.href=FCKConfig.PluginsPath + 'MP3/fck_mp3.php';
			return ;
		case 500 :
			alert( FCKLang['ConnectorDisabled'] ) ;
			window.location.href=FCKConfig.PluginsPath + 'MP3/fck_mp3.php';
			return ;
		default :
			alert( FCKLang['UploadError'] + errorNumber ) ;
			window.location.href=FCKConfig.PluginsPath + 'MP3/fck_mp3.php';
			return ;
	}

	SetUrl( fileUrl ) ;
	//SetAutostart(GetE('autostart').value);

	GetE('frmUpload').reset() ;
	// Reset the interface elements.
	//document.getElementById('eUploadMessage').innerHTML = 'Upload' ;
	document.getElementById('btnUpload').disabled = false ;

}

var oUploadAllowedExtRegex	= new RegExp( FCKConfig.MP3UploadAllowedExtensions, 'i' ) ;
var oUploadDeniedExtRegex	= new RegExp( FCKConfig.MP3UploadDeniedExtensions, 'i' ) ;

function CheckUpload()
{
	var sFile = GetE('txtUploadFile').value ;

	if ( sFile.length == 0 )
	{
		alert( FCKLang['UploadSelectFileFirst'] ) ;
		return false ;
	}

	if ( ( FCKConfig.MP3UploadAllowedExtensions.length > 0 && !oUploadAllowedExtRegex.test( sFile ) ) ||
		( FCKConfig.MP3UploadDeniedExtensions.length > 0 && oUploadDeniedExtRegex.test( sFile ) ) )
	{
		OnUploadCompleted( 202 ) ;
		return false ;
	}
	//document.getElementById('eUploadMessage').innerHTML = 'Upload in progress, please wait...' ;
	document.getElementById('btnUpload').disabled = true ;
	return true ;
}

function getObjData(mpUrl)
{ 		// to create data attribute for object
		var url=mpUrl;
		//var configBasePath = FCKConfig.BasePath;
		//var cor_indx=configBasePath.indexOf("inc/")+4;
		//configBasePath.substring(0, cor_indx)+"lib/mp3player/player_mp3.swf";
		var objdata = rel_path+'inc/lib/mediaplayer/player.swf'; // real_path variable is defined in fck_mp3.php
		setSoundUrl(GetE('mpUrl').value);
		return objdata;
}

function setSoundUrl(url)
{
	// Added by Ivan Tcholakov.
	url = FCK.GetUrl( url, FCK.SEMI_ABSOLUTE_URL ) ;

	mp3_url = url ;
}

function getSoundUrl()
{
	return mp3_url;
}

function getAutostart()
{
	return GetE('autostart').checked;
}

function getObjUrl(mpUrl2)
{ // to get source url
		var url2=mpUrl2;
		var cor_indx2 = url2.indexOf("son=")+4;
		var objdata2 = url2.substring(cor_indx2, mpUrl2.length);
		return objdata2;
}
