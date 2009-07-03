// Reworks and improvements by Ivan Tcholakov, JUL-2009.

var dialog		= window.parent ;
var oEditor		= dialog.InnerDialogLoaded() ;
var FCK			= oEditor.FCK ;
var FCKLang		= oEditor.FCKLang ;
var FCKConfig	= oEditor.FCKConfig ;
var FCKTools	= oEditor.FCKTools ;

// Security RegExp
var REG_SCRIPT = new RegExp( "< *script.*>|< *style.*>|< *link.*>|< *body .*>", "i" ) ;
var REG_PROTOCOL = new RegExp( "javascript:|vbscript:|about:", "i" ) ;
var REG_CALL_SCRIPT = new RegExp( "&\{.*\};", "i" ) ;
var REG_EVENT = new RegExp( "onError|onUnload|onBlur|onFocus|onClick|onMouseOver|onMouseOut|onSubmit|onReset|onChange|onSelect|onAbort", "i" ) ;
// Cookie Basic
var REG_AUTH = new RegExp( "document\.cookie|Microsoft\.XMLHTTP", "i" ) ;
// TEXTAREA
var REG_NEWLINE = new RegExp( "\x0d|\x0a", "i" ) ;

var YoutubeSite = 'http://www.youtube.com/v/' ;
var HighQualityString = '%26hl=en%26fs=1%26rel=0%26ap=%2526fmt=18' ;
var LowQualityString = '%26hl=en%26fs=1%26rel=0' ;

// Set the language direction.
window.document.dir = FCKLang.Dir ;

FCKLang['DlgYouTubeURLTipContent1'] = FCKLang['DlgYouTubeURLTipContent1'] ? FCKLang['DlgYouTubeURLTipContent1'] : '' ;
FCKLang['DlgYouTubeURLTipContent3'] = FCKLang['DlgYouTubeURLTipContent3'] ? FCKLang['DlgYouTubeURLTipContent3'] : '' ;
FCKLang['DlgYouTubeURLTipContent1'] = FCKLang['DlgYouTubeURLTipContent1'].toString().replace( '%s', '<a href="http://www.youtube.com/" target="_blank">http://www.youtube.com/<\/a>' ) ;
FCKLang['DlgYouTubeURLTipContent3'] = FCKLang['DlgYouTubeURLTipContent3'].toString().replace( '%s', 'http://www.youtube.com/watch?v=XXXXXXXXXXX...' ) ;
	
//#### Dialog Tabs

// Set the dialog tabs.
dialog.AddTab( 'Info', FCKLang.DlgInfoTab ) ;
dialog.AddTab( 'Preview', FCKLang.DlgImgPreview ) ;

// This function is called when a dialog tab has been selected.
function OnDialogTabChange( tabCode )
{
	ShowE( 'divInfo', ( tabCode == 'Info' ) ) ;

	ShowE( 'divPreview', ( tabCode == 'Preview' ) ) ;
	if ( tabCode == 'Preview' )
	{
		UpdatePreview() ;
	}
	else
	{
		ClearPreview() ;
	}
}

// Get the selected video (if available).
var oFakeImage = FCK.Selection.GetSelectedElement() ;
var oEmbed ;

if ( oFakeImage )
{
	if ( oFakeImage.tagName == 'IMG' && oFakeImage.getAttribute( '_fckvideo' ) )
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

	GetE( 'txtUrl' ).value = GetOriginalYoutubeUrl ( src ) ;

	if ( GetQuality ( src ) == 'high' )
	{
		GetE( 'radioHigh' ).checked = true ;
		GetE( 'radioLow' ).checked = false ;
	}
	else
	{
		GetE( 'radioHigh' ).checked = false ;
		GetE( 'radioLow' ).checked = true ;
	}

	GetE( 'txtWidth' ).value  = GetAttribute( oEmbed, 'width', 425 ) ;
	GetE( 'txtHeight' ).value = GetAttribute( oEmbed, 'height', 344 ) ;
}

//#### The OK button was hit.
function Ok()
{
	GetE( 'txtUrl' ).value = GetE( 'txtUrl' ).value.Trim() ;

	if ( GetE('txtUrl').value.length == 0 )
	{
		dialog.SetSelectedTab( 'Info' ) ;
		GetE( 'txtUrl' ).focus() ;

		alert( oEditor.FCKLang.DlgYouTubeCode ) ;

		return false ;
	}
	
	// Check security
	if ( checkCode( GetE( 'txtUrl' ).value ) == false )
	{
		alert( oEditor.FCKLang.DlgYouTubeSecurity ) ;
		return false ;
	}
	
    oEditor.FCKUndo.SaveUndoStep() ;
    if ( !oEmbed )
	{
		oEmbed = FCK.EditorDocument.createElement( 'EMBED' ) ;
		oFakeImage = null ;
	}
	UpdateEmbed( oEmbed ) ;

	if ( !oFakeImage )
	{
		oFakeImage = oEditor.FCKDocumentProcessor_CreateFakeImage( 'FCK__Video', oEmbed ) ;
		oFakeImage.setAttribute( '_fckvideo', 'true', 0 ) ;
		oFakeImage = FCK.InsertElement( oFakeImage ) ;
	}

    oEditor.FCKEmbedAndObjectProcessor.RefreshView( oFakeImage, oEmbed ) ;

	return true ;
}

function UpdateEmbed( e )
{
	var YoutubeUrl = GetE( 'txtUrl' ).value ;
	var YoutubeId = GetYoutubeId( YoutubeUrl ) ;
	
	SetAttribute( e, 'type', 'application/x-shockwave-flash' ) ;
	SetAttribute( e, 'pluginspage', 'http://www.macromedia.com/go/getflashplayer' ) ;
    SetAttribute( e, 'allowfullscreen', 'true' ) ;	
	if ( GetE( 'radioHigh' ).checked )
	{
		SetAttribute( e, 'src', YoutubeSite + YoutubeId + HighQualityString ) ;
	}
	else
	{
		SetAttribute( e, 'src', YoutubeSite + YoutubeId + LowQualityString ) ;
	}

	SetAttribute( e, 'width' , GetE( 'txtWidth' ).value == '' ? 425 : GetE( 'txtWidth' ).value ) ;
	SetAttribute( e, 'height', GetE( 'txtHeight' ).value == '' ? 344 : GetE( 'txtHeight' ).value ) ;
}

function checkCode( code )
{
	if ( code.search( REG_SCRIPT ) != -1 )
	{
		return false ;
	}
	
	if ( code.search( REG_PROTOCOL ) != -1 )
	{
		return false ;
	}

	if ( code.search( REG_CALL_SCRIPT ) != -1 )
	{
		return false ;
	}

	if ( code.search( REG_EVENT ) != -1 )
	{
		return false ;
	}

	if ( code.search( REG_AUTH ) != -1 )
	{
		return false ;
	}

	if ( code.search( REG_NEWLINE ) != -1 )
	{
		return false ;
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

var ePreview ;

function IsValidMedia( e )
{
	if ( !e )
		return false ;

	var src = GetAttribute( e, 'src', '' ) ;
	var width = GetAttribute( e, 'width', '' ) ;
	var height = GetAttribute( e, 'height', '' ) ;

	if ( src.length == 0 )
		return false ;

	if ( src.toString().toLowerCase().indexOf( 'youtube.com/v/%' ) != -1 )
		return false ;

	if ( isNaN( width ) )
		return false ;

	if ( parseInt( width, 10 ) <= 0 )
		return false ;

	if ( isNaN( height ) )
		return false ;

	if ( parseInt( height, 10 ) <= 0 )
		return false ;

	return true ;
}

function SetPreviewElement( previewEl )
{
	ePreview = previewEl ;

	if ( IsValidMedia( oEmbed ) )
		UpdatePreview() ;
}

function UpdatePreview()
{
	if ( !ePreview )
		return ;

	while ( ePreview.firstChild )
		ePreview.removeChild( ePreview.firstChild ) ;

	var oDoc = ePreview.ownerDocument || ePreview.document ;
	var e = oDoc.createElement( 'EMBED' ) ;
	UpdateEmbed( e ) ;

	if ( !IsValidMedia( e ) )
	{
		ClearPreview() ;
	}
	else
	{
		var max_width = 515 ;
		var max_height = 275 ;
		var width = GetAttribute( e, 'width', 425 ) ;
		var height = GetAttribute( e, 'height', 344 ) ;
		var new_size = FCK.ResizeToFit( width, height, max_width, max_height ) ;
		width = new_size[0] ;
		height = new_size[1] ;
		SetAttribute( e, 'width' , width ) ;
		SetAttribute( e, 'height', height ) ;

		ePreview.appendChild( e ) ;

		var margin_left = parseInt( ( max_width - width ) / 2, 10 ) ;
		var margin_top = parseInt( ( max_height - height ) / 2, 10 ) ;

		if ( ePreview.currentStyle )
		{
			// IE
			ePreview.style.marginLeft = margin_left ;
			ePreview.style.marginTop = margin_top ;
		}
		else
		{
			// Other browsers
			SetAttribute( ePreview, 'style', 'margin-left: ' + margin_left + 'px; margin-top: ' + margin_top + 'px;' ) ;
		}
	}
}

function ClearPreview()
{
	if ( !ePreview )
		return ;

	while ( ePreview.firstChild )
		ePreview.removeChild( ePreview.firstChild ) ;

	ePreview.innerHTML = '&nbsp;' ;
}
