<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

api_block_anonymous_users();

$currentUser = api_get_user_entity(api_get_user_id());
$pendingList = Container::getSurveyInvitationRepository()->getUserPendingInvitations($currentUser);

$surveysData = [];

foreach ($pendingList as $pending) {
    $course = $pending->getCourse();
    $session = $pending->getSession();
    $survey = $pending->getSurvey();

    $courseArr = null;
    if ($course) {
        $courseArr = [
            'id' => $course->getId(),
            'code' => $course->getCode(),
            'title' => $course->getTitle(),
        ];
    }

    $sessionArr = null;
    if ($session) {
        $sessionArr = [
            'id' => $session->getId(),
            'name' => $session->getTitle(),
        ];
    }

    $surveysData[] = [
        'title' => $survey->getTitle(),
        'avail_from' => $survey->getAvailFrom(),
        'avail_till' => $survey->getAvailTill(),
        'course' => $courseArr,
        'session' => $sessionArr,
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
