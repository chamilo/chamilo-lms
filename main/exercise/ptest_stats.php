<?php
/* See license terms in /license.txt */

require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

api_protect_course_script(true, false, true);

$showPage = false;

if (api_is_platform_admin() || api_is_course_admin() ||
    api_is_course_tutor() || api_is_session_general_coach() || api_is_allowed_to_edit(null, true)
) {
    $showPage = true;
}

if (!$showPage) {
    api_not_allowed(true);
}

$exerciseId = isset($_GET['exerciseId']) && !empty($_GET['exerciseId']) ? (int) $_GET['exerciseId'] : 0;
$objExercise = new Exercise();
$result = $objExercise->read($exerciseId);

if (!$result) {
    api_not_allowed(true);
}

$sessionId = api_get_session_id();
$courseCode = api_get_course_id();

if (empty($sessionId)) {
    $students = CourseManager:: get_student_list_from_course_code(
        $courseCode,
        false
    );
} else {
    $students = CourseManager:: get_student_list_from_course_code(
        $courseCode,
        true,
        $sessionId
    );
}
$count_students = count($students);
$question_list = $objExercise->get_validated_question_list();
$content = '';

if (!empty($question_list)) {
    $id = 0;
    $counterLabel = 0;
    foreach ($question_list as $question_id) {
        $counterLabel++;
        $data = [];
        $questionObj = Question::read($question_id, null, null, true);
        $exercise_stats = ExerciseLib::get_student_stats_by_question(
            $question_id,
            $exerciseId,
            $courseCode,
            $sessionId
        );
        $content .= Display::page_subheader2($counterLabel.'. '.$questionObj->question);
        $content .= '<p>'.get_lang('QuestionType').': <em>'.$questionObj->get_question_type_name(true).'</em></p>';

        $answer = new Answer($question_id);
        $answer_count = $answer->selectNbrAnswers();

        for ($answer_id = 1; $answer_id <= $answer_count; $answer_id++) {
            $answer_info = $answer->selectAnswer($answer_id);
            $real_answer_id = $answer->selectAutoId($answer_id);

            // Overwriting values depending of the question
            switch ($questionObj->type) {
                case QUESTION_PT_TYPE_CATEGORY_RANKING:
                    $headers = [
                        get_lang('Answer'),
                        get_lang('NumberStudentWhoSelectedIt'),
                    ];

                    $data[$id]['answer'] = $answer_info;
                    $count = ExerciseLib::get_number_students_answer_count(
                        $real_answer_id,
                        $question_id,
                        $exerciseId,
                        $courseCode,
                        $sessionId,
                        $questionObj->type
                    );
                    $percentage = 0;
                    if (!empty($count_students)) {
                        $percentage = $count / $count_students * 100;
                    }
                    $data[$id]['attempts'] = Display::bar_progress(
                        $percentage,
                        false,
                        $count.' / '.$count_students
                    );
                    break;
                case QUESTION_PT_TYPE_AGREE_OR_DISAGREE:
                    $headers = [
                        get_lang('Answer'),
                        get_lang('MostAgree'),
                        get_lang('LeastAgree'),
                    ];
                    $data[$id]['answer'] = $real_answer_id.' - '.$answer_info;
                    $count = ExerciseLib::get_number_students_answer_count(
                        $real_answer_id,
                        $question_id,
                        $exerciseId,
                        $courseCode,
                        $sessionId,
                        $questionObj->type
                    );
                    $percentageAgree = 0;
                    $percentageDisagree = 0;

                    if (!empty($count_students)) {
                        $percentageAgree = $count[0] / $count_students * 100;
                        $percentageDisagree = $count[1] / $count_students * 100;
                    }
                    $data[$id]['agree'] = Display::bar_progress(
                        $percentageAgree,
                        false,
                        $count[0].' / '.$count_students
                    );
                    $data[$id]['disagree'] = Display::bar_progress(
                        $percentageDisagree,
                        false,
                        $count[1].' / '.$count_students
                    );
                    break;
                case QUESTION_PT_TYPE_AGREE_SCALE:
                    $headers = [
                        get_lang('Answer'),
                        get_lang('AverageScore'),
                    ];

                    $data[$id]['answer'] = $answer_info;
                    $count = ExerciseLib::get_number_students_answer_count(
                        $real_answer_id,
                        $question_id,
                        $exerciseId,
                        $courseCode,
                        $sessionId,
                        $questionObj->type
                    );
                    $percentage = 0;
                    if (!empty($count_students)) {
                        $percentage = $count / 5 * 100;
                    }
                    $data[$id]['attempts'] = Display::bar_progress(
                        $percentage,
                        false,
                        $count.' / 5'
                    );
                    break;
                case QUESTION_PT_TYPE_AGREE_REORDER:
                    $headers = [
                        get_lang('Answer'),
                        get_lang('AverageScore'),
                    ];

                    $data[$id]['answer'] = $answer_info;
                    $count = ExerciseLib::get_number_students_answer_count(
                        $real_answer_id,
                        $question_id,
                        $exerciseId,
                        $courseCode,
                        $sessionId,
                        $questionObj->type
                    );
                    $percentage = 0;
                    if (!empty($count_students)) {
                        $percentage = $count / 5 * 100;
                    }
                    $data[$id]['attempts'] = Display::bar_progress(
                        $percentage,
                        false,
                        $count.' / 5'
                    );
                    break;
                default:
                    if ($answer_id == 1) {
                        $data[$id]['name'] = cut($questionObj->question, 100);
                    } else {
                        $data[$id]['name'] = '-';
                    }
                    $data[$id]['answer'] = $answer_info;
                    $data[$id]['correct'] = $correct_answer;

                    $count = ExerciseLib::get_number_students_answer_count(
                        $real_answer_id,
                        $question_id,
                        $exerciseId,
                        $courseCode,
                        $sessionId
                    );
                    $percentage = 0;
                    if (!empty($count_students)) {
                        $percentage = $count / $count_students * 100;
                    }
                    $data[$id]['attempts'] = Display::bar_progress(
                        $percentage,
                        false,
                        $count.' / '.$count_students
                    );
            }
            $id++;
        }

        // Format A table
        $table = new HTML_Table(['class' => 'data_table']);
        $row = 0;
        $column = 0;
        foreach ($headers as $header) {
            $table->setHeaderContents($row, $column, $header);
            $column++;
        }
        $row++;
        foreach ($data as $row_table) {
            $column = 0;
            foreach ($row_table as $cell) {
                $table->setCellContents($row, $column, $cell);
                $table->updateCellAttributes($row, $column, 'align="center"');
                $column++;
            }
            $table->updateRowAttributes($row, $row % 2 ? 'class="row_even"' : 'class="row_odd"', true);
            $row++;
        }
        $content .= $table->toHtml();
    }
}

$interbreadcrumb[] = [
    "url" => "exercise.php?".api_get_cidreq(),
    "name" => get_lang('Exercises'),
];
$interbreadcrumb[] = [
    "url" => "admin.php?exerciseId=$exerciseId&".api_get_cidreq(),
    "name" => $objExercise->selectTitle(true),
];

$tpl = new Template(get_lang('ReportByQuestion'));
$actions = '<a href="ptest_exercise_report.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'">'.
    Display:: return_icon(
        'back.png',
        get_lang('GoBackToQuestionList'),
        '',
        ICON_SIZE_MEDIUM
    )
    .'</a>';
$actions = Display::div($actions, ['class' => 'actions']);
$content = $actions.$content;
$tpl->assign('content', $content);
$tpl->display_one_col_template();
