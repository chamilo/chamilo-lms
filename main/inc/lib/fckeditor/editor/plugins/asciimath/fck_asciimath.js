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

var showasciiformulaonhover = false;

// Suppressing the built-in notification message when the browser is incompatible.
notifyIfNoMathML = false ;
//Small font is used in the dialog.
mathfontsize = "1.1em";

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

	// Load the selected element information (if any).
	LoadSelection() ;

	dialog.SetAutoSize( true ) ;

	// Activate the "OK" button.
	dialog.SetOkButton( true ) ;

	var inputField = GetE( 'inputText' ) ;
	inputField.focus() ;
}

function Set ( string )
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
		var newnode = AMcreateElementXHTML( 'div' ) ;
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

function CheckBrowserCompatibility( show_message )
{
	if ( FCKBrowserInfo.IsGecko )
	{
		// The browser is compatible, it is genuine Gecko - Firefox, etc.
		return true ;
	}
	else if ( FCKBrowserInfo.IsIE )
	{
		// Internet Explorer.
		if ( FCKBrowserInfo.IsIE6 )
		{
			if ( IsMathPlayerInstalled() )
			{
				var start = navigator.appVersion.indexOf( 'MathPlayer' ) ;
				if ( start != -1 )
				{
					// The browser is Internet Explorer 6.0+ with properly set up plugin MathPalyer 2.
					return true ;
				}
				else
				{
					// Notify reader they need to upgrade to MathPlayer 2.
					if ( show_message )
					{
						document.write( '<span style="color:red;">' + FCKLang['DlgAsciiMathOldMathPlayer'] + '</span>' ) ;
					}
					return false ;
				}
			}
			else
			{
				// Direct reader to MathPlayer page.
				if ( show_message )
				{
					document.write( '<span style="color:red;">' + FCKLang['DlgAsciiMathInstallMathPlayer'] + '</span>' ) ;
				}
				return false ;
			}
		}
		else
		{
			// The browser is a very old version of Internet Explorer, it have to be upgraded.
			if ( show_message )
			{
				document.write( '<span style="color:red;">' + FCKLang['DlgAsciiMathOldIE'] + '</span>' ) ;
			}
			return false ;
		}
	}
	else if ( FCKBrowserInfo.IsOpera && parseFloat( navigator.appVersion, 10 ) >= 9.5 )
	{
		return true ;
	}

	// The browser is not compatible.
	if ( show_message )
	{
		document.write( '<span style="color:red;">' + FCKLang['DlgAsciiIncompatibleBrowser'] + '</span>' ) ;
	}
	return false ;
}

// Returns true if MathPlayer is installed.
function IsMathPlayerInstalled()
{
	try
	{
		var oMP = new ActiveXObject( 'MathPlayer.Factory.1' ) ;
		return true ;
	}
	catch(e)
	{
		return false ;
	}
}
