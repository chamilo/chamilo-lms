/*
 *	Dokeos - elearning and course management software
 *
 *	Copyright (c) 2008-2009 Dokeos SPRL
 *	Copyright (c) 2008-2009 Julio Montoya Armas <gugli100@gmail.com>
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
 * This plugin uses also fragments of the original source code of
 * FCKeditor version 2.6.4.
 * 
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2009 Frederico Caldeira Knabben
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
 * Initialization.
 **************************************************************************************
 */

// Data about available toolbar sets should be unserialized when it comes from php-side.
if ( typeof FCKConfig.ToolbarSets == 'string' || FCKConfig.ToolbarSets instanceof ( String ) )
{
	FCKConfig.ToolbarSets = eval( '(' + FCKConfig.ToolbarSets + ')' ) ;
}

// Reading a setting which tells whether simple or advanced file manager is to be used.
FCKConfig.AdvancedFileManager = null ;
if ( FCKConfig.AdvancedFileManager )
{
	FCKConfig.AdvancedFileManager = FCKConfig.AdvancedFileManager.toString().toLowerCase() == 'true' ? true : false ;
}
else
{
	// If this setting has been omited, let us try to detect it indirectly.
	FCKConfig.AdvancedFileManager = false ;
	if ( ( FCKConfig.ImageBrowserURL && FCKConfig.ImageBrowserURL.toString().indexOf( 'ajaxfilemanager' ) != -1 )
		|| ( FCKConfig.FlashBrowserURL && FCKConfig.FlashBrowserURL.toString().indexOf( 'ajaxfilemanager' ) != -1 )
		|| ( FCKConfig.MP3BrowserURL && FCKConfig.MP3BrowserURL.toString().indexOf( 'ajaxfilemanager' ) != -1 )
		|| ( FCKConfig.VideoBrowserURL && FCKConfig.VideoBrowserURL.toString().indexOf( 'ajaxfilemanager' ) != -1 )
		|| ( FCKConfig.LinkBrowserURL && FCKConfig.LinkBrowserURL.toString().indexOf( 'ajaxfilemanager' ) != -1 )
		|| ( FCKConfig.MediaBrowserURL && FCKConfig.MediaBrowserURL.toString().indexOf( 'ajaxfilemanager' ) != -1 ) )
	{
		FCKConfig.AdvancedFileManager = true ;
	}
}

// A flag to see whether a course documents repository is to be used.
if ( FCKConfig.InDocument )
{
	FCKConfig.InDocument = FCKConfig.InDocument.toString().toLowerCase() == 'true' ? true : false ;
}
else
{
	FCKConfig.InDocument = false ;
}

// Absolute URL to document repository root.
if ( !FCKConfig.CreateDocumentWebDir )
{
	FCKConfig.CreateDocumentWebDir = '' ;
}

// Relative path from the document to the repository root.
if ( !FCKConfig.CreateDocumentDir )
{
	FCKConfig.CreateDocumentDir = '' ;
}

// This is the base of the reltive URLs that are used by the dialog system.
if ( !FCKConfig.BaseHref || FCKConfig.BaseHref.toString().length == 0 )
{
	if ( FCKConfig.BaseHref.toString().length == 0 )
	{
		FCKConfig.BaseHref = FCKConfig.CreateDocumentWebDir ;
	}
}

if ( !FCKConfig.BaseHref )
{
	if ( typeof ( FCKConfig.BaseHref ) != 'string' )
	{
		FCKConfig.BaseHref = FCKConfig.CreateDocumentWebDir ;
	}
}

FCKConfig.BaseHref = FCKConfig.BaseHref.toString();

if ( FCKConfig.BaseHref.length > 0 )
{
	if ( FCKConfig.BaseHref.substr( FCKConfig.BaseHref.length - 1 ) != '/' )
	{
		FCKConfig.BaseHref = FCKConfig.BaseHref + '/' ;
	}
}


/*
 **************************************************************************************
 * Internal configuration data. It is meant to be modified by developers only.
 **************************************************************************************
 */

// The icon for the image properties button/command.
if ( !FCKConfig.ImagesIcon )
{
	// This is the original icon from a chosen skin.
	//FCKConfig.ImagesIcon = 37 ;
	// We will use a better icon by default.
	FCKConfig.ImagesIcon = FCKConfig.PluginsPath + 'customizations/images/images_icon.gif' ;
}


/*
 **************************************************************************************
 * Plugins.
 **************************************************************************************
 */

// Checks whether a specified plugin has been loaded.
FCK.Plugins.IsLoaded = function( name )
{
	if ( name )
	{
		for ( var i = 0 ; i < FCKConfig.Plugins.Items.length ; i++ )
		{
			if ( FCKConfig.Plugins.Items[i][0] == name )
			{
				return true ;
			}
		}
	}
	return false ;
}


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

			// Removed by Ivan Tcholakov.
			// These statements are not relevant to the case, also they cause errors.
			//dialog._ParentDialog = topDialog ;
			//topDialog = dialog ;

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
				try
				{
					dialog._ParentDialog.contentWindow.SetEnabled( true ) ;
				}
				catch ( ex ) { }
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
 * Blocking copy/pase feature.
 **************************************************************************************
 */

FCK.BlockCopyPasteKeystrokes = function()
{
	var Keystrokes = [] ;

	for ( var i = 0 ; i < FCKConfig.Keystrokes.length ; i++ )
	{
		switch ( FCKConfig.Keystrokes[i][0] )
		{
			case CTRL + 67 : // Ctrl + C, 'Copy'
			case CTRL + 86 : // Ctrl + V, 'Paste'
			case CTRL + 88 : // Ctrl + X, 'Cut'
				break ;
			default :
				Keystrokes.push( FCKConfig.Keystrokes[i] ) ;
				break ;
		}
	}

	FCKConfig.Keystrokes = Keystrokes ;
}

if ( FCKConfig.BlockCopyPaste )
{
	FCK.BlockCopyPasteKeystrokes() ;
}

FCK.GetNamedCommandState = function( commandName )
{
	// This is a modification of the original code.
	if ( FCKConfig.BlockCopyPaste )
	{
		switch ( commandName )
		{
			case 'Cut' :
			case 'Copy' :
			case 'Paste' :
			case 'PasteText' :
			case 'PasteWord' :
				return FCK_TRISTATE_DISABLED ;
				break ;
			default :
				break ;
		}
	}
	//

	try
	{

		// Bug #50 : Safari never returns positive state for the Paste command, override that.
		if ( FCKBrowserInfo.IsSafari && FCK.EditorWindow && commandName.IEquals( 'Paste' ) )
			return FCK_TRISTATE_OFF ;

		if ( !FCK.EditorDocument.queryCommandEnabled( commandName ) )
			return FCK_TRISTATE_DISABLED ;
		else
		{
			return FCK.EditorDocument.queryCommandState( commandName ) ? FCK_TRISTATE_ON : FCK_TRISTATE_OFF ;
		}
	}
	catch ( e )
	{
		return FCK_TRISTATE_OFF ;
	}
}


/*
 **************************************************************************************
 * Toolbar items (buttons).
 **************************************************************************************
 */

// This function has been redefined here in order hard-codeded parameters
// of toolbar items to be controlled by the developers.
FCKToolbarItems.GetItem = function( itemName )
{
	var oItem = FCKToolbarItems.LoadedItems[ itemName ] ;

	if ( oItem )
		return oItem ;

	switch ( itemName )
	{
		case 'Source'			: oItem = new FCKToolbarButton( 'Source'	, FCKLang.Source, null,null, true, true, 1 ) ; break ;
		case 'DocProps'			: oItem = new FCKToolbarButton( 'DocProps'	, FCKLang.DocProps, null, null, null, null, 2 ) ; break ;
		case 'Save'				: oItem = new FCKToolbarButton( 'Save'		, FCKLang.Save, null, null, true, null, 3 ) ; break ;
		case 'NewPage'			: oItem = new FCKToolbarButton( 'NewPage'	, FCKLang.NewPage, null, null, true, null, 4  ) ; break ;
		case 'Preview'			: oItem = new FCKToolbarButton( 'Preview'	, FCKLang.Preview, null, null, true, null, 5  ) ; break ;
		case 'Templates'		: oItem = new FCKToolbarButton( 'Templates'	, FCKLang.Templates, null, null, null, null, 6 ) ; break ;
		case 'About'			: oItem = new FCKToolbarButton( 'About'		, FCKLang.About, null, null, true, null, 47  ) ; break ;

		case 'Cut'				: oItem = new FCKToolbarButton( 'Cut'		, FCKLang.Cut, null, null, false, true, 7 ) ; break ;
		case 'Copy'				: oItem = new FCKToolbarButton( 'Copy'		, FCKLang.Copy, null, null, false, true, 8 ) ; break ;
		case 'Paste'			: oItem = new FCKToolbarButton( 'Paste'		, FCKLang.Paste, null, null, false, true, 9 ) ; break ;
		case 'PasteText'		: oItem = new FCKToolbarButton( 'PasteText'	, FCKLang.PasteText, null, null, false, true, 10 ) ; break ;
		case 'PasteWord'		: oItem = new FCKToolbarButton( 'PasteWord'	, FCKLang.PasteWord, null, null, false, true, 11 ) ; break ;
		case 'Print'			: oItem = new FCKToolbarButton( 'Print'		, FCKLang.Print, null, null, false, true, 12 ) ; break ;
		case 'SpellCheck'		: oItem = new FCKToolbarButton( 'SpellCheck', FCKLang.SpellCheck, null, null, null, null, 13 ) ; break ;
		case 'Undo'				: oItem = new FCKToolbarButton( 'Undo'		, FCKLang.Undo, null, null, false, true, 14 ) ; break ;
		case 'Redo'				: oItem = new FCKToolbarButton( 'Redo'		, FCKLang.Redo, null, null, false, true, 15 ) ; break ;
		case 'SelectAll'		: oItem = new FCKToolbarButton( 'SelectAll'	, FCKLang.SelectAll, null, null, true, null, 18 ) ; break ;
		case 'RemoveFormat'		: oItem = new FCKToolbarButton( 'RemoveFormat', FCKLang.RemoveFormat, null, null, false, true, 19 ) ; break ;
		case 'FitWindow'		: oItem = new FCKToolbarButton( 'FitWindow'	, FCKLang.FitWindow, null, null, true, true, 66 ) ; break ;

		case 'Bold'				: oItem = new FCKToolbarButton( 'Bold'		, FCKLang.Bold, null, null, false, true, 20 ) ; break ;
		case 'Italic'			: oItem = new FCKToolbarButton( 'Italic'	, FCKLang.Italic, null, null, false, true, 21 ) ; break ;
		case 'Underline'		: oItem = new FCKToolbarButton( 'Underline'	, FCKLang.Underline, null, null, false, true, 22 ) ; break ;
		case 'StrikeThrough'	: oItem = new FCKToolbarButton( 'StrikeThrough'	, FCKLang.StrikeThrough, null, null, false, true, 23 ) ; break ;
		case 'Subscript'		: oItem = new FCKToolbarButton( 'Subscript'		, FCKLang.Subscript, null, null, false, true, 24 ) ; break ;
		case 'Superscript'		: oItem = new FCKToolbarButton( 'Superscript'	, FCKLang.Superscript, null, null, false, true, 25 ) ; break ;

		case 'OrderedList'		: oItem = new FCKToolbarButton( 'InsertOrderedList'		, FCKLang.NumberedListLbl, FCKLang.NumberedList, null, false, true, 26 ) ; break ;
		case 'UnorderedList'	: oItem = new FCKToolbarButton( 'InsertUnorderedList'	, FCKLang.BulletedListLbl, FCKLang.BulletedList, null, false, true, 27 ) ; break ;
		case 'Outdent'			: oItem = new FCKToolbarButton( 'Outdent'	, FCKLang.DecreaseIndent, null, null, false, true, 28 ) ; break ;
		case 'Indent'			: oItem = new FCKToolbarButton( 'Indent'	, FCKLang.IncreaseIndent, null, null, false, true, 29 ) ; break ;
		case 'Blockquote'			: oItem = new FCKToolbarButton( 'Blockquote'	, FCKLang.Blockquote, null, null, false, true, 73 ) ; break ;
		case 'CreateDiv'			: oItem = new FCKToolbarButton( 'CreateDiv'	, FCKLang.CreateDiv, null, null, false, true, 74 ) ; break ;

		case 'Link'				: oItem = new FCKToolbarButton( 'Link'		, FCKLang.InsertLinkLbl, FCKLang.InsertLink, null, false, true, 34 ) ; break ;
		case 'Unlink'			: oItem = new FCKToolbarButton( 'Unlink'	, FCKLang.RemoveLink, null, null, false, true, 35 ) ; break ;
		case 'Anchor'			: oItem = new FCKToolbarButton( 'Anchor'	, FCKLang.Anchor, null, null, null, null, 36 ) ; break ;

		//case 'Image'			: oItem = new FCKToolbarButton( 'Image'			, FCKLang.InsertImageLbl, FCKLang.InsertImage, null, false, true, 37 ) ; break ;
		case 'Image'			: oItem = new FCKToolbarButton( 'Image'			, FCKLang.InsertImageLbl, FCKLang.InsertImage, null, false, true, FCKConfig.ImagesIcon ) ; break ;

		case 'Flash'			: oItem = new FCKToolbarButton( 'Flash'			, FCKLang.InsertFlashLbl, FCKLang.InsertFlash, null, false, true, 38 ) ; break ;
		case 'Table'			: oItem = new FCKToolbarButton( 'Table'			, FCKLang.InsertTableLbl, FCKLang.InsertTable, null, false, true, 39 ) ; break ;
		case 'SpecialChar'		: oItem = new FCKToolbarButton( 'SpecialChar'	, FCKLang.InsertSpecialCharLbl, FCKLang.InsertSpecialChar, null, false, true, 42 ) ; break ;
		case 'Smiley'			: oItem = new FCKToolbarButton( 'Smiley'		, FCKLang.InsertSmileyLbl, FCKLang.InsertSmiley, null, false, true, 41 ) ; break ;
		case 'PageBreak'		: oItem = new FCKToolbarButton( 'PageBreak'		, FCKLang.PageBreakLbl, FCKLang.PageBreak, null, false, true, 43 ) ; break ;

		case 'Rule'				: oItem = new FCKToolbarButton( 'Rule'			, FCKLang.InsertLineLbl, FCKLang.InsertLine, null, false, true, 40 ) ; break ;

		case 'JustifyLeft'		: oItem = new FCKToolbarButton( 'JustifyLeft'	, FCKLang.LeftJustify, null, null, false, true, 30 ) ; break ;
		case 'JustifyCenter'	: oItem = new FCKToolbarButton( 'JustifyCenter'	, FCKLang.CenterJustify, null, null, false, true, 31 ) ; break ;
		case 'JustifyRight'		: oItem = new FCKToolbarButton( 'JustifyRight'	, FCKLang.RightJustify, null, null, false, true, 32 ) ; break ;
		case 'JustifyFull'		: oItem = new FCKToolbarButton( 'JustifyFull'	, FCKLang.BlockJustify, null, null, false, true, 33 ) ; break ;

		case 'Style'			: oItem = new FCKToolbarStyleCombo() ; break ;
		case 'FontName'			: oItem = new FCKToolbarFontsCombo() ; break ;
		case 'FontSize'			: oItem = new FCKToolbarFontSizeCombo() ; break ;
		case 'FontFormat'		: oItem = new FCKToolbarFontFormatCombo() ; break ;

		case 'TextColor'		: oItem = new FCKToolbarPanelButton( 'TextColor', FCKLang.TextColor, null, null, 45 ) ; break ;
		case 'BGColor'			: oItem = new FCKToolbarPanelButton( 'BGColor'	, FCKLang.BGColor, null, null, 46 ) ; break ;

		case 'Find'				: oItem = new FCKToolbarButton( 'Find'		, FCKLang.Find, null, null, null, null, 16 ) ; break ;
		case 'Replace'			: oItem = new FCKToolbarButton( 'Replace'	, FCKLang.Replace, null, null, null, null, 17 ) ; break ;

		case 'Form'				: oItem = new FCKToolbarButton( 'Form'			, FCKLang.Form, null, null, null, null, 48 ) ; break ;
		case 'Checkbox'			: oItem = new FCKToolbarButton( 'Checkbox'		, FCKLang.Checkbox, null, null, null, null, 49 ) ; break ;
		case 'Radio'			: oItem = new FCKToolbarButton( 'Radio'			, FCKLang.RadioButton, null, null, null, null, 50 ) ; break ;
		case 'TextField'		: oItem = new FCKToolbarButton( 'TextField'		, FCKLang.TextField, null, null, null, null, 51 ) ; break ;
		case 'Textarea'			: oItem = new FCKToolbarButton( 'Textarea'		, FCKLang.Textarea, null, null, null, null, 52 ) ; break ;
		case 'HiddenField'		: oItem = new FCKToolbarButton( 'HiddenField'	, FCKLang.HiddenField, null, null, null, null, 56 ) ; break ;
		case 'Button'			: oItem = new FCKToolbarButton( 'Button'		, FCKLang.Button, null, null, null, null, 54 ) ; break ;
		case 'Select'			: oItem = new FCKToolbarButton( 'Select'		, FCKLang.SelectionField, null, null, null, null, 53 ) ; break ;
		case 'ImageButton'		: oItem = new FCKToolbarButton( 'ImageButton'	, FCKLang.ImageButton, null, null, null, null, 55 ) ; break ;
		case 'ShowBlocks'		: oItem = new FCKToolbarButton( 'ShowBlocks'	, FCKLang.ShowBlocks, null, null, null, true, 72 ) ; break ;

		default:
			// Customization: We want to suppress this alarm in order to be
			// able to turn off plugins without need to modify defined toolbars.
			//alert( FCKLang.UnknownToolbarItem.replace( /%1/g, itemName ) ) ;
			return null ;
	}

	FCKToolbarItems.LoadedItems[ itemName ] = oItem ;

	return oItem ;
}


// A modification of the "Save" command in order "nice buttons" in Dokeos
// forms to be supported (to be "clicked" when this command executes).
FCKSaveCommand.prototype.Execute = function()
{
	// Get the linked field form.
	var oForm = FCK.GetParentForm() ;

	if ( typeof( oForm.onsubmit ) == 'function' )
	{
		var bRet = oForm.onsubmit() ;
		if ( bRet != null && bRet === false )
			return ;
	}

	// Next, we will try to scan all styled buttons within the form and to find
	// the button that means "Save", "Ok" "Submit", or something similar.
	// The way of searching may be not accurate enough, it will be made more
	// precise if problems are reported.
	for ( var i = 0 ; i < oForm.elements.length ; i++)
	{
		if ( oForm.elements[i].type == 'submit' )
		{
			// Let us check whether the buton is styled, i.e. whether it is "nice".
			if ( oForm.elements[i].getAttribute( 'class' ) )
			{
				try
				{
					// "Clicking" the found button.
					oForm.elements[i].click() ;
				} catch ( ex ) { }
				return ;
			}
		}
	}

	// An attempt for submitting the form that has no proper styled button detected.
	// If there's a button named "submit" then the form.submit() function is masked and
	// can't be called in Mozilla, so we call the click() method of that button.
	if ( typeof( oForm.submit ) == 'function' )
		oForm.submit() ;
	else
		oForm.submit.click() ;
}


/*
 **************************************************************************************
 * Dialog system
 **************************************************************************************
 */

// Making a new alternative command for processing Images.
var FCKImageCommand = function( name )
{
	this.Name = name ;
	this.ImageProperties = new FCKDialogCommand( 'Image', FCKLang.DlgImgTitle, 'dialog/fck_image.html', 600, 450 ) ;
	this.ImageManager = null ;
	if ( FCK.Plugins.IsLoaded( 'ImageManager' ) )
	{
		this.ImageManager = new FCKImageManager('ImageManager') ;
	}
}

FCKImageCommand.prototype.Execute = function()
{
	// If the advanced file manager it to be used, the image properties dialog shoud be activated.
	if ( FCKConfig.AdvancedFileManager )
	{
		this.ImageProperties.Execute() ;
	}
	else
	{
		// If the ImageManager plugin has not been loaded, the image properties dialog shoud be activated too.
		if ( !this.ImageManager )
		{
			this.ImageProperties.Execute() ;
		}
		else
		{
			var image = FCK.Selection.GetSelectedElement() ;
			if ( image )
			{
				// If an image has been selected in the editor, the image properties dialog shoud be activated.
				if ( FCK.IsRealImage( image ) )
				{
					this.ImageProperties.Execute() ;
				}
				// If the selected object is fake image, the image manager dialog should be activated.
				else
				{
					this.ImageManager.Execute() ;
				}
			}
			// In other cases (no object selected or the selected object is not an image),
			// the image manager dialog should be activated.
			else
			{
				this.ImageManager.Execute() ;
			}
		}
	}
} ;

FCKImageCommand.prototype.GetState = function()
{
	return FCK_TRISTATE_OFF ;
}

// This function has been redefined here in order hard-coded dialog sizes
// to be controlled by the developers.
// The Image command's behaviour has been changed.
FCKCommands.GetCommand = function( commandName )
{
	var oCommand = FCKCommands.LoadedCommands[ commandName ] ;

	if ( oCommand )
		return oCommand ;

	switch ( commandName )
	{
		case 'Bold'			:
		case 'Italic'		:
		case 'Underline'	:
		case 'StrikeThrough':
		case 'Subscript'	:
		case 'Superscript'	: oCommand = new FCKCoreStyleCommand( commandName ) ; break ;

		case 'RemoveFormat'	: oCommand = new FCKRemoveFormatCommand() ; break ;

		case 'DocProps'		: oCommand = new FCKDialogCommand( 'DocProps'	, FCKLang.DocProps				, 'dialog/fck_docprops.html'	, 540, 380, FCKCommands.GetFullPageState ) ; break ;
		case 'Templates'	: oCommand = new FCKDialogCommand( 'Templates'	, FCKLang.DlgTemplatesTitle		, 'dialog/fck_template.html'	, 380, 450 ) ; break ;
		case 'Link'			: oCommand = new FCKDialogCommand( 'Link'		, FCKLang.DlgLnkWindowTitle		, 'dialog/fck_link.html'		, 600, 300 ) ; break ;
		case 'Unlink'		: oCommand = new FCKUnlinkCommand() ; break ;
		case 'VisitLink'	: oCommand = new FCKVisitLinkCommand() ; break ;
		case 'Anchor'		: oCommand = new FCKDialogCommand( 'Anchor'		, FCKLang.DlgAnchorTitle		, 'dialog/fck_anchor.html'		, 420, 180 ) ; break ;
		case 'AnchorDelete'	: oCommand = new FCKAnchorDeleteCommand() ; break ;
		case 'BulletedList'	: oCommand = new FCKDialogCommand( 'BulletedList', FCKLang.BulletedListProp		, 'dialog/fck_listprop.html?UL'	, 420, 180 ) ; break ;
		case 'NumberedList'	: oCommand = new FCKDialogCommand( 'NumberedList', FCKLang.NumberedListProp		, 'dialog/fck_listprop.html?OL'	, 420, 180 ) ; break ;
		case 'About'		: oCommand = new FCKDialogCommand( 'About'		, FCKLang.About					, 'dialog/fck_about.html'		, 500, 380, function(){ return FCK_TRISTATE_OFF ; } ) ; break ;
		case 'Find'			: oCommand = new FCKDialogCommand( 'Find'		, FCKLang.DlgFindAndReplaceTitle, 'dialog/fck_replace.html'		, 450, 250, null, null, 'Find' ) ; break ;
		case 'Replace'		: oCommand = new FCKDialogCommand( 'Replace'	, FCKLang.DlgFindAndReplaceTitle, 'dialog/fck_replace.html'		, 450, 250, null, null, 'Replace' ) ; break ;

		//case 'Image'		: oCommand = new FCKDialogCommand( 'Image'		, FCKLang.DlgImgTitle			, 'dialog/fck_image.html'		, 600, 450 ) ; break ;
		case 'Image'		: oCommand = new FCKImageCommand( 'Image' ) ; break ;

		case 'Flash'		: oCommand = new FCKDialogCommand( 'Flash'		, FCKLang.DlgFlashTitle			, 'dialog/fck_flash.html'		, 600, 450 ) ; break ;
		case 'SpecialChar'	: oCommand = new FCKDialogCommand( 'SpecialChar', FCKLang.DlgSpecialCharTitle	, 'dialog/fck_specialchar.html'	, 540, 450 ) ; break ;
		case 'Smiley'		: oCommand = new FCKDialogCommand( 'Smiley'		, FCKLang.DlgSmileyTitle		, 'dialog/fck_smiley.html'		, FCKConfig.SmileyWindowWidth, FCKConfig.SmileyWindowHeight ) ; break ;
		case 'Table'		: oCommand = new FCKDialogCommand( 'Table'		, FCKLang.DlgTableTitle			, 'dialog/fck_table.html'		, 600, 300 ) ; break ;
		case 'TableProp'	: oCommand = new FCKDialogCommand( 'Table'		, FCKLang.DlgTableTitle			, 'dialog/fck_table.html?Parent', 600, 300 ) ; break ;
		case 'TableCellProp': oCommand = new FCKDialogCommand( 'TableCell'	, FCKLang.DlgCellTitle			, 'dialog/fck_tablecell.html'	, 600, 300 ) ; break ;

		case 'Style'		: oCommand = new FCKStyleCommand() ; break ;

		case 'FontName'		: oCommand = new FCKFontNameCommand() ; break ;
		case 'FontSize'		: oCommand = new FCKFontSizeCommand() ; break ;
		case 'FontFormat'	: oCommand = new FCKFormatBlockCommand() ; break ;

		case 'Source'		: oCommand = new FCKSourceCommand() ; break ;
		case 'Preview'		: oCommand = new FCKPreviewCommand() ; break ;
		case 'Save'			: oCommand = new FCKSaveCommand() ; break ;
		case 'NewPage'		: oCommand = new FCKNewPageCommand() ; break ;
		case 'PageBreak'	: oCommand = new FCKPageBreakCommand() ; break ;
		case 'Rule'			: oCommand = new FCKRuleCommand() ; break ;
		case 'Nbsp'			: oCommand = new FCKNbsp() ; break ;

		case 'TextColor'	: oCommand = new FCKTextColorCommand('ForeColor') ; break ;
		case 'BGColor'		: oCommand = new FCKTextColorCommand('BackColor') ; break ;

		case 'Paste'		: oCommand = new FCKPasteCommand() ; break ;
		case 'PasteText'	: oCommand = new FCKPastePlainTextCommand() ; break ;
		case 'PasteWord'	: oCommand = new FCKPasteWordCommand() ; break ;

		case 'JustifyLeft'	: oCommand = new FCKJustifyCommand( 'left' ) ; break ;
		case 'JustifyCenter'	: oCommand = new FCKJustifyCommand( 'center' ) ; break ;
		case 'JustifyRight'	: oCommand = new FCKJustifyCommand( 'right' ) ; break ;
		case 'JustifyFull'	: oCommand = new FCKJustifyCommand( 'justify' ) ; break ;
		case 'Indent'	: oCommand = new FCKIndentCommand( 'indent', FCKConfig.IndentLength ) ; break ;
		case 'Outdent'	: oCommand = new FCKIndentCommand( 'outdent', FCKConfig.IndentLength * -1 ) ; break ;
		case 'Blockquote'	: oCommand = new FCKBlockQuoteCommand() ; break ;
		case 'CreateDiv'	: oCommand = new FCKDialogCommand( 'CreateDiv', FCKLang.CreateDiv, 'dialog/fck_div.html', 400, 300, null, null, true ) ; break ;
		case 'EditDiv'		: oCommand = new FCKDialogCommand( 'EditDiv', FCKLang.EditDiv, 'dialog/fck_div.html', 400, 300, null, null, false ) ; break ;
		case 'DeleteDiv'	: oCommand = new FCKDeleteDivCommand() ; break ;

		case 'TableInsertRowAfter'		: oCommand = new FCKTableCommand('TableInsertRowAfter') ; break ;
		case 'TableInsertRowBefore'		: oCommand = new FCKTableCommand('TableInsertRowBefore') ; break ;
		case 'TableDeleteRows'			: oCommand = new FCKTableCommand('TableDeleteRows') ; break ;
		case 'TableInsertColumnAfter'	: oCommand = new FCKTableCommand('TableInsertColumnAfter') ; break ;
		case 'TableInsertColumnBefore'	: oCommand = new FCKTableCommand('TableInsertColumnBefore') ; break ;
		case 'TableDeleteColumns'		: oCommand = new FCKTableCommand('TableDeleteColumns') ; break ;
		case 'TableInsertCellAfter'		: oCommand = new FCKTableCommand('TableInsertCellAfter') ; break ;
		case 'TableInsertCellBefore'	: oCommand = new FCKTableCommand('TableInsertCellBefore') ; break ;
		case 'TableDeleteCells'			: oCommand = new FCKTableCommand('TableDeleteCells') ; break ;
		case 'TableMergeCells'			: oCommand = new FCKTableCommand('TableMergeCells') ; break ;
		case 'TableMergeRight'			: oCommand = new FCKTableCommand('TableMergeRight') ; break ;
		case 'TableMergeDown'			: oCommand = new FCKTableCommand('TableMergeDown') ; break ;
		case 'TableHorizontalSplitCell'	: oCommand = new FCKTableCommand('TableHorizontalSplitCell') ; break ;
		case 'TableVerticalSplitCell'	: oCommand = new FCKTableCommand('TableVerticalSplitCell') ; break ;
		case 'TableDelete'				: oCommand = new FCKTableCommand('TableDelete') ; break ;

		case 'Form'			: oCommand = new FCKDialogCommand( 'Form'		, FCKLang.Form			, 'dialog/fck_form.html'		, 380, 210 ) ; break ;
		case 'Checkbox'		: oCommand = new FCKDialogCommand( 'Checkbox'	, FCKLang.Checkbox		, 'dialog/fck_checkbox.html'	, 380, 200 ) ; break ;
		case 'Radio'		: oCommand = new FCKDialogCommand( 'Radio'		, FCKLang.RadioButton	, 'dialog/fck_radiobutton.html'	, 380, 200 ) ; break ;
		case 'TextField'	: oCommand = new FCKDialogCommand( 'TextField'	, FCKLang.TextField		, 'dialog/fck_textfield.html'	, 380, 210 ) ; break ;
		case 'Textarea'		: oCommand = new FCKDialogCommand( 'Textarea'	, FCKLang.Textarea		, 'dialog/fck_textarea.html'	, 380, 210 ) ; break ;
		case 'HiddenField'	: oCommand = new FCKDialogCommand( 'HiddenField', FCKLang.HiddenField	, 'dialog/fck_hiddenfield.html'	, 380, 190 ) ; break ;
		case 'Button'		: oCommand = new FCKDialogCommand( 'Button'		, FCKLang.Button		, 'dialog/fck_button.html'		, 380, 210 ) ; break ;
		case 'Select'		: oCommand = new FCKDialogCommand( 'Select'		, FCKLang.SelectionField, 'dialog/fck_select.html'		, 450, 380 ) ; break ;
		case 'ImageButton'	: oCommand = new FCKDialogCommand( 'ImageButton', FCKLang.ImageButton	, 'dialog/fck_image.html?ImageButton', 600, 450 ) ; break ;

		case 'SpellCheck'	: oCommand = new FCKSpellCheckCommand() ; break ;
		case 'FitWindow'	: oCommand = new FCKFitWindow() ; break ;

		case 'Undo'	: oCommand = new FCKUndoCommand() ; break ;
		case 'Redo'	: oCommand = new FCKRedoCommand() ; break ;
		case 'Copy'	: oCommand = new FCKCutCopyCommand( false ) ; break ;
		case 'Cut'	: oCommand = new FCKCutCopyCommand( true ) ; break ;

		case 'SelectAll'			: oCommand = new FCKSelectAllCommand() ; break ;
		case 'InsertOrderedList'	: oCommand = new FCKListCommand( 'insertorderedlist', 'ol' ) ; break ;
		case 'InsertUnorderedList'	: oCommand = new FCKListCommand( 'insertunorderedlist', 'ul' ) ; break ;
		case 'ShowBlocks' : oCommand = new FCKShowBlockCommand( 'ShowBlocks', FCKConfig.StartupShowBlocks ? FCK_TRISTATE_ON : FCK_TRISTATE_OFF ) ; break ;

		// Generic Undefined command (usually used when a command is under development).
		case 'Undefined'	: oCommand = new FCKUndefinedCommand() ; break ;

		// By default we assume that it is a named command.
		default:
			if ( FCKRegexLib.NamedCommands.test( commandName ) )
				oCommand = new FCKNamedCommand( commandName ) ;
			else
			{
				alert( FCKLang.UnknownCommand.replace( /%1/g, commandName ) ) ;
				return null ;
			}
	}

	FCKCommands.LoadedCommands[ commandName ] = oCommand ;

	return oCommand ;
}

// Upgrading the language subsystem, so it could be able to translate new nice buttons.
FCKLanguageManager.TranslatePage = function( targetDocument )
{
	this.TranslateElements( targetDocument, 'INPUT', 'value' ) ;
	this.TranslateElements( targetDocument, 'SPAN', 'innerHTML' ) ;
	this.TranslateElements( targetDocument, 'LABEL', 'innerHTML' ) ;
	this.TranslateElements( targetDocument, 'OPTION', 'innerHTML', true ) ;
	this.TranslateElements( targetDocument, 'LEGEND', 'innerHTML' ) ;
	// The following tag has been added for searching:
	this.TranslateElements( targetDocument, 'BUTTON', 'innerHTML' ) ;
}

// Calculating size of rectangular object so it to fit within other rectangular object
// with preserving aspect ratio.
FCK.ResizeToFit = function( width, height, max_width, max_height )
{
	var result = [0, 0] ;

	result[0] = width;
	result[1] = height ;

	if ( width <= max_width && height <= max_height )
		return result ;

	if ( width > max_width )
	{
		height = height * max_width / width ;
		width = max_width ;
	}

	if ( height > max_height )
	{
		width = width * max_height / height ;
		height = max_height ;
	}

	result[0] = parseInt ( width, 10 );
	result[1] = parseInt ( height, 10 ) ;

	return result ;
}


/*
 **************************************************************************************
 * Fake images support
 **************************************************************************************
 */

// This is a modification of the original function.
FCKDocumentProcessor_CreateFakeImage = function( fakeClass, realElement )
{
	var oImg = FCKTools.GetElementDocument( realElement ).createElement( 'IMG' ) ;
	oImg.className = fakeClass ;
	oImg.src = FCKConfig.BasePath + 'images/spacer.gif' ;
	oImg.setAttribute( '_fckfakelement', 'true', 0 ) ;
	oImg.setAttribute( '_fckrealelement', FCKTempBin.AddElement( realElement ), 0 ) ;
	if ( fakeClass == 'FCK__Video' )
	{
		// Specific to flv player, SWFObject attaching technique.
		if ( realElement.nodeName.IEquals( 'div' ) )
		{
			for ( var i = 0; i < realElement.childNodes.length; i++ )
			{
				if ( realElement.childNodes[i].nodeName.IEquals( 'div' ) )
				{
					oImg.style.width = realElement.childNodes[i].style.width ;
					oImg.style.height = realElement.childNodes[i].style.height ;
					break ;
				}
			}
		}
		// For embedded video.
		else
		{
			var width = realElement.width ;
			var height = realElement.height ;
			if ( width )
			{
				oImg.style.width = width.toString().indexOf('%') != -1 ? width : ( width + 'px' ) ;
			}
			if ( height )
			{
				oImg.style.height = height.toString().indexOf('%') != -1 ? height : ( height + 'px' ) ;
			}
		}
	}
	return oImg ;
}

// A custom handler for audio files when a new tag has been added.
FCKEmbedAndObjectProcessor.AddCustomHandler( function ( el, fakeImg )
	{
		if ( !FCK.IsAudio( el ) )
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
			if ( FCK.IsAudio( embed ) )
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
		if ( !FCK.IsVideo( el ) )
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
			if ( FCK.IsVideo( embed ) )
			{
				var oImg = FCKDocumentProcessor_CreateFakeImage( 'FCK__Video', embed.cloneNode(true) ) ;
				oImg.setAttribute( '_fckvideo', 'true', 0 ) ;
				embed.parentNode.insertBefore( oImg, embed ) ;
				embed.parentNode.removeChild( embed ) ;			
			}
		}

		// For flv player, SWFObject attaching tecnique.
		var divs = document.getElementsByTagName( 'div' ) ; 
		var div;
		var i = divs.length - 1 ; 
		while ( i >= 0 && ( div = divs[i--] ) )
		{
			if ( FCK.IsVideo( div ) )
			{
				var oImg = FCKDocumentProcessor_CreateFakeImage( 'FCK__Video', div.cloneNode(true) ) ;
				oImg.setAttribute( '_fckvideo', 'true', 0 ) ;
				div.parentNode.insertBefore( oImg, div ) ;
				div.parentNode.removeChild( div ) ;			
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
		if ( FCK.IsRealImage( tag ) )
		{
			// Grouping all image-related commands at the bottom.
			menu.AddSeparator();
			//menu.AddItem( 'Image', FCKLang.ImageProperties, 37 ) ;
			menu.AddItem( 'Image', FCKLang.ImageProperties, FCKConfig.ImagesIcon ) ;
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
		var AudioTitle = 'Import MP3' ;
		var AudioIcon = FCKConfig.PluginsPath + 'MP3/mp3.gif' ;

		if ( FCKLang.DlgAudioTitle )
		{
			AudioTitle = FCKLang.DlgAudioTitle ;
			AudioIcon = FCKConfig.PluginsPath + 'audio/audio.gif' ;
		}
		else if ( FCKLang.DlgMP3Title )
		{
			AudioTitle = FCKLang.DlgMP3Title ;
		}

		if ( tagName == 'IMG' && tag.getAttribute( '_fckmp3' ) )
		{
			if ( FCK.Plugins.IsLoaded( 'audio' ) || FCK.Plugins.IsLoaded( 'MP3' ) )
			{
				menu.AddSeparator() ;
				menu.AddItem( 'MP3', AudioTitle, AudioIcon ) ;
			}
		}
	} }
) ;

// Video-related commands.
FCK.ContextMenu.RegisterListener( {
	AddItems : function ( menu, tag, tagName )
	{
		if ( tagName == 'IMG' && tag.getAttribute( '_fckvideo' ) )
		{
			switch ( FCK.GetVideoType( tag ) )
			{
				case 'embedded_video' :
					if ( FCK.Plugins.IsLoaded( 'fckEmbedMovies' ) )
					{
						menu.AddSeparator() ;
						menu.AddItem( 'EmbedMovies', FCKLang.DlgEmbedMoviesTitle, FCKConfig.PluginsPath + 'fckEmbedMovies/embedmovies.gif' ) ;
					}
					break ;
				case 'youtube' :
					if ( FCK.Plugins.IsLoaded( 'youtube' ) )
					{
						menu.AddSeparator() ;
						menu.AddItem( 'YouTube', FCKLang.YouTubeTip, FCKConfig.PluginsPath + 'youtube/youtube.gif' ) ;
					}
					break ;
				case 'flv' :
					if ( FCK.Plugins.IsLoaded( 'flvPlayer' ) )
					{
						menu.AddSeparator() ;
						menu.AddItem( 'flvPlayer', FCKLang.DlgFLVPlayerTitle, FCKConfig.PluginsPath + 'flvPlayer/flvPlayer.gif' ) ;
					}
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

// Image properties command.
FCK.RegisterDoubleClickHandler(
		function ( tag )
		{
			if ( FCK.IsRealImage( tag ) )
			{
				var command = new FCKDialogCommand( 'Image', FCKLang.DlgImgTitle, 'dialog/fck_image.html', 600, 450 ) ;
				command.Execute() ;
			}
		}, 'IMG'
	) ;

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
			if ( FCK.Plugins.IsLoaded( 'audio' ) || FCK.Plugins.IsLoaded( 'MP3' ) )
			{
				FCKCommands.GetCommand( 'MP3' ).Execute() ;
			}
		}
	}, 'IMG'
) ;	

// Video-related commands.
FCK.RegisterDoubleClickHandler(
	function ( tag )
	{
		if ( tag.tagName == 'IMG' && tag.getAttribute( '_fckvideo' ) )
		{
			switch ( FCK.GetVideoType( tag ) )
			{
				case 'embedded_video' :
					if ( FCK.Plugins.IsLoaded( 'fckEmbedMovies' ) )
					{
						FCKCommands.GetCommand( 'EmbedMovies' ).Execute() ;
					}
					break ;
				case 'youtube' :
					if ( FCK.Plugins.IsLoaded( 'youtube' ) )
					{
						FCKCommands.GetCommand( 'YouTube' ).Execute() ;
					}
					break ;
				case 'flv':
					if ( FCK.Plugins.IsLoaded( 'flvPlayer' ) )
					{
						FCKCommands.GetCommand( 'flvPlayer' ).Execute() ;
					}
					break ;
				default :
					break ;
			}
		}
	}, 'IMG'
) ;	


/*
 **************************************************************************************
 * Routines for testing the type of a selected visual object.
 **************************************************************************************
 */

// Checking whether a selected object is a real image or not.
FCK.IsRealImage = function ( tag )
{
	if ( !tag )
	{
		return false ;
	}

	return ( tag.nodeName.IEquals( 'img' )
		&& !tag.getAttribute( '_fckfakelement' )
		&& !tag.getAttribute( '_fckflash' )
		&& !tag.getAttribute( '_fckmp3' )
		&& !tag.getAttribute( '_fckvideo' )
		&& !tag.getAttribute( 'MapNumber' )
		&& !( tag.getAttribute( 'src' ) && tag.getAttribute( 'src' ).toString().indexOf( '/cgi-bin/mimetex' ) >= 0 )
		&& !( tag.getAttribute( 'src' ) && tag.getAttribute( 'src' ).toString().indexOf( '/cgi-bin/mathtex' ) >= 0 )
		) ? true : false ;
} ;

// Checking for audio file reference which is to be used by a flash player.
FCK.IsAudio = function ( tag )
{
	if ( !tag )
	{
		return false ;
	}

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
FCK.IsVideo = function ( tag )
{
	if ( !tag )
	{
		return false ;
	}

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

	// This is for the specific flv player and SWFObject technique for attaching multimedia.
	if ( tag.nodeName.IEquals( 'div' ) )
	{
		if ( tag.id )
		{
			if ( tag.id.match( /^player[0-9]*-parent$/ ) )
			{
				return true ;
			}
		}
	}

	return false ;
} ;

// Returns specific type/source of embedded video.
FCK.GetVideoType = function ( img )
{
	var tag = FCK.GetRealElement( img ) ;

	if ( !tag )
	{
		return false ;
	}

	// This is for the specific flv player and SWFObject technique for attaching multimedia.
	if ( tag.nodeName.IEquals( 'div' ) )
	{
		if ( tag.id )
		{
			if ( tag.id.match( /^player[0-9]*-parent$/ ) )
			{
				return 'flv' ;
			}
		}
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
} ;


/*
 **************************************************************************************
 * Routines to deal with conversions of absolute and relative URLs.
 **************************************************************************************
 */

// Constants for fundamental URL conversions.
var RELATIVE_URL = 'relative' ;
var ABSOLUTE_URL = 'absolute' ;
var SEMI_ABSOLUTE_URL = 'semi-absolute' ;
FCK.RELATIVE_URL = RELATIVE_URL ;
FCK.ABSOLUTE_URL = ABSOLUTE_URL ;
FCK.SEMI_ABSOLUTE_URL = SEMI_ABSOLUTE_URL ;

// Constants used for conversions of special relative URLs.
var REPOSITORY_RELATIVE_URL = 'repository-relative' ;
var DOCUMENT_RELATIVE_URL = 'document-relative' ;
FCK.REPOSITORY_RELATIVE_URL = REPOSITORY_RELATIVE_URL ;
FCK.DOCUMENT_RELATIVE_URL = DOCUMENT_RELATIVE_URL ;

// Conversion of selected by the file managers Flash URL.
// In introduction sections relative flash URLs do not work.
// This is why we will record semi-absolute URLs there.
FCK.GetSelectedFlashUrl = function ( url )
{
	// Detection of introduction section.
	if ( FCKConfig.CreateDocumentDir == 'document/' ||
		/\.\.\/.*\/document\/$/.test( FCKConfig.CreateDocumentDir ) )
	{
		return FCK.GetUrl( url, SEMI_ABSOLUTE_URL ) ;
	}
	else
	{
		return FCK.GetSelectedUrl( url ) ;
	}
}

// Conversion of selected by the file managers URLs.
FCK.GetSelectedUrl = function ( url )
{
	url = FCK.GetUrl ( url, DOCUMENT_RELATIVE_URL ) ;

	if ( FCK.GetUrlType (url) != RELATIVE_URL )
	{
		url = FCK.GetUrl ( url, SEMI_ABSOLUTE_URL ) ;
	}

	return url ;
}

// Conversion of a URL into desired type.
FCK.GetUrl = function ( url, type )
{
	if ( !url )
	{
		return url ;
	}

	if ( !type )
	{
		return url ;
	}

	url = url.toString().Trim() ;

	if ( url.indexOf( './' ) == 0 )
	{
		url = url.substr( 2 );
	}

	switch ( type )
	{
		case RELATIVE_URL:

			switch ( FCK.GetUrlType( url ) )
			{
				case RELATIVE_URL:

					break ;

				case ABSOLUTE_URL:
				case SEMI_ABSOLUTE_URL:

					url = FCK.ConvertUrl( url, RELATIVE_URL, FCKConfig.CreateDocumentWebDir ) ;
					if ( FCK.GetUrlType( url ) == RELATIVE_URL )
					{
						url = FCK.GetUrl( url, DOCUMENT_RELATIVE_URL ) ;
					}

					break ;

				default:
					break ;
			}

			break ;

		case REPOSITORY_RELATIVE_URL:

			switch ( FCK.GetUrlType( url ) )
			{
				case RELATIVE_URL:

					if ( url.indexOf( FCKConfig.CreateDocumentDir) == 0 )
					{
						url = url.substr( FCKConfig.CreateDocumentDir.length ) ;
					}
					
					break ;

				case ABSOLUTE_URL:

					url = FCK.ConvertUrl( url, RELATIVE_URL, FCKConfig.CreateDocumentWebDir ) ;

					break ;

				case SEMI_ABSOLUTE_URL:

					url = FCK.ConvertUrl( url, RELATIVE_URL, FCKConfig.CreateDocumentWebDir ) ;

					break ;

				default:
					break ;
			}

			break ;

		case DOCUMENT_RELATIVE_URL:

			switch ( FCK.GetUrlType( url ) )
			{
				case RELATIVE_URL:

					if ( FCKConfig.CreateDocumentDir !=  '/' )
					{
						url = FCKConfig.CreateDocumentDir + url ;
					}
					
					break ;

				case ABSOLUTE_URL:
				case SEMI_ABSOLUTE_URL:

					url = FCK.ConvertUrl( url, RELATIVE_URL, FCKConfig.CreateDocumentWebDir ) ;
					if ( FCK.GetUrlType( url ) == RELATIVE_URL )
					{
						url = FCK.GetUrl( url, DOCUMENT_RELATIVE_URL ) ;
					}

					break ;

				default:
					break ;
			}

			break ;

		case ABSOLUTE_URL:

			switch ( FCK.GetUrlType( url ) )
			{
				case RELATIVE_URL:

					url = FCK.GetUrl( url, REPOSITORY_RELATIVE_URL ) ;
					url = FCK.ConvertUrl( url, ABSOLUTE_URL, FCKConfig.CreateDocumentWebDir) ;
					
					break ;

				case ABSOLUTE_URL:

					break ;

				case SEMI_ABSOLUTE_URL:

					url = FCK.ConvertUrl( url, ABSOLUTE_URL, FCKConfig.CreateDocumentWebDir) ;

					break ;

				default:
					break ;
			}

			break ;

		case SEMI_ABSOLUTE_URL:

			switch ( FCK.GetUrlType( url ) )
			{
				case RELATIVE_URL:

					url = FCK.GetUrl( url, REPOSITORY_RELATIVE_URL ) ;
					url = FCK.ConvertUrl( url, SEMI_ABSOLUTE_URL, FCKConfig.CreateDocumentWebDir) ;
					
					break ;

				case ABSOLUTE_URL:

					url = FCK.ConvertUrl( url, SEMI_ABSOLUTE_URL, FCKConfig.CreateDocumentWebDir) ;

					break ;

				case SEMI_ABSOLUTE_URL:

					break ;

				default:
					break ;
			}

			break ;

		default:
			break ;
	}

	return url ;
}

// Common URL conversion routine.
FCK.ConvertUrl = function ( url, type, base )
{
	if ( !url )
	{
		return '' ;
	}

	if ( !type )
	{
		return '' ;
	}

	url = url.toString().Trim() ;

	if ( url.indexOf( './' ) == 0 )
	{
		url = url.substr( 2 );
	}

	type = type.toString().Trim() ;

	if ( !base )
	{
		base = '' ;
	}

	base = base.toString().Trim() ;

	if ( base ==  '/' )
	{
		base = '' ;
	}

	switch ( type )
	{
		case RELATIVE_URL:

			switch ( FCK.GetUrlType( url ) )
			{
				case ABSOLUTE_URL:

					base = FCK.ConvertUrl( base, ABSOLUTE_URL ) ;

					if ( url.indexOf( base ) == 0 )
					{
						url = url.substr( base.length ) ;
					}

					break ;

				case SEMI_ABSOLUTE_URL:

					base = FCK.ConvertUrl( base, SEMI_ABSOLUTE_URL ) ;

					if ( url.indexOf( base ) == 0 )
					{
						url = url.substr( base.length ) ;
					}

					break ;

				default:
					break ;
			}

			break ;

		case ABSOLUTE_URL:

			switch ( FCK.GetUrlType( url ) )
			{
				case RELATIVE_URL:

					base = FCK.ConvertUrl( base, ABSOLUTE_URL ) ;

					url = base + url ;

					break ;

				case SEMI_ABSOLUTE_URL:

					url = FCK.GetServerBase() + url.substr( 1 ) ;

					break ;

				default:
					break ;
			}

			break ;

		case SEMI_ABSOLUTE_URL:

			switch ( FCK.GetUrlType( url ) )
			{
				case RELATIVE_URL:

					base = FCK.ConvertUrl( base, SEMI_ABSOLUTE_URL ) ;

					url = base + url ;

					break ;

				case ABSOLUTE_URL:

					var serverBase = FCK.GetServerBase() ;
					if ( serverBase == FCK.GetServerBase( url ) )
					{
						url = '/' + url.substr( serverBase.length ) ;
					}

					break ;

				default:
					break ;
			}

			break ;

		default:
			break ;
	}

	return url ;
}

// Returns type of a given URL.
// Returned values:
// RELATIVE_URL ( returned for example for images/image.png )
// SEMI_ABSOLUTE_URL ( /dokeos/courses/TEST/document/images/image.png )
// ABSOLUTE_URL ( http://localhost/dokeos/courses/TEST/document/images/image.png )
// '' - in case of error
FCK.GetUrlType = function ( url )
{
	if ( !url )
	{
		return '' ;
	}

	url = url.toString().Trim() ;

	if ( url.indexOf( '/' ) == 0 )
	{
		return SEMI_ABSOLUTE_URL ;
	}

	if ( url.match( /^([^:]+\:)?\/\// ) )
	{
		return ABSOLUTE_URL ;
	}

	return RELATIVE_URL ;
} ;

// Extracts the server base from a given URL.
// If the URL is omited, the function returns the base of the server where LMS runs.
// Example:
//     Your site is http://www.mysite.org/dokeos
//     The server base is http://www.mysite.org/
FCK.GetServerBase = function ( url )
{
	if ( !url )
	{
		if ( FCKConfig.CreateDocumentWebDir )
		{
			url = FCKConfig.CreateDocumentWebDir ;
		}
		else
		{
			url = location.href ;
		}
	}

	url = url.toString().replace( /(https?:\/\/[^\/]*)\/.*/, '$1' ) + '/' ;

	return url ;
} ;
