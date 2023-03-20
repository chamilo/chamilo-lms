<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

// Build the form
$form = new FormValidator('resend');
$form->addHeader(get_lang('Send message confirmation mail again'));
$form->addText('user', get_lang('Username'), true);
$form->addButtonSend(get_lang('Send message'));

if ($form->validate()) {
    $values = $form->exportValues();
    $user = UserManager::getRepository()->findUserByUsername($values['user']);
    if ($user) {
        UserManager::sendUserConfirmationMail($user);
    } else {
        Display::addFlash(Display::return_message(get_lang('This user doesn\'t exist')));
    }

    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$tpl = new Template(null);
$tpl->assign('content', $form->toHtml());
$tpl->display_one_col_template();
