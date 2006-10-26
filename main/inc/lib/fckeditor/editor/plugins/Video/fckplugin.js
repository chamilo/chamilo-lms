// Register the related commands.
var dialogPath = FCKConfig.PluginsPath + 'Video/fck_video.php';
var videoDialogCmd = new FCKDialogCommand( FCKLang["DlgVideoTitle"], FCKLang["DlgVideoTitle"], dialogPath, 450,300);
FCKCommands.RegisterCommand( 'Video', videoDialogCmd ) ;

// Create the Flash toolbar button.
var oVideoItem		= new FCKToolbarButton( 'Video', FCKLang["DlgVideoTitle"]) ;
oVideoItem.IconPath	= FCKConfig.PluginsPath + 'Video/videos.gif' ;

FCKToolbarItems.RegisterItem( 'Video', oVideoItem ) ;