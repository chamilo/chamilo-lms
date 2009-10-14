/*
 *	Dokeos - elearning and course management software
 *
 *	Copyright (c) 2008-2009 Dokeos SPRL
 *	Copyright (c) 2008-2009 Ivan Tcholakov <ivantcholakov@gmail.com>
 *
 *	License:
 *	GNU Lesser General Public License, Version 3, 29 June 2007
 *	by Free Software Foundation, Inc. (http://www.gnu.org/licenses/lgpl.html)
 *
 * Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
 * Mail: info@dokeos.com
 */

// Loading the ASCIIMathML.js if it is not present yet.
if ( typeof AMprocessNode != 'function' )
{
	LoadScript( FCKConfig.ScriptASCIIMathML ) ;
}
// Suppressing the built-in notification message when the browser is incompatible.
notifyIfNoMathML = false ;
// Suppressing parsing AsciiMath formulas at loading.
translateASCIIMath = false ;
// Small font is used in the dialog.
mathfontsize = "1.1em";

// Registering the related command.
FCKCommands.RegisterCommand( 'asciimath', new FCKDialogCommand( FCKLang['DlgAsciiMath'], FCKLang['DlgAsciiMath'], FCKConfig.PluginsPath + 'asciimath/fck_asciimath.html', 800, 550 ) ) ;

// Create the "asciimath" toolbar button.
var oAsciiMathItem = new FCKToolbarButton( 'asciimath', FCKLang['DlgAsciiMath'] ) ;
oAsciiMathItem.IconPath = FCKConfig.PluginsPath + 'asciimath/asciimath.gif' ;
// 'asciimath' is the name used in the Toolbar config.
FCKToolbarItems.RegisterItem( 'asciimath', oAsciiMathItem ) ;

// Context menu support.
FCK.ContextMenu.RegisterListener( {
	AddItems : function( menu, tag, tagName )
	{
		if ( FCKAsciiMath.FindFormulaContainer( tag ) )
		{
			menu.AddSeparator() ;
			menu.AddItem( 'asciimath', FCKLang['DlgAsciiMath'], oAsciiMathItem.IconPath ) ;
		}
	}}
);

// Double-click support.
FCK.RegisterDoubleClickHandler(
	function ( tag )
	{
		if ( FCKAsciiMath.FindFormulaContainer( tag ) )
		{
			FCKCommands.GetCommand( 'asciimath' ).Execute() ;
		}
	}, null
) ;

// This object implements some AsciiMath related operations.
var FCKAsciiMath = new Object() ;

FCKAsciiMath.GetSearchElementFromSelection = function()
{
	var oSelectedContainer = FCK.Selection.GetSelectedElement() ;
	if ( ! oSelectedContainer )
	{
		oSelectedContainer = FCK.Selection.GetBoundaryParentElement( true ) ;
	}
	return oSelectedContainer ;
}

FCKAsciiMath.IsFormula = function( node )
{
	if ( node && node.nodeName && node.nodeName.IEquals( 'span' ) && node.className && node.className.indexOf( 'AM' ) != -1 )
	{
		return true ;
	}
	return false ;
}

FCKAsciiMath.FindFormulaContainer = function( node )
{
	var current_node = node ;
	while ( current_node )
	{
		if ( !current_node.nodeName )
		{
			continue ;
		}
		if ( current_node.nodeName.IEquals( 'body', 'table' ) )
		{
			break ;
		}
		if ( FCKAsciiMath.IsFormula( current_node ) )
		{
			return current_node ;
		}
		if ( current_node.parentNode )
		{
			current_node = current_node.parentNode ;
		}
		else
		{
			break ;
		}
	}
	return null ;
}

FCKAsciiMath.IsParsed = function( node )
{
	return node.getElementsByTagName( 'math' )[0] ? true : false ;
}

FCKAsciiMath.GetFormula = function( node )
{
	var result = '' ;
	if ( FCKAsciiMath.IsFormula( node ) )
	{
		if ( FCKAsciiMath.IsParsed( node ) )
		{
			if ( node.title )
			{
				result = node.title ;
			}
		}
		else
		{
			result = node.innerHTML ;
		}
	}
	return result.replace( /`/g, '' ) ;
}

FCKAsciiMath.Delete = function()
{
	var oSpanAM = FCKAsciiMath.FindFormulaContainer( FCKAsciiMath.GetSearchElementFromSelection() ) ;
	if ( oSpanAM )
	{
		FCK.Selection.SelectNode( oSpanAM ) ;
	}
	else
	{
		return ;
	}

	if ( FCKBrowserInfo.IsIE )
	{
		// For IE: Before deletion, we have to move the selection outside the formula
		// in order to prevent "Unspecified error".
		var span_target = FCK.EditorDocument.createElement( 'span' ) ;
		span_target.innerHTML = '&nbsp;' ;
		span_target = oSpanAM.parentNode.insertBefore( span_target, oSpanAM ) ;
		FCK.Selection.SelectNode( span_target ) ;
	}

	FCK.Selection.Delete() ;

	if ( FCKBrowserInfo.IsIE )
	{
		FCKUndo.SaveUndoStep() ;
		oSpanAM.parentNode.removeChild( oSpanAM ) ;
	}
}

var FCKAsciiMathProcessor = FCKDocumentProcessor.AppendNew() ;

FCKAsciiMathProcessor.ProcessDocument = function( document )
{
	var spans = FCK.EditorDocument.getElementsByTagName( 'SPAN' ) ;
	var span ;
	var i = spans.length - 1 ;
	while ( i >= 0 && ( span = spans[i--] ) )
	{
		if ( FCKAsciiMath.IsFormula( span ) && !FCKAsciiMath.IsParsed ( span ) )
		{
			var clone = span.cloneNode( true ) ;
			clone.title = span.innerHTML ;
			AMprocessNode( clone, false ) ;

			clone.setAttribute( '_fckfakelement', 'true', 0 ) ;
			clone.setAttribute( '_fckrealelement', FCKTempBin.AddElement( span ), 0 ) ;

			// To disable resizing.
			clone.onresizestart = function()
			{
				FCK.EditorWindow.event.returnValue = false ;
				return false ;
			}

			span.parentNode.insertBefore( clone, span ) ;
			span.parentNode.removeChild( span ) ;
		}
	}
}

FCKAsciiMath.SetListeners = function()
{
	if ( FCK.EditMode != FCK_EDITMODE_WYSIWYG )
	{
		return ;
	}

	if ( !FCKBrowserInfo.IsIE )
	{
		// On Gecko we must do this trick so the user select all the SPAN when clicking on it.
		FCK.EditorDocument.addEventListener( 'click', function( e )
			{
				var formula_container = FCKAsciiMath.FindFormulaContainer( e.target ) ;
				if ( formula_container )
				{
					FCKSelection.SelectNode( formula_container ) ;
				}
			}, true
		) ;

		// On Gecko-like browsers we have to prevent editing the parsed formula in the editor.
		FCK.EditorDocument.addEventListener( 'keypress', function( e )
			{
				var key = e.keyCode || e.which ;
				switch ( key )
				{
					// Only keys for moving the cursor should be enabled.
					case 37: // Left
					case 39: // Right
					case 38: // Up
					case 40: // Down
					case 36: // Home
					case 35: // End
					case 33: // Pg Up
					case 34: // Pg Down
						break ;
					// Deletion.
					case 8:  // Backspace
					case 46: // Del
						if ( FCKAsciiMath.FindFormulaContainer( FCKAsciiMath.GetSearchElementFromSelection() ) )
						{
							FCKAsciiMath.Delete() ;
							if ( e.preventDefault ) e.preventDefault() ;
							if ( e.stopPropagation ) e.stopPropagation() ;
							break ;
						}
					default:
						if ( FCKAsciiMath.FindFormulaContainer( FCKAsciiMath.GetSearchElementFromSelection() ) )
						{
							// We are inside a formula, block edition.
							if ( e.preventDefault ) e.preventDefault() ;
							if ( e.stopPropagation ) e.stopPropagation() ;
						}
						break ;
				}
			}, true
		) ;
	}
	else
	{
		FCKAsciiMath.KeyDownIE = function( e )
		{
			if ( !e ) e = window.event ;
			var key = e.keyCode ;
			switch ( key )
			{
				// Deletion.
				case 8:  // Backspace
				case 46: // Del
					if ( FCKAsciiMath.FindFormulaContainer( FCKAsciiMath.GetSearchElementFromSelection() ) )
					{
						FCKAsciiMath.Delete() ;
						e.cancelBubble = true ;
						break ;
					}
				default:
					break ;
			}
		}

		FCKTools.AddEventListener( FCK.EditorDocument.body, 'keydown', FCKAsciiMath.KeyDownIE ) ;
	}
}

FCK.Events.AttachEvent( 'OnAfterSetHTML', FCKAsciiMath.SetListeners ) ;

// We need to attach the script AsciiMathML.js after editing a full page content.
// There is no appropriate event to be captured, so the following method has been chosen for modification.
FCK.UpdateLinkedField = function()
{
	// Added code.
	if ( FCKConfig.FullPage )
	{
		var html = FCK.EditorDocument.getElementsByTagName('html')[0] ;
		var head ;
		if ( typeof html == 'object' )
		{
			head = html.getElementsByTagName( 'HEAD' )[0] ;
		}

		if ( typeof head == 'object' )
		{
			var has_formula = false ;
			var spans = FCK.EditorDocument.getElementsByTagName( 'SPAN' ) ;
			var span ;
			var i = spans.length - 1 ;
			while ( i >= 0 && ( span = spans[i--] ) )
			{
				if ( FCKAsciiMath.IsFormula( span ) )
				{
					has_formula = true ;
					break ;
				}
			}

			var has_script = false ;
			var head_data = FCK.GetData( false );
			if ( head_data )
			{
				head_data = head_data.toString().match( /<head\s?[^>]*>(.*?)<\/head\s*>/i ) ;
				if ( head_data[1] )
				{
					head_data = head_data[1] ;
					if ( head_data.indexOf( 'ASCIIMathML.js' ) != -1 )
					{
						has_script = true ;
					}
				}
			}

			if ( !has_script )
			{
				// TODO: This fragment works in WYSIWYG mode only.
				script = FCK.EditorDocument.createElement( 'script' ) ;
				script.setAttribute( 'src', FCKConfig.ScriptASCIIMathML ) ;
				script.setAttribute( 'type', 'text/javascript' ) ;
				head.appendChild( script ) ;
			}
		}
	}
	// End added code.

	var value = FCK.GetData( FCKConfig.FormatOutput ) ;

	if ( FCKConfig.HtmlEncodeOutput )
		value = FCKTools.HTMLEncode( value ) ;

	FCK.LinkedField.value = value ;
	FCK.Events.FireEvent( 'OnAfterLinkedFieldUpdate' ) ;
} ;
