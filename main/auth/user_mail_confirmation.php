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
    $user->setActive(1); // Set to 1 to activate the user
    $user->setConfirmationToken(null);

    Database::getManager()->persist($user);
    Database::getManager()->flush();

    // See where to redirect the user to, if any redirection has been set
    $url = api_get_path(WEB_PATH);

    if (!empty($_GET['c'])) {
        $courseCode = Security::remove_XSS($_GET['c']);
    }
    if (!empty($_GET['s'])) {
        $sessionId = (int) $_GET['s'];
    }

    // Get URL to a course, to a session, or an empty string
    $courseUrl = api_get_course_url($courseCode, $sessionId);
    if (!empty($courseUrl)) {
        $url = $courseUrl;
    }

    Event::addEvent(
        LOG_USER_CONFIRMED_EMAIL,
        LOG_USER_OBJECT,
        api_get_user_info($user->getId()),
        api_get_utc_datetime()
    );

    Display::addFlash(
        Display::return_message(get_lang('UserConfirmedNowYouCanLogInThePlatform'), 'success')
    );
    header('Location: '.$url);
    exit;
} else {
    Display::addFlash(
        Display::return_message(get_lang('LinkExpired'))
    );
    header('Location: '.api_get_path(WEB_PATH));
    exit;
}
