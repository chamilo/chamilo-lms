<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Exercise result
 * This script gets information from the script "exercise_submit.php",
 * through the session, and calculates the score of the student for
 * that exercise.
 * Then it shows the results on the screen.
 * @package chamilo.exercise
 * @author Olivier Brouckaert, main author
 * @author Roan Embrechts, some refactoring
 * @author Julio Montoya switchable fill in blank option added
 *
 * @todo    split more code up in functions, move functions to library?
 */

$debug = false;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_COURSES;

/* 	ACCESS RIGHTS  */
api_protect_course_script(true);

if ($debug) {
    error_log('Entering exercise_result.php: '.print_r($_POST, 1));
}

// general parameters passed via POST/GET
if (empty($origin)) {
    $origin = Security::remove_XSS($_REQUEST['origin']);
}

/** @var Exercise $objExercise */
if (empty($objExercise)) {
    $objExercise = Session::read('objExercise');
}
if (empty($remind_list)) {
    $remind_list = isset($_REQUEST['remind_list']) ? $_REQUEST['remind_list'] : null;
}

$exe_id = isset($_REQUEST['exe_id']) ? intval($_REQUEST['exe_id']) : 0;

if (empty($objExercise)) {
    // Redirect to the exercise overview
    // Check if the exe_id exists
    $objExercise = new Exercise();
    $exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exe_id);
    if (!empty($exercise_stat_info) && isset($exercise_stat_info['exe_exo_id'])) {
        header("Location: overview.php?exerciseId=".$exercise_stat_info['exe_exo_id']);
        exit;
    }
    api_not_allowed();
}

$gradebook = '';
if (isset($_SESSION['gradebook'])) {
    $gradebook = $_SESSION['gradebook'];
}
if (!empty($gradebook) && $gradebook == 'view') {
    $interbreadcrumb[] = array(
        'url' => '../gradebook/'.$_SESSION['gradebook_dest'],
        'name' => get_lang('ToolGradebook'),
    );
}

$nameTools = get_lang('Exercises');

$interbreadcrumb[] = array(
    "url" => "exercise.php?".api_get_cidreq(),
    "name" => get_lang('Exercises'),
);

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/js/hotspot.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/css/hotspot.css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'annotation/js/annotation.js"></script>';

if ($origin != 'learnpath') {
    // So we are not in learnpath tool
    Display::display_header($nameTools, get_lang('Exercise'));
} else {
    $htmlHeadXtra[] = "
    <style>
    body { background: none;}
    </style>
    ";
    Display::display_reduced_header();
}

/* DISPLAY AND MAIN PROCESS */

// I'm in a preview mode as course admin. Display the action menu.
if (api_is_course_admin() && $origin != 'learnpath') {
	echo '<div class="actions">';
	echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id.'">'.
        Display::return_icon('back.png', get_lang('GoBackToQuestionList'), array(), 32).'</a>';
	echo '<a href="exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$objExercise->id.'">'.
        Display::return_icon('edit.png', get_lang('ModifyExercise'), array(), 32).'</a>';
	echo '</div>';
}

$feedback_type = $objExercise->feedback_type;
$exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exe_id);

if (!empty($exercise_stat_info['data_tracking'])) {
    $question_list = explode(',', $exercise_stat_info['data_tracking']);
}

$learnpath_id = isset($exercise_stat_info['orig_lp_id']) ? $exercise_stat_info['orig_lp_id'] : 0;
$learnpath_item_id = isset($exercise_stat_info['orig_lp_item_id']) ? $exercise_stat_info['orig_lp_item_id'] : 0;
$learnpath_item_view_id = isset($exercise_stat_info['orig_lp_item_view_id']) ? $exercise_stat_info['orig_lp_item_view_id'] : 0;

if ($origin == 'learnpath') {
?>
    <form method="GET" action="exercise.php?<?php echo api_get_cidreq() ?>">
    <input type="hidden" name="origin" value="<?php echo $origin; ?>" />
    <input type="hidden" name="learnpath_id" value="<?php echo $learnpath_id; ?>" />
    <input type="hidden" name="learnpath_item_id" 		value="<?php echo $learnpath_item_id; ?>" />
    <input type="hidden" name="learnpath_item_view_id"  value="<?php echo $learnpath_item_view_id; ?>" />
<?php
}

$i = $total_score = $max_score = 0;
$remainingMessage = '';

// We check if the user attempts before sending to the exercise_result.php
if ($objExercise->selectAttempts() > 0) {
    $attempt_count = Event::get_attempt_count(
        api_get_user_id(),
        $objExercise->id,
        $learnpath_id,
        $learnpath_item_id,
        $learnpath_item_view_id
    );
    if ($attempt_count >= $objExercise->selectAttempts()) {
        echo Display::return_message(
            sprintf(get_lang('ReachedMaxAttempts'), $objExercise->selectTitle(), $objExercise->selectAttempts()),
            'warning',
            false
        );
        if ($origin != 'learnpath') {
            //we are not in learnpath tool
            Display::display_footer();
        }
        exit;
    } else {
        $attempt_count++;
        $remainingAttempts = $objExercise->selectAttempts() - $attempt_count;

        if ($remainingAttempts) {
            $attemptButton = Display::toolbarButton(
                get_lang('AnotherAttempt'),
                api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq().'&'.http_build_query([
                    'exerciseId' => $objExercise->id,
                    'learnpath_id' => $learnpath_id,
                    'learnpath_item_id' => $learnpath_item_id
                ]),
                'pencil-square-o',
                'info'
            );
            $attemptMessage = sprintf(get_lang('RemainingXAttempts'), $remainingAttempts);
            $remainingMessage = sprintf("<p>%s</p> %s", $attemptMessage, $attemptButton);
        }
    }
}

$total_score = 0;
if (!empty($exercise_stat_info)) {
    $total_score = $exercise_stat_info['exe_result'];
}

$max_score = $objExercise->get_max_score();

echo Display::return_message(get_lang('Saved').'<br />', 'normal', false);

// Display and save questions
ExerciseLib::displayQuestionListByAttempt(
    $objExercise,
    $exe_id,
    true,
    $remainingMessage
);

//Unset session for clock time
ExerciseLib::exercise_time_control_delete(
    $objExercise->id,
    $learnpath_id,
    $learnpath_item_id
);

ExerciseLib::delete_chat_exercise_session($exe_id);

if ($origin != 'learnpath') {
    echo '<div class="question-return">';
    echo Display::url(
        get_lang('ReturnToCourseHomepage'),
        api_get_course_url(),
        array('class' => 'btn btn-primary')
    );
    echo '</div>';

    if (api_is_allowed_to_session_edit()) {
        Session::erase('objExercise');
        Session::erase('exe_id');
    }
    Display::display_footer();
} else {
	$lp_mode = isset($_SESSION['lp_mode']) ? $_SESSION['lp_mode'] : null;
	$url = '../lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$learnpath_id.'&lp_item_id='.$learnpath_item_id.'&exeId='.$exercise_stat_info['exe_id'].'&fb_type='.$objExercise->feedback_type.'#atoc_'.$learnpath_item_id;
	$href = $lp_mode == 'fullscreen' ? ' window.opener.location.href="'.$url.'" ' : ' top.location.href="'.$url.'"';

    if (api_is_allowed_to_session_edit()) {
        Session::erase('objExercise');
        Session::erase('exe_id');
    }

    Session::write('attempt_remaining', $remainingMessage);

    // Record the results in the learning path, using the SCORM interface (API)
    echo "<script>window.parent.API.void_save_asset('$total_score', '$max_score', 0, 'completed');</script>";
    echo '<script type="text/javascript">'.$href.'</script>';
    echo '</body></html>';
}
