<?php

declare(strict_types=1);

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\XApiToolLaunch;
use Chamilo\CoreBundle\Framework\Container;

require_once __DIR__.'/../../../main/inc/global.inc.php';

$request = Container::getRequest();

$course = api_get_course_entity();
$session = api_get_session_entity();

if (
    !$request->isXmlHttpRequest()
    || !api_is_allowed_to_edit()
    || !$course
) {
    echo Display::return_message(
        get_lang('You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.'),
        'error'
    );
    exit;
}

$plugin = XApiPlugin::create();
$em = Database::getManager();

$toolLaunch = $em->find(
    XApiToolLaunch::class,
    $request->request->getInt('tool')
);

$student = api_get_user_entity($request->request->getInt('student'));

if (!$toolLaunch || !$student) {
    echo Display::return_message(
        get_lang('You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.'),
        'error'
    );
    exit;
}

$userIsSubscribedToCourse = CourseManager::is_user_subscribed_in_course(
    $student->getId(),
    $course->getCode(),
    (bool) $session,
    $session ? $session->getId() : 0
);

if (!$userIsSubscribedToCourse) {
    echo Display::return_message(
        get_lang('You are not allowed to see this page. Either your connection has expired or you are trying to access a page for which you do not have the sufficient privileges.'),
        'error'
    );
    exit;
}

$actor = $plugin->buildTinCanActorPayload($student);
$stateId = $plugin->getTinCanStateId($toolLaunch->getId());

try {
    $stateDocument = $plugin->fetchActivityStateDocument(
        (string) $toolLaunch->getActivityId(),
        $actor,
        $stateId,
        null,
        $toolLaunch->getLrsUrl(),
        $toolLaunch->getLrsAuthUsername(),
        $toolLaunch->getLrsAuthPassword()
    );
} catch (Exception $exception) {
    echo Display::return_message($exception->getMessage(), 'error');
    exit;
}

if (empty($stateDocument) || !is_array($stateDocument)) {
    echo Display::return_message(get_lang('No results found'), 'warning');
    exit;
}

$content = '';

foreach ($stateDocument as $attemptId => $attempt) {
    if (!is_array($attempt)) {
        continue;
    }

    $firstLaunch = !empty($attempt[XApiPlugin::STATE_FIRST_LAUNCH])
        ? api_convert_and_format_date(
            $attempt[XApiPlugin::STATE_FIRST_LAUNCH],
            DATE_TIME_FORMAT_LONG
        )
        : '-';

    $lastLaunch = !empty($attempt[XApiPlugin::STATE_LAST_LAUNCH])
        ? api_convert_and_format_date(
            $attempt[XApiPlugin::STATE_LAST_LAUNCH],
            DATE_TIME_FORMAT_LONG
        )
        : '-';

    $content .= '<dl class="dl-horizontal">'
        .'<dt>'.$plugin->get_lang('ActivityFirstLaunch').'</dt>'
        .'<dd>'.$firstLaunch.'</dd>'
        .'<dt>'.$plugin->get_lang('ActivityLastLaunch').'</dt>'
        .'<dd>'.$lastLaunch.'</dd>'
        .'</dl>'
        .Display::toolbarButton(
            get_lang('ShowAllAttempts'),
            '#',
            'th-list',
            'default',
            [
                'class' => 'btn_xapi_attempt_detail',
                'data-attempt' => (string) $attemptId,
                'data-tool' => $toolLaunch->getId(),
                'style' => 'margin-bottom: 20px; margin-left: 180px;',
                'role' => 'button',
            ]
        );
}

echo '' !== $content
    ? $content
    : Display::return_message(get_lang('No results found'), 'warning');
