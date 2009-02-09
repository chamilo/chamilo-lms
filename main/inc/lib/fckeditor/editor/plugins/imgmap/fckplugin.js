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


/*
// Removed by Ivan Tcholakov, 18-DEC-2008.

// The code has been added in FCKeditor 2.5, so we only need it here for previous versions.
if ( !FCKRegexLib.ProtectUrlsArea )
{
	if ( FCKBrowserInfo.IsIE )
	{
		// Fix behavior for IE, it doesn't read back the .name on newly created maps 
		FCKXHtml.TagProcessors['map'] = function( node, htmlNode )
		{
			if ( ! node.attributes.getNamedItem( 'name' ) )
			{
				var name = htmlNode.name ;
				if ( name )
					FCKXHtml._AppendAttribute( node, 'name', name ) ;
			}

			node = FCKXHtml._AppendChildNodes( node, htmlNode, true ) ;

			return node ;
		}
	}

	// The href in the areas might get distorted by the browser.

	// Keep a reference to the default processsor:
	var imgmap_OldAreaProcessor = FCKXHtml.TagProcessors['area'] ;

	FCKXHtml.TagProcessors['area'] = function( node, htmlNode )
	{
		var sSavedUrl = htmlNode.getAttribute( '_fcksavedurl' ) ;
		if ( sSavedUrl != null )
			FCKXHtml._AppendAttribute( node, 'href', sSavedUrl ) ;

		// Call the default processor
		if (typeof imgmap_OldAreaProcessor == 'function') 
			node = imgmap_OldAreaProcessor ( node, htmlNode ) ;

		return node ;
	}

	// Saves URLs on links and images on special attributes, so they don't change when 
	// moving around.
	var imgmap_OldProtectUrls = FCK.ProtectUrls ;
	FCK.ProtectUrls  = function( html )
	{
		html = imgmap_OldProtectUrls( html ) ;

		// <AREA> href
		html = html.replace( /<area(?=\s).*?\shref=((?:(?:\s*)("|').*?\2)|(?:[^"'][^ >]+))/gi	, '$& _fcksavedurl=$1' ) ;

		return html ;
	}
}
*/
