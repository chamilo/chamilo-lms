<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!ctype_alnum($token)) {
    $token = '';
}


// Build the form
$form = new FormValidator('reset', 'POST', api_get_self().'?token='.$token);
$form->addElement('header', get_lang('ResetPassword'));
$form->addHidden('token', $token);
$form->addElement('password', 'pass1', get_lang('Password'));
$form->addElement(
    'password',
    'pass2',
    get_lang('Confirmation'),
    array('id' => 'pass2', 'size' => 20, 'autocomplete' => 'off')
);
$form->addRule('pass1', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('pass2', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule(array('pass1', 'pass2'), get_lang('PassTwo'), 'compare');
$form->addButtonSave(get_lang('Update'));

$ttl = api_get_setting('user_reset_password_token_limit');
if (empty($ttl)) {
    $ttl = 3600;
}

if ($form->validate()) {
    $em = Database::getManager();
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

        Display::addFlash(Display::return_message(get_lang('Updated')));
        header('Location: '.api_get_path(WEB_PATH));
        exit;
    } else {
        Display::addFlash(
            Display::return_message(get_lang('LinkExpired'))
        );
    }
}

$tpl = new Template(null);
$tpl->assign('form', $form->toHtml());
$content = $tpl->get_template('auth/set_temp_password.tpl');
$tpl->assign('content', $tpl->fetch($content));
$tpl->display_one_col_template();

