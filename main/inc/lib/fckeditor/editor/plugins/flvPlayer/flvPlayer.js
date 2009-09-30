// Reworks and improvements by Ivan Tcholakov, JUL-2009.

var dialog = window.parent ;
var oEditor = dialog.InnerDialogLoaded() ;
var FCK = oEditor.FCK ;
var FCKLang = oEditor.FCKLang ;
var FCKConfig = oEditor.FCKConfig ;
var FCKTools = oEditor.FCKTools ;

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
dialog.AddTab( 'Preview', FCKLang.DlgImgPreview ) ;
if ( FCKConfig.MediaUpload )
{
	dialog.AddTab( 'Upload', FCKLang.DlgLnkUpload ) ;
}

// This function is called when a dialog tab has been selected.
function OnDialogTabChange( tabCode )
{
	ShowE( 'divInfo', ( tabCode == 'Info' ) ) ;
	ShowE( 'divPreview', ( tabCode == 'Preview' ) ) ;
	ShowE( 'divUpload', ( tabCode == 'Upload' ) ) ;

	if ( tabCode == 'Preview' )
	{
		UpdatePreview() ;
	}
	else
	{
		ClearPreview() ;
	}
}

function OnDialogModeChange( mode )
{
	if ( mode == 'single')
	{
		btnBrowse.disabled = false ;
		btnImgBrowse.disabled = false ;
		btnLnkBrowse.disabled = true ;
		txtURL.disabled = false ;
		txtImgURL.disabled = false ;
		txtPlaylist.disabled = true ;
		txtPlaylist.value = '' ;
		txtURL.style.background = '#ffffff' ;
		txtImgURL.style.background = '#ffffff' ;
		txtPlaylist.style.background = 'transparent' ;
		selDispPlaylist.disabled = true ;
	}
	else
	{
		btnBrowse.disabled = true ;
		btnImgBrowse.disabled = true ;
		btnLnkBrowse.disabled = false ;
		txtURL.disabled = true ;
		txtImgURL.disabled = true ;
		txtPlaylist.disabled = false ;
		txtURL.value = '' ;
		txtImgURL.value = '' ;
		txtURL.style.background = 'transparent' ;
		txtImgURL.style.background = 'transparent' ;
		txtPlaylist.style.background = '#ffffff' ;
		selDispPlaylist.disabled = false ;
	}
}

var oMedia = null ;
var is_new_flvplayer = true ;

window.onload = function()
{
	// Translate the dialog box texts.
	oEditor.FCKLanguageManager.TranslatePage(document) ;

	// Load the selected element information (if any).
	LoadSelection() ;

	// Show/Hide the "Browse Server" button.
	GetE('tdBrowse').style.display = FCKConfig.MediaBrowser ? '' : 'none' ;

	// Set the actual uploader URL.
	if ( FCKConfig.MediaUpload )
		GetE('frmUpload').action = FCKConfig.MediaUploadURL ;

	dialog.SetAutoSize( true ) ;

	// Activate the "OK" button.
	dialog.SetOkButton( true ) ;
}

function getSelectedMovie()
{
	var oFakeImage = FCK.Selection.GetSelectedElement() ;
	var oSel = null ;
	oMedia = new Media() ;

	if ( oFakeImage )
	{
		if ( oFakeImage.tagName == 'IMG' && oFakeImage.getAttribute( '_fckvideo' ) )
		{
			oSel = FCK.GetRealElement( oFakeImage ) ;
			if ( oSel && oSel.id && oSel.id.match( /^player[0-9]*-parent$/ ) )
			{
				for ( var i = 0 ; i < oSel.childNodes.length ; i++ )
				{
					if ( oSel.childNodes.item(i).nodeName == "DIV" )
					{
						for ( var j = 0 ; j < oSel.childNodes.item(i).childNodes.length ; j++ )
						{
							if ( oSel.childNodes.item(i).childNodes.item(j).nodeName == "DIV" &&
								oSel.childNodes.item(i).childNodes.item(j).id &&
								oSel.childNodes.item(i).childNodes.item(j).id.match( /^player[0-9]*$/ ) )
							{
								for ( var k = 0 ; k < oSel.childNodes.item(i).childNodes.item(j).childNodes.length ; k++ )
								{
									if ( oSel.childNodes.item(i).childNodes.item(j).childNodes.item(k).nodeName == "DIV" &&
										oSel.childNodes.item(i).childNodes.item(j).childNodes.item(k).id &&
										oSel.childNodes.item(i).childNodes.item(j).childNodes.item(k).id.match( /^player[0-9]*-config$/ ) )
									{
										var oC = oSel.childNodes.item(i).childNodes.item(j).childNodes.item(k).innerHTML.split(' ') ;
										for ( var o = 0 ; o < oC.length ; o++ )
										{
											var tmp = oC[o].split( '=' ) ;
											oMedia.setAttribute( tmp[0], tmp[1] ) ;
										}
										is_new_flvplayer = false ;
										break ;
									}
								}
							}
						}
					}
				}
			}
		}
	}
	return oMedia ;
}

function updatePlaylistOption()
{
	if ( GetE( 'selDispPlaylist' ).value == "right" || GetE( 'selDispPlaylist' ).value == "below" )
	{
		GetE( 'chkPLThumbs' ).disabled = false ;
		GetE( 'chkPLThumbs' ).checked = true ;
		GetE( 'txtPLDim' ).disabled = false ;
		GetE( 'txtPLDim' ).style.background = '#ffffff' ;
		GetE( 'spanDimText' ).style.display = 'none' ;
		if ( GetE( 'selDispPlaylist' ).value == "right" )
		{
			GetE( 'spanDimWText' ).style.display = '' ;
			GetE( 'spanDimHText' ).style.display = 'none' ;
		}
		else if ( GetE( 'selDispPlaylist' ).value == "below" )
		{
			GetE( 'spanDimWText' ).style.display = 'none' ;
			GetE( 'spanDimHText' ).style.display = '' ;
		}
	}
	else
	{
		GetE( 'chkPLThumbs' ).disabled = true ;
		GetE( 'chkPLThumbs' ).checked = false ;
		GetE( 'txtPLDim' ).value = "" ;
		GetE( 'txtPLDim' ).disabled = true ;
		GetE( 'txtPLDim' ).style.background = 'transparent' ;
		GetE( 'spanDimText' ).style.display = '' ;
		GetE( 'spanDimWText' ).style.display = 'none' ;
		GetE( 'spanDimHText' ).style.display = 'none' ;
	}
}

function LoadSelection()
{
	oMedia = new Media() ;
	oMedia = getSelectedMovie() ;

	GetE( 'rbFileType' ).value = oMedia.fileType ;
	GetE( 'txtURL' ).value = oMedia.url ;
	GetE( 'txtPlaylist' ).value = oMedia.purl ;
	GetE( 'txtImgURL' ).value = oMedia.iurl ;
	GetE( 'txtWMURL' ).value = oMedia.wmurl ;
	GetE( 'txtWidth' ).value = oMedia.width.toString().length > 0 ? oMedia.width : 320 ;
	GetE( 'txtHeight' ).value = oMedia.height.toString().length > 0 ? oMedia.height : 240 ;
	GetE( 'chkLoop' ).checked = oMedia.loop ;
	GetE( 'chkAutoplay' ).checked	= oMedia.play ;
	GetE( 'chkDownload' ).checked = oMedia.downloadable ;
	GetE( 'chkFullscreen' ).checked = oMedia.fullscreen ;
	GetE( 'txtBgColor' ).value = oMedia.bgcolor ;
	GetE( 'txtToolbarColor' ).value = oMedia.toolcolor ;
	GetE( 'txtToolbarTxtColor' ).value = oMedia.tooltcolor ;
	GetE( 'txtToolbarTxtRColor' ).value = oMedia.tooltrcolor ;
	GetE( 'chkShowNavigation' ).checked = oMedia.displayNavigation ;
	GetE( 'chkShowDigits' ).checked = oMedia.displayDigits ;
	GetE( 'selAlign' ).value = oMedia.align ;
	GetE( 'selDispPlaylist' ).value = oMedia.dispPlaylist ;
	GetE('txtRURL' ).value = oMedia.rurl ;
	GetE( 'txtPLDim' ).value = oMedia.playlistDim ;
	GetE( 'chkPLThumbs' ).checked = oMedia.playlistThumbs ;

	UpdatePreview() ;
}

//#### The OK button was hit.
function Ok()
{
	var rbFileTypeVal = "single" ;
	if ( GetE( 'rbFileType' ).checked == false )
	{
		rbFileTypeVal = "list" ;
	}

	if ( rbFileTypeVal == "single" )
	{
		if ( GetE( 'txtURL' ).value.length == 0 )
		{
			GetE( 'txtURL' ).focus() ;

			alert( oEditor.FCKLang.DlgFLVPlayerAlertUrl ) ;
			return false ;
		}
	}

	if ( rbFileTypeVal == "list" )
	{
		if ( GetE( 'txtPlaylist' ).value.length == 0 )
		{
			GetE( 'txtPlaylist' ).focus() ;

			alert( oEditor.FCKLang.DlgFLVPlayerAlertPlaylist ) ;
			return false ;
		}
	}

	if ( GetE( 'txtWidth' ).value.length == 0 )
	{
		GetE( 'txtWidth' ).focus() ;

		alert( oEditor.FCKLang.DlgFLVPlayerAlertWidth ) ;
		return false ;
	}

	if ( GetE( 'txtHeight' ).value.length == 0 )
	{
		GetE( 'txtHeight' ).focus() ;

		alert( oEditor.FCKLang.DlgFLVPlayerAlertHeight ) ;
		return false ;
	}

	var e = ( oMedia || new Media() ) ;

	UpdateMovie( e ) ;

	// Replace or insert?
	if ( !is_new_flvplayer )
	{
		var oFakeImage = FCK.Selection.GetSelectedElement() ;
		var oSel = null ;
		oMedia = new Media() ;

		if ( oFakeImage )
		{
			if ( oFakeImage.tagName == 'IMG' && oFakeImage.getAttribute( '_fckvideo' ) )
			{
				oSel = FCK.GetRealElement( oFakeImage ) ;
				if ( oSel )
				{
					oSel = null ;
					FCK.InsertHtml( e.getInnerHTML() ) ;
				}
			}
		}
	}
	else
	{
		FCK.InsertHtml( e.getInnerHTML() ) ;
	}

	return true ;
}

function UpdateMovie( e )
{
	e.fileType = GetE( 'rbFileType' ).value ;
	e.url = GetE( 'txtURL' ).value ;
	e.purl = GetE( 'txtPlaylist' ).value ;
	e.iurl = GetE( 'txtImgURL' ).value ;
	e.wmurl = GetE( 'txtWMURL' ).value ;

	e.bgcolor = GetE( 'txtBgColor' ).value ;
	e.toolcolor = GetE( 'txtToolbarColor' ).value ;
	e.tooltcolor = GetE( 'txtToolbarTxtColor' ).value ;
	e.tooltrcolor = GetE( 'txtToolbarTxtRColor' ).value ;
	e.width = ( isNaN( GetE( 'txtWidth' ).value ) ) ? 0 : parseInt( GetE( 'txtWidth' ).value ) ;
	e.height = ( isNaN( GetE( 'txtHeight' ).value ) ) ? 0 : parseInt( GetE( 'txtHeight' ).value ) ;
	e.loop = ( GetE( 'chkLoop' ).checked ) ? 'true' : 'false' ;
	e.play = ( GetE( 'chkAutoplay' ).checked ) ? 'true' : 'false' ;
	e.downloadable = ( GetE( 'chkDownload' ).checked ) ? 'true' : 'false' ;
	e.fullscreen = ( GetE( 'chkFullscreen' ).checked ) ? 'true' : 'false' ;
	e.displayNavigation = ( GetE( 'chkShowNavigation' ).checked ) ? 'true' : 'false' ;
	e.displayDigits = ( GetE( 'chkShowDigits' ).checked) ? 'true' : 'false' ;
	e.align = GetE( 'selAlign' ).value ;
	e.dispPlaylist = GetE( 'selDispPlaylist' ).value ;

	e.rurl = GetE( 'txtRURL' ).value ;

	e.playlistDim = GetE( 'txtPLDim' ).value ;
	e.playlistThumbs = ( GetE( 'chkPLThumbs' ).checked ) ? 'true' : 'false' ;
}

function BrowseServer()
{
	OpenServerBrowser( 'flv', FCKConfig.MediaBrowserURL, FCKConfig.MediaBrowserWindowWidth, FCKConfig.MediaBrowserWindowHeight ) ;
}

function LnkBrowseServer()
{
	OpenServerBrowser( 'link', FCKConfig.LinkBrowserURL, FCKConfig.LinkBrowserWindowWidth, FCKConfig.LinkBrowserWindowHeight ) ;
}

function Lnk2BrowseServer()
{
	OpenServerBrowser( 'link2', FCKConfig.LinkBrowserURL, FCKConfig.LinkBrowserWindowWidth, FCKConfig.LinkBrowserWindowHeight ) ;
}

function img1BrowseServer()
{
	OpenServerBrowser( 'img1', FCKConfig.ImageBrowserURL, FCKConfig.ImageBrowserWindowWidth, FCKConfig.ImageBrowserWindowHeight ) ;
}

function img2BrowseServer()
{
	OpenServerBrowser( 'img2', FCKConfig.ImageBrowserURL, FCKConfig.ImageBrowserWindowWidth, FCKConfig.ImageBrowserWindowHeight ) ;
}

function OpenServerBrowser( type, url, width, height )
{
	sActualBrowser = type ;
	OpenFileBrowser( url, width, height ) ;
}

var sActualBrowser ;


function SetUrl( url ) {

	url = FCK.GetUrl( url, FCK.SEMI_ABSOLUTE_URL ) ;

	if ( sActualBrowser == 'flv' )
	{
		GetE('txtURL').value = url ;
		GetE('txtWidth').value = 320 ;
		GetE('txtHeight').value = 240 ;
	}
	else if ( sActualBrowser == 'link' )
	{
		GetE('txtPlaylist').value = url ;
	}
	else if ( sActualBrowser == 'link2' )
	{
		GetE('txtRURL').value = url ;
	}
	else if ( sActualBrowser == 'img1' )
	{
		GetE('txtImgURL').value = url ;
	}
	else if ( sActualBrowser == 'img2' )
	{
		GetE('txtWMURL').value = url ;
	}

	UpdatePreview() ;

	dialog.SetSelectedTab( 'Info' ) ;
}

var Media = function ( o )
{
	this.fileType = '' ;
	this.url = '' ;
	this.purl = '' ;
	this.iurl = '' ;
	this.wmurl = '' ;
	this.width = '' ;
	this.height = '' ;
	this.loop = '' ;
	this.play = '' ;
	this.downloadable = '' ;
	this.fullscreen = true ;
	this.bgcolor = '' ;
	this.toolcolor = '' ;
	this.tooltcolor = '' ;
	this.tooltrcolor = '' ;
	this.displayNavigation = true ;
	this.displayDigits = true ;
	this.align = '' ;
	this.dispPlaylist = '' ;
	this.rurl = '' ;
	this.playlistDim = '' ;
	this.playlistThumbs = '' ;

	if ( o )
		this.setObjectElement( o ) ;
} ;

Media.prototype.setObjectElement = function ( e )
{
	if ( !e ) return ;
	this.width = GetAttribute( e, 'width', this.width ) ;
	this.height = GetAttribute( e, 'height', this.height ) ;
} ;

Media.prototype.setAttribute = function( attr, val )
{
	if ( val == "true" )
	{
		this[attr] = true ;
	}
	else if (val == "false" )
	{
		this[attr] = false ;
	}
	else
	{
		this[attr] = val ;
	}
} ;

Media.prototype.getInnerHTML = function ( objectId )
{
	var randomnumber = Math.floor( Math.random() * 1000001 ) ;
	var thisWidth = this.width ;
	var thisHeight = this.height ;

	var thisMediaType = "single" ;
	if ( GetE( 'rbFileType' ).checked == false )
	{
		thisMediaType = "mpl" ;
	}

	// Align
	var cssalign = '' ;
	var cssfloat = '' ;
	if ( this.align == "center" )
	{
		cssalign = 'margin-left: auto;margin-right: auto;' ;
	}
	else if ( this.align == "right" )
	{
		cssfloat = 'float: right;' ;
	}
	else if ( this.align == "left" )
	{
		cssfloat = 'float: left;' ;
	}

	var s = "" ;
	s += '\n' ;
	s += '<div id="player' + randomnumber + '-parent" style="text-align: center;">\n'; //'<div id="player' + randomnumber + '-parent" style="text-align: center;' + cssfloat + '">\n';
	s += '<div style="border-style: none; height: ' + thisHeight + 'px; width: ' + thisWidth + 'px; overflow: hidden; background-color: rgb(220, 220, 220); ' + cssalign + '">' ;

	s += '<script src="' + FCKConfig.ScriptSWFObject + '" type="text/javascript"></script>\n' ;

	s += '<div id="player' + randomnumber + '">' ;
	s += '<a href="http://www.macromedia.com/go/getflashplayer" target="_blank">Get the Flash Player</a> to see this video.' ;
	// Moved after info - Added width,height,overflow for MSIE7
	s += '<div id="player' + randomnumber + '-config" style="display: none;visibility: hidden;width: 0px;height:0px;overflow: hidden;">' ;
	// Save settings
	for ( var i in this )
	{
		if ( !i || !this[i] ) continue ;
	        if ( !i.match( /(set|get)/ ) )
	        {
	        	s += i + "=" + this[i] + " " ;
        	}
	}
	s += '</div>' ;
	s += '</div>' ;
	s += '<script type="text/javascript">\n' ;
	//s += '	//NOTE: FOR LIST OF POSSIBLE SETTINGS GOTO http://www.jeroenwijering.com/extras/readme.html\n' ;

	s += '	var s1 = new SWFObject("' + FCKConfig.FlashPlayerVideo + '","' + thisMediaType + '","' + thisWidth + '","' + thisHeight + '","7");\n' ;

	s += '	s1.addVariable("width","' + thisWidth + '");\n' ;
	s += '	s1.addVariable("height","' + thisHeight + '");\n' ;
	s += '	s1.addVariable("autostart","' + this.play + '");\n' ;

	if ( thisMediaType == 'mpl' )
	{
		s += '	s1.addVariable("file","' + this.purl + '");\n' ;
		s += '	s1.addVariable("autoscroll","true");\n' ;
		s += '	s1.addParam("allowscriptaccess","always");\n' ;

		var dispWidth = thisWidth ;
		var dispHeight = thisHeight ;
		var dispThumbs = false ;

		if ( this.dispPlaylist != "none" )
		{
			if ( this.dispPlaylist == "right" )
			{
				if ( this.playlistDim.length > 0 )
				{
					dispWidth = thisWidth - this.playlistDim ;
					if ( this.playlistDim < 100 )
					{
						dispThumbs = false ;
					}
					else
					{
						dispThumbs = true ;
					}
				}
				else
				{
					if ( thisWidth >= 550 )
					{
						dispWidth = thisWidth - 200 ;
						dispThumbs = true ;
					}
					else if ( thisWidth >= 450 )
					{
						dispWidth = thisWidth - 100 ;
						dispThumbs = false ;
					}
					else if ( thisWidth >= 350 )
					{
						dispWidth = thisWidth - 50 ;
						dispThumbs = false ;
					}
				}

				s += '	s1.addVariable("displaywidth","' + dispWidth + '");\n' ;
			}
			else if ( this.dispPlaylist == "below" )
			{
				dispThumbs = true ;

				if ( this.playlistDim.length > 0 )
				{
					dispHeight = thisWidth - this.playlistDim ;
				}
				else
				{
					if ( thisHeight >= 550 )
					{
						dispHeight = thisWidth - 200 ;
					}
					else if ( thisHeight >= 450 )
					{
						dispHeight = thisHeight - 150 ;
					}
					else if ( thisHeight >= 350 )
					{
						dispHeight = thisHeight - 100 ;
					}
				}

				s += '	s1.addVariable("displayheight","' + dispHeight + '");\n' ;
			}

			if ( this.playlistThumbs == "false" )
			{
				dispThumbs = false ;
			}

			s += '	s1.addVariable("thumbsinplaylist","' + dispThumbs + '");\n' ;
		}

		s += '	s1.addVariable("shuffle","false");\n' ;
		if (this.loop == true)
		{
			s += '	s1.addVariable("repeat","list");\n' ;
		}
		else
		{
			s += '	s1.addVariable("repeat","' + this.loop + '");\n' ;
		}
		s += '	//s1.addVariable("transition","bgfade");\n' ;
	}
	else
	{
		s += '	s1.addVariable("file","' + this.url + '");\n' ;
		s += '	s1.addVariable("repeat","' + this.loop + '");\n' ;
		s += '	s1.addVariable("image","' + this.iurl + '");\n' ;
	}

	s += '	s1.addVariable("showdownload","' + this.downloadable + '");\n' ;
	s += '	s1.addVariable("link","' + this.url + '");\n' ;
	s += '	s1.addParam("allowfullscreen","' + this.fullscreen + '");\n' ;
	s += '	s1.addVariable("showdigits","' + this.displayDigits + '");\n' ;
	s += '	s1.addVariable("shownavigation","' + this.displayNavigation + '");\n' ;

	// SET THE COLOR OF THE TOOLBAR
	var colorChoice1 = this.toolcolor ;
	if ( colorChoice1.length > 0 )
	{
		colorChoice1 = colorChoice1.replace( "#", "0x" ) ;
		s += '	s1.addVariable("backcolor","' + colorChoice1 + '");\n' ;
	}
	// SET THE COLOR OF THE TOOLBARS TEXT AND BUTTONS
	var colorChoice2 = this.tooltcolor ;
	if ( colorChoice2.length > 0 )
	{
		colorChoice2 = colorChoice2.replace( "#", "0x" ) ;
		s += '	s1.addVariable("frontcolor","' + colorChoice2 + '");\n' ;
	}
	//SET COLOR OF ROLLOVER TEXT AND BUTTONS
	var colorChoice3 = this.tooltrcolor ;
	if ( colorChoice3.length > 0 )
	{
		colorChoice3 = colorChoice3.replace( "#", "0x" ) ;
		s += '	s1.addVariable("lightcolor","' + colorChoice3 + '");\n' ;
	}
	//SET COLOR OF BACKGROUND
	var colorChoice4 = this.bgcolor ;
	if ( colorChoice4.length > 0 )
	{
		colorChoice4 = colorChoice4.replace( "#", "0x" ) ;
		s += '	s1.addVariable("screencolor","' + colorChoice4 + '");\n' ;
	}

	s += '	s1.addVariable("logo","' + this.wmurl + '");\n' ;
	if ( this.rurl.length > 0 )
	{
		s += '	s1.addVariable("recommendations","' + this.rurl + '");\n' ;
	}

	//s += '	//s1.addVariable("largecontrols","true");\n' ;
	//s += '	//s1.addVariable("bufferlength","3");\n' ;
	//s += '	//s1.addVariable("audio","http://www.jeroenwijering.com/extras/readme.html");\n' ;

	s += '	s1.write("player' + randomnumber + '");\n' ;
	s += '</script>\n' ;
	s += '</div>\n' ;
	s += '</div>\n' ;
	s += '\n' ;

	return s ;
} ;

function SelectColor1()
{
	oEditor.FCKDialog.OpenDialog( 'FCKDialog_Color', oEditor.FCKLang.DlgColorTitle, 'dialog/fck_colorselector.html', 400, 330, SelectBackColor ) ;
}

function SelectColor2()
{
	oEditor.FCKDialog.OpenDialog( 'FCKDialog_Color', oEditor.FCKLang.DlgColorTitle, 'dialog/fck_colorselector.html', 400, 330, SelectToolColor ) ;
}

function SelectColor3()
{
	oEditor.FCKDialog.OpenDialog( 'FCKDialog_Color', oEditor.FCKLang.DlgColorTitle, 'dialog/fck_colorselector.html', 400, 330, SelectToolTextColor ) ;
}

function SelectColor4()
{
	oEditor.FCKDialog.OpenDialog( 'FCKDialog_Color', oEditor.FCKLang.DlgColorTitle, 'dialog/fck_colorselector.html', 400, 330, SelectToolTextRColor ) ;
}

function SelectBackColor( color )
{
	if ( color && color.length > 0 )
	{
		GetE( 'txtBgColor' ).value = color ;
	}
}

function SelectToolColor( color )
{
	if ( color && color.length > 0 )
	{
		GetE( 'txtToolbarColor' ).value = color ;
	}
}

function SelectToolTextColor( color )
{
	if ( color && color.length > 0 )
	{
		GetE( 'txtToolbarTxtColor' ).value = color ;
	}
}

function SelectToolTextRColor( color )
{
	if ( color && color.length > 0 )
	{
		GetE( 'txtToolbarTxtRColor' ).value = color ;
	}
}

var ePreview ;

function IsValidMedia( oMedia )
{
	if ( !oMedia )
		return false ;

	var url = oMedia.url ;
	var purl = oMedia.purl ;
	var width = oMedia.width ;
	var height = oMedia.height ;

	if ( url.length == 0 && purl.length == 0 )
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

	if ( IsValidMedia( oMedia ) )
		UpdatePreview() ;
}

function UpdatePreview()
{
	if ( !ePreview )
		return ;

	while ( ePreview.firstChild )
		ePreview.removeChild( ePreview.firstChild ) ;

	if ( !oMedia )
	{
		var oMedia = new Media() ;
		UpdateMovie( oMedia ) ;
	}

	if ( !IsValidMedia( oMedia ) )
		ePreview.innerHTML = '&nbsp;' ;
	else
	{
		var max_width = 710 ;
		var max_height = 400 ;
		var new_size = FCK.ResizeToFit( oMedia.width, oMedia.height, max_width, max_height ) ;
		oMedia.width = new_size[0] ;
		oMedia.height = new_size[1] ;
		oMedia.play = false ;

		code = oMedia.getInnerHTML() ;
		var start = code.indexOf( 'var s1 = new SWFObject' ) ;
		if ( start == -1 )
			return ;
		var end = code.indexOf( 's1.write' ) ;
		if ( end == -1 )
			return ;
		code = code.substring( start, end ) + 'html = s1.getSWFHTML();' ;
		var html = '';
		eval (code) ;

		ePreview.innerHTML = html ;

		var margin_left = parseInt( ( max_width - oMedia.width ) / 2, 10 ) ;
		var margin_top = parseInt( ( max_height - oMedia.height ) / 2, 10 ) ;

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

	OnDialogModeChange( 'single' ) ;
	sActualBrowser = 'flv' ;
	SetUrl( fileUrl ) ;
	GetE('frmUpload').reset() ;
}

var oUploadAllowedExtRegex = new RegExp( FCKConfig.MediaUploadAllowedExtensions, 'i' ) ;
var oUploadDeniedExtRegex = new RegExp( FCKConfig.MediaUploadDeniedExtensions, 'i' ) ;

function CheckUpload()
{
	var sFile = GetE( 'txtUploadFile' ).value ;

	if ( sFile.length == 0 )
	{
		alert( FCKLang['UploadSelectFileFirst'] ) ;
		return false ;
	}

	if ( ( FCKConfig.MediaUploadAllowedExtensions.length > 0 && !oUploadAllowedExtRegex.test( sFile ) ) ||
		( FCKConfig.MediaUploadDeniedExtensions.length > 0 && oUploadDeniedExtRegex.test( sFile ) ) )
	{
		OnUploadCompleted( 202 ) ;
		return false ;
	}

	// Show animation
	window.parent.Throbber.Show( 100 ) ;
	GetE( 'divUpload' ).style.display  = 'none' ;

	return true ;
}
