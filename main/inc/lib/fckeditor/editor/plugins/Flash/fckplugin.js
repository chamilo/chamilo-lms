// Register the related commands.
var dialogPath = FCKConfig.PluginsPath + 'Flash/fck_flash.html';
var flashDialogCmd = new FCKDialogCommand( FCKLang["DlgFlashTitle"], FCKLang["DlgFlashTitle"], dialogPath, 540, 450 );
FCKCommands.RegisterCommand( 'Flash', flashDialogCmd ) ;

// Create the Flash toolbar button.
var oFlashItem		= new FCKToolbarButton( 'Flash', FCKLang["DlgFlashTitle"]) ;
oFlashItem.IconPath	= FCKConfig.PluginsPath + 'Flash/button.flash.gif' ;

FCKToolbarItems.RegisterItem( 'Flash', oFlashItem ) ;
// 'Flash' is the name used in the Toolbar config.

