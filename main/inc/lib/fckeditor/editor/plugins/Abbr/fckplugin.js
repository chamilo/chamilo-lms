/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2005 Frederico Caldeira Knabben
 * 
 * Licensed under the terms of the GNU Lesser General Public License:
 *    http://www.opensource.org/licenses/lgpl-license.php
 * 
 * For further information visit:
 *    http://www.fckeditor.net/
 * 
 * File Name: fckplugin.js
 * 
 */
FCKCommands.RegisterCommand('Abbr', new FCKDialogCommand( 'Abbr', FCKLang.AbbrDlgTitle, FCKPlugins.Items['Abbr'].Path + 'fck_abbr.html', 350, 300 ) ) ;

// Create the "Abbr" toolbar button.
var oAbbrItem = new FCKToolbarButton( 'Abbr', FCKLang.AbbrBtn ) ;
oAbbrItem.IconPath = FCKPlugins.Items['Abbr'].Path + 'Abbr.gif' ;
FCKToolbarItems.RegisterItem( 'Abbr', oAbbrItem ) ;

// The object used for all Abbr operations.
var FCKAbbr = new Object() ;

// Insert a new Abbr
FCKAbbr.Insert = function(val) {

	var hrefStartHtml	= (val == '') ? '<abbr>' : '<abbr title="'+val+'">';
	var hrefEndHtml		= '</abbr>';

	mySelection = ( FCKBrowserInfo.IsIE) ? FCKSelection.GetSelectedHTML() : removeBR(FCKSelection.GetSelectedHTML());

	hrefHtml = hrefStartHtml+mySelection+hrefEndHtml;

//////////////////////////////////////////////////////
// 	choose one of these two lines; in fckeditor 2.5 both lines can be skipped!!!
//	
// 	hrefHtml = FCK.ProtectTags( hrefHtml ) ;								// needed because in fckeditor 2.4 protected tags only works with SetHTML
	hrefHtml = ProtectTags( hrefHtml ) ;									// needed because IE doesn't support <abbr> and it breaks it.
/////////////////////////////////////////////////////

	FCK.InsertHtml(hrefHtml);
}

FCKSelection.GetSelectedHTML = function() {									// see http://www.quirksmode.org/js/selected.html for other browsers
	if( FCKBrowserInfo.IsIE) {												// IE
		var oRange = FCK.EditorDocument.selection.createRange() ;
		//if an object like a table is deleted, the call to GetType before getting again a range returns Control
		switch ( this.GetType() ) {
			case 'Control' :
			return oRange.item(0).outerHTML;

			case 'None' :
			return '' ;

			default :
			return oRange.htmlText ;
		}
	}
	else if ( FCKBrowserInfo.IsGecko ) {									// Mozilla, Safari
		var oSelection = FCK.EditorWindow.getSelection();
		//Gecko doesn't provide a function to get the innerHTML of a selection,
		//so we must clone the selection to a temporary element and check that innerHTML
		var e = FCK.EditorDocument.createElement( 'DIV' );
		for ( var i = 0 ; i < oSelection.rangeCount ; i++ ) {
			e.appendChild( oSelection.getRangeAt(i).cloneContents() );
		}
		return e.innerHTML;
	}
}

function removeBR(input) {							/* Used with Gecko */
	var output = "";
	for (var i = 0; i < input.length; i++) {
		if ((input.charCodeAt(i) == 13) && (input.charCodeAt(i + 1) == 10)) {
			i++;
			output += " ";
		}
		else {
			output += input.charAt(i);
   		}
	}
	return output;
}

function ProtectTags ( html ) {											// copied from _source/internals/fck.js in fckeditor 2.4
	// IE doesn't support <abbr> and it breaks it. Let's protect it.
	if ( FCKBrowserInfo.IsIE ) {
		var sTags = 'ABBR' ;

		var oRegex = new RegExp( '<(' + sTags + ')([ \>])', 'gi' ) ;
		html = html.replace( oRegex, '<FCK:$1$2' ) ;

		oRegex = new RegExp( '<\/(' + sTags + ')>', 'gi' ) ;
		html = html.replace( oRegex, '<\/FCK:$1>' ) ;
	}
	return html ;
}

// put it into the contextmenu (optional)
FCK.ContextMenu.RegisterListener( {
	AddItems : function( menu, tag, tagName ) {
		// when the option is displayed, show a separator then the command
			//menu.AddSeparator() ;//Disabled by Chamilo. TODO:config by toolbar name teacher on/ student off
		// the command needs the registered command name, the title for the context menu, and the icon path
			//menu.AddItem( 'Abbr', FCKLang.Abbr, oAbbrItem.IconPath ) ;//Disabled by Chamilo. TODO:config by toolbar name teacher on/ student off
	}
}
);