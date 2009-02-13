// Register the command.
FCKCommands.RegisterCommand( 'flvPlayer',
	new FCKDialogCommand( FCKLang['DlgFLVPlayerTitle'], FCKLang['DlgFLVPlayerTitle'],
	FCKConfig.PluginsPath + 'flvPlayer/flvPlayer.html', 800, 570 )
) ;

// Create and register the toolbar button.
var oFlvPlayerItem		= new FCKToolbarButton( 'flvPlayer', FCKLang['DlgFLVPlayerTitle']) ;
oFlvPlayerItem.IconPath	= FCKPlugins.Items['flvPlayer'].Path + 'flvPlayer.gif' ;
FCKToolbarItems.RegisterItem( 'flvPlayer', oFlvPlayerItem ) ;			
