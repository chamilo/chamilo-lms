/*
 *	Dokeos - elearning and course management software
 *
 *	Copyright (c) 2008-2009 Dokeos SPRL
 *	Copyright (c) 2008-2009 Julio Montoya
 *	Copyright (c) 2008-2009 Juan Carlos Raña
 *	Copyright (c) 2008-2009 Ivan Tcholakov
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


/*
 * Editor's toolbar definitions.
 */

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

FCKConfig.ToolbarSets["Small"] = [
	['Bold','Italic','Underline','StrikeThrough','Link','Unlink','Image','Flash','OrderedList','UnorderedList','Table']
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

FCKConfig.ToolbarSets["Profil"] = [
	['FitWindow','PasteWord','Undo','Redo'],
	['Link','Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','Table','googlemaps'],
	['Bold','Italic','Underline','OrderedList','UnorderedList','TextColor','-','Source']
] ;

FCKConfig.ToolbarSets["Messages"] = [
	['FitWindow','PasteWord','Undo','Redo'],
	['Link','Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','Table','googlemaps'],
	['Bold','Italic','Underline','OrderedList','UnorderedList','TextColor','-','Source']
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

FCKConfig.ToolbarSets["Agenda"] = [
	['FitWindow','-','PasteWord','-','Undo','Redo','RemoveFormat'],
	['Link','Unlink'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Rule','SpecialChar','googlemaps'],
	['FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['OrderedList','UnorderedList','-','TextColor','BGColor'],
	['Source']
] ;

FCKConfig.ToolbarSets["Agenda_Student"] = [
	['FitWindow','-','PasteWord','-','Undo','Redo','RemoveFormat'],
	['Link','Unlink'],
	['Image','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Rule','SpecialChar','googlemaps'],
	['FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['OrderedList','UnorderedList','-','TextColor','BGColor'],
	['ShowBlocks']
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

FCKConfig.ToolbarSets["Documents"] = [
	['NewPage','Templates','Save','PageBreak','Preview','FitWindow','-','PasteText','PasteWord','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Find'],
	['Link','Unlink','Anchor'],
	['Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','CreateDiv','Rule','Smiley','SpecialChar','googlemaps'],
	['FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['Source']
] ;

FCKConfig.ToolbarSets["Documents_Student"] = [
	['NewPage','Save','PageBreak','Preview','FitWindow','-','PasteText','PasteWord','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Find'],
	['Link','Unlink','Anchor'],
	['Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','CreateDiv','Rule','Smiley','SpecialChar','googlemaps'],
	['FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['ShowBlocks']
] ;

FCKConfig.ToolbarSets["ForumLight"] = [
	['Bold','Italic','Underline','StrikeThrough']
] ;

FCKConfig.ToolbarSets["Forum"] = [
	['NewPage','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Find'],
	['Link','Unlink','Anchor'],
	['Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Rule','Smiley','SpecialChar','googlemaps'],
	['FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['Subscript','Superscript','-','OrderedList','UnorderedList','-','Outdent','Indent','Blockquote','-','TextColor','BGColor'],
	['Source']
] ;

FCKConfig.ToolbarSets["Forum_Student"] = [
	['NewPage','FitWindow','-','PasteWord','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Find'],
	['Link','Unlink','Anchor'],
	['Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Rule','Smiley','SpecialChar','googlemaps'],
	['FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['Subscript','Superscript','-','OrderedList','UnorderedList','-','Outdent','Indent','Blockquote','-','TextColor','BGColor'],
	['ShowBlocks']
] ;

FCKConfig.ToolbarSets["Glossary"] = [
	['NewPage','Save','FitWindow','-','PasteText','PasteWord','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Find'],
	['Link','Unlink','Anchor'],
	['Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Rule','SpecialChar','googlemaps'],
	['FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['Subscript','Superscript','-','JustifyLeft','JustifyFull','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['Source']
] ;

FCKConfig.ToolbarSets["Notebook"] = [
	['NewPage','Save','FitWindow','-','PasteText','PasteWord','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Find'],
	['Link','Unlink','Anchor'],
	['Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Rule','SpecialChar','googlemaps'],
	['FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['Subscript','Superscript','-','JustifyLeft','JustifyFull','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['Source']
] ;

FCKConfig.ToolbarSets["Notebook_Student"] = [
	['NewPage','Save','FitWindow','-','PasteText','PasteWord','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Find'],
	['Link','Unlink','Anchor'],
	['Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','Rule','SpecialChar','googlemaps'],
	['FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['Subscript','Superscript','-','JustifyLeft','JustifyFull','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['ShowBlocks']
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

FCKConfig.ToolbarSets["Wiki_Student"] = [
	['NewPage','Save','PageBreak','Preview','FitWindow','-','PasteText','PasteWord','-','Undo','Redo','-','SelectAll','RemoveFormat','-','Find'],
	['Wikilink','Link','Unlink','Anchor'],
	['Image','imgmapPopup','flvPlayer','Flash','EmbedMovies','YouTube','MP3'],
	['Table','CreateDiv','Rule','Smiley','SpecialChar','googlemaps'],
	['FontFormat','FontName','FontSize'],
	['Bold','Italic','Underline','StrikeThrough'],
	['Subscript','Superscript','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor'],
	['ShowBlocks']
] ;


/*
 * Toolbar drop-down lists customizations.
 */

// Reduction of the format list.
FCKConfig.FontFormats	= 'p;h1;h2;h3;h4;h5' ;


/*
 * Helper variables.
 */

// At this moment of loading editor's javascripts, the setting FCKConfig.AdvancedFileManager
// has not been read yet from the php-integration file. We are able to detect which file manager
// will be used in another way. The following property has pure boolean type: true/false.
FCK.AdvancedFileManager = FCKConfig.PageConfig.AdvancedFileManager ;

var sOtherPluginPath = FCKConfig.BasePath.substr(0, FCKConfig.BasePath.length - 7) + 'editor/plugins/' ;


/*
 * Plugins.
 * If you want to add a non-existing language code in some of the pligin
 * declarations, it is mandatory a corresponding language file to be opened
 * in the plugin's "lang" directory.
 */

// The "customizations" plugin modifies some internal functionalities of the editor.
// It should be loaded before the other plugins.
FCKConfig.Plugins.Add('customizations', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// These plugins improve table-operations.
FCKConfig.Plugins.Add('dragresizetable') ;
FCKConfig.Plugins.Add('tablecommands') ;

// Audio files insertion.
FCKConfig.Plugins.Add('MP3', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// Another dialog for insertion audio files.
// If you wish to use it, disable the "MP3" plugin first.
//FCKConfig.Plugins.Add('audio', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// A specialised browser/editor for images.
if (!FCK.AdvancedFileManager)
{
	// It is not needed in the advanced file manager mode.
	FCKConfig.Plugins.Add('ImageManager', 'en,de,fr,nl,no,pl,ru,sv') ;
}

// This is the old flash plugin. Now the editor has a built-in flash dialog.
//FCKConfig.Plugins.Add('Flash', 'en');

// Embeding video files.
FCKConfig.Plugins.Add('fckEmbedMovies', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh');

// flv video files insertion.
FCKConfig.Plugins.Add('flvPlayer', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// Video streams insertion, YouTube service.
FCKConfig.Plugins.Add('youtube', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;

// Digital maps insertion, GoogleMaps service.
FCKConfig.Plugins.Add('googlemaps', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh') ;
// This key is for http://localhost. You must get one for each server where you want to use the plugin,
// just get the key for free here after agreeing to the Terms of Use of the GoogleMaps API:
// http://www.google.com/apis/maps/signup.html. // If you leave an empty string then the toolbar icon won't be shown.
FCKConfig.GoogleMaps_Key = 'ABQIAAAAlXu5Pw6DFAUgqM2wQn01gxT2yXp_ZAY8_ufC3CFXhHIE1NvwkxSy5hTGQdsosYD3dz6faZHVrO-02A';

// Mathematical formulas insertion.
// Before enabling the "mimetex" plugin, preliminary changes in server
// configuration have to be done. See the installation guide.
FCKConfig.Plugins.Add('mimetex', 'en,de,es,fr') ;

// Wiki-formated links insertion.
FCKConfig.Plugins.Add('wikilink', 'en,es') ;

// A dialog for assigning hyperlinks to specified image areas.
FCKConfig.Plugins.Add('imgmap', 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh');


/*
 * Settings for browsing the server.
 */

// For all acceptable types of resources (files).

// true/false to enable/disable the browser.
FCKConfig.LinkBrowser = true ;

// Browser window sizes.
FCKConfig.LinkBrowserWindowWidth	= 782 ;
FCKConfig.LinkBrowserWindowHeight	= 490 ;

// For images.

// true/false to enable/disable the browser.
FCKConfig.ImageBrowser = true ;

// Browser window sizes.
FCKConfig.ImageBrowserWindowWidth   = 782 ;
FCKConfig.ImageBrowserWindowHeight  = 490 ;

// Upload path for the Image manager. Leave it empty.
FCKConfig.IMUploadPath = '' ;

// For flash objects.

// true/false to enable/disable the browser.
FCKConfig.FlashBrowser = true ;

// Browser window sizes.
FCKConfig.FlashBrowserWindowWidth   = 782 ;
FCKConfig.FlashBrowserWindowHeight  = 490 ;

// For audio files.

// true/false to enable/disable the browser.
FCKConfig.MP3Browser = true ;

// Browser window sizes.
FCKConfig.MP3BrowserWindowWidth     = 782 ;
FCKConfig.MP3BrowserWindowHeight    = 490 ;

// For video files.

// true/false to enable/disable the browser.
FCKConfig.VideoBrowser = true ;

// Browser window sizes.
FCKConfig.VideoBrowserWindowWidth   = 782 ;
FCKConfig.VideoBrowserWindowHeight  = 490 ;

// For video (flv) files.

// true/false to enable/disable the browser.
FCKConfig.MediaBrowser = true ;

// Browser window sizes.
FCKConfig.MediaBrowserWindowWidth   = 782 ;
FCKConfig.MediaBrowserWindowHeight  = 490 ;


/*
 * Settings for direct uploads on the server, without using browsers.
 * Some of the editor's dialogs have quick-upload tabs for this purpose.
 */

// For all acceptable types of resources (files).

// true/false to enable/disable the quick-upload tab.
FCKConfig.LinkUpload = true ;

// To be moved in the php-integration file.
FCKConfig.LinkUploadAllowedExtensions	= ".(7z|aiff|asf|avi|bmp|csv|doc|fla|flv|gif|gz|gzip|jpeg|jpg|mid|mov|mp3|mp4|mpc|mpeg|mpg|ods|odt|pdf|png|ppt|pxd|qt|ram|rar|rm|rmi|rmvb|rtf|sdc|sitd|swf|sxc|sxw|tar|tgz|tif|tiff|txt|vsd|wav|wma|wmv|xls|xml|zip)$" ;			// empty for all
FCKConfig.LinkUploadDeniedExtensions	= "" ;

// For images.

// true/false to enable/disable the quick-upload tab.
FCKConfig.ImageUpload = true ;

// To be moved in the php-integration file.
FCKConfig.ImageUploadAllowedExtensions	= ".(jpg|gif|jpeg|png|bmp)$" ;
FCKConfig.ImageUploadDeniedExtensions	= "" ;

// For flash objects.

// true/false to enable/disable the quick-upload tab.
FCKConfig.FlashUpload = true ;

// To be moved in the php-integration file.
FCKConfig.FlashUploadAllowedExtensions	= ".(swf)$" ;
FCKConfig.FlashUploadDeniedExtensions	= "" ;

// For audio files.

// true/false to enable/disable the quick-upload tab.
FCKConfig.MP3Upload = true ;

// To be moved in the php-integration file.
FCKConfig.MP3UploadAllowedExtensions	= ".(mp3)$" ;
FCKConfig.MP3UploadDeniedExtensions	= "" ;

// For video files.

// true/false to enable/disable the quick-upload tab.
FCKConfig.VideoUpload = true ;

// To be moved in the php-integration file.
FCKConfig.VideoUploadAllowedExtensions	= ".(mpg|mpeg|mp4|avi|wmv|mov|asf)$" ;
FCKConfig.VideoUploadDeniedExtensions	= "" ;

// For video (flv) files.

// true/false to enable/disable the quick-upload tab.
FCKConfig.MediaUpload = true ;

// To be moved in the php-integration file.
FCKConfig.MediaUploadAllowedExtensions	= ".(flv)$" ;
FCKConfig.MediaUploadDeniedExtensions	= "" ;


/*
 * Alternative settings for the advanced file manager mode.
 */

// If you wish to alter some of the settings above and to make them
// specific for the advanced file manager mode, you may do this within
// the following "if" block.
if (FCK.AdvancedFileManager)
{
	FCKConfig.LinkUpload = false ;
	FCKConfig.ImageUpload = false ;
	FCKConfig.MP3Upload = false ;
	//FCKConfig.FlashUpload = false ; //See plugin
}


/*
 * Other settings.
 */

FCKConfig.UserStatus = 'teacher' ;
