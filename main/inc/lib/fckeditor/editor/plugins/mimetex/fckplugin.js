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
        FCKConfig.PluginsPath + 'mimetex/mimetex.html', 750, 400));
// Create the "mimeTeX" toolbar button.
var oFindItem = new FCKToolbarButton('mimetex', FCKLang['DlgMimeTeX']);
oFindItem.IconPath = FCKConfig.PluginsPath + 'mimetex/mimetex.gif' ;
// 'mimetex' is the name used in the Toolbar config.
FCKToolbarItems.RegisterItem( 'mimetex', oFindItem ) ;
