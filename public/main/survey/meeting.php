<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CourseBundle\Entity\CSurvey;

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$sessionId = api_get_session_id();
$courseId = api_get_course_int_id();
$userId = api_get_user_id();
$courseInfo = api_get_course_info();

$surveyId = isset($_REQUEST['survey_id']) ? (int) $_REQUEST['survey_id'] : 0;
$invitationcode = isset($_REQUEST['invitationcode']) ? Database::escape_string($_REQUEST['invitationcode']) : 0;
$action = $_REQUEST['action'] ?? '';
$survey = null;
$repo = Container::getSurveyRepository();
$questionRepo = Container::getSurveyQuestionRepository();

if (!empty($invitationcode) || !api_is_allowed_to_edit()) {
    $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
    $table_survey = Database::get_course_table(TABLE_SURVEY);

    $sql = "SELECT * FROM $table_survey_invitation
            WHERE
                c_id = $courseId AND
                invitation_code = '".Database::escape_string($invitationcode)."'";
    $result = Database::query($sql);
    if (Database::num_rows($result) < 1) {
        api_not_allowed(true, get_lang('Wrong invitation code'));
    }

    $survey_invitation = Database::fetch_assoc($result);
    if ($survey_invitation) {
        $surveyId = (int) $survey_invitation['survey_id'];
    }
}

/** @var CSurvey|null $survey */
$survey = $repo->find($surveyId);

if (null === $survey) {
    api_not_allowed(true);
}

$surveyId = $survey->getIid();
$invitations = SurveyUtil::get_invited_users($survey);
$students = $invitations['course_users'] ?? [];

$content = Display::page_header($survey->getTitle());

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?cid='.$courseInfo['real_id'].'&sid='.$sessionId,
    'name' => get_lang('Survey list'),
];

$url = api_get_self().'?survey_id='.$surveyId.'&invitationcode='.$invitationcode.'&'.api_get_cidreq();
$urlEdit = $url.'&action=edit';

if (!api_is_allowed_to_edit()) {
    SurveyManager::checkTimeAvailability($survey);
}

$questions = $survey->getQuestions();

if (isset($_POST) && !empty($_POST)) {
    $options = isset($_POST['options']) ? array_keys($_POST['options']) : [];

    foreach ($questions as $item) {
        $questionId = $item->getIid();
        SurveyUtil::remove_answer($userId, $surveyId, $questionId);
    }

    $status = 1;
    if (!empty($options)) {
        foreach ($options as $selectedQuestionId) {
            $question = $questionRepo->find($selectedQuestionId);
            SurveyUtil::saveAnswer(
                $userId,
                $survey,
                $question,
                1,
                $status
            );
        }
    } else {
        foreach ($questions as $item) {
            $questionId = $item->getIid();
            $question = $questionRepo->find($questionId);
            SurveyUtil::saveAnswer(
                $userId,
                $survey,
                $question,
                1,
                0
            );
        }
    }

    SurveyManager::updateSurveyAnswered($survey, $survey_invitation['user_id'] ?? api_get_user_id());

    Display::addFlash(Display::return_message(get_lang('Saved.')));
    header('Location: '.$url);
    exit;
}

$template = new Template();

$table = new HTML_Table(['class' => 'table table-hover table-striped data_table mt-5']);
$row = 0;
$column = 0;
$answerList = [];

$table->setHeaderContents($row, $column, "");
$column++;
foreach ($questions as $item) {
    $questionId = $item->getIid();
    $answers = SurveyUtil::get_answers_of_question_by_user($surveyId, $questionId);
    foreach ($answers as $tempUserId => &$value) {
        $value = $value[0];
        $value = explode('*', $value);
        $value = $value[1] ?? 0;
    }
    $answerList[$questionId] = $answers;
    $parts = explode('@@', $item->getSurveyQuestion());
    $startDateTime = api_get_local_time($parts[0]);
    $endDateTime = api_get_local_time($parts[1]);

    $date = explode(' ', $startDateTime);
    $endDate = explode(' ', $endDateTime);
    $mainDate = $date[0];

    $startTime = isset($date[1]) && !empty($date[1]) ? sprintf(get_lang('From %s'), $date[1]) : '';
    $endTime = isset($endDate[1]) && !empty($endDate[1]) ? sprintf(get_lang('To %s'), $endDate[1]) : '';

    if (isset($date[1]) && isset($endDate[1])) {
        if ($date[1] === $endDate[1]) {
            $startTime = '';
            $endTime = '';
        }
    }

    $mainDate = api_format_date($mainDate, DATE_FORMAT_SHORT);
    $table->setHeaderContents($row, $column, "<h4>$mainDate</h4> <span class='text-lg'>$startTime <br >$endTime</span>");
    $column++;
}

$row = 0;
$column = 0;

// Total counter
$table->setCellContents(
    $row,
    0,
    '<span class="text-bold font-extrabold text-xl">'.get_lang('Number of users').': '.count($students).'</span>'
);

foreach ($questions as $item) {
    $questionId = $item->getIid();
    $count = '';
    $questionsWithAnswer = 0;
    if (isset($answerList[$questionId])) {
        foreach ($answerList[$questionId] as $userAnswer) {
            if (1 === (int) $userAnswer) {
                $questionsWithAnswer++;
            }
        }
        $count = '<p class="text-info text-center p-2 text-bold font-extrabold text-2xl">
                  <span class="mdi mdi-check text-3xl"></span>'.$questionsWithAnswer.'</p>';
    }
    $table->setCellContents(
        $row,
        ++$column,
        $count
    );
}

$row = 1;
$column = 0;
$availableIcon = Display::getMdiIcon(StateIcon::ACTIVE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Available'));
$notAvailableIcon = Display::getMdiIcon(StateIcon::INACTIVE, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Not available'));

foreach ($students as $studentId) {
    $userInfo = api_get_user_info($studentId);
    $name = $userInfo['complete_name'];
    if ($userId == $studentId) {
        if ('edit' !== $action) {
            $name .= Display::url(
                Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Edit')),
                $urlEdit
            );
        }
        $rowColumn = 1;
        foreach ($questions as $item) {
            $questionId = $item->getIid();
            $checked = '';
            $html = '';
            if (isset($answerList[$questionId][$studentId])) {
                $checked = $availableIcon;
                if (empty($answerList[$questionId][$studentId])) {
                    $checked = $notAvailableIcon;
                }
                if ('edit' === $action) {
                    $checked = '';
                    if (1 == $answerList[$questionId][$studentId]) {
                        $checked = 'checked';
                    }
                }
            }

            if ('edit' === $action) {
                $html = '<div class="alert alert-info text-center"><input
                    id="'.$questionId.'"
                    name="options['.$questionId.']"
                    class="question" '.$checked.'
                    type="checkbox"
                /></div>';
            } else {
                $html = $checked;
            }

            $table->setCellContents(
                $row,
                $rowColumn,
                $html
            );
            $rowColumn++;
        }
    } else {
        $rowColumn = 1;
        foreach ($questions as $item) {
            $questionId = $item->getIid();
            $checked = '';
            if (isset($answerList[$questionId][$studentId])) {
                $checked = $availableIcon;
                if (empty($answerList[$questionId][$studentId])) {
                    $checked = $notAvailableIcon;
                }
            }
            $table->setCellContents(
                $row,
                $rowColumn,
                $checked
            );
            $rowColumn++;
        }
    }
    $column = 0;
    $table->setCellContents($row, $column, '<span class="text-bold font-extrabold text-lg">'.$name.'</span>');
    $row++;
}
if ('edit' === $action) {
    $content .= '<form name="meeting" action="'.$urlEdit.'" method="post">';
}

$content .= $table->toHtml();

if ('edit' === $action) {
    $content .= '<div class="pull-right">
        <button name="submit" type="submit" class="btn btn--primary btn-lg">'.get_lang('Save').'</button></div>';
    $content .= '</form>';
}

$actions = '';
if (api_is_allowed_to_edit()) {
    $actions .= Display::url(
        Display::getMdiIcon(ActionIcon::EDIT, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit survey')),
        api_get_path(WEB_CODE_PATH).'survey/edit_meeting.php?'.api_get_cidreq().'&action=edit&survey_id='.$surveyId
    );
    $actions .= Display::url(
        Display::getMdiIcon(ActionIcon::DELETE, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Delete survey')),
        api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq().'&action=delete&survey_id='.$surveyId,
        ['onclick' => 'javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('Delete survey').'?', ENT_QUOTES)).'\')) return false;']
    );
    $actions .= Display::url(
        Display::getMdiIcon(StateIcon::MAIL_NOTIFICATION, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Publish')),
        api_get_path(WEB_CODE_PATH).'survey/survey_invite.php?'.api_get_cidreq().'&survey_id='.$surveyId
    );
}

$template->assign('actions', Display::toolbarAction('toolbar', [$actions]));
$template->assign('content', $content);
$template->display_one_col_template();
