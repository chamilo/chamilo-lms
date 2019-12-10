<?php

require_once __DIR__.'/../../main/inc/global.inc.php';

$tool = 'justification';
$plugin = Justification::create();

$tpl = new Template($tool);
$fields = [];

$list = $plugin->getList();

$tpl->assign('list', $list);

$content = $tpl->fetch('justification/view/list.tpl');

$actionLinks = '';
if (api_is_platform_admin()) {
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

    $actionLinks .= Display::toolbarButton(
        $plugin->get_lang('Add'),
        api_get_path(WEB_PLUGIN_PATH).'justification/add.php',
        'plus',
        'primary'
    );

    $tpl->assign(
        'actions',
        Display::toolbarAction('toolbar', [$actionLinks])
    );
}

$tpl->assign('content', $content);
$tpl->display_one_col_template();
