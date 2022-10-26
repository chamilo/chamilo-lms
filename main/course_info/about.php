<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\ExtraField;
use Chamilo\CoreBundle\Entity\Repository\SequenceResourceRepository;
use Chamilo\CoreBundle\Entity\SequenceResource;
use Chamilo\CourseBundle\Entity\CCourseDescription;

/**
 * Course about page
 * Show information about a course.
 *
 * @author Alex Aragon Calixto <alex.aragon@beeznest.com>
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

if (empty($courseId)) {
    api_not_allowed(true);
}

$token = Security::get_existing_token();
$em = Database::getManager();

$userId = api_get_user_id();
$course = api_get_course_entity($courseId);

if (!$course) {
    api_not_allowed(true);
}

$userRepo = UserManager::getRepository();
$fieldsRepo = $em->getRepository('ChamiloCoreBundle:ExtraField');
$fieldTagsRepo = $em->getRepository('ChamiloCoreBundle:ExtraFieldRelTag');

/** @var CCourseDescription $courseDescription */
$courseDescriptionTools = $em->getRepository('ChamiloCourseBundle:CCourseDescription')
    ->findBy(
        [
            'cId' => $course->getId(),
            'sessionId' => 0,
        ],
        [
            'id' => 'DESC',
            'descriptionType' => 'ASC',
        ]
    );

$courseValues = new ExtraFieldValue('course');
$userValues = new ExtraFieldValue('user');

$urlCourse = api_get_path(WEB_PATH)."course/$courseId/about";
$courseTeachers = $course->getTeachers();
$teachersData = [];

/** @var CourseRelUser $teacherSubscription */
foreach ($courseTeachers as $teacherSubscription) {
    $teacher = $teacherSubscription->getUser();
    $userData = [
        'complete_name' => UserManager::formatUserFullName($teacher),
        'image' => UserManager::getUserPicture(
            $teacher->getId(),
            USER_IMAGE_SIZE_ORIGINAL
        ),
        'diploma' => $teacher->getDiplomas(),
        'openarea' => $teacher->getOpenarea(),
    ];

    $teachersData[] = $userData;
}

$tagField = $fieldsRepo->findOneBy([
    'extraFieldType' => ExtraField::COURSE_FIELD_TYPE,
    'variable' => 'tags',
]);

$courseTags = [];

if (!is_null($tagField)) {
    $courseTags = $fieldTagsRepo->getTags($tagField, $courseId);
}

$courseDescription = $courseObjectives = $courseTopics = $courseMethodology = $courseMaterial = $courseResources = $courseAssessment = '';
$courseCustom = [];
foreach ($courseDescriptionTools as $descriptionTool) {
    switch ($descriptionTool->getDescriptionType()) {
        case CCourseDescription::TYPE_DESCRIPTION:
            $courseDescription = Security::remove_XSS($descriptionTool->getContent());
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
        case CCourseDescription::TYPE_ASSESSMENT:
            $courseAssessment = $descriptionTool;
            break;
        case CCourseDescription::TYPE_CUSTOM:
            $courseCustom[] = $descriptionTool;
            break;
    }
}

$topics = [
    'objectives' => $courseObjectives,
    'topics' => $courseTopics,
    'methodology' => $courseMethodology,
    'material' => $courseMaterial,
    'resources' => $courseResources,
    'assessment' => $courseAssessment,
    'custom' => array_reverse($courseCustom),
];

$subscriptionUser = CourseManager::is_user_subscribed_in_course($userId, $course->getCode());

$allowSubscribe = false;
if ($course->getSubscribe() || api_is_platform_admin()) {
    $allowSubscribe = true;
}
$plugin = BuyCoursesPlugin::create();
$checker = $plugin->isEnabled();
$courseIsPremium = null;
if ($checker) {
    $courseIsPremium = $plugin->getItemByProduct(
        $courseId,
        BuyCoursesPlugin::PRODUCT_TYPE_COURSE
    );
}

$courseItem = [
    'code' => $course->getCode(),
    'visibility' => $course->getVisibility(),
    'title' => $course->getTitle(),
    'description' => $courseDescription,
    'image' => CourseManager::getPicturePath($course, true),
    'syllabus' => $topics,
    'tags' => $courseTags,
    'teachers' => $teachersData,
    'extra_fields' => $courseValues->getAllValuesForAnItem(
        $course->getId(),
        null,
        true
    ),
    'subscription' => $subscriptionUser,
];

$metaInfo = '<meta property="og:url" content="'.$urlCourse.'" />';
$metaInfo .= '<meta property="og:type" content="website" />';
$metaInfo .= '<meta property="og:title" content="'.$courseItem['title'].'" />';
$metaInfo .= '<meta property="og:description" content="'.strip_tags($courseDescription).'" />';
$metaInfo .= '<meta property="og:image" content="'.$courseItem['image'].'" />';

$htmlHeadXtra[] = $metaInfo;
$htmlHeadXtra[] = api_get_asset('readmore-js/readmore.js');

/** @var SequenceResourceRepository $sequenceResourceRepo */
$sequenceResourceRepo = $em->getRepository('ChamiloCoreBundle:SequenceResource');
$requirements = $sequenceResourceRepo->getRequirements(
    $course->getId(),
    SequenceResource::COURSE_TYPE
);

$hasRequirements = false;
foreach ($requirements as $sequence) {
    if (!empty($sequence['requirements'])) {
        $hasRequirements = true;
        break;
    }
}

if ($hasRequirements) {
    $sequenceList = $sequenceResourceRepo->checkRequirementsForUser($requirements, SequenceResource::COURSE_TYPE, $userId);
    $allowSubscribe = $sequenceResourceRepo->checkSequenceAreCompleted($sequenceList);
}

$template = new Template($course->getTitle(), true, true, false, true, false);

$template->assign('course', $courseItem);
$essence = Essence\Essence::instance();
$template->assign('essence', $essence);
$template->assign('is_premium', $courseIsPremium);
$template->assign('allow_subscribe', $allowSubscribe);
$template->assign('token', $token);
$template->assign('url', $urlCourse);
$template->assign(
    'subscribe_button',
    CoursesAndSessionsCatalog::getRequirements(
        $course->getId(),
        SequenceResource::COURSE_TYPE,
        true,
        true
    )
);
$template->assign('has_requirements', $hasRequirements);
$template->assign('sequences', $requirements);

$layout = $template->get_template('course_home/about.tpl');
$content = $template->fetch($layout);
$template->assign('content', $content);
$template->display_one_col_template();
