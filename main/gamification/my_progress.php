<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Repository\TrackECourseAccessRepository;

/**
 * See the progress for a user when the gamification mode is active.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_TRACKING;
$nameTools = get_lang('MyProgress');

api_block_anonymous_users();

if (api_get_setting('gamification_mode') == '0') {
    api_not_allowed(true);
}

$userId = api_get_user_id();
$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
$allowAccess = false;

$entityManager = Database::getManager();
$user = api_get_user_entity($userId);

if (empty($sessionId) && $user) {
    /** @var TrackECourseAccessRepository $trackCourseAccessRepository */
    $trackCourseAccessRepository = $entityManager->getRepository('ChamiloCoreBundle:TrackECourseAccess');
    $lastCourseAccess = $trackCourseAccessRepository->getLastAccessByUser($user);
    $lastSessionId = 0;
    if ($lastCourseAccess) {
        $lastSessionId = $lastCourseAccess->getSessionId();
    }

    $UserIsSubscribedToSession = SessionManager::isUserSubscribedAsStudent($lastSessionId, $user->getId());

    if (!empty($lastSessionId) && $UserIsSubscribedToSession) {
        $urlWithSession = api_get_self().'?'.http_build_query([
            'session_id' => $lastCourseAccess->getSessionId(),
        ]);

        header("Location: $urlWithSession");
        exit;
    }
}

$sessionCourseSubscriptions = $user->getSessionCourseSubscriptions();
$currentSession = api_get_session_entity($sessionId);

$sessionList = [];
foreach ($sessionCourseSubscriptions as $subscription) {
    $session = $subscription->getSession();

    if (array_key_exists($session->getId(), $sessionList)) {
        continue;
    }

    if ($currentSession && $currentSession->getId() === $session->getId()) {
        $allowAccess = true;
    }

    $sessionList[$session->getId()] = $session;
}

if ($currentSession && !$allowAccess) {
    api_not_allowed(true);
}

$template = new Template($nameTools);
$template->assign('user', $user);
$template->assign(
    'user_avatar',
    SocialManager::show_social_avatar_block('home', 0, $user->getId())
);
$template->assign(
    'gamification_stars',
    GamificationUtils::getTotalUserStars($user->getId(), $user->getStatus())
);
$template->assign(
    'gamification_points',
    GamificationUtils::getTotalUserPoints($user->getId(), $user->getStatus())
);
$template->assign(
    'gamification_progress',
    GamificationUtils::getTotalUserProgress($user->getId(), $user->getStatus())
);
$template->assign('sessions', $sessionList);
$template->assign('current_session', $currentSession);

if ($currentSession) {
    $sessionData = [];
    $sessionCourses = $currentSession->getCourses();

    foreach ($sessionCourses as $sessionCourse) {
        $course = $sessionCourse->getCourse();

        $courseData = [
            'title' => $course->getTitle(),
            'stats' => [],
        ];

        $courseInfo = api_get_course_info($course->getCode());
        $learningPathList = new LearnpathList(
            $user->getId(),
            $courseInfo,
            $currentSession->getId()
        );

        foreach ($learningPathList->list as $learningPathId => $learningPath) {
            $courseData['stats'][] = [
                $learningPath['lp_name'],
                'lp/lp_controller.php?'.http_build_query([
                    'action' => 'stats',
                    'cidReq' => $course->getCode(),
                    'id_session' => $currentSession->getId(),
                    'gidReq' => 0,
                    'lp_id' => $learningPathId,
                ]).api_get_cidreq(),
            ];
        }
        $sessionData[$course->getId()] = $courseData;
    }
    $template->assign('session_data', $sessionData);
}

$layout = $template->get_template('gamification/my_progress.tpl');

$template->assign('header', $nameTools);
$template->assign('content', $template->fetch($layout));
$template->display_one_col_template();
