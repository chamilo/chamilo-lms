<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$sessionId = api_get_session_id();
$courseId = api_get_course_int_id();
$userId = api_get_user_id();
$courseInfo = api_get_course_info();

$surveyId = isset($_REQUEST['survey_id']) ? (int) $_REQUEST['survey_id'] : 0;
$invitationcode = isset($_REQUEST['invitationcode']) ? Database::escape_string($_REQUEST['invitationcode']) : 0;

if (!api_is_allowed_to_edit() || !empty($invitationcode)) {
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

// getting all the students of the course
if (empty($sessionId)) {
    // Registered students in a course outside session.
    $students = CourseManager:: get_student_list_from_course_code(
        api_get_course_id(),
        false,
        0,
        null,
        null,
        true
    );
} else {
    // Registered students in session.
    $students = CourseManager:: get_student_list_from_course_code(
        api_get_course_id(),
        true,
        $sessionId
    );
}

$surveyData = SurveyManager::get_survey($surveyId);

if (empty($surveyData)) {
    api_not_allowed(true);
}

$content = Display::page_header($surveyData['title']);

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'survey/survey_list.php?cidReq='.$courseInfo['code'].'&id_session='.$sessionId,
    'name' => get_lang('SurveyList'),
];

$template = new Template();

$questions = SurveyManager::get_questions($surveyData['iid']);

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
    $table->setHeaderContents($row, $column, api_get_local_time($item['question']));
    $column++;
}

$row = 1;
$column = 0;
foreach ($students as $student) {
    $name = api_get_person_name($student['firstname'], $student['lastname']);
    $studentId = $student['user_id'];
    if ($userId == $studentId) {
        $rowColumn = 1;
        foreach ($questions as $item) {
            $checked = '';
            if (isset($answerList[$item['question_id']][$studentId])) {
                $checked = 'checked';
            }

            $table->setHeaderContents(
                $row,
                $rowColumn,
                '<input id="'.$item['question_id'].'" class="question" '.$checked.' type="checkbox"/>'
            );
            $rowColumn++;
        }
    } else {
        $rowColumn = 1;
        foreach ($questions as $item) {
            $checked = '';
            if (isset($answerList[$item['question_id']][$studentId])) {
                $checked = Display::return_icon('check-circle.png');
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
    $class = 'class="row_odd"';
    if ($row % 2) {
        $class = 'class="row_even"';
    }
    //$table->setRowAttributes($row, $class, true);
    //$column++;
    $row++;
}

$content .= $table->toHtml();

$ajaxUrl = api_get_path(WEB_AJAX_PATH).'survey.ajax.php?a=save_question&'.api_get_cidreq().'&survey_id='.$surveyId.'&question_id=';

$content .= '<script>
$(function() {    
    $(".question").on("change", function() {
        var questionId = $(this).attr("id");
        
        var status = 0;
        if ($(this).prop("checked")) {
            status = 1;
        }
        
        $.ajax({ 
            url: "'.$ajaxUrl.'" + questionId + "&status=" + status,
            success: function (data) {
                return;
            }, 
        });        
    });    
  
});
</script>';

$template->assign('content', $content);
$template->display_one_col_template();
