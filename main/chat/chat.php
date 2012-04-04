<?php
/* For licensing terms, see /license.txt */
/**
 * Chat tool
 * @package chamilo.chat
 */
/**
 * Code
 */

$language_file = array('chat');
require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_CHAT;

require_once api_get_path(LIBRARY_PATH).'groupmanager.lib.php';
$this_section = SECTION_COURSES;
$nameTools = get_lang('ToolChat');

if ($_GET["origin"] != 'whoisonline') {
	api_protect_course_script(true);
} else {
	$origin = $_SESSION['origin'];
	$target = $_SESSION['target'];
	$_SESSION['origin']=$_GET["origin"];
	$_SESSION['target']=$_GET["target"];
}

/*  TRACKING */

event_access_tool(TOOL_CHAT);

header('Content-Type: text/html; charset='.api_get_system_encoding());

/*
 * Choose CSS style (platform's, user's, or course's)
 */
$my_style = api_get_visual_theme();

$mycourseid = api_get_course_id();
if (!empty($mycourseid) && $mycourseid != -1) {
	$open_chat_window = api_get_course_setting('allow_open_chat_window');
}

$cidreq = Security::remove_XSS($_GET['cidReq']);

$toolgroup = Security::remove_XSS($_GET['toolgroup']); //fix when change by vertical or horizontal menu from a chat group to chat course.
if (empty($toolgroup) && empty($open_chat_window)){
	unset($_SESSION['_gid']);
}


?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo api_get_language_isocode(); ?>" lang="<?php echo api_get_language_isocode(); ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>" />
<?php
echo'<title>'.get_lang('Chat').' - '.$mycourseid.' - '.api_get_setting('siteName').'</title>';

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
	$interbreadcrumb[] = array('url' => '../group/group_space.php?gidReq='.$_SESSION['_gid'], 'name' => get_lang('GroupSpace').' '.$group_properties['name']);
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

//$is_allowed_to_edit = api_is_allowed_to_edit(false, true);


if (empty($open_chat_window)) {
	Display::display_header($tool_name, 'Chat');
}

echo '<iframe src="chat_whoisonline.php?cidReq='.$cidreq.'" name="chat_whoisonline" scrolling="auto" style="height:320px; width:19%; border: 0px none; float:left"></iframe>';
echo '<iframe src="chat_chat.php?origin='.Security::remove_XSS($_GET['origin']).'&target='.Security::remove_XSS($_GET['target']).'&amp;cidReq='.$cidreq.'" name="chat_chat" scrolling="auto" height="240" style="width:80%; border: 0px none; float:right"></iframe>';
echo '<iframe src="chat_message.php?cidReq='.$cidreq.'" name="chat_message" scrolling="no" height="80" style="width:80%; border: 0px none; float:right"></iframe>';
echo '<iframe src="chat_hidden.php?cidReq='.$cidreq.'" name="chat_hidden" height="0" style="border: 0px none"></iframe>';

if (empty($open_chat_window)) {
	Display::display_footer();
}

echo '</html>';
