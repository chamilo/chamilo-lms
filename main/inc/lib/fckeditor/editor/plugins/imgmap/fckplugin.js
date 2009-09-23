/*
	ImgMap plugin for FCKeditor
	version 0.4 14/12/2007

	See docs/install.html


*/

imgmapCommand_GetState = function() {
	if ( FCK.EditMode != FCK_EDITMODE_WYSIWYG )
		return FCK_TRISTATE_DISABLED;

	var oImage = FCK.Selection.GetSelectedElement() ;
	if ( oImage && oImage.tagName == 'IMG'  )
	{
		if ( !FCK.IsRealImage( oImage ) )
		{
			return FCK_TRISTATE_DISABLED ;
		}

		// Does it has an assigned map?
		if (oImage.getAttribute( 'usemap' ))
			return FCK_TRISTATE_ON;

		// Plain image
		return FCK_TRISTATE_OFF;
	}
	// No image selected
	return FCK_TRISTATE_DISABLED;
}


/*
FCKCommands.RegisterCommand( 'imgmapPopup',
	new FCKDialogCommand( FCKLang.imgmapDlgName, FCKLang.imgmapDlgTitle, FCKPlugins.Items['imgmap'].Path + 'popup.html', 700, 620, imgmapCommand_GetState ) ) ;
*/
FCKCommands.RegisterCommand( 'imgmapPopup',
		new FCKDialogCommand( FCKLang.imgmapDlgName, FCKLang.imgmapDlgTitle, FCKPlugins.Items['imgmap'].Path + 'popup.html', 750, 580, imgmapCommand_GetState ) ) ;


// create imgmap toolbar button.
var imgmapButton = new FCKToolbarButton('imgmapPopup', FCKLang.imgmapBtn, null, null, false, true);
// Use the proper icon according to the skin:
if ( /\/editor\/skins\/(.*)\//.test(FCKConfig.SkinPath) )
	imgmapButton.IconPath = FCKPlugins.Items['imgmap'].Path + 'images/icon_' + RegExp.$1 + '.gif';
else
	imgmapButton.IconPath = FCKPlugins.Items['imgmap'].Path + 'images/editor_icon.gif';

FCKToolbarItems.RegisterItem('imgmapPopup', imgmapButton);

// register new contextmenu
FCK.ContextMenu.RegisterListener({
	AddItems : function( menu, tag, tagName ) {
		// under what circumstances do we display this option
		if ( FCK.IsRealImage( tag ) )
		{
			// when the option is displayed, show a separator  the command
			//menu.AddSeparator();
			// the command needs the registered command name, the title for the context menu, and the icon path
			menu.AddItem('imgmapPopup', FCKLang.imgmapDlgTitle, imgmapButton.IconPath);
		}
	}
});
