<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

$tool = 'notification_event';

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

if (empty($id)) {
    api_not_allowed(true);
}

$manager = new NotificationEvent();

$notification = $manager->get($id);

if (empty($notification)) {
    api_not_allowed(true);
}

$tpl = new Template($tool);
$fields = [];

$form = new FormValidator('edit', 'post', api_get_self().'?id='.$id);
$form = $manager->getForm($form, $notification);

$notification['users'] = array_keys($notification['users']);
$form->setDefaults($notification);
$form->addButtonSave(get_lang('Update'));

if ($form->validate()) {
    $values = $form->getSubmitValues();
    $values['id'] = $id;
    $values['persistent'] = isset($values['persistent']) ? 1 : 0;
    $manager->update($values);
    Display::addFlash(
        Display::return_message(get_lang('Updated'), 'success')
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
