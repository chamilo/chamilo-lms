<?php
/* For license terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!ctype_alnum($token)) {
    $token = '';
}

/** @var \Chamilo\UserBundle\Entity\User $user */
$user = UserManager::getManager()->findUserByConfirmationToken($token);

if ($user) {
    $user->setActive(1); // Setted 1 to active the user
    $user->setConfirmationToken(null);

    Database::getManager()->persist($user);
    Database::getManager()->flush();

    Display::addFlash(Display::return_message(get_lang('UserConfirmedNowYouCanLogInThePlatform'), 'success'));
    header('Location: '.api_get_path(WEB_PATH));
    exit;
} else {
    Display::addFlash(
        Display::return_message(get_lang('LinkExpired'))
    );
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}
