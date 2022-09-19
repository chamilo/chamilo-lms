<?php

/* For license terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$token = $_GET['token'] ?? '';

if (!ctype_alnum($token)) {
    $token = '';
}

$user = UserManager::getManager()->findUserByConfirmationToken($token);

if (!$user) {
    Display::addFlash(
        Display::return_message(get_lang('LinkExpired'), 'error')
    );

    header('Location: '.api_get_path(WEB_PATH));
    exit;
}

// Build the form
$form = new FormValidator('reset', 'POST', api_get_self().'?token='.$token);
$form->addElement('header', get_lang('ResetPassword'));
$form->addHidden('token', $token);
$form->addElement(
    'password',
    'pass1',
    get_lang('Password'),
    [
        'show_hide' => true,
    ]
);
$form->addElement(
    'password',
    'pass2',
    get_lang('Confirmation'),
    ['id' => 'pass2', 'size' => 20, 'autocomplete' => 'off']
);
$form->addRule('pass1', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('pass2', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule(['pass1', 'pass2'], get_lang('PassTwo'), 'compare');
$form->addPasswordRule('pass1');
$form->addNoSamePasswordRule('pass1', $user);
$form->addButtonSave(get_lang('Update'));

$ttl = api_get_setting('user_reset_password_token_limit');
if (empty($ttl)) {
    $ttl = 3600;
}

if ($form->validate()) {
    $values = $form->exportValues();
    $password = $values['pass1'];
    $token = $values['token'];

    /** @var \Chamilo\UserBundle\Entity\User $user */
    $user = UserManager::getManager()->findUserByConfirmationToken($token);

    if ($user) {
        if (!$user->isPasswordRequestNonExpired($ttl)) {
            Display::addFlash(Display::return_message(get_lang('LinkExpired')), 'warning');
            header('Location: '.api_get_path(WEB_CODE_PATH).'auth/lostPassword.php');
            exit;
        }

        $user->setPlainPassword($password);
        $userManager = UserManager::getManager();
        $userManager->updateUser($user, true);

        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);

        Database::getManager()->persist($user);
        Database::getManager()->flush();

        if (api_get_configuration_value('force_renew_password_at_first_login')) {
            $extraFieldValue = new ExtraFieldValue('user');
            $value = $extraFieldValue->get_values_by_handler_and_field_variable($user->getId(), 'ask_new_password');
            if (!empty($value) && isset($value['value']) && 1 === (int) $value['value']) {
                $extraFieldValue->delete($value['id']);
            }
        }

        Display::addFlash(Display::return_message(get_lang('Updated')));
        header('Location: '.api_get_path(WEB_PATH));
        exit;
    } else {
        Display::addFlash(
            Display::return_message(get_lang('LinkExpired'))
        );
    }
}

$htmlHeadXtra[] = api_get_password_checker_js('#username', '#reset_pass1');

$tpl = new Template(null);
$tpl->assign('content', $form->toHtml());
$tpl->display_one_col_template();
