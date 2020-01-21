<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This script contains the code to edit and send an e-mail to one of
 * the platform's users.
 * It can be called from the JavaScript library email_links.lib.php which
 * overtakes the mailto: links to use the internal interface instead.
 *
 * @author	Yannick Warnier <ywarnier@beeznest.org>
 * @author Julio Montoya <gugli100@gmail.com> Updating form with formvalidator
 */
require_once __DIR__.'/../inc/global.inc.php';

if (empty(api_get_user_id())) {
    api_not_allowed(true);
}

$_user = api_get_user_info();

$originUrl = Session::read('origin_url');
if (empty($originUrl)) {
    Session::write('origin_url', $_SERVER['HTTP_REFERER']);
}

$action = isset($_GET['action']) ? $_GET['action'] : null;

$form = new FormValidator('email_editor', 'post');
$form->addElement('hidden', 'dest');
$form->addElement('text', 'email_address', get_lang('EmailDestination'));
$form->addElement('text', 'email_title', get_lang('EmailTitle'));
$form->freeze('email_address');
$form->addElement('textarea', 'email_text', get_lang('EmailText'), ['rows' => '6']);
$form->addRule('email_address', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('email_title', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('email_text', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('email_address', get_lang('EmailWrong'), 'email');
$form->addButtonSend(get_lang('SendMail'));

switch ($action) {
    case 'subscribe_me_to_session':
        $sessionName = isset($_GET['session']) ? Security::remove_XSS($_GET['session']) : null;

        $objTemplate = new Template();
        $objTemplate->assign('session_name', $sessionName);
        $objTemplate->assign('user', api_get_user_info(api_get_user_id(), false, false, true));
        $mailTemplate = $objTemplate->get_template('mail/subscribe_me_to_session.tpl');

        $emailDest = api_get_setting('emailAdministrator');
        $emailTitle = get_lang('SubscribeToSessionRequest');
        $emailText = $objTemplate->fetch($mailTemplate);
        break;
    default:
        $emailDest = isset($_REQUEST['dest']) ? Security::remove_XSS($_REQUEST['dest']) : '';
        $emailTitle = isset($_REQUEST['subject']) ? Security::remove_XSS($_REQUEST['subject']) : '';
        $emailText = isset($_REQUEST['body']) ? Security::remove_XSS($_REQUEST['body']) : '';
        break;
}

$defaults = [
    'dest' => $emailDest,
    'email_address' => $emailDest,
    'email_title' => $emailTitle,
    'email_text' => $emailText,
];
$form->setDefaults($defaults);

if ($form->validate()) {
    $check = Security::check_token();
    Security::clear_token();
    if ($check) {
        Security::clear_token();
        $values = $form->getSubmitValues();
        $text = nl2br($values['email_text']).'<br /><br /><br />'.get_lang('EmailSentFromLMS').' '.api_get_path(
                WEB_PATH
            );
        $email_administrator = $values['dest'];
        $title = $values['email_title'];

        if (!empty($_user['mail'])) {
            api_mail_html(
                '',
                $email_administrator,
                $title,
                $text,
                api_get_person_name($_user['firstname'], $_user['lastname']),
                $_user['mail'],
                [
                    'reply_to' => [
                        'mail' => $_user['mail'],
                        'name' => api_get_person_name($_user['firstname'], $_user['lastname']),
                    ],
                ]
            );
        } else {
            api_mail_html(
                '',
                $email_administrator,
                $title,
                $text,
                get_lang('Anonymous')
            );
        }

        Display::addFlash(Display::return_message(get_lang('MessageSent')));
        $orig = Session::read('origin_url');
        Session::erase('origin_url');
        header('Location:'.$orig);
        exit;
    }
}

$form->addHidden('sec_token', Security::get_token());

Display::display_header(get_lang('SendEmail'));
$form->display();
Display::display_footer();
