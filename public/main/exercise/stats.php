<?php

/* See license terms in /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;

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
//$question_list = $objExercise->get_validated_question_list();
$totalQuestions = $objExercise->getQuestionCount(); //Get total of questions
$question_list = $objExercise->getQuestionForTeacher(0, $totalQuestions); // get questions from 0 to total

$data = [];
// Question title 	# of students who tool it 	Lowest score 	Average 	Highest score 	Maximum score
$headers = [
    get_lang('Question'),
    get_lang('Question type'),
    get_lang('Number of times the question was answered'),
    get_lang('Lowest score'),
    get_lang('Average score'),
    get_lang('Highest score'),
    get_lang('Score'),
];

if (!empty($question_list)) {
    foreach ($question_list as $question_id) {
        $questionObj = Question::read($question_id);

        $exercise_stats = ExerciseLib::getStudentStatsByQuestion(
            $question_id,
            $exerciseId,
            $courseCode,
            $sessionId,
            true
        );

        $data[$question_id]['name'] = cut($questionObj->question, 100);
        $data[$question_id]['type'] = $questionObj->getExplanation();

        $totalTimesQuestionAnswered = ExerciseLib::getTotalQuestionAnswered(
            $questionObj->course['id'],
            $exerciseId,
            $question_id,
            true
        );
        $data[$question_id]['students_who_try_exercise'] = $totalTimesQuestionAnswered;
        $data[$question_id]['lowest_score'] = round($exercise_stats['min'], 2);
        $data[$question_id]['average_score'] = round($exercise_stats['average'], 2);
        $data[$question_id]['highest_score'] = round($exercise_stats['max'], 2);
        $data[$question_id]['max_score'] = round($questionObj->weighting, 2);
    }
}

// Format A table
$table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
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
    get_lang('Number of times this answer was selected'),
];

$data = [];

if (!empty($question_list)) {
    $id = 0;
    foreach ($question_list as $question_id) {
        $questionObj = Question::read($question_id);
        $exercise_stats = ExerciseLib::getStudentStatsByQuestion(
            $question_id,
            $exerciseId,
            $courseCode,
            $sessionId,
            true
        );

        $answer = new Answer($question_id);
        $answerCount = $answer->selectNbrAnswers();

        for ($answerId = 1; $answerId <= $answerCount; $answerId++) {
            $answerInfo = $answer->selectAnswer($answerId);
            $isCorrect = $answer->isCorrect($answerId);
            $correctAnswer = 1 == $isCorrect ? get_lang('Yes') : get_lang('No');
            $realAnswerId = $answer->selectAutoId($answerId);

            // Overwriting values depending on the question
            switch ($questionObj->type) {
                case FILL_IN_BLANKS:
                    $answerInfo = substr($answerInfo, 0, strpos($answerInfo, '::'));
                    $correctAnswer = $isCorrect;
                    $answers = $objExercise->fill_in_blank_answer_to_array($answerInfo);
                    $counter = 0;
                    foreach ($answers as $answer_item) {
                        if (0 == $counter) {
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
                        $count = $count[$counter] ?? 0;

                        $data[$id]['attempts'] = $count;
                        $id++;
                        $counter++;
                    }

                    break;
                case MATCHING:
                case MATCHING_DRAGGABLE:
                    if (0 == $isCorrect) {
                        if (1 == $answerId) {
                            $data[$id]['name'] = cut($questionObj->question, 100);
                        } else {
                            $data[$id]['name'] = '-';
                        }
                        $correct = '';
                        for ($i = 1; $i <= $answerCount; $i++) {
                            $isCorrectI = $answer->isCorrect($i);
                            if (0 != $isCorrectI && $isCorrectI == $answerId) {
                                $correct = $answer->selectAnswer($i);

                                break;
                            }
                        }
                        $data[$id]['answer'] = $correct;
                        $data[$id]['correct'] = $answerInfo;

                        $countOfAnswered = ExerciseLib::getCountOfAnswers(
                            $realAnswerId,
                            $question_id,
                            $exerciseId,
                            $courseCode,
                            $sessionId,
                            MATCHING
                        );

                        $data[$id]['attempts'] = $countOfAnswered;
                    }

                    break;
                case HOT_SPOT:
                    if (1 == $answerId) {
                        $data[$id]['name'] = cut($questionObj->question, 100);
                    } else {
                        $data[$id]['name'] = '-';
                    }
                    $data[$id]['answer'] = $answerInfo;
                    $data[$id]['correct'] = '-';

                    $count = ExerciseLib::getNumberStudentsAnswerHotspotCount(
                        $realAnswerId,
                        $question_id,
                        $exerciseId,
                        $courseCode,
                        $sessionId
                    );
                    $data[$id]['attempts'] = $count;

                    break;
                default:
                    if (1 == $answerId) {
                        $data[$id]['name'] = cut($questionObj->question, 100);
                    } else {
                        $data[$id]['name'] = '-';
                    }
                    $data[$id]['answer'] = $answerInfo;
                    $data[$id]['correct'] = $correctAnswer;

                    $countOfAnswered = ExerciseLib::getCountOfAnswers(
                        $realAnswerId,
                        $question_id,
                        $exerciseId,
                        $courseCode,
                        $sessionId
                    );
                    $data[$id]['attempts'] = $countOfAnswered;
            }
            $id++;
        }
    }
}

// Format A table
$table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
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
$exportPdf = isset($_GET['export_pdf']) && !empty($_GET['export_pdf']) ? (int) $_GET['export_pdf'] : 0;
if ($exportPdf) {
    $fileName = get_lang('Report').'_'.api_get_course_id().'_'.api_get_local_time();
    $params = [
        'filename' => $fileName,
        'pdf_title' => $objExercise->selectTitle(true).'<br>'.get_lang('ReportByQuestion'),
        'pdf_description' => get_lang('Report'),
        'format' => 'A4',
        'orientation' => 'P',
    ];

    Export::export_html_to_pdf($content, $params);
    exit;
}

$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Tests'),
];
$interbreadcrumb[] = [
    'url' => "admin.php?exerciseId=$exerciseId&".api_get_cidreq(),
    'name' => $objExercise->selectTitle(true),
];

$tpl = new Template(get_lang('Report by question'));
$actions = '<a href="exercise_report.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().'">'.
    Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Go back to the questions list'))
    .'</a>';
$actions .= Display::url(
    Display::getMdiIcon(ActionIcon::EXPORT_PDF, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('ExportToPDF')),
    'stats.php?exerciseId='.$exerciseId.'&export_pdf=1&'.api_get_cidreq()
);
$actions = Display::toolbarAction('exercise_report', [$actions]);
$content = $actions.$content;
$tpl->assign('content', $content);
$tpl->display_one_col_template();
