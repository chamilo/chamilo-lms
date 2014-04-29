// Register the command.
FCKCommands.RegisterCommand( 'YouTube',
	new FCKDialogCommand( FCKLang['DlgYouTubeTitle'], FCKLang['DlgYouTubeTitle'],
	FCKConfig.PluginsPath + 'youtube/youtube.html', 600, 440 ) ) ;

// Create the "YouTube" toolbar button.
var oYouTubeItem = new FCKToolbarButton( 'YouTube', FCKLang['YouTubeTip'] ) ;
oYouTubeItem.IconPath	= FCKConfig.PluginsPath + 'youtube/youtube.gif' ;
FCKToolbarItems.RegisterItem( 'YouTube', oYouTubeItem ) ;
