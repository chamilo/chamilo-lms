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

$data = [];
// Question title 	# of students who tool it 	Lowest score 	Average 	Highest score 	Maximum score
$headers = [
    get_lang('Question'),
    get_lang('Question type'),
    get_lang('Number of learners who selected it'),
    get_lang('Lowest score'),
    get_lang('Average score'),
    get_lang('Highest score'),
    get_lang('Score'),
];

if (!empty($question_list)) {
    foreach ($question_list as $question_id) {
        $questionObj = Question::read($question_id);

        $exercise_stats = ExerciseLib::get_student_stats_by_question(
            $question_id,
            $exerciseId,
            $courseCode,
            $sessionId
        );

        $count_users = ExerciseLib::get_number_students_question_with_answer_count(
            $question_id,
            $exerciseId,
            $courseCode,
            $sessionId,
            $questionObj->type
        );

        $data[$question_id]['name'] = cut($questionObj->question, 100);
        $data[$question_id]['type'] = $questionObj->get_question_type_name();
        $percentage = 0;
        if ($count_students) {
            $percentage = $count_users / $count_students * 100;
        }

        $data[$question_id]['students_who_try_exercise'] = Display::bar_progress(
            $percentage,
            false,
            $count_users.' / '.$count_students
        );
        $data[$question_id]['lowest_score'] = round($exercise_stats['min'], 2);
        $data[$question_id]['average_score'] = round($exercise_stats['average'], 2);
        $data[$question_id]['highest_score'] = round($exercise_stats['max'], 2);
        $data[$question_id]['max_score'] = round($questionObj->weighting, 2);
    }
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
$content = $table->toHtml();

// Format B
$headers = [
    get_lang('Question'),
    get_lang('Answer'),
    get_lang('Correct'),
    get_lang('Number of learners who selected it'),
];

$data = [];

if (!empty($question_list)) {
    $id = 0;
    foreach ($question_list as $question_id) {
        $questionObj = Question::read($question_id);
        $exercise_stats = ExerciseLib::get_student_stats_by_question(
            $question_id,
            $exerciseId,
            $courseCode,
            $sessionId
        );

        $answer = new Answer($question_id);
        $answer_count = $answer->selectNbrAnswers();

        for ($answer_id = 1; $answer_id <= $answer_count; $answer_id++) {
            $answer_info = $answer->selectAnswer($answer_id);
            $is_correct = $answer->isCorrect($answer_id);
            $correct_answer = $is_correct == 1 ? get_lang('Yes') : get_lang('No');
            $real_answer_id = $answer->selectAutoId($answer_id);

            // Overwriting values depending of the question
            switch ($questionObj->type) {
                case FILL_IN_BLANKS:
                    $answer_info_db = $answer_info;
                    $answer_info = substr($answer_info, 0, strpos($answer_info, '::'));
                    $correct_answer = $is_correct;
                    $answers = $objExercise->fill_in_blank_answer_to_array($answer_info);
                    $counter = 0;
                    foreach ($answers as $answer_item) {
                        if ($counter == 0) {
                            $data[$id]['name'] = cut($questionObj->question, 100);
                        } else {
                            $data[$id]['name'] = '-';
                        }
                        $data[$id]['answer'] = $answer_item;

                        $answer_item = api_substr($answer_item, 1);
                        $answer_item = api_substr($answer_item, 0, api_strlen($answer_item) - 1);

                        $data[$id]['answer'] = $answer_item;
                        $data[$id]['correct'] = '-';

                        $count = ExerciseLib::getNumberStudentsFillBlanksAnswerCount($question_id, $exerciseId);
                        $count = isset($count[$counter]) ? $count[$counter] : 0;

                        $percentage = 0;
                        if (!empty($count_students)) {
                            $percentage = $count / $count_students * 100;
                        }
                        $data[$id]['attempts'] = Display::bar_progress(
                            $percentage,
                            false,
                            $count.' / '.$count_students
                        );
                        $id++;
                        $counter++;
                    }
                    break;
                case MATCHING:
                case MATCHING_DRAGGABLE:
                    if ($is_correct == 0) {
                        if ($answer_id == 1) {
                            $data[$id]['name'] = cut($questionObj->question, 100);
                        } else {
                            $data[$id]['name'] = '-';
                        }
                        $correct = '';
                        for ($i = 1; $i <= $answer_count; $i++) {
                            $is_correct_i = $answer->isCorrect($i);
                            if ($is_correct_i != 0 && $is_correct_i == $answer_id) {
                                $correct = $answer->selectAnswer($i);
                                break;
                            }
                        }
                        $data[$id]['answer'] = $correct;
                        $data[$id]['correct'] = $answer_info;

                        $count = ExerciseLib::get_number_students_answer_count(
                            $answer_id,
                            $question_id,
                            $exerciseId,
                            $courseCode,
                            $sessionId,
                            MATCHING
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
                    break;
                case HOT_SPOT:
                    if ($answer_id == 1) {
                        $data[$id]['name'] = cut($questionObj->question, 100);
                    } else {
                        $data[$id]['name'] = '-';
                    }
                    $data[$id]['answer'] = $answer_info;
                    $data[$id]['correct'] = '-';

                    $count = ExerciseLib::get_number_students_answer_hotspot_count(
                        $answer_id,
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
    }
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

$interbreadcrumb[] = [
    "url" => "exercise.php?".api_get_cidreq(),
    "name" => get_lang('Tests'),
];
$interbreadcrumb[] = [
    "url" => "admin.php?exerciseId=$exerciseId&".api_get_cidreq(),
    "name" => $objExercise->selectTitle(true),
];

$tpl = new Template(get_lang('Report by question'));
$actions = '<a href="exercise_report.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'">'.
    Display:: return_icon(
        'back.png',
        get_lang('Go back to the questions list'),
        '',
        ICON_SIZE_MEDIUM
    )
    .'</a>';
$actions = Display::div($actions, ['class' => 'actions']);
$content = $actions.$content;
$tpl->assign('content', $content);
$tpl->display_one_col_template();
