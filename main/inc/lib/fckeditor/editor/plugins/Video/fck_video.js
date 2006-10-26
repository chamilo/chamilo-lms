var oEditor		= window.parent.InnerDialogLoaded() ;
var FCK			= oEditor.FCK ;
var FCKLang		= oEditor.FCKLang ;
var FCKConfig	= oEditor.FCKConfig ;

// Set the dialog tabs.
window.parent.AddTab( 'Upload', FCKLang.DlgVideoUpload ) ;
window.parent.AddTab( 'Info', FCKLang.DlgVideoTab ) ;

// Function called when a dialog tag is selected.
function OnDialogTabChange( tabCode )
{
	ShowE('divUpload'	, ( tabCode == 'Upload' ) ) ;
	ShowE('divInfo'		, ( tabCode == 'Info' ) );
}

// Get the selected Video embed (if available).
var oFakeImage = FCK.Selection.GetSelectedElement() ;
var oEmbed ;

if ( oFakeImage )
{
	if ( oFakeImage.tagName == 'IMG' && oFakeImage.getAttribute('_fckVideo') )
		oEmbed = FCK.GetRealElement( oFakeImage ) ;
	else
		oFakeImage = null ;
}

window.onload = function()
{
	// Translate the dialog box texts.
	oEditor.FCKLanguageManager.TranslatePage(document) ;

	window.parent.SetSelectedTab( 'Upload' ) ;
	// Load the selected element information (if any).
	LoadSelection() ;

	// Show/Hide the "Browse Server" button.
	GetE('tdBrowse').style.display = FCKConfig.VideoBrowser	? '' : 'none' ;

	// Set the actual uploader URL.
	if ( FCKConfig.VideoUpload )
		GetE('frmUpload').action = FCKConfig.VideoUploadURL ;

	window.parent.SetAutoSize( true ) ;

	// Activate the "OK" button.
	//window.parent.SetOkButton( true ) ;
}

function LoadSelection()
{
	if ( ! oEmbed ) return ;

	//var sUrl = GetAttribute( oEmbed, 'src', '' ) ;

	GetE('txtUrl').value    = GetAttribute( oEmbed, 'src', '' ) ;
	GetE('controller').value    = 'true' ;
	//GetE('txtWidth').value  = GetAttribute( oEmbed, 'width', '' ) ;
	//GetE('txtHeight').value = GetAttribute( oEmbed, 'height', '' ) ;

	// Get Advances Attributes
	//GetE('txtAttId').value		= oEmbed.id ;
	//GetE('chkAutoPlay').checked	= GetAttribute( oEmbed, 'play', 'true' ) == 'true' ;
	//GetE('chkLoop').checked		= GetAttribute( oEmbed, 'loop', 'true' ) == 'true' ;

	UpdatePreview() ;
}

//#### The OK button was hit.
function Ok()
{
	if ( GetE('txtUrl').value.length == 0 )
	{
		window.parent.SetSelectedTab( 'Info' ) ;
		GetE('txtUrl').focus() ;

		alert( oEditor.FCKLang.DlgAlertUrl ) ;

		return false ;
	}

	if ( !oEmbed )
	{
		oEmbed		= FCK.EditorDocument.createElement( 'EMBED' ) ;
		oFakeImage  = null ;
	}
	UpdateEmbed( oEmbed ) ;
	
	if ( !oFakeImage )
	{
		oFakeImage	= oEditor.FCKDocumentProcessors_CreateFakeImage( 'FCK__Video', oEmbed ) ;
		oFakeImage.setAttribute( '_fckVideo', 'true', 0 ) ;
		oFakeImage	= FCK.InsertElementAndGetIt( oFakeImage ) ;
	}
	else
		oEditor.FCKUndo.SaveUndoStep() ;
	
	oEditor.FCKFlashProcessor.RefreshView( oFakeImage, oEmbed ) ;

	return true ;
}


function UpdateEmbed( e )
{
	//SetAttribute( e, 'type'			, 'application/x-shockwave-flash' ) ;
	//SetAttribute( e, 'pluginspage'	, 'http://www.macromedia.com/go/getflashplayer' ) ;

	SetAttribute( e, "controller", 'true' ) ;

	e.src = GetE('txtUrl').value ;
	//SetAttribute( e, "width" , GetE('txtWidth').value ) ;
	//SetAttribute( e, "height", GetE('txtHeight').value ) ;
	
	// Advances Attributes

	//SetAttribute( e, 'id'	, GetE('txtAttId').value ) ;
	//SetAttribute( e, 'scale', GetE('cmbScale').value ) ;
	
	//SetAttribute( e, 'play', GetE('chkAutoPlay').checked ? 'true' : 'false' ) ;
	//SetAttribute( e, 'loop', GetE('chkLoop').checked ? 'true' : 'false' ) ;
	//SetAttribute( e, 'menu', GetE('chkMenu').checked ? 'true' : 'false' ) ;

	//SetAttribute( e, 'title'	, GetE('txtAttTitle').value ) ;

	/*
	if ( oEditor.FCKBrowserInfo.IsIE )
	{
		SetAttribute( e, 'className', GetE('txtAttClasses').value ) ;
		e.style.cssText = GetE('txtAttStyle').value ;
	}
	else
	{
		SetAttribute( e, 'class', GetE('txtAttClasses').value ) ;
		SetAttribute( e, 'style', GetE('txtAttStyle').value ) ;
	}
	*/
}

function UpdatePreview()
{
	
	if ( GetE('txtUrl').value.length == 0 )
	{
		return;
	}
	else
	{
		window.parent.SetSelectedTab( 'Info' ) ;
	}
}

// <embed id="ePreview" src="fck_flash/claims.swf" width="100%" height="100%" style="visibility:hidden" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer">

function BrowseServer()
{
	OpenFileBrowser( FCKConfig.VideoBrowserURL, FCKConfig.VideoBrowserWindowWidth, FCKConfig.VideoBrowserWindowHeight ) ;
}

function SetUrl( url, width, height )
{
	GetE('txtUrl').value = url ;
	
	if ( width )
		GetE('txtWidth').value = width ;
		
	if ( height ) 
		GetE('txtHeight').value = height ;

	UpdatePreview() ;

	//window.parent.SetSelectedTab( 'Info' ) ;
	Ok();
	window.parent.close();

}

function OnUploadCompleted( errorNumber, fileUrl, fileName, customMsg )
{
	switch ( errorNumber )
	{
		case 0 :	// No errors
			//alert( 'Your file has been successfully uploaded' ) ;
			break ;
		case 1 :	// Custom error
			alert( customMsg ) ;
			return ;
		case 101 :	// Custom warning
			alert( customMsg ) ;
			break ;
		case 201 :
			alert( 'A file with the same name is already available. The uploaded file has been renamed to "' + fileName + '"' ) ;
			break ;
		case 202 :
			alert( 'Invalid file type' ) ;
			window.location.href=FCKConfig.PluginsPath + 'Video/fck_video.php';
			return ;
		case 203 :
			alert( "Security error. You probably don't have enough permissions to upload. Please check your server." ) ;
			window.location.href=FCKConfig.PluginsPath + 'Video/fck_video.php';
			return ;
		default :
			alert( 'Error on file upload. Error number: ' + errorNumber ) ;
			window.location.href=FCKConfig.PluginsPath + 'Video/fck_video.php';
			return ;
	}

	SetUrl( fileUrl ) ;
	GetE('frmUpload').reset() ;
}

var oUploadAllowedExtRegex	= new RegExp( FCKConfig.VideoUploadAllowedExtensions, 'i' ) ;
var oUploadDeniedExtRegex	= new RegExp( FCKConfig.VideoUploadDeniedExtensions, 'i' ) ;

function CheckUpload()
{
	var sFile = GetE('txtUploadFile').value ;
	
	if ( sFile.length == 0 )
	{
		alert( 'Please select a file to upload' ) ;
		return false ;
	}
	
	if ( ( FCKConfig.VideoUploadAllowedExtensions.length > 0 && !oUploadAllowedExtRegex.test( sFile ) ) ||
		( FCKConfig.VideoUploadDeniedExtensions.length > 0 && oUploadDeniedExtRegex.test( sFile ) ) )
	{
		OnUploadCompleted( 202 ) ;
		return false ;
	}
	
	return true ;
}

function OnSizeChanged( dimension, value ) 
{	
	UpdatePreview() ;
}
