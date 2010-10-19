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
var translateOnLoad = false ;
// Formula tooltips are hard-coded in this dialog, there is no need they to be generated.
var showasciiformulaonhover = false ;
// Font size of the formulas in this dialog.
var mathfontsize = "1.1em" ;


function LoadSelection()
{
    // ...

    Preview() ;
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
    init() ;

    // Load the selected element information (if any).
    //LoadSelection() ;

    dialog.SetAutoSize( true ) ;

    // Activate the "OK" button.
    dialog.SetOkButton( true ) ;

}

