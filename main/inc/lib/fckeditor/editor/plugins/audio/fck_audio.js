/*
 *	Dokeos - elearning and course management software
 *
 *	Copyright (c) 2008-2009 Dokeos SPRL
 *	Copyright (c) 2008-2009 Ivan Tcholakov <ivantcholakov@gmail.com>
 *
 *	For a full list of contributors, see "credits.txt".
 *	The full license can be read in "license.txt".
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 2
 *	of the License, or (at your option) any later version.
 *
 *	See the GNU General Public License for more details.
 *
 * Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
 * Mail: info@dokeos.com
 */

/*
 * This plugin uses as a template some original source code of the
 * FCKeditor 2.6.4, see for example the flash dialog or the image
 * properties dialog.
 */

var dialog		= window.parent ;
var oEditor		= dialog.InnerDialogLoaded() ;
var FCK			= oEditor.FCK ;
var FCKLang		= oEditor.FCKLang ;
var FCKConfig	= oEditor.FCKConfig ;
var FCKTools	= oEditor.FCKTools ;

// Set the language direction.
window.document.dir = FCKLang.Dir ;

// We have to avoid javascript errors if some language variables have not been defined.
FCKLang['UploadSelectFileFirst'] = FCKLang['UploadSelectFileFirst'] ? FCKLang['UploadSelectFileFirst'] : 'Please, select a file before pressing the upload button.' ;
FCKLang['FileSuccessfullyUploaded'] = FCKLang['FileSuccessfullyUploaded'] ? FCKLang['FileSuccessfullyUploaded'] : 'Your file has been successfully uploaded.' ;
FCKLang['FileRenamed'] = FCKLang['FileRenamed'] ? FCKLang['FileRenamed'] : 'A file with the same name is already available. The uploaded file has been renamed to ' ;
FCKLang['InvalidFileType'] = FCKLang['InvalidFileType'] ? FCKLang['InvalidFileType'] : 'Invalid file type.' ;
FCKLang['SecurityError'] = FCKLang['SecurityError'] ? FCKLang['SecurityError'] : 'Security error. You probably don\'t have enough permissions to upload. Please check your server.' ;
FCKLang['ConnectorDisabled'] = FCKLang['ConnectorDisabled'] ? FCKLang['ConnectorDisabled'] : 'The upload feature (connector) is disabled.' ;
FCKLang['UploadError'] = FCKLang['UploadError'] ? FCKLang['UploadError'] : 'Error on file upload. Error number: ' ;

//#### Dialog Tabs

// Set the dialog tabs.
dialog.AddTab( 'Info', FCKLang.DlgInfoTab ) ;

if ( FCKConfig.MP3Upload )
	dialog.AddTab( 'Upload', FCKLang.DlgLnkUpload ) ;


// Function called when a dialog tag is selected.
function OnDialogTabChange( tabCode )
{
	ShowE('divInfo'		, ( tabCode == 'Info' ) ) ;
	ShowE('divUpload'	, ( tabCode == 'Upload' ) ) ;
}

// We have to determine the path to access the Dokeos libraries.
var lib_path = window.location.toString().split( '/' ) ;
lib_path = lib_path.slice( 0, lib_path.length - 5 ) ;
lib_path = lib_path.join( '/' ) + '/' ;
lib_path = FCK.GetUrl( lib_path, FCK.SEMI_ABSOLUTE_URL ) ;

// This is the semi-absolute URL of the audio player.
var player = lib_path + 'mediaplayer/player.swf' ;


// Get the selected audio (if available).
var oFakeImage = dialog.Selection.GetSelectedElement() ;
var oEmbed ;

if ( oFakeImage )
{
	if ( oFakeImage.tagName == 'IMG' && oFakeImage.getAttribute('_fckmp3') )
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

	// Show/Hide the "Browse Server" button.
	GetE('tdBrowse').style.display = FCKConfig.MP3Browser ? '' : 'none' ;

	// Set the actual uploader URL.
	if ( FCKConfig.MP3Upload )
		GetE('frmUpload').action = FCKConfig.MP3UploadURL ;

	dialog.SetAutoSize( true ) ;

	// Activate the "OK" button.
	dialog.SetOkButton( true ) ;

	SelectField( 'txtUrl' ) ;
}

function LoadSelection()
{
	if ( ! oEmbed ) return ;

	var flashvars = GetAttribute( oEmbed, 'flashvars', '' ).toString() ;
	if ( flashvars.length == 0 ) return ;

	var start = flashvars.indexOf( 'file=' ) ;
	if ( start == -1 ) return ;
	start = start + 5 ;

	var stop = flashvars.indexOf( '&', start ) ;
	if ( stop == -1 ) stop = flashvars.length ;

	GetE('txtUrl').value = flashvars.substring( start, stop ) ;

	var autostart = '' ;

	start = flashvars.indexOf( 'autostart=' ) ;
	if ( start != -1 )
	{
		start = start + 10 ;
		stop = flashvars.indexOf( '&', start ) ;
		if ( stop == -1 ) stop = flashvars.length ;
		autostart = flashvars.substring( start, stop ) ;
	}

	GetE('chkAutoplay').checked	= ( autostart.toString() == 'true' ) ;

	UpdatePreview();
}

//#### The OK button was hit.
function Ok()
{
	if ( GetE('txtUrl').value.length == 0 )
	{
		dialog.SetSelectedTab( 'Info' ) ;
		GetE('txtUrl').focus() ;

		alert( oEditor.FCKLang.DlgAlertUrl ) ;

		return false ;
	}

	oEditor.FCKUndo.SaveUndoStep() ;
	if ( !oEmbed )
	{
		oEmbed		= FCK.EditorDocument.createElement( 'EMBED' ) ;
		oFakeImage  = null ;
	}
	UpdateEmbed(oEmbed);

	if ( !oFakeImage )
	{
		oFakeImage	= oEditor.FCKDocumentProcessor_CreateFakeImage( 'FCK__MP3', oEmbed ) ;
		oFakeImage.setAttribute( '_fckmp3', 'true', 0 ) ;
		oFakeImage	= FCK.InsertElement( oFakeImage ) ;
	}

	oEditor.FCKEmbedAndObjectProcessor.RefreshView( oFakeImage, oEmbed ) ;

	return true ;
}

function UpdateEmbed( e )
{
	SetAttribute( e, 'type', 'application/x-shockwave-flash' ) ;
	SetAttribute( e, 'pluginspage'	, 'http://www.macromedia.com/go/getflashplayer' ) ;
	SetAttribute( e, 'width', '300' ) ;
	SetAttribute( e, 'height', '20' ) ;
	SetAttribute( e, 'bgcolor', '#FFFFFF' ) ;
	SetAttribute( e, 'src', player ) ;
	SetAttribute( e, 'allowfullscreen', 'false' ) ;
	SetAttribute( e, 'allowscriptaccess', 'always' ) ;
	SetAttribute( e, 'flashvars', 'file=' + GetE( 'txtUrl' ).value + '&autostart=' + GetE('chkAutoplay').checked ) ;
}

var ePreview ;

function SetPreviewElement( previewEl )
{
	ePreview = previewEl ;

	if ( GetE( 'txtUrl' ).value.length > 0 )
		UpdatePreview() ;
}

function UpdatePreview()
{
	if ( !ePreview )
		return;

	while ( ePreview.firstChild )
		ePreview.removeChild( ePreview.firstChild ) ;

	if ( GetE('txtUrl').value.length == 0 )
		ePreview.innerHTML = '&nbsp;' ;
	else
	{
		var oDoc	= ePreview.ownerDocument || ePreview.document ;
		var e		= oDoc.createElement( 'EMBED' ) ;

		SetAttribute( e, 'type', 'application/x-shockwave-flash' ) ;
		SetAttribute( e, 'pluginspage', 'http://www.macromedia.com/go/getflashplayer' ) ;
		SetAttribute( e, 'width', '300' ) ;
		SetAttribute( e, 'height', '20' ) ;
		SetAttribute( e, 'bgcolor', '#FFFFFF' ) ;
		SetAttribute( e, 'src', player ) ;
		SetAttribute( e, 'allowfullscreen', 'false' ) ;
		SetAttribute( e, 'allowscriptaccess', 'always' ) ;
		SetAttribute( e, 'flashvars', 'file=' + GetE( 'txtUrl' ).value + '&autostart=false' ) ;

		var d = oDoc.createElement( 'DIV' ) ;
		SetAttribute( d, 'style', 'margin-right: auto; margin-left: auto; width: 300px;' ) ;
		SetAttribute( d, 'align', 'center' ) ; // IE6

		d.appendChild( e ) ;
		ePreview.appendChild( d ) ;
	}
}

function BrowseServer()
{
	OpenFileBrowser( FCKConfig.MP3BrowserURL, FCKConfig.MP3BrowserWindowWidth, FCKConfig.MP3BrowserWindowHeight ) ;
}

function SetUrl( url )
{
	//url = FCK.GetSelectedUrl( url ) ;
	url = FCK.GetUrl( url, FCK.SEMI_ABSOLUTE_URL ) ;

	GetE('txtUrl').value = url ;

	UpdatePreview() ;

	dialog.SetSelectedTab( 'Info' ) ;
}

function OnUploadCompleted( errorNumber, fileUrl, fileName, customMsg )
{
	// Remove animation
	window.parent.Throbber.Hide() ;
	GetE( 'divUpload' ).style.display  = '' ;

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
			return ;
		case 203 :
			alert( FCKLang['SecurityError'] ) ;
			return ;
		case 500 :
			alert( FCKLang['ConnectorDisabled'] ) ;
			break ;
		default :
			alert( FCKLang['UploadError'] + errorNumber ) ;
			return ;
	}

	SetUrl( fileUrl ) ;
	GetE('frmUpload').reset() ;
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

	// Show animation
	window.parent.Throbber.Show( 100 ) ;
	GetE( 'divUpload' ).style.display  = 'none' ;

	return true ;
}
