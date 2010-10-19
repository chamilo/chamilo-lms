/*
 *	Chamilo LMS
 *
 *	Copyright (c) 2010 Ivan Tcholakov <ivantcholakov@gmail.com>
 *
 *	License:
 *	GNU Lesser General Public License, Version 3, 29 June 2007
 *	by Free Software Foundation, Inc. (http://www.gnu.org/licenses/lgpl.html)
 */

// Loading the ASCIIMathML.js if it is not present yet.
if ( typeof AMprocessNode != 'function' )
{
    LoadScript( FCKConfig.ScriptASCIIMathML ) ;
}

// Settings for ASCIIMathML.js
// Suppressing the built-in notification messages when the browser is incompatible.
var notifyIfNoMathML = false ;
var alertIfNoMathML = false;
var notifyIfNoSVG = false;
var alertIfNoSVG = false;
// Suppressing automatic parsing of the document at loading, the editor is to initate this.
var translateASCIIMath = false ;

// Registering the related command.
FCKCommands.RegisterCommand( 'asciisvg', new FCKDialogCommand( FCKLang['DlgAsciiSvg'], FCKLang['DlgAsciiSvgGraphEditor'], FCKConfig.PluginsPath + 'asciisvg/fck_asciisvg.html', 500, 500 ) ) ;

// Create the "asciisvg" toolbar button.
var oAsciiSvgItem = new FCKToolbarButton( 'asciisvg', FCKLang['DlgAsciiSvg'] ) ;
oAsciiSvgItem.IconPath = FCKConfig.PluginsPath + 'asciisvg/asciisvg.gif' ;
// 'asciisvg' is the name used in the Toolbar config.
FCKToolbarItems.RegisterItem( 'asciisvg', oAsciiSvgItem ) ;

// Context menu support.
// TODO

// Double-click support.
// TODO

