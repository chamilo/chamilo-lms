<?php

/* For licensing terms, see /license.txt */

/**
 * Exercise list: This script shows the list of exercises for administrators and students.
 *
 * @author Olivier Brouckaert, original author
 * @author Denes Nagy, HotPotatoes integration
 * @author Wolfgang Schneider, code/html cleanup
 */
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;
api_protect_course_script(true);

$show = isset($_GET['show']) && $_GET['show'] === 'result' ? 'result' : 'test';
$is_allowedToEdit = api_is_allowed_to_edit(null, true);
$is_tutor = api_is_allowed_to_edit(true);

if (!$is_allowedToEdit) {
    header('Location: '.api_get_path(WEB_CODE_PATH).'exercise/exercise.php?'.api_get_cidreq());
    exit;
}

$interbreadcrumb[] = [
    'url' => 'exercise_report.php?'.api_get_cidreq(),
    'name' => get_lang('Exercises'),
];
$interbreadcrumb[] = [
    'url' => 'exercise_report.php?filter=2&'.api_get_cidreq(),
    'name' => get_lang('StudentScore'),
];
$interbreadcrumb[] = [
    'url' => 'exercise_history.php?exe_id='.intval($_GET['exe_id']).'&'.api_get_cidreq(),
    'name' => get_lang('Details'),
];

$TBL_USER = Database::get_main_table(TABLE_MAIN_USER);
$TBL_EXERCISES = Database::get_course_table(TABLE_QUIZ_TEST);
$TBL_EXERCISES_QUESTION = Database::get_course_table(TABLE_QUIZ_QUESTION);
$TBL_TRACK_ATTEMPT_RECORDING = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
Display::display_header($nameTools, get_lang('Exercise'));

if (isset($_GET['message'])) {
    if (in_array($_GET['message'], ['ExerciseEdited'])) {
        $my_message_history = Security::remove_XSS($_GET['message']);
        echo Display::return_message(get_lang($my_message_history), 'confirm');
    }
}

echo '<div class="actions">';
echo '<a href="exercise_report.php?'.api_get_cidreq().'&filter=2">'.
    Display::return_icon('back.png', get_lang('BackToResultList'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</div>';

?>

<table class="table table-hover table-striped data_table">
    <tr>
        <th><?php echo get_lang('Question'); ?></th>
        <th width="50px"><?php echo get_lang('Value'); ?></th>
        <th><?php echo get_lang('Feedback'); ?></th>
        <th><?php echo get_lang('Author'); ?></th>
        <th width="160px"><?php echo get_lang('Date'); ?></th>
    </tr>
<?php

$sql = "SELECT *, quiz_question.question, firstname, lastname
        FROM $TBL_TRACK_ATTEMPT_RECORDING t, $TBL_USER,
        $TBL_EXERCISES_QUESTION quiz_question
        WHERE
            quiz_question.iid = question_id AND
            user_id = author AND
            exe_id = '".(int) $_GET['exe_id']."'
        ORDER BY position";
$query = Database::query($sql);
while ($row = Database::fetch_array($query)) {
    echo '<tr>';
    echo '<td>'.$row['question'].'</td>';
    echo '<td>'.$row['marks'].'</td>';
    if (!empty($row['teacher_comment'])) {
        echo '<td>'.$row['teacher_comment'].'</td>';
    } else {
        echo '<td>'.get_lang('WithoutComment').'</td>';
    }
    echo '<td>'.(empty($row['firstname']) && empty($row['lastname']) ? '<i>'.get_lang('OriginalValue').'</i>' : api_get_person_name($row['firstname'], $row['lastname'])).'</td>';
    echo '<td>'.api_convert_and_format_date($row['insert_date'], DATE_TIME_FORMAT_LONG).'</td>';
    echo '</tr>';
}
echo '</table>';
Display::display_footer();
