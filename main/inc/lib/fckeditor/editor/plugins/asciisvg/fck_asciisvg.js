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

function LoadSelection()
{
    UpdatePreview() ;
}

function UpdatePreview()
{
    AsciisvgDialog.graphit() ;
}



//tinyMCEPopup.requireLangPack();

var AsciisvgDialog =
{
    width: 300 ,
    height: 200 ,
    alignm: 'middle' ,
    //sscr: '' ,
    sscr: '-7.5,7.5,-5,5,1,1,1,1,1,300,200' ,
    //isnew: null ,
    isnew: true ,
    AScgiloc: null ,

    init : function()
    {
        //var f = document.forms[ 0 ] ;

        // Get the selected contents as text and place it in the input.
        /*
        this.width = tinyMCEPopup.getWindowArg( 'width' ) ;
        this.height = tinyMCEPopup.getWindowArg( 'height' ) ;
        this.isnew = tinyMCEPopup.getWindowArg( 'isnew' ) ;
        this.sscr = tinyMCEPopup.getWindowArg( 'sscr' ) ;
        */
        //this.AScgiloc = tinyMCEPopup.getWindowArg( 'AScgiloc' ) ;
        this.AScgiloc = AScgiloc ;
        /*
        this.alignm = tinyMCEPopup.getWindowArg( 'alignm' ) ;
        */

        if ( noSVG )
        {
            GetE( 'preview' ).innerHTML = '<img id="previewimg" style="width:' + this.width + 'px; height: ' + this.height + 'px; vertical-align: middle; float: none;" src="' + this.AScgiloc + '?sscr=' + encodeURIComponent( this.sscr ) + '" script=" " />' ;
        }
        else
        {
            //GetE( 'preview' ).innerHTML = '<embed id="previewsvg" type="image/svg+xml" src="' + FCKConfig.DrawingASCIISVG + '" style="width: 300px; height: 200px; vertical-align: middle; float: none;" sscr="-7.5,7.5,-5,5,1,1,1,1,1,300,200" />' ;
            GetE( 'previewsvg' ).setAttribute( 'sscr' , this.sscr );
        }
        this.getsscr( this.sscr ) ;
    } ,

    insert : function()
    {
        ed = tinyMCEPopup.editor ;
        // Insert the contents from the input into the document.
        if ( this.isnew )
        {
            if ( this.alignm == 'left' || this.alignm == 'right' )
            {
                aligntxt = 'vertical-align: middle; float: ' + this.alignm + ';' ;
            }
            else
            {
                aligntxt = 'vertical-align: ' + this.alignm + '; float: none;' ;
            }
            tinyMCEPopup.editor.execCommand( 'mceInsertContent', false, '<img style="width: 300px; height: 200px; ' + aligntxt + '" src="' + this.AScgiloc + '?sscr=' + encodeURIComponent( this.sscr ) + '" sscr="' + this.sscr + '" script=" " />') ;
        }
        else
        {
            el = tinyMCEPopup.editor.selection.getNode() ;
            ed.dom.setAttrib( el , 'sscr' , this.sscr ) ;
            ed.dom.setAttrib( el , 'src' , this.AScgiloc + '?sscr=' + encodeURIComponent( this.sscr ) ) ;
            ed.dom.setAttrib( el , 'width' , this.width ) ;
            ed.dom.setAttrib( el , 'height' , this.height ) ;
            ed.dom.setStyle( el , 'width' , this.width + 'px' ) ;
            ed.dom.setStyle( el , 'height' , this.height + 'px') ;
            if ( this.alignm == 'left' || this.alignm == 'right' )
            {
                ed.dom.setStyle( el , 'float' , this.alignm ) ;
                ed.dom.setStyle( el , 'vertical-align' , 'middle' ) ;
            }
            else
            {
                ed.dom.setStyle( el , 'float' , 'none' ) ;
                ed.dom.setStyle( el , 'vertical-align' , this.alignm ) ;
            }
        }
        tinyMCEPopup.close() ;
    } ,

    addgraph : function()
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
            eq2= GetE( 'eqn2' ).value ;
            newopt.text = '[x,y]=[' + eq1 + ','+ eq2 + ']' ;
        }
        else if ( type == 'slope' )
        {
            newopt.text = 'dy/dx=' + eq1 ;
            eq2= GetE( 'eqn2' ).value ;
        }

        m_gstart = GetE( 'gstart' ).selectedIndex ;
        m_gend = GetE( 'gend' ).selectedIndex ;
        m_color = GetE( 'gcolor' ).value ;
        m_strokewidth = GetE( 'strokewidth' ).value ;
        m_strokedash = GetE( 'strokedash' ).value ;
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
        this.graphit() ;
        GetE( 'equation' ).focus() ;
    } ,

    replacegraph : function()
    {
        var graphs = GetE( 'graphs' ) ;
        if ( graphs.selectedIndex >= 0 )
        {
            graphs.options[ graphs.selectedIndex ] = null ;  //standards compliant
        }
        this.addgraph() ;
    } ,

    removegraph : function()
    {
        var graphs = GetE( 'graphs' ) ;
        if ( graphs.selectedIndex >= 0 ) {
            graphs.options[ graphs.selectedIndex ] = null ;
            if ( graphs.options.length > 0 ) { this.loadeqn() ; }
        }
        this.graphit() ;
        GetE( 'equation' ).focus() ;
    } ,

    graphit : function()
    {
        //ed = tinyMCEPopup.editor ;
        var commands ;
        commands = '' ;

        initialized = false ;

        //commands = 'setBorder(5);' ;

        m_xmin = GetE( 'xmin' ).value ;
        m_xmax = GetE( 'xmax' ).value ;
        m_ymin = GetE( 'ymin' ).value ;
        m_ymax = GetE( 'ymax' ).value ;
        if ( m_ymin == '' ) m_ymin = null ;
        if ( m_ymax == '' ) m_ymax = null ;
        commands += m_xmin + ',' + m_xmax + ',' + m_ymin + ',' + m_ymax + ',' ;

        m_xscl = GetE( 'xscl' ).value ;
        m_yscl = GetE( 'yscl' ).value ;
        if ( m_xscl == '' ) m_xscl = null ;
        if ( m_yscl == '' ) m_yscl = null ;
        if ( GetE( 'labels' ).checked )
        {
            m_labels = '1' ;
        }
        else
        {
            m_labels = 'null' ;
        }

        if ( GetE( 'grid' ).checked )
        {
            m_grid = ',' + m_xscl + ',' + m_yscl ;
        }
        else
        {
            m_grid = ',null,null' ;
        }
        commands += m_xscl + ',' + m_yscl + ',' + m_labels + m_grid ;

        commands += ',' + GetE( 'gwidth' ).value + ',' + GetE( 'gheight' ).value ;


        graphs = GetE( 'graphs' ) ;
        for ( i = 0 ; i < graphs.length ; i++ )
        {
            commands += ',' + graphs.options[i].value ;
        }

        this.width = GetE( 'gwidth' ).value ;
        this.height = GetE( 'gheight' ).value ;
        this.sscr = commands ;
        this.alignm = GetE( 'alignment' ).value ;

        if ( noSVG )
        {
            pvimg = GetE( 'previewimg' ) ;
            pvimg.src = this.AScgiloc + '?sscr=' + encodeURIComponent(commands) ;
            //ed.dom.setStyle( pvimg, 'width' , this.width + 'px' ) ;
            //ed.dom.setStyle( pvimg, 'height' , this.height + 'px' ) ;
        }
        else
        {
            pvsvg = GetE( 'previewsvg' ) ;
            parseShortScript( commands , this.width , this.height ) ;
        }
    } ,

    changetype : function()
    {
        var type = GetE( 'eqntype' ).value ;

        if ( type == 'func' )
        {
            this.chgtext( 'eq1lbl', 'f(x)=' ) ;
            GetE( 'equation' ).value = 'sin(x)' ;
            this.chgtext( 'eq2lbl' , '' ) ;
            this.chgtext( 'eq2' , '' ) ;

        }
        else if ( type == 'polar' )
        {
            this.chgtext( 'eq1lbl' , 'r(t)=' ) ;
            GetE( 'equation' ).value = 't' ;
            this.chgtext( 'eq2lbl' , '' ) ;
            this.chgtext( 'eq2' , '' ) ;

        }
        else if ( type == 'param' )
        {
            this.chgtext( 'eq1lbl' , 'f(t)=' ) ;
            this.chgtext( 'eq2lbl' , 'g(t)= ' ) ;
            var newinput = document.createElement( 'input' ) ;
            newinput.type = 'text' ;
            newinput.name = 'eqn2' ;
            newinput.id = 'eqn2' ;
            newinput.size = '15' ;
            newinput.value = 'cos(t)' ;
            var cnode = GetE( 'eq2' ) ;
            cnode.replaceChild( newinput,cnode.lastChild ) ;
            GetE( 'equation' ).value = 'sin(t)' ;

        }
        else if ( type == 'slope' )
        {
            this.chgtext( 'eq1lbl' , 'dy/dx (x,y) = ' ) ;
            GetE( 'equation' ).value = 'x*y' ;
            this.chgtext( 'eq2lbl' , 'every ' ) ;
            var newinput = document.createElement( 'input' ) ;
            newinput.type = 'text' ;
            newinput.name = 'eqn2' ;
            newinput.id = 'eqn2' ;
            newinput.size = '2' ;
            newinput.value = '1' ;
            var cnode = GetE( 'eq2' ) ;
            cnode.replaceChild( newinput , cnode.lastChild ) ;
        }
        GetE( 'gstart' ).selectedIndex = 0 ;
        GetE( 'gend' ).selectedIndex = 0 ;
        GetE( 'xstart' ).value = '' ;
        GetE( 'xend' ).value = '' ;
        GetE( 'gcolor' ).selectedIndex = 0 ;
        GetE( 'strokewidth' ).selectedIndex = 0 ;
        GetE( 'strokedash' ).selectedIndex = 0 ;
    } ,

    loadeqn : function()
    {
        graphs = GetE( 'graphs' ) ;

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
        this.changetype() ;
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
    } ,

    getsscr : function( text , alignment )
    {
        alignment = 'middle' ;
        sa = text.split( ',' ) ;
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
            graphs = GetE( 'graphs' ) ;
            graphs.options[ graphs.options.length ] = newopt ;
            //GetE( 'graphs' ).add( newopt ) ;
            inx += 10 ;
        }
        if ( inx > 11 ) {
            this.loadeqn() ;
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

        //this.graphit() ;
    } ,

    chgtext : function( tag , text )
    {
        var cnode = GetE( tag ) ;
        cnode.replaceChild( document.createTextNode( text ) , cnode.lastChild ) ;
    }

} ;
