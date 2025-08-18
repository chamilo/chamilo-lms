<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This script contains the code to send an e-mail to the portal admin.
 */
require_once __DIR__.'/../inc/global.inc.php';

if ('false' === api_get_setting('mail.allow_email_editor_for_anonymous')) {
    api_not_allowed(true);
}

$originUrl = Session::read('origin_url');
if (empty($originUrl) && isset($_SERVER['HTTP_REFERER'])) {
    Session::write('origin_url', $_SERVER['HTTP_REFERER']);
}

$action = isset($_GET['action']) ? $_GET['action'] : null;

$form = new FormValidator('email_editor', 'post');
$form->addText('email', get_lang('E-mail'));
$form->addRule('email', get_lang('The email address is not complete or contains some invalid characters'), 'email');
$form->addText('email_title', get_lang('Subject'));
$form->addTextarea('email_text', get_lang('Message'), ['rows' => '6'], true);
$form->addCaptcha();
$form->addButtonSend(get_lang('Send email'));

$emailTitle = isset($_REQUEST['subject']) ? Security::remove_XSS($_REQUEST['subject']) : '';
$emailText = isset($_REQUEST['body']) ? Security::remove_XSS($_REQUEST['body']) : '';

$defaults = [
    'email_title' => $emailTitle,
    'email_text' => $emailText,
];

if (isset($_POST)) {
    $defaults = [
        'email' => $_REQUEST['email'] ?? null,
        'email_title' => $_REQUEST['email_title'] ?? null,
        'email_text' => $_REQUEST['email_text'] ?? null,
    ];
}

$form->setDefaults($defaults);
if ($form->validate()) {
    $values = $form->getSubmitValues();
    $message =
        get_lang('sender').': '.$values['email'].'<br /><br />'.
        nl2br($values['email_text']).
        '<br /><br /><br />'.get_lang('E-mail sent from the platform').' '.api_get_path(WEB_PATH);

    api_mail_html(
        '',
        api_get_setting('emailAdministrator'),
        $values['email_title'],
        $message,
        get_lang('Anonymous')
    );

    Display::addFlash(Display::return_message(get_lang('Message Sent')));
    $orig = Session::read('origin_url');
    Session::erase('origin_url');
    header('Location:'.$orig);
    exit;
}

Display::display_header(get_lang('Send email'));
$form->display();
Display::display_footer();
