<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$currentUser = api_get_user_entity(api_get_user_id());
$pendingList = Container::getUserRepository()->getUserPendingInvitations($currentUser);

$surveysData = [];

foreach ($pendingList as $pending) {
    $course = $pending->getCourse();
    $session = $pending->getSession();
    $survey = $pending->getSurvey();

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
            $pending->getInvitationCode(),
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
