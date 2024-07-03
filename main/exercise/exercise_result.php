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
 * @author  Olivier Brouckaert, main author
 * @author  Roan Embrechts, some refactoring
 * @author  Julio Montoya switchable fill in blank option added
 *
 * @todo    split more code up in functions, move functions to library?
 */
$debug = false;
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_QUIZ;
$this_section = SECTION_COURSES;

api_protect_course_script(true);
$origin = api_get_origin();

/** @var Exercise $objExercise */
if (empty($objExercise)) {
    $objExercise = Session::read('objExercise');
}

$exeId = isset($_REQUEST['exe_id']) ? (int) $_REQUEST['exe_id'] : 0;
if (empty($objExercise)) {
    // Redirect to the exercise overview
    // Check if the exe_id exists
    $objExercise = new Exercise();
    $exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exeId);
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
        'name' => get_lang('ToolGradebook'),
    ];
}

$nameTools = get_lang('Exercises');
$currentUserId = api_get_user_id();

$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Exercises'),
];
if (RESULT_DISABLE_RADAR === (int) $objExercise->results_disabled) {
    $htmlHeadXtra[] = api_get_js('chartjs/Chart.min.js');
}

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/js/hotspot.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/css/hotspot.css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'annotation/js/annotation.js"></script>';
if (api_get_configuration_value('quiz_prevent_copy_paste')) {
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'jquery.nocopypaste.js"></script>';
}

if (!empty($objExercise->getResultAccess())) {
    $htmlHeadXtra[] = api_get_css(
        api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/renderers/minute/epiclock.minute.css'
    );
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.dateformat.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.epiclock.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/renderers/minute/epiclock.minute.js');
}

$showHeader = false;
$showFooter = false;
$showLearnPath = true;
$pageActions = '';
$pageTop = '';
$pageBottom = '';
$pageContent = '';
$courseInfo = api_get_course_info();
if (!in_array($origin, ['learnpath', 'embeddable', 'mobileapp', 'iframe'])) {
    // So we are not in learnpath tool
    $showHeader = true;
    $showLearnPath = false;
}

// I'm in a preview mode as course admin. Display the action menu.
if (api_is_course_admin() && !in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
    $pageActions = Display::toolbarAction(
        'exercise_result_actions',
        [
            Display::url(
                Display::return_icon('back.png', get_lang('GoBackToQuestionList'), [], 32),
                'admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->iid
            )
            .Display::url(
                Display::return_icon('settings.png', get_lang('ModifyExercise'), [], 32),
                'exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$objExercise->iid
            ),
        ]
    );
}
$exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exeId);
$learnpath_id = isset($exercise_stat_info['orig_lp_id']) ? $exercise_stat_info['orig_lp_id'] : 0;
$learnpath_item_id = isset($exercise_stat_info['orig_lp_item_id']) ? $exercise_stat_info['orig_lp_item_id'] : 0;
$learnpath_item_view_id = isset($exercise_stat_info['orig_lp_item_view_id'])
    ? $exercise_stat_info['orig_lp_item_view_id'] : 0;
$exerciseId = isset($exercise_stat_info['exe_exo_id']) ? $exercise_stat_info['exe_exo_id'] : 0;

$logInfo = [
    'tool' => TOOL_QUIZ,
    'tool_id' => $objExercise->iid,
    'action' => $learnpath_id,
    'action_details' => $learnpath_id,
];
Event::registerLog($logInfo);

$allowSignature = ExerciseSignaturePlugin::exerciseHasSignatureActivated($objExercise);
if ($allowSignature) {
    $htmlHeadXtra[] = api_get_asset('signature_pad/signature_pad.umd.js');
}

if ($origin === 'learnpath') {
    $pageTop .= '
        <form method="GET" action="exercise.php?'.api_get_cidreq().'">
            <input type="hidden" name="origin" value='.$origin.'/>
            <input type="hidden" name="learnpath_id" value="'.$learnpath_id.'"/>
            <input type="hidden" name="learnpath_item_id" value="'.$learnpath_item_id.'"/>
            <input type="hidden" name="learnpath_item_view_id" value="'.$learnpath_item_view_id.'"/>
    ';
}

$i = $total_score = $max_score = 0;
$remainingMessage = '';
$attemptButton = '';
if (!in_array($origin, ['iframe', 'embeddable'])) {
    $attemptButton = Display::toolbarButton(
        get_lang('AnotherAttempt'),
        api_get_path(WEB_CODE_PATH).'exercise/overview.php?'.api_get_cidreq().'&'.http_build_query(
            [
                'exerciseId' => $objExercise->iid,
                'learnpath_id' => $learnpath_id,
                'learnpath_item_id' => $learnpath_item_id,
                'learnpath_item_view_id' => $learnpath_item_view_id,
            ]
        ),
        'pencil-square-o',
        'info'
    );
}

// We check if the user attempts before sending to the exercise_result.php
$attempt_count = Event::get_attempt_count(
    $currentUserId,
    $objExercise->iid,
    $learnpath_id,
    $learnpath_item_id,
    $learnpath_item_view_id
);

if ($objExercise->selectAttempts() > 0) {
    if ($attempt_count >= $objExercise->selectAttempts()) {
        Display::addFlash(
            Display::return_message(
                sprintf(get_lang('ReachedMaxAttempts'), $objExercise->selectTitle(), $objExercise->selectAttempts()),
                'warning',
                false
            )
        );

        if (!in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
            $showFooter = true;
        }

        $template = new Template($nameTools, $showHeader, $showFooter);
        $template->assign('actions', $pageActions);
        $template->display_one_col_template();
        exit;
    } else {
        $attempt_count++;
        $remainingAttempts = $objExercise->selectAttempts() - $attempt_count;
        if ($remainingAttempts) {
            $attemptMessage = sprintf(get_lang('RemainingXAttempts'), $remainingAttempts);
            $remainingMessage = sprintf('<p>%s</p> %s', $attemptMessage, $attemptButton);
        }
    }
} else {
    $remainingMessage = $attemptButton ? "<p>$attemptButton</p>" : '';
}

$total_score = 0;
if (!empty($exercise_stat_info)) {
    $total_score = $exercise_stat_info['exe_result'];
}

$max_score = $objExercise->get_max_score();

if ('embeddable' === $origin) {
    $pageTop .= showEmbeddableFinishButton();
} else {
    Display::addFlash(
        Display::return_message(get_lang('Saved'), 'normal', false)
    );
}
$saveResults = true;
$feedbackType = $objExercise->getFeedbackType();

ob_start();
$stats = ExerciseLib::displayQuestionListByAttempt(
    $objExercise,
    $exeId,
    $saveResults,
    $remainingMessage,
    $allowSignature,
    api_get_configuration_value('quiz_results_answers_report'),
    false
);
$pageContent .= ob_get_contents();
ob_end_clean();

// Change settings for teacher access.
$oldResultDisabled = $objExercise->results_disabled;
$objExercise->results_disabled = RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS;
$objExercise->forceShowExpectedChoiceColumn = true;
$objExercise->disableHideCorrectAnsweredQuestions = true;

ob_start();
$statsTeacher = ExerciseLib::displayQuestionListByAttempt(
    $objExercise,
    $exeId,
    false,
    $remainingMessage,
    $allowSignature,
    api_get_configuration_value('quiz_results_answers_report'),
    false
);
ob_end_clean();

// Restore settings.
$objExercise->results_disabled = $oldResultDisabled;
$objExercise->forceShowExpectedChoiceColumn = false;
$objExercise->disableHideCorrectAnsweredQuestions = false;

// Save here LP status
if (!empty($learnpath_id) && $saveResults) {
    // Save attempt in lp
    Exercise::saveExerciseInLp($learnpath_item_id, $exeId);
}

ExerciseLib::sendNotification(
    api_get_user_id(),
    $objExercise,
    $exercise_stat_info,
    $courseInfo,
    $attempt_count++,
    $stats,
    $statsTeacher
);

$hookQuizEnd = HookQuizEnd::create();
$hookQuizEnd->setEventData(['exe_id' => $exeId]);
$hookQuizEnd->notifyQuizEnd();

$advancedCourseMessage = RemedialCoursePlugin::create()->getAdvancedCourseList(
    $objExercise,
    api_get_user_id(),
    api_get_session_id(),
    $learnpath_id ?: 0,
    $learnpath_item_id ?: 0
);
if (null != $advancedCourseMessage) {
    Display::addFlash(
        Display::return_message($advancedCourseMessage, 'info', false)
    );
}

$remedialMessage = RemedialCoursePlugin::create()->getRemedialCourseList(
    $objExercise,
    api_get_user_id(),
    api_get_session_id(),
    false,
    $learnpath_id ?: 0,
    $learnpath_item_id ?: 0
);

if (null != $remedialMessage) {
    Display::addFlash(
        Display::return_message($remedialMessage, 'warning', false)
    );
}
// Unset session for clock time
ExerciseLib::exercise_time_control_delete(
    $objExercise->iid,
    $learnpath_id,
    $learnpath_item_id
);

ExerciseLib::delete_chat_exercise_session($exeId);

if (!in_array($origin, ['learnpath', 'embeddable', 'mobileapp', 'iframe'])) {
    $pageBottom .= '<div class="question-return">';
    $pageBottom .= Display::url(
        get_lang('ReturnToCourseHomepage'),
        api_get_course_url(),
        ['class' => 'btn btn-primary']
    );
    $pageBottom .= '</div>';

    if (api_is_allowed_to_session_edit()) {
        Exercise::cleanSessionVariables();
    }

    $showFooter = true;
} elseif (in_array($origin, ['embeddable', 'mobileapp', 'iframe'])) {
    if (api_is_allowed_to_session_edit()) {
        Exercise::cleanSessionVariables();
    }
    Session::write('attempt_remaining', $remainingMessage);
    $showFooter = false;
} else {
    $lp_mode = Session::read('lp_mode');
    $url = '../lp/lp_controller.php?'.api_get_cidreq().'&action=view&lp_id='.$learnpath_id
        .'&lp_item_id='.$learnpath_item_id.'&exeId='.$exeId
        .'&fb_type='.$objExercise->getFeedbackType().'#atoc_'.$learnpath_item_id;
    $href = 'fullscreen' === $lp_mode ? ' window.opener.location.href="'.$url.'" ' : ' top.location.href="'.$url.'"';

    if (api_is_allowed_to_session_edit()) {
        Exercise::cleanSessionVariables();
    }
    Session::write('attempt_remaining', $remainingMessage);

    // Record the results in the learning path, using the SCORM interface (API)
    $pageBottom .= "<script>window.parent.API.void_save_asset('$total_score', '$max_score', 0, 'completed');</script>";
    if (empty($_SESSION['oLP']->lti_launch_id)) {
        $pageBottom .= '<script type="text/javascript">'.$href.'</script>';
    }
    $showFooter = false;
}

$template = new Template($nameTools, $showHeader, $showFooter, $showLearnPath);
$template->assign('page_top', $pageTop);
$template->assign('page_content', $pageContent);
$template->assign('page_bottom', $pageBottom);
$template->assign('allow_signature', $allowSignature);
$template->assign('exe_id', $exeId);
$template->assign('actions', $pageActions);
$template->assign('content', $template->fetch($template->get_template('exercise/result.tpl')));
$template->display_one_col_template();

function showEmbeddableFinishButton()
{
    $js = '<script>
        $(function () {
            $(\'.btn-close-quiz\').on(\'click\', function () {
                var playerId = window.frameElement.parentElement.parentElement.parentElement.id;
                window.parent.mejs.players[playerId].play();
            });
        });
    </script>';

    $html = Display::tag(
        'p',
        Display::toolbarButton(
            get_lang('GoBackToVideo'),
            '#',
            'undo',
            'warning',
            ['role' => 'button', 'class' => 'btn-close-quiz']
        ),
        ['class' => 'text-center']
    );

    // We check if a tool provider
    if (isset($_REQUEST['lti_launch_id'])) {
        $ltiLaunchId = Security::remove_XSS($_REQUEST['lti_launch_id']);
        global $exeId;
        $js .= '<script>
            $(function () {
                var url = "'.api_get_path(WEB_PLUGIN_PATH).'lti_provider/tool/api/score.php?'.api_get_cidreq().'&lti_tool=quiz&launch_id='.$ltiLaunchId.'&lti_result_id='.$exeId.'";
                $.get(url);
            });
        </script>';
    }

    return $js.PHP_EOL.$html;
}
