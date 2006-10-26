// Register the related commands.
//alert( oEditor.parentNode.tagName) ;

var dialogPath = FCKConfig.PluginsPath + 'Attachment/fck_Attachment.php';
var AttachmentDialogCmd = new FCKDialogCommand( FCKLang["DlgAttachTitle"], FCKLang["DlgAttachTitle"], dialogPath, 400, 300 );
FCKCommands.RegisterCommand( 'Attachment', AttachmentDialogCmd ) ;

// Create the Attachment toolbar button.
var oAttachmentItem		= new FCKToolbarButton( 'Attachment', FCKLang["DlgAttachTitle"]) ;
oAttachmentItem.IconPath	= FCKConfig.PluginsPath + 'Attachment/attachment.gif' ;

FCKToolbarItems.RegisterItem( 'Attachment', oAttachmentItem ) ;			

