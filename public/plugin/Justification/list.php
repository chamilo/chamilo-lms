<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$allowSessionAdmins = api_get_plugin_setting('justification', 'access_for_session_admin') === 'true';
api_protect_admin_script($allowSessionAdmins);

$tool = 'justification';
$plugin = Justification::create();

$tpl = new Template($tool);
$fields = [];

$list = $plugin->getList();

$tpl->assign('list', $list);

$content = $tpl->fetch('Justification/view/list.tpl');
$actionLinks = '';
$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

switch ($action) {
    case 'delete':
        $sql = "DELETE FROM justification_document WHERE id = $id";
        Database::query($sql);

        Display::addFlash(Display::return_message(get_lang('Deleted')));
        header('Location: '.api_get_self());
        exit;
        break;
}

if (api_is_platform_admin()) {
    $actionLinks .= Display::toolbarButton(
        $plugin->get_lang('Add'),
        api_get_path(WEB_PLUGIN_PATH).'Justification/add.php',
        'plus',
        'primary'
    );
}

$actionLinks .= Display::toolbarButton(
    $plugin->get_lang('Users'),
    api_get_path(WEB_PLUGIN_PATH).'Justification/justification_by_user.php',
    'user',
    'primary'
);

if (api_is_platform_admin()) {
    $actionLinks .= Display::toolbarButton(
        $plugin->get_lang('SetNewCourse'),
        api_get_path(WEB_PLUGIN_PATH).'Justification/set_course.php',
        'book',
        'primary'
    );
}

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);

$tpl->assign('content', $content);
$tpl->display_one_col_template();
