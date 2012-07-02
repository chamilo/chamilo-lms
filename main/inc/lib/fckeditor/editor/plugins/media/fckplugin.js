// Register the command.
FCKCommands.RegisterCommand( 'Media',
    new FCKDialogCommand( FCKLang['DlgMediaTitle'], FCKLang['DlgMediaTitle'],
        FCKConfig.PluginsPath + 'media/media.html', 600, 440 )) ;

// Create the "YouTube" toolbar button.
var oMediaItem = new FCKToolbarButton( 'Media', FCKLang['MediaTip'] ) ;
oMediaItem.IconPath	= FCKConfig.PluginsPath + 'media/media.png' ;
FCKToolbarItems.RegisterItem( 'media', oMediaItem ) ;
