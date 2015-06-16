<?php
/* For licensing terms, see /license.txt */
/**
 * Session about page
 * Show information about a session and its courses
 * @author Angel Fernando Quiroz Campos <angel.quiroz@beeznest.com>
 * @package chamilo.session
 */
use Chamilo\CourseBundle\Entity\CCourseDescription;
use Chamilo\CoreBundle\Entity\ExtraField;

$cidReset = true;

require_once '../inc/global.inc.php';

$sessionId = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

$entityManager = Database::getManager();

$session = $entityManager->find('ChamiloCoreBundle:Session', $sessionId);

$sessionCourses = $entityManager->getRepository('ChamiloCoreBundle:Session')
    ->getCoursesOrderedByPosition($session);

$courses = [];

$entityManager = Database::getManager();
$fieldsRepo = $entityManager->getRepository('ChamiloCoreBundle:ExtraField');
$fieldValuesRepo = $entityManager->getRepository('ChamiloCoreBundle:ExtraFieldValues');
$fieldTagsRepo = $entityManager->getRepository('ChamiloCoreBundle:ExtraFieldRelTag');

$videoUrlField = $fieldsRepo->findOneBy([
    'extraFieldType' => ExtraField::COURSE_FIELD_TYPE,
    'variable' => 'video_url'
]);
$tagField = $fieldsRepo->findOneBy([
    'extraFieldType' => ExtraField::COURSE_FIELD_TYPE,
    'variable' => 'tags'
]);

foreach ($sessionCourses as $sessionCourse) {
    $courseVideo = null;
    $courseTags = [];

    if (!is_null($videoUrlField)) {
        $videoUrlValue = $fieldValuesRepo->findOneBy([
            'field' => $videoUrlField,
            'itemId' => $sessionCourse->getId()
        ]);

        if (!is_null($videoUrlValue)) {
            $essence = \Essence\Essence::instance();

            $courseVideo = $essence->replace($videoUrlValue->getValue());
        }
    }

    if (!is_null($tagField)) {
        $courseTags = $fieldTagsRepo->getTags($tagField, $sessionCourse->getId());
    }

    $courseCoaches = $entityManager->getRepository('ChamiloCoreBundle:Session')
        ->getCourseCoachesForCoach($session, $sessionCourse);

    $courseDescriptionTools = $entityManager->getRepository('ChamiloCourseBundle:CCourseDescription')
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

    $courseDescription = $courseObjectives = $courseTopics = null;

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
        }
    }

    $courses[] = [
        'course' => $sessionCourse,
        'video' => $courseVideo,
        'description' => $courseDescription,
        'tags' => $courseTags,
        'objectives' => $courseObjectives,
        'topics' => $courseTopics,
        'coaches' => $courseCoaches
    ];
}

/* View */
$template = new Template($session->getName(), true, true, false, true, false);
$template->assign('courses', $courses);

$templateFolder = api_get_configuration_value('default_template');

if (!empty($templateFolder)) {
    $content = $template->fetch($templateFolder.'/session/about.tpl');
} else {
    $content = $template->fetch('default/session/about.tpl');
}

$template->assign('header', $session->getName());
$template->assign('content', $content);
$template->display_one_col_template();
