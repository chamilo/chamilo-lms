/*
 *	Dokeos - elearning and course management software
 *
 *	Copyright (c) 2009 Dokeos SPRL
 *	Copyright (c) 2009 Ivan Tcholakov <ivantcholakov@gmail.com>
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
 * A fragment of the original source code of FCKeditor version 2.6.4.1
 * is used in this file.
 *
 * FCKeditor - The text editor for Internet - http://www.fckeditor.net
 * Copyright (C) 2003-2009 Frederico Caldeira Knabben
 */

// Logic-improvement for ensuring that English language will be shown at least, when the required language file is missing.
FCKPlugin.prototype.Load = function()
{
	// Logic-modification about loading language files.
	switch ( this.Name )
	{
		// The following plugins do not need language files or they load language files in their own way.
		case 'dragresizetable':
		case 'tablecommands':
		case 'ImageManager':
		case 'prompt':
			// In this case we have to clear the list of available languages.
			this.AvailableLangs = null ;
			this.AvailableLangs = new Array() ;
			break ;
		// The following plugins have all needed language files, default logic works well for them.
		case 'customizations':
		case 'audio':
		case 'fckEmbedMovies':
		case 'flvPlayer':
		case 'youtube':
		case 'googlemaps':
		case 'mimetex':
		case 'wikilink':
		case 'imgmap':
			// No actions are needed for fully integrated plugins.
			break ;
		// For new (unknown) plugins we assume that the English language file always exists.
		default:
			// English language is loaded first, later the required language is to be loaded.
			LoadScript( this.Path + 'lang/en.js' ) ;
	}
	// End of logic-modification.

	// Load the language file, if defined.
	if ( this.AvailableLangs.length > 0 )
	{
		var sLang ;

		// Check if the plugin has the language file for the active language.
		if ( this.AvailableLangs.IndexOf( FCKLanguageManager.ActiveLanguage.Code ) >= 0 )
			sLang = FCKLanguageManager.ActiveLanguage.Code ;
		else
			// Load the default language file (first one) if the current one is not available.
			sLang = this.AvailableLangs[0] ;

		// Add the main plugin script.
		LoadScript( this.Path + 'lang/' + sLang + '.js' ) ;
	}

	// Add the main plugin script.
	// Logic-modification about loading compressed version of some plugins.
	//LoadScript( this.Path + 'fckplugin.js' ) ;
	var file;
	switch ( this.Name )
	{
		case 'asciimath':
		case 'audio':
		case 'autogrow':
		case 'customizations':
		case 'dragresizetable':
		case 'fckEmbedMovies':
		case 'flvPlayer':
		case 'googlemaps':
		case 'ImageManager':
		case 'imgmap':
		case 'mimetex':
		case 'prompt':
		case 'tablecommands':
		case 'wikilink':
		case 'youtube':
			file = ( window.document.location.toString().indexOf('fckeditor.original.html') != -1 )
				? 'fckplugin.js'
				: 'fckplugin_compressed.js';
			break;
		default:
			file = 'fckplugin.js';
	}
	LoadScript( this.Path + file ) ;
	// End of logic-modification.
}
