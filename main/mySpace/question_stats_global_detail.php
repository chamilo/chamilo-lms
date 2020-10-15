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
$exercisesList = [];
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
            $exercisesList += array_column($courseExerciseList, 'title', 'iid');
        }
        $courseOptions[$courseId] = $courseInfo['name'];
    }
}

$exercisesList = array_unique($exercisesList);

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
        $exercisesList,
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
        get_lang('Session'),
        get_lang('CorrectAttempts'),
        get_lang('WrongAttempts'),
        get_lang('StudentWithCorrectAnswers'),
        get_lang('StudentWithWrongAnswers'),
    ];
    $scoreDisplay = new ScoreDisplay();
    $exercises = $form->getSubmitValue('exercises');

    if ($exercises) {
        foreach ($selectedExercises as $courseId => $exerciseList) {
            $sessions = SessionManager::get_session_by_course($courseId);
            $courseTitle = $courseOptions[$courseId];

            foreach ($exerciseList as $exerciseId) {
                $exerciseTitle = $exercisesList[$exerciseId];
                $tableContent .= Display::page_subheader2($courseTitle.' - '.$exerciseTitle);

                $orderedData = [];
                foreach ($sessions as $session) {
                    $sessionId = $session['id'];
                    $correctCount = ExerciseLib::getExerciseResultsCount('correct', $courseId, $exerciseId, $sessionId);
                    $wrongCount = ExerciseLib::getExerciseResultsCount('wrong', $courseId, $exerciseId, $sessionId);

                    $correctCountStudent = ExerciseLib::getExerciseResultsCount(
                        'correct_student',
                        $courseId,
                        $exerciseId,
                        $sessionId
                    );
                    $wrongCountStudent = ExerciseLib::getExerciseResultsCount(
                        'wrong_student',
                        $courseId,
                        $exerciseId,
                        $sessionId
                    );

                    $questions = ExerciseLib::getWrongQuestionResults($courseId, $exerciseId, $sessionId, 10);
                    $orderedData[] = [
                        $session['name'],
                        $correctCount,
                        $wrongCount,
                        $correctCountStudent,
                        $wrongCountStudent,
                    ];
                }

                $table = new SortableTableFromArray(
                    $orderedData,
                    1,
                    20,
                    uniqid('question_tracking_')
                );
                $column = 0;
                foreach ($headers as $header) {
                    $table->set_header($column, $header, false);
                    $column++;
                }
                $tableContent .= $table->return_table();
            }
        }
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
