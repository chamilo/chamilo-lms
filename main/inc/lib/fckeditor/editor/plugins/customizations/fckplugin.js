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
 * Customizations for better integration of all the plugins with the editor.
 **************************************************************************************
 */


/*
 **************************************************************************************
 * Dialog system
 **************************************************************************************
 */

// This function has been redefined here in order dialog sizes to be controlled.
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

		case 'DocProps'		: oCommand = new FCKDialogCommand( 'DocProps'	, FCKLang.DocProps				, 'dialog/fck_docprops.html'	, 400, 380, FCKCommands.GetFullPageState ) ; break ;
		case 'Templates'	: oCommand = new FCKDialogCommand( 'Templates'	, FCKLang.DlgTemplatesTitle		, 'dialog/fck_template.html'	, 380, 450 ) ; break ;
		case 'Link'			: oCommand = new FCKDialogCommand( 'Link'		, FCKLang.DlgLnkWindowTitle		, 'dialog/fck_link.html'		, 400, 300 ) ; break ;
		case 'Unlink'		: oCommand = new FCKUnlinkCommand() ; break ;
		case 'VisitLink'	: oCommand = new FCKVisitLinkCommand() ; break ;
		case 'Anchor'		: oCommand = new FCKDialogCommand( 'Anchor'		, FCKLang.DlgAnchorTitle		, 'dialog/fck_anchor.html'		, 370, 160 ) ; break ;
		case 'AnchorDelete'	: oCommand = new FCKAnchorDeleteCommand() ; break ;
		case 'BulletedList'	: oCommand = new FCKDialogCommand( 'BulletedList', FCKLang.BulletedListProp		, 'dialog/fck_listprop.html?UL'	, 370, 160 ) ; break ;
		case 'NumberedList'	: oCommand = new FCKDialogCommand( 'NumberedList', FCKLang.NumberedListProp		, 'dialog/fck_listprop.html?OL'	, 370, 160 ) ; break ;
		case 'About'		: oCommand = new FCKDialogCommand( 'About'		, FCKLang.About					, 'dialog/fck_about.html'		, 420, 330, function(){ return FCK_TRISTATE_OFF ; } ) ; break ;
		case 'Find'			: oCommand = new FCKDialogCommand( 'Find'		, FCKLang.DlgFindAndReplaceTitle, 'dialog/fck_replace.html'		, 340, 230, null, null, 'Find' ) ; break ;
		case 'Replace'		: oCommand = new FCKDialogCommand( 'Replace'	, FCKLang.DlgFindAndReplaceTitle, 'dialog/fck_replace.html'		, 340, 230, null, null, 'Replace' ) ; break ;

		case 'Image'		: oCommand = new FCKDialogCommand( 'Image'		, FCKLang.DlgImgTitle			, 'dialog/fck_image.html'		, 450, 390 ) ; break ;
		case 'Flash'		: oCommand = new FCKDialogCommand( 'Flash'		, FCKLang.DlgFlashTitle			, 'dialog/fck_flash.html'		, 450, 390 ) ; break ;
		case 'SpecialChar'	: oCommand = new FCKDialogCommand( 'SpecialChar', FCKLang.DlgSpecialCharTitle	, 'dialog/fck_specialchar.html'	, 400, 290 ) ; break ;
		case 'Smiley'		: oCommand = new FCKDialogCommand( 'Smiley'		, FCKLang.DlgSmileyTitle		, 'dialog/fck_smiley.html'		, FCKConfig.SmileyWindowWidth, FCKConfig.SmileyWindowHeight ) ; break ;
		case 'Table'		: oCommand = new FCKDialogCommand( 'Table'		, FCKLang.DlgTableTitle			, 'dialog/fck_table.html'		, 480, 250 ) ; break ;
		case 'TableProp'	: oCommand = new FCKDialogCommand( 'Table'		, FCKLang.DlgTableTitle			, 'dialog/fck_table.html?Parent', 480, 250 ) ; break ;
		case 'TableCellProp': oCommand = new FCKDialogCommand( 'TableCell'	, FCKLang.DlgCellTitle			, 'dialog/fck_tablecell.html'	, 550, 240 ) ; break ;

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
		case 'CreateDiv'	: oCommand = new FCKDialogCommand( 'CreateDiv', FCKLang.CreateDiv, 'dialog/fck_div.html', 380, 210, null, null, true ) ; break ;
		case 'EditDiv'		: oCommand = new FCKDialogCommand( 'EditDiv', FCKLang.EditDiv, 'dialog/fck_div.html', 380, 210, null, null, false ) ; break ;
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
		case 'Select'		: oCommand = new FCKDialogCommand( 'Select'		, FCKLang.SelectionField, 'dialog/fck_select.html'		, 400, 340 ) ; break ;
		case 'ImageButton'	: oCommand = new FCKDialogCommand( 'ImageButton', FCKLang.ImageButton	, 'dialog/fck_image.html?ImageButton', 450, 390 ) ; break ;

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

		// For flv player, SWFObject attaching tecnique.
		var divs = document.getElementsByTagName( 'div' ) ; 
		var div;
		var i = divs.length - 1 ; 
		while ( i >= 0 && ( div = divs[i--] ) )
		{
			if ( FCK.is_video( div ) )
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
		if ( tagName == 'IMG' &&
			!tag.getAttribute( '_fckfakelement' ) &&
			!tag.getAttribute( '_fckflash' ) &&
			!tag.getAttribute( '_fckmp3' ) &&
			!tag.getAttribute( '_fckvideo' ) )
		{
			// Grouping all image-related commands at the bottom.
			menu.AddSeparator();
			// If an existing image has been selected, then Image Manager actually activates the Image Properties dialog.
			// These two dialogs are united under single button.
			//menu.AddItem( 'Image', FCKLang.ImageProperties, 37 ) ;
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
					menu.AddItem( 'EmbedMovies', FCKLang.DlgEmbedMoviesTooltip, FCKConfig.PluginsPath + 'fckEmbedMovies/embedmovies.gif' ) ;
					break ;
				case 'youtube' :
					menu.AddSeparator() ;
					menu.AddItem( 'YouTube', FCKLang.YouTubeTip, FCKConfig.PluginsPath + 'youtube/youtube.gif' ) ;
					break ;
				case 'flv' :
					menu.AddSeparator() ;
					menu.AddItem( 'flvPlayer', FCKLang.DlgFLVPlayerTitle, FCKConfig.PluginsPath + 'flvPlayer/flvPlayer.gif' ) ;
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
				case 'youtube' :
					FCKCommands.GetCommand( 'YouTube' ).Execute() ;
					break ;
				case 'flv':
					FCKCommands.GetCommand( 'flvPlayer' ).Execute() ;
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
FCK.get_video_type = function ( img )
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
