// Register the related commands.
var dialogPath = FCKConfig.PluginsPath + 'audio/fck_audio.html' ;
var flashDialogCmd = new FCKDialogCommand( FCKLang["DlgAudioTitle"], FCKLang["DlgAudioTitle"], dialogPath, 600, 300 ) ;
FCKCommands.RegisterCommand( 'MP3', flashDialogCmd ) ;

// Create the Audio toolbar button.
var oFlashItem		= new FCKToolbarButton( 'MP3', FCKLang["DlgAudioTitle"]) ;
oFlashItem.IconPath	= FCKConfig.PluginsPath + 'audio/audio.gif' ;

FCKToolbarItems.RegisterItem( 'MP3', oFlashItem ) ;
