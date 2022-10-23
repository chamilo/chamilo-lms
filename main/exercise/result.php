<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Shows the exercise results.
 *
 * @author Julio Montoya - Simple exercise result page
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_QUIZ;

$id = isset($_REQUEST['id']) ? (int) $_GET['id'] : 0; // exe id
$show_headers = isset($_REQUEST['show_headers']) ? (int) $_REQUEST['show_headers'] : null;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$origin = api_get_origin();

if (in_array($origin, ['learnpath', 'embeddable', 'mobileapp', 'iframe'])) {
    $show_headers = false;
}

api_protect_course_script($show_headers);

if (empty($id)) {
    api_not_allowed($show_headers);
}

$is_allowedToEdit = api_is_allowed_to_edit(null, true) || api_is_course_tutor();

// Getting results from the exe_id. This variable also contain all the information about the exercise
$track_exercise_info = ExerciseLib::get_exercise_track_exercise_info($id);

// No track info
if (empty($track_exercise_info)) {
    api_not_allowed($show_headers);
}

$exercise_id = $track_exercise_info['exe_exo_id'];
$student_id = (int) $track_exercise_info['exe_user_id'];
$current_user_id = api_get_user_id();

$objExercise = new Exercise();
if (!empty($exercise_id)) {
    $objExercise->read($exercise_id);
}

if (empty($objExercise)) {
    api_not_allowed($show_headers);
}

// Only users can see their own results
if (!$is_allowedToEdit) {
    if ($student_id != $current_user_id) {
        api_not_allowed($show_headers);
    }
}

$allowSignature = false;
if ($student_id === $current_user_id && ExerciseSignaturePlugin::exerciseHasSignatureActivated($objExercise)) {
    // Check if signature exists.
    $signature = ExerciseSignaturePlugin::getSignature($current_user_id, $track_exercise_info);
    if (false === $signature) {
        $allowSignature = true;
    }
}

$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/css/hotspot.css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/js/hotspot.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'annotation/js/annotation.js"></script>';
if ($allowSignature) {
    $htmlHeadXtra[] = api_get_asset('signature_pad/signature_pad.umd.js');
}

if (!empty($objExercise->getResultAccess())) {
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/renderers/minute/epiclock.minute.css');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.dateformat.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.epiclock.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/renderers/minute/epiclock.minute.js');
}

if ($show_headers) {
    $interbreadcrumb[] = [
        'url' => 'exercise.php?'.api_get_cidreq(),
        'name' => get_lang('Exercises'),
    ];
    $interbreadcrumb[] = ['url' => '#', 'name' => get_lang('Result')];
    $this_section = SECTION_COURSES;
} else {
    $htmlHeadXtra[] = '<style>
        body { background: none;}
    </style>';

    if ($origin === 'mobileapp') {
        echo '<div class="actions">';
        echo '<a href="javascript:window.history.go(-1);">'.
            Display::return_icon('back.png', get_lang('GoBackToQuestionList'), [], 32).'</a>';
        echo '</div>';
    }
}

if (api_get_configuration_value('allow_skill_rel_items') == true) {
    $htmlContentExtraClass[] = 'feature-item-user-skill-on';
}

$message = Session::read('attempt_remaining');
Session::erase('attempt_remaining');

$allowExportPdf = api_get_configuration_value('quiz_results_answers_report');

ob_start();
$stats = ExerciseLib::displayQuestionListByAttempt(
    $objExercise,
    $id,
    false,
    $message,
    $allowSignature,
    $allowExportPdf,
    'export' === $action
);
$pageContent = ob_get_contents();
ob_end_clean();

switch ($action) {
    case 'export':
        if ($allowExportPdf) {
            $allAnswers = $stats['all_answers_html'];
            @$pdf = new PDF();
            $cssFile = api_get_path(SYS_CSS_PATH).'themes/chamilo/default.css';
            $title = get_lang('ResponseReport');
            $exerciseTitle = $objExercise->get_formated_title();
            $studentInfo = api_get_user_info($student_id);
            $userHeader = $objExercise->showExerciseResultHeader(
                $studentInfo,
                $track_exercise_info,
                false,
                false,
                false
            );
            $filename = get_lang('Exercise').'_'.$exerciseTitle;
            $pdf->content_to_pdf("
                    <html><body>
                    <h2 style='text-align: center'>$title</h2>
                    $userHeader
                    $allAnswers
                    </body></html>",
                file_get_contents($cssFile),
                $filename,
                api_get_course_id(),
                'D',
                false,
                null,
                false,
                true
            );
        } else {
            api_not_allowed(true);
        }
        exit;
        break;
}

$lpId = (int) $track_exercise_info['orig_lp_id'];
$lpItemId = (int) $track_exercise_info['orig_lp_item_id'];
$lpViewId = (int) $track_exercise_info['orig_lp_item_view_id'];

$pageBottom = '<div class="question-return">';
$pageBottom .= Display::url(
    get_lang('BackToAttemptList'),
    api_get_path(WEB_CODE_PATH).'exercise/overview.php?exerciseId='.$exercise_id.'&'.api_get_cidreq().
    "&learnpath_id=$lpId&learnpath_item_id=$lpItemId&learnpath_item_view_id=$lpViewId",
    ['class' => 'btn btn-primary']
);
$pageBottom .= '</div>';
$pageContent .= $pageBottom;

$template = new Template('', $show_headers, $show_headers);
$template->assign('page_content', $pageContent);
$template->assign('allow_signature', $allowSignature);
$template->assign('exe_id', $id);
$layout = $template->fetch($template->get_template('exercise/result.tpl'));
$template->assign('content', $layout);
$template->display_one_col_template();
