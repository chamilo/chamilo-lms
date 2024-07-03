<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$tool = 'notification_event';

$tpl = new Template($tool);
$fields = [];

$manager = new NotificationEvent();

$form = new FormValidator('add');
$form = $manager->getAddForm($form);

if (isset($_POST) && isset($_POST['title']) && $form->validate()) {
    $values = $form->getSubmitValues();
    $manager->save($values);
    Display::addFlash(
        Display::return_message(get_lang('Saved'), 'success')
    );
    $url = api_get_path(WEB_CODE_PATH).'notification_event/list.php?';
    header('Location: '.$url);
    exit;
}

$actionLinks = Display::toolbarButton(
    get_lang('Back'),
    api_get_path(WEB_CODE_PATH).'notification_event/list.php',
    'arrow-left',
    'primary'
);

$tpl->assign(
    'actions',
    Display::toolbarAction('toolbar', [$actionLinks])
);

$content = $form->returnForm();

$tpl->assign('content', $content);
$tpl->display_one_col_template();
