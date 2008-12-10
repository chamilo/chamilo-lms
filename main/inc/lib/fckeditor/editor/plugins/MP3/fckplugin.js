// Register the related commands.
var dialogPath = FCKConfig.PluginsPath + 'MP3/fck_mp3.php';
var flashDialogCmd = new FCKDialogCommand( FCKLang["DlgMP3Title"], FCKLang["DlgMP3Title"], dialogPath, 550, 450);
FCKCommands.RegisterCommand( 'MP3', flashDialogCmd ) ;

// Create the Flash toolbar button.
var oFlashItem		= new FCKToolbarButton( 'MP3', FCKLang["DlgMP3Title"]) ;
oFlashItem.IconPath	= FCKConfig.PluginsPath + 'MP3/button.flash.gif' ;

FCKToolbarItems.RegisterItem( 'MP3', oFlashItem ) ;			
