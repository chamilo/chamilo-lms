/*
 * MindTouch Deki - enterprise collaboration and integration platform
 * Copyright (C) 2006-2009 MindTouch, Inc.
 * www.mindtouch.com  oss@mindtouch.com
 *
 * For community documentation and downloads visit www.opengarden.org;
 * please review the licensing section.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

DimensionPicker = function(element, command, onPick) {
	this._minCols = 5;
	this._minRows = 5;

	this.show = function ( panelX, panelY, relElement )
	{
		this._panelX = panelX ;
		this._panelY = panelY ;
		this._relEl  = relElement ;
		
		this._pick( 0, 0 ) ;
	};

	this.onPick = onPick;

	this._setDimensionInCells = function( element, cols, rows )
	{
		element.style.width = 18 * cols + 'px' ;
		element.style.height = 18 * rows + 'px' ;

		element.cols = cols ;
		element.rows = rows ;
	};

	this._init = function()
	{
		this._targetDoc = FCKTools.GetElementDocument( element ) ;

		this._picker = element ;

		this._command = command ;

		this._mouseDiv = this._targetDoc.createElement( 'div' ) ;
		this._mouseDiv.className = 'dimension-picker-mouse' ;

		this._uhDiv = this._targetDoc.createElement( 'div' ) ;
		this._uhDiv.className = 'dimension-picker-unhighlighted' ;

		this._hDiv = this._targetDoc.createElement( 'div' ) ;
		this._hDiv.className = 'dimension-picker-highlighted' ;

		this.statusDiv = this._targetDoc.createElement( 'div' ) ;
		this.statusDiv.className = 'dimension-picker-status' ;

		this._cellsDiv = this._targetDoc.createElement( 'div' ) ;
		this._picker.appendChild( this._cellsDiv ) ;
		this._picker.appendChild( this.statusDiv ) ;
		this._cellsDiv.appendChild( this._mouseDiv ) ;
		this._cellsDiv.appendChild( this._uhDiv ) ;
		this._cellsDiv.appendChild( this._hDiv ) ;
		
		var dimensionPicker = this ;

		var k = 0 ;
		
		FCKTools.AddEventListener(this._mouseDiv, 'mousemove', function ( event ) {
			var current = dimensionPicker._getCurrent( event ) ;
			if ( dimensionPicker._changed(current.cols, current.rows) )
			{
				dimensionPicker._pick( current.cols, current.rows ) ;
			}
			k++ ;
		});

		FCKTools.AddEventListener(this._picker, 'click', function ( event ) {
			var current = dimensionPicker._getCurrent( event ) ;
			dimensionPicker.onPick( command, { 'cols' : current.cols, 'rows' : current.rows } ) ;
		});
	};

	this._pick = function( cols, rows )
	{
		this._setDimensionInCells( this._hDiv, cols, rows ) ;

		var uhCols = Math.max( this._minCols, cols ) ;
		var uhRows = Math.max( this._minRows, rows ) ;

		this._setDimensionInCells( this._uhDiv, uhCols, uhRows ) ;

		this.statusDiv.innerHTML = rows + 'x' + cols ;
		
		if ( FCKBrowserInfo.IsIE )
		{
			this._mouseDiv.style.width = this._cellsDiv.offsetWidth + 18 + 'px' ;
			this._mouseDiv.style.height = this._cellsDiv.offsetHeight + 18 + 'px' ;
		}

		var pickerWidth = this._uhDiv.offsetWidth ;
		var pickerHeight = this._uhDiv.offsetHeight + this.statusDiv.offsetHeight ;

		pickerWidth += 14 ;
		pickerHeight += 14;

		if ( FCKBrowserInfo.IsIE )
		{
			this._command._Panel.Show( this._panelX, this._panelY, this._relEl, pickerWidth + 6, pickerHeight + 6 ) ;
		}
		else
		{
			this._command._Panel._IFrame.style.width = pickerWidth + 6 + 'px' ;
			this._command._Panel._IFrame.style.height = pickerHeight + 6 + 'px' ;
		}

		this._command._Panel.MainNode.style.width = pickerWidth + 'px' ;
		this._command._Panel.MainNode.style.height = pickerHeight + 'px' ;

		this._picker.style.width = pickerWidth ;
		this._picker.style.height = pickerHeight ;
	};

	this._getCurrent = function( event )
	{
		var offset = FCKTools.GetElementPosition( this._cellsDiv, FCKTools.GetDocumentWindow(this._targetDoc) ) ;
		var mousePos = FCKTools.GetMousePosition( event ) ;

		var x = mousePos.X - offset.X ;
		var y = mousePos.Y - offset.Y ;

		if ( x <= 0 || y <= 0 )
		{
			x = y = 0 ;
		}

		var cols = Math.ceil( x / 18.0 ) ;
		var rows = Math.ceil( y / 18.0 ) ;

		return { cols: cols, rows: rows } ;
	};

	this._changed = function( cols, rows )
	{
		if ( cols != this._lastCols || rows != this._lastRows )
		{
			this._lastCols = cols ;
			this._lastRows = rows ;
			return true ;
		}

		return false ;
	};

	this._init();

	this._lastCols = null;
	this._lastRows = null;
};

// from quirksmode.org
FCKTools.GetEventTarget = function( e )
{
	var target = null ;

	if ( !e )
		var e = window.event ;

	if ( e.target )
		target = e.target ;
	else if ( e.srcElement )
		target = e.srcElement ;

	if ( target && target.nodeType == 3 ) // defeat Safari bug
		target = target.parentNode ;

	return target ;
}

// from quirksmode.org
FCKTools.GetMousePosition = function( e )
{
	var posx = 0 ;
	var posy = 0 ;

	if ( !e )
		var e = window.event ;

	if ( e.pageX || e.pageY )
	{
		posx = e.pageX ;
		posy = e.pageY ;
	}
	else if ( e.clientX || e.clientY )
	{
		posx = e.clientX + document.body.scrollLeft
			+ document.documentElement.scrollLeft ;
		posy = e.clientY + document.body.scrollTop
			+ document.documentElement.scrollTop ;
	}

	return { X : posx, Y : posy } ;
}

var TableDimensionPickerCommand = function( oToolbarSet )
{
	this.Name = 'TableOC' ;
	this.Type = 'TableOC' ;

	var oWindow ;

	if ( FCKBrowserInfo.IsIE )
		oWindow = window ;
	else if ( oToolbarSet._IFrame )
		oWindow = FCKTools.GetElementWindow( oToolbarSet._IFrame ) ;
	else
		oWindow = window.parent ;

	this._Panel = new FCKPanel( oWindow ) ;
	this._Panel.AppendStyleSheet( FCKConfig.SkinEditorCSS ) ;
	this._Panel.AppendStyleSheet( FCKConfig.PluginsPath + 'tableoneclick/css/style.css' ) ;
	this._Panel.MainNode.className = 'FCK_Panel' ;
	this._CreatePanelBody( this._Panel.Document, this._Panel.MainNode ) ;
	oToolbarSet.ToolbarItems.GetItem( this.Name ).RegisterPanel( this._Panel ) ;

	FCKTools.DisableSelection( this._Panel.Document.body ) ;
}

TableDimensionPickerCommand.prototype.Execute = function( panelX, panelY, relElement )
{
	this._Panel.Show( panelX, panelY, relElement ) ;
	this._Picker.show( panelX, panelY, relElement ) ;
}

TableDimensionPickerCommand.prototype.GetState = function()
{
	if ( FCK.EditMode != FCK_EDITMODE_WYSIWYG )
		return FCK_TRISTATE_DISABLED ;
	return FCK_TRISTATE_OFF ;
}

function TableDimensionPickerCommand_OnClick( command, dimension )
{
	if ( dimension.cols > 0 && dimension.rows > 0 )
	{
		FCKUndo.SaveUndoStep() ;

		var oDoc = FCK.EditorDocument ;
		var oTable = oDoc.createElement( 'TABLE' ) ;

		oTable.style.width = '100%' ;
		oTable.style.tableLayout = 'fixed' ;
		oTable.setAttribute( 'cellPadding', 1 ) ;
		oTable.setAttribute( 'cellSpacing', 1 ) ;
		oTable.setAttribute( 'border', 1 ) ;
		oTable.setAttribute( '_fckstyle', oTable.style.cssText ) ;

		var oTbody = oDoc.createElement( 'TBODY' ) ;
		oTable.appendChild( oTbody ) ;

		for ( var i = 0 ; i < dimension.rows ; i++ )
		{
			var oRow = oDoc.createElement( 'TR' ) ;
			oTbody.appendChild( oRow ) ;

			for ( var j = 0 ; j < dimension.cols ; j++ )
			{
				var oCell = oDoc.createElement( 'TD' ) ;
				oRow.appendChild( oCell ) ;

				if ( FCKBrowserInfo.IsGeckoLike )
					FCKTools.AppendBogusBr( oCell ) ;
			}
		}

		FCK.InsertElement( oTable ) ;
	}

	FCK.Focus() ;

	if ( dimension.cols > 0 && dimension.rows > 0 )
		FCK.Events.FireEvent( 'OnSelectionChange' ) ;

	command._Panel.Hide() ;
}

TableDimensionPickerCommand.prototype._CreatePanelBody = function( targetDocument, targetDiv )
{
	var oDiv = targetDiv.appendChild( targetDocument.createElement( "DIV" ) ) ;
	oDiv.id = 'dimension-picker' ;

	this._Picker = new DimensionPicker( oDiv, this, TableDimensionPickerCommand_OnClick ) ;
}

function FCKToolbarSet_Create( overhideLocation )
{
	var oToolbarSet ;

	var sLocation = overhideLocation || FCKConfig.ToolbarLocation ;
	switch ( sLocation )
	{
		case 'In' :
			document.getElementById( 'xToolbarRow' ).style.display = '' ;
			oToolbarSet = new FCKToolbarSet( document ) ;
			break ;
		case 'None' :
			oToolbarSet = new FCKToolbarSet( document ) ;
			break ;

//		case 'OutTop' :
			// Not supported.

		default :
			FCK.Events.AttachEvent( 'OnBlur', FCK_OnBlur ) ;
			FCK.Events.AttachEvent( 'OnFocus', FCK_OnFocus ) ;

			var eToolbarTarget ;

			// Out:[TargetWindow]([TargetId])
			var oOutMatch = sLocation.match( /^Out:(.+)\((\w+)\)$/ ) ;
			if ( oOutMatch )
			{
				if ( FCKBrowserInfo.IsAIR )
					FCKAdobeAIR.ToolbarSet_GetOutElement( window, oOutMatch ) ;
				else
					eToolbarTarget = eval( 'parent.' + oOutMatch[1] ).document.getElementById( oOutMatch[2] ) ;
			}
			else
			{
				// Out:[TargetId]
				oOutMatch = sLocation.match( /^Out:(\w+)$/ ) ;
				if ( oOutMatch )
					eToolbarTarget = parent.document.getElementById( oOutMatch[1] ) ;
			}

			if ( !eToolbarTarget )
			{
				alert( 'Invalid value for "ToolbarLocation"' ) ;
				return arguments.callee( 'In' );
			}

			// If it is a shared toolbar, it may be already available in the target element.
			oToolbarSet = eToolbarTarget.__FCKToolbarSet ;
			if ( oToolbarSet )
				break ;

			// Create the IFRAME that will hold the toolbar inside the target element.
			var eToolbarIFrame = FCKTools.GetElementDocument( eToolbarTarget ).createElement( 'iframe' ) ;
			eToolbarIFrame.src = 'javascript:void(0)' ;
			eToolbarIFrame.frameBorder = 0 ;
			eToolbarIFrame.width = '100%' ;
			eToolbarIFrame.height = '10' ;
			eToolbarTarget.appendChild( eToolbarIFrame ) ;
			eToolbarIFrame.unselectable = 'on' ;

			// Write the basic HTML for the toolbar (copy from the editor main page).
			var eTargetDocument = eToolbarIFrame.contentWindow.document ;

			// Workaround for Safari 12256. Ticket #63
			var sBase = '' ;
			if ( FCKBrowserInfo.IsSafari )
				sBase = '<base href="' + window.document.location + '">' ;

			// Initialize the IFRAME document body.
			eTargetDocument.open() ;
			eTargetDocument.write( '<html><head>' + sBase + '<script type="text/javascript"> var adjust = function() { window.frameElement.height = document.body.scrollHeight ; }; '
					+ 'window.onresize = window.onload = '
					+ 'function(){'		// poll scrollHeight until it no longer changes for 1 sec.
					+ 'var timer = null;'
					+ 'var lastHeight = -1;'
					+ 'var lastChange = 0;'
					+ 'var poller = function(){'
					+ 'var currentHeight = document.body.scrollHeight || 0;'
					+ 'var currentTime = (new Date()).getTime();'
					+ 'if (currentHeight != lastHeight){'
					+ 'lastChange = currentTime;'
					+ 'adjust();'
					+ 'lastHeight = document.body.scrollHeight;'
					+ '}'
					+ 'if (lastChange < currentTime - 1000) clearInterval(timer);'
					+ '};'
					+ 'timer = setInterval(poller, 100);'
					+ '}'
					+ '</script></head><body style="overflow: hidden">' + document.getElementById( 'xToolbarSpace' ).innerHTML + '</body></html>' ) ;
			eTargetDocument.close() ;

			if( FCKBrowserInfo.IsAIR )
				FCKAdobeAIR.ToolbarSet_InitOutFrame( eTargetDocument ) ;

			FCKTools.AddEventListener( eTargetDocument, 'contextmenu', FCKTools.CancelEvent ) ;

			// Load external resources (must be done here, otherwise Firefox will not
			// have the document DOM ready to be used right away.
			FCKTools.AppendStyleSheet( eTargetDocument, FCKConfig.SkinEditorCSS ) ;

			oToolbarSet = eToolbarTarget.__FCKToolbarSet = new FCKToolbarSet( eTargetDocument ) ;
			oToolbarSet._IFrame = eToolbarIFrame ;

			if ( FCK.IECleanup )
				FCK.IECleanup.AddItem( eToolbarTarget, FCKToolbarSet_Target_Cleanup ) ;
	}

	oToolbarSet.CurrentInstance = FCK ;
	if ( !oToolbarSet.ToolbarItems )
		oToolbarSet.ToolbarItems = FCKToolbarItems ;

	FCK.AttachToOnSelectionChange( oToolbarSet.RefreshItemsState ) ;

	FCK.Events.FireEvent( 'OnToolbarCreated', oToolbarSet ) ;

	return oToolbarSet ;
}

if ( FCKBrowserInfo.IsIE )
{
	FCKTools.GetElementPosition = function( el, relativeWindow )
	{
		// Initializes the Coordinates object that will be returned by the function.
		var c = { X:0, Y:0 } ;

		var oWindow = relativeWindow || window ;

		// Loop throw the offset chain.
		while ( el )
		{
				c.X += el.offsetLeft - el.scrollLeft ;
				c.Y += el.offsetTop - el.scrollTop  ;

				if ( el.offsetParent == null )
				{
						var oOwnerWindow = FCKTools.GetElementWindow( el ) ;

						if ( oOwnerWindow != oWindow )
								el = oOwnerWindow.frameElement ;
						else
						{
								c.X += el.scrollLeft ;
								c.Y += el.scrollTop  ;
								break ;
						}
				}
				else
						el = el.offsetParent ;
		}

		// Return the Coordinates object
		return c ;
	}
}

FCK.Events.AttachEvent( 'OnToolbarCreated', function( oEditor, oToolbarSet )
{
	FCKToolbarItems.RegisterItem( 'TableOC', new FCKToolbarPanelButton( 'TableOC', FCKLang.InsertTable, null, null, 39 ) ) ;
	FCKCommands.RegisterCommand( 'TableOC', new TableDimensionPickerCommand( oToolbarSet ) ) ;
} ) ;
