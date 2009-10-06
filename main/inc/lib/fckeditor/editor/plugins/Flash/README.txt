This plugin was improved by Andrey Grebnev

I've taken Hernux's Flash patch [see 1] and turned it into a plugin for FCKeditor-2.0FC to make installing it easier.

Installation Instructions
1. Unzip the Flash plugin zip and paste it into "editor/plugins/"
2. In fckconfig.js make the following additions;
3. add the following line after where "FCKConfig.PluginsPath" is defined
 FCKConfig.Plugins.Add("Flash", "en,ru");
4. Add 'Flash' to your toolsbarset in fckconfig.js. E.G.,

  FCKConfig.ToolbarSets["Default"] = [
    ['Flash', 'Bold', 'Italic']
  ] ;

5. Add the Flash file browser config like so,

  // Flash Browsing
  FCKConfig.FlashBrowser = true ;
  FCKConfig.FlashBrowserURL = FCKConfig.BasePath + "filemanager/browser/default/browser.html?Type=Flash&Connector=connectors/jsp/connector" ;
  FCKConfig.FlashBrowserWindowWidth  = screen.width * 0.7 ;	//70% ;
  FCKConfig.FlashBrowserWindowHeight = screen.height * 0.7 ;	//70% ;

The FlashBrowserURL line will be slightly different depending on how you've configured your connector. Generally it's the same as your ImageBrowserURL except that the 'type=Image' should be 'type=Flash'

Please let me know if you experience any issues.


References
1 - Hernux Flash Patch [http://sourceforge.net/tracker/index.php?func=detail&aid=1051555&group_id=75348&atid=543655]
2 - Installing the FindReplace Plugin [https://sourceforge.net/forum/message.php?msg_id=2943394]