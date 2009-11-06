<?php // $Id: chat_banner.php,v 1.3 2005/06/06 13:01:23 olivierb78 Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Dokeos banner
*
*	@author Olivier Brouckaert
*	@package dokeos.chat
==============================================================================
*/

$language_file = array ('chat');
include('../inc/global.inc.php');
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';

//$interbreadcrumb[] = array ("url" => "chat.php", "name" => get_lang("Chat"));
//$noPHP_SELF=true;
//$shortBanner=false;
//Display::display_header(null,"Chat");

$tool_name = get_lang('Chat');

// If it is a group chat then the breadcrumbs.
if ($_SESSION['_gid'] OR $_GET['group_id']) {

	if (isset($_SESSION['_gid'])) {
		$_clean['group_id']=(int)$_SESSION['_gid'];
	}
	if (isset($_GET['group_id'])) {
		$_clean['group_id']=(int)Database::escape_string($_GET['group_id']);
	}

	$group_properties  = GroupManager :: get_group_properties($_clean['group_id']);
	$interbreadcrumb[] = array ("url" => "../group/group.php", "name" => get_lang('Groups'));
	$interbreadcrumb[] = array ("url"=>"../group/group_space.php?gidReq=".$_SESSION['_gid'], "name"=> get_lang('GroupSpace').' ('.$group_properties['name'].')');
	$noPHP_SELF=true;
	$shortBanner=false;
	$add_group_to_title = ' ('.$group_properties['name'].')';
	$groupfilter='group_id="'.$_clean['group_id'].'"';

	//ensure this tool in groups whe it's private or deactivated
	/*if 	($group_properties['chat_state']==0)
	{
		echo api_not_allowed();
	}
	elseif ($group_properties['chat_state']==2)
	{
 		if (!api_is_allowed_to_edit(false,true) and !GroupManager :: is_user_in_group($_user['user_id'], $_SESSION['_gid']))
		{
			echo api_not_allowed();
		}
	}*/

}
else
{
	$groupfilter='group_id=0';
}
Display::display_header($tool_name, 'Chat');
//$is_allowed_to_edit = api_is_allowed_to_edit(false,true);
?>

</body>
</html>