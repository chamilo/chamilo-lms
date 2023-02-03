<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * Exercise submission
 * This script allows to run an exercise. According to the exercise type, questions
 * can be on an unique page, or one per page with a Next button.
 *
 * One exercise may contain different types of answers (unique or multiple selection,
 * matching, fill in blanks, free answer, hot-spot).
 *
 * Questions are selected randomly or not.
 *
 * When the user has answered all questions and clicks on the button "Ok",
 * it goes to exercise_result.php
 *
 * Notice : This script is also used to show a question before modifying it by
 * the administrator
 *
 * @author Olivier Brouckaert
 * @author Julio Montoya <gugli100@gmail.com>
 *            Fill in blank option added (2008)
 *            Cleaning exercises (2010),
 *            Adding hotspot delineation support (2011)
 *            Adding reminder + ajax support (2011)
 * Modified by hubert.borderiou (2011-10-21 question category)
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_QUIZ;
$this_section = SECTION_COURSES;
$debug = false;

// Notice for unauthorized people.
api_protect_course_script(true);

$origin = api_get_origin();
$is_allowedToEdit = api_is_allowed_to_edit(null, true);
$courseId = api_get_course_int_id();
$sessionId = api_get_session_id();
$glossaryExtraTools = api_get_setting('show_glossary_in_extra_tools');
$allowTimePerQuestion = api_get_configuration_value('allow_time_per_question');
if ($allowTimePerQuestion) {
    $htmlHeadXtra[] = api_get_asset('easytimer/easytimer.min.js');
}

$showPreviousButton = true;
$showGlossary = in_array($glossaryExtraTools, ['true', 'exercise', 'exercise_and_lp']);
if ('learnpath' === $origin) {
    $showGlossary = in_array($glossaryExtraTools, ['true', 'lp', 'exercise_and_lp']);
}
if ($showGlossary) {
    $htmlHeadXtra[] = '<script
        type="text/javascript"
        src="'.api_get_path(WEB_CODE_PATH).'glossary/glossary.js.php?add_ready=1&'.api_get_cidreq().'"></script>';
    $htmlHeadXtra[] = api_get_js('jquery.highlight.js');
}

$js = '<script>'.api_get_language_translate_html().'</script>';
$htmlHeadXtra[] = $js;
$htmlHeadXtra[] = api_get_js('jqueryui-touch-punch/jquery.ui.touch-punch.min.js');
$htmlHeadXtra[] = api_get_js('jquery.jsPlumb.all.js');
$htmlHeadXtra[] = api_get_js('d3/jquery.xcolor.js');
$htmlHeadXtra[] = api_get_css(api_get_path(WEB_LIBRARY_PATH).'javascript/epiclock/renderers/minute/epiclock.minute.css');
$htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.dateformat.min.js');
$htmlHeadXtra[] = api_get_js('epiclock/javascript/jquery.epiclock.min.js');
$htmlHeadXtra[] = api_get_js('epiclock/renderers/minute/epiclock.minute.js');
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/css/hotspot.css">';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/js/hotspot.js"></script>';
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'annotation/js/annotation.js"></script>';
$htmlHeadXtra[] = api_get_jquery_libraries_js(['jquery-ui', 'jquery-upload']);
if (api_get_configuration_value('quiz_prevent_copy_paste')) {
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'jquery.nocopypaste.js"></script>';
}

if ('true' === api_get_setting('enable_record_audio')) {
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'rtc/RecordRTC.js"></script>';
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'wami-recorder/recorder.js"></script>';
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'wami-recorder/gui.js"></script>';
    $htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'swfobject/swfobject.js"></script>';
    $htmlHeadXtra[] = api_get_js('record_audio/record_audio.js');
}

$zoomOptions = api_get_configuration_value('quiz_image_zoom');
if (isset($zoomOptions['options']) && !in_array($origin, ['embeddable', 'iframe', 'mobileapp'])) {
    $options = $zoomOptions['options'];
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'jquery.elevatezoom.js"></script>';
    $htmlHeadXtra[] = '<script>
        $(function() {
            $("img").each(function() {
                var attr = $(this).attr("data-zoom-image");
                // For some browsers, `attr` is undefined; for others,
                // `attr` is false.  Check for both.
                if (typeof attr !== typeof undefined && attr !== false) {
                    $(this).elevateZoom({
                        scrollZoom : true,
                        cursor: "crosshair",
                        tint:true,
                        tintColour:\'#CCC\',
                        tintOpacity:0.5,
                        zoomWindowWidth:'.$options['zoomWindowWidth'].',
                        zoomWindowHeight:'.$options['zoomWindowHeight'].',
                        zoomWindowPosition: 7
                    });
                }
            });

            $(document).contextmenu(function() {
                return false;
            });
        });
    </script>';
}

$template = new Template();

// General parameters passed via POST/GET
$learnpath_id = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : 0;
$learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : 0;
$learnpath_item_view_id = isset($_REQUEST['learnpath_item_view_id']) ? (int) $_REQUEST['learnpath_item_view_id'] : 0;
$reminder = isset($_REQUEST['reminder']) ? (int) $_REQUEST['reminder'] : 0;
$remind_question_id = isset($_REQUEST['remind_question_id']) ? (int) $_REQUEST['remind_question_id'] : 0;
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : 0;
$formSent = isset($_REQUEST['formSent']) ? $_REQUEST['formSent'] : null;
$exerciseResult = isset($_REQUEST['exerciseResult']) ? $_REQUEST['exerciseResult'] : null;
$exerciseResultCoordinates = isset($_REQUEST['exerciseResultCoordinates']) ? $_REQUEST['exerciseResultCoordinates'] : null;
$choice = isset($_REQUEST['choice']) ? $_REQUEST['choice'] : null;
$choice = empty($choice) ? isset($_REQUEST['choice2']) ? $_REQUEST['choice2'] : null : null;
$current_question = $currentQuestionFromUrl = isset($_REQUEST['num']) ? (int) $_REQUEST['num'] : null;
$currentAnswer = isset($_REQUEST['num_answer']) ? (int) $_REQUEST['num_answer'] : null;
$logInfo = [
    'tool' => TOOL_QUIZ,
    'tool_id' => $exerciseId,
    'action' => $learnpath_id,
    'action_details' => $learnpath_id,
];
Event::registerLog($logInfo);

$error = '';
$exercise_attempt_table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

/*  Teacher takes an exam and want to see a preview,
    we delete the objExercise from the session in order to get the latest
    changes in the exercise */
if (api_is_allowed_to_edit(null, true) &&
    isset($_GET['preview']) && $_GET['preview'] == 1
) {
    Session::erase('objExercise');
}

// 1. Loading the $objExercise variable
/** @var \Exercise $exerciseInSession */
$exerciseInSession = Session::read('objExercise');
if (empty($exerciseInSession) || (!empty($exerciseInSession) && $exerciseInSession->iid != $_GET['exerciseId'])) {
    // Construction of Exercise
    /** @var |Exercise $objExercise */
    $objExercise = new Exercise($courseId);
    Session::write('firstTime', true);
    if ($debug) {
        error_log('1. Setting the $objExercise variable');
    }
    Session::erase('questionList');

    // if the specified exercise doesn't exist or is disabled
    if (!$objExercise->read($exerciseId) ||
        (!$objExercise->selectStatus() && !$is_allowedToEdit && !in_array($origin, ['learnpath', 'embeddable', 'iframe']))
    ) {
        unset($objExercise);
        $error = get_lang('ExerciseNotFound');
    } else {
        // Saves the object into the session
        Session::write('objExercise', $objExercise);
    }
} else {
    Session::write('firstTime', false);
}
// 2. Checking if $objExercise is set.
/** @var |Exercise $objExercise */
if (!isset($objExercise) && isset($exerciseInSession)) {
    if ($debug) {
        error_log('2. Loading $objExercise from session');
    }
    $objExercise = $exerciseInSession;
}

$exerciseInSession = Session::read('objExercise');

// 3. $objExercise is not set, then return to the exercise list.
if (!is_object($objExercise)) {
    header('Location: exercise.php?'.api_get_cidreq());
    exit;
}

if ('true' === api_get_plugin_setting('positioning', 'tool_enable')) {
    $plugin = Positioning::create();
    if ($plugin->blockFinalExercise(api_get_user_id(), $objExercise->iid, api_get_course_int_id(), $sessionId)) {
        api_not_allowed(true);
    }
}

// if the user has submitted the form.
$exercise_title = Security::remove_XSS($objExercise->selectTitle());
$exercise_sound = $objExercise->selectSound();

// If reminder ends we jump to the exercise_reminder
if ($objExercise->review_answers) {
    if (-1 == $remind_question_id) {
        $extraParams = "&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&learnpath_item_view_id=$learnpath_item_view_id";
        $url = api_get_path(WEB_CODE_PATH).
            'exercise/exercise_reminder.php?exerciseId='.$exerciseId.'&'.api_get_cidreq().$extraParams;
        api_location($url);
    }
}

$template->assign('shuffle_answers', $objExercise->random_answers);
$templateName = $template->get_template('exercise/submit.js.tpl');
$htmlHeadXtra[] = $template->fetch($templateName);

$current_timestamp = time();
$myRemindList = [];
$time_control = false;
if (0 != $objExercise->expired_time) {
    $time_control = true;
}

// Generating the time control key for the user
$current_expired_time_key = ExerciseLib::get_time_control_key(
    $objExercise->iid,
    $learnpath_id,
    $learnpath_item_id
);

Session::write('duration_time_previous', [$current_expired_time_key => $current_timestamp]);
$durationTime = Session::read('duration_time');
if (!empty($durationTime) && isset($durationTime[$current_expired_time_key])) {
    Session::write(
        'duration_time_previous',
        [$current_expired_time_key => $durationTime[$current_expired_time_key]]
    );
}
Session::write('duration_time', [$current_expired_time_key => $current_timestamp]);

if ($time_control) {
    // Get the expired time of the current exercise in track_e_exercises
    $total_seconds = $objExercise->expired_time * 60;
}

$show_clock = true;
$user_id = api_get_user_id();
if ($objExercise->selectAttempts() > 0) {
    $messageReachedMax = Display::return_message(
        sprintf(get_lang('ReachedMaxAttempts'), $exercise_title, $objExercise->selectAttempts()),
        'warning',
        false
    );

    $attempt_html = '';
    $attempt_count = Event::get_attempt_count(
        $user_id,
        $exerciseId,
        $learnpath_id,
        $learnpath_item_id,
        $learnpath_item_view_id
    );

    if ($attempt_count >= $objExercise->selectAttempts()) {
        $show_clock = false;
        if (!api_is_allowed_to_edit(null, true)) {
            if ($objExercise->results_disabled == 0 && !in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
                // Showing latest attempt according with task BT#1628
                $exercise_stat_info = Event::getExerciseResultsByUser(
                    $user_id,
                    $exerciseId,
                    $courseId,
                    $sessionId
                );

                if (!empty($exercise_stat_info)) {
                    $isQuestionsLimitReached = ExerciseLib::isQuestionsLimitPerDayReached(
                        $user_id,
                        count($objExercise->get_validated_question_list()),
                        $courseId,
                        $sessionId
                    );

                    if ($isQuestionsLimitReached) {
                        $maxQuestionsAnswered = (int) api_get_course_setting('quiz_question_limit_per_day');
                        Display::addFlash(
                            Display::return_message(
                                sprintf(get_lang('QuizQuestionsLimitPerDayXReached'), $maxQuestionsAnswered),
                                'warning',
                                false
                            )
                        );

                        if (in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
                            Display::display_reduced_header();
                            Display::display_reduced_footer();
                        } else {
                            Display::display_header(get_lang('Exercises'));
                            Display::display_footer();
                        }
                        exit;
                    }

                    $max_exe_id = max(array_keys($exercise_stat_info));
                    $last_attempt_info = $exercise_stat_info[$max_exe_id];
                    $attempt_html .= Display::div(
                        get_lang('Date').': '.api_get_local_time($last_attempt_info['exe_date']),
                        ['id' => '']
                    );

                    $attempt_html .= $messageReachedMax;
                    if (!empty($last_attempt_info['question_list'])) {
                        foreach ($last_attempt_info['question_list'] as $questions) {
                            foreach ($questions as $question_data) {
                                $question_id = $question_data['question_id'];
                                $marks = $question_data['marks'];
                                $question_info = Question::read($question_id);
                                $attempt_html .= Display::div(
                                    $question_info->question,
                                    ['class' => 'question_title']
                                );
                                $attempt_html .= Display::div(
                                    get_lang('Score').' '.$marks,
                                    ['id' => 'question_question_titlescore']
                                );
                            }
                        }
                    }
                    $score = ExerciseLib::show_score(
                        $last_attempt_info['exe_result'],
                        $last_attempt_info['exe_weighting']
                    );
                    $attempt_html .= Display::div(
                        get_lang('YourTotalScore').' '.$score,
                        ['id' => 'question_score']
                    );
                } else {
                    $attempt_html .= $messageReachedMax;
                }
            } else {
                $attempt_html .= $messageReachedMax;
            }
        } else {
            $attempt_html .= $messageReachedMax;
        }

        if (in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
            Display::display_reduced_header();
        } else {
            Display::display_header(get_lang('Exercises'));
        }

        echo $attempt_html;
        if (!in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
            Display::display_footer();
        }
        exit;
    }
}

/* 5. Getting user exercise info (if the user took the exam before) generating exe_id */
$exercise_stat_info = $objExercise->get_stat_track_exercise_info(
    $learnpath_id,
    $learnpath_item_id,
    $learnpath_item_view_id
);

// Fix in order to get the correct question list.
$questionListUncompressed = $objExercise->getQuestionListWithMediasUncompressed();
Session::write('question_list_uncompressed', $questionListUncompressed);
$clock_expired_time = null;
if (empty($exercise_stat_info)) {
    $disable = api_get_configuration_value('exercises_disable_new_attempts');
    if ($disable) {
        api_not_allowed(true);
    }
    $total_weight = 0;
    $questionList = $objExercise->get_validated_question_list();
    foreach ($questionListUncompressed as $question_id) {
        $objQuestionTmp = Question::read($question_id);
        $total_weight += (float) $objQuestionTmp->weighting;
    }

    if ($time_control) {
        $expected_time = $current_timestamp + $total_seconds;
        if ($debug) {
            error_log('5.1. $current_timestamp '.$current_timestamp);
            error_log('5.2. $expected_time '.$expected_time);
        }

        $clock_expired_time = api_get_utc_datetime($expected_time);
        if ($debug) {
            error_log('5.3. $expected_time '.$clock_expired_time);
        }

        //Sessions  that contain the expired time
        $_SESSION['expired_time'][$current_expired_time_key] = $clock_expired_time;
        if ($debug) {
            error_log(
                '5.4. Setting the $_SESSION[expired_time]: '.$_SESSION['expired_time'][$current_expired_time_key]
            );
        }
    }

    $exe_id = $objExercise->save_stat_track_exercise_info(
        $clock_expired_time,
        $learnpath_id,
        $learnpath_item_id,
        $learnpath_item_view_id,
        $questionList,
        $total_weight
    );
    $exercise_stat_info = $objExercise->get_stat_track_exercise_info(
        $learnpath_id,
        $learnpath_item_id,
        $learnpath_item_view_id
    );

    // Send notification at the start
    if (!api_is_allowed_to_edit(null, true) &&
        !api_is_excluded_user_type()
    ) {
        $objExercise->send_mail_notification_for_exam(
            'start',
            [],
            $origin,
            $exe_id
        );
    }
} else {
    $exe_id = $exercise_stat_info['exe_id'];
    // Remember last question id position.
    $isFirstTime = Session::read('firstTime');
    if ($isFirstTime && ONE_PER_PAGE == $objExercise->type) {
        $resolvedQuestions = Event::getAllExerciseEventByExeId($exe_id);
        if (!empty($resolvedQuestions) &&
            !empty($exercise_stat_info['data_tracking'])
        ) {
            // Get current question based in data_tracking question list, instead of track_e_attempt order BT#17789.
            $resolvedQuestionsQuestionIds = array_keys($resolvedQuestions);
            $count = 0;
            $attemptQuestionList = explode(',', $exercise_stat_info['data_tracking']);
            foreach ($attemptQuestionList as $index => $question) {
                if (in_array($question, $resolvedQuestionsQuestionIds)) {
                    $count = $index;
                    continue;
                }
            }
            $current_question = $count;
            //var_dump($current_question, $index);exit;
        }
    }
}

Session::write('exe_id', $exe_id);
$checkAnswersUrl = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?a=check_answers&exe_id='.$exe_id.'&'.api_get_cidreq();
$saveDurationUrl = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?a=update_duration&exe_id='.$exe_id.'&'.api_get_cidreq();
$questionListInSession = Session::read('questionList');
$selectionType = $objExercise->getQuestionSelectionType();

$allowBlockCategory = false;
if (api_get_configuration_value('block_category_questions')) {
    $extraFieldValue = new ExtraFieldValue('exercise');
    $extraFieldData = $extraFieldValue->get_values_by_handler_and_field_variable($objExercise->iid, 'block_category');
    if ($extraFieldData && isset($extraFieldData['value']) && 1 === (int) $extraFieldData['value']) {
        $allowBlockCategory = true;
    }
}

if (!isset($questionListInSession)) {
    // Selects the list of question ID
    $questionList = $objExercise->getQuestionList();
    // Media questions.
    $media_is_activated = $objExercise->mediaIsActivated();
    // Getting order from random
    if ($media_is_activated == false &&
        (
            $objExercise->isRandom() ||
            !empty($objExercise->getRandomByCategory()) ||
            $selectionType > EX_Q_SELECTION_RANDOM
        ) &&
        isset($exercise_stat_info) &&
        !empty($exercise_stat_info['data_tracking'])
    ) {
        $questionList = explode(',', $exercise_stat_info['data_tracking']);
        $questionList = array_combine(
            range(1, count($questionList)),
            $questionList
        );
        $categoryList = [];
        if ($allowBlockCategory) {
            foreach ($questionList as $question) {
                $categoryId = TestCategory::getCategoryForQuestion($question);
                $categoryList[$categoryId][] = $question;
            }
            Session::write('categoryList', $categoryList);
        }
    }
    Session::write('questionList', $questionList);
} else {
    if (isset($objExercise) && isset($exerciseInSession)) {
        $questionList = Session::read('questionList');
    }
}
// Array to check in order to block the chat
ExerciseLib::create_chat_exercise_session($exe_id);

if (!empty($exercise_stat_info['questions_to_check'])) {
    $myRemindList = $exercise_stat_info['questions_to_check'];
    $myRemindList = explode(',', $myRemindList);
    $myRemindList = array_filter($myRemindList);
}

$params = "exe_id=$exe_id&exerciseId=$exerciseId&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&learnpath_item_view_id=$learnpath_item_view_id&".api_get_cidreq().'&reminder='.$reminder;
// It is a lti provider
$ltiLaunchId = '';
$ltiParams = '';
if (isset($_REQUEST['lti_launch_id'])) {
    $ltiLaunchId = Security::remove_XSS($_REQUEST['lti_launch_id']);
    $ltiParams = '&lti_launch_id='.$ltiLaunchId;
    $params .= $ltiParams;
}
if (2 === $reminder && empty($myRemindList)) {
    if ($debug) {
        error_log('6.2 calling the exercise_reminder.php');
    }
    header('Location: exercise_reminder.php?'.$params);
    exit;
}

/*
 * 7. Loading Time control parameters
 * If the expired time is major that zero(0) then the expired time is compute on this time.
 */
if ($time_control) {
    if ($debug) {
        error_log('7.1. Time control is enabled');
        error_log('7.2. $current_expired_time_key  '.$current_expired_time_key);
        error_log(
            '7.3. $_SESSION[expired_time][$current_expired_time_key] '.
            $_SESSION['expired_time'][$current_expired_time_key]
        );
    }

    if (!isset($_SESSION['expired_time'][$current_expired_time_key])) {
        // Timer - Get expired_time for a student.
        if (!empty($exercise_stat_info)) {
            $expired_time_of_this_attempt = $exercise_stat_info['expired_time_control'];
            if ($debug) {
                error_log('7.4 Seems that the session ends and the user want to retake the exam');
                error_log('7.5 $expired_time_of_this_attempt: '.$expired_time_of_this_attempt);
            }
            // Get the last attempt of an exercise
            $last_attempt_date = Event::getLastAttemptDateOfExercise($exercise_stat_info['exe_id']);

            /* This means that the user enters the exam but do not answer the
               first question we get the date from the track_e_exercises not from
               the track_et_attempt see #2069 */
            if (empty($last_attempt_date)) {
                $diff = $current_timestamp - api_strtotime($exercise_stat_info['start_date'], 'UTC');
                $last_attempt_date = api_get_utc_datetime(
                    api_strtotime($exercise_stat_info['start_date'], 'UTC') + $diff
                );
            } else {
                //Recalculate the time control due #2069
                $diff = $current_timestamp - api_strtotime($last_attempt_date, 'UTC');
                $last_attempt_date = api_get_utc_datetime(api_strtotime($last_attempt_date, 'UTC') + $diff);
            }

            // New expired time - it is due to the possible closure of session.
            $new_expired_time_in_seconds = api_strtotime($expired_time_of_this_attempt, 'UTC') - api_strtotime($last_attempt_date, 'UTC');
            $expected_time = $current_timestamp + $new_expired_time_in_seconds;
            $clock_expired_time = api_get_utc_datetime($expected_time);

            // First we update the attempt to today
            /* How the expired time is changed into "track_e_exercises" table,
               then the last attempt for this student should be changed too */
            $sql = "UPDATE $exercise_attempt_table SET
                      tms = '".api_get_utc_datetime()."'
                    WHERE
                        exe_id = '".$exercise_stat_info['exe_id']."' AND
                        tms = '".$last_attempt_date."' ";
            Database::query($sql);

            // Sessions that contain the expired time
            $_SESSION['expired_time'][$current_expired_time_key] = $clock_expired_time;

            if ($debug) {
                error_log('7.6. $last_attempt_date: '.$last_attempt_date);
                error_log('7.7. $new_expired_time_in_seconds: '.$new_expired_time_in_seconds);
                error_log('7.8. $expected_time1: '.$expected_time);
                error_log('7.9. $clock_expired_time: '.$clock_expired_time);
                error_log('7.10. $sql: '.$sql);
                error_log('7.11. Setting the $_SESSION[expired_time]: '.$_SESSION['expired_time'][$current_expired_time_key]);
            }
        }
    } else {
        $clock_expired_time = $_SESSION['expired_time'][$current_expired_time_key];
    }
}

// Get time left for expiring time
$time_left = api_strtotime($clock_expired_time, 'UTC') - time();

/*
 * The time control feature is enable here - this feature is enable for a jquery plugin called epiclock
 * for more details of how it works see this link : http://eric.garside.name/docs.html?p=epiclock
 */
if ($time_control) {
    //Sends the exercise form when the expired time is finished.
    $htmlHeadXtra[] = $objExercise->showTimeControlJS($time_left);
}
// in LP's is enabled the "remember question" feature?
if (!isset($_SESSION['questionList'])) {
    // selects the list of question ID
    $questionList = $objExercise->get_validated_question_list();
    if ($objExercise->isRandom() && !empty($exercise_stat_info['data_tracking'])) {
        $questionList = explode(',', $exercise_stat_info['data_tracking']);
    }
    Session::write('questionList', $questionList);
} else {
    if (isset($objExercise) && isset($_SESSION['objExercise'])) {
        $questionList = Session::read('questionList');
    }
}

$isLastQuestionInCategory = 0;
if ($allowBlockCategory &&
    ONE_PER_PAGE == $objExercise->type &&
    EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM == $selectionType
) {
    // Check current attempt.
    $currentAttempt = Event::get_exercise_results_by_attempt($exe_id, 'incomplete');
    $answeredQuestions = [];
    if (!empty($currentAttempt) && isset($currentAttempt[$exe_id]) &&
        isset($currentAttempt[$exe_id]['question_list'])
    ) {
        $answeredQuestions = array_keys($currentAttempt[$exe_id]['question_list']);
    }
    $categoryAllResolved = [];
    $categoryList = Session::read('categoryList');
    foreach ($categoryList as $categoryId => $categoryQuestionList) {
        $categoryAllResolved[$categoryId] = false;
        $answered = 1;
        foreach ($categoryQuestionList as $questionInCategoryId) {
            if (in_array($questionInCategoryId, $answeredQuestions)) {
                $answered++;
                break;
            }
        }
        if ($answered === count($categoryList[$categoryId])) {
            $categoryAllResolved[$categoryId] = true;
        }
    }

    $blockedCategories = [];
    if (isset($exercise_stat_info['blocked_categories']) && !empty($exercise_stat_info['blocked_categories'])) {
        $blockedCategories = explode(',', $exercise_stat_info['blocked_categories']);
    }

    $count = 0;
    $questionCheck = null;
    foreach ($questionList as $questionId) {
        // if it is not the right question, goes to the next loop iteration
        if ((int) $current_question === $count) {
            $questionCheck = Question::read($questionId);
            break;
        }
        $count++;
    }

    $categoryId = 0;
    if (null !== $questionCheck) {
        $categoryId = $questionCheck->category;
    }

    if ($objExercise->review_answers && isset($_GET['category_id'])) {
        $categoryId = $_GET['category_id'] ?? 0;
    }

    if (!empty($categoryId)) {
        $categoryInfo = $categoryList[$categoryId];
        $count = 1;
        $total = count($categoryList[$categoryId]);

        foreach ($categoryList[$categoryId] as $checkQuestionId) {
            if ((int) $checkQuestionId === (int) $questionCheck->iid) {
                break;
            }
            $count++;
        }

        if ($count === $total) {
            $isLastQuestionInCategory = $categoryId;
            if ($isLastQuestionInCategory) {
                // This is the last question
                if ((int) $current_question + 1 === count($questionList)) {
                    if (false === $objExercise->review_answers) {
                        $isLastQuestionInCategory = 0;
                    }
                }
            }
        }

        if (0 === $isLastQuestionInCategory) {
            $showPreviousButton = false;
        }
        if (0 === $isLastQuestionInCategory && 2 === $reminder) {
            //    $isLastQuestionInCategory = $categoryId;
        }
    }
    //var_dump($categoryId, $blockedCategories, $isLastQuestionInCategory);

    // Blocked if category was already answered.
    if ($categoryId && in_array($categoryId, $blockedCategories)) {
        // Redirect to category intro.
        $url = api_get_path(WEB_CODE_PATH).'exercise/exercise_question_reminder.php?'.
            $params.'&num='.$current_question.'&category_id='.$isLastQuestionInCategory;
        api_location($url);
    }
}

if ($debug) {
    error_log('8. Question list loaded '.print_r($questionList, 1));
}

// Real question count.
$question_count = 0;
if (!empty($questionList)) {
    $question_count = count($questionList);
}

if ($current_question > $question_count) {
    // If time control then don't change the current question, otherwise there will be a loop.
    // @todo
    if (false == $time_control) {
        $current_question = 0;
    }
}

if ($formSent && isset($_POST)) {
    if (!is_array($exerciseResult)) {
        $exerciseResult = [];
        $exerciseResultCoordinates = [];
    }

    // Only for hotspot
    if (!isset($choice) && isset($_REQUEST['hidden_hotspot_id'])) {
        $hotspot_id = (int) $_REQUEST['hidden_hotspot_id'];
        $choice = [$hotspot_id => ''];
    }

    // Only for upload answer
    if (!isset($choice) && isset($_REQUEST['uploadChoice'])) {
        $uploadAnswerFileNames = $_REQUEST['uploadChoice'];
        $choice = implode('|', $uploadAnswerFileNames[$questionId]);
    }

    // if the user has answered at least one question
    if (is_array($choice)) {
        if ($debug) {
            error_log('9.1. $choice is an array '.print_r($choice, 1));
        }
        // Also store hotspot spots in the session ($exerciseResultCoordinates
        // will be stored in the session at the end of this script)
        if (isset($_POST['hotspot'])) {
            $exerciseResultCoordinates = $_POST['hotspot'];
            if ($debug) {
                error_log('9.2. $_POST[hotspot] data '.print_r($exerciseResultCoordinates, 1));
            }
        }
        if (ALL_ON_ONE_PAGE == $objExercise->type) {
            // $exerciseResult receives the content of the form.
            // Each choice of the student is stored into the array $choice
            $exerciseResult = $choice;
        } else {
            // gets the question ID from $choice. It is the key of the array
            [$key] = array_keys($choice);
            // if the user didn't already answer this question
            if (!isset($exerciseResult[$key])) {
                // stores the user answer into the array
                $exerciseResult[$key] = $choice[$key];
                // Saving each question.
                if (!in_array($objExercise->getFeedbackType(), [EXERCISE_FEEDBACK_TYPE_DIRECT])) {
                    $nro_question = $current_question; // - 1;
                    $questionId = $key;
                    // gets the student choice for this question
                    $choice = $exerciseResult[$questionId];
                    if (isset($exe_id)) {
                        // Manage the question and answer attempts
                        $objExercise->manage_answer(
                            $exe_id,
                            $questionId,
                            $choice,
                            'exercise_show',
                            $exerciseResultCoordinates,
                            true,
                            false,
                            false,
                            $objExercise->propagate_neg,
                            []
                        );
                    }
                }
            }
        }
        if ($debug) {
            error_log('9.3.  $choice is an array - end');
            error_log('9.4.  $exerciseResult '.print_r($exerciseResult, 1));
        }
    }

    // the script "exercise_result.php" will take the variable $exerciseResult from the session
    Session::write('exerciseResult', $exerciseResult);
    Session::write('exerciseResultCoordinates', $exerciseResultCoordinates);

    // if all questions on one page OR if it is the last question (only for an exercise with one question per page)
    if ($objExercise->type == ALL_ON_ONE_PAGE || $current_question >= $question_count) {
        if (api_is_allowed_to_session_edit()) {
            // goes to the script that will show the result of the exercise
            if ($objExercise->type == ALL_ON_ONE_PAGE) {
                if ($debug) {
                    error_log('10. Exercise ALL_ON_ONE_PAGE -> Redirecting to exercise_result.php');
                }
                //We check if the user attempts before sending to the exercise_result.php
                if ($objExercise->selectAttempts() > 0) {
                    $attempt_count = Event::get_attempt_count(
                        api_get_user_id(),
                        $exerciseId,
                        $learnpath_id,
                        $learnpath_item_id,
                        $learnpath_item_view_id
                    );
                    if ($attempt_count >= $objExercise->selectAttempts()) {
                        echo Display::return_message(
                            sprintf(get_lang('ReachedMaxAttempts'), $exercise_title, $objExercise->selectAttempts()),
                            'warning',
                            false
                        );
                        if (!in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
                            //so we are not in learnpath tool
                            echo '</div>'; //End glossary div
                            Display::display_footer();
                        } else {
                            echo '</body></html>';
                        }
                    }
                }
                header("Location: exercise_result.php?".api_get_cidreq()."&exe_id=$exe_id&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&learnpath_item_view_id=$learnpath_item_view_id.$ltiParams");
                exit;
            } else {
                if ($debug) {
                    error_log('10. Redirecting to exercise_result.php');
                }
                header("Location: exercise_result.php?".api_get_cidreq()."&exe_id=$exe_id&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&learnpath_item_view_id=$learnpath_item_view_id.$ltiParams");
                exit;
            }
        } else {
            if ($debug) {
                error_log('10. Redirecting to exercise_submit.php');
            }
            header("Location: exercise_submit.php?".api_get_cidreq()."&exerciseId=$exerciseId");
            exit;
        }
    }
    if ($debug) {
        error_log('11. $formSent was set - end');
    }
}

// If questionNum comes from POST and not from GET
$latestQuestionId = Event::getLatestQuestionIdFromAttempt($exe_id);

if (is_null($current_question)) {
    $current_question = 1;
    if ($latestQuestionId) {
        $current_question = $objExercise->getPositionInCompressedQuestionList($latestQuestionId);
    }
} else {
    $current_question++;
}

if ($question_count != 0) {
    if ($objExercise->type == ALL_ON_ONE_PAGE || $current_question > $question_count) {
        if (api_is_allowed_to_session_edit()) {
            // goes to the script that will show the result of the exercise
            if ($objExercise->type == ALL_ON_ONE_PAGE) {
                if ($debug) {
                    error_log('12. Exercise ALL_ON_ONE_PAGE -> Redirecting to exercise_result.php');
                }

                // We check if the user attempts before sending to the exercise_result.php
                if ($objExercise->selectAttempts() > 0) {
                    $attempt_count = Event::get_attempt_count(
                        api_get_user_id(),
                        $exerciseId,
                        $learnpath_id,
                        $learnpath_item_id,
                        $learnpath_item_view_id
                    );
                    if ($attempt_count >= $objExercise->selectAttempts()) {
                        Display::return_message(
                            sprintf(get_lang('ReachedMaxAttempts'), $exercise_title, $objExercise->selectAttempts()),
                            'warning',
                            false
                        );
                        if (!in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
                            //so we are not in learnpath tool
                            echo '</div>'; //End glossary div
                            Display::display_footer();
                        } else {
                            echo '</body></html>';
                        }
                        exit;
                    }
                }
            } else {
                if ($objExercise->review_answers) {
                    header('Location: exercise_reminder.php?'.$params);
                    exit;
                } else {
                    $certaintyQuestionPresent = false;
                    foreach ($questionList as $questionId) {
                        $question = Question::read($questionId);
                        if ($question->type == MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
                            $certaintyQuestionPresent = true;
                            break;
                        }
                    }
                    if ($certaintyQuestionPresent) {
                        // Certainty grade question
                        // We send an email to the student before redirection to the result page
                        MultipleAnswerTrueFalseDegreeCertainty::sendQuestionCertaintyNotification(
                            $user_id, $objExercise, $exe_id
                        );
                    }

                    header("Location: exercise_result.php?"
                        .api_get_cidreq()
                        ."&exe_id=$exe_id&learnpath_id=$learnpath_id&learnpath_item_id="
                        .$learnpath_item_id
                        ."&learnpath_item_view_id=$learnpath_item_view_id.$ltiParams"
                    );
                    exit;
                }
            }
        }
    }
} else {
    $error = get_lang('ThereAreNoQuestionsForThisExercise');
    // if we are in the case where user select random by category, but didn't choose the number of random question
    if ($objExercise->getRandomByCategory() > 0 && $objExercise->random <= 0) {
        $error .= '<br/>'.get_lang('PleaseSelectSomeRandomQuestion');
    }
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('ToolGradebook'),
    ];
}

$interbreadcrumb[] = ['url' => 'exercise.php?'.api_get_cidreq(), 'name' => get_lang('Exercises')];
$interbreadcrumb[] = ['url' => '#', 'name' => $objExercise->selectTitle(true)];

// Time per question.
$questionTimeCondition = '';
$showQuestionClock = false;
if ($allowTimePerQuestion && $objExercise->type == ONE_PER_PAGE) {
    $objQuestionTmp = null;
    $previousQuestion = null;
    if (!empty($questionList)) {
        $i = 0;
        foreach ($questionList as $questionId) {
            $i++;
            $objQuestionTmp = Question::read($questionId);
            // if it is not the right question, goes to the next loop iteration
            if ($current_question == $i) {
                break;
            }
            $previousQuestion = $objQuestionTmp;
        }
    }
    $extraFieldValue = new ExtraFieldValue('question');
    $value = $extraFieldValue->get_values_by_handler_and_field_variable($objQuestionTmp->iid, 'time');
    if (!empty($value) && isset($value['value']) && !empty($value['value'])) {
        $showQuestionClock = true;
        $seconds = (int) $value['value'];
        $now = time();
        $timeSpent = Event::getAttemptQuestionDuration($exe_id, $objQuestionTmp->iid);
        // Redirect to next question.
        if ($timeSpent > $seconds) {
            $nextQuestion = (int) $currentQuestionFromUrl + 1;
            $nextQuestionUrl = api_get_path(WEB_CODE_PATH).
                "exercise/exercise_submit.php?$params&num=$nextQuestion&remind_question_id=$remind_question_id";
            api_location($nextQuestionUrl);
        }

        $seconds = $seconds - $timeSpent;
        $questionTimeCondition = "
                var timer = new easytimer.Timer();
                timer.start({countdown: true, startValues: {seconds: $seconds}});
                timer.addEventListener('secondsUpdated', function (e) {
                    $('#question_timer').html(timer.getTimeValues().toString());
                });
                timer.addEventListener('targetAchieved', function (e) {
                    $('.question-validate-btn').click();
                });
            ";
    }
}

$quizKeepAlivePingInterval = api_get_configuration_value('quiz_keep_alive_ping_interval');

if (false !== $quizKeepAlivePingInterval) {
    $quizKeepAlivePingInterval *= 1000;

    $htmlHeadXtra[] = "<script>$(function () {
        window.setInterval(function () {
            $.post(_p.web_ajax + 'exercise.ajax.php', {a: 'ping', exe_id: '{$objExercise->iid}'});
        }, $quizKeepAlivePingInterval);
    })</script>";
}

if (!in_array($origin, ['learnpath', 'embeddable', 'mobileapp', 'iframe'])) {
    //so we are not in learnpath tool
    SessionManager::addFlashSessionReadOnly();
    Display::display_header(null, 'Exercises');
} else {
    Display::display_reduced_header();
    echo '<div style="height:10px">&nbsp;</div>';
}

if ($origin === 'mobileapp') {
    echo '<div class="actions">';
    echo '<a href="javascript:window.history.go(-1);">'.
        Display::return_icon('back.png', get_lang('GoBackToQuestionList'), [], 32).'</a>';
    echo '</div>';
}

$show_quiz_edition = $objExercise->added_in_lp();
// I'm in a preview mode
if (api_is_course_admin() && !in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
    echo '<div class="actions">';
    if ($show_quiz_edition == false) {
        echo '<a href="exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$objExercise->iid.'">'.
            Display::return_icon('settings.png', get_lang('ModifyExercise'), '', ICON_SIZE_MEDIUM).'</a>';
    } else {
        echo '<a href="#">'.
            Display::return_icon('settings_na.png', get_lang('ModifyExercise'), '', ICON_SIZE_MEDIUM).'</a>';
    }
    echo '</div>';
}

$is_visible_return = $objExercise->is_visible(
    $learnpath_id,
    $learnpath_item_id,
    $learnpath_item_view_id,
    true,
    $sessionId
);

if ($is_visible_return['value'] == false) {
    echo $is_visible_return['message'];
    if (!in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
        Display::display_footer();
    }
    exit;
}

if (!api_is_allowed_to_session_edit()) {
    if (!in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
        Display::display_footer();
    }
    exit;
}

$exercise_timeover = false;
$limit_time_exists = !empty($objExercise->start_time) || !empty($objExercise->end_time) ? true : false;
if ($limit_time_exists) {
    $exercise_start_time = api_strtotime($objExercise->start_time, 'UTC');
    $exercise_end_time = api_strtotime($objExercise->end_time, 'UTC');
    $time_now = time();
    $permission_to_start = true;
    if (!empty($objExercise->start_time)) {
        $permission_to_start = (($time_now - $exercise_start_time) > 0) ? true : false;
    }
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        if (!empty($objExercise->end_time)) {
            $exercise_timeover = (($time_now - $exercise_end_time) > 0) ? true : false;
        }
    }

    if (!$permission_to_start || $exercise_timeover) {
        if (!api_is_allowed_to_edit(null, true)) {
            $message_warning = $permission_to_start ? get_lang('ReachedTimeLimit') : get_lang('ExerciseNoStartedYet');
            echo Display::return_message(
                sprintf(
                    $message_warning,
                    $exercise_title,
                    $objExercise->selectAttempts()
                ),
                'warning'
            );
            if (!in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
                Display::display_footer();
            }
            exit;
        } else {
            $message_warning = $permission_to_start ? get_lang('ReachedTimeLimitAdmin') : get_lang('ExerciseNoStartedAdmin');
            echo Display::return_message(
                sprintf(
                    $message_warning,
                    $exercise_title,
                    $objExercise->selectAttempts()
                ),
                'warning'
            );
        }
    }
}

// Blocking empty start times see BT#2800
global $_custom;
if (isset($_custom['exercises_hidden_when_no_start_date']) &&
    $_custom['exercises_hidden_when_no_start_date']
) {
    if (empty($objExercise->start_time)) {
        echo Display::return_message(
            sprintf(
                get_lang('ExerciseNoStartedYet'),
                $exercise_title,
                $objExercise->selectAttempts()
            ),
            'warning'
        );
        if (!in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
            Display::display_footer();
            exit;
        }
    }
}

if ($time_control) {
    echo $objExercise->returnTimeLeftDiv();
    echo '<div style="display:none" class="warning-message" id="expired-message-id">'.
        get_lang('ExerciseExpiredTimeMessage').'</div>';
}

if ($showQuestionClock) {
    $icon = Display::returnFontAwesomeIcon('clock-o');
    echo '<div class="well" style="text-align: center">
            '.get_lang('RemainingTimeToFinishQuestion').'
            <div id="question_timer" class="label label-warning"></div>
          </div>';
}

if (!in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
    echo '<div id="highlight-plugin" class="glossary-content">';
}
if (2 === $reminder) {
    $data_tracking = $exercise_stat_info['data_tracking'];
    $data_tracking = explode(',', $data_tracking);
    $current_question = 1; // Set by default the 1st question

    if (!empty($myRemindList)) {
        // Checking which questions we are going to call from the remind list
        for ($i = 0; $i < count($data_tracking); $i++) {
            for ($j = 0; $j < count($myRemindList); $j++) {
                if (!empty($remind_question_id)) {
                    if ($remind_question_id == $myRemindList[$j]) {
                        if ($remind_question_id == $data_tracking[$i]) {
                            if (isset($myRemindList[$j + 1])) {
                                $remind_question_id = $myRemindList[$j + 1];
                                $current_question = $i + 1;
                            } else {
                                // We end the remind list we go to the exercise_reminder.php please
                                $remind_question_id = -1;
                                $current_question = $i + 1; // last question
                            }
                            break 2;
                        }
                    }
                } else {
                    if ($myRemindList[$j] == $data_tracking[$i]) {
                        if (isset($myRemindList[$j + 1])) {
                            $remind_question_id = $myRemindList[$j + 1];
                            $current_question = $i + 1; // last question
                        } else {
                            // We end the remind list we go to the exercise_reminder.php please
                            $remind_question_id = -1;
                            $current_question = $i + 1; // last question
                        }
                        break 2;
                    }
                }
            }
        }
    } else {
        if ($objExercise->review_answers) {
            if ($debug) {
                error_log('. redirecting to exercise_reminder.php ');
            }
            header("Location: exercise_reminder.php?$params");
            exit;
        }
    }
}

if (!empty($error)) {
    Display::addFlash(Display::return_message($error, 'error', false));
    api_not_allowed();
    exit;
}

$script_php = 'exercise_result.php';
if ($objExercise->review_answers) {
    $script_php = 'exercise_reminder.php';
}

if (!empty($exercise_sound)) {
    echo "<a
        href=\"../document/download.php?doc_url=%2Faudio%2F".Security::remove_XSS($exercise_sound)."\"
        target=\"_blank\">";
    echo "<img src=\"../img/sound.gif\" border=\"0\" align=\"absmiddle\" alt=", get_lang('Sound')."\" /></a>";
}
// Get number of hotspot questions for javascript validation
$number_of_hotspot_questions = 0;
$i = 0;
if (!empty($questionList)) {
    foreach ($questionList as $questionId) {
        $i++;
        $objQuestionTmp = Question::read($questionId);
        $selectType = $objQuestionTmp->selectType();
        // for sequential exercises.
        if ($objExercise->type == ONE_PER_PAGE) {
            // if it is not the right question, goes to the next loop iteration
            if ($current_question != $i) {
                continue;
            } else {
                if (in_array($selectType, [HOT_SPOT, HOT_SPOT_COMBINATION, HOT_SPOT_DELINEATION])) {
                    $number_of_hotspot_questions++;
                }
                break;
            }
        } else {
            if (in_array($selectType, [HOT_SPOT, HOT_SPOT_COMBINATION, HOT_SPOT_DELINEATION])) {
                $number_of_hotspot_questions++;
            }
        }
    }
}

if ($allowBlockCategory &&
    ONE_PER_PAGE == $objExercise->type &&
    EX_Q_SELECTION_CATEGORIES_ORDERED_QUESTIONS_RANDOM == $selectionType
) {
    if (0 === $isLastQuestionInCategory && 2 === $reminder) {
        $endReminderValue = false;
        if (!empty($myRemindList)) {
            $endValue = end($myRemindList);
            if ($endValue == $questionId) {
                $endReminderValue = true;
            }
        }
        if ($endReminderValue) {
            $isLastQuestionInCategory = $categoryId;
        }
    }
}

$saveIcon = Display::return_icon(
    'save.png',
    get_lang('Saved'),
    [],
    ICON_SIZE_SMALL,
    false,
    true
);
$loading = Display::returnFontAwesomeIcon('spinner', null, true, 'fa-spin');

echo '<script>
    function addExerciseEvent(elm, evType, fn, useCapture) {
        if (elm.addEventListener) {
            elm.addEventListener(evType, fn, useCapture);
            return;
        } else if (elm.attachEvent) {
            elm.attachEvent(\'on\' + evType, fn);
        } else{
            elm[\'on\'+evType] = fn;
        }
        return;
    }

    var calledUpdateDuration = false;
    function updateDuration() {
        if (calledUpdateDuration === false) {
            var saveDurationUrl = "'.$saveDurationUrl.'";
            // Logout of course just in case
            $.ajax({
                url: saveDurationUrl,
                success: function (data) {
                    calledUpdateDuration = true;
                    return;
                },
            });
            return;
        }
    }

    $(function() {
        '.$questionTimeCondition.'
        // This pre-load the save.png icon
        var saveImage = new Image();
        saveImage.src = "'.$saveIcon.'";

        // Block form submition on enter
        $(".block_on_enter").keypress(function(event) {
            return event.keyCode != 13;
        });

        $(".checkCalculatedQuestionOnEnter").keypress(function(event) {
            if (event.keyCode === 13) {
                event.preventDefault();
                var id = $(this).attr("id");
                var parts = id.split("_");
                var buttonId = "button_" + parts[1];
                document.getElementById(buttonId).click();
            }
        });

        $(".main_question").mouseout(function() {
            $(this).removeClass("question_highlight");
        });

        $(".no_remind_highlight").hide();
        $("form#exercise_form").prepend($("#exercise-description"));

        $(\'button[name="previous_question_and_save"]\').on("touchstart click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            var
                $this = $(this),
                previousId = parseInt($this.data(\'prev\')) || 0,
                questionId = parseInt($this.data(\'question\')) || 0;

            previous_question_and_save(previousId, questionId);
        });

        $(\'button[name="save_question_list"]\').on(\'touchstart click\', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $this = $(this);
            var questionList = $this.data(\'list\').split(",");

            save_question_list(questionList);
        });

        $(\'button[name="check_answers"]\').on(\'touchstart click\', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $this = $(this);
            var questionId = parseInt($this.data(\'question\')) || 0;

            save_now(questionId, "check_answers");
        });

        $(\'button[name="save_now"]\').on(\'touchstart click\', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var
                $this = $(this),
                questionId = parseInt($this.data(\'question\')) || 0,
                urlExtra = $this.data(\'url\') || null;

            save_now(questionId, urlExtra);
        });

        $(\'button[name="validate_all"]\').on(\'touchstart click\', function (e) {
            e.preventDefault();
            e.stopPropagation();

            validate_all();
        });

        // Save attempt duration
        addExerciseEvent(window, \'unload\', updateDuration , false);
        addExerciseEvent(window, \'beforeunload\', updateDuration , false);
    });

    function previous_question(question_num) {
        var url = "exercise_submit.php?'.$params.'&num="+question_num;
        window.location = url;
    }

    function previous_question_and_save(previous_question_id, question_id_to_save) {
        var url = "exercise_submit.php?'.$params.'&num="+previous_question_id;
        //Save the current question
        save_now(question_id_to_save, url);
    }

    function save_question_list(question_list) {
        $.each(question_list, function(key, question_id) {
            save_now(question_id, null);
        });

        var url = "";
        if ('.$reminder.' == 1 ) {
            url = "exercise_reminder.php?'.$params.'&num='.$current_question.'";
        } else if ('.$reminder.' == 2 ) {
            url = "exercise_submit.php?'.$params.'&num='.$current_question.'&remind_question_id='.$remind_question_id.'&reminder=2";
        } else {
            url = "exercise_submit.php?'.$params.'&num='.$current_question.'&remind_question_id='.$remind_question_id.'";
        }
        window.location = url;
    }

    function redirectExerciseToResult()
    {
        window.location = "'.$script_php.'?'.$params.'";
    }

    function save_now(question_id, url_extra) {
        // 1. Normal choice inputs
        var my_choice = $(\'*[name*="choice[\'+question_id+\']"]\').serialize();

        // 2. Reminder checkbox
        var remind_list = $(\'*[name*="remind_list"]\').serialize();

        // 3. Hotspots
        var hotspot = $(\'*[name*="hotspot[\'+question_id+\']"]\').serialize();

        // 4. choice for degree of certainty
        var my_choiceDc = $(\'*[name*="choiceDegreeCertainty[\'+question_id+\']"]\').serialize();

        // 5. upload answer files
        var uploadAnswerFiles = $(\'*[name*="uploadChoice[\'+question_id+\'][]"]\').serialize();

        // Checking CkEditor
        if (question_id) {
            if (CKEDITOR.instances["choice["+question_id+"]"]) {
                var ckContent = CKEDITOR.instances["choice["+question_id+"]"].getData();
                my_choice = {};
                my_choice["choice["+question_id+"]"] = ckContent;
                my_choice = $.param(my_choice);
            }
        }

        if ($(\'input[name="remind_list[\'+question_id+\']"]\').is(\':checked\')) {
            $("#question_div_"+question_id).addClass("remind_highlight");
        } else {
            $("#question_div_"+question_id).removeClass("remind_highlight");
        }

        // Only for the first time
        var dataparam = "'.$params.'&type=simple&question_id="+question_id;
        dataparam += "&"+my_choice;
        dataparam += hotspot ? ("&" + hotspot) : "";
        dataparam += remind_list ? ("&" + remind_list) : "";
        dataparam += my_choiceDc ? ("&" + my_choiceDc) : "";
        dataparam += uploadAnswerFiles ? ("&" + uploadAnswerFiles) : "";

        $("#save_for_now_"+question_id).html(\''.$loading.'\');
        $.ajax({
            type:"post",
            url: "'.api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&a=save_exercise_by_now",
            data: dataparam,
            success: function(return_value) {
                if (return_value.ok) {
                    $("#save_for_now_"+question_id).html(\''.
                    Display::return_icon('save.png', get_lang('Saved'), [], ICON_SIZE_SMALL).'\');
                } else if (return_value.error) {
                    $("#save_for_now_"+question_id).html(\''.
                        Display::return_icon('error.png', get_lang('Error'), [], ICON_SIZE_SMALL).'\');
                } else if (return_value.type == "one_per_page") {
                    var url = "";
                    if ('.$reminder.' == 1 ) {
                        url = "exercise_reminder.php?'.$params.'&num='.$current_question.'";
                    } else if ('.$reminder.' == 2 ) {
                        url = "exercise_submit.php?'.$params.'&num='.$current_question.
                            '&remind_question_id='.$remind_question_id.'&reminder=2";
                    } else {
                        url = "exercise_submit.php?'.$params.'&num='.$current_question.
                            '&remind_question_id='.$remind_question_id.'";
                    }

                    // If last question in category send to exercise_question_reminder.php
                    if ('.$isLastQuestionInCategory.' > 0 ) {
                        url = "exercise_question_reminder.php?'.$params.'&num='.($current_question - 1).'&category_id='.$isLastQuestionInCategory.'";
                    }

                    if (url_extra) {
                        url = url_extra;
                    }

                    $("#save_for_now_"+question_id).html(\''.
                        Display::return_icon('save.png', get_lang('Saved'), [], ICON_SIZE_SMALL).'\' + return_value.savedAnswerMessage);

                    // Show popup
                    if ("check_answers" === url_extra) {
                        var button = $(\'button[name="check_answers"]\');
                        var questionId = parseInt(button.data(\'question\')) || 0;
                        var urlExtra = button.data(\'url\') || null;
                        var checkUrl = "'.$checkAnswersUrl.'";

                        $("#global-modal").attr("data-keyboard", "false");
                        $("#global-modal").attr("data-backdrop", "static");
                        $("#global-modal").find(".close").hide();

                        $("#global-modal .modal-body").load(checkUrl, function() {
                            $("#global-modal .modal-body").append("<div class=\"btn-group\"></div>");
                            var continueTest = $("<a>",{
                                text: "'.addslashes(get_lang('ContinueTest')).'",
                                title: "'.addslashes(get_lang('ContinueTest')).'",
                                href: "javascript:void(0);",
                                click: function(){
                                    $(this).attr("disabled", "disabled");
                                    $("#global-modal").modal("hide");
                                    $("#global-modal .modal-body").html("");
                                }
                            }).addClass("btn btn-default").appendTo("#global-modal .modal-body .btn-group");

                             $("<a>",{
                                text: "'.addslashes(get_lang('EndTest')).'",
                                title: "'.addslashes(get_lang('EndTest')).'",
                                href: "javascript:void(0);",
                                click: function() {
                                    $(this).attr("disabled", "disabled");
                                    continueTest.attr("disabled", "disabled");
                                    save_now(questionId, urlExtra);
                                    $("#global-modal .modal-body").html("<span style=\"text-align:center\">'.addslashes($loading).addslashes(get_lang('Loading')).'</span>");
                                }
                            }).addClass("btn btn-primary").appendTo("#global-modal .modal-body .btn-group");
                        });
                        $("#global-modal").modal("show");

                        return true;
                    }
                    // window.quizTimeEnding will be reset in exercise.class.php
                    if (window.quizTimeEnding) {
                        redirectExerciseToResult();
                    } else {
                        window.location = url;
                    }
                }
            },
            error: function() {
                $("#save_for_now_"+question_id).html(\''.
                    Display::return_icon('error.png', get_lang('Error'), [], ICON_SIZE_SMALL).'\');
            }
        });
    }

    function save_now_all(validate) {
        // 1. Input choice.
        var my_choice = $(\'*[name*="choice"]\').serialize();

        // 2. Reminder.
        var remind_list = $(\'*[name*="remind_list"]\').serialize();

        // 3. Hotspots.
        var hotspot = $(\'*[name*="hotspot"]\').serialize();

        // Question list.
        var question_list = ['.implode(',', $questionList).'];
        var free_answers = {};
        $.each(question_list, function(index, my_question_id) {
            // Checking Ckeditor and upload answer
            if (my_question_id) {
                if (CKEDITOR.instances["choice["+my_question_id+"]"]) {
                    var ckContent = CKEDITOR.instances["choice["+my_question_id+"]"].getData();
                    free_answers["free_choice["+my_question_id+"]"] = ckContent;
                }
                if ($(\'*[name*="uploadChoice[\'+my_question_id+\']"]\').length) {
                    var uploadChoice = $(\'*[name*="uploadChoice[\'+my_question_id+\']"]\').serializeArray();
                    $.each(uploadChoice, function(i, obj) {
                        free_answers["uploadChoice["+my_question_id+"]["+i+"]"] = uploadChoice[i].value;
                    });
                }
            }
        });

        free_answers = $.param(free_answers);
        $("#save_all_response").html(\''.$loading.'\');

        var requestData = "'.$params.'&type=all";
        requestData += "&" + my_choice;
        requestData += hotspot ? ("&" + hotspot) : "";
        requestData += free_answers ? ("&" + free_answers) : "";
        requestData += remind_list ? ("&" + remind_list) : "";

        $.ajax({
            type:"post",
            url: "'.api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&a=save_exercise_by_now",
            data: requestData,
            success: function(return_value) {
                if (return_value.ok) {
                    if (validate == "validate") {
                        $("#save_all_response").html(return_value.savedAnswerMessage);
                        window.location = "'.$script_php.'?'.$params.'";
                    } else {
                        $("#save_all_response").html(\''.Display::return_icon('accept.png').'\');
                    }
                } else {
                    $("#save_all_response").html(\''.Display::return_icon('wrong.gif').'\');
                }
            }
        });
        return false;
    }

    function validate_all() {
        save_now_all("validate");
    }

    window.quizTimeEnding = false;
</script>';

echo '<form id="exercise_form" method="post" action="'.
        api_get_self().'?'.api_get_cidreq().'&reminder='.$reminder.
        '&autocomplete=off&exerciseId='.$exerciseId.'" name="frm_exercise">
     <input type="hidden" name="formSent" value="1" />
     <input type="hidden" name="exerciseId" value="'.$exerciseId.'" />
     <input type="hidden" name="num" value="'.$current_question.'" id="num_current_id" />
     <input type="hidden" name="num_answer" value="'.$currentAnswer.'" id="num_current_answer_id" />
     <input type="hidden" name="exe_id" value="'.$exe_id.'" />
     <input type="hidden" name="origin" value="'.$origin.'" />
     <input type="hidden" name="reminder" value="'.$reminder.'" />
     <input type="hidden" name="learnpath_id" value="'.$learnpath_id.'" />
     <input type="hidden" name="learnpath_item_id" value="'.$learnpath_item_id.'" />
     <input type="hidden" name="learnpath_item_view_id" value="'.$learnpath_item_view_id.'" />';
if (!empty($ltiLaunchId)) {
    echo '<input type="hidden" name="lti_launch_id" value="'.$ltiLaunchId.'" />';
}

// Show list of questions
$i = 1;
$attempt_list = [];
if (isset($exe_id)) {
    $attempt_list = Event::getAllExerciseEventByExeId($exe_id);
}

$remind_list = [];
if (isset($exercise_stat_info['questions_to_check']) &&
    !empty($exercise_stat_info['questions_to_check'])
) {
    $remind_list = explode(',', $exercise_stat_info['questions_to_check']);
}

foreach ($questionList as $questionId) {
    // for sequential exercises
    if (ONE_PER_PAGE == $objExercise->type) {
        // if it is not the right question, goes to the next loop iteration
        if ($current_question != $i) {
            $i++;
            continue;
        } else {
            if (!in_array($objExercise->getFeedbackType(), [EXERCISE_FEEDBACK_TYPE_DIRECT, EXERCISE_FEEDBACK_TYPE_POPUP])) {
                // if the user has already answered this question
                if (isset($exerciseResult[$questionId])) {
                    // construction of the Question object
                    $objQuestionTmp = Question::read($questionId);
                    $questionName = $objQuestionTmp->selectTitle();
                    // destruction of the Question object
                    unset($objQuestionTmp);
                    echo Display::return_message(get_lang('AlreadyAnswered'));
                    $i++;
                    break;
                }
            }

            if (1 === $exerciseInSession->getPreventBackwards()) {
                if (isset($attempt_list[$questionId])) {
                    echo Display::return_message(get_lang('AlreadyAnswered'));
                    $i++;
                    break;
                }
            }
        }
    }

    $user_choice = null;
    if (isset($attempt_list[$questionId])) {
        $user_choice = $attempt_list[$questionId];
    } elseif ($objExercise->getSaveCorrectAnswers()) {
        $correctAnswers = [];
        switch ($objExercise->getSaveCorrectAnswers()) {
            case 1:
                $correctAnswers = $objExercise->getCorrectAnswersInAllAttempts(
                    $learnpath_id,
                    $learnpath_item_id
                );
                break;
            case 2:
                $correctAnswers = $objExercise->getAnswersInAllAttempts(
                    $learnpath_id,
                    $learnpath_item_id,
                    false
                );
                break;
        }

        if (isset($correctAnswers[$questionId])) {
            $user_choice = $correctAnswers[$questionId];
        }
    }

    $remind_highlight = '';
    // Hides questions when reviewing a ALL_ON_ONE_PAGE exercise see #4542 no_remind_highlight class hide with jquery
    if ($objExercise->type == ALL_ON_ONE_PAGE &&
        isset($_GET['reminder']) && $_GET['reminder'] == 2
    ) {
        $remind_highlight = 'no_remind_highlight';
    }

    $exerciseActions = '';
    $is_remind_on = false;
    $attributes = ['id' => 'remind_list['.$questionId.']'];
    if (in_array($questionId, $remind_list)) {
        $is_remind_on = true;
        $attributes['checked'] = 1;
        $remind_question = true;
        $remind_highlight = ' remind_highlight ';
    }

    // Showing the exercise description
    if (!empty($objExercise->description)) {
        if ($objExercise->type == ONE_PER_PAGE || ($objExercise->type != ONE_PER_PAGE && $i == 1)) {
            echo Display::panelCollapse(
                '<span>'.get_lang('ExerciseDescriptionLabel').'</span>',
                Security::remove_XSS($objExercise->description, COURSEMANAGERLOWSECURITY),
                'exercise-description',
                [],
                'description',
                'exercise-collapse',
                false,
                true
            );
        }
    }

    echo '<div id="question_div_'.$questionId.'" class="main-question '.$remind_highlight.'" >';
    $showQuestion = true;
    $exerciseResultFromSession = Session::read('exerciseResult');
    if ($objExercise->getFeedbackType() === EXERCISE_FEEDBACK_TYPE_POPUP &&
        isset($exerciseResultFromSession[$questionId])
    ) {
        $showQuestion = false;
    }

    // Shows the question and its answers
    if ($showQuestion) {
        ExerciseLib::showQuestion(
            $objExercise,
            $questionId,
            false,
            $origin,
            $i,
            $objExercise->getHideQuestionTitle() ? false : true,
            false,
            $user_choice,
            false,
            null,
            false,
            true
        );
    } else {
        echo Display::return_message(get_lang('AlreadyAnswered'));
    }

    // Button save and continue
    switch ($objExercise->type) {
        case ONE_PER_PAGE:
            $exerciseActions .= $objExercise->show_button(
                $questionId,
                $current_question,
                [],
                [],
                $myRemindList,
                $showPreviousButton,
                $learnpath_id,
                $learnpath_item_id,
                $learnpath_item_view_id
            );
            break;
        case ALL_ON_ONE_PAGE:
            if (api_is_allowed_to_session_edit()) {
                $button = [
                    Display::button(
                        'save_now',
                        get_lang('SaveForNow'),
                        [
                            'type' => 'button',
                            'class' => 'btn btn-info',
                            'data-question' => $questionId,
                        ]
                    ),
                    '<span id="save_for_now_'.$questionId.'"></span>&nbsp;',
                ];
                $exerciseActions .= Display::div(
                    implode(PHP_EOL, $button),
                    ['class' => 'exercise_save_now_button']
                );
            }
            break;
    }

    // Checkbox review answers
    if ($objExercise->review_answers) {
        $remind_question_div = Display::tag(
            'label',
            Display::input(
                'checkbox',
                'remind_list['.$questionId.']',
                '',
                $attributes
            ).get_lang('ReviewQuestionLater'),
            [
                'class' => 'checkbox',
                'for' => 'remind_list['.$questionId.']',
            ]
        );
        $exerciseActions .= Display::div(
            $remind_question_div,
            ['class' => 'exercise_save_now_button']
        );
    }
    echo Display::div($exerciseActions, ['class' => 'form-actions']);
    echo '</div>';

    $i++;
    // for sequential exercises
    if ($objExercise->type == ONE_PER_PAGE) {
        // quits the loop
        break;
    }
}

if ($objExercise->type == ALL_ON_ONE_PAGE) {
    $exerciseActions = $objExercise->show_button(
        $questionId,
        $current_question,
        [],
        '',
        [],
        true,
        $learnpath_id,
        $learnpath_item_id,
        $learnpath_item_view_id
    );
    echo Display::div($exerciseActions, ['class' => 'exercise_actions']);
    echo '<br>';
}
echo '</form>';

if (!in_array($origin, ['learnpath', 'embeddable', 'iframe'])) {
    // So we are not in learnpath tool
    echo '</div>'; //End glossary div
}
Display::display_footer();
