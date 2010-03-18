<?php
/* For licensing terms, see /license.txt */

/**
 *	Chamilo banner
 *
 *	@author Olivier Brouckaert
 *	@chamilo chamilo.chat
 */

$language_file = array ('chat');
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
$this_section = SECTION_COURSES;

//$interbreadcrumb[] = array ('url' => 'chat.php', 'name' => get_lang('Chat'));
//$noPHP_SELF = true;
//$shortBanner = false;
//Display::display_header(null, 'Chat');

$tool_name = get_lang('ToolChat');

// If it is a group chat then the breadcrumbs.
if ($_SESSION['_gid'] OR $_GET['group_id']) {

	if (isset($_SESSION['_gid'])) {
		$_clean['group_id'] = (int)$_SESSION['_gid'];
	}
	if (isset($_GET['group_id'])) {
		$_clean['group_id'] = (int)Database::escape_string($_GET['group_id']);
	}

	$group_properties  = GroupManager :: get_group_properties($_clean['group_id']);
	$interbreadcrumb[] = array('url' => '../group/group.php', 'name' => get_lang('Groups'));
	$interbreadcrumb[] = array('url' => '../group/group_space.php?gidReq='.$_SESSION['_gid'], 'name' => get_lang('GroupSpace').' ('.$group_properties['name'].')');
	$noPHP_SELF = true;
	$shortBanner = false;
	$add_group_to_title = ' ('.$group_properties['name'].')';
	$groupfilter = 'group_id="'.$_clean['group_id'].'"';

	// Ensure this tool in groups whe it's private or deactivated
	/*if ($group_properties['chat_state'] == 0) {
		echo api_not_allowed();
	} elseif ($group_properties['chat_state'] == 2) {
 		if (!api_is_allowed_to_edit(false,true) and !GroupManager :: is_user_in_group($_user['user_id'], $_SESSION['_gid'])) {
			echo api_not_allowed();
		}
	}*/

} else {
	$groupfilter = 'group_id=0';
}
Display::display_header($tool_name, 'Chat');
//$is_allowed_to_edit = api_is_allowed_to_edit(false, true);
?>

</body>
</html>