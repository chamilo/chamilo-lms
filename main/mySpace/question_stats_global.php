<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_TRACKING;

api_block_anonymous_users();

$allowToTrack = api_is_platform_admin(true, true) || api_is_teacher();

if (!$allowToTrack) {
    api_not_allowed(true);
}

$interbreadcrumb[] = ["url" => "index.php", "name" => get_lang('MySpace')];

$courseIdList = isset($_REQUEST['courses']) ? $_REQUEST['courses'] : [];
$exercises = isset($_REQUEST['exercises']) ? $_REQUEST['exercises'] : [];

$courseOptions = [];
$exerciseList = [];
$selectedExercises = [];
if (!empty($courseIdList)) {
    foreach ($courseIdList as $courseId) {
        $courseInfo = api_get_course_info_by_id($courseId);
        $courseExerciseList = ExerciseLib::get_all_exercises(
            $courseInfo,
            0,
            false,
            null,
            false,
            3
        );

        if (!empty($courseExerciseList)) {
            foreach ($courseExerciseList as $exercise) {
                $exerciseId = $exercise['iid'];
                if (in_array($exerciseId, $exercises)) {
                    $selectedExercises[$courseId][] = $exerciseId;
                }
            }
            $exerciseList += array_column($courseExerciseList, 'title', 'iid');
        }
        $courseOptions[$courseId] = $courseInfo['name'];
    }
}

$exerciseList = array_unique($exerciseList);
if (!empty($exerciseList)) {
    array_walk($exerciseList, function (&$title) {
        $title = Exercise::get_formated_title_variable($title);
    });
}

$form = new FormValidator('search_form', 'GET', api_get_self());
$form->addSelectAjax(
    'courses',
    get_lang('Course'),
    $courseOptions,
    [
        'url' => api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=search_course',
        'multiple' => true,
    ]
);

if (!empty($courseIdList)) {
    $form->addSelect(
        'exercises',
        get_lang('Exercise'),
        $exerciseList,
        [
            'multiple' => true,
        ]
    );
}

$form->setDefaults(['course_id_list' => array_keys($courseOptions)]);
$form->addButtonSearch(get_lang('Search'));

$tableContent = '';
if ($form->validate()) {
    $headers = [
        get_lang('Course'),
        get_lang('Exercise'),
        get_lang('Question'),
        get_lang('WrongAnswer').' / '.get_lang('Total'),
        '%',
    ];
    /*$table = new HTML_Table(['class' => 'table table-hover table-striped']);
    $row = 0;
    $column = 0;
    foreach ($headers as $header) {
        $table->setHeaderContents($row, $column, $header);
        $column++;
    }
    $row++;*/
    $scoreDisplay = new ScoreDisplay();
    $exercises = $form->getSubmitValue('exercises');
    if ($exercises) {
        $orderedData = [];
        foreach ($selectedExercises as $courseId => $selectedExerciseList) {
            foreach ($selectedExerciseList as $exerciseId) {
                $questions = ExerciseLib::getWrongQuestionResults($courseId, $exerciseId, null, 10);
                foreach ($questions as $data) {
                    $questionId = (int) $data['question_id'];
                    $total = ExerciseLib::getTotalQuestionAnswered($courseId, $exerciseId, $questionId);
                    /*$column = 0;
                    $table->setCellContents($row, $column++, $courseOptions[$courseId]);
                    $table->setCellContents($row, $column++, $exerciseList[$exerciseId]);
                    $table->setCellContents($row, $column++, $data['question']);
                    $table->setCellContents($row, $column++, $data['count'].' / '.$total);
                    $percentage = $data['count']/$total;
                    $table->setCellContents(
                        $row,
                        $column++,
                        $scoreDisplay->display_score([$data['count'], $total], SCORE_AVERAGE)
                    );
                    $row++;*/
                    $orderedData[] = [
                        $courseOptions[$courseId],
                        $exerciseList[$exerciseId],
                        $data['question'],
                        $data['count'].' / '.$total,
                        $scoreDisplay->display_score([$data['count'], $total], SCORE_AVERAGE),
                    ];
                }
            }
        }

        $table = new SortableTableFromArray(
            $orderedData,
            1,
            20,
            'question_tracking'
        );
        $table->column = 4;
        $column = 0;
        foreach ($headers as $header) {
            $table->set_header($column, $header, false);
            $column++;
        }

        $tableContent = $table->return_table();
    }
}

$nameTools = get_lang('ExerciseManagement');
$htmlHeadXtra[] = '<script>
$(function() {
 $("#search_form").submit();
    $("#search_form_courses").on("change", function (e) {
       $("#search_form_exercises").parent().parent().parent().hide();
       $("#search_form_exercises").each(function() {
            $(this).remove();
        });
    });
});
</script>';

Display::display_header($nameTools, get_lang('Exercise'));
$form->display();
echo $tableContent;

Display::display_footer();
