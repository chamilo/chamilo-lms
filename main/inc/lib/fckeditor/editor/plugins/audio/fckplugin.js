// Register the command.
FCKCommands.RegisterCommand( 'MP3',
	new FCKDialogCommand( FCKLang['DlgAudioTitle'], FCKLang['DlgAudioTitle'] + ' (mp3)',
	FCKConfig.PluginsPath + 'audio/fck_audio.html', 600, 310 )
) ;

// Create and register the Audio toolbar button.
var oAudioItem = new FCKToolbarButton( 'MP3', FCKLang['DlgAudioTitle'] + ' (mp3)' ) ;
oAudioItem.IconPath	= FCKConfig.PluginsPath + 'audio/audio.gif' ;
FCKToolbarItems.RegisterItem( 'MP3', oAudioItem ) ;
