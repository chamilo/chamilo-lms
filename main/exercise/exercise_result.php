<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Exercise result
 * This script gets information from the script "exercise_submit.php",
 * through the session, and calculates the score of the student for
 * that exercise.
 * Then it shows the results on the screen.
 *
 * @package chamilo.exercise
 *
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

$origin = api_get_origin();

/** @var Exercise $objExercise */
if (empty($objExercise)) {
    $objExercise = Session::read('objExercise');
}

$exe_id = isset($_REQUEST['exe_id']) ? (int) $_REQUEST['exe_id'] : 0;

if (empty($objExercise)) {
    // Redirect to the exercise overview
    // Check if the exe_id exists
    $objExercise = new Exercise();
    $exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exe_id);
    if (!empty($exercise_stat_info) && isset($exercise_stat_info['exe_exo_id'])) {
        header('Location: overview.php?exerciseId='.$exercise_stat_info['exe_exo_id'].'&'.api_get_cidreq());
        exit;
    }
    api_not_allowed(true);
}

$js = '<script>'.api_get_language_translate_html().'</script>';
$htmlHeadXtra[] = $js;

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$nameTools = get_lang('Tests');

$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Tests'),
];

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/js/hotspot.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/css/hotspot.css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'annotation/js/annotation.js"></script>';
if (api_get_configuration_value('quiz_prevent_copy_paste')) {
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'jquery.nocopypaste.js"></script>';
}

if (!empty($objExercise->getResultAccess())) {
    /*$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/renderers/minute/epiclock.minute.css');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.dateformat.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.epiclock.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/renderers/minute/epiclock.minute.js');*/
}

$htmlHeadXtra[] = api_get_build_js('exercise.js');

if (!in_array($origin, ['learnpath', 'embeddable'])) {
    // So we are not in learnpath tool
    Display::display_header($nameTools, get_lang('Test'));
} else {
    $htmlHeadXtra[] = "
    <style>
    body { background: none;}
    </style>
    ";
    Display::display_reduced_header();
}

// I'm in a preview mode as course admin. Display the action menu.
if (api_is_course_admin() && !in_array($origin, ['learnpath', 'embeddable'])) {
    echo '<div class="actions">';
    echo '<a href="admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id.'">'.
        Display::return_icon('back.png', get_lang('Go back to the questions list'), [], 32).'</a>';
    echo '<a href="exercise_admin.php?'.api_get_cidreq().'&modifyTest=yes&exerciseId='.$objExercise->id.'">'.
        Display::return_icon('edit.png', get_lang('ModifyTest'), [], 32).'</a>';
    echo '</div>';
}
$exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exe_id);
$learnpath_id = isset($exercise_stat_info['orig_lp_id']) ? $exercise_stat_info['orig_lp_id'] : 0;
$learnpath_item_id = isset($exercise_stat_info['orig_lp_item_id']) ? $exercise_stat_info['orig_lp_item_id'] : 0;
$learnpath_item_view_id = isset($exercise_stat_info['orig_lp_item_view_id']) ? $exercise_stat_info['orig_lp_item_view_id'] : 0;

if ($origin === 'learnpath') {
    ?>
    <form method="GET" action="exercise.php?<?php echo api_get_cidreq(); ?>">
    <input type="hidden" name="origin" value="<?php echo $origin; ?>" />
    <input type="hidden" name="learnpath_id" value="<?php echo $learnpath_id; ?>" />
    <input type="hidden" name="learnpath_item_id" value="<?php echo $learnpath_item_id; ?>"/>
    <input type="hidden" name="learnpath_item_view_id"  value="<?php echo $learnpath_item_view_id; ?>" />
<?php
}

$i = $total_score = $max_score = 0;
$remainingMessage = '';
$attemptButton = '';

if ($origin !== 'embeddable') {
    $attemptButton = Display::toolbarButton(
        get_lang('Another attempt'),
        api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq().'&'.http_build_query([
            'exerciseId' => $objExercise->id,
            'learnpath_id' => $learnpath_id,
            'learnpath_item_id' => $learnpath_item_id,
            'learnpath_item_view_id' => $learnpath_item_view_id,
        ]),
        'pencil-square-o',
        'info'
    );
}

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
            sprintf(get_lang('You cannot take test <b>%s</b> because you have already reached the maximum of %s attempts.'), $objExercise->selectTitle(), $objExercise->selectAttempts()),
            'warning',
            false
        );
        if (!in_array($origin, ['learnpath', 'embeddable'])) {
            //we are not in learnpath tool
            Display::display_footer();
        }
        exit;
    } else {
        $attempt_count++;
        $remainingAttempts = $objExercise->selectAttempts() - $attempt_count;
        if ($remainingAttempts) {
            $attemptMessage = sprintf(get_lang('Remaining %d attempts'), $remainingAttempts);
            $remainingMessage = sprintf('<p>%s</p> %s', $attemptMessage, $attemptButton);
        }
    }
} else {
    $remainingMessage = $attemptButton ? "<p>$attemptButton</p>" : '';
}

$total_score = 0;
if (!empty($exercise_stat_info)) {
    $total_score = $exercise_stat_info['score'];
}

$max_score = $objExercise->get_max_score();

if ($origin === 'embeddable') {
    showEmbeddableFinishButton();
} else {
    echo Display::return_message(get_lang('Saved.').'<br />', 'normal', false);
}

$saveResults = true;
$feedbackType = $objExercise->getFeedbackType();
if (!in_array($feedbackType, [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
    //$saveResults = false;
}

// Display and save questions
ExerciseLib::displayQuestionListByAttempt(
    $objExercise,
    $exe_id,
    $saveResults,
    $remainingMessage
);

//Unset session for clock time
ExerciseLib::exercise_time_control_delete(
    $objExercise->id,
    $learnpath_id,
    $learnpath_item_id
);

ExerciseLib::delete_chat_exercise_session($exe_id);

if (!in_array($origin, ['learnpath', 'embeddable'])) {
    echo '<div class="question-return">';
    echo Display::url(
        get_lang('Return to Course Homepage'),
        api_get_course_url(),
        ['class' => 'btn btn-primary']
    );
    echo '</div>';

    if (api_is_allowed_to_session_edit()) {
        Exercise::cleanSessionVariables();
    }
    Display::display_footer();
} elseif ($origin === 'embeddable') {
    if (api_is_allowed_to_session_edit()) {
        Exercise::cleanSessionVariables();
    }

    Session::write('attempt_remaining', $remainingMessage);
    showEmbeddableFinishButton();
    Display::display_reduced_footer();
} else {
    $lp_mode = Session::read('lp_mode');
    $url = '../lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$learnpath_id.'&lp_item_id='.$learnpath_item_id.'&exeId='.$exercise_stat_info['exe_id'].'&fb_type='.$objExercise->getFeedbackType().'#atoc_'.$learnpath_item_id;
    $href = $lp_mode === 'fullscreen' ? ' window.opener.location.href="'.$url.'" ' : ' top.location.href="'.$url.'"';

    if (api_is_allowed_to_session_edit()) {
        Exercise::cleanSessionVariables();
    }
    Session::write('attempt_remaining', $remainingMessage);

    // Record the results in the learning path, using the SCORM interface (API)
    echo "<script>window.parent.API.void_save_asset('$total_score', '$max_score', 0, 'completed');</script>";
    echo '<script type="text/javascript">'.$href.'</script>';
    Display::display_reduced_footer();
}

function showEmbeddableFinishButton()
{
    echo '<script>
        $(function () {
            $(\'.btn-close-quiz\').on(\'click\', function () {    
                window.parent.$(\'video:not(.skip), audio:not(.skip)\').get(0).play();
            });
        });
    </script>';

    echo Display::tag(
        'p',
        Display::toolbarButton(
            get_lang('End test'),
            '#',
            'times',
            'warning',
            ['role' => 'button', 'class' => 'btn-close-quiz']
        ),
        ['class' => 'text-right']
    );
}
