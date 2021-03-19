<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Entity\CSurvey;
use Chamilo\CourseBundle\Entity\CSurveyInvitation;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$em = Database::getManager();

$currentUser = api_get_user_entity(api_get_user_id());
$pending = SurveyUtil::getUserPendingInvitations($currentUser->getId());

$surveysData = [];

foreach ($pending as $i => $item) {
    if (is_a($item, 'Chamilo\CourseBundle\Entity\CSurveyInvitation')) {
        continue;
    }

    /** @var CSurvey $survey */
    $survey = $item;
    /** @var CSurveyInvitation invitation */
    $invitation = $pending[$i + 1];
    $course = api_get_course_entity($survey->getCId());
    $session = api_get_session_entity($survey->getSessionId());

    //$course = $course ? ['id' => $course->getId(), 'title' => $course->getTitle(), 'code' => $course->getCode()] : null;
    $session = $session ? ['id' => $session->getId(), 'name' => $session->getName()] : null;
    $courseInfo = api_get_course_info_by_id($course->getId());
    $surveysData[$survey->getIid()] = [
        'title' => $survey->getTitle(),
        'avail_from' => $survey->getAvailFrom(),
        'avail_till' => $survey->getAvailTill(),
        'course' => $course,
        'session' => $session,
        'link' => SurveyUtil::generateFillSurveyLink(
            $survey,
            $invitation->getInvitationCode(),
            $course
        ),
    ];
}

$toolName = get_lang('Pending surveys');

$template = new Template($toolName);
$template->assign('user', $currentUser);
$template->assign('surveys', $surveysData);
$layout = $template->get_template('survey/pending.tpl');
$content = $template->fetch($layout);
$template->assign('header', $toolName);
$template->assign('content', $content);
$template->display_one_col_template();
