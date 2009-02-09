<?php // $Id: view_message.php 18385 2009-02-09 21:56:23Z iflorespaz $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>
	Copyright (c) Facultad de Matematicas, UADY (México)
	Copyright (c) Evie, Free University of Brussels (Belgium)	
	Copyright (c) 2009 Isaac Flores Paz <isaac.flores.paz@gmail.com>
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

    Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
    Mail: info@dokeos.com
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
=========================================================================5====
*/
// name of the language file that needs to be included
$language_file= 'messages';
$cidReset= true;
include_once('../inc/global.inc.php');
api_block_anonymous_users();
if (api_get_setting('allow_message_tool')!='true'){
	api_not_allowed();
}
require_once(api_get_path(LIBRARY_PATH).'message.lib.php');
$nameTools = get_lang('Messages');
if (isset($_GET['id_send']) || isset($_GET['id'])) {
	if (isset($_GET['rs'])) {
		$interbreadcrumb[]= array (
			'url' => '#',
			'name' => get_lang($nameTools)
		);
		$interbreadcrumb[]= array (
				'url' => '../social/'.$_SESSION['social_dest'].'?#remote-tab-2',
				'name' => get_lang('SocialNetwork')
		);	
		$interbreadcrumb[]= array (
			'url' => 'inbox.php',
			'name' => get_lang('Inbox')
		);
		$interbreadcrumb[]= array (
			'url' => 'outbox.php',
			'name' => get_lang('Outbox')
		);
	} else {
	$interbreadcrumb[]= array (
		'url' => '#',
		'name' => get_lang($nameTools)
	);
	$interbreadcrumb[]= array (
		'url' => 'inbox.php',
		'name' => get_lang('Inbox')
	);
	$interbreadcrumb[]= array (
		'url' => 'outbox.php',
		'name' => get_lang('Outbox')
	);
	}	
}
/*
==============================================================================
		HEADER
==============================================================================
*/
Display::display_header('');
api_display_tool_title(get_lang('ReadMessage'));
MessageManager::show_message_box();
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>