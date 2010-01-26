// Register the command.
FCKCommands.RegisterCommand( 'MP3',
	new FCKDialogCommand( FCKLang["DlgMP3Title"], FCKLang["DlgMP3Title"],
	FCKConfig.PluginsPath + 'MP3/fck_mp3.php', 600, 530 )
) ;

// Create and register the MP3 toolbar button.
var oMP3Item = new FCKToolbarButton( 'MP3', FCKLang["DlgMP3Title"] ) ;
oMP3Item.IconPath	= FCKConfig.PluginsPath + 'MP3/mp3.gif' ;
FCKToolbarItems.RegisterItem( 'MP3', oMP3Item ) ;
