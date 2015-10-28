<?php
/* For licensing terms, see /license.txt */

/**
 * Chat tool
 * @package chamilo.chat
 */

require_once '../inc/global.inc.php';
$current_course_tool  = TOOL_CHAT;
$this_section = SECTION_COURSES;
$nameTools = get_lang('ToolChat');

$origin = isset($_GET["origin"]) ? Security::remove_XSS($_GET["origin"]) : null;
$target = isset($_GET["target"]) ? Security::remove_XSS($_GET["target"]) : null;

if ($origin != 'whoisonline') {
    api_protect_course_script(true);
} else {
    $origin = $_SESSION['origin'];
    $target = $_SESSION['target'];
    $_SESSION['origin']= $origin;
    $_SESSION['target']= $target;
}

api_protect_course_group(GroupManager::GROUP_TOOL_CHAT, false);

/*  TRACKING */
Event::event_access_tool(TOOL_CHAT);
header('Content-Type: text/html; charset=UTF-8');

/*
 * Choose CSS style (platform's, user's, or course's)
 */
$my_style = api_get_visual_theme();

$mycourseid = api_get_course_id();
if (!empty($mycourseid) && $mycourseid != -1) {
	$open_chat_window = api_get_course_setting('allow_open_chat_window');
}

$courseCode = Security::remove_XSS($_GET['cidReq']);

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8" />
<link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CSS_PATH); ?>chat.css">
<?php
echo'<title>'.get_lang('Chat').' - '.$mycourseid.' - '.api_get_setting('siteName').'</title>';

$groupId = api_get_group_id();

// If it is a group chat then the breadcrumbs.
if (!empty($groupId)) {
	$group_properties  = GroupManager :: get_group_properties($groupId);
    $interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'group/group.php?'.api_get_cidreq(),
        'name' => get_lang('Groups')
    );
	$interbreadcrumb[] = array(
        'url' => api_get_path(WEB_CODE_PATH).'group/group_space.php?'.api_get_cidreq(),
        'name' => get_lang('GroupSpace').' '.$group_properties['name']
    );
	$noPHP_SELF = true;
	$shortBanner = false;
	$add_group_to_title = ' ('.$group_properties['name'].')';
	$groupfilter = 'group_id="'.$groupId.'"';
} else {
	$groupfilter = 'group_id=0';
}

if (empty($open_chat_window)) {
    Display::display_header($tool_name, 'Chat');
}

$url = api_get_path(WEB_CODE_PATH).'chat/';
$params = api_get_cidreq();

echo '<div class="page-chat">';
echo '<iframe src="'.$url.'chat_whoisonline.php?'.$params.'" name="chat_whoisonline" scrolling="no" style="height:550px; width:35%; border: 0px none; float:left"></iframe>';
echo '<iframe src="'.$url.'chat_chat.php?origin='.$origin.'&target='.$target.'&'.$params.'" name="chat_chat" id="chat_chat" scrolling="auto" height="380" style="width:65%; border: 0px none; float:right"></iframe>';
echo '<iframe src="'.$url.'chat_message.php?'.$params.'" name="chat_message" scrolling="no" height="182px" style="width:65%; border: 0px none; float:right"></iframe>';
echo '<iframe src="'.$url.'chat_hidden.php?'.$params.'" name="chat_hidden" height="0px" style="height:0px; border: 0px none"></iframe>';
echo '</div>';

if (empty($open_chat_window)) {
    Display::display_footer();
}

echo '</html>';
