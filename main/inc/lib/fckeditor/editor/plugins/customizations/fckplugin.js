/*
 *	Dokeos - elearning and course management software
 *
 *	Copyright (c) 2004-2008 Dokeos S.A.
 *	Copyright (c) 2003 Ghent University (UGent)
 *	Copyright (c) 2001 Universite catholique de Louvain (UCL)
 *	Copyright (c) 2008 Julio Montoya
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
 * Customizations by Julio Montoya for handling audio and video files.
 */

// mp3 files
FCKDocumentProcessor.AppendNew().ProcessDocument = function(A)
{
	var B = A.getElementsByTagName('embed'); 
	var C;
	var i = B.length - 1; 
	while (i >= 0 && (C = B[i--]))
	{
		var str = C.src;
		var leng = str.length;	
		var extension = str.substring(leng -3, leng );
		if (extension == 'mp3')
		{			 
			var D = FCKDocumentProcessor_CreateFakeImage('FCK__MP3', C.cloneNode(true));
			D.setAttribute('_fckmp3', 'true', 0);
			C.parentNode.insertBefore(D, C);
			C.parentNode.removeChild(C);			
		}
	}
};

// Files supported by fckembedvideo mpg, mpeg, avi, wmv, mov and asf. ')
FCKDocumentProcessor.AppendNew().ProcessDocument = function(A)
{
	var B = A.getElementsByTagName('embed'); 
	var C;
	var i = B.length - 1; 
	while (i >= 0 && (C = B[i--]))
	{
		var str = C.src;		
		var leng = str.length;	
		var extension = str.substring(leng -3, leng);
		if (extension == 'mpg' || extension == 'mpeg'|| extension == 'avi' || extension == 'wmv' || extension == 'mov' || extension == 'asf' )
		{			 
			var D = FCKDocumentProcessor_CreateFakeImage('FCK__Video', C.cloneNode(true));
			D.setAttribute('_fckvideo', 'true', 0);
			C.parentNode.insertBefore(D, C);
			C.parentNode.removeChild(C);			
		}
	}
};

// Fake images handling customization
FCKEmbedAndObjectProcessor.AddCustomHandler(function(A, B)
{
	if (!(A.nodeName.IEquals('embed') &&
		(A.type == 'application/x-shockwave-flash' || /\.swf($|#|\?)/i.test(A.src)))) return;
	if (A.src.match(/mediaplayer/g)) 
	{
		B.className = 'FCK__MP3'  //DOKEOS CUSTOMIZATION : the mp3 fake should appear if the flash is the mediaplayer
	}
	else
	{
		B.className = 'FCK__Flash';
	}
	B.setAttribute('_fckflash', 'true', 0);
});


/*
 * Customizations by Julio Montoya for enabling the external template selection dialog.
 */

FCKToolbarButton.prototype.Click = function()
{
	var A = this._ToolbarButton || this;
	FCK.ToolbarSet.CurrentInstance.Commands.GetCommand(A.CommandName).Execute();
};

FCKToolbarButton.prototype.ClickFrame = function()
{
	var A = this._ToolbarButton || this;
	return FCK.ToolbarSet.CurrentInstance.Commands.GetCommand(A.CommandName).ExecuteFrame();
};

FCKDialogCommand.prototype.Execute = function()
{
	FCKDialog.OpenDialog('FCKDialog_' + this.Name, this.Title, this.Url, this.Width, this.Height, this.CustomValue, null, this.Resizable);
};

FCKDialogCommand.prototype.ExecuteFrame = function()
{
	return FCKDialog.OpenDialogFrame('FCKDialog_' + this.Name, this.Title, this.Url, this.Width, this.Height, this.CustomValue, null, this.Resizable);
}; 

/*
 * The following customization of the dialog sub-system for enabling
 * the external template selection uses original source code,
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
 *
 * Dialog windows operations.
 */

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
		 */

		OpenDialogFrame: function(dialogName, dialogTitle, dialogPage, width, height, customValue, parentWindow, resizable)
		{
			//if (!topDialog) this.DisplayMainCover();
			var I = 
			{
				Title: dialogTitle,
				Page: dialogPage,
				Editor: window,
				CustomValue: customValue,
				TopWindow: topWindow
			};
			FCK.ToolbarSet.CurrentInstance.Selection.Save();
			var J = FCKTools.GetViewPaneSize(topWindow);
			var K = { 'X': 0, 'Y': 0 };
			var L = FCKBrowserInfo.IsIE && (!FCKBrowserInfo.IsIE7 || !FCKTools.IsStrictMode(topWindow.document));
			if (L) K = FCKTools.GetScrollPosition(topWindow);
			var M = Math.max(K.Y + (J.Height - height - 20)/2,0);
			var N = Math.max(K.X + (J.Width - width - 20)/2,0);
			var O = topDocument.createElement('iframe');
			//FCKTools.ResetStyles(O);
			O.src = FCKConfig.BasePath + 'fckdialogframe.html';
			O.frameBorder = 0;
			O.allowTransparency = true;
			FCKDomTools.SetElementStyles(O,
			{
				'position': (L) ? 'absolute' : 'fixed',
				'top': M + 'px',
				'left': N + 'px',
				'width': width + 'px',
				'height': height + 'px',
				'zIndex': getZIndex()
			});
			O._DialogArguments = I;
			//E.body.appendChild(O);
			O._ParentDialog = topDialog;
			topDialog = O;
			return I;
		},

		/*
		 * End of the added code.
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
				dialog._ParentDialog.contentWindow.SetEnabled( true ) ;
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