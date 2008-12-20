/*
 *	Dokeos - elearning and course management software
 *
 *	Copyright (c) 2004-2008 Dokeos S.A.
 *	Copyright (c) 2003 Ghent University (UGent)
 *	Copyright (c) 2001 Universite catholique de Louvain (UCL)
 *	Copyright (c) 2008 Julio Montoya
 *	Copyright (c) 2008 Ivan Tcholakov
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
 *	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
 *	Mail: info@dokeos.com
 */


/*
 * This plugin uses also fragments of the original source code of
 * FCKeditor version 2.6.4 SVN, Build 21065 (nightly, 06-DEC-2008).
 * 
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2008 Frederico Caldeira Knabben
 *
 * == BEGIN LICENSE ==
 *
 * Licensed under the terms of any of the following licenses at your
 * choice:
 *
 *  - GNU General Public License Version 2 or later (the "GPL")
 *    http://www.gnu.org/licenses/gpl.html
 *
 *  - GNU Lesser General Public License Version 2.1 or later (the "LGPL")
 *    http://www.gnu.org/licenses/lgpl.html
 *
 *  - Mozilla Public License Version 1.1 or later (the "MPL")
 *    http://www.mozilla.org/MPL/MPL-1.1.html
 *
 * == END LICENSE ==
 */


/*
 **************************************************************************************
 * Customizations by Julio Montoya for enabling the external template selection dialog.
 * December, 2008
 **************************************************************************************
 */

FCKToolbarButton.prototype.ClickFrame = function()
{
	var A = this._ToolbarButton || this;
	return FCK.ToolbarSet.CurrentInstance.Commands.GetCommand(A.CommandName).ExecuteFrame() ;
};

FCKDialogCommand.prototype.ExecuteFrame = function()
{
	return FCKDialog.OpenDialogFrame( 'FCKDialog_' + this.Name, this.Title, this.Url, this.Width, this.Height, this.CustomValue, null, this.Resizable ) ;
};

var FCKDialog = ( function()
{
	var topDialog ;
	var baseZIndex ;
	var cover ;

	// The document that holds the dialog.
	var topWindow = window.parent ;

	while ( topWindow.parent && topWindow.parent != topWindow )
	{
		try
		{
			if ( topWindow.parent.document.domain != document.domain )
				break ;
			if ( topWindow.parent.document.getElementsByTagName( 'frameset' ).length > 0 )
				break ;
		}
		catch ( e )
		{
			break ;
		}
		topWindow = topWindow.parent ;
	}

	var topDocument = topWindow.document ;

	var getZIndex = function()
	{
		if ( !baseZIndex )
			baseZIndex = FCKConfig.FloatingPanelsZIndex + 999 ;
		return ++baseZIndex ;
	}

	// TODO : This logic is not actually working when reducing the window, only
	// when enlarging it.
	var resizeHandler = function()
	{
		if ( !cover )
			return ;

		var relElement = FCKTools.IsStrictMode( topDocument ) ? topDocument.documentElement : topDocument.body ;

		FCKDomTools.SetElementStyles( cover,
			{
				'width' : Math.max( relElement.scrollWidth,
					relElement.clientWidth,
					topDocument.scrollWidth || 0 ) - 1 + 'px',
				'height' : Math.max( relElement.scrollHeight,
					relElement.clientHeight,
					topDocument.scrollHeight || 0 ) - 1 + 'px'
			} ) ;
	}

	return {
		/**
		 * Opens a dialog window using the standard dialog template.
		 */
		OpenDialog : function( dialogName, dialogTitle, dialogPage, width, height, customValue, parentWindow, resizable )
		{
			if ( !topDialog )
				this.DisplayMainCover() ;

			// Setup the dialog info to be passed to the dialog.
			var dialogInfo =
			{
				Title : dialogTitle,
				Page : dialogPage,
				Editor : window,
				CustomValue : customValue,		// Optional
				TopWindow : topWindow
			}

			FCK.ToolbarSet.CurrentInstance.Selection.Save( true ) ;

			// Calculate the dialog position, centering it on the screen.
			var viewSize = FCKTools.GetViewPaneSize( topWindow ) ;
			var scrollPosition = { 'X' : 0, 'Y' : 0 } ;
			var useAbsolutePosition = FCKBrowserInfo.IsIE && ( !FCKBrowserInfo.IsIE7 || !FCKTools.IsStrictMode( topWindow.document ) ) ;
			if ( useAbsolutePosition )
				scrollPosition = FCKTools.GetScrollPosition( topWindow ) ;
			var iTop  = Math.max( scrollPosition.Y + ( viewSize.Height - height - 20 ) / 2, 0 ) ;
			var iLeft = Math.max( scrollPosition.X + ( viewSize.Width - width - 20 )  / 2, 0 ) ;

			// Setup the IFRAME that will hold the dialog.
			var dialog = topDocument.createElement( 'iframe' ) ;
			FCKTools.ResetStyles( dialog ) ;
			dialog.src = FCKConfig.BasePath + 'fckdialog.html' ;

			// Dummy URL for testing whether the code in fckdialog.js alone leaks memory.
			// dialog.src = 'about:blank';

			dialog.frameBorder = 0 ;
			dialog.allowTransparency = true ;
			FCKDomTools.SetElementStyles( dialog,
					{
						'position'	: ( useAbsolutePosition ) ? 'absolute' : 'fixed',
						'top'		: iTop + 'px',
						'left'		: iLeft + 'px',
						'width'		: width + 'px',
						'height'	: height + 'px',
						'zIndex'	: getZIndex()
					} ) ;

			// Save the dialog info to be used by the dialog page once loaded.
			dialog._DialogArguments = dialogInfo ;

			// Append the IFRAME to the target document.
			topDocument.body.appendChild( dialog ) ;

			// Keep record of the dialog's parent/child relationships.
			dialog._ParentDialog = topDialog ;
			topDialog = dialog ;
		},

		/*
		 * Added by Julio Montoya for enabling the external template selection dialog.
		 ***************************************************************************************
		 */

		OpenDialogFrame: function( dialogName, dialogTitle, dialogPage, width, height, customValue, parentWindow, resizable )
		{
			//if ( !topDialog )
			//	this.DisplayMainCover() ;

			var dialogInfo = 
			{
				Title: dialogTitle,
				Page: dialogPage,
				Editor: window,
				CustomValue: customValue,
				TopWindow: topWindow
			} ;

			//FCK.ToolbarSet.CurrentInstance.Selection.Save();
			FCK.ToolbarSet.CurrentInstance.Selection.Save( true ) ;

			var viewSize = FCKTools.GetViewPaneSize( topWindow ) ;
			var scrollPosition = { 'X': 0, 'Y': 0 } ;
			var useAbsolutePosition = FCKBrowserInfo.IsIE && ( !FCKBrowserInfo.IsIE7 || !FCKTools.IsStrictMode( topWindow.document ) ) ;
			if (useAbsolutePosition) scrollPosition = FCKTools.GetScrollPosition( topWindow ) ;
			var iTop = Math.max(scrollPosition.Y + ( viewSize.Height - height - 20 ) / 2, 0 ) ;
			var iLeft = Math.max(scrollPosition.X + ( viewSize.Width - width - 20 ) / 2, 0 ) ;

			var dialog = topDocument.createElement( 'iframe' ) ;
			//FCKTools.ResetStyles( dialog );
			dialog.src = FCKConfig.BasePath + 'fckdialogframe.html' ;

			dialog.frameBorder = 0 ;
			dialog.allowTransparency = true ;
			FCKDomTools.SetElementStyles(dialog,
			{
				'position'	: (useAbsolutePosition) ? 'absolute' : 'fixed',
				'top'		: iTop + 'px',
				'left'		: iLeft + 'px',
				'width'		: width + 'px',
				'height'	: height + 'px',
				'zIndex'	: getZIndex()
			}) ;

			dialog._DialogArguments = dialogInfo ;

			//E.body.appendChild( dialog ) ;

			dialog._ParentDialog = topDialog ;
			topDialog = dialog ;

			return dialogInfo ;
		},

		/*
		 ***************************************************************************************
		 */

		/**
		 * (For internal use)
		 * Called when the top dialog is closed.
		 */
		OnDialogClose : function( dialogWindow )
		{
			var dialog = dialogWindow.frameElement ;
			FCKDomTools.RemoveNode( dialog ) ;

			if ( dialog._ParentDialog )		// Nested Dialog.
			{
				topDialog = dialog._ParentDialog ;
				// Modified by Ivan Tcholakov, caused errors during tests.
				//dialog._ParentDialog.contentWindow.SetEnabled( true ) ;
				if (dialog._ParentDialog.contentWindow)
				{
					dialog._ParentDialog.contentWindow.SetEnabled( true ) ;
				}
				//
			}
			else							// First Dialog.
			{
				// Set the Focus in the browser, so the "OnBlur" event is not
				// fired. In IE, there is no need to do that because the dialog
				// already moved the selection to the editing area before
				// closing (EnsureSelection). Also, the Focus() call here
				// causes memory leak on IE7 (weird).
				if ( !FCKBrowserInfo.IsIE )
					FCK.Focus() ;

				this.HideMainCover() ;
				// Bug #1918: Assigning topDialog = null directly causes IE6 to crash.
				setTimeout( function(){ topDialog = null ; }, 0 ) ;

				// Release the previously saved selection.
				FCK.ToolbarSet.CurrentInstance.Selection.Release() ;
			}
		},

		DisplayMainCover : function()
		{
			// Setup the DIV that will be used to cover.
			cover = topDocument.createElement( 'div' ) ;
			FCKTools.ResetStyles( cover ) ;
			FCKDomTools.SetElementStyles( cover,
				{
					'position' : 'absolute',
					'zIndex' : getZIndex(),
					'top' : '0px',
					'left' : '0px',
					'backgroundColor' : FCKConfig.BackgroundBlockerColor
				} ) ;
			FCKDomTools.SetOpacity( cover, FCKConfig.BackgroundBlockerOpacity ) ;

			// For IE6-, we need to fill the cover with a transparent IFRAME,
			// to properly block <select> fields.
			if ( FCKBrowserInfo.IsIE && !FCKBrowserInfo.IsIE7 )
			{
				var iframe = topDocument.createElement( 'iframe' ) ;
				FCKTools.ResetStyles( iframe ) ;
				iframe.hideFocus = true ;
				iframe.frameBorder = 0 ;
				iframe.src = FCKTools.GetVoidUrl() ;
				FCKDomTools.SetElementStyles( iframe,
					{
						'width' : '100%',
						'height' : '100%',
						'position' : 'absolute',
						'left' : '0px',
						'top' : '0px',
						'filter' : 'progid:DXImageTransform.Microsoft.Alpha(opacity=0)'
					} ) ;
				cover.appendChild( iframe ) ;
			}

			// We need to manually adjust the cover size on resize.
			FCKTools.AddEventListener( topWindow, 'resize', resizeHandler ) ;
			resizeHandler() ;

			topDocument.body.appendChild( cover ) ;

			FCKFocusManager.Lock() ;

			// Prevent the user from refocusing the disabled
			// editing window by pressing Tab. (Bug #2065)
			var el = FCK.ToolbarSet.CurrentInstance.GetInstanceObject( 'frameElement' ) ;
			el._fck_originalTabIndex = el.tabIndex ;
			el.tabIndex = -1 ;
		},

		HideMainCover : function()
		{
			FCKDomTools.RemoveNode( cover ) ;
			FCKFocusManager.Unlock() ;

			// Revert the tab index hack. (Bug #2065)
			var el = FCK.ToolbarSet.CurrentInstance.GetInstanceObject( 'frameElement' ) ;
			el.tabIndex = el._fck_originalTabIndex ;
			FCKDomTools.ClearElementJSProperty( el, '_fck_originalTabIndex' ) ;
		},

		GetCover : function()
		{
			return cover ;
		}
	} ;
} )() ;


/*
 **************************************************************************************
 * Customizations by the Dokeos Company to make all the plugins compatible.
 **************************************************************************************
 */


/*
 **************************************************************************************
 * Fake images support
 **************************************************************************************
 */

// This is a modification of the original function.
FCKDocumentProcessor_CreateFakeImage = function( fakeClass, realElement )
{
	var real = FCKTools.GetElementDocument( realElement ) ;
	var oImg = real.createElement( 'IMG' ) ;
	oImg.className = fakeClass ;
	oImg.src = FCKConfig.BasePath + 'images/spacer.gif' ;
	oImg.setAttribute( '_fckfakelement', 'true', 0 ) ;
	oImg.setAttribute( '_fckrealelement', FCKTempBin.AddElement( realElement ), 0 ) ;
	if ( fakeClass == 'FCK__Video' )
	{
		if ( real.width )
		{
			oImg.style.width = FCKTools.ConvertHtmlSizeToStyle( real.width ) ;
		}
		if ( real.height )
		{
			oImg.style.height = FCKTools.ConvertHtmlSizeToStyle( real.height ) ;
		}
	}
	return oImg ;
}

// A custom handler for audio files when a new tag has been added.
FCKEmbedAndObjectProcessor.AddCustomHandler( function ( el, fakeImg )
	{
		if ( !FCK.is_audio( el ) )
		{
			return ;
		}

		fakeImg.className = 'FCK__MP3' ;
		fakeImg.setAttribute( '_fckmp3', 'true', 0 ) ;
	} ) ;

// Fake images for audio files when the document has been opened.
FCKDocumentProcessor.AppendNew().ProcessDocument = function ( document )
	{
		var embeds = document.getElementsByTagName( 'embed' ) ; 
		var embed ;
		var i = embeds.length - 1 ; 
		while ( i >= 0 && ( embed = embeds[i--] ) )
		{
			if ( FCK.is_audio( embed ) )
			{
				var oImg = FCKDocumentProcessor_CreateFakeImage( 'FCK__MP3', embed.cloneNode(true) ) ;
				oImg.setAttribute( '_fckmp3', 'true', 0 ) ;
				embed.parentNode.insertBefore( oImg, embed ) ;
				embed.parentNode.removeChild( embed ) ;			
			}
		}
	} ;

// A custom handler for video when a new tag has been added.
FCKEmbedAndObjectProcessor.AddCustomHandler( function ( el, fakeImg )
	{
		if ( !FCK.is_video( el ) )
		{
			return ;
		}

		fakeImg.className = 'FCK__Video' ;
		fakeImg.setAttribute( '_fckvideo', 'true', 0 ) ;
	} ) ;

// Fake images for video when the document has been opened.
FCKDocumentProcessor.AppendNew().ProcessDocument = function ( document )
	{
		var embeds = document.getElementsByTagName( 'embed' ) ; 
		var embed;
		var i = embeds.length - 1 ; 
		while ( i >= 0 && ( embed = embeds[i--] ) )
		{
			if ( FCK.is_video( embed ) )
			{
				var oImg = FCKDocumentProcessor_CreateFakeImage( 'FCK__Video', embed.cloneNode(true) ) ;
				oImg.setAttribute( '_fckvideo', 'true', 0 ) ;
				embed.parentNode.insertBefore( oImg, embed ) ;
				embed.parentNode.removeChild( embed ) ;			
			}
		}
	} ;


/*
 **************************************************************************************
 * Context menu support
 **************************************************************************************
 */

// Removing some built-in context menu commands.
// Their default functionalities break proper working of the activated plugins.
for ( var i in FCK.ContextMenu.Listeners )
{
	var listener = '' ;

	if ( FCK.ContextMenu.Listeners[i].AddItems )
	{
		listener = FCK.ContextMenu.Listeners[i].AddItems.toString() ;
	}

	// Dealing with the built-in context menu handler for images.
	if ( listener.indexOf( 'IMG' ) != -1 && listener.indexOf( '_fckfakelement' ) != -1 )
	{
		FCK.ContextMenu.Listeners[i].AddItems = function ( menu, tag, tagName )
			{
				return ;
			} ;
	}

	// Dealing with the built-in context menu handler for flash objects.
	if ( listener.indexOf( 'IMG' ) != -1 && listener.indexOf( '_fckflash' ) != -1 )
	{
		FCK.ContextMenu.Listeners[i].AddItems = function ( menu, tag, tagName )
			{
				return ;
			} ;
	}
}

// Adding context menu commands.

// Image-related commands.
FCK.ContextMenu.RegisterListener( {
	AddItems : function ( menu, tag, tagName )
	{
		if ( tagName == 'IMG' &&
			!tag.getAttribute( '_fckfakelement' ) &&
			!tag.getAttribute( '_fckflash' ) &&
			!tag.getAttribute( '_fckmp3' ) &&
			!tag.getAttribute( '_fckvideo' ) )
		{
			// Grouping all image-related commands at the bottom.
			menu.AddSeparator();
			menu.AddItem( 'Image', FCKLang.ImageProperties, 37 ) ;
			menu.AddItem( 'ImageManager', FCKLang.ImageProperties, FCKConfig.PluginsPath + 'ImageManager/icon.gif' ) ;
			// The "imgmap" plugin should add its own icon here.
		}
	} }
) ;

// Flash command.
FCK.ContextMenu.RegisterListener( {
	AddItems : function ( menu, tag, tagName )
	{
		if ( tagName == 'IMG' && tag.getAttribute( '_fckflash' ) &&
			!tag.getAttribute( '_fckmp3' ) &&
			!tag.getAttribute( '_fckvideo' ) )
		{
			menu.AddSeparator() ;
			menu.AddItem( 'Flash', FCKLang.FlashProperties, 38 ) ;
		}
	} }
) ;

// MP3 command.
FCK.ContextMenu.RegisterListener( {
	AddItems : function ( menu, tag, tagName )
	{
		if ( tagName == 'IMG' && tag.getAttribute( '_fckmp3' ) )
		{
			menu.AddSeparator() ;
			menu.AddItem( 'MP3', FCKLang.DlgMP3Title, FCKConfig.PluginsPath + 'MP3/button.flash.gif' ) ;
		}
	} }
) ;

// Video-related commands.
FCK.ContextMenu.RegisterListener( {
	AddItems : function ( menu, tag, tagName )
	{
		if ( tagName == 'IMG' && tag.getAttribute( '_fckvideo' ) )
		{
			switch ( FCK.get_video_type( tag ) )
			{
				case 'embedded_video' :
					menu.AddSeparator() ;
					menu.AddItem( 'EmbedMovies', FCKLang.DlgMP3Title, FCKConfig.PluginsPath + 'fckEmbedMovies/embedmovies.gif' ) ;
					break ;
				default :
					break ;
			}
		}
	} }
) ;

/*
 **************************************************************************************
 * Double click support
 **************************************************************************************
 */

// Flash command.
FCK.RegisterDoubleClickHandler(
	function ( tag )
	{
		if ( tag.tagName == 'IMG' && tag.getAttribute( '_fckflash' ) &&
			!tag.getAttribute( '_fckmp3' ) &&
			!tag.getAttribute( '_fckvideo' ) )
		{
			FCKCommands.GetCommand( 'Flash' ).Execute() ;
		}
	}, 'IMG'
) ;	

// MP3 command.
FCK.RegisterDoubleClickHandler(
	function ( tag )
	{
		if ( tag.tagName == 'IMG' && tag.getAttribute( '_fckmp3' ) )
		{
			FCKCommands.GetCommand( 'MP3' ).Execute() ;
		}
	}, 'IMG'
) ;	

// Video-related commands.
FCK.RegisterDoubleClickHandler(
	function ( tag )
	{
		if ( tag.tagName == 'IMG' && tag.getAttribute( '_fckvideo' ) )
		{
			switch ( FCK.get_video_type( tag ) )
			{
				case 'embedded_video' :
					FCKCommands.GetCommand( 'EmbedMovies' ).Execute() ;
					break ;
				default :
					break ;
			}
		}
	}, 'IMG'
) ;	


/*
 **************************************************************************************
 * Common utilities
 **************************************************************************************
 */

// Checking for audio file reference which is to be used by a flash player.
FCK.is_audio = function ( tag )
	{
		if ( tag.nodeName.IEquals( 'embed' ) )
		{
			if ( !tag.src )
			{
				return false ;
			}

			if ( tag.type == 'application/x-shockwave-flash' || /\.swf($|#|\?|&)?/i.test( tag.src ) )
			{
				// Possible way of detection for other players.
				if ( /\.mp3/i.test( tag.src ) )
				{
					return true ;
				}

				// Specific to mediaplayer detection.
				var flashvars = FCKDomTools.GetAttributeValue( tag, 'flashvars' ) ;
				flashvars = flashvars ? flashvars.toLowerCase() : '' ;

				if ( /\.mp3/i.test( flashvars ) )
				{
					return true ;
				}
			}
		}

		return false ;
	} ;

// Checking for video file reference within an embedded object.
FCK.is_video = function ( tag )
	{
		if ( tag.nodeName.IEquals( 'embed' ) )
		{
			if ( !tag.src )
			{
				return false ;
			}

			// There are three plugins dealing with video content. Detection looks a bit messy.

			// Embedded video.
			if ( /\.(mpg|mpeg|mp4|avi|wmv|mov|asf)/i.test( tag.src ) )
			{
				return true ;
			}

			if ( tag.type == 'application/x-shockwave-flash' || /\.swf($|#|\?|&)?/i.test( tag.src ) )
			{
				// Youtube.
				if ( /\.youtube\.com/i.test( tag.src ) )
				{
					return true ;
				}

				// FLV player.
				if ( /\.flv/i.test( tag.src ) )
				{
					return true ;
				}

				var flashvars = FCKDomTools.GetAttributeValue( tag, 'flashvars' ) ;
				flashvars = flashvars ? flashvars.toLowerCase() : '' ;

				if ( /\.flv/i.test( flashvars ) )
				{
					return true ;
				}
			}
		}

		return false ;
	} ;

// Returns specific type/source of embedded video.
FCK.get_video_type = function ( img )
{
	var tag = FCK.GetRealElement( img ) ;

	if ( !tag )
	{
		return false ;
	}

	if ( !tag.src )
	{
		return false ;
	}

	// Embedded video.
	if ( /\.(mpg|mpeg|mp4|avi|wmv|mov|asf)/i.test( tag.src ) )
	{
		return 'embedded_video' ;
	}

	// Youtube.
	if ( /\.youtube\.com/i.test( tag.src ) )
	{
		return 'youtube' ;
	}

	// FLV player.
	if ( /\.flv/i.test( tag.src ) )
	{
		return 'flv' ;
	}

	var flashvars = FCKDomTools.GetAttributeValue( tag, 'flashvars' ) ;
	flashvars = flashvars ? flashvars.toLowerCase() : '' ;

	if ( /\.flv/i.test( flashvars ) )
	{
		return 'flv' ;
	}

	return false ;
}

// This is a utility for debugging purposes.
function var_dump( variable, level )
{
	var result = '' ;

	if ( !level )
	{
		level = 0 ;
	}

	var padding = '' ;

	for ( var i = 0; i < level + 1; i++ )
	{
		padding += '    ' ;
	}

	if (typeof( variable ) == 'object')
	{
		for ( var item in variable )
		{
			var value = variable[item] ;
			
			if (typeof( value ) == 'object')
			{
				result += padding + "'" + item + "' ...\n" ;
				result += var_dump( value, level + 1 ) ;
			}
			else
			{
				result += padding + "'" + item + "' => \"" + value + "\"\n" ;
			}
		}
	}
	else
	{
		result = '===>' + variable + '<===(' + typeof(variable) + ')' ;
	}
	return result ;
}
