<?php
/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\UserBundle\Entity\Repository\UserRepository;
use Chamilo\UserBundle\Entity\User;
use ChamiloSession as Session;

/**
 * Session about page
 * Show information about a session and its courses
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @author Julio Montoya
 *
 * @package chamilo.session
 */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

$em = Database::getManager();

$session = $em->find('ChamiloCoreBundle:Session', $sessionId);

if (!$session) {
    api_not_allowed(true);
}

$courses = [];
$sessionCourses = $em->getRepository('ChamiloCoreBundle:Session')->getCoursesOrderedByPosition($session);
$fieldsRepo = $em->getRepository('ChamiloCoreBundle:ExtraField');
$fieldTagsRepo = $em->getRepository('ChamiloCoreBundle:ExtraFieldRelTag');
/** @var UserRepository $userRepo */
$userRepo = $em->getRepository('ChamiloUserBundle:User');
$sequenceResourceRepo = $em->getRepository('ChamiloCoreBundle:SequenceResource');

$tagField = $fieldsRepo->findOneBy([
    'extraFieldType' => ExtraField::COURSE_FIELD_TYPE,
    'variable' => 'tags'
]);

$courseValues = new ExtraFieldValue('course');
$userValues = new ExtraFieldValue('user');
$sessionValues = new ExtraFieldValue('session');

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
            'complete_name' => $courseCoach->getCompleteName(),
            'image' => UserManager::getUserPicture(
                $courseCoach->getId(),
                USER_IMAGE_SIZE_ORIGINAL
            ),
            'extra_fields' => $userValues->getAllValuesForAnItem(
                $courseCoach->getId(),
                null,
                true
            ),
        ];

        $coachesData[] = $coachData;
    }

    $courseDescriptionTools = $em->getRepository('ChamiloCourseBundle:CCourseDescription')
        ->findBy(
            [
                'cId' => $sessionCourse->getId(),
                'sessionId' => 0
            ],
            [
                'id' => 'DESC',
                'descriptionType' => 'ASC'
            ]
        );

    $courseDescription = $courseObjectives = $courseTopics = $courseMethodology = $courseMaterial = $courseResources = $courseAssessment = '';
    $courseCustom = [];
    foreach ($courseDescriptionTools as $descriptionTool) {
        switch ($descriptionTool->getDescriptionType()) {
            case CCourseDescription::TYPE_DESCRIPTION:
                $courseDescription = $descriptionTool;
                break;
            case CCourseDescription::TYPE_OBJECTIVES:
                $courseObjectives = $descriptionTool;
                break;
            case CCourseDescription::TYPE_TOPICS:
                $courseTopics = $descriptionTool;
                break;
            case CCourseDescription::TYPE_METHODOLOGY:
                $courseMethodology = $descriptionTool;
                break;
            case CCourseDescription::TYPE_COURSE_MATERIAL:
                $courseMaterial = $descriptionTool;
                break;
            case CCourseDescription::TYPE_RESOURCES:
                $courseResources = $descriptionTool;
                break;
            case CCourseDescription::TYPE_ASSESMENT:
                $courseAssessment = $descriptionTool;
                break;
            case CCourseDescription::TYPE_CUSTOM:
                $courseCustom[] = $descriptionTool;
                break;
        }
    }

    $courses[] = [
        'course' => $sessionCourse,
        'description' => $courseDescription,
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

$sessionRequirements = $sequenceResourceRepo->getRequirements(
    $session->getId(),
    SequenceResource::SESSION_TYPE
);

$hasRequirements = false;
foreach ($sessionRequirements as $sequence) {
    if (!empty($sequence['requirements'])) {
        $hasRequirements = true;
        break;
    }
}

$courseController = new CoursesController();

/* View */
$template = new Template($session->getName(), true, true, false, true, false);
$template->assign('show_tutor', (api_get_setting('show_session_coach') === 'true' ? true : false));
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
    $courseController->getRegisteredInSessionButton(
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

if ($checker) {
    $sessionIsPremium = $plugin->getItemByProduct(
        $sessionId,
        BuyCoursesPlugin::PRODUCT_TYPE_SESSION
    );
    if ($sessionIsPremium) {
        Session::write('SessionIsPremium', true);
        Session::write('sessionId', $sessionId);
    }
}

$redirectToSession = api_get_configuration_value('allow_redirect_to_session_after_inscription_about');
$redirectToSession = $redirectToSession ? '?s='.$sessionId : false;

$coursesInThisSession = SessionManager::get_course_list_by_session_id($sessionId);
$coursesCount = count($coursesInThisSession);
$redirectToSession = $coursesCount == 1 && $redirectToSession ? $redirectToSession.'&cr='.array_values($coursesInThisSession)[0]['directory'] : $redirectToSession;

$template->assign('redirect_to_session', $redirectToSession);
$template->assign('courses', $courses);
$essence = Essence\Essence::instance();
$template->assign('essence', $essence);
$template->assign(
    'session_extra_fields',
    $sessionValues->getAllValuesForAnItem($session->getId(), null, true)
);
$template->assign('has_requirements', $hasRequirements);
$template->assign('sequences', $sessionRequirements);
$layout = $template->get_template('session/about.tpl');
$content = $template->fetch($layout);
$template->assign('header', $session->getName());
$template->assign('content', $content);
$template->display_one_col_template();
