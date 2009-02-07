/*
 *	Dokeos - elearning and course management software
 *
 *	Copyright (c) 2008-2009 Dokeos SPRL
 *
 *	For a full list of contributors, see "credits.txt".
 *	The full license can be read in "license.txt".
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License
 *	as published by the Free Software Foundation; either version 2
 *	of the License, or (at your option) any later version.
 *
 *	See the GNU General Public License for more details.
 *
 * Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
 * Mail: info@dokeos.com
 */

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
	['FontSize','Bold','Italic','Underline','StrikeThrough','TextColor','-','OrderedList','UnorderedList','-','Rule','Link','Unlink','Table','-','Image','Flash','Source']
] ;

FCKConfig.ToolbarSets["Agenda"] = [
	['FontSize','Bold','Italic','Underline','StrikeThrough','TextColor','-','OrderedList','UnorderedList','-','Rule','Link','Unlink','Table','-','Image']
] ;

FCKConfig.ToolbarSets["Small"] = [
	['Bold','Italic','Underline','StrikeThrough','Link','Unlink','Image','Flash','OrderedList','UnorderedList','Table']
] ;

FCKConfig.ToolbarSets["Profil"] = [
	['FitWindow','PasteWord','Undo','Redo'],
	['Link','Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','Table','googlemaps'],
	['Bold','Italic','Underline','OrderedList','UnorderedList','TextColor','-','Source']
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
	['Bold','Italic','Underline','StrikeThrough','Link','Unlink','Image','Flash','MP3','OrderedList','UnorderedList','Table','Source']
] ;

FCKConfig.ToolbarSets["TestComment"] = [
	['FontName','FontSize','TextColor','BGColor'],['Bold','Italic','Underline','StrikeThrough','Subscript', 'Superscript','Link','Unlink','Image','Flash','MP3','OrderedList','UnorderedList','Table','Source']
] ;

FCKConfig.ToolbarSets["Test"] = [
	['Bold','Italic','Underline','StrikeThrough','Subscript','Superscript','Link','Unlink','Image','MP3','OrderedList','UnorderedList','Table']
] ;

FCKConfig.ToolbarSets["Survey"] = [
	['FontSize','Bold','Italic','TextColor','-','OrderedList','UnorderedList','-','Rule','Link','Unlink','Table','-','Image','Source']
] ;

FCKConfig.ToolbarSets["Introduction"] = [
	['NewPage','Templates','Preview','FitWindow','-','PasteText','PasteWord','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Find'],
	['Link','Unlink','Anchor'],
	['Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','CreateDiv','Rule','Smiley','SpecialChar','googlemaps'],
	['FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['Source']
] ;

FCKConfig.ToolbarSets["CourseDescription"] = [
	['NewPage','Templates','Save','PageBreak','Preview','FitWindow','-','PasteText','PasteWord','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Find'],
	['Link','Unlink','Anchor'],
	['Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','CreateDiv','Rule','Smiley','SpecialChar','googlemaps'],
	['FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['Source']
] ;

FCKConfig.ToolbarSets["Wiki"] = [
	['NewPage','Templates','Save','PageBreak','Preview','FitWindow','-','PasteText','PasteWord','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Find'],
	['Wikilink','Link','Unlink','Anchor'],
	['Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','CreateDiv','Rule','Smiley','SpecialChar','googlemaps'],
	['FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['Source']
] ;

// At this moment of loading editor's scripts the setting FCKConfig.AdvancedFileManager
// has not been read yet from the php-integration file. 
// However we are able to detect which file manager will be used in another way.
// The following variable is pure boolean type: true/false.
var UseAdvancedFileManager = FCKConfig.PageConfig.AdvancedFileManager ;

var sOtherPluginPath = FCKConfig.BasePath.substr(0, FCKConfig.BasePath.length - 7) + 'editor/plugins/' ;

// Plugins

//Added by Ivan Tcholakov, 18-DEC-2008.
FCKConfig.Plugins.Add('customizations', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;
FCKConfig.Plugins.Add('dragresizetable') ;
FCKConfig.Plugins.Add('tablecommands') ;

/*
FCKConfig.Plugins.Add("Video", "en", sOtherPluginPath ) ;
FCKConfig.Plugins.Add("Attachment", "en", sOtherPluginPath ) ;*/

// added by Julio Montoya
FCKConfig.Plugins.Add("MP3", "en", sOtherPluginPath ) ;
//FCKConfig.Plugins.Add('audio', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;
FCKConfig.Plugins.Add('ImageManager','en') ;
FCKConfig.Plugins.Add('flvPlayer','en') ;
//FCKConfig.Plugins.Add("Flash", "en");
FCKConfig.Plugins.Add('fckEmbedMovies');

// added by Juan Carlos Ra�a
FCKConfig.Plugins.Add("mimetex", "en", sOtherPluginPath ) ;
FCKConfig.Plugins.Add("wikilink", "en,es", sOtherPluginPath ) ;
FCKConfig.Plugins.Add("imgmap", "en,es", sOtherPluginPath );
FCKConfig.Plugins.Add("googlemaps", "en,es", sOtherPluginPath ) ;
// This key is for http://localhost. You must get one for each server where you want to use the plugin,
// just get the key for free here after agreeing to the Terms of Use of the GoogleMaps API:
// http://www.google.com/apis/maps/signup.html. // If you leave an empty string then the toolbar icon won't be shown.
FCKConfig.GoogleMaps_Key = 'ABQIAAAAlXu5Pw6DFAUgqM2wQn01gxT2yXp_ZAY8_ufC3CFXhHIE1NvwkxSy5hTGQdsosYD3dz6faZHVrO-02A';

//Added by Ivan Tcholakov, 19-DEC-2008.
FCKConfig.Plugins.Add('youtube', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// reduce format list
FCKConfig.FontFormats	= 'p;h1;h2;h3;h4;h5' ;

/////////////////////////////////////////////////////////////////////// moved from fckconfig.js

FCKConfig.LinkBrowser = true ;
FCKConfig.LinkBrowserWindowWidth	= 782;
FCKConfig.LinkBrowserWindowHeight	= 490;

FCKConfig.ImageBrowser = true ;
FCKConfig.ImageBrowserWindowWidth  = 782;
FCKConfig.ImageBrowserWindowHeight = 490;

FCKConfig.FlashBrowser = true ;
FCKConfig.FlashBrowserWindowWidth  = 782;
FCKConfig.FlashBrowserWindowHeight = 490;

FCKConfig.MediaBrowser = true ;
FCKConfig.MediaBrowserWindowWidth = 782;
FCKConfig.MediaBrowserWindowHeight= 490;

FCKConfig.MP3Browser = true ;
FCKConfig.MP3BrowserWindowWidth  = 782;
FCKConfig.MP3BrowserWindowHeight = 490;

FCKConfig.LinkUpload = true ;
FCKConfig.LinkUploadURL = FCKConfig.BasePath + 'filemanager/connectors/' + _QuickUploadLanguage + '/upload.' + _QuickUploadExtension ;
FCKConfig.LinkUploadAllowedExtensions	= ".(7z|aiff|asf|avi|bmp|csv|doc|fla|flv|gif|gz|gzip|jpeg|jpg|mid|mov|mp3|mp4|mpc|mpeg|mpg|ods|odt|pdf|png|ppt|pxd|qt|ram|rar|rm|rmi|rmvb|rtf|sdc|sitd|swf|sxc|sxw|tar|tgz|tif|tiff|txt|vsd|wav|wma|wmv|xls|xml|zip)$" ;			// empty for all
FCKConfig.LinkUploadDeniedExtensions	= "" ;	// empty for no one

FCKConfig.ImageUpload = true ;
FCKConfig.ImageUploadURL = FCKConfig.BasePath + 'filemanager/connectors/' + _QuickUploadLanguage + '/upload.' + _QuickUploadExtension + '?Type=Image' ;
FCKConfig.ImageUploadAllowedExtensions	= ".(jpg|gif|jpeg|png|bmp)$" ;		// empty for all
FCKConfig.ImageUploadDeniedExtensions	= "" ;							// empty for no one

FCKConfig.FlashUpload = true ;

FCKConfig.MP3Upload = true ;
FCKConfig.MP3UploadAllowedExtensions	= ".(mp3)$" ;		// empty for all
FCKConfig.MP3UploadDeniedExtensions	= "" ;					// empty for no one
