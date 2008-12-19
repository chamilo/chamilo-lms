var dialog		= window.parent ;
var oEditor		= dialog.InnerDialogLoaded() ;
var FCK			= oEditor.FCK ;
var FCKLang		= oEditor.FCKLang ;
var FCKConfig	= oEditor.FCKConfig ;

//security RegExp
var REG_SCRIPT = new RegExp("< *script.*>|< *style.*>|< *link.*>|< *body .*>", "i");
var REG_PROTOCOL = new RegExp("javascript:|vbscript:|about:", "i");
var REG_CALL_SCRIPT = new RegExp("&\{.*\};", "i");
var REG_EVENT = new RegExp("onError|onUnload|onBlur|onFocus|onClick|onMouseOver|onMouseOut|onSubmit|onReset|onChange|onSelect|onAbort", "i");
// Cookie Basic
var REG_AUTH = new RegExp("document\.cookie|Microsoft\.XMLHTTP", "i");
// TEXTAREA
var REG_NEWLINE = new RegExp("\x0d|\x0a", "i");

//#### Dialog Tabs

// Set the dialog tabs.
dialog.AddTab( 'Info', oEditor.FCKLang.DlgInfoTab ) ;

// Get the selected flash embed (if available).
var oFakeImage = FCK.Selection.GetSelectedElement() ;
var oEmbed ;

window.onload = function()
{
	// Translate the dialog box texts.
	oEditor.FCKLanguageManager.TranslatePage(document) ;

	dialog.SetAutoSize( true ) ;

	// Activate the "OK" button.
	dialog.SetOkButton( true ) ;

	SelectField( 'txtUrl' ) ;
}

//#### The OK button was hit.
function Ok()
{
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
		oFakeImage	= oEditor.FCKDocumentProcessor_CreateFakeImage( 'FCK__Flash', oEmbed ) ;
		oFakeImage.setAttribute( '_fckflash', 'true', 0 ) ;
		oFakeImage	= FCK.InsertElement( oFakeImage ) ;
	}

    oEditor.FCKEmbedAndObjectProcessor.RefreshView( oFakeImage, oEmbed ) ;

	return true ;
}

function UpdateEmbed( e )
{
	var youtubeUrl 	= GetE('txtUrl').value;
	var youtubeId 	= youtubeUrl.slice(youtubeUrl.search(/\?v=/i)+3);
	
	SetAttribute( e, 'type'			, 'application/x-shockwave-flash' ) ;
	SetAttribute( e, 'pluginspage'	, 'http://www.macromedia.com/go/getflashplayer' ) ;
	
	if ( GetE('radioHigh').checked ) {
		SetAttribute( e, 'src'		, 'http://www.youtube.com/v/'+youtubeId+'%26hl=en%26fs=1%26rel=0%26ap=%2526fmt=18') ;
	} else {
		SetAttribute( e, 'src'		, 'http://www.youtube.com/v/'+youtubeId+'%26hl=en%26fs=1%26rel=0') ;
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