<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

$plugin = Justification::create();
$allowSessionAdmins = $plugin->canSessionAdminsManageUsers();

api_protect_admin_script($allowSessionAdmins);

$tool = 'justification';
$tpl = new Template($tool);

$action = isset($_REQUEST['a']) ? $_REQUEST['a'] : '';
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

switch ($action) {
    case 'delete':
        if (!api_is_platform_admin()) {
            api_not_allowed(true);
        }

        if (!Security::check_token('get')) {
            api_not_allowed(true);
        }

        Security::clear_token();

        if ($id > 0) {
            Database::query('DELETE FROM '.Justification::TABLE_DOCUMENT.' WHERE id = '.$id);
            Display::addFlash(Display::return_message(get_lang('Deleted')));
        }

        header('Location: '.api_get_self());
        exit;
}

$list = $plugin->getList();

$tpl->assign('list', $list);
$tpl->assign('token', Security::get_token());
$tpl->assign('can_manage_documents', api_is_platform_admin());

$content = $tpl->fetch('Justification/view/list.tpl');

$actionLinks = '';

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

$actionLinks .= Display::toolbarButton(
    $plugin->get_lang('MyJustifications'),
    api_get_path(WEB_PLUGIN_PATH).'Justification/upload.php',
    'upload',
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
