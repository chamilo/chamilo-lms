<?php
/* For license terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../inc/global.inc.php';

$token = (string) ($_GET['token'] ?? '');
$token = trim($token);

// Allow typical tokens (alnum, "_" and "-") to avoid rejecting UUID-like tokens.
if ($token === '' || !preg_match('/^[a-zA-Z0-9_-]+$/', $token)) {
    $token = '';
}

/** @var User|null $user */
$user = null;
if ($token !== '') {
    $user = Container::getUserRepository()->findOneBy(['confirmationToken' => $token]);
}

if ($user) {
    $user->setActive(1);
    $user->setConfirmationToken(null);

    Database::getManager()->persist($user);
    Database::getManager()->flush();

    // Default redirect
    $url = api_get_path(WEB_PATH);

    $courseId = !empty($_GET['c']) ? (int) $_GET['c'] : 0;
    $sessionId = !empty($_GET['s']) ? (int) $_GET['s'] : 0;

    $courseCode = '';
    if ($courseId > 0) {
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseCode = (string) ($courseInfo['code'] ?? $courseInfo['course_code'] ?? $courseInfo['directory'] ?? '');
    }

    // Get URL to a course (in session), to a session, or empty.
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
        Display::return_message(get_lang('User confirmed. Now you can login the platform.'), 'success')
    );

    header('Location: '.$url);
    exit;
}

Display::addFlash(
    Display::return_message(get_lang('Link expired, please try again.'))
);
header('Location: '.api_get_path(WEB_PATH));
exit;
