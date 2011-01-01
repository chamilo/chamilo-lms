/*
 *	Chamilo LMS
 *
 *	Copyright (c) 2010 Ivan Tcholakov <ivantcholakov@gmail.com>
 *
 *	License:
 *	GNU Lesser General Public License, Version 3, 29 June 2007
 *	by Free Software Foundation, Inc. (http://www.gnu.org/licenses/lgpl.html)
 */

var dialog = window.parent ;
var oEditor	= dialog.InnerDialogLoaded() ;
var FCK = oEditor.FCK ;
var FCKLang = oEditor.FCKLang ;
var FCKConfig = oEditor.FCKConfig ;
var FCKTools = oEditor.FCKTools ;
var FCKBrowserInfo = oEditor.FCKBrowserInfo ;
var FCKUndo = oEditor.FCKUndo ;

// Set the language direction.
window.document.dir = FCKLang.Dir ;

// TODO: This language variable to be corrected
FCKLang["DlgAsciiIncompatibleBrowser"] = FCKLang["DlgAsciiIncompatibleBrowser"] ? FCKLang["DlgAsciiIncompatibleBrowser"] : 'Your browser is not able to show mathematical formulas. Please, use %s1 or Internet Explorer with %s2 plugin.' ;

// Settings for ASCIIMathML.js
// Checking for native MathML support, it is always needed for this dialog.
var checkForMathML = true;
// Suppressing the built-in notification messages when the browser is incompatible.
var notifyIfNoMathML = false ;
var alertIfNoMathML = false ;
var notifyIfNoSVG = false ;
var alertIfNoSVG = false ;
// Formula translation will be called explicitly in this dialog after it loads.
//var translateOnLoad = false ;
var translateOnLoad = true ;
//
// Formula tooltips are hard-coded in this dialog, there is no need they to be generated.
var showasciiformulaonhover = false ;
// Font size of the formulas in this dialog.
var mathfontsize = "1.1em" ;

var noSVG = ASnoSVG ; // Temporarily added.
var width = 300 ;
var height = 200 ;
var alignm = 'middle' ;
//var sscr = '' ;
var sscr = '-7.5,7.5,-5,5,1,1,1,1,1,300,200' ;
//var isnew = null ;
var isnew = true ;

function LoadSelection()
{
    // ...

    UpdatePreview() ;
}

function Ok()
{
    // ...

    return true ;
}

window.onload = function()
{
    // Translate the dialog box texts.
    oEditor.FCKLanguageManager.TranslatePage( document ) ;

    // Initialization of the script ASCIIMathML.js.
    //init() ;

    // Initialization of the dialog's script.
    AsciisvgDialog.init() ;

    // Load the selected element information (if any).
    LoadSelection() ;

    dialog.SetAutoSize( true ) ;

    // Activate the "OK" button.
    dialog.SetOkButton( true ) ;
}

function UpdatePreview()
{
    var commands = '' ;

    initialized = false ;

    var m_xmin = GetE( 'xmin' ).value ;
    var m_xmax = GetE( 'xmax' ).value ;
    var m_ymin = GetE( 'ymin' ).value ;
    var m_ymax = GetE( 'ymax' ).value ;
    if ( m_ymin == '' ) m_ymin = null ;
    if ( m_ymax == '' ) m_ymax = null ;
    commands += m_xmin + ',' + m_xmax + ',' + m_ymin + ',' + m_ymax + ',' ;

    var m_xscl = GetE( 'xscl' ).value ;
    var m_yscl = GetE( 'yscl' ).value ;
    if ( m_xscl == '' ) m_xscl = null ;
    if ( m_yscl == '' ) m_yscl = null ;
    if ( GetE( 'labels' ).checked )
    {
        var m_labels = '1' ;
    }
    else
    {
        var m_labels = 'null' ;
    }

    if ( GetE( 'grid' ).checked )
    {
        var m_grid = ',' + m_xscl + ',' + m_yscl ;
    }
    else
    {
        var m_grid = ',null,null' ;
    }
    commands += m_xscl + ',' + m_yscl + ',' + m_labels + m_grid ;

    commands += ',' + GetE( 'gwidth' ).value + ',' + GetE( 'gheight' ).value ;


    var graphs = GetE( 'graphs' ) ;
    for ( i = 0 ; i < graphs.length ; i++ )
    {
        commands += ',' + graphs.options[i].value ;
    }

    width = GetE( 'gwidth' ).value ;
    height = GetE( 'gheight' ).value ;
    sscr = commands ;
    alignm = GetE( 'alignment' ).value ;

    if ( noSVG )
    {
        var pvimg = GetE( 'previewimg' ) ;
        pvimg.src = AScgiloc + '?sscr=' + encodeURIComponent(commands) ;
        //var ed = tinyMCEPopup.editor ;
        //ed.dom.setStyle( pvimg, 'width' , width + 'px' ) ;
        //ed.dom.setStyle( pvimg, 'height' , height + 'px' ) ;
    }
    else
    {
        var pvsvg = GetE( 'previewsvg' ) ;
        parseShortScript( commands , width , height ) ;
    }
}

function UpdateText( id , text )
{
    var node = GetE( id ) ;
    node.replaceChild( document.createTextNode( text ) , node.lastChild ) ;
}

function UpdateEquationType()
{
    var type = GetE( 'eqntype' ).value ;

    if ( type == 'func' )
    {
        UpdateText( 'eq1lbl', 'f(x)=' ) ;
        GetE( 'equation' ).value = 'sin(x)' ;
        UpdateText( 'eq2lbl' , '' ) ;
        UpdateText( 'eq2' , '' ) ;

    }
    else if ( type == 'polar' )
    {
        UpdateText( 'eq1lbl' , 'r(t)=' ) ;
        GetE( 'equation' ).value = 't' ;
        UpdateText( 'eq2lbl' , '' ) ;
        UpdateText( 'eq2' , '' ) ;

    }
    else if ( type == 'param' )
    {
        UpdateText( 'eq1lbl' , 'f(t)=' ) ;
        UpdateText( 'eq2lbl' , 'g(t)= ' ) ;
        var newinput = document.createElement( 'input' ) ;
        newinput.type = 'text' ;
        newinput.name = 'eqn2' ;
        newinput.id = 'eqn2' ;
        newinput.size = '15' ;
        newinput.value = 'cos(t)' ;
        var node = GetE( 'eq2' ) ;
        node.replaceChild( newinput,node.lastChild ) ;
        GetE( 'equation' ).value = 'sin(t)' ;

    }
    else if ( type == 'slope' )
    {
        UpdateText( 'eq1lbl' , 'dy/dx (x,y) = ' ) ;
        GetE( 'equation' ).value = 'x*y' ;
        UpdateText( 'eq2lbl' , 'every ' ) ;
        var newinput = document.createElement( 'input' ) ;
        newinput.type = 'text' ;
        newinput.name = 'eqn2' ;
        newinput.id = 'eqn2' ;
        newinput.size = '2' ;
        newinput.value = '1' ;
        var node = GetE( 'eq2' ) ;
        node.replaceChild( newinput , node.lastChild ) ;
    }
    GetE( 'gstart' ).selectedIndex = 0 ;
    GetE( 'gend' ).selectedIndex = 0 ;
    GetE( 'xstart' ).value = '' ;
    GetE( 'xend' ).value = '' ;
    GetE( 'gcolor' ).selectedIndex = 0 ;
    GetE( 'strokewidth' ).selectedIndex = 0 ;
    GetE( 'strokedash' ).selectedIndex = 0 ;
}

function LoadEquation()
{
    var graphs = GetE( 'graphs' ) ;

    var sa = graphs.options[ graphs.selectedIndex ].value.split( ',' ) ;

    if ( sa[0] == 'func' ) {
        GetE( 'eqntype' ).selectedIndex = 0 ;
    } else if ( sa[0] == 'polar' ) {
        GetE( 'eqntype' ).selectedIndex = 1 ;
    } else if ( sa[0] == 'param' ) {
        GetE( 'eqntype' ).selectedIndex = 2 ;
    } else if ( sa[0] == 'slope' ) {
        GetE( 'eqntype' ).selectedIndex = 3 ;
    }
    UpdateEquationType() ;
    GetE( 'equation' ).value = sa[1] ;
    if ( ( sa[0] == 'param' ) || ( sa[0] == 'slope' ) ) {
        GetE( 'eqn2' ).value = sa[2] ;
    }

    GetE( 'gstart' ).selectedIndex = sa[ 3 ] ;
    GetE( 'gend' ).selectedIndex = sa[ 4 ] ;
    GetE( 'xstart' ).value = sa[ 5 ] ;
    GetE( 'xend' ).value = sa[ 6 ] ;
    switch ( sa[ 7 ] )
    {
        case 'black' : GetE( 'gcolor' ).selectedIndex = 0 ; break ;
        case 'red' : GetE( 'gcolor' ).selectedIndex = 1 ; break ;
        case 'orange' : GetE( 'gcolor' ).selectedIndex = 2 ; break ;
        case 'yellow' : GetE( 'gcolor' ).selectedIndex = 3 ; break ;
        case 'green' : GetE( 'gcolor' ).selectedIndex = 4 ; break ;
        case 'blue' : GetE( 'gcolor' ).selectedIndex = 5 ; break ;
        case 'purple' : GetE( 'gcolor' ).selectedIndex = 6 ; break ;
    }
    GetE( 'strokewidth' ).selectedIndex = sa[ 8 ] - 1 ;
    switch ( sa[ 9 ] )
    {
        case '2' : GetE( 'strokedash' ).selectedIndex = 1 ; break ;
        case '5' : GetE( 'strokedash' ).selectedIndex = 2 ; break ;
        case '5 2' : GetE( 'strokedash' ).selectedIndex = 3 ; break ;
        case '7 3 2 3' : GetE( 'strokedash' ).selectedIndex = 4 ; break ;
        default : GetE( 'strokedash' ).selectedIndex = 0 ;
    }
}

function LoadGraphScript( text )
{
    var alignment = 'middle' ;
    var sa = text.split( ',' ) ;
    GetE( 'xmin' ).value = sa[ 0 ] ;
    GetE( 'xmax' ).value = sa[ 1 ] ;
    GetE( 'ymin' ).value = sa[ 2 ] ;
    GetE( 'ymax' ).value = sa[ 3 ] ;
    GetE( 'xscl' ).value = sa[ 4 ] ;
    GetE( 'yscl' ).value = sa[ 5 ] ;

    if ( sa[ 6 ] != 'null' )
    {
        GetE( 'labels' ).checked = true ;
    }
    else
    {
        GetE( 'labels' ).checked = false ;
    }
    if ( typeof eval( sa[ 7 ] ) == 'number' )
    {
        GetE( 'grid' ).checked = true ;
    }
    else
    {
        GetE( 'grid' ).checked = false ;
    }

    GetE( 'gwidth' ).value = sa[ 9 ] ;
    GetE( 'gheight' ).value = sa[ 10 ] ;

    GetE( 'graphs' ).length = 0 ;

    var inx = 11 ;
    while ( sa.length > inx + 9 )
    {
        var newopt = document.createElement( 'option' ) ;

        if ( sa[ inx ] == 'func' )
        {
            newopt.text = 'y=' + sa[ inx + 1 ] ;
        }
        else if ( sa[ inx ] == 'polar' )
        {
            newopt.text = 'r=' + sa[ inx + 1 ] ;
        }
        else if ( sa[inx] == 'param' )
        {
            newopt.text = '[x,y]=[' + sa[ inx + 1 ] + ',' + sa[ inx + 2 ] + ']' ;
        }
        else if ( sa[inx] == 'slope' )
        {
            newopt.text = 'dy/dx=' + sa[ inx + 1 ] ;
        }
        newopt.value = sa[inx] + ',' + sa[ inx + 1 ] + ',' + sa[ inx + 2 ] + ',' + sa[ inx + 3 ] + ',' + sa[ inx + 4 ] + ',' + sa[ inx + 5 ] + ',' + sa[ inx + 6 ] + ',' + sa[ inx + 7 ] + ',' + sa[ inx + 8] + ',' + sa[ inx + 9 ] ;
        var graphs = GetE( 'graphs' ) ;
        graphs.options[ graphs.options.length ] = newopt ;
        //GetE( 'graphs' ).add( newopt ) ;
        inx += 10 ;
    }
    if ( inx > 11 ) {
        LoadEquation() ;
    }

    switch ( alignment.toLowerCase() )
    {
        case 'text-top' : GetE( 'alignment' ).selectedIndex = 0 ; break ;
        case 'middle' : GetE( 'alignment' ).selectedIndex = 1 ; break ;
        case 'text-bottom' : GetE( 'alignment' ).selectedIndex = 2 ; break ;
        case 'left' : GetE( 'alignment' ).selectedIndex = 3 ; break ;
        case 'right' : GetE( 'alignment' ).selectedIndex = 4 ; break ;
        default: GetE( 'alignment' ).selectedIndex = 0 ; break ;
    }

    //UpdatePreview() ;
}

function AddGraph()
{
    var graphs = GetE( 'graphs' ) ;
    var newopt = document.createElement( 'option' ) ;

    var type = GetE( 'eqntype' ).value ;
    var eq1 = GetE( 'equation' ).value ;
    var eq2 = null ;

    if ( type == 'func' )
    {
        newopt.text = 'y=' + eq1 ;
    }
    else if ( type == 'polar' )
    {
        newopt.text = 'r=' + eq1 ;
    }
    else if ( type == 'param' )
    {
        eq2 = GetE( 'eqn2' ).value ;
        newopt.text = '[x,y]=[' + eq1 + ','+ eq2 + ']' ;
    }
    else if ( type == 'slope' )
    {
        newopt.text = 'dy/dx=' + eq1 ;
        eq2 = GetE( 'eqn2' ).value ;
    }

    var m_gstart = GetE( 'gstart' ).selectedIndex ;
    var m_gend = GetE( 'gend' ).selectedIndex ;
    var m_color = GetE( 'gcolor' ).value ;
    var m_strokewidth = GetE( 'strokewidth' ).value ;
    var m_strokedash = GetE( 'strokedash' ).value ;
    if ( GetE( 'xstart' ).value.length > 0 )
    {
        //newopt.value = 'myplot(' + eqn +',"' + m_gstart+  '","' + m_gend + '",' + GetE( 'xstart' ).value + ',' + GetE( 'xend' ).value  + ');' ;
        newopt.value = type + ',' + eq1 + ',' + eq2 + ',' + m_gstart + ',' + m_gend + ',' + GetE( 'xstart' ).value + ',' + GetE( 'xend' ).value + ',' + m_color + ',' + m_strokewidth + ',' + m_strokedash ;
    }
    else
    {
        //newopt.value = 'myplot(' + eqn + ',"' + m_gstart + '","' + m_gend + '");' ;
        newopt.value = type + ',' + eq1 + ',' + eq2 + ',' + m_gstart + ',' + m_gend + ',,' + ',' + m_color + ',' + m_strokewidth + ',' + m_strokedash ;
    }

    graphs.options[ graphs.options.length ] = newopt ;
    graphs.selectedIndex = graphs.options.length - 1 ;
    UpdatePreview() ;
    GetE( 'equation' ).focus() ;
}

function ReplaceGraph()
{
    var graphs = GetE( 'graphs' ) ;
    if ( graphs.selectedIndex >= 0 )
    {
        graphs.options[ graphs.selectedIndex ] = null ;  // Standards compliant.
    }
    AddGraph() ;
}

function RemoveGraph()
{
    var graphs = GetE( 'graphs' ) ;
    if ( graphs.selectedIndex >= 0 )
    {
        graphs.options[ graphs.selectedIndex ] = null ;
        if ( graphs.options.length > 0 ) { LoadEquation() ; }
    }
    UpdatePreview() ;
    GetE( 'equation' ).focus() ;
}

// TODO: To be removed ...
var AsciisvgDialog =
{
    init : function()
    {
        //var f = document.forms[ 0 ] ;

        // Get the selected contents as text and place it in the input.
        /*
        width = tinyMCEPopup.getWindowArg( 'width' ) ;
        height = tinyMCEPopup.getWindowArg( 'height' ) ;
        isnew = tinyMCEPopup.getWindowArg( 'isnew' ) ;
        sscr = tinyMCEPopup.getWindowArg( 'sscr' ) ;
        alignm = tinyMCEPopup.getWindowArg( 'alignm' ) ;
        */

        if ( noSVG )
        {
            GetE( 'preview' ).innerHTML = '<img id="previewimg" style="width:' + width + 'px; height: ' + height + 'px; vertical-align: middle; float: none;" src="' + AScgiloc + '?sscr=' + encodeURIComponent( sscr ) + '" script=" " />' ;
        }
        else
        {
            //GetE( 'preview' ).innerHTML = '<embed id="previewsvg" type="image/svg+xml" src="' + FCKConfig.DrawingASCIISVG + '" style="width: 300px; height: 200px; vertical-align: middle; float: none;" sscr="-7.5,7.5,-5,5,1,1,1,1,1,300,200" />' ;
            GetE( 'previewsvg' ).setAttribute( 'sscr' , sscr );
        }
        LoadGraphScript( sscr ) ;
    } ,

    insert : function()
    {
        var ed = tinyMCEPopup.editor ;
        // Insert the contents from the input into the document.
        if ( isnew )
        {
            if ( alignm == 'left' || alignm == 'right' )
            {
                aligntxt = 'vertical-align: middle; float: ' + alignm + ';' ;
            }
            else
            {
                aligntxt = 'vertical-align: ' + alignm + '; float: none;' ;
            }
            tinyMCEPopup.editor.execCommand( 'mceInsertContent', false, '<img style="width: 300px; height: 200px; ' + aligntxt + '" src="' + AScgiloc + '?sscr=' + encodeURIComponent( sscr ) + '" sscr="' + sscr + '" script=" " />') ;
        }
        else
        {
            el = tinyMCEPopup.editor.selection.getNode() ;
            ed.dom.setAttrib( el , 'sscr' , sscr ) ;
            ed.dom.setAttrib( el , 'src' , AScgiloc + '?sscr=' + encodeURIComponent( sscr ) ) ;
            ed.dom.setAttrib( el , 'width' , width ) ;
            ed.dom.setAttrib( el , 'height' , height ) ;
            ed.dom.setStyle( el , 'width' , width + 'px' ) ;
            ed.dom.setStyle( el , 'height' , height + 'px') ;
            if ( alignm == 'left' || alignm == 'right' )
            {
                ed.dom.setStyle( el , 'float' , alignm ) ;
                ed.dom.setStyle( el , 'vertical-align' , 'middle' ) ;
            }
            else
            {
                ed.dom.setStyle( el , 'float' , 'none' ) ;
                ed.dom.setStyle( el , 'vertical-align' , alignm ) ;
            }
        }
        tinyMCEPopup.close() ;
    }

} ;
