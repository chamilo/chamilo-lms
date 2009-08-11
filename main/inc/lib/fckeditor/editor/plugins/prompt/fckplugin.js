/*
 *	Dokeos - elearning and course management software
 *
 *	Copyright (c) 2008-2009 Dokeos SPRL
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
FCKDialog.Prompt = function( message, default_value, action, title, width, height )
{
	if ( !message )
	{
		message = '&nbsp;';
	}

	if ( !title )
	{
		title = '&nbsp;';
	}

	if ( !default_value )
	{
		default_value = '';
	}

	if ( !width )
	{
		width = 500 ;
	}

	if ( !height )
	{
		height = 200 ;
	}

	var custom_value = {} ;
	custom_value.default_value = default_value
	custom_value.action = action ;
	custom_value.message = message ;
	
	FCKDialog.OpenDialog( 'FCKDialog_Prompt', title, FCKConfig.PluginsPath + 'prompt/fck_prompt.html', width, height, custom_value ) ;
}
