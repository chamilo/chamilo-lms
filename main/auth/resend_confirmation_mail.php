<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

// Build the form
$form = new FormValidator('resend');
$form->addElement('header', get_lang('ReSendConfirmationMail'));
$form->addText('user', get_lang('UserName'), true);
$form->addButtonSend(get_lang('Send'));

if ($form->validate()) {
    $values = $form->exportValues();

    /** @var \Chamilo\UserBundle\Entity\User $thisUser */
    $thisUser = Database::getManager()->getRepository('ChamiloUserBundle:User')->findBy(['username' => $values['user']]);

    UserManager::sendUserConfirmationMail($thisUser);
    Display::addFlash(Display::return_message(get_lang('EmailSent')));
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

$tpl = new Template(null);
$tpl->assign('form', $form->toHtml());
$content = $tpl->get_template('auth/resend_confirmation_mail.tpl');
$tpl->assign('content', $tpl->fetch($content));
$tpl->display_one_col_template();
