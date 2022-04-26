<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$tool = 'notification_event';

$tpl = new Template($tool);
$fields = [];
$manager = new NotificationEvent();

$list = $manager->get_all();

foreach ($list as &$item) {
    $item['event_type'] = $manager->eventTypeToString($item['event_type']);
}
$tpl->assign('list', $list);

$content = $tpl->fetch($tpl->get_template('notification_event/list.tpl'));

$actionLinks = '';
$action = $_REQUEST['a'] ?? '';
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

if ($action == 'delete') {
    $manager->delete($id);

    Display::addFlash(
        Display::return_message(get_lang('Deleted'), 'success')
    );
    header('Location: '.api_get_self());
    exit;
}

$actionLinks .= Display::toolbarButton(
    get_lang('Add'),
    api_get_path(WEB_CODE_PATH).'notification_event/add.php',
    'plus',
    'primary'
);

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);

$tpl->assign('content', $content);
$tpl->display_one_col_template();
