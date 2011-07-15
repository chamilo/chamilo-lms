/*
 *	Chamilo LMS
 *
 *	Copyright (c) 2009-2010 Ivan Tcholakov <ivantcholakov@gmail.com>
 *	Copyright (c) 2009 Dokeos SPRL
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
var FCKAsciiMath = oEditor.FCKAsciiMath ;

// Set the language direction.
window.document.dir = FCKLang.Dir ;

FCKLang["DlgAsciiIncompatibleBrowser"] = FCKLang["DlgAsciiIncompatibleBrowser"] ? FCKLang["DlgAsciiIncompatibleBrowser"] : 'Your browser is not able to show mathematical formulas. Please, use %s1 or Internet Explorer with %s2 plugin.' ;
FCKLang['DlgAsciiIncompatibleBrowser'] = FCKLang['DlgAsciiIncompatibleBrowser'].replace( '%s1', '<a href="http://www.mozilla.com" onclick="javascript: window.open(this.href,\'_blank\');return false;">Mozilla Firefox 1.5+</a>, <a href="http://www.opera.com" onclick="javascript: window.open(this.href,\'_blank\');return false;">Opera 9.5+</a>' ) ;
FCKLang['DlgAsciiIncompatibleBrowser'] = FCKLang['DlgAsciiIncompatibleBrowser'].replace( '%s2', '<a href="http://www.dessci.com/en/products/mathplayer/" onclick="javascript: window.open(this.href,\'_blank\');return false;">MathPlayer</a>' ) ;

FCKLang["DlgAsciiMathOldIE"] = FCKLang["DlgAsciiMathOldIE"] ? FCKLang["DlgAsciiMathOldIE"] : 'Your browser is not able to show mathematical formulas. You need to upgrade to Internet Explorer 6.0+. Then you need to install the MathPlayer 2 plugin for Internet Explorer. Please, see %s for more information.' ;
FCKLang['DlgAsciiMathOldIE'] = FCKLang['DlgAsciiMathOldIE'].replace( '%s', '<a href="http://www.dessci.com/en/products/mathplayer/" onclick="javascript: window.open(this.href,\'_blank\');return false;">http://www.dessci.com/en/products/mathplayer/</a>' ) ;

FCKLang['DlgAsciiMathInstallMathPlayer'] = FCKLang['DlgAsciiMathInstallMathPlayer'] ? FCKLang['DlgAsciiMathInstallMathPlayer'] : 'Your browser is not able to show mathematical formulas. You need to install the MathPlayer 2 plugin for Internet Explorer. Please, see %s for more information.' ;
FCKLang['DlgAsciiMathInstallMathPlayer'] = FCKLang['DlgAsciiMathInstallMathPlayer'].replace( '%s', '<a href="http://www.dessci.com/en/products/mathplayer/" onclick="javascript: window.open(this.href,\'_blank\');return false;">http://www.dessci.com/en/products/mathplayer/</a>' ) ;

FCKLang['DlgAsciiMathOldMathPlayer'] = FCKLang['DlgAsciiMathOldMathPlayer'] ? FCKLang['DlgAsciiMathOldMathPlayer'] : 'Your browser is not able to show mathematical formulas. You need to upgrade the MathPlayer plugin for Internet Explorer to version 2. Please, see %s for more information.' ;
FCKLang['DlgAsciiMathOldMathPlayer'] = FCKLang['DlgAsciiMathOldMathPlayer'].replace( '%s', '<a href="http://www.dessci.com/en/products/mathplayer/" onclick="javascript: window.open(this.href,\'_blank\');return false;">http://www.dessci.com/en/products/mathplayer/</a>' ) ;

// Settings for ASCIIMathML.js
// Checking for native MathML support, it is always needed for this dialog.
var checkForMathML = true;
// Suppressing the built-in notification messages when the browser is incompatible.
var notifyIfNoMathML = false ;
var alertIfNoMathML = false ;
var notifyIfNoSVG = false ;
var alertIfNoSVG = false ;
// Formula translation will be called explicitly in this dialog after it loads.
var translateOnLoad = false ;
// Formula tooltips are hard-coded in this dialog, there is no need they to be generated.
var showasciiformulaonhover = false ;
// Font size of the formulas in this dialog.
var mathfontsize = "1.1em" ;

// oSpanAM: The actual selected span element in the editor that contains the formula.
var oSpanAM = FCKAsciiMath.FindFormulaContainer( FCKAsciiMath.GetSearchElementFromSelection() ) ;
if ( oSpanAM )
{
    FCK.Selection.SelectNode( oSpanAM ) ;
}

function LoadSelection()
{
    GetE( 'inputText' ).value = FCKAsciiMath.GetFormula( oSpanAM ) ;
    Preview() ;
}

function Ok()
{
    if ( FCKBrowserInfo.IsIE )
    {
        if ( oSpanAM )
        {
            FCK.Selection.SelectNode( oSpanAM ) ;
            // For IE: Before updating, we have to move the selection outside the formula
            // in order to prevent "Unspecified error".
            var span_target = FCK.EditorDocument.createElement( 'span' ) ;
            span_target.innerHTML = '&nbsp;' ;
            span_target = oSpanAM.parentNode.insertBefore( span_target, oSpanAM ) ;
            FCK.Selection.SelectNode( span_target ) ;
        }
    }

    var formula = GetE( 'inputText' ).value ;

    if ( formula != '' )
    {
        FCK.InsertHtml( '<span class="AM">`' + formula + '`<\/span>' ) ;
    }
    else
    {
        FCK.Selection.Delete() ;
    }

    if ( FCKBrowserInfo.IsIE )
    {
        if ( oSpanAM )
        {
            FCKUndo.SaveUndoStep() ;
            oSpanAM.parentNode.removeChild( oSpanAM ) ;
        }
    }

    return true ;
}

window.onload = function()
{
    // Translate the dialog box texts.
    oEditor.FCKLanguageManager.TranslatePage( document ) ;

    // Initialization of the script ASCIIMathML.js.
    init() ;

    // Load the selected element information (if any).
    LoadSelection() ;

    dialog.SetAutoSize( true ) ;

    // When MathML is available show the button "Show MathML code".
    if ( !noMathML ) {
        GetE( 'show_mathml' ).style.display = '' ;
    }

    // Activate the "OK" button.
    dialog.SetOkButton( true ) ;

    var inputField = GetE( 'inputText' ) ;
    inputField.focus() ;
}

function Set( string )
{
    var inputField = GetE( 'inputText' ) ;
    inputField.value += string ;
    Preview() ;
    inputField.focus() ;
    return false ;
}

function Clear()
{
    var inputField = GetE( 'inputText' ) ;
    inputField.value = '' ;
    Preview() ;
    inputField.focus() ;
    return false ;
}

function Delete()
{
    Clear();
    dialog.Ok();
}

function Preview()
{
    if ( GetE( 'inputText' ).value != '' )
    {
        var str = GetE( 'inputText' ).value ;
        var outnode = GetE( 'outputNode' ) ;
        var newnode = createElementXHTML( 'div' ) ;
        newnode.setAttribute( 'id', 'outputNode' ) ;
        outnode.parentNode.replaceChild( newnode, outnode ) ;
        outnode = GetE( 'outputNode' ) ;
        var n = outnode.childNodes.length ;
        for ( var i = 0; i < n; i++ )
        {
            outnode.removeChild( outnode.firstChild ) ;
        }
        outnode.appendChild( document.createComment( '`' + str + '`' ) ) ;
        AMprocessNode( outnode, true ) ;
        if ( FCKLang['DlgAsciiMathShowMathML'] )
        {
            GetE( 'show_mathml' ).value = FCKLang['DlgAsciiMathShowMathML'] ;
        }
    }
    else
    {
        var outnode = GetE( 'outputNode' ) ;
        var n = outnode.childNodes.length ;
        for ( var i = 0; i < n; i++ )
        {
            outnode.removeChild( outnode.firstChild ) ;
        }
    }
}

function AMnode2string( inNode, indent )
{
    // thanks to James Frazer for contributing an initial version of this function
    var i, str = '' ;
    if ( inNode.nodeType == 1 )
    {
        var name = inNode.nodeName.toLowerCase() ; // (IE fix)
        str = '\r' + indent + '<' + name ;
        for ( i = 0; i < inNode.attributes.length; i++ )
        {
            if ( inNode.attributes[i].nodeValue != 'italic' &&
                inNode.attributes[i].nodeValue != '' &&  //stop junk attributes
                inNode.attributes[i].nodeValue != 'inherit' && // (mostly IE)
                inNode.attributes[i].nodeValue != undefined &&
                inNode.attributes[i].nodeName[0] != '-' )
            {
                str += ' ' + inNode.attributes[i].nodeName + '=' + '"' + inNode.attributes[i].nodeValue + '"' ;
            }
        }
        if ( name == 'math' )
        {
            str += ' xmlns="http://www.w3.org/1998/Math/MathML"' ;
        }
        str += '>' ;
        for ( i = 0; i < inNode.childNodes.length; i++ )
        {
            str += AMnode2string( inNode.childNodes[i], indent + '  ' ) ;
        }
        if ( name != 'mo' && name != 'mi' && name != 'mn' ) str += '\r' + indent ;
        str += '</' + name + '>' ;
    }
    else if( inNode.nodeType == 3 )
    {
        var st = inNode.nodeValue ;
        for ( i = 0; i < st.length; i++ )
        {
            if ( st.charCodeAt( i ) < 32 || st.charCodeAt( i ) > 126 )
            {
                str += '&#' + st.charCodeAt( i ) + ';' ;
            }
            else if ( st.charAt(i) == '<' && indent != '  ' ) str += '&lt;' ;
            else if ( st.charAt(i) == '>' && indent != '  ' ) str += '&gt;' ;
            else if ( st.charAt(i) == '&' && indent != '  ' ) str += '&amp;' ;
            else str += st.charAt( i ) ;
        }
    }
    return str ;
}

function ShowMathML()
{
    if ( GetE( 'inputText' ).value != '' )
    {
        var math = GetE( 'outputNode' ).getElementsByTagName( 'math' )[0] ;
        if ( math )
        {
            var width ;
            if ( GetE( 'outputNode' ).offsetWidth )
            {
                width = GetE( 'outputNode' ).offsetWidth ;
            }

            math.parentNode.innerHTML = '<pre>' + FCKTools.HTMLEncode( AMnode2string( math, '' ) ) + '</pre>' ;

            if ( width && FCKBrowserInfo.IsGecko )
            {
                GetE( 'outputNode' ).style.width = width + 'px' ;
            }

            if ( FCKLang['DlgAsciiMathFormulaPreview'] )
            {
                GetE( 'show_mathml' ).value = FCKLang['DlgAsciiMathFormulaPreview'] ;
            }
        }
        else
        {
            Preview() ;
        }
    }
    else
    {
        Preview() ;
    }
}

// Highlighting formulas.

function over(td)
{
    td.className = 'LightBackground Hand' ;
}

function out(td)
{
    td.className = 'Hand' ;
}
