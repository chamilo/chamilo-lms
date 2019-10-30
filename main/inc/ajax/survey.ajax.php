<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../global.inc.php';

$action = isset($_GET['a']) ? $_GET['a'] : null;

$current_user_id = api_get_user_id();
$courseId = api_get_course_int_id();

$surveyId = isset($_REQUEST['survey_id']) ? $_REQUEST['survey_id'] : null;
$questionId = isset($_REQUEST['question_id']) ? $_REQUEST['question_id'] : null;

switch ($action) {
    case 'load_question_options':
        if (!api_is_allowed_to_edit()) {
            exit;
        }

        $question = SurveyManager::get_question($questionId);
        if (!empty($question)) {
            foreach ($question['answers'] as $index => $answer) {
                echo Display::input(
                    'radio',
                    'option['.$questionId.']',
                    $question['answersid'][$index],
                    ['class' => 'question_option']
                );
                echo $answer;
                echo '<br />';
            }
        }

        echo '
            <script>
                $(function() {
                    $(".question_option").on("click", function() {
                        $("#question_form_option_id").attr("value", $(this).val());
                    });
                });
            </script>
        ';
        break;
    case 'save_question':
        if (api_is_anonymous()) {
            echo '';
            break;
        }
        $status = isset($_GET['status']) ? (int) $_GET['status'] : null;
        $userId = api_get_user_id();

        $surveyData = SurveyManager::get_survey($surveyId);

        if (empty($surveyData)) {
            exit;
        }

        SurveyUtil::remove_answer(
            $userId,
            $surveyId,
            $questionId,
            $courseId
        );

        SurveyUtil::store_answer(
            $userId,
            $surveyId,
            $questionId,
            1,
            $status,
            $surveyData
        );

        break;
}
exit;
