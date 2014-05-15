/*
FCKCommands.RegisterCommand(commandName, command)
       commandName - Command name, referenced by the Toolbar, etc...
       command - Command object (must provide an Execute() function).
*/
// Register the related commands.
FCKCommands.RegisterCommand(
   'mimetex',
    new FCKDialogCommand(
        FCKLang['DlgMimeTeX'],
        FCKLang['DlgMimeTeX'],
        FCKConfig.PluginsPath + 'mimetex/mimetex.html', 800, 550));
// Create the "mimeTeX" toolbar button.
var oMimeTeXItem = new FCKToolbarButton('mimetex', FCKLang['DlgMimeTeX']);
oMimeTeXItem.IconPath = FCKConfig.PluginsPath + 'mimetex/mimetex.gif' ;
// 'mimetex' is the name used in the Toolbar config.
FCKToolbarItems.RegisterItem( 'mimetex', oMimeTeXItem ) ;

// Context menu support.
FCK.ContextMenu.RegisterListener( {
	AddItems : function( menu, tag, tagName )
	{
		if ( tagName == 'IMG' && tag.getAttribute( 'src' ) && tag.getAttribute( 'src' ).toString().indexOf( '/cgi-bin/mimetex' ) >= 0 )
		{
			menu.AddSeparator() ;
			menu.AddItem( 'mimetex', FCKLang['DlgMimeTeX'], oMimeTeXItem.IconPath ) ;
		}
	}}
);

// Double-click support.
FCK.RegisterDoubleClickHandler(
	function ( tag )
	{
		if ( tag.nodeName.IEquals( 'img' ) && tag.getAttribute( 'src' ) && tag.getAttribute( 'src' ).toString().indexOf( '/cgi-bin/mimetex' ) >= 0 )
		{
			FCKCommands.GetCommand( 'mimetex' ).Execute() ;
		}
	}, 'IMG'
) ;

