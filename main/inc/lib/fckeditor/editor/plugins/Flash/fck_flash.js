var oEditor = window.parent.InnerDialogLoaded() ;
var FCK		= oEditor.FCK ;

// Set the language direction.
window.document.dir = oEditor.FCKLang.Dir ;

// Set the Skin CSS.
document.write( '<link href="' + oEditor.FCKConfig.SkinPath + 'fck_dialog.css" type="text/css" rel="stylesheet">' ) ;

var sAgent = navigator.userAgent.toLowerCase() ;

var is_ie = ( sAgent.indexOf( "msie" ) != -1 ) ; // FCKBrowserInfo.IsIE
var is_gecko = !is_ie; // FCKBrowserInfo.IsGecko

// contendr� el object sobre el que trabajamos.
// Contains the object on which we work.
var oMedia = null ;


function window_onload()
{
	// Translate the dialog box texts.
	oEditor.FCKLanguageManager.TranslatePage(document) ;

	// Load the selected element information (if any).
	LoadSelection() ;

	// Show/Hide the "Browse Server" button.
	GetE('tdBrowse').style.display = oEditor.FCKConfig.FlashBrowser ? '' : 'none' ;

	// Activate the "OK" button.
	window.parent.SetOkButton( true ) ;
}


/**
 * obtiene el elemento seleccionado
 * Gets the selected item.
 */
function getSelectedMovie()
{
	var oSel = null ;

	// explorer..
	if ( is_ie )
	{
		oSel = FCK.Selection.GetSelectedElement( 'OBJECT' ) ;
	}
	
	// gecko
	else if ( is_gecko )
	{
		var o = FCK.EditorWindow.getSelection() ;

		if ( ( o != null ) && ( o.anchorNode.tagName == 'OBJECT' ) )
		{
			oSel = o.anchorNode ;
		}
	}	
	// other
	else
	{
		alert ( 'Browser Not Supported' ) ;
	}

	return oSel;
}


function LoadSelection()
{
	oMedia = new Media() ;
	oMedia.setObjectElement( getSelectedMovie() ) ;

	GetE( 'txtURL' ).value    	= oMedia.url ;

	GetE( 'txtVSpace' ).value	= oMedia.vspace ;
	GetE( 'txtHSpace' ).value	= oMedia.hspace ;
	GetE( 'selAlign' ).value	= oMedia.align ;
	GetE( 'txtWidth' ).value	= oMedia.width ;
	GetE( 'txtHeight' ).value	= oMedia.height ;
	GetE( 'selQuality' ).value	= oMedia.quality ;
	GetE( 'selScale' ).value	= oMedia.scale ;
	GetE( 'txtBgColor' ).value	= oMedia.bgcolor ;
	GetE( 'chkLoop' ).value		= oMedia.loop ;
	GetE( 'chkAutoplay' ).value	= oMedia.play ;

	updatePreview() ;
}

//#### The OK button was hit.
function Ok()
{
	if ( GetE( 'txtURL' ).value.length == 0 )
	{
		GetE( 'txtURL' ).focus() ;
		alert( oEditor.FCKLang.DlgFlashAlertUrl ) ;
		return false ;
	}

	//var e = ( oMedia || FCK.EditorDocument.createElement( 'OBJECT' ) ) ;
	var e = ( oMedia || new Media() ) ;

	e.url = GetE( 'txtURL' ).value ;
	
	updateMovie(e) ;	

	FCK.InsertHtml( e.getInnerHTML() ) ;
	return true ;
}

/**
 * Obtiene los datos del form y actualiza el objeto..
 * Obtains data from the form and updates the object ...
 */
function updateMovie( e )
{
	e.width = ( isNaN( GetE( 'txtWidth' ).value ) ) ? 0 : parseInt( GetE( 'txtWidth' ).value ) ;
	e.height = ( isNaN( GetE( 'txtHeight' ).value ) ) ? 0 : parseInt(GetE( 'txtHeight' ).value ) ;
	e.vspace = ( isNaN( GetE( 'txtVSpace' ).value ) ) ? 0 : parseInt(GetE( 'txtVSpace' ).value ) ;
	e.hspace = ( isNaN( GetE( 'txtHSpace' ).value ) ) ? 0 : parseInt(GetE( 'txtHSpace' ).value ) ;
	e.quality = GetE( 'selQuality' ).value ;
	e.scale = GetE( 'selScale' ).value ;
	e.align = GetE( 'selAlign' ).value ;
	e.bgcolor = GetE( 'txtBgColor' ).value ;
	e.loop = ( GetE( 'chkLoop' ).checked ) ? 'true' : 'false' ;
	e.play = ( GetE( 'chkAutoplay' ).checked ) ? 'true' : 'false' ;
}

function updatePreview()
{		 
	if ( GetE( 'txtURL' ).value.length == 0 )
	{
		ShowE( 'flashPreview', false ) ;
	}
	else
	{
		var preview = GetE( 'flashPreview' ) ;  
		
		oMedia.url = GetE( 'txtURL' ).value ;
		
		updateMovie( oMedia ) ;
		
		// preview.innerHTML dies on IE.. why?? :S
		if ( is_ie )
		{
			preview.outerHTML = oMedia.getInnerHTML( 'flashPreview' ) ;
		}
		
		// preview.outerHTML does nothing on gecko..
		if ( is_gecko )
		{
			oMedia.replaceObject( preview ) ;
		}
		
		ShowE( 'flashPreview', true ) ;	
	}
}

// Fired when the width or height input texts change
function OnSizeChanged( dimension, value ) 
{
	// Verifies if the aspect ration has to be mantained
	/*
	if ( oMovieOriginal && bLockRatio )
	{
		if ( value.length == 0 || isNaN( value ) )
		{
			GetE('txtHeight').value = GetE('txtWidth').value = '' ;
			return ;
		}
	
		if ( dimension == 'Width' )
			GetE('txtHeight').value = Math.round( oMovieOriginal.height * ( value  / oMovieOriginal.width ) ) ;
		else
			GetE('txtWidth').value  = Math.round( oMovieOriginal.width  * ( value / oMovieOriginal.height ) ) ;
	}
	*/
	
	updatePreview() ;
}

function BrowseServer()
{
	// Set the browser window feature.
	var iWidth	= oEditor.FCKConfig.FlashBrowserWindowWidth ;
	var iHeight	= oEditor.FCKConfig.FlashBrowserWindowHeight ;
	
	var iLeft = ( screen.width  - iWidth ) / 2 ;
	var iTop  = ( screen.height - iHeight ) / 2 ;

	var sOptions = "toolbar=no,status=no,resizable=yes,dependent=yes" ;
	sOptions += ",width=" + iWidth ; 
	sOptions += ",height=" + iHeight ;
	sOptions += ",left=" + iLeft ;
	sOptions += ",top=" + iTop ;
	
	// Open the browser window.	
	var oWindow = window.open( oEditor.FCKConfig.FlashBrowserURL, "FCKBrowseWindow", sOptions ) ;
}

function SetUrl( url )
{
	// Added by Ivan Tcholakov.
	//url = FCK.GetSelectedUrl( url ) ;
	url = FCK.GetSelectedFlashUrl( url ) ;

	document.getElementById( 'txtURL' ).value = url ;

	GetE( 'txtHeight' ).value = GetE( 'txtWidth' ).value = '' ;
	updatePreview() ;
}


/* ============================================================ */
/* Esta clase contendra el elemento. 							*/
/* This class will contain the element.                         */
/* ============================================================ */
//'url' : '',
//'width' : '',
//'height' : '',
//'quality' : 'high',
//'scale' : 'showall',
//'id' : '',
//'vspace' : '',
//'hspace' : '',
//'align' : '',
//'bgcolor' : '',
//'loop' : 'true',
//'play' : 'true'


var Media = function ( o )
{
	this.url = '' ;
	this.width = '' ;
	this.height = '' ;
	this.quality = 'high' ;
	this.scale = 'showall' ;
	this.id = '' ;
	this.vspace = '' ;
	this.hspace = '' ;
	this.align = '' ;
	this.bgcolor = '' ;
	this.loop = 'true' ;
	this.play = 'true' ;
	this.controller = 'true' ;
	
	if ( o )
	{
		this.setObjectElement( o ) ;
	}
} ;

/**
 * Toma los datos de un elemento.
 * 	Takes data from an item.
 */ 
Media.prototype.setObjectElement = function ( e )
{
	if (!e) return ;

	this.id = GetAttribute( e, 'id', this.id ) ;
	this.align = GetAttribute( e, 'align', this.align ) ;
	this.width = GetAttribute( e, 'width', this.width ) ;
	this.height = GetAttribute( e, 'height', this.height ) ;
	this.vspace = GetAttribute( e, 'vspace', this.vspace ) ;
	this.hspace = GetAttribute( e, 'hspace', this.hspace ) ;

	// params
	for ( var i = 0 ; i < e.childNodes.length ; i++ ) {
		if ( e.childNodes[i].tagName == 'PARAM' ) {
			var paramName = GetAttribute( e.childNodes[i], 'name', '' ).toLowerCase() ;
			var paramValue = GetAttribute( e.childNodes[i], 'value', '' ) ;

			switch ( paramName )
			{
				case 'movie' :
					this.url = paramValue ;
					break ;
				case 'quality' :
					this.quality = paramValue ;
					break ;
				case 'scale' :
					this.scale = paramValue ;
					break ;
				case 'bgcolor' :
					this.bgcolor = paramValue ;
					break ;
				case 'loop' :
					this.loop = paramValue ;
					break ;
				case 'play' :
					this.play = paramValue ;
					break ;
			}
		}
	}
} ;

Media.prototype.replaceObject = function( o )
{
	if ( !o ) return ;

	SetAttribute( o, 'classid', 'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' ) ;
	SetAttribute( o, 'codebase', 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0' ) ;

	if ( this.align != '' )
	{
		SetAttribute( o, 'align', this.align ) ;
	}
	if ( this.width > 0 )
	{
		SetAttribute( o, 'width', this.width ) ;
	}
	if ( this.height > 0 )
	{
		SetAttribute( o, 'height', this.height ) ;
	}
	if ( this.vspace > 0 )
	{
		SetAttribute( o, 'vspace', this.vspace ) ;
	}
	if ( this.hspace > 0 )
	{
		SetAttribute( o, 'hspace', this.height ) ;
	}

	o.innerHTML = this.getInnerHTML() ;
} ;

/**
 * Devuelve el valor de classid para el elemento que estamos visualizando.
 * Returns the value of classid for the item you're viewing.
 */
Media.prototype.getClassId = function ()
{
	var fl = 'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' ;
	var qt = 'clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B' ;

	if ( this.isFlash() )
	{
		return fl ;
	}
	else if ( this.isQuickTime() )
	{
		return qt ;
	}
}

/**
 * Devuelve el valor de codebase para el elemento que estamos visualizando.
 * Returns the value of codebase for the item you're viewing.
 */
Media.prototype.getCodeBase = function ()
{
	var fl = 'http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=5,0,0,0' ;
	var qt = 'http://www.apple.com/qtactivex/qtplugin.cab#version=6,0,2,0' ;

	if ( this.isFlash() )
	{
		return fl ;
	}
	else if ( this.isQuickTime() )
	{
		return qt ;
	}
}

/**
 * Devuelve el valor de pluginpage para el elemento que estamos visualizando.
 * Returns the value of pluginpage for the item you're viewing.
 */
Media.prototype.getPluginsPage = function ()
{
	var fl = 'http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash' ;
	var qt = 'http://www.apple.com/quicktime/download/indext.html' ;

	if ( this.isFlash() )
	{
		return fl ;
	}
	else if ( this.isQuickTime() )
	{
		return qt ;
	}
}

/**
 * Devuelve el valor de mime type para el elemento que estamos visualizando.
 * Returns the value of mime type for the item you're viewing.
 */
Media.prototype.getMimeType = function ()
{
	var fl = 'application/x-shockwave-flash' ;
	var qt = 'video/quicktime' ;

	if ( this.isFlash() )
	{
		return fl ;
	}
	else if ( this.isQuickTime() )
	{
		return qt ;
	}
}


/**
 * Devuelve el codigo HTML externo del elemento
 * Returns the HTML code of the external element
 */
Media.prototype.getOuterHTML = function ( objectId )
{
	var s ;

	s = '<object ' ;

	s += this.createAttribute( 'classid', this.getClassId() ) ;
	s += this.createAttribute( 'codebase', this.getCodeBase() ) ;

	// si me paso el id, lo uso..
	// if I pass the id, what use ...
	if ( objectId )
	{
		s += this.createAttribute( 'id', objectId ) ;
	}
	else if ( this.id != '' )
	{
		s += this.createAttribute( 'id', this.id ) ; 
	}
	if ( this.width > 0 )
	{
		s += this.createAttribute( 'width', this.width ) ;
	}
	if ( this.height > 0 )
	{
		s += this.createAttribute( 'height', this.height ) ;
	}
	if ( this.align != '' )
	{
		s += this.createAttribute( 'align', this.align ) ;
	}
	if ( this.vspace > 0 )
	{
		s += this.createAttribute( 'vspace', this.vspace ) ;
	}
	if ( this.hspace > 0 )
	{
		s += this.createAttribute( 'hspace', this.hspace ) ;
	}

	s += '>' ;
	s += this.getInnerHTML( objectId ) ;
	s += '</object>' ;
  
	return s ;
} ;


/**
 * Devuelve el codigo HTML interno del elemento
 * 	Returns the HTML code inside the element
 */
Media.prototype.getInnerHTML = function ( objectId )
{
	var s = '' ;
	
//	s += this.createParam( 'movie', this.url ) ;
//	s += this.createParam( 'src', this.url ) ;
//	s += this.createParam( 'quality', this.quality ) ;
//	s += this.createParam( 'scale', this.scale );
//	s += this.createParam( 'bgcolor', this.bgcolor ) ;
//	s += this.createParam( 'loop', this.loop ) ;
//	s += this.createParam( 'play', this.play ) ;
//	s += this.createParam( 'pluginspage', this.getPluginsPage() ) ;
//	s += this.createParam( 'type', this.getMimeType() ) ;
//	s += this.createParam( 'controller', this.controller ) ;
/*
	if ( objectId )		
		var my_id = objectId ;
	else 
	if ( this.id != '' ) 
		my_id = this.id ;	
	s += '<object ';
	if ( my_id > 0 )
		s += 'id="' + my_id  + '" ' ;
	if (this.align > 0)
		s += 'align="'+ this.align +'" ' ;
		
	if (this.height > 0)
		s += 'height="'+ this.height +'" ' ;	
	
	if (this.width > 0)
		s += 'width="'+ this.width +'" ' ;		
	s += 'codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" ' ;
	s += 'classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" > ' ;
	
	s += '<param value="' + this.url + '" name="movie"> ' ;
	if (this.quality > 0)
		s += '<param value="' + this.quality+ '" name="quality"> ' ;
	if (this.bgcolor > 0)
		s += '<param value="' + this.bgcolor+ '" name="quality"> ' ;
*/
	 s+= '<embed ' ;

	s += this.createAttribute( 'controller', this.controller ) ;
	s += this.createAttribute( 'pluginspage', this.getPluginsPage() ) ;
	s += this.createAttribute( 'type', this.getMimeType() ) ;
	s += this.createAttribute( 'src', this.url ) ;
	s += this.createAttribute( 'quality', this.quality ) ;
	s += this.createAttribute( 'scale', this.scale ) ;
	s += this.createAttribute( 'bgcolor', this.bgcolor ) ;
	s += this.createAttribute( 'loop', this.loop ) ;
	s += this.createAttribute( 'play', this.play ) ;
  
	if ( objectId )
	{
		s += this.createAttribute( 'id', objectId ) ;
	}
	else if ( this.id != '' )
	{
		s += this.createAttribute( 'id', this.id ) ;
	}
	if ( this.width > 0 )
	{
		s += this.createAttribute( 'width', this.width ) ;
	}
	if ( this.height > 0 )
	{
		s += this.createAttribute( 'height', this.height ) ;
	}
	if ( this.align != '' )
	{
		s += this.createAttribute( 'align', this.align ) ;
	}
	if (this.vspace > 0) 
	{
		s += this.createAttribute( 'vspace', this.vspace ) ;
	}
	if (this.hspace > 0)
	{
		s += this.createAttribute( 'hspace', this.hspace ) ;
	}

	s += '></embed>' ;
	/*
	s += '</object>' ;
	*/
	return s ;
} ;

Media.prototype.createParam = function( n, v )
{
	return '<param name="' + n + '" value="' + v + '">' ;
}

Media.prototype.createAttribute = function( n, v )
{
	return ' ' + n + '="' + v + '" ' ;
}

Media.prototype.isQuickTime = function ()
{
	return ( this.url.match( new RegExp( '.*\.mov$' ) ) != null ) ;
}

Media.prototype.isFlash = function ()
{
	return ( this.url.match( new RegExp( '.*\.swf$' ) ) != null ) ;
}

function SelectColor()
{
	oEditor.FCKDialog.OpenDialog( 'FCKDialog_Color', oEditor.FCKLang.DlgColorTitle, 'dialog/fck_colorselector.html', 400, 330, SelectBackColor, window ) ;
}

function SelectBackColor( color )
{
	if ( color && color.length > 0 )
	{
		GetE( 'txtBgColor' ).value = color ;
		updatePreview() ;
	}
}
