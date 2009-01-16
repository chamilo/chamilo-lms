FCKConfig.IMUploadPath = '';
FCKConfig.FlashUploadPath = '' ;
FCKConfig.AudioUploadPath = '' ;
FCKConfig.UserStatus = 'teacher' ;

FCKConfig.ToolbarSets["Question"] = [
	['Source','DocProps','-','NewPage','Preview','-'],
	['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
	['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
	['OrderedList','UnorderedList','-','Outdent','Indent'],
	['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
	['Link','Unlink','Anchor'],
	['Image','Flash','MP3','Table','Rule','Smiley','SpecialChar','UniversalKey'],
	['Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField'],
	'/',
	['Style','FontFormat','FontName','FontSize'],['Attachment']
] ;

FCKConfig.ToolbarSets["Middle"] = [
	['Source','FontSize','Bold','Italic','Underline','StrikeThrough','TextColor','-','OrderedList','UnorderedList','-','Rule','Link','Unlink','Table','-','Image','Flash']
] ;

FCKConfig.ToolbarSets["Agenda"] = [
	['FontSize','Bold','Italic','Underline','StrikeThrough','TextColor','-','OrderedList','UnorderedList','-','Rule','Link','Unlink','Table','-','Image']
] ;

FCKConfig.ToolbarSets["Small"] = [
	['Bold','Italic','Underline','StrikeThrough','Link','Unlink','Image','Flash','OrderedList','UnorderedList','Table']
] ;

FCKConfig.ToolbarSets["Profil"] = [
	['Bold','Italic','Underline','StrikeThrough','Link','Unlink','OrderedList','UnorderedList']
] ;

FCKConfig.ToolbarSets["Blog"] = [
	['Bold','Italic','Underline','StrikeThrough','Link','Unlink','Image','OrderedList','UnorderedList','Table']
] ;

FCKConfig.ToolbarSets["Announcements"] = [
	['Bold','Italic','Underline','StrikeThrough','Link','Unlink','Image','OrderedList','UnorderedList','Table']
] ;

FCKConfig.ToolbarSets["Full"] = [
	['FitWindow','PasteWord','Link','Unlink','Anchor','-','Image','flvPlayer','Flash','EmbedMovies','MP3','YouTube','Table','Rule','-','Subscript', 'Superscript','-','OrderedList','UnorderedList','Outdent','Indent','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],'/',['FontFormat','Style','FontName','FontSize','Bold','Italic','Underline','StrikeThrough','TextColor', 'BGColor','-','Source']
] ;

FCKConfig.ToolbarSets["Comment"] = [
	['Bold','Italic','Underline','StrikeThrough']
] ;

FCKConfig.ToolbarSets["ForumLight"] = [
	['Bold','Italic','Underline','StrikeThrough']
] ;

FCKConfig.ToolbarSets["NewTest"] = [
	['Source','Bold','Italic','Underline','StrikeThrough','Link','Unlink','Image','Flash','MP3','OrderedList','UnorderedList','Table']
] ;

FCKConfig.ToolbarSets["TestComment"] = [
	['Source','FontName','FontSize','TextColor','BGColor'],['Bold','Italic','Underline','StrikeThrough','Subscript', 'Superscript','Link','Unlink','Image','Flash','MP3','OrderedList','UnorderedList','Table']
] ;

FCKConfig.ToolbarSets["Test"] = [
	['Bold','Italic','Underline','StrikeThrough','Subscript','Superscript','Link','Unlink','Image','MP3','OrderedList','UnorderedList','Table']
] ;

FCKConfig.ToolbarSets["Survey"] = [
	['FontSize','Bold','Italic','TextColor','-','OrderedList','UnorderedList','-','Rule','Link','Unlink','Table','-','Image','Source']
] ;

FCKConfig.ToolbarSets["Wiki"] = [
	['NewPage','Templates','Save','Print','PageBreak','Preview','ShowBlocks','-','Cut','Copy','Paste','PasteText','PasteWord','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Find'],
	['Wikilink','Link','Unlink','Anchor','-','Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','MP3','Table','CreateDiv','Rule','Smiley','SpecialChar','googlemaps'],
	['FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor','Source'],
	['FitWindow']
	
] ;

var sOtherPluginPath = FCKConfig.BasePath.substr(0, FCKConfig.BasePath.length - 7) + 'editor/plugins/' ;

// Plugins

//Added by Ivan Tcholakov, 18-DEC-2008.
FCKConfig.Plugins.Add( "customizations" ) ;
FCKConfig.Plugins.Add( "dragresizetable" ) ;
FCKConfig.Plugins.Add( "tablecommands" ) ;

/*
FCKConfig.Plugins.Add("Video", "en", sOtherPluginPath ) ;
FCKConfig.Plugins.Add("Attachment", "en", sOtherPluginPath ) ;*/

// added by Julio Montoya
FCKConfig.Plugins.Add("MP3", "en", sOtherPluginPath ) ;
FCKConfig.Plugins.Add('ImageManager','en') ;
FCKConfig.Plugins.Add('flvPlayer','en') ;

//FCKConfig.Plugins.Add("Flash", "en");

FCKConfig.Plugins.Add('fckEmbedMovies');
FCKConfig.Plugins.Add("wikilink", "en,es", sOtherPluginPath ) ; // support to english, spanish
FCKConfig.Plugins.Add("imgmap", "en,es", sOtherPluginPath );

FCKConfig.Plugins.Add("googlemaps", "en,es", sOtherPluginPath ) ;
// This key is for http://localhost. You must get one for each server where you want to use the plugin,
// just get the key for free here after agreeing to the Terms of Use of the GoogleMaps API:
// http://www.google.com/apis/maps/signup.html. // If you leave an empty string then the toolbar icon won't be shown.
FCKConfig.GoogleMaps_Key = 'ABQIAAAAlXu5Pw6DFAUgqM2wQn01gxT2yXp_ZAY8_ufC3CFXhHIE1NvwkxSy5hTGQdsosYD3dz6faZHVrO-02A';

//Added by Ivan Tcholakov, 19-DEC-2008.
FCKConfig.Plugins.Add( 'youtube', 'en' ) ;

// reduce format list
FCKConfig.FontFormats	= 'p;h1;h2;h3;h4;h5' ;

/////////////////////////////////////////////////////////////////////// moved from fckconfig.js

FCKConfig.LinkBrowser = true ;

//
//FCKConfig.LinkBrowserURL = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Connector=' + encodeURIComponent( FCKConfig.BasePath + 'filemanager/connectors/' + _FileBrowserLanguage + '/connector.' + _FileBrowserExtension ) ;
//

FCKConfig.LinkBrowserWindowWidth	= FCKConfig.ScreenWidth * 0.7 ;		// 70%
FCKConfig.LinkBrowserWindowHeight	= FCKConfig.ScreenHeight * 0.7 ;	// 70%

FCKConfig.ImageBrowser = true ;

// this is set in the  main/inc/lib/formvalidator/Element/html_editor.php file very hard to find!!
//FCKConfig.ImageBrowserURL = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Type=Image&Connector=' + encodeURIComponent( FCKConfig.BasePath + 'filemanager/connectors/' + _FileBrowserLanguage + '/connector.' + _FileBrowserExtension ) ;

FCKConfig.ImageBrowserWindowWidth  = FCKConfig.ScreenWidth * 0.7 ;	// 70% ;
FCKConfig.ImageBrowserWindowHeight = FCKConfig.ScreenHeight * 0.7 ;	// 70% ;

// Flash Browsing
FCKConfig.FlashBrowser = true ;

// this is set in the  main/inc/lib/formvalidator/Element/html_editor.php file very hard to find!!
//FCKConfig.FlashBrowserURL = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Type=Flash&Connector=' + encodeURIComponent( FCKConfig.BasePath + 'filemanager/connectors/' + _FileBrowserLanguage + '/connector.' + _FileBrowserExtension ) ;

FCKConfig.FlashBrowserWindowWidth  = FCKConfig.ScreenWidth * 0.7 ;	//70% ;
FCKConfig.FlashBrowserWindowHeight = FCKConfig.ScreenHeight * 0.7 ;	//70% ;

FCKConfig.MediaBrowser = true ;
// this is set in the  main/inc/lib/formvalidator/Element/html_editor.php
//FCKConfig.MediaBrowserURL = FCKConfig.FlashBrowserURL;

FCKConfig.MediaBrowserWindowWidth = FCKConfig.ScreenWidth * 0.7 ;	//70% ;
FCKConfig.MediaBrowserWindowHeight= FCKConfig.ScreenHeight * 0.7 ;	//70% ;

FCKConfig.LinkUpload = true ;
FCKConfig.LinkUploadURL = FCKConfig.BasePath + 'filemanager/connectors/' + _QuickUploadLanguage + '/upload.' + _QuickUploadExtension ;
FCKConfig.LinkUploadAllowedExtensions	= ".(7z|aiff|asf|avi|bmp|csv|doc|fla|flv|gif|gz|gzip|jpeg|jpg|mid|mov|mp3|mp4|mpc|mpeg|mpg|ods|odt|pdf|png|ppt|pxd|qt|ram|rar|rm|rmi|rmvb|rtf|sdc|sitd|swf|sxc|sxw|tar|tgz|tif|tiff|txt|vsd|wav|wma|wmv|xls|xml|zip)$" ;			// empty for all
FCKConfig.LinkUploadDeniedExtensions	= "" ;	// empty for no one

FCKConfig.ImageUpload = true ;
FCKConfig.ImageUploadURL = FCKConfig.BasePath + 'filemanager/connectors/' + _QuickUploadLanguage + '/upload.' + _QuickUploadExtension + '?Type=Image' ;
FCKConfig.ImageUploadAllowedExtensions	= ".(jpg|gif|jpeg|png|bmp)$" ;		// empty for all
FCKConfig.ImageUploadDeniedExtensions	= "" ;							// empty for no one

// plugin added
/* 
FCKConfig.FlashUpload = true ;
FCKConfig.FlashUploadURL = FCKConfig.BasePath + 'filemanager/connectors/' + _QuickUploadLanguage + '/upload.' + _QuickUploadExtension + '?Type=Flash' ;
FCKConfig.FlashUploadAllowedExtensions	= ".(swf|flv)$" ;		// empty for all
FCKConfig.FlashUploadDeniedExtensions	= "" ;					// empty for no one
*/

// mp3 plugin 
FCKConfig.MP3Browser = true ;
// this is set in the  main/inc/lib/formvalidator/Element/html_editor.php 
//FCKConfig.MP3BrowserURL = FCKConfig.BasePath + 'filemanager/browser/default/browser.html?Type=MP3&Connector=connectors/' + _FileBrowserLanguage + '/connector.' + _FileBrowserExtension ;
FCKConfig.MP3BrowserWindowWidth  = FCKConfig.ScreenWidth * 0.7 ;	//70% ;
FCKConfig.MP3BrowserWindowHeight = FCKConfig.ScreenHeight * 0.7 ;	//70% ;

FCKConfig.MP3Upload = true ;
//FCKConfig.MP3UploadURL = FCKConfig.BasePath + 'filemanager/upload/' + FCKConfig.QuickUploadLanguage + '/upload.' + _QuickUploadLanguage + '?Type=MP3' ;
FCKConfig.MP3UploadAllowedExtensions	= ".(mp3)$" ;		// empty for all
FCKConfig.MP3UploadDeniedExtensions	= "" ;					// empty for no one
