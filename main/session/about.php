<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Repository\SequenceResourceRepository;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\UserBundle\Entity\User;

/**
 * Session about page
 * Show information about a session and its courses.
 *
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author Julio Montoya
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$sessionId = isset($_GET['session_id']) ? (int) $_GET['session_id'] : 0;

$em = Database::getManager();

$session = api_get_session_entity($sessionId);

if (!$session) {
    api_not_allowed(true);
}

if (api_is_multiple_url_enabled()) {
    $accessUrlId = api_get_current_access_url_id();
    $sessionOnUrl = UrlManager::relation_url_session_exist($sessionId, $accessUrlId);

    if (!$sessionOnUrl) {
        api_not_allowed(true);
    }
}

$htmlHeadXtra[] = api_get_asset('readmore-js/readmore.js');
$courses = [];
$sessionCourses = $em->getRepository('ChamiloCoreBundle:Session')->getCoursesOrderedByPosition($session);
$fieldsRepo = $em->getRepository('ChamiloCoreBundle:ExtraField');
$fieldTagsRepo = $em->getRepository('ChamiloCoreBundle:ExtraFieldRelTag');
$userRepo = UserManager::getRepository();
/** @var SequenceResourceRepository $sequenceResourceRepo */
$sequenceResourceRepo = $em->getRepository('ChamiloCoreBundle:SequenceResource');

$tagField = $fieldsRepo->findOneBy([
    'extraFieldType' => ExtraField::COURSE_FIELD_TYPE,
    'variable' => 'tags',
]);

$courseValues = new ExtraFieldValue('course');
$userValues = new ExtraFieldValue('user');
$sessionValues = new ExtraFieldValue('session');

/** @var Course $sessionCourse */
foreach ($sessionCourses as $sessionCourse) {
    $courseTags = [];

    if (!is_null($tagField)) {
        $courseTags = $fieldTagsRepo->getTags($tagField, $sessionCourse->getId());
    }

    $courseCoaches = $userRepo->getCoachesForSessionCourse($session, $sessionCourse);
    $coachesData = [];
    /** @var User $courseCoach */
    foreach ($courseCoaches as $courseCoach) {
        $coachData = [
            'complete_name' => UserManager::formatUserFullName($courseCoach),
            'image' => UserManager::getUserPicture(
                $courseCoach->getId(),
                USER_IMAGE_SIZE_ORIGINAL
            ),
            'diploma' => $courseCoach->getDiplomas(),
            'openarea' => $courseCoach->getOpenarea(),
            'extra_fields' => $userValues->getAllValuesForAnItem(
                $courseCoach->getId(),
                null,
                true
            ),
        ];

        $coachesData[] = $coachData;
    }

    $cd = new CourseDescription();
    $cd->set_course_id($sessionCourse->getId());
    $cd->set_session_id($session->getId());
    $descriptionsData = $cd->get_description_data();

    $courseDescription = [];
    $courseObjectives = [];
    $courseTopics = [];
    $courseMethodology = [];
    $courseMaterial = [];
    $courseResources = [];
    $courseAssessment = [];
    $courseCustom = [];

    if (!empty($descriptionsData['descriptions'])) {
        foreach ($descriptionsData['descriptions'] as $descriptionInfo) {
            switch ($descriptionInfo['description_type']) {
                case CCourseDescription::TYPE_DESCRIPTION:
                    $courseDescription[] = $descriptionInfo;
                    break;
                case CCourseDescription::TYPE_OBJECTIVES:
                    $courseObjectives[] = $descriptionInfo;
                    break;
                case CCourseDescription::TYPE_TOPICS:
                    $courseTopics[] = $descriptionInfo;
                    break;
                case CCourseDescription::TYPE_METHODOLOGY:
                    $courseMethodology[] = $descriptionInfo;
                    break;
                case CCourseDescription::TYPE_COURSE_MATERIAL:
                    $courseMaterial[] = $descriptionInfo;
                    break;
                case CCourseDescription::TYPE_RESOURCES:
                    $courseResources[] = $descriptionInfo;
                    break;
                case CCourseDescription::TYPE_ASSESSMENT:
                    $courseAssessment[] = $descriptionInfo;
                    break;
                case CCourseDescription::TYPE_CUSTOM:
                    $courseCustom[] = $descriptionInfo;
                    break;
            }
        }
    }

    $courses[] = [
        'course' => $sessionCourse,
        'description' => $courseDescription,
        'image' => CourseManager::getPicturePath($sessionCourse, true),
        'tags' => $courseTags,
        'objectives' => $courseObjectives,
        'topics' => $courseTopics,
        'methodology' => $courseMethodology,
        'material' => $courseMaterial,
        'resources' => $courseResources,
        'assessment' => $courseAssessment,
        'custom' => array_reverse($courseCustom),
        'coaches' => $coachesData,
        'extra_fields' => $courseValues->getAllValuesForAnItem(
            $sessionCourse->getId(),
            null,
            true
        ),
    ];
}

$sessionDates = SessionManager::parseSessionDates(
    [
        'display_start_date' => $session->getDisplayStartDate(),
        'display_end_date' => $session->getDisplayEndDate(),
        'access_start_date' => $session->getAccessStartDate(),
        'access_end_date' => $session->getAccessEndDate(),
        'coach_access_start_date' => $session->getCoachAccessStartDate(),
        'coach_access_end_date' => $session->getCoachAccessEndDate(),
    ],
    true
);

$requirements = $sequenceResourceRepo->getRequirements(
    $session->getId(),
    SequenceResource::SESSION_TYPE
);

$hasRequirements = false;
foreach ($requirements as $sequence) {
    if (!empty($sequence['requirements'])) {
        $hasRequirements = true;
        break;
    }
}

/* View */
$template = new Template($session->getName(), true, true, false, true, false);
$template->assign('show_tutor', ('true' === api_get_setting('show_session_coach') ? true : false));
$template->assign('page_url', api_get_path(WEB_PATH)."session/{$session->getId()}/about/");
$template->assign('session', $session);
$template->assign('session_date', $sessionDates);
$template->assign(
    'is_subscribed',
    SessionManager::isUserSubscribedAsStudent(
        $session->getId(),
        api_get_user_id()
    )
);
$template->assign(
    'subscribe_button',
    CoursesAndSessionsCatalog::getRegisteredInSessionButton(
        $session->getId(),
        $session->getName(),
        $hasRequirements,
        true,
        true
    )
);
$template->assign(
    'user_session_time',
    SessionManager::getDayLeftInSession(
        ['id' => $session->getId(), 'duration' => $session->getDuration()],
        api_get_user_id()
    )
);

$plugin = BuyCoursesPlugin::create();
$checker = $plugin->isEnabled();
$sessionIsPremium = null;
if ($checker) {
    $sessionIsPremium = $plugin->getItemByProduct(
        $sessionId,
        BuyCoursesPlugin::PRODUCT_TYPE_SESSION
    );
    if ($sessionIsPremium) {
        ChamiloSession::write('SessionIsPremium', true);
        ChamiloSession::write('sessionId', $sessionId);
    }
}

$redirectToSession = api_get_configuration_value('allow_redirect_to_session_after_inscription_about');
$redirectToSession = $redirectToSession ? '?s='.$sessionId : false;

$coursesInThisSession = SessionManager::get_course_list_by_session_id($sessionId);
$coursesCount = count($coursesInThisSession);
$redirectToSession = 1 == $coursesCount && $redirectToSession
    ? ($redirectToSession.'&cr='.array_values($coursesInThisSession)[0]['directory'])
    : $redirectToSession;

$template->assign('redirect_to_session', $redirectToSession);
$template->assign('courses', $courses);
$essence = Essence\Essence::instance();
$template->assign('essence', $essence);
$template->assign(
    'session_extra_fields',
    $sessionValues->getAllValuesForAnItem($session->getId(), null, true)
);
$template->assign('has_requirements', $hasRequirements);
$template->assign('sequences', $requirements);
$template->assign('is_premium', $sessionIsPremium);
$layout = $template->get_template('session/about.tpl');
$content = $template->fetch($layout);
$template->assign('content', $content);
$template->display_one_col_template();
