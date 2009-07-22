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
 * Custom editor configuration settings.
 *
 * Follow this link for more information:
 * http://docs.fckeditor.net/FCKeditor_2.x/Developers_Guide/Configuration/Configuration_Options
 *
 * Please, do not modify the file fckconfig.js in order to make upgrades easy.
 * Just create your desired settings in this file, myconfig.js.
 * Also, configuration options (with higher priority) may be created/modified within the file myconfig.php.
 */

FCKConfig.DocType = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">' ;
FCKConfig.SkinPath = FCKConfig.BasePath + 'skins/silver/' ;

FCKConfig.ProtectedSource.Add( /<script[\s\S]*?\/script>/gi ) ;	// To protect <script> tags.

FCKConfig.AutoDetectLanguage = false ;

FCKConfig.ContextMenu = ['Generic','Link','Anchor','Image','Flash','Select','Textarea','Checkbox','Radio','TextField','HiddenField','ImageButton','Button','BulletedList','NumberedList','Table','Form'] ;
FCKConfig.TemplatesXmlPath	= FCKConfig.EditorPath + 'fcktemplates.xml.php' ;

FCKConfig.DisableFFTableHandles = false ;

FCKConfig.SmileyWindowWidth = 450 ;
FCKConfig.SmileyWindowHeight = 250 ;


/*
 * Plugins.
 */

// Loading integrated by the Dokeos team plugins. To enable/disable them, see myconfig.php.
FCKConfig.AvailableLanguages = 'en,af,ar,bg,bn,bs,ca,cs,da,de,el,en-au,en-ca,en-uk,eo,es,et,eu,fa,fi,fo,fr-ca,fr,gl,gu,he,hi,hr,hu,is,it,ja,km,ko,lt,lv,mn,ms,nb,nl,no,pl,pt-br,pt,ro,ru,sk,sl,sr-latn,sr,sv,th,tr,uk,vi,zh-cn,zh' ;
FCKConfig.LoadPlugin = eval( '(' + FCKConfig.PageConfig.LoadPlugin + ')' ) ;
for ( var i = 0 ; i < FCKConfig.LoadPlugin.length ; i++ ) {
	switch ( FCKConfig.LoadPlugin[i] ) {
		// These plugins do not need language files or they load lannguage files in their own way.
		case 'dragresizetable':
		case 'tablecommands':
		case 'ImageManager':
			FCKConfig.Plugins.Add( FCKConfig.LoadPlugin[i] ) ;
			break ;
		default:
			// The rest of the plugins require loading language files.
			FCKConfig.Plugins.Add( FCKConfig.LoadPlugin[i], FCKConfig.AvailableLanguages ) ;
	}
}

// API-key for the "googlemaps" plugin.
// The following key is valid for http://localhost. You must get one for each server where you want to use
// the plugin, just get the key for free here after agreeing to the Terms of Use of the GoogleMaps API:
// http://www.google.com/apis/maps/signup.html.
// If you leave an empty string then the toolbar icon won't be shown.
FCKConfig.GoogleMaps_Key = 'ABQIAAAAlXu5Pw6DFAUgqM2wQn01gxT2yXp_ZAY8_ufC3CFXhHIE1NvwkxSy5hTGQdsosYD3dz6faZHVrO-02A' ;

// This is the old flash plugin. Now the editor has a built-in flash dialog.
// Probably this plugin will be removed at the next release.
//FCKConfig.Plugins.Add('Flash', 'en') ;

// You may add your own plugins here, i.e. write something as follows:
// FCKConfig.Plugins.Add('my_plugin', 'en') ;


/*
 * File Manager.
 */

//Upload path for the Image manager. Leave it empty.
FCKConfig.IMUploadPath = '' ;


/*
 * Quick-upload tabs.
 */

// Lists of allowed and denied name extensions of files to be uploaded.
// for all acceptable types of files;
FCKConfig.LinkUploadAllowedExtensions	= '.(7z|aiff|asf|avi|bmp|csv|doc|fla|flv|gif|gz|gzip|jpeg|jpg|mid|mov|mp3|mp4|mpc|mpeg|mpg|ods|odt|pdf|png|ppt|pxd|qt|ram|rar|rm|rmi|rmvb|rtf|sdc|sitd|swf|sxc|sxw|tar|tgz|tif|tiff|txt|vsd|wav|wma|wmv|xls|xml|zip)$' ;			// empty for all
FCKConfig.LinkUploadDeniedExtensions	= '' ;
// for images;
FCKConfig.ImageUploadAllowedExtensions	= '.(jpg|gif|jpeg|png|bmp)$' ;
FCKConfig.ImageUploadDeniedExtensions	= '' ;
// for flash objects;
FCKConfig.FlashUploadAllowedExtensions	= '.(swf)$' ;
FCKConfig.FlashUploadDeniedExtensions	= '' ;
// for audio files;
FCKConfig.MP3UploadAllowedExtensions	= '.(mp3)$' ;
FCKConfig.MP3UploadDeniedExtensions		= '' ;
// for video files;
FCKConfig.VideoUploadAllowedExtensions	= '.(mpg|mpeg|mp4|avi|wmv|mov|asf)$' ;
FCKConfig.VideoUploadDeniedExtensions	= '' ;
// for video (flv) files.
FCKConfig.MediaUploadAllowedExtensions	= '.(flv|mp4)$' ;
FCKConfig.MediaUploadDeniedExtensions	= '' ;
// Note: These lists get combined with the platform's white and black lists.


/*
 * Other settings.
 */

// TODO: This setting seems obsolete. To be checked for removal.
FCKConfig.UserStatus = 'teacher' ;
