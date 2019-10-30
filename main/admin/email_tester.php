<?php
/* For licensing terms, see /license.txt */
/**
 * Index page of the admin tools.
 *
 * @package chamilo.admin
 */
// Resetting the course id.
$cidReset = true;

// Including some necessary chamilo files.
require_once __DIR__.'/../inc/global.inc.php';

api_protect_admin_script();

// Setting the section (for the tabs).
$this_section = SECTION_PLATFORM_ADMIN;
$toolName = get_lang('E-mail tester');

$form = new FormValidator('email_tester');
$form->addText('smtp_host', get_lang('Host'), false, ['cols-size' => [2, 8, 2]]);
$form->addText('smtp_port', get_lang('Port'), false, ['cols-size' => [2, 8, 2]]);
$form->addText('destination', get_lang('Destination'), true, ['cols-size' => [2, 8, 2]]);
$form->addText('subject', get_lang('Subject'), true, ['cols-size' => [2, 8, 2]]);
$form->addHtmlEditor(
    'content',
    get_lang('Message'),
    true,
    false,
    ['ToolbarSet' => 'Minimal', 'cols-size' => [2, 8, 2]]
);
$form->addButtonSend(get_lang('Send message'), 'submit', false, ['cols-size' => [2, 8, 2]]);

$errorsInfo = MessageManager::failedSentMailErrors();

if ($form->validate()) {
    $values = $form->exportValues();

    $user = api_get_user_entity(api_get_user_id());

    $mailIsSent = api_mail_html(
        get_lang('User testing of e-mail configuration'),
        $values['destination'],
        $values['subject'],
        $values['content'],
        UserManager::formatUserFullName($user),
        $user->getEmail()
    );

    Display::addFlash(
        Display::return_message(get_lang('E-mail sent. This procedure works in all aspects similarly to the normal e-mail sending of Chamilo, but allows for more flexibility in terms of destination e-mail and message body.'), 'success')
    );

    header('Location: '.api_get_self());
    exit;
}

$view = new Template($toolName);
$view->assign('form', $form->returnForm());
$view->assign('errors', $errorsInfo);

$template = $view->get_template('admin/email_tester.tpl');
$content = $view->fetch($template);

$view->assign('header', $toolName);
$view->assign('content', $content);
$view->display_one_col_template();
