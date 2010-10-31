 /*
 * Author: Juan Carlos Raña Trabado
 * Plugin to insert "Wikilinks"
*/

// Register the related command.
FCKCommands.RegisterCommand( 'Wikilink', new FCKDialogCommand( 'Wikilink', FCKLang.WikilinkDlgTitle, FCKPlugins.Items['wikilink'].Path + 'fck_wikilink.html', 350, 250 ) ) ;


var oPlaceholderItem = new FCKToolbarButton( 'Wikilink', FCKLang.WikilinkBtn ) ;
oPlaceholderItem.IconPath = FCKPlugins.Items['wikilink'].Path + 'wikilink.gif' ;

FCKToolbarItems.RegisterItem( 'Wikilink', oPlaceholderItem ) ;

var FCKPlaceholders = new Object() ;


FCKPlaceholders.Add = function( name )
{
	var oSpan = FCK.InsertElement( 'strong' ) ;
	this.SetupSpan( oSpan, name ) ;
}

FCKPlaceholders.SetupSpan = function( span, name )
{
	span.innerHTML = '[[ ' + name + ' ]]' ;
}