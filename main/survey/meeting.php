<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$sessionId = api_get_session_id();
$courseId = api_get_course_int_id();
$userId = api_get_user_id();
$courseInfo = api_get_course_info();

$surveyId = isset($_REQUEST['survey_id']) ? (int) $_REQUEST['survey_id'] : 0;
$invitationcode = isset($_REQUEST['invitationcode']) ? Database::escape_string($_REQUEST['invitationcode']) : 0;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if (!empty($invitationcode) || !api_is_allowed_to_edit()) {
    $table_survey_invitation = Database::get_course_table(TABLE_SURVEY_INVITATION);
    $table_survey = Database::get_course_table(TABLE_SURVEY);

    $sql = "SELECT * FROM $table_survey_invitation
            WHERE
                c_id = $courseId AND
                invitation_code = '".Database::escape_string($invitationcode)."'";
    $result = Database::query($sql);
    if (Database::num_rows($result) < 1) {
        api_not_allowed(true, get_lang('WrongInvitationCode'));
    }

    $survey_invitation = Database::fetch_array($result, 'ASSOC');
    $sql = "SELECT * FROM $table_survey
            WHERE
                c_id = $courseId AND
                code = '".Database::escape_string($survey_invitation['survey_code'])."'";
    $sql .= api_get_session_condition($sessionId);
    $result = Database::query($sql);
    $result = Database::fetch_array($result, 'ASSOC');
    $surveyId = $result['iid'];
}

$surveyData = SurveyManager::get_survey($surveyId);

if (empty($surveyData)) {
    api_not_allowed(true);
}

if (!api_is_allowed_to_edit()) {
    SurveyManager::checkTimeAvailability($surveyData);
}

$invitations = SurveyUtil::get_invited_users($surveyData['code']);
$students = isset($invitations['course_users']) ? $invitations['course_users'] : [];

$content = Display::page_header($surveyData['title']);

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).
        'survey/survey_list.php?cidReq='.$courseInfo['code'].'&id_session='.$sessionId,
    'name' => get_lang('SurveyList'),
];

$questions = SurveyManager::get_questions($surveyData['iid']);

$url = api_get_self().'?survey_id='.$surveyId.'&invitationcode='.$invitationcode.'&'.api_get_cidreq();
$urlEdit = $url.'&action=edit';

if (isset($_POST) && !empty($_POST)) {
    $options = isset($_POST['options']) ? array_keys($_POST['options']) : [];

    foreach ($questions as $item) {
        $questionId = $item['question_id'];
        SurveyUtil::remove_answer(
            $userId,
            $surveyId,
            $questionId,
            $courseId
        );
    }

    $status = 1;
    if (!empty($options)) {
        foreach ($options as $selectedQuestionId) {
            SurveyUtil::store_answer(
                $userId,
                $surveyId,
                $selectedQuestionId,
                1,
                $status,
                $surveyData
            );
        }
    } else {
        foreach ($questions as $item) {
            $questionId = $item['question_id'];
            SurveyUtil::store_answer(
                $userId,
                $surveyId,
                $questionId,
                1,
                0,
                $surveyData
            );
        }
    }

    SurveyManager::update_survey_answered(
        $surveyData,
        $survey_invitation['user'],
        $survey_invitation['survey_code']
    );

    Display::addFlash(Display::return_message(get_lang('Saved')));
    header('Location: '.$url);
    exit;
}

$template = new Template();

$table = new HTML_Table(['class' => 'table']);

$row = 0;
$column = 1;
$answerList = [];
foreach ($questions as $item) {
    $answers = SurveyUtil::get_answers_of_question_by_user($surveyId, $item['question_id']);
    foreach ($answers as $tempUserId => &$value) {
        $value = $value[0];
        $value = explode('*', $value);
        $value = isset($value[1]) ? $value[1] : 0;
    }
    $answerList[$item['question_id']] = $answers;
    $parts = explode('@@', $item['question']);
    $startDateTime = api_get_local_time($parts[0]);
    $endDateTime = api_get_local_time($parts[1]);

    $date = explode(' ', $startDateTime);
    $endDate = explode(' ', $endDateTime);
    $mainDate = $date[0];

    $startTime = isset($date[1]) && !empty($date[1]) ? sprintf(get_lang('FromTimeX'), $date[1]) : '';
    $endTime = isset($endDate[1]) && !empty($endDate[1]) ? sprintf(get_lang('ToTimeX'), $endDate[1]) : '';

    if (isset($date[1]) && isset($endDate[1])) {
        if ($date[1] === $endDate[1]) {
            $startTime = '';
            $endTime = '';
        }
    }

    $mainDate = api_format_date($mainDate, DATE_FORMAT_SHORT);
    $table->setHeaderContents($row, $column, "<h4>$mainDate</h4> $startTime <br >$endTime");
    $column++;
}

$row = 1;
$column = 0;

// Total counter
$table->setHeaderContents(
    $row,
    0,
    get_lang('NumberOfUsers').': '.count($students)
);

foreach ($questions as $item) {
    $questionId = $item['question_id'];
    $count = 0;
    $questionsWithAnswer = 0;
    if (isset($answerList[$questionId])) {
        foreach ($answerList[$questionId] as $userAnswer) {
            if ((int) $userAnswer === 1) {
                $questionsWithAnswer++;
            }
        }
        $count = '<p style="color:cornflowerblue" >
                  <span class="fa fa-check fa-2x"></span>'.$questionsWithAnswer.'</p>';
    }
    $table->setHeaderContents(
        $row,
        ++$column,
        $count
    );
}

$row = 2;
$column = 0;
$availableIcon = Display::return_icon('bullet_green.png', get_lang('Available'));
$notAvailableIcon = Display::return_icon('bullet_red.png', get_lang('NotAvailable'));

foreach ($students as $studentId) {
    $userInfo = api_get_user_info($studentId);
    $name = $userInfo['complete_name'];
    if ($userId == $studentId) {
        if ($action !== 'edit') {
            $name .= Display::url(
                Display::return_icon('edit.png', get_lang('Edit')),
                $urlEdit
            );
        }
        $rowColumn = 1;
        foreach ($questions as $item) {
            $checked = '';
            $html = '';
            if (isset($answerList[$item['question_id']][$studentId])) {
                $checked = $availableIcon;
                if (empty($answerList[$item['question_id']][$studentId])) {
                    $checked = $notAvailableIcon;
                }
                if ($action === 'edit') {
                    $checked = '';
                    if ($answerList[$item['question_id']][$studentId] == 1) {
                        $checked = 'checked';
                    }
                }
            }

            if ($action === 'edit') {
                $html = '<div class="alert alert-info"><input
                    id="'.$item['question_id'].'"
                    name="options['.$item['question_id'].']"
                    class="question" '.$checked.'
                    type="checkbox"
                /></div>';
            } else {
                $html = $checked;
            }

            $table->setHeaderContents(
                $row,
                $rowColumn,
                $html
            );
            $rowColumn++;
        }
    } else {
        $rowColumn = 1;
        foreach ($questions as $item) {
            $checked = '';
            if (isset($answerList[$item['question_id']][$studentId])) {
                $checked = $availableIcon;
                if (empty($answerList[$item['question_id']][$studentId])) {
                    $checked = $notAvailableIcon;
                }
            }
            $table->setHeaderContents(
                $row,
                $rowColumn,
                $checked
            );
            $rowColumn++;
        }
    }
    $column = 0;
    $table->setCellContents($row, $column, $name);
    $row++;
}
if ($action === 'edit') {
    $content .= '<form name="meeting" action="'.$urlEdit.'" method="post">';
}

$content .= $table->toHtml();

if ($action === 'edit') {
    $content .= '<div class="pull-right">
        <button name="submit" type="submit" class="btn btn-primary btn-lg">'.get_lang('Save').'</button></div>';
    $content .= '</form>';
}

$actions = '';
if (api_is_allowed_to_edit()) {
    $actions .= Display::url(
        Display::return_icon('edit.png', get_lang('EditSurvey'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'survey/edit_meeting.php?'.api_get_cidreq().'&action=edit&survey_id='.$surveyId
    );
    $actions .= Display::url(
        Display::return_icon('delete.png', get_lang('DeleteSurvey'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'survey/survey_list.php?'.api_get_cidreq().'&action=delete&survey_id='.$surveyId,
        ['onclick' => 'javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('DeleteSurvey').'?', ENT_QUOTES)).'\')) return false;']
    );
    $actions .= Display::url(
        Display::return_icon('mail_send.png', get_lang('Publish'), '', ICON_SIZE_MEDIUM),
        api_get_path(WEB_CODE_PATH).'survey/survey_invite.php?'.api_get_cidreq().'&survey_id='.$surveyId
    );
}

$template->assign('actions', Display::toolbarAction('toolbar', [$actions]));
$template->assign('content', $content);
$template->display_one_col_template();
