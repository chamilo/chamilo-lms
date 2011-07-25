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
FCKCommands.RegisterCommand( 'asciisvg', new FCKDialogCommand( FCKLang['DlgAsciiSvg'], FCKLang['DlgAsciiSvgGraphEditor'], FCKConfig.PluginsPath + 'asciisvg/fck_asciisvg.html', 750, 550 ) ) ;

// Create the "asciisvg" toolbar button.
var oAsciiSvgItem = new FCKToolbarButton( 'asciisvg', FCKLang['DlgAsciiSvg'] ) ;
oAsciiSvgItem.IconPath = FCKConfig.PluginsPath + 'asciisvg/asciisvg.gif' ;
// 'asciisvg' is the name used in the Toolbar config.
FCKToolbarItems.RegisterItem( 'asciisvg', oAsciiSvgItem ) ;

// Context menu support.
FCK.ContextMenu.RegisterListener( {
    AddItems : function( menu, tag, tagName )
    {
        if ( tag.tagName == 'IMG' && tag.getAttribute( '_fckasciisvg' ) )
        {
            menu.AddSeparator() ;
            menu.AddItem( 'asciisvg', FCKLang['DlgAsciiSvg'], oAsciiSvgItem.IconPath ) ;
        }
    }}
);

// Double-click support.
FCK.RegisterDoubleClickHandler(
    function ( tag )
    {
        if ( tag.tagName == 'IMG' && tag.getAttribute( '_fckasciisvg' ) )
        {
            FCKCommands.GetCommand( 'asciisvg' ).Execute() ;
        }
    }, null
) ;

var FCKAsciiSvg = new Object() ;

// We need to attach automaticaly the script AsciiMathML.js when saving a full page.
FCKAsciiSvg.UpdateLinkedField = FCK.UpdateLinkedField ;
FCK.UpdateLinkedField = function()
{
    if ( FCKConfig.FullPage )
    {
        var html = FCK.EditorDocument.getElementsByTagName('html')[0] ;
        var head ;
        var body ;
        if ( typeof html == 'object' )
        {
            head = html.getElementsByTagName( 'HEAD' )[0] ;
        }

        if ( typeof head == 'object' )
        {
            var doc_data = FCK.GetData( false );
            var has_graph = false ;
            var has_script = false ;

            if ( doc_data )
            {
                if ( doc_data.toString().match( /<embed[^>]*sscr=[^>]*>/i ) )
                {
                    has_graph = true ;
                }

                head_data = doc_data.toString().match( /<head[^>]*>(.*?)<\/head\s*>/i ) ;
                if ( head_data && head_data.toString().indexOf( 'ASCIIMathML.js' ) != -1 )
                {
                    has_script = true ;
                }
            }

            if ( has_graph && !has_script )
            {
                // TODO: This fragment works in WYSIWYG mode only.
                script = FCK.EditorDocument.createElement( 'script' ) ;
                script.setAttribute( 'src', FCKConfig.ScriptASCIIMathML ) ;
                script.setAttribute( 'type', 'text/javascript' ) ;
                head.appendChild( script ) ;
            }
        }
    }

    // Calling the original method FCK.UpdateLinkedField().
    FCKAsciiSvg.UpdateLinkedField() ;
} ;
