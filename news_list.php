<?php
/* For licensing terms, see /license.txt */

require_once 'main/inc/global.inc.php';

$tool_name = get_lang('SystemAnnouncements');
$visibility = SystemAnnouncementManager::getCurrentUserVisibility();
$id = isset($_GET['id']) ? $_GET['id'] : 0;

if (empty($id)) {
    $content = SystemAnnouncementManager::displayAnnouncementsSlider($visibility);
} else {
    $content = SystemAnnouncementManager::displayAnnouncement($id, $visibility);
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
