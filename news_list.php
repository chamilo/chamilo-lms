<?php
/* For licensing terms, see /license.txt */

// including necessary files
require_once 'main/inc/global.inc.php';
$tool_name = get_lang('SystemAnnouncements');

$actions = '';
if (api_is_platform_admin()) {
	$actions = '<a href="'.api_get_path(WEB_PATH).'main/admin/system_announcements.php">'.
        Display::return_icon('edit.png', get_lang('EditSystemAnnouncement'), array(), 32).'</a>';
}

if (api_is_anonymous()) {
    $visibility = SystemAnnouncementManager::VISIBLE_GUEST;
} else {
    $visibility = api_is_allowed_to_create_course() ? SystemAnnouncementManager::VISIBLE_TEACHER : SystemAnnouncementManager::VISIBLE_STUDENT;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $content =  SystemAnnouncementManager::display_announcements_slider($visibility, $_GET['id']);
} else {
    $content = SystemAnnouncementManager::displayAnnouncement($_GET['id'], $visibility);
}

$tpl = new Template($tool_name);
$tpl->assign('actions', $actions);
//$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
