<?php

/* For licensing terms, see /license.txt */

/**
 * Exercise preview.
 *
 * @author Julio Montoya <gugli100@gmail.com>
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_QUIZ;
Exercise::cleanSessionVariables();
$this_section = SECTION_COURSES;

//$js = '<script>'.api_get_language_translate_html().'</script>';
//$htmlHeadXtra[] = $js;

// Notice for unauthorized people.
api_protect_course_script(true);
$sessionId = api_get_session_id();
$courseCode = api_get_course_id();
$exercise_id = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : 0;

$objExercise = new Exercise();
$result = $objExercise->read($exercise_id, true);

if (!$result) {
    api_not_allowed(true);
}

if ('true' === api_get_plugin_setting('positioning', 'tool_enable')) {
    $plugin = Positioning::create();
    if ($plugin->blockFinalExercise(api_get_user_id(), $exercise_id, api_get_course_int_id(), $sessionId)) {
        api_not_allowed(true);
    }
}

$learnpath_id = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : null;
$learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : null;
$learnpathItemViewId = isset($_REQUEST['learnpath_item_view_id']) ? (int) $_REQUEST['learnpath_item_view_id'] : null;
$origin = api_get_origin();

$logInfo = [
    'tool' => TOOL_QUIZ,
    'tool_id' => $exercise_id,
    'action' => isset($_REQUEST['learnpath_id']) ? 'learnpath_id' : '',
    'action_details' => isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : '',
];
Event::registerLog($logInfo);

$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Tests'),
];
$interbreadcrumb[] = ['url' => '#', 'name' => $objExercise->selectTitle(true)];

$time_control = false;
$clock_expired_time = ExerciseLib::get_session_time_control_key($objExercise->id, $learnpath_id, $learnpath_item_id);

if (0 != $objExercise->expired_time && !empty($clock_expired_time)) {
    $time_control = true;
}

$htmlHeadXtra[] = api_get_build_js('exercise.js');
if ($time_control) {
    // Get time left for expiring time
    $time_left = api_strtotime($clock_expired_time, 'UTC') - time();
    /*$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/stylesheet/jquery.epiclock.css');
    $htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/renderers/minute/epiclock.minute.css');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.dateformat.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.epiclock.min.js');
    $htmlHeadXtra[] = api_get_js('epiclock/renderers/minute/epiclock.minute.js');*/
    $htmlHeadXtra[] = $objExercise->showTimeControlJS($time_left);
}

if (!in_array($origin, ['learnpath', 'embeddable', 'mobileapp'])) {
    SessionManager::addFlashSessionReadOnly();
    Display::display_header();
} else {
    $htmlHeadXtra[] = '
    <style>
    body { background: none;}
    </style>
    ';
    Display::display_reduced_header();
}

if ('mobileapp' === $origin) {
    $actions = '<a href="javascript:window.history.go(-1);">'.
        Display::return_icon('back.png', get_lang('GoBackToQuestionList'), [], 32).'</a>';
    echo Display::toolbarAction('toolbar', [$actions]);
}

$html = '';
$message = '';
$html .= '<div class="exercise-overview">';
$is_allowed_to_edit = api_is_allowed_to_edit(null, true);
$editLink = '';
if ($is_allowed_to_edit) {
    if ($objExercise->sessionId == $sessionId) {
        $editLink = Display::url(
            Display::return_icon('edit.png', get_lang('Edit'), [], ICON_SIZE_SMALL),
            api_get_path(WEB_CODE_PATH).'exercise/admin.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id
        );
    }
    $editLink .= Display::url(
        Display::return_icon('test_results.png', get_lang('Results and feedback and feedback'), [], ICON_SIZE_SMALL),
        api_get_path(WEB_CODE_PATH).'exercise/exercise_report.php?'.api_get_cidreq().'&exerciseId='.$objExercise->id,
        ['title' => get_lang('Results and feedback and feedback')]
    );
}

$iconExercise = Display::return_icon('test-quiz.png', null, [], ICON_SIZE_MEDIUM);

// Exercise name.
if ('true' === api_get_setting('editor.save_titles_as_html')) {
    $html .= Display::div(
        $objExercise->get_formated_title().PHP_EOL.$editLink
    );
} else {
    $html .= Display::page_header(
        $iconExercise.PHP_EOL.$objExercise->selectTitle().PHP_EOL.$editLink
    );
}

// Exercise description.
if (!empty($objExercise->description)) {
    $html .= Display::div($objExercise->description, ['class' => 'exercise_description']);
}

$extra_params = '';
if (isset($_GET['preview'])) {
    $extra_params = '&preview=1';
}

$exercise_stat_info = $objExercise->get_stat_track_exercise_info(
    $learnpath_id,
    $learnpath_item_id,
    0
);

//1. Check if this is a new attempt or a previous
$label = get_lang('Start test');
if ($time_control && !empty($clock_expired_time) || isset($exercise_stat_info['exe_id'])) {
    $label = get_lang('Proceed with the test');
}

if (isset($exercise_stat_info['exe_id'])) {
    $message = Display::return_message(get_lang('You have tried to resolve this exercise earlier'));
}

// 2. Exercise button
// Notice we not add there the lp_item_view_id because is not already generated
$exercise_url = api_get_path(WEB_CODE_PATH).'exercise/exercise_submit.php?'.
    api_get_cidreq().'&exerciseId='.$objExercise->id.'&learnpath_id='.$learnpath_id.'&learnpath_item_id='.$learnpath_item_id.'&learnpath_item_view_id='.$learnpathItemViewId.$extra_params;
$exercise_url_button = Display::url(
    $label,
    $exercise_url,
    ['class' => 'btn btn--success btn-large']
);

$btnCheck = '';
$quizCheckButtonEnabled = ('true' === api_get_setting('exercise.quiz_check_button_enable'));
if ($quizCheckButtonEnabled) {
    $btnCheck = Display::button(
            'quiz_check_request_button',
            Display::getMdiIcon('loading', 'animate-spin hidden').' '.get_lang('TestYourBrowser'),
            [
                'type' => 'button',
                'role' => 'button',
                'id' => 'quiz-check-request-button',
                'class' => 'btn btn--plain',
                'data-loading-text' => get_lang('Loading'),
                'autocomplete' => 'off',
            ]
        ).PHP_EOL.'<strong id="quiz-check-request-text"></strong>';
}

// 3. Checking visibility of the exercise (overwrites the exercise button).
$visible_return = $objExercise->is_visible(
    $learnpath_id,
    $learnpath_item_id,
    null,
    true
);

// Exercise is not visible remove the button
if (false == $visible_return['value']) {
    if ($is_allowed_to_edit) {
        $message = Display::return_message(get_lang('This item is invisible for learner but you have access as teacher.'), 'warning');
    } else {
        $message = $visible_return['message'];
        $exercise_url_button = null;
    }
}

if (!api_is_allowed_to_session_edit()) {
    $exercise_url_button = null;
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
$table_content = '';

/* Make a special case for IE, which doesn't seem to be able to handle the
 * results popup -> send it to the full results page */

$url_suffix = '';
$btn_class = ' ';
$blockShowAnswers = false;
if (in_array(
    $objExercise->results_disabled,
    [
        RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
        //RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
        RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
    ])
) {
    if (count($attempts) < $objExercise->attempts) {
        $blockShowAnswers = true;
    }
}

$certificateBlock = '';

if (!empty($attempts)) {
    $i = $counter;
    foreach ($attempts as $attempt_result) {
        if (empty($certificateBlock)) {
            $certificateBlock = ExerciseLib::generateAndShowCertificateBlock(
                $attempt_result['score'],
                $attempt_result['max_score'],
                $objExercise,
                $attempt_result['exe_user_id'],
                api_get_course_int_id(),
                $sessionId
            );
        }

        $score = ExerciseLib::show_score($attempt_result['score'], $attempt_result['max_score']);
        $attempt_url = api_get_path(WEB_CODE_PATH).'exercise/result.php?';
        $attempt_url .= api_get_cidreq().'&show_headers=1&';
        $attempt_url .= http_build_query(['id' => $attempt_result['exe_id']]);
        $attempt_url .= $url_suffix;

        $attempt_link = Display::url(
            get_lang('Show'),
            $attempt_url,
            [
                'class' => $btn_class.'btn btn--plain',
                'data-title' => get_lang('Show'),
                'data-size' => 'lg',
            ]
        );

        $teacher_revised = Display::label(get_lang('Validated'), 'success');
        if (0 == $attempt_result['attempt_revised']) {
            $teacher_revised = Display::label(get_lang('Not validated'), 'info');
        }
        $row = [
            'count' => $i,
            'date' => api_convert_and_format_date($attempt_result['start_date'], DATE_TIME_FORMAT_LONG),
            'userIp' => $attempt_result['user_ip'],
        ];
        $attempt_link .= PHP_EOL.$teacher_revised;

        if (in_array(
            $objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                RESULT_DISABLE_SHOW_SCORE_ONLY,
                RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
                RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
                RESULT_DISABLE_RANKING,
                RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
            ]
        )) {
            $row['result'] = $score;
        }

        if (in_array(
            $objExercise->results_disabled,
            [
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS,
                RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING,
                RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES,
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT,
                RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK,
                RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK,
                RESULT_DISABLE_RANKING,
                RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER,
            ]
        ) || (
            RESULT_DISABLE_SHOW_SCORE_ONLY == $objExercise->results_disabled &&
            EXERCISE_FEEDBACK_TYPE_END == $objExercise->getFeedbackType()
        )
        ) {
            if ($blockShowAnswers &&
                RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK != $objExercise->results_disabled
            ) {
                $attempt_link = '';
            }
            if (true == $blockShowAnswers &&
                RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK == $objExercise->results_disabled
            ) {
                if (isset($row['result'])) {
                    unset($row['result']);
                }
            }

            if (!empty($objExercise->getResultAccess())) {
                if (!$objExercise->hasResultsAccess($attempt_result)) {
                    $attempt_link = '';
                }
            }
            $row['attempt_link'] = $attempt_link;
        }
        $my_attempt_array[] = $row;
        $i--;
    }

    $header_names = [];
    $table = new HTML_Table(['class' => 'table table-striped table-hover']);
    // Hiding score and answer.
    switch ($objExercise->results_disabled) {
        case RESULT_DISABLE_DONT_SHOW_SCORE_ONLY_IF_USER_FINISHES_ATTEMPTS_SHOW_ALWAYS_FEEDBACK:
            if ($blockShowAnswers) {
                $header_names = [get_lang('Attempt'), get_lang('Start Date'), get_lang('IP'), get_lang('Details')];
            } else {
                $header_names = [
                    get_lang('Attempt'),
                    get_lang('Start Date'),
                    get_lang('IP'),
                    get_lang('Score'),
                    get_lang('Details'),
                ];
            }

            break;
        case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT_NO_FEEDBACK:
        case RESULT_DISABLE_SHOW_SCORE_ATTEMPT_SHOW_ANSWERS_LAST_ATTEMPT:
            if ($blockShowAnswers) {
                $header_names = [get_lang('Attempt'), get_lang('Start Date'), get_lang('IP'), get_lang('Score')];
            } else {
                $header_names = [
                    get_lang('Attempt'),
                    get_lang('Start Date'),
                    get_lang('IP'),
                    get_lang('Score'),
                    get_lang('Details'),
                ];
            }

            break;
        case RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS:
        case RESULT_DISABLE_SHOW_SCORE_AND_EXPECTED_ANSWERS_AND_RANKING:
        case RESULT_DISABLE_SHOW_FINAL_SCORE_ONLY_WITH_CATEGORIES:
        case RESULT_DISABLE_RANKING:
        case RESULT_DISABLE_SHOW_ONLY_IN_CORRECT_ANSWER:
            $header_names = [
                get_lang('Attempt'),
                get_lang('Start Date'),
                get_lang('IP'),
                get_lang('Score'),
                get_lang('Details'),
            ];

            break;
        case RESULT_DISABLE_NO_SCORE_AND_EXPECTED_ANSWERS:
            $header_names = [get_lang('Attempt'), get_lang('Start Date'), get_lang('IP')];

            break;
        case RESULT_DISABLE_SHOW_SCORE_ONLY:
            if (EXERCISE_FEEDBACK_TYPE_END != $objExercise->getFeedbackType()) {
                $header_names = [get_lang('Attempt'), get_lang('Start Date'), get_lang('IP'), get_lang('Score')];
            } else {
                $header_names = [
                    get_lang('Attempt'),
                    get_lang('Start Date'),
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
    $table_content = $table->toHtml();
}

$selectAttempts = $objExercise->selectAttempts();
if ($selectAttempts) {
    $attempt_message = get_lang('Attempts').' '.$counter.' / '.$selectAttempts;
    if ($counter == $selectAttempts) {
        $attempt_message = Display::return_message($attempt_message, 'error');
    } else {
        $attempt_message = Display::return_message($attempt_message, 'info');
    }
    if (true == $visible_return['value']) {
        $message .= $attempt_message;
    }
}

if ($time_control) {
    $html .= $objExercise->returnTimeLeftDiv();
}

$html .= $message;

$disable = ('true' === api_get_setting('exercise.exercises_disable_new_attempts'));
if ($disable && empty($exercise_stat_info)) {
    $exercise_url_button = Display::return_message(get_lang('The portal do not allowed to start new test for the moment, please come back later.'));
}

$isLimitReached = ExerciseLib::isQuestionsLimitPerDayReached(
    api_get_user_id(),
    count($objExercise->get_validated_question_list()),
    api_get_course_int_id(),
    api_get_session_id()
);

if (!empty($exercise_url_button) && !$isLimitReached) {
    if ($quizCheckButtonEnabled) {
        $html .= Display::div(
            $btnCheck,
            ['class' => 'exercise_overview_options']
        );
        $html .= '<br>';
    }

    $html .= Display::div(
        Display::div(
            $exercise_url_button,
            ['class' => 'exercise_overview_options']
        ),
        ['class' => 'options']
    );
}

if ($isLimitReached) {
    $maxQuestionsAnswered = (int) api_get_course_setting('quiz_question_limit_per_day');

    $html .= Display::return_message(
        sprintf(get_lang('Sorry, you have reached the maximum number of questions (%s) for the day. Please try again tomorrow.'), $maxQuestionsAnswered),
        'warning',
        false
    );
}

$html .= Display::tag(
    'div',
    $table_content,
    ['class' => 'table-responsive']
);
$html .= '</div>';

if ($certificateBlock) {
    $html .= PHP_EOL.$certificateBlock;
}

if ($quizCheckButtonEnabled) {
    $quizCheckRequestUrl = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&a=browser_test';
    $params = http_build_query(
        [
            'exe_id' => 1,
            'exerciseId' => $exercise_id,
            'learnpath_id' => $learnpath_id,
            'learnpath_item_id' => $learnpath_item_id,
            'learnpath_item_view_id' => $learnpathItemViewId,
            'reminder' => '0',
            'type' => 'simple',
            'question_id' => 23,
            'choice[23]' => 45,
        ]
    ).'&'.api_get_cidreq();

    $html .= "<script>
        $(function () {
            var btnTest = $('#quiz-check-request-button'),
                iconBtnTest = btnTest.children('.animate-spin');

            btnTest.on('click', function (e) {
                e.preventDefault();

                btnTest.prop('disabled', true).removeClass('btn--success btn--danger').addClass('btn--plain');
                iconBtnTest.removeClass('hidden');

                var txtResult = $('#quiz-check-request-text').removeClass('text-success text-error').hide();

                $
                    .when(
                        $.ajax({
                            url: '$quizCheckRequestUrl',
                            type: 'post',
                            data: '$params'
                        }),
                        $.ajax({
                            url: '$quizCheckRequestUrl',
                            type: 'post',
                            data: '$params&sleep=1'
                        })
                    )
                    .then(
                        function (xhr1, xhr2) {
                            var xhr1IsOk = !!xhr1 && xhr1[1] === 'success' && !!xhr1[0] && 'ok' === xhr1[0];
                            var xhr2IsOk = !!xhr2 && xhr2[1] === 'success' && !!xhr2[0] && 'ok' === xhr2[0];

                            if (xhr1IsOk && xhr2IsOk) {
                                btnTest.removeClass('btn--plain btn--danger').addClass('btn--success');
                                txtResult.text(\"".get_lang('QuizBrowserCheckOK')."\").addClass('text-success').show();
                            } else {
                                btnTest.removeClass('btn--plain btn--success').addClass('btn--danger');
                                txtResult.text(\"".get_lang('QuizBrowserCheckKO')."\").addClass('text-error').show();
                            }
                        },
                        function () {
                            txtResult.text(\"".get_lang('QuizBrowserCheckKO')."\").addClass('text-error').show();
                            btnTest.removeClass('btn--plain btn--success').addClass('btn--danger');
                        }
                    )
                    .always(function () {
                        btnTest.prop('disabled', false);
                        iconBtnTest.addClass('hidden');
                    });
            });
        });
        </script>";
}

echo $html;

Display::display_footer();
