<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Exercise preview.
 *
 * @package chamilo.exercise
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

$current_course_tool = TOOL_QUIZ;

// Clear the exercise session just in case
Session::erase('objExercise');
Session::erase('calculatedAnswerId');
Session::erase('duration_time_previous');
Session::erase('duration_time');

$this_section = SECTION_COURSES;

// Notice for unauthorized people.
api_protect_course_script(true);
$sessionId = api_get_session_id();
$exercise_id = isset($_REQUEST['exerciseId']) ? intval($_REQUEST['exerciseId']) : 0;

$objExercise = new Exercise();
$result = $objExercise->read($exercise_id);
if (!$result) {
    api_not_allowed(true);
}

$learnpath_id = isset($_REQUEST['learnpath_id']) ? intval($_REQUEST['learnpath_id']) : null;
$learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? intval($_REQUEST['learnpath_item_id']) : null;
$learnpathItemViewId = isset($_REQUEST['learnpath_item_view_id']) ? intval($_REQUEST['learnpath_item_view_id']) : null;
$origin = api_get_origin();

$interbreadcrumb[] = [
    "url" => "exercise.php?".api_get_cidreq(),
    "name" => get_lang('Exercises'),
];
$interbreadcrumb[] = ["url" => "#", "name" => $objExercise->selectTitle(true)];

$time_control = false;
$clock_expired_time = ExerciseLib::get_session_time_control_key($objExercise->id, $learnpath_id, $learnpath_item_id);

if ($objExercise->expired_time != 0 && !empty($clock_expired_time)) {
    $time_control = true;
}

if ($time_control) {
    // Get time left for expiring time
    $time_left = api_strtotime($clock_expired_time, 'UTC') - time();
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/stylesheet/jquery.epiclock.css');
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/renderers/minute/epiclock.minute.css');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.dateformat.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.epiclock.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/renderers/minute/epiclock.minute.js');
    $htmlHeadXtra[] = $objExercise->showTimeControlJS($time_left);
}

if ($origin != 'learnpath') {
    //Display::display_header();
    $fluid = false;
} else {
    $htmlHeadXtra[] = "
    <style>
    body { background: none;}
    </style>
    ";
    $fluid = true;
    //Display::display_reduced_header();
}

$tpl = new Template('Overview');
$list = array();

$html = '';
$message = '';


$isAllowedToEdit = api_is_allowed_to_edit(null, true);
$list['id'] = $objExercise->id;
$list['session_id'] = $objExercise->sessionId;
$list['edit'] = $isAllowedToEdit;

// Exercise name.

if (api_get_configuration_value('save_titles_as_html')) {
    $list['title'] = $objExercise->get_formated_title().PHP_EOL;
} else {
    $list['title'] = $objExercise->selectTitle().PHP_EOL;
}

//Exercise description
if (!empty($objExercise->description)) {
    $list['description'] = $objExercise->description;
}

$extra_params = '';
if (isset($_GET['preview'])) {
    $extra_params = '&preview=1';
}

$exerciseStatInfo = $objExercise->get_stat_track_exercise_info(
    $learnpath_id,
    $learnpath_item_id,
    0
);

//1. Check if this is a new attempt or a previous
$label = get_lang('StartTest');
if ($time_control && !empty($clock_expired_time) || isset($exerciseStatInfo['exe_id'])) {
    $label = get_lang('ContinueTest');
}

$msgStatus = null;
if (isset($exerciseStatInfo['exe_id'])) {
    $msgStatus = get_lang('YouTriedToResolveThisExerciseEarlier');
}

// 2. Exercise button
// Notice we not add there the lp_item_view_id because is not already generated
$list['url'] = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id.'&learnpath_id='.$learnpath_id.'&learnpath_item_id='.$learnpath_item_id.'&learnpath_item_view_id='.$learnpathItemViewId.$extra_params;

//3. Checking visibility of the exercise (overwrites the exercise button)
$list['is_visible'] = $objExercise->is_visible(
    $learnpath_id,
    $learnpath_item_id,
    null,
    false
);

// Exercise is not visible remove the button
if ($list['is_visible']['value'] == false) {
    if ($is_allowed_to_edit) {
        $message = get_lang('ThisItemIsInvisibleForStudentsButYouHaveAccessAsTeacher');
    } else {
        $message = $visible_return['message'];
    }
}

$attempts = Event::getExerciseResultsByUser(
    api_get_user_id(),
    $objExercise->id,
    api_get_course_int_id(),
    api_get_session_id(),
    $learnpath_id,
    $learnpath_item_id,
    'desc'
);
$counter = count($attempts);

$my_attempt_array = [];
$tableContent = '';

/* Make a special case for IE, which doesn't seem to be able to handle the
 * results popup -> send it to the full results page */

$browser = new Browser();
$current_browser = $browser->getBrowser();
$url_suffix = '';
$btn_class = ' ';
if ($current_browser == 'Internet Explorer') {
    $url_suffix = '&show_headers=1';
    $btn_class = '';
}

$blockShowAnswers = false;
if ($objExercise->results_disabled == RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT) {
    if (count($attempts) < $objExercise->attempts) {
        $blockShowAnswers = true;
    }
}

if (!empty($attempts)) {
    $i = $counter;
    foreach ($attempts as $attempt_result) {
        $score = ExerciseLib::show_score(
            $attempt_result['exe_result'],
            $attempt_result['exe_weighting']
        );
        $attempt_url = api_get_path(WEB_CODE_PATH).'exercise/result.php?';
        $attempt_url .= api_get_cidreq().'&show_headers=1&';
        $attempt_url .= http_build_query([
            'id' => $attempt_result['exe_id'],
        ]);
        $attempt_url .= $url_suffix;

        $attempt_link = Display::url(
            get_lang('Show'),
            $attempt_url,
            [
                'class' => $btn_class.'btn btn-default',
                'data-title' => get_lang('Show'),
                'data-size' => 'lg',
            ]
        );

        $teacher_revised = Display::label(get_lang('Validated'), 'success');
        if ($attempt_result['attempt_revised'] == 0) {
            $teacher_revised = Display::label(get_lang('NotValidated'), 'info');
        }
        $row = [
            'count' => $i,
            'date' => api_convert_and_format_date(
                $attempt_result['start_date'],
                DATE_TIME_FORMAT_LONG
            ),
            'userIp' => $attempt_result['user_ip'],
        ];
        $attempt_link .= "&nbsp;&nbsp;&nbsp;".$teacher_revised;

        if (in_array(
            $objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                RESULT_DISABLE_SHOW_SCORE_ONLY,
                RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
            ]
        )) {
            $row['result'] = $score;
        }

        if (in_array(
            $objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
            ]
        ) || (
            $objExercise->results_disabled == RESULT_DISABLE_SHOW_SCORE_ONLY &&
            $objExercise->feedback_type == EXERCISE_FEEDBACK_TYPE_END
        )
        ) {
            if ($blockShowAnswers) {
                $attempt_link = '';
            }

            $row['attempt_link'] = $attempt_link;
        }
        $my_attempt_array[] = $row;
        $i--;
    }

    $header_names = [];
    $table = new HTML_Table(['class' => 'table table-striped table-hover']);

    // Hiding score and answer
    switch ($objExercise->results_disabled) {
        case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT:
            if ($blockShowAnswers) {
                $header_names = [get_lang('Attempt'), get_lang('StartDate'), get_lang('IP'), get_lang('Score')];
            } else {
                $header_names = [
                    get_lang('Attempt'),
                    get_lang('StartDate'),
                    get_lang('IP'),
                    get_lang('Score'),
                    get_lang('Details'),
                ];
            }
            break;
        case RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS:
        case RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES:
            $header_names = [
                get_lang('Attempt'),
                get_lang('StartDate'),
                get_lang('IP'),
                get_lang('Score'),
                get_lang('Details'),
            ];
            break;
        case RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS:
            $header_names = [get_lang('Attempt'), get_lang('StartDate'), get_lang('IP')];
            break;
        case RESULT_DISABLE_SHOW_SCORE_ONLY:
            if ($objExercise->feedback_type != EXERCISE_FEEDBACK_TYPE_END) {
                $header_names = [get_lang('Attempt'), get_lang('StartDate'), get_lang('IP'), get_lang('Score')];
            } else {
                $header_names = [
                    get_lang('Attempt'),
                    get_lang('StartDate'),
                    get_lang('IP'),
                    get_lang('Score'),
                    get_lang('Details'),
                ];
            }
            break;
    }
    $column = 0;
    foreach ($header_names as $item) {
        $table->setHeaderContents(0, $column, $item);
        $column++;
    }
    $row = 1;
    if (!empty($my_attempt_array)) {
        foreach ($my_attempt_array as $data) {
            $column = 0;
            $table->setCellContents($row, $column, $data);
            $table->setRowAttributes($row, null, true);
            $column++;
            $row++;
        }
    }
    $tableContent = $table->toHtml();
}

if ($objExercise->selectAttempts()) {
    $list['attempts'] = $objExercise->selectAttempts();
}

if ($time_control) {
    $tpl->assign('time_control',$objExercise->return_time_left_div());
}

$tpl->assign('fluid', $fluid);
$tpl->assign('data', $list);
$tpl->assign('label', $label);
$tpl->assign('message', $message);
$tpl->assign('count', $counter);
$tpl->assign('status', $msgStatus);
$tpl->assign('table_result', $tableContent);
$layout = $tpl->get_template('exercise/overview.tpl');
$tpl->assign('content', $tpl->fetch($layout));
$tpl->display_one_col_template();

