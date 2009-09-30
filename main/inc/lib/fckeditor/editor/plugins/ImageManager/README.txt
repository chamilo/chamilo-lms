Below are the steps required to setup the Wei Zhuo's Image Manager (http://www.zhuo.org/htmlarea/)
as a plugin for FCKeditor.

-----------------------------
SETTING UP THE IMAGE MANAGER:
-----------------------------

- Firstly extract the archive to the editor/plugins directory
- Edit the file 'editor/plugins/ImageManager/config.inc.php' to configure the plugin.

---------------------------
INSTALLING AS A FCK PLUGIN:
---------------------------

The required fckplugin.js file is already in the archive, so all you need to do is update fckconfig.js (in the root directory) and make the following updates:

- The language for the plugin and to directEdit option must be set in its 'editor/plugins/ImageManager/fckplugin.js' file.
-  Add the plugin to your FCKeditor by opening you 'fckconfig.js' file and update the 'FCKConfig.ToolbarSets' you're using by replacing 'Image' with 'ImageManager';
   Register the plugin with the following statement: "FCKConfig.Plugins.Add('ImageManager');"
- Done. (Should the toolbar icon not appear, try to clear your browser's cache)


--------------------------------------------------------------
UPDATING THE CONTEXT MENU FOR IMAGES TO USE THE IMAGE MANAGER:
--------------------------------------------------------------

Copy an icon for the image manager for the popup context menu
cp /editor/skins/default/toolbar/image.gif /editor/skins/default/toolbar/imagemanager.gif

edit editor/_source/internals/fckcontextmenu.js
	  - search for 'FCKLang.ImageProperties'
	  - replace the string in the parameter before the above mentioned one from 'Image' to 'ImageManager'.
			the line should now read: 	return new FCKContextMenuGroup( true, this, 'ImageManager', FCKLang.ImageProperties, true ) ;

This updates the _source context menu to use the image manager. However to avoid having to recompile the whole thing, also need to update the compiled versions too so also repeat the above on the following files:
	 
	 - editor/js/fckeditorcode_gecko_2.js
	 - editor/js/fckeditorcode_ie_2.js


Enjoy!
Brent Kelly
Zeald.com
http://www.zeald.com

Paul Moers
http://www.saulmade.nl