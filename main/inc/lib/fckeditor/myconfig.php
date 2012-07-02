<?php

/*
 *	Chamilo LMS
 *
 *	Copyright (c) 2009-2011 Juan Carlos Raña
 *	Copyright (c) 2009-2011 Ivan Tcholakov
 *	Copyright (c) 2009 Dokeos SPRL
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
 */


/*
 * Custom editor configuration settings, php-side.
 * See http://docs.fckeditor.net/FCKeditor_2.x/Developers_Guide/Configuration/Configuration_Options
 *
 * Configuration data for the editor comes from different sources which are prioritised.
 *
 * 1-st level (the highest priority)
 * "Hardcoded" options by developers. 'Width' and 'Height' are exception of this rule.
 *
 * 2-nd level
 * Configuration settings from myconfig.php (this file).
 *
 * 3-rd level
 * Default configuration settings that are determined (calculated) by the system..
 *
 * 4-th level
 * Configuration settings from myconfig.js. This file may be modified for customization purposes too.
 * You may choose to create there options or to transfer options from here (not all) which have low probability of future changes.
 * Thus, you will gain performance by exploiting caching, but changes in myconfig.js do not enforce immediatelly.
 * Here is the mapping rule:
 *
 * myconfig.php                                      myconfig.js
 * ---------------------------------------------------------------------------------------------
 * $config['FontFormats'] = 'p;h1;h2;h3;h4;h5';      FCKConfig.FontFormats = 'p;h1;h2;h3;h4;h5';
 *
 * 5-th level (the lowest priority)
 * Configuration settings from myconfig.js. This file is "as is" in the original source, modifying it is not recommended.
 */

/*
 * Toolbar definitions.
 */

// The following setting is the directory where the online editor's toobar definitions reside in correspondent php-files.
// By default, the directory name is 'default' and it has been created at .../chamilo/main/inc/lib/fckeditor/toolbars/ .
// For using your customized toolbars, crate another directory, for example 'custom' at the same path, i.e.
// create .../chamilo/main/inc/lib/fckeditor/toolbars/custom/ . Then, copy the original php-definition files
// from .../chamilo/main/inc/lib/fckeditor/toolbars/default/ to the new one. Change the following configuration setting, so it to
// point to the new directory:
// $config['ToolbarSets']['Directory'] = 'custom';
// Then, you may modify the newly copied toolbar definitions at your will, just keep correct php-syntax.
// It is not mandatory you to create custom files for all the toolbars. In case of missing file in the directory with the
// custom toobar definitions the system would read the correspondent "factory" toolbar definition (form 'default' directory).


// This is the visible toolbar set when the editor is maximized.
// If it has not been defined, then the toolbar set for the "normal" size is used.
// if do you prefer configure for each toolbar edit main/inc/lib/fckeditor/toolbars/

if ((api_get_setting('more_buttons_maximized_mode') == 'true')) {
    $config['ToolbarSets']['Directory'] = 'extended';
} else {
    $config['ToolbarSets']['Directory'] = 'default';
}


/*
 * Plugins.
 */

// customizations : This plugin has been developed by the Chamilo team for editor's integration within the system.
// The plugin should be loaded first, before all other plugins. Please, do not disable it.
$config['LoadPlugin'][] = 'customizations';

// dragresizetable & tablecommands : Plugins for improvement table-related operations.
if (trim(get_lang('text_dir', '')) != 'rtl') {
    // This plugin works properly only when language writting system is "from left to right (ltr)".
    $config['LoadPlugin'][] = 'dragresizetable';
}
$config['LoadPlugin'][] = 'tablecommands';

//This plug-in allows to insert tables with one click
$config['LoadPlugin'][] = 'tableoneclick';

// prompt : This plugin is a dialog implementation as a replacemet of the javascript function prompt().
// It provides consistent appearance and avoiding activation of browser's blocking features.
$config['LoadPlugin'][] = 'prompt';

// audio: Adds a dialog for inserting audio files (.mp3).
$config['LoadPlugin'][] = 'audio';

// glossary: this plugin add a term from glossary tool in Chamilo.
if (api_get_setting('show_glossary_in_documents') == 'ismanual') {
    $config['LoadPlugin'][] = 'glossary';
}

// MP3: This is the old plugin for inserting audio files.
// If you wish to use it, disable the "audio" plugin first.
//$config['LoadPlugin'][] = 'MP3';

// ImageManager : Adds a dialog (image gallery) for inserting images. The advanced file manager has its own functionality
// for previewing images. This is why we load this plugin only in case when the simple file manager is used.
if (!(api_get_setting('advanced_filemanager') == 'true')) {
    $config['LoadPlugin'][] = 'ImageManager';
    // The following setting defines how the simple file manager to be opened:
    // true  - in a new browser window, or
    // false - as a dialog whithin the page (recommended).
    $config['OpenImageManagerInANewWindow'] = false;
}

// fckEmbedMovies : Adds a dilog for inserting video files.
$config['LoadPlugin'][] = 'fckEmbedMovies';

// flvPlayer : Adds a dilog for inserting video files (.flv, .mp4), so they to be viewed through a flash-based player.
$config['LoadPlugin'][] = 'flvPlayer';

// Choosing Flash embedding technique:
// 'embed'	   - <embed> tag is used, this is the oldest way that is still supported by most of the browsers;
// 'object'    - <object> tag is used, specific for Internet Explorer;
// 'adobe'     - this is the "adobe way" - <object><embed /></object>, it works very well, but the <object> tag is not XML-compliant;
// 'swfobject' - this is a technique that uses a special javascript, it works very well if javasripts are not disabled (due to security reasons).
// This setting works only for the plugin 'flvPlayer' so far.
$config['FlashEmbeddingMethod'] = 'adobe' ;

// youtube : Adds a dilog for inserting YouTube video-streams.
if (api_get_setting('youtube_for_students') == 'true') {
    $config['LoadPlugin'][] = 'youtube';
} else {
    if (api_is_allowed_to_edit() || api_is_platform_admin()) { // (not for students)
        $config['LoadPlugin'][] = 'youtube';
    }
}
$config['LoadPlugin'][] = 'media';

// googlemaps : Adds a dialog for inserting Google maps.
if ((api_get_setting('enabled_googlemaps') == 'true')) {
    $config['LoadPlugin'][] = 'googlemaps';

    /*
    **API-key for the "googlemaps" plugin.
    The following key is valid for http://localhost (see myconfig.js where this key has been activated by default).
    You must get a new for each server where you intend to use the plugin 'googlemaps'. Just get the key for free after agreeing with the Terms of Use of the GoogleMaps API from here: http://www.google.com/apis/maps/signup.html.
    At you choice, you may activate the newly obtained API-key using the following setting or using the same setting in myconfig.js.
    Activated here API-key is not cached by browsers and overrides the key from the configuration file myconfig.js.
    *
    */

    //$config['GoogleMaps_Key'] = 'ABQIAAAAlXu5Pw6DFAUgqM2wQn01gxT2yXp_ZAY8_ufC3CFXhHIE1NvwkxSy5hTGQdsosYD3dz6faZHVrO-02A'; //sample for localhost
}


// mimetex : Adds a dialog for inserting mathematical formulas. In order this plugin to work properly, preliminary changes.
if ((api_get_setting('math_mimetex') == 'true')) {
    $config['LoadPlugin'][] = 'mimetex';
    // in your server configuration have to be done. The MimeTex executable file has to be installed, see the installation guide.

        // Some additional settings become active only when the 'mimetex' plugin has been enabled:
        //
        // The following setting determines whether MimeTeX executable file has been installed on the server or not. This file should be accessible
        // for browsers at address http://mysite.com/cgi-bin/mimetex.cgi (Linux) or at address http://mysite.com/cgi-bin/mimetex.exe (Windows).
        // How to test manually: Open your browser and enter in the address bar something like http://mysite.com/cgi-bin/mimetex.cgi?hello
        // By default, the system tries to detect the MimeTeX executable file automatically.
        // If you are sure that the MimeTeX executable has been correctly installed, you may set this option to boolean true value.

        $config['MimetexExecutableInstalled'] = 'true'; // 'detect' (default), true, false.

        // Sometimes detection fails (due to slow DNS service, security restrictions, ...). For better probability of success,
        // the following methods for detection have been defined:
        // 'bootstrap_ip'   - detection is tried at address like http://127.0.0.1/cgi-bin/mimetex.cgi
        // 'localhost'      - detection is tried at address like http://localhost/cgi-bin/mimetex.cgi
        // 'ip'             - detection is tried at ip address, for example http://192.168.0.1/cgi-bin/mimetex.cgi
        // 'server_name'    - detection is tried at address based on server's name, for example http://mysite.com/cgi-bin/mimetex.cgi

        if (IS_WINDOWS_OS) {
            $config['MimetexExecutableDetectionMethod'] = 'bootstrap_ip'; // 'bootstrap_ip' for better chance on Windows (no firewall blocking).
        } else {
            $config['MimetexExecutableDetectionMethod'] = 'server_name';
        }
        // Timeout for MimeTeX executable file detection - keep this value as low as possible, especially on Windows servers.
        $config['MimetexExecutableDetectionTimeout'] = 0.05;
}


// asciimath : Yet another plugin for inserting mathematical formulas.
// This plugin works only with full-page documents (with full html syntax).
// The "Documents" tool can use this plugin for sure, so, activate its button there.
// An additional javascript library ASCIIMathML.js is automaticaly inserted within the pages with formulas,
// if there is no security restriction (script-tag should not be filtered, by default this is valid for teachers).
// After enabling it, this plugin is configured to work with full-page html documents out-of-the box.
// Browser compatibility: Internet Explorer 6.0+ with MathPlayer plugin, Mozilla Firefox 2.0+, Opera 9.5+
if ((api_get_setting('math_asciimathML') == 'true')) {
    // The following additional conditions are mandatory for correct working of the plugin. Please, don't alter them.
    if ($this->Config['FullPage'] || api_get_setting('include_asciimathml_script') == 'true') {
        $config['LoadPlugin'][] = 'asciimath';
    }
}

// asciisvg: A plugin for drawing mathematical graphics on svg-enabled browsers.
// This plugin works only with full-page documents (i.e. place its buttons for "Documents" tool).
// An additional javascript library ASCIIMathML.js is automaticaly inserted within the pages with graphics,
// if there is no security restriction (script-tag should not be filtered, by default this is valid for teachers).
// Internet Explorer 8 (or prior) needs Adobe SVG Viewer installed, see http://www.adobe.com/svg/viewer/install/
if ((api_get_setting('enabled_asciisvg') == 'true')) {
    // The following additional conditions are mandatory for correct working of the plugin. Please, don't alter them.
    if ($this->Config['FullPage'] || api_get_setting('include_asciimathml_script') == 'true') {
        $config['LoadPlugin'][] = 'asciisvg';
    }
}

// Yet another mandatory condition for proper working of the plugins 'asciimath' and 'asciisvg'.
if (api_get_setting('include_asciimathml_script') == 'true') {
    // The automatic content parsing should be disabled on this case, otherwise content would be damaged.
    // The editor does the necessary parsing within its separate iframe.
    echo '<script type="text/javascript">var translateOnLoad = false ;</script>';
}

// WIRIS: plugin for inserting mathematical formulas
// You may try it in the "Documents" and "Wiki" tools.
// Users' computers need java installed
if ((api_get_setting('enabled_wiris') == 'true')) {
    $config['LoadPlugin'][] = 'fckeditor_wiris';
}

// imgmap : Adds a dialog for assigning hyperlinks to specified image areas.
if ((api_get_setting('enabled_imgmap') == 'true')) {
    $config['LoadPlugin'][] = 'imgmap';
}

// wikilink : Adds a dialog for inserting wiki-formatted links.
$config['LoadPlugin'][] = 'wikilink';


// insertHtml: Plugin for inserting HTML. A single preconfigured snippet; a choice from multiple preconfigured snippets; or manually entered HTML.
if ((api_get_setting('enabled_insertHtml') == 'true')) {
    $config['LoadPlugin'][] = 'insertHtml';

    // Enter a single snippet (HMTL string) or multiple snippets to choose form (object of description string and HTML string pairs). Or leave empty if you want to show a dialog for the user to enter HTML manually (and not show any default HTML in the dialog textarea)

    //$config['insertHtml_snippets'] = { 'ArtGallery': '<embed width="475" height="400" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="FlashGallery/Art/ArtFlashGallery.swf" mediaType="flashGallery" bgcolor="#ffffff" allowfullscreen="true" flashvars="XMLFile=userfiles/image/ArtGallery/gallery.xml"></embed>', 'PhotoFlowGallery' : '<embed width="700" height="300" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="FlashGallery/PhotoFlow/PhotoFlowGallery.swf" mediaType="flashGallery" bgcolor="#ffffff" flashvars="XMLFile=userfiles/image/PhotoFlowGallery/gallery.xml"></embed>', 'StackPhotoGallery' : '<embed width="600" height="400" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="FlashGallery/StackPhoto/StackPhotoGallery.swf" mediaType="flashGallery" bgcolor="#ffffff" flashvars="XMLFile=userfiles/image/StackPhotoGallery/gallery.xml"></embed>', 'ZenGallery' : '<embed width="550" height="400" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" src="FlashGallery/Zen/ZenGallery.swf" mediaType="flashGallery" bgcolor="#ffffff" flashvars="XMLFile=userfiles/image/ZenGallery/gallery.xml"></embed>'};// TODO: this $config['insertHtml_snippets'] does not work. See also temporal hack around line 76 insertHtml.js

    // (If no snippets are specified the dialog will show up anyhow)
    $config['insertHtml_showDialog'] = true;

    // Specify a custom tooltip if you want this to appear when hovering the plugin's toolbar button (e.g. a description of the element being inserted). If no custom tooltip is set 'Insert HTML' will appear, and if available, translated into your language
    $config['insertHtml_buttonTooltip'] = '';

    // Show a textarea in the dialog? The first HTML set with $config['insertHtml_snippets'] will be shown in the textarea by default. If no snippets specified the textarea will show anyhow
    $config['insertHtml_showTextarea']= false;

    // The size of the textarea in the dialog, in px
    $config['insertHtml_textareaWidth'] = 400;
    $config['insertHtml_textareaHeight'] = 300;

    // The size of the the dialog, in px
    $config['insertHtml_dialogWidth'] = 475;
    $config['insertHtml_dialogHeight'] = 475;
}

// Abbr : Insert, change or delete Abbr-Title-TAG

$config['LoadPlugin'][] = 'Abbr';

/*
 * File manager.
 */

// Set true/false to enable/disable the file manager for different resource types:
$config['LinkBrowser']  = true;   // for any type of files;
$config['ImageBrowser'] = true;   // for images;
$config['FlashBrowser'] = true ;  // for flash objects;
$config['MP3Browser'] = true ;    // for audio files;
$config['VideoBrowser'] = true ;  // for video files;
$config['MediaBrowser'] = true ;  // for video (flv) files.

// The following setting defines how the simple file manager to be opened:
// true  - in a new browser window, or
// false - as a dialog whithin the page (recommended).
$config['OpenSimpleFileManagerInANewWindow'] = false;

// How the advanced file manager to be opened:
// true  - in a new browser window, or
// false - as a dialog whithin the page (recommended).
$config['OpenAdvancedFileManagerInANewWindow'] = false;


/*
 * Separate settings for the simple and the advanced file manager modes.
 */

if ((api_get_setting('advanced_filemanager') == 'true')) {

    // For the advanced file manager.

    // Dialog/window size for browsing:
    // any type of files;
    $config['LinkBrowserWindowWidth']	= 930 ;
    $config['LinkBrowserWindowHeight']	= 580 ;
    // images;
    $config['ImageBrowserWindowWidth']	= 930 ;
    $config['ImageBrowserWindowHeight']	= 580 ;
    // flash objects;
    $config['FlashBrowserWindowWidth']	= 930 ;
    $config['FlashBrowserWindowHeight']	= 580 ;
    // audio files;
    $config['MP3BrowserWindowWidth']	= 930 ;
    $config['MP3BrowserWindowHeight']	= 580 ;
    // video files;
    $config['VideoBrowserWindowWidth']	= 930 ;
    $config['VideoBrowserWindowHeight']	= 580 ;
    // video (flv) files.
    $config['MediaBrowserWindowWidth']	= 930 ;
    $config['MediaBrowserWindowHeight']	= 580 ;

    // Set true/false to enable/disable the quick-upload tabs for different resource types:
    $config['LinkUpload']  = false;  // for any type of files;
    $config['ImageUpload'] = false;  // for images;
    $config['FlashUpload'] = false;  // for flash objects;
    $config['MP3Upload']   = false;  // for audio files;
    $config['VideoUpload'] = false;  // for video files;
    $config['MediaUpload'] = false;  // for video (flv) files.

} else {

    // For the simple file manager.

    // Dialog/window size for browsing:
    // any type of files;
    $config['LinkBrowserWindowWidth']	= 780 ;
    $config['LinkBrowserWindowHeight']	= 500 ;
    // images;
    $config['ImageBrowserWindowWidth']	= 780 ;
    $config['ImageBrowserWindowHeight']	= 500 ;
    // flash objects;
    $config['FlashBrowserWindowWidth']	= 780 ;
    $config['FlashBrowserWindowHeight']	= 500 ;
    // audio files;
    $config['MP3BrowserWindowWidth']	= 780 ;
    $config['MP3BrowserWindowHeight']	= 500 ;
    // video files;
    $config['VideoBrowserWindowWidth']	= 780 ;
    $config['VideoBrowserWindowHeight']	= 500 ;
    // video (flv) files.
    $config['MediaBrowserWindowWidth']	= 780 ;
    $config['MediaBrowserWindowHeight']	= 500 ;

    // Set true/false to enable/disable the quick-upload tabs for different resource types:
    $config['LinkUpload']  = true;  // for any type of files;
    $config['ImageUpload'] = true;  // for images;
    $config['FlashUpload'] = true;  // for flash objects;
    $config['MP3Upload']   = true;  // for audio files;
    $config['VideoUpload'] = true;  // for video files;
    $config['MediaUpload'] = true;  // for video (flv) files.
}


/*
 * Miscellaneous settings.
 */

// The items in the format drop-down list.
//$config['FontFormats'] = 'p;h1;h2;h3;h4;h5;h6;pre;address;div';
$config['FontFormats'] = 'p;h1;h2;h3;h4;h5'; // A reduced format list.

// The following setting guarantees white backgroung for the editing area
// for all browsers. You may disable or change it if you wish.
$config['EditorAreaStyles'] = 'body { background: #ffffff; }';

// A setting for blocking copy/paste functions of the editor.
// This setting activates on leaners only. For users with other statuses there is no blocking copy/paste.
// if do you prefer configure for each toolbar edit main/inc/lib/fckeditor/toolbars/
if ((api_get_setting('block_copy_paste_for_students') == 'true')) {
    $config['BlockCopyPaste'] = true;
}

/*
 * Additional note:
 * For debugging purposes the editor may run using original source versions of its javascripts, not the "compressed" versions.
 * In case of problems, when you need to use this feature, go to the platform administration settings page and switch the system
 * into "test server" mode. Don't forged to switch it back to "production server" mode after testing.
 */
