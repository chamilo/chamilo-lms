<?php
/* For licensing terms, see /license.txt */
/**
 * This script exports the PDF reports from a test for several students at once.
 * This script is the teacher view of a similar script (for admins) at main/admin/export_exercise_results.php.
 */
require_once __DIR__.'/../../inc/global.inc.php';

// Setting the section (for the tabs).
$this_section = SECTION_COURSES;

api_protect_course_script(true);
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$sessionId = api_get_session_id();
$courseId = api_get_course_int_id();
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : null;
$exerciseIdChanged = isset($_GET['exercise_id_changed']) ? (int) $_GET['exercise_id_changed'] : null;

$courseInfo = [];
if (empty($courseId)) {
    $exerciseId = 0;
} else {
    $courseInfo = api_get_course_info_by_id($courseId);
}

$interbreadcrumb[] = ['url' => '../exercise.php?'.api_get_cidreq(), 'name' => get_lang('Exercises')];

$confirmYourChoice = addslashes(get_lang('ConfirmYourChoice'));
$htmlHeadXtra[] = "
<script>
    function submit_form(obj) {
        document.export_all_results_form.submit();
    }

    function mark_exercise_id_changed() {
        $('#exercise_id_changed').val('0');
    }

    function confirm_your_choice() {
        return confirm('$confirmYourChoice');
    }
</script>";

// Get exercise list for this course
$exerciseList = ExerciseLib::get_all_exercises_for_course_id(
    $courseInfo,
    $sessionId,
    $courseId,
    false
);

$exerciseSelectList = [];
$exerciseSelectList = [0 => get_lang('All')];
if (is_array($exerciseList)) {
    foreach ($exerciseList as $row) {
        $exerciseTitle = $row['title'];
        $exerciseSelectList[$row['iid']] = $exerciseTitle;
    }
}

$url = api_get_self().'?'.api_get_cidreq().'&'.http_build_query(
        [
            'session_id' => $sessionId,
            'exerciseId' => $exerciseId,
            'exercise_id_changed' => $exerciseIdChanged,
        ]
    );

// Form
$form = new FormValidator('export_all_results_form', 'GET', $url);
$form->addHeader(get_lang('ExportExerciseAllResults'));
$form
    ->addSelect(
        'exerciseId',
        get_lang('Exercise'),
        $exerciseSelectList
    )
    ->setSelected($exerciseId);

$form->addDateTimePicker('start_date', get_lang('StartDate'));
$form->addDateTimePicker('end_date', get_lang('EndDate'));
$form->addRule('start_date', get_lang('InvalidDate'), 'datetime');
$form->addRule('end_date', get_lang('InvalidDate'), 'datetime');

$form->addRule(
    ['start_date', 'end_date'],
    get_lang('StartDateShouldBeBeforeEndDate'),
    'date_compare',
    'lte'
);

$form->addHidden('exercise_id_changed', '0');
$form->addButtonExport(get_lang('Export'), 'name');

if ($form->validate()) {
    $values = $form->getSubmitValues();

    $exerciseId = (int) $values['exerciseId'];
    $filterDates = [
        'start_date' => (!empty($values['start_date']) ? $values['start_date'] : ''),
        'end_date' => (!empty($values['end_date']) ? $values['end_date'] : ''),
    ];
    if ($exerciseId === 0) {
        ExerciseLib::exportAllExercisesResultsZip($sessionId, $courseId, $filterDates);
    } else {
        ExerciseLib::exportExerciseAllResultsZip($sessionId, $courseId, $exerciseId, $filterDates);
    }
}

Display::display_header(get_lang('ExportExerciseAllResults'));

echo $form->display();

Display::display_footer();
