<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!ctype_alnum($token)) {
    $token = '';
}

// Build the form
$form = new FormValidator('reset', 'POST', api_get_self().'?token='.$token);
$form->addElement('header', get_lang('Reset password'));
$form->addHidden('token', $token);
$form->addElement('password', 'pass1', get_lang('Password'));
$form->addElement(
    'password',
    'pass2',
    get_lang('Confirm password'),
    ['id' => 'pass2', 'size' => 20, 'autocomplete' => 'off']
);
$form->addRule('pass1', get_lang('Required field'), 'required');
$form->addRule('pass2', get_lang('Required field'), 'required');
$form->addRule(['pass1', 'pass2'], get_lang('You have typed two different passwords'), 'compare');
$form->addButtonSave(get_lang('Update'));

$ttl = api_get_setting('user_reset_password_token_limit');
if (empty($ttl)) {
    $ttl = 3600;
}

if ($form->validate()) {
    $values = $form->exportValues();
    $password = $values['pass1'];
    $token = $values['token'];

    /** @var \Chamilo\CoreBundle\Entity\User $user */
    $user = UserManager::getRepository()->findUserByConfirmationToken($token);
    if ($user) {
        if (!$user->isPasswordRequestNonExpired($ttl)) {
            Display::addFlash(Display::return_message(get_lang('Link expired, please try again.')), 'warning');
            header('Location: '.api_get_path(WEB_CODE_PATH).'auth/lostPassword.php');
            exit;
        }

        $user->setPlainPassword($password);
        $userManager = UserManager::getRepository();
        $userManager->updateUser($user, true);

        $user->setConfirmationToken(null);
        $user->setPasswordRequestedAt(null);

        Database::getManager()->persist($user);
        Database::getManager()->flush();

        Display::addFlash(Display::return_message(get_lang('Update successful')));
        header('Location: '.api_get_path(WEB_PATH));
        exit;
    } else {
        Display::addFlash(
            Display::return_message(get_lang('Link expired, please try again.'))
        );
    }
}

$tpl = new Template(null);
$tpl->assign('content', $form->toHtml());
$tpl->display_one_col_template();
