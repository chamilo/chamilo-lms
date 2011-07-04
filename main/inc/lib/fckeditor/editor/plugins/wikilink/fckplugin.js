 /*
 * Author: Juan Carlos Raña Trabado
 * Plugin to insert "Wikilinks"
*/

// Register the related command.
FCKCommands.RegisterCommand( 'Wikilink', new FCKDialogCommand( 'Wikilink', FCKLang.WikilinkDlgTitle, FCKPlugins.Items['wikilink'].Path + 'fck_wikilink.html', 350, 250 ) ) ;


var oPlaceholderItem = new FCKToolbarButton( 'Wikilink', FCKLang.WikilinkBtn ) ;
oPlaceholderItem.IconPath = FCKPlugins.Items['wikilink'].Path + 'wikilink.gif' ;

FCKToolbarItems.RegisterItem( 'Wikilink', oPlaceholderItem ) ;

// Security RegExp
var REG_SCRIPT = new RegExp( "< *script.*>|< *style.*>|< *link.*>|< *body.*>", "i" ) ;
var REG_PROTOCOL = new RegExp( "javascript:|vbscript:|about:", "i" ) ;
var REG_CALL_SCRIPT = new RegExp( "&\{.*\};", "i" ) ;
var REG_EVENT = new RegExp( "onError|onUnload|onBlur|onFocus|onClick|onMouseOver|onMouseOut|onSubmit|onReset|onChange|onSelect|onAbort", "i" ) ;
var REG_AUTH = new RegExp( "document\.cookie|Microsoft\.XMLHTTP", "i" ) ;// Cookie Basic
var REG_NEWLINE = new RegExp( "\x0d|\x0a", "i" ) ;// TEXTAREA

// Placeholders object
var FCKPlaceholders = new Object() ;


FCKPlaceholders.Add = function( name )
{
	var oSpan = FCK.InsertElement( 'strong' ) ;
	this.SetupSpan( oSpan, name ) ;
}

FCKPlaceholders.SetupSpan = function( span, name )
{
	// Call check security
	if ( !checkCode(name) )
	{
		alert( 'Forbiden' ) ;
		return false;
	}
	
	span.innerHTML = '[[ ' + name + ' ]]' ;
}

// Check security
function checkCode( code )
{
	if ( code.search( REG_SCRIPT ) != -1 )
	{
		return false ;
	}

	if ( code.search( REG_PROTOCOL ) != -1 )
	{
		return false ;
	}

	if ( code.search( REG_CALL_SCRIPT ) != -1 )
	{
		return false ;
	}

	if ( code.search( REG_EVENT ) != -1 )
	{
		return false ;
	}

	if ( code.search( REG_AUTH ) != -1 )
	{
		return false ;
	}

	if ( code.search( REG_NEWLINE ) != -1 )
	{
		return false ;
	}

	return true ;
}