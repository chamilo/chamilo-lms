/*
 *	This piece of software has been created for Chamilo LMS
 *	Mail: info@chamilo.org
 *
 *	Copyright (c) 2009-2010 Ivan Tcholakov <ivantcholakov@gmail.com>
 *
 *	For a full list of contributors detaining copyrights over parts of
 *	the Chamilo software, see "documentation/credits.html".
 *	The full license can be read in "documentation/license.html".
 *
 *	This program is free software; you can redistribute it and/or
 *	modify it under the terms of the GNU General Public License version 3
 *	as published by the Free Software Foundation.
 *
 *	See the GNU General Public License for more details.
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
	custom_value.default_value = default_value ;
	custom_value.action = action ;
	custom_value.message = message ;

	FCKDialog.OpenDialog( 'FCKDialog_Prompt', title, FCKConfig.PluginsPath + 'prompt/fck_prompt.html', width, height, custom_value ) ;
}
