<?php
/* For licensing terms, see /licence.txt */

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
$countStudents = count($students);
$questionList = $objExercise->get_validated_question_list();
$content = '';

if (!empty($questionList)) {
    $id = 0;
    $counterLabel = 0;
    foreach ($questionList as $questionId) {
        $counterLabel++;
        $data = [];
        $questionObj = Question::read($questionId, null, null, true);
        $exerciseStats = ExerciseLib::get_student_stats_by_question(
            $questionId,
            $exerciseId,
            $courseCode,
            $sessionId
        );
        $content .= Display::page_subheader2($counterLabel.'. '.$questionObj->question);
        $content .= '<p>'.get_lang('QuestionType').': <em>'.$questionObj->get_question_type_name(true).'</em></p>';

        $answer = new Answer($questionId);
        $answerCount = $answer->selectNbrAnswers();

        for ($answerId = 1; $answerId <= $answerCount; $answerId++) {
            $answerInfo = $answer->selectAnswer($answerId);
            $realAnswerId = $answer->selectAutoId($answerId);

            // Overwriting values depending of the question
            switch ($questionObj->type) {
                case QUESTION_PT_TYPE_CATEGORY_RANKING:
                    $headers = [
                        get_lang('Answer'),
                        get_lang('NumberStudentWhoSelectedIt'),
                    ];

                    $data[$id]['answer'] = $answerInfo;
                    $count = ExerciseLib::get_number_students_answer_count(
                        $realAnswerId,
                        $questionId,
                        $exerciseId,
                        $courseCode,
                        $sessionId,
                        $questionObj->type
                    );
                    $percentage = 0;
                    if (!empty($countStudents)) {
                        $percentage = $count / $countStudents * 100;
                    }
                    $data[$id]['attempts'] = Display::bar_progress(
                        $percentage,
                        false,
                        $count.' / '.$countStudents
                    );
                    break;
                case QUESTION_PT_TYPE_AGREE_OR_DISAGREE:
                    $headers = [
                        get_lang('Answer'),
                        get_lang('MostAgree'),
                        get_lang('LeastAgree'),
                    ];
                    $data[$id]['answer'] = $realAnswerId.' - '.$answerInfo;
                    $count = ExerciseLib::get_number_students_answer_count(
                        $realAnswerId,
                        $questionId,
                        $exerciseId,
                        $courseCode,
                        $sessionId,
                        $questionObj->type
                    );
                    $percentageAgree = 0;
                    $percentageDisagree = 0;

                    if (!empty($countStudents)) {
                        $percentageAgree = $count[0] / $countStudents * 100;
                        $percentageDisagree = $count[1] / $countStudents * 100;
                    }
                    $data[$id]['agree'] = Display::bar_progress(
                        $percentageAgree,
                        false,
                        $count[0].' / '.$countStudents
                    );
                    $data[$id]['disagree'] = Display::bar_progress(
                        $percentageDisagree,
                        false,
                        $count[1].' / '.$countStudents
                    );
                    break;
                case QUESTION_PT_TYPE_AGREE_SCALE:
                    $headers = [
                        get_lang('Answer'),
                        get_lang('AverageScore'),
                    ];

                    $data[$id]['answer'] = $answerInfo;
                    $count = ExerciseLib::get_number_students_answer_count(
                        $realAnswerId,
                        $questionId,
                        $exerciseId,
                        $courseCode,
                        $sessionId,
                        $questionObj->type
                    );
                    $percentage = 0;
                    if (!empty($countStudents)) {
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

                    $data[$id]['answer'] = $answerInfo;
                    $count = ExerciseLib::get_number_students_answer_count(
                        $realAnswerId,
                        $questionId,
                        $exerciseId,
                        $courseCode,
                        $sessionId,
                        $questionObj->type
                    );
                    $percentage = 0;
                    if (!empty($countStudents)) {
                        $percentage = $count / 5 * 100;
                    }
                    $data[$id]['attempts'] = Display::bar_progress(
                        $percentage,
                        false,
                        $count.' / 5'
                    );
                    break;
                default:
                    if ($answerId == 1) {
                        $data[$id]['name'] = cut($questionObj->question, 100);
                    } else {
                        $data[$id]['name'] = '-';
                    }
                    $data[$id]['answer'] = $answerInfo;
                    $data[$id]['correct'] = $correct_answer;

                    $count = ExerciseLib::get_number_students_answer_count(
                        $realAnswerId,
                        $questionId,
                        $exerciseId,
                        $courseCode,
                        $sessionId
                    );
                    $percentage = 0;
                    if (!empty($countStudents)) {
                        $percentage = $count / $countStudents * 100;
                    }
                    $data[$id]['attempts'] = Display::bar_progress(
                        $percentage,
                        false,
                        $count.' / '.$countStudents
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
        foreach ($data as $rowTable) {
            $column = 0;
            foreach ($rowTable as $cell) {
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
