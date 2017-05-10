<?php
/* For licensing terms, see /license.txt */

require_once 'main/inc/global.inc.php';
$tool_name = get_lang('SystemAnnouncements');
$visibility = SystemAnnouncementManager::getCurrentUserVisibility();
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $content = SystemAnnouncementManager::displayAnnouncementsSlider($visibility, $_GET['id']);
} else {
    $content = SystemAnnouncementManager::displayAnnouncement($_GET['id'], $visibility);
}

$tpl = new Template($tool_name);

if (api_is_platform_admin()) {
    $actionEdit = Display::url(
        Display::return_icon('edit.png', get_lang('EditSystemAnnouncement'), [], ICON_SIZE_MEDIUM),
        api_get_path(WEB_PATH).'main/admin/system_announcements.php'
    );

    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actionEdit])
    );
}

$tpl->assign('content', $content);
$tpl->display_one_col_template();
