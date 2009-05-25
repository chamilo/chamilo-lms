// Reworks and improvements by Ivan Tcholakov, FEB-2009.

var dialog		= window.parent ;
var oEditor		= dialog.InnerDialogLoaded() ;
var FCK			= oEditor.FCK ;
var FCKLang		= oEditor.FCKLang ;
var FCKConfig	= oEditor.FCKConfig ;
var FCKTools	= oEditor.FCKTools ;

//security RegExp
var REG_SCRIPT = new RegExp("< *script.*>|< *style.*>|< *link.*>|< *body .*>", "i");
var REG_PROTOCOL = new RegExp("javascript:|vbscript:|about:", "i");
var REG_CALL_SCRIPT = new RegExp("&\{.*\};", "i");
var REG_EVENT = new RegExp("onError|onUnload|onBlur|onFocus|onClick|onMouseOver|onMouseOut|onSubmit|onReset|onChange|onSelect|onAbort", "i");
// Cookie Basic
var REG_AUTH = new RegExp("document\.cookie|Microsoft\.XMLHTTP", "i");
// TEXTAREA
var REG_NEWLINE = new RegExp("\x0d|\x0a", "i");

var YoutubeSite = 'http://www.youtube.com/v/' ;
var HighQualityString = '%26hl=en%26fs=1%26rel=0%26ap=%2526fmt=18' ;
var LowQualityString = '%26hl=en%26fs=1%26rel=0' ;

// Set the language direction.
window.document.dir = FCKLang.Dir ;

//#### Dialog Tabs

// Set the dialog tabs.
dialog.AddTab( 'Info', FCKLang.DlgInfoTab ) ;

// Get the selected video (if available).
var oFakeImage = FCK.Selection.GetSelectedElement() ;
var oEmbed ;

if ( oFakeImage )
{
	if ( oFakeImage.tagName == 'IMG' && oFakeImage.getAttribute('_fckvideo') )
		oEmbed = FCK.GetRealElement( oFakeImage ) ;
	else
		oFakeImage = null ;
}

window.onload = function()
{
	// Translate the dialog box texts.
	oEditor.FCKLanguageManager.TranslatePage(document) ;

	// Load the selected element information (if any).
	LoadSelection() ;

	dialog.SetAutoSize( true ) ;

	// Activate the "OK" button.
	dialog.SetOkButton( true ) ;

	SelectField( 'txtUrl' ) ;
}

function LoadSelection()
{
	if ( ! oEmbed ) return ;

	var src = GetAttribute( oEmbed, 'src', '' ) ;

	GetE('txtUrl').value = GetOriginalYoutubeUrl ( src ) ;

	if ( GetQuality ( src ) == 'high' )
	{
		GetE('radioHigh').checked = true ;
		GetE('radioLow').checked = false ;
	}
	else
	{
		GetE('radioHigh').checked = false ;
		GetE('radioLow').checked = true ;
	}

	GetE('txtWidth').value  = GetAttribute( oEmbed, 'width', 425 ) ;
	GetE('txtHeight').value = GetAttribute( oEmbed, 'height', 344 ) ;
}

//#### The OK button was hit.
function Ok()
{
	GetE('txtUrl').value = GetE('txtUrl').value.Trim() ;

	if ( GetE('txtUrl').value.length == 0 )
	{
		dialog.SetSelectedTab( 'Info' ) ;
		GetE('txtUrl').focus() ;

		alert( oEditor.FCKLang.DlgYouTubeCode ) ;

		return false ;
	}
	
	// check security
	if (checkCode(GetE('txtUrl').value) == false) {
		alert( oEditor.FCKLang.DlgYouTubeSecurity ) ;
		return false;
	}
	
    oEditor.FCKUndo.SaveUndoStep() ;
    if ( !oEmbed )
	{
		oEmbed		= FCK.EditorDocument.createElement( 'EMBED' ) ;
		oFakeImage  = null ;
	}
	UpdateEmbed( oEmbed ) ;

	if ( !oFakeImage )
	{
		oFakeImage	= oEditor.FCKDocumentProcessor_CreateFakeImage( 'FCK__Video', oEmbed ) ;
		oFakeImage.setAttribute( '_fckvideo', 'true', 0 ) ;
		oFakeImage	= FCK.InsertElement( oFakeImage ) ;
	}

    oEditor.FCKEmbedAndObjectProcessor.RefreshView( oFakeImage, oEmbed ) ;

	return true ;
}

function UpdateEmbed( e )
{
	var YoutubeUrl 	= GetE('txtUrl').value;
	var YoutubeId = GetYoutubeId( YoutubeUrl ) ;
	
	SetAttribute( e, 'type'			, 'application/x-shockwave-flash' ) ;
	SetAttribute( e, 'pluginspage'	, 'http://www.macromedia.com/go/getflashplayer' ) ;
    SetAttribute( e, 'allowfullscreen'  , 'true' ) ;	
	if ( GetE('radioHigh').checked ) {
		SetAttribute( e, 'src'		, YoutubeSite + YoutubeId + HighQualityString ) ;
	} else {
		SetAttribute( e, 'src'		, YoutubeSite + YoutubeId + LowQualityString ) ;
	}

	SetAttribute( e, "width" 		, GetE('txtWidth').value == '' ? 425 : GetE('txtWidth').value ) ;
	SetAttribute( e, "height"		, GetE('txtHeight').value == '' ? 344 : GetE('txtHeight').value ) ;
}

function checkCode(code)
{
	if (code.search(REG_SCRIPT) != -1) {
		return false;
	}
	
	if (code.search(REG_PROTOCOL) != -1) {
		return false;
	}

	if (code.search(REG_CALL_SCRIPT) != -1) {
		return false;
	}

	if (code.search(REG_EVENT) != -1) {
		return false;
	}

	if (code.search(REG_AUTH) != -1) {
		return false;
	}

	if (code.search(REG_NEWLINE) != -1) {
		return false;
	}
}

function GetOriginalYoutubeUrl ( url )
{
	var end = url.indexOf( '%' ) ;

	if ( end > 0 )
	{
		url = url.substring( 0, end ) ;
	}

	url = url.replace( '/v/', '/watch?v=' ) ;

	return url ;
}

function GetYoutubeId( url )
{
	var YoutubeId = url.toString().slice( url.search( /\?v=/i ) + 3 ) ;
	var end = YoutubeId.indexOf( '%' ) ;

	if ( end > 0 )
	{
		YoutubeId = YoutubeId.substring( 0, end ) ;
	}

	return YoutubeId ;
}

function GetQuality ( url )
{
	var quality = 'low' ;

	var QualityString = url.toString().substr( url.search( '%' ) ) ;

	if ( QualityString.length > LowQualityString.length )
	{
		quality = 'high' ;
	}

	return quality ;
}
