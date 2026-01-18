<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Framework\Container;
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
$glossaryExtraTools = api_get_setting('glossary.show_glossary_in_extra_tools');
$allowTimePerQuestion = ('true' === api_get_setting('exercise.allow_time_per_question'));
if ($allowTimePerQuestion) {
    $htmlHeadXtra[] = api_get_asset('easytimer/easytimer.min.js');
}

$showPreviousButton = true;
$showGlossary = false;

switch ($glossaryExtraTools) {
    case 'exercise':
        // Only show in standalone exercises, not when launched from LP
        $showGlossary = ('learnpath' !== $origin);
        break;

    case 'lp':
        // Only show when the exercise is launched from a learning path
        $showGlossary = ('learnpath' === $origin);
        break;

    case 'exercise_and_lp':
    case 'true':
        // Show in both standalone exercises and LP context
        $showGlossary = true;
        break;

    default:
        $showGlossary = false;
        break;
}
if ($showGlossary) {
    $htmlHeadXtra[] = api_get_glossary_auto_snippet(
        api_get_course_int_id(),
        api_get_session_id(),
        null
    );
}
$htmlHeadXtra[] = api_get_build_js('legacy_exercise.js');
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/css/hotspot.css">';
if ('true' === api_get_setting('exercise.quiz_prevent_copy_paste')) {
    $htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'jquery.nocopypaste.js"></script>';
}

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'rtc/RecordRTC.js"></script>';
$htmlHeadXtra[] = api_get_js('record_audio/record_audio.js');

$zoomOptions = api_get_setting('exercise.quiz_image_zoom', true);
if (isset($zoomOptions['options']) && !in_array($origin, ['embeddable', 'mobileapp'])) {
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

// If we are not explicitly in LP context, ignore any leaked LP params and clear persisted context.
// This avoids attaching standalone exercises to the last learning path attempt.
if ('learnpath' !== $origin) {
    Session::erase('learnpath_id');
    Session::erase('lp_id');
    Session::erase('learnpath_item_id');
    Session::erase('lp_item_id');
    Session::erase('learnpath_item_view_id');
    Session::erase('lp_item_view_id');

    $learnpath_id = 0;
    $learnpath_item_id = 0;
    $learnpath_item_view_id = 0;
}

if ('learnpath' === $origin) {
    // Normalize common aliases used by LP tool
    $learnpath_id = $learnpath_id > 0 ? $learnpath_id : (int) ($_REQUEST['lp_id'] ?? 0);

    // Some links use item_id instead of learnpath_item_id
    $learnpath_item_id = $learnpath_item_id > 0
        ? $learnpath_item_id
        : (int) ($_REQUEST['item_id'] ?? ($_REQUEST['lp_item_id'] ?? 0));

    $learnpath_item_view_id = $learnpath_item_view_id > 0
        ? $learnpath_item_view_id
        : (int) ($_REQUEST['lp_item_view_id'] ?? 0);

    // Restore from session when modal/navigation loses query params
    if ($learnpath_id <= 0) {
        $learnpath_id = (int) (Session::read('learnpath_id') ?? Session::read('lp_id') ?? 0);
    }
    if ($learnpath_item_id <= 0) {
        $learnpath_item_id = (int) (Session::read('learnpath_item_id') ?? Session::read('lp_item_id') ?? 0);
    }
    if ($learnpath_item_view_id <= 0) {
        $learnpath_item_view_id = (int) (Session::read('learnpath_item_view_id') ?? Session::read('lp_item_view_id') ?? 0);
    }

    // Persist context for future requests (modal, JS navigation, session resume)
    if ($learnpath_id > 0) {
        Session::write('learnpath_id', $learnpath_id);
        Session::write('lp_id', $learnpath_id);
    }
    if ($learnpath_item_id > 0) {
        Session::write('learnpath_item_id', $learnpath_item_id);
        Session::write('lp_item_id', $learnpath_item_id);
    }
    if ($learnpath_item_view_id > 0) {
        Session::write('learnpath_item_view_id', $learnpath_item_view_id);
        Session::write('lp_item_view_id', $learnpath_item_view_id);
    }
}

$reminder = isset($_REQUEST['reminder']) ? (int) $_REQUEST['reminder'] : 0;
$remind_question_id = isset($_REQUEST['remind_question_id']) ? (int) $_REQUEST['remind_question_id'] : 0;
$exerciseId = isset($_REQUEST['exerciseId']) ? (int) $_REQUEST['exerciseId'] : 0;
$formSent = isset($_REQUEST['formSent']) ? $_REQUEST['formSent'] : null;
$exerciseResult = isset($_REQUEST['exerciseResult']) ? $_REQUEST['exerciseResult'] : null;
$exerciseResultCoordinates = isset($_REQUEST['exerciseResultCoordinates']) ? $_REQUEST['exerciseResultCoordinates'] : null;
$choice = isset($_REQUEST['choice']) ? $_REQUEST['choice'] : null;
$choice = empty($choice) ? isset($_REQUEST['choice2']) ? $_REQUEST['choice2'] : null : null;
$questionCategoryId = isset($_REQUEST['category_id']) ? (int) $_REQUEST['category_id'] : 0;
$current_question = $currentQuestionFromUrl = isset($_REQUEST['num']) ? (int) $_REQUEST['num'] : null;
$currentAnswer = isset($_REQUEST['num_answer']) ? (int) $_REQUEST['num_answer'] : null;
$page = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
$currentBreakId = isset($_REQUEST['currentBreakId']) ? (int) $_REQUEST['currentBreakId'] : null;

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
    isset($_GET['preview']) && 1 == $_GET['preview']
) {
    Session::erase('objExercise');
}

// 1. Loading the $objExercise variable
/** @var \Exercise $exerciseInSession */
$exerciseInSession = Session::read('objExercise');
if (empty($exerciseInSession) || (!empty($exerciseInSession) && ($exerciseInSession->id != $_GET['exerciseId']))) {
    // Construction of Exercise
    $objExercise = new Exercise($courseId);
    Session::write('firstTime', true);
    if ($debug) {
        error_log('1. Setting the $objExercise variable');
    }
    Session::erase('questionList');

    // if the specified exercise doesn't exist or is disabled
    if (!$objExercise->read($exerciseId) ||
        (!$objExercise->selectStatus() && !$is_allowedToEdit && !in_array($origin, ['learnpath', 'embeddable']))
    ) {
        unset($objExercise);
        $error = get_lang('Test not found or not visible');
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

if (Container::getPluginHelper()->isPluginEnabled('Positioning')) {
    $plugin = Positioning::create();
    if ($plugin->blockFinalExercise(api_get_user_id(), $objExercise->iId, api_get_course_int_id(), $sessionId)) {
        api_not_allowed(true);
    }
}

// if the user has submitted the form.
$exercise_title = $objExercise->selectTitle();
$exercise_sound = $objExercise->getSound();

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
    $objExercise->id,
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
        sprintf(get_lang('You cannot take test <b>%s</b> because you have already reached the maximum of %s attempts.'), $exercise_title, $objExercise->selectAttempts()),
        'warning',
        false
    );

    $attempt_html = '';
    $attempt_count = Event::get_attempt_count(
        $user_id,
        $exerciseId,
        (int) $learnpath_id,
        (int) $learnpath_item_id,
        (int) $learnpath_item_view_id
    );

    if ($attempt_count >= $objExercise->selectAttempts()) {
        $show_clock = false;
        if (!api_is_allowed_to_edit(null, true)) {
            if (0 == $objExercise->results_disabled && !in_array($origin, ['learnpath', 'embeddable'])) {
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
                                sprintf(get_lang('Sorry, you have reached the maximum number of questions (%s) for the day. Please try again tomorrow.'), $maxQuestionsAnswered),
                                'warning',
                                false
                            )
                        );

                        if (in_array($origin, ['learnpath', 'embeddable'])) {
                            Display::display_reduced_header();
                            Display::display_reduced_footer();
                        } else {
                            Display::display_header(get_lang('Tests'));
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
                        $last_attempt_info['score'],
                        $last_attempt_info['max_score']
                    );
                    $attempt_html .= Display::div(
                        get_lang('Score for the test').' '.$score,
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

        if (in_array($origin, ['learnpath', 'embeddable'])) {
            Display::display_reduced_header();
        } else {
            Display::display_header(get_lang('Tests'));
        }

        echo $attempt_html;

        if (!in_array($origin, ['learnpath', 'embeddable'])) {
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

if (ONE_PER_PAGE == $objExercise->type) {
    $filtered = [];
    foreach ($questionListUncompressed as $qid) {
        $q = Question::read($qid);
        if (
            $q
            && $q->type !== PAGE_BREAK
            && $q->type !== MEDIA_QUESTION
        ) {
            $filtered[] = $qid;
        }
    }
    $questionListUncompressed = $filtered;
    Session::write('question_list_uncompressed', $questionListUncompressed);

    if (Session::read('questionList') !== null) {
        Session::write('questionList', $filtered);
    }
}

$clock_expired_time = null;
if (empty($exercise_stat_info)) {
    $disable = ('true' === api_get_setting('exercise.exercises_disable_new_attempts'));
    if ($disable) {
        api_not_allowed(true);
    }
    $total_weight = 0;
    $questionList = $objExercise->get_validated_question_list();
    $questionList = ExerciseLib::normalizeAttemptQuestionList($objExercise, $questionList);
    $total_weight = 0;
    foreach ($questionList as $question_id) {
        $objQuestionTmp = Question::read((int) $question_id);
        if ($objQuestionTmp && (int) $objQuestionTmp->type !== MEDIA_QUESTION && (int) $objQuestionTmp->type !== PAGE_BREAK) {
            $total_weight += (float) $objQuestionTmp->weighting;
        }
    }

    if ($time_control) {
        $expected_time = $current_timestamp + $total_seconds;
        if ($debug) {
            error_log('5.1. $current_timestamp '.$current_timestamp);
            error_log('5.2. $expected_time '.$expected_time);
        }

        $clock_expired_time = api_get_utc_datetime($expected_time, false, true);
        if ($debug) {
            error_log('5.3. $expected_time '.$clock_expired_time->format('Y-m-d H:i:s'));
        }

        //Sessions  that contain the expired time
        $_SESSION['expired_time'][$current_expired_time_key] = $clock_expired_time->format('Y-m-d H:i:s');
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
        if (!empty($resolvedQuestions) && !empty($exercise_stat_info['data_tracking'])) {
            $resolvedQuestionsQuestionIds = array_keys($resolvedQuestions);
            $lastAnsweredIndex = -1;
            $attemptQuestionList = explode(',', $exercise_stat_info['data_tracking']);
            foreach ($attemptQuestionList as $index => $question) {
                if (in_array($question, $resolvedQuestionsQuestionIds)) {
                    $lastAnsweredIndex = max($lastAnsweredIndex, $index);
                }
            }
            $current_question = max(1, $lastAnsweredIndex + 2);
        }
    }
}
Session::write('exe_id', $exe_id);
$storedExeId = (int) (Session::read('questionListExeId') ?? 0);
if ($storedExeId !== (int) $exe_id) {
    Session::erase('questionList');
    Session::erase('categoryList');
    Session::write('questionListExeId', (int) $exe_id);
}

// Always restore persisted question order from DB (data_tracking) on resume
// Rationale: session may be lost between visits; data_tracking is the single source of truth
// for the question order selected at the very first entry of the attempt.
if (!empty($exercise_stat_info) && !empty($exercise_stat_info['data_tracking'])) {
    $restoredQuestionList = array_map('intval', explode(',', $exercise_stat_info['data_tracking']));

    // Fix random + media questions on resume as well.
    $restoredQuestionList = ExerciseLib::normalizeAttemptQuestionList($objExercise, $restoredQuestionList);

    // Persist correction so it doesn't flip-flop between requests.
    ExerciseLib::updateAttemptDataTrackingIfNeeded((int) $exe_id, $restoredQuestionList, $exercise_stat_info);

    Session::write('questionList', $restoredQuestionList);
}

$checkAnswersUrl = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?a=check_answers&exe_id='.$exe_id.'&'.api_get_cidreq();
$saveDurationUrl = api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?a=update_duration&exe_id='.$exe_id.'&'.api_get_cidreq();
$questionListInSession = Session::read('questionList');
$selectionType = $objExercise->getQuestionSelectionType();

$allowBlockCategory = false;
if ('true' === api_get_setting('exercise.block_category_questions')) {
    $extraFieldValue = new ExtraFieldValue('exercise');
    $extraFieldData = $extraFieldValue->get_values_by_handler_and_field_variable($objExercise->iId, 'block_category');
    if ($extraFieldData && isset($extraFieldData['value']) && 1 === (int) $extraFieldData['value']) {
        $allowBlockCategory = true;
    }
}

if (!isset($questionListInSession)) {
    // === FIX: Prefer persisted selection (data_tracking) to guarantee stable order across sessions ===
    if (!empty($exercise_stat_info) && !empty($exercise_stat_info['data_tracking'])) {
        $questionList = array_map('intval', explode(',', $exercise_stat_info['data_tracking']));
    } else {
        // Brand-new attempt fallback: build from current exercise definition
        $questionList = $objExercise->getQuestionList();
    }

    // Keep order; filter out non-renderable media questions
    $questionList = array_values(array_filter($questionList, function (int $qid) {
        $q = Question::read($qid);
        return $q && $q->type !== MEDIA_QUESTION;
    }));

    // Prepare category grouping when feature is enabled, using the persisted list order.
    if ($allowBlockCategory) {
        $categoryList = [];
        foreach ($questionList as $question) {
            $categoryId = TestCategory::getCategoryForQuestion($question);
            $categoryList[$categoryId][] = $question;
        }
        Session::write('categoryList', $categoryList);
    }

    Session::write('questionList', $questionList);
} else {
    if (isset($objExercise) && isset($exerciseInSession)) {
        $questionList = Session::read('questionList');
        // Ensure categoryList exists if blocking by category is enabled
        if ($allowBlockCategory && Session::read('categoryList') === null) {
            $categoryList = [];
            foreach ($questionList as $question) {
                $categoryId = TestCategory::getCategoryForQuestion($question);
                $categoryList[$categoryId][] = $question;
            }
            Session::write('categoryList', $categoryList);
        }
    }
}
// Array to check in order to block the chat
ExerciseLib::create_chat_exercise_session($exe_id);

if (!empty($exercise_stat_info['questions_to_check'])) {
    $myRemindList = $exercise_stat_info['questions_to_check'];
    $myRemindList = explode(',', $myRemindList);
    $myRemindList = array_filter($myRemindList);
}

$params = "exe_id=$exe_id&exerciseId=$exerciseId&learnpath_id=$learnpath_id"
    . "&learnpath_item_id=$learnpath_item_id&learnpath_item_view_id=$learnpath_item_view_id"
    . "&page=" . ($page ?? 1)
    . "&" . api_get_cidreq();

// Base query strings used by JS navigation (keep LP context on every click).
$submitBaseQuery = "exe_id=$exe_id&exerciseId=$exerciseId"
    . "&learnpath_id=$learnpath_id"
    . "&learnpath_item_id=$learnpath_item_id"
    . "&learnpath_item_view_id=$learnpath_item_view_id"
    . "&reminder=$reminder"
    . "&" . api_get_cidreq();

$resultBaseQuery = "exe_id=$exe_id"
    . "&learnpath_id=$learnpath_id"
    . "&learnpath_item_id=$learnpath_item_id"
    . "&learnpath_item_view_id=$learnpath_item_view_id"
    . "&" . api_get_cidreq();


if (2 === $reminder && empty($myRemindList)) {
    if ($debug) {
        error_log('6.2 calling the exercise_reminder.php ');
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
        if (isset($_SESSION['expired_time'])) {
            if ($_SESSION['expired_time'][$current_expired_time_key] instanceof DateTimeInterface) {
                error_log(
                    '7.3. $_SESSION[expired_time][$current_expired_time_key] '.
                    $_SESSION['expired_time'][$current_expired_time_key]->format('Y-m-d H:i:s')
                );
            } else {
                error_log(
                    '7.3. $_SESSION[expired_time][$current_expired_time_key] '.
                    $_SESSION['expired_time'][$current_expired_time_key]
                );
            }
        }
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
        if ($_SESSION['expired_time'][$current_expired_time_key] instanceof DateTimeInterface) {
            $clock_expired_time = $_SESSION['expired_time'][$current_expired_time_key]->format('Y-m-d H:i:s');
        } else {
            $clock_expired_time = $_SESSION['expired_time'][$current_expired_time_key];
        }
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
    if (isset($exe_id) && $time_left <= 0) {
        Database::query("
        UPDATE " . Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES) . "
        SET status = 'completed',
            exe_date = FROM_UNIXTIME(LEAST(UNIX_TIMESTAMP('" . Database::escape_string($clock_expired_time) . "'), UNIX_TIMESTAMP()))
        WHERE exe_id = " . (int) $exe_id . "
          AND status = 'incomplete'
    ");

        $resultUrl = 'exercise_result.php?' . api_get_cidreq()
            . "&exe_id=$exe_id"
            . "&learnpath_id=$learnpath_id"
            . "&learnpath_item_id=$learnpath_item_id"
            . "&learnpath_item_view_id=$learnpath_item_view_id";
        header('Location: ' . $resultUrl);
        exit;
    }
}

//in LP's is enabled the "remember question" feature?
// Do not rely on random flags; always prefer persisted data_tracking when available
if (!isset($_SESSION['questionList'])) {
    if (!empty($exercise_stat_info['data_tracking'])) {
        $questionList = array_map('intval', explode(',', $exercise_stat_info['data_tracking']));
    } else {
        // Fallback for a brand-new attempt
        $questionList = $objExercise->get_validated_question_list();
    }
    // Keep order; filter out non-renderable media questions
    $questionList = array_values(array_filter($questionList, function (int $qid) {
        $q = Question::read($qid);
        return $q && $q->type !== MEDIA_QUESTION;
    }));
    Session::write('questionList', $questionList);
} else {
    if (isset($objExercise) && isset($_SESSION['objExercise'])) {
        $questionList = Session::read('questionList');
    }
}

// Remove any leading page breaks
while (count($questionList) > 0) {
    // reset() moves the internal pointer to the first element…
    $firstId  = reset($questionList);
    // …key() returns its key, so we can unset by that key
    $firstKey = key($questionList);
    $q        = Question::read((int) $firstId);
    if ($q && $q->type === PAGE_BREAK) {
        unset($questionList[$firstKey]);
    } else {
        // stop once the first element is not a page break
        break;
    }
}

// Remove any trailing page breaks
while (count($questionList) > 0) {
    // end() moves the internal pointer to the last element
    $lastId  = end($questionList);
    $lastKey = key($questionList);
    $q       = Question::read((int) $lastId);
    if ($q && $q->type === PAGE_BREAK) {
        unset($questionList[$lastKey]);
    } else {
        // stop once the last element is not a page break
        break;
    }
}

$hasMediaWithChildren = false;
$mediaQids = array_filter($objExercise->getQuestionOrderedList(), function(int $qid) {
    $q = Question::read($qid);
    return $q && $q->type === MEDIA_QUESTION;
});

if (!empty($mediaQids)) {
    foreach ($questionListUncompressed as $qid) {
        $q = Question::read($qid);
        if ($q && in_array($q->parent_id, $mediaQids, true)) {
            $hasMediaWithChildren = true;
            break;
        }
    }
}

$forceGrouped = (ONE_PER_PAGE === $objExercise->type && $hasMediaWithChildren);
if ($forceGrouped) {
    $objExercise->type = ALL_ON_ONE_PAGE;
}

if (ALL_ON_ONE_PAGE === $objExercise->type || $forceGrouped) {
    $flat = array_filter($questionList, function(int $qid) {
        $q = Question::read($qid);
        return $q && $q->type !== MEDIA_QUESTION;
    });

    if ($hasMediaWithChildren) {
        $pages = [];
        $groupIndexByParent = [];
        foreach ($flat as $qid) {
            $q = Question::read($qid);
            if ($q && $q->parent_id > 0) {
                $pid = (int) $q->parent_id;
                if (!array_key_exists($pid, $groupIndexByParent)) {
                    $groupIndexByParent[$pid] = count($pages);
                    $pages[] = ['parent' => $pid, 'questions' => []];
                }
                $pages[$groupIndexByParent[$pid]]['questions'][] = $qid;
            } else {
                $pages[] = ['parent' => null, 'questions' => [$qid]];
            }
        }

        $totalPages   = count($pages);
        $page         = min(max(1, $page), $totalPages);
        $questionList = $pages[$page - 1]['questions'];
        $currentBreakId = null;
    } else {
        $pages    = [[]];
        $breakIds = [null];
        foreach ($flat as $qid) {
            $q = Question::read($qid);
            if ($q->type === PAGE_BREAK) {
                $pages[]    = [];
                $breakIds[] = $qid;
            } else {
                $pages[count($pages) - 1][] = $qid;
            }
        }
        $totalPages    = count($pages);
        $page          = min(max(1, $page), $totalPages);
        $questionList  = $pages[$page - 1];
        $currentBreakId = ($page > 1 ? $breakIds[$page - 1] : null);
    }

    $questionNumberOffset = 0;
    if (isset($pages) && is_array($pages) && isset($page)) {
        for ($p = 0; $p < max(0, $page - 1); $p++) {
            $chunk = $pages[$p];
            $questionNumberOffset += (is_array($chunk) && array_key_exists('questions', $chunk))
                ? count($chunk['questions'])
                : (is_array($chunk) ? count($chunk) : 0);
        }
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
    if ($debug) {
        error_log('9. $formSent was set');
    }

    if (!is_array($exerciseResult)) {
        $exerciseResult = [];
        $exerciseResultCoordinates = [];
    }

    //Only for hotspot
    if (!isset($choice) && isset($_REQUEST['hidden_hotspot_id'])) {
        $hotspot_id = (int) $_REQUEST['hidden_hotspot_id'];
        $choice = [$hotspot_id => ''];
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
    if (ALL_ON_ONE_PAGE == $objExercise->type || $current_question >= $question_count) {
        if (api_is_allowed_to_session_edit()) {
            // goes to the script that will show the result of the exercise
            if (ALL_ON_ONE_PAGE == $objExercise->type) {
                if ($debug) {
                    error_log('10. Exercise ALL_ON_ONE_PAGE -> Redirecting to exercise_result.php');
                }
                //We check if the user attempts before sending to the exercise_result.php
                if ($objExercise->selectAttempts() > 0) {
                    $attempt_count = Event::get_attempt_count(
                        api_get_user_id(),
                        $exerciseId,
                        (int) $learnpath_id,
                        (int) $learnpath_item_id,
                        (int) $learnpath_item_view_id
                    );
                    if ($attempt_count >= $objExercise->selectAttempts()) {
                        echo Display::return_message(
                            sprintf(get_lang('You cannot take test <b>%s</b> because you have already reached the maximum of %s attempts.'), $exercise_title, $objExercise->selectAttempts()),
                            'warning',
                            false
                        );
                        if (!in_array($origin, ['learnpath', 'embeddable'])) {
                            //so we are not in learnpath tool
                            echo '</div>'; //End glossary div
                            Display::display_footer();
                        } else {
                            echo '</body></html>';
                        }
                    }
                }

                // Final redirect: if "Review my answers" is enabled, go to reminder list first.
                $endUrl = $objExercise->review_answers
                    ? 'exercise_reminder.php?'.$params
                    : 'exercise_result.php?'.api_get_cidreq()
                    ."&exe_id=$exe_id"
                    ."&learnpath_id=$learnpath_id"
                    ."&learnpath_item_id=$learnpath_item_id"
                    ."&learnpath_item_view_id=$learnpath_item_view_id";

                header('Location: '.$endUrl);
                exit;
            } else {
                if ($debug) {
                    error_log('10. Redirecting to exercise_result.php');
                }
                header('Location: exercise_result.php?'.api_get_cidreq()."&exe_id=$exe_id&learnpath_id=$learnpath_id&learnpath_item_id=$learnpath_item_id&learnpath_item_view_id=$learnpath_item_view_id");
                exit;
            }
        } else {
            if ($debug) {
                error_log('10. Redirecting to exercise_submit.php');
            }
            header('Location: exercise_submit.php?'.api_get_cidreq()."&exerciseId=$exerciseId");
            exit;
        }
    }
    if ($debug) {
        error_log('11. $formSent was set - end');
    }
}

$reqGetNum  = isset($_GET['num'])  ? (int) $_GET['num']  : null;
$reqPostNum = isset($_POST['num']) ? (int) $_POST['num'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reqPostNum !== null) {
    $current_question = max(1, $reqPostNum + 1);
} elseif ($reqGetNum !== null) {
    $current_question = max(1, $reqGetNum);
} else {
    $current_question = 1;
    $latestQuestionId = Event::getLatestQuestionIdFromAttempt($exe_id);
    if ($latestQuestionId) {
        // Resume position using the persisted questionList order
        // Using DB-backed order avoids inconsistencies when sessions expire.
        $idx = array_search((int) $latestQuestionId, $questionList ?? [], true);
        if ($idx === false) {
            // Fallback to legacy computation if not found in current in-memory list
            $pos = (int) $objExercise->getPositionInCompressedQuestionList($latestQuestionId);
            $current_question = max(1, $pos + 1);
        } else {
            $current_question = $idx + 1;
        }
    }
}

$question_count = !empty($questionList) ? count($questionList) : 0;
if ($_SERVER['REQUEST_METHOD'] === 'GET'
    && $reqGetNum !== null
    && $question_count > 0
    && $reqGetNum > $question_count
) {
    // If review answers is enabled, show reminder list instead of results.
    if ($objExercise->review_answers) {
        header('Location: exercise_reminder.php?'.$params);
    } else {
        header('Location: exercise_result.php?'.api_get_cidreq()
            ."&exe_id=$exe_id"
            ."&learnpath_id=$learnpath_id"
            ."&learnpath_item_id=$learnpath_item_id"
            ."&learnpath_item_view_id=$learnpath_item_view_id"
        );
    }
    exit;
}

if ($question_count > 0 && $current_question > $question_count) {
    $current_question = $question_count;
}

if (0 != $question_count) {
    if (ALL_ON_ONE_PAGE == $objExercise->type ||
        $current_question > $question_count
    ) {
        if (api_is_allowed_to_session_edit()) {
            // goes to the script that will show the result of the exercise
            if (ALL_ON_ONE_PAGE == $objExercise->type) {
                if ($debug) {
                    error_log('12. Exercise ALL_ON_ONE_PAGE -> Redirecting to exercise_result.php');
                }

                // We check if the user attempts before sending to the exercise_result.php
                if ($objExercise->selectAttempts() > 0) {
                    $attempt_count = Event::get_attempt_count(
                        api_get_user_id(),
                        $exerciseId,
                        (int) $learnpath_id,
                        (int) $learnpath_item_id,
                        (int) $learnpath_item_view_id
                    );
                    if ($attempt_count >= $objExercise->selectAttempts()) {
                        Display::return_message(
                            sprintf(get_lang('You cannot take test <b>%s</b> because you have already reached the maximum of %s attempts.'), $exercise_title, $objExercise->selectAttempts()),
                            'warning',
                            false
                        );
                        if (!in_array($origin, ['learnpath', 'embeddable'])) {
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
                        if (MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY == $question->type) {
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

                    header('Location: exercise_result.php?'
                        .api_get_cidreq()
                        ."&exe_id=$exe_id&learnpath_id=$learnpath_id&learnpath_item_id="
                        .$learnpath_item_id
                        ."&learnpath_item_view_id=$learnpath_item_view_id"
                    );
                    exit;
                }
            }
        }
    }
} else {
    $error = get_lang('There are no questions for this exercise');
    // if we are in the case where user select random by category, but didn't choose the number of random question
    if ($objExercise->getRandomByCategory() > 0 && $objExercise->random <= 0) {
        $error .= '<br/>'.get_lang('Please select some random question');
    }
}

if (api_is_in_gradebook()) {
    $interbreadcrumb[] = [
        'url' => Category::getUrl(),
        'name' => get_lang('Assessments'),
    ];
}

$interbreadcrumb[] = [
    'url' => 'exercise.php?'.api_get_cidreq(),
    'name' => get_lang('Tests'),
];
$interbreadcrumb[] = ['url' => '#', 'name' => $objExercise->selectTitle(true)];

// Time per question.
$questionTimeCondition = '';
$showQuestionClock = false;
if ($allowTimePerQuestion && ONE_PER_PAGE == $objExercise->type) {
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
if (!in_array($origin, ['learnpath', 'embeddable', 'mobileapp'])) {
    //so we are not in learnpath tool
    SessionManager::addFlashSessionReadOnly();

    Display::display_header(null, 'Exercises');
} else {
    Display::display_reduced_header();
    echo '<div style="height:10px">&nbsp;</div>';
}

if ('mobileapp' == $origin) {
    echo '<div class="actions">';
    echo '<a href="javascript:window.history.go(-1);">'.
        Display::getMdiIcon(ActionIcon::BACK, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Go back to the questions list')).'</a>';
    echo '</div>';
}

$show_quiz_edition = $objExercise->added_in_lp();

// I'm in a preview mode
if (api_is_course_admin() && !in_array($origin, ['learnpath', 'embeddable'])) {
    $actions = '';
    if (false == $show_quiz_edition) {
        $actions .= '<a href="exercise_admin.php?'.api_get_cidreq().'&modifyExercise=yes&exerciseId='.$objExercise->id.'">'.
            Display::getMdiIcon('cog', 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Edit test name and settings')).'</a>';
    } else {
        $actions .= '<a href="#">'.
            Display::getMdiIcon('cog', 'ch-tool-icon-disabled', null, ICON_SIZE_MEDIUM, get_lang('Edit test name and settings')).
            '</a>';
    }
    echo Display::toolbarAction('toolbar', [$actions]);
}

$is_visible_return = $objExercise->is_visible(
    $learnpath_id,
    $learnpath_item_id,
    $learnpath_item_view_id
);

if (false == $is_visible_return['value']) {
    echo $is_visible_return['message'];
    if (!in_array($origin, ['learnpath', 'embeddable'])) {
        Display :: display_footer();
    }
    exit;
}

if (!api_is_allowed_to_session_edit()) {
    if (!in_array($origin, ['learnpath', 'embeddable'])) {
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
    if ('POST' != $_SERVER['REQUEST_METHOD']) {
        if (!empty($objExercise->end_time)) {
            $exercise_timeover = $time_now - $exercise_end_time > 0 ? true : false;
        }
    }

    if (!$permission_to_start || $exercise_timeover) {
        if (!api_is_allowed_to_edit(null, true)) {
            $message_warning = $permission_to_start ? get_lang('Time limit reached') : get_lang('The test did not start yet');
            echo Display::return_message(
                sprintf(
                    $message_warning,
                    $exercise_title,
                    $objExercise->selectAttempts()
                ),
                'warning'
            );
            if (!in_array($origin, ['learnpath', 'embeddable'])) {
                Display::display_footer();
            }
            exit;
        } else {
            $message_warning = $permission_to_start ? get_lang('Time limit reached') : get_lang('The trainer did not allow the test to start yet');
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
// @todo use api_get_configuration_value()
/*global $_custom;
if (isset($_custom['exercises_hidden_when_no_start_date']) &&
    $_custom['exercises_hidden_when_no_start_date']
) {
    if (empty($objExercise->start_time)) {
        echo Display:: return_message(
            sprintf(
                get_lang('The test did not start yet'),
                $exercise_title,
                $objExercise->selectAttempts()
            ),
            'warning'
        );
        if (!in_array($origin, ['learnpath', 'embeddable'])) {
            Display::display_footer();
            exit;
        }
    }
}*/

if ($time_control) {
    echo $objExercise->returnTimeLeftDiv();
    echo '<div style="display:none" class="warning-message" id="expired-message-id">'.
        get_lang('The exercise time limit has expired').'</div>';
}

if ($showQuestionClock) {
    $icon = Display::getMdiIcon('clock-outline');
    echo '<div class="well" style="text-align: center">
            '.get_lang('Remaining time to finish question').'
            <div id="question_timer" class="label label-warning"></div>
          </div>';
}

if (!in_array($origin, ['learnpath', 'embeddable'])) {
    echo '<div id="highlight-plugin" class="glossary-content">';
}

if (2 == $reminder) {
    $data_tracking = $exercise_stat_info['data_tracking'];
    $data_tracking = explode(',', $data_tracking);
    $current_question = 1; //set by default the 1st question

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
    echo '<a
            href="../document/download.php?doc_url=%2Faudio%2F'.Security::remove_XSS($exercise_sound).'"
            target="_blank">';
    echo '<img
            src="../img/sound.gif" border="0"
            align="absmiddle" alt=', get_lang('Audio or video file').'" /></a>';
}
// Get number of hotspot questions for javascript validation
$number_of_hotspot_questions = 0;
$i = 0;
if (!empty($questionList)) {
    foreach ($questionList as $questionId) {
        $i++;
        $objQuestionTmp = Question::read($questionId);
        $selectType = $objQuestionTmp->selectType();
        // for sequential exercises

        if (ONE_PER_PAGE == $objExercise->type) {
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
$saveIcon = Display::getMdiIcon(
    ActionIcon::SAVE_FORM,
    'ch-tool-icon',
    null,
    ICON_SIZE_SMALL
);
$loading = Display::getMdiIcon('loading', 'animate-spin');

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

        var page = '.(int) $page.';
        var totalPages = '.(int) ($totalPages ?? 1).';
        var chSubmitBaseUrl = "'.api_get_self().'?'.$submitBaseQuery.'";
        var chResultUrl = "'.$script_php.'?'.$params.'";

        function navigateNext() {
            var url;
            if (page === totalPages) {
                url = chResultUrl;
            } else {
                url = chSubmitBaseUrl + "&page=" + (page + 1);
            }
            window.location = url;
        }

        function save_question_list(question_list) {
            if (!question_list.length) {
                return navigateNext();
            }
            var saves = $.map(question_list, function(qid) {
                var my_choice   = $(\'*[name*="choice[\'+qid+\']"]\').serialize();
                var remind_list = $(\'*[name*="remind_list"]\').serialize();
                var hotspot     = $(\'*[name*="hotspot[\'+qid+\']"]\').serialize();
                var dc          = $(\'*[name*="choiceDegreeCertainty[\'+qid+\']"]\').serialize();

                var editorContent = getContentFromEditor("choice"+qid);
                var free_choice = "";
                if (editorContent) {
                    var obj = {};
                    obj["choice["+qid+"]"] = editorContent;
                    my_choice = $.param(obj);
                    var fo = {};
                    fo["free_choice["+qid+"]"] = editorContent;
                    free_choice = $.param(fo);
                }

                var dataStr = "'.$params.'&type=simple&question_id="+qid
                              +"&"+my_choice
                              + (hotspot     ? "&"+hotspot     : "")
                              + (remind_list ? "&"+remind_list : "")
                              + (dc          ? "&"+dc          : "")
                              + (free_choice ? "&"+free_choice : "");

                return $.ajax({
                    type: "POST",
                    url: "'.api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&a=save_exercise_by_now",
                    data: dataStr
                });
            });
            $.when.apply($, saves).always(function(){
                navigateNext();
            });
        }

        function getCurrentQuestionNumber() {
          var n = parseInt($(\'#num_current_id\').val(), 10);
          return isNaN(n) || n < 1 ? 1 : n;
        }

        $(function() {
        '.$questionTimeCondition.'
            //This pre-load the save.png icon
            var saveImage = new Image();
         //   saveImage.src = "'.htmlspecialchars($saveIcon).'";

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
                if (window._quizNavLock) return;
                window._quizNavLock = true;

                var $this = $(this),
                questionId = parseInt($this.data(\'question\')) || 0,
                prevId = Math.max(1, getCurrentQuestionNumber() - 1);
                previous_question_and_save(prevId, questionId);
            });

            $(\'button[name="save_question_list"]\').on(\'touchstart click\', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $btn = $(this);

                $(\'button[name="save_question_list"]\').prop(\'disabled\', true);
                $btn.append(\' ' . addslashes($loading) . '\');

                var listStr = $btn.data(\'list\') || \'\';
                if (!listStr) {
                    return navigateNext();
                }
                var arr = listStr.toString().split(\',\');
                save_question_list(arr);
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
          var url = "exercise_submit.php?'.$params.'&num=" + question_num;
          window.location = url;
        }

        function previous_question_and_save(previous_question_id, question_id_to_save) {
            var url = chSubmitBaseUrl + "&num=" + previous_question_id;
            save_now(question_id_to_save, url);
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

            // Checking Editor
            if (question_id) {
                const content = getContentFromEditor("choice"+question_id);
                if (content) {
                    my_choice = {};
                    my_choice["choice["+question_id+"]"] = content;
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

            $("#save_for_now_"+question_id).html(\''.$loading.'\');
            $.ajax({
                type:"post",
                url: "'.api_get_path(WEB_AJAX_PATH).'exercise.ajax.php?'.api_get_cidreq().'&a=save_exercise_by_now",
                data: dataparam,
                success: function(return_value) {
                    if (return_value.ok) {
                        $("#save_for_now_"+question_id).html(\''.
    Display::getMdiIcon(ActionIcon::SAVE_FORM, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Saved.')).'\');
                    } else if (return_value.error) {
                        $("#save_for_now_"+question_id).html(\''.
    Display::getMdiIcon('alert-circle', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Error')).'\');
                    } else if (return_value.type == "one_per_page") {
                        var nextNum = getCurrentQuestionNumber() + 1;
                        var url = "";
                        if ('.$reminder.' == 1) {
                            url = "exercise_reminder.php?'.$params.'&num=" + nextNum;
                        } else if ('.$reminder.' == 2) {
                            url = "exercise_submit.php?'.$params.'&num=" + nextNum + "&remind_question_id='.$remind_question_id.'&reminder=2";
                        } else {
                            url = "exercise_submit.php?'.$params.'&num=" + nextNum + "&remind_question_id='.$remind_question_id.'";
                        }

                        // If last question in category send to exercise_question_reminder.php
                        if ('.$isLastQuestionInCategory.' > 0 ) {
                            url = "exercise_question_reminder.php?'.$params.'&num='.($current_question - 1).'&category_id='.$isLastQuestionInCategory.'";
                        }

                        if (url_extra) {
                            url = url_extra;
                        }

                        $("#save_for_now_"+question_id).html(\''.
    Display::getMdiIcon(ActionIcon::SAVE_FORM, 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Saved.')).'\' + return_value.savedAnswerMessage);

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
                                    text: "'.addslashes(get_lang('Proceed with the test')).'",
                                    title: "'.addslashes(get_lang('Proceed with the test')).'",
                                    href: "javascript:void(0);",
                                    click: function(){
                                        $(this).attr("disabled", "disabled");
                                        $("#global-modal").modal("hide");
                                        $("#global-modal .modal-body").html("");
                                    }
                                }).addClass("btn btn--plain").appendTo("#global-modal .modal-body .btn-group");

                                $("<a>",{
                                    text: "'.addslashes(get_lang('End test')).'",
                                    title: "'.addslashes(get_lang('End test')).'",
                                    href: "javascript:void(0);",
                                    click: function() {
                                        $(this).attr("disabled", "disabled");
                                        continueTest.attr("disabled", "disabled");
                                        save_now(questionId, urlExtra);
                                        $("#global-modal .modal-body").html("<span style=\"text-align:center\">'.addslashes($loading).addslashes(get_lang('Loading')).'</span>");
                                    }
                                }).addClass("btn btn--primary").appendTo("#global-modal .modal-body .btn-group");
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
    Display::getMdiIcon('alert-circle', 'ch-tool-icon', null, ICON_SIZE_SMALL, get_lang('Error')).'\');
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
                // Checking editor
                if (my_question_id) {
                    const content = getContentFromEditor("choice"+my_question_id);
                    if (content) {
                        free_answers["free_choice["+my_question_id+"]"] = content;
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
                            $("#save_all_response").html(\''.Display::getMdiIcon(StateIcon::COMPLETE, 'ch-tool-icon').'\');
                        }
                    } else {
                        $("#save_all_response").html(\''.Display::getMdiIcon(StateIcon::INCOMPLETE, 'ch-tool-icon').'\');
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

echo '<form id="exercise_form" method="post" action="'
    . api_get_self() . '?' . api_get_cidreq()
    . '&page=' . $page
    . '&reminder=' . $reminder
    . '&autocomplete=off&exerciseId=' . $exerciseId
    . '" name="frm_exercise">
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

// Show list of questions
$i = 1 + (isset($questionNumberOffset) ? (int) $questionNumberOffset : 0);
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

if ($currentBreakId) {
    ExerciseLib::showQuestion(
        $objExercise,
        $currentBreakId,
        false,
        $origin,
        '',
        false
    );
}

$prevParent = null;
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
                    echo Display::return_message(get_lang('You already answered the question'));
                    $i++;

                    break;
                }
            }

            if (1 === $exerciseInSession->getPreventBackwards()) {
                if (isset($attempt_list[$questionId])) {
                    echo Display::return_message(get_lang('Already answered'));
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
    if (ALL_ON_ONE_PAGE == $objExercise->type &&
        isset($_GET['reminder']) && 2 == $_GET['reminder']
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
        if (ONE_PER_PAGE == $objExercise->type || (ONE_PER_PAGE != $objExercise->type && 1 == $i)) {
            echo Display::panelCollapse(
                get_lang('Description'),
                Display::div($objExercise->description, ['class' => 'exercise_description wysiwyg']),
                'exercise-description',
                [],
                'description',
                'exercise-collapse',
                false,
                true
            );
        }
    }

    $q = Question::read($questionId);
    $currentParent = $q->parent_id > 0 ? $q->parent_id : null;
    if ($currentParent !== $prevParent) {
        if ($prevParent !== null) {
            echo "</div>\n";
        }
        if ($currentParent !== null) {
            echo '<div class="media-group" style="border:1px dashed #aaa; padding:15px; margin:20px 0;">';
            ExerciseLib::showQuestion(
                $objExercise,
                $currentParent,
                false,
                $origin,
                '',
                false
            );
        }
    }

    echo '<div id="question_div_'.$questionId.'" class="main-question '.$remind_highlight.'" >';

    $showQuestion = true;
    $exerciseResultFromSession = Session::read('exerciseResult');
    if (EXERCISE_FEEDBACK_TYPE_POPUP === $objExercise->getFeedbackType() &&
        isset($exerciseResultFromSession[$questionId])
    ) {
        $showQuestion = false;
    }

    // Shows the question and its answers
    if ($showQuestion) {
        $user_choice = $attempt_list[$questionId] ?? null;
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
        echo Display::return_message(get_lang('You already answered the question'));
    }

    // Button save and continue
    if (!$hasMediaWithChildren) {
        switch ($objExercise->type) {
            case ONE_PER_PAGE:
                $exerciseActions .= $objExercise->show_button(
                    $questionId,
                    $current_question,
                    [],
                    [],
                    $myRemindList,
                    $showPreviousButton
                );

                break;
            case ALL_ON_ONE_PAGE:
                if (api_is_allowed_to_session_edit()) {
                    $button = [
                        Display::button(
                            'save_now',
                            get_lang('Save and continue'),
                            [
                                'type' => 'button',
                                'class' => 'btn btn--info',
                                'data-question' => $questionId,
                            ]
                        ),
                        '<span id="save_for_now_' . $questionId . '"></span>&nbsp;',
                    ];
                    $exerciseActions .= Display::div(
                        implode(PHP_EOL, $button),
                        ['class' => 'exercise_save_now_button mb-4']
                    );
                }

                break;
        }
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
            ).get_lang('Revise question later'),
            [
                'class' => 'checkbox',
                'for' => 'remind_list['.$questionId.']',
            ]
        );
        $exerciseActions .= Display::div(
            $remind_question_div,
            ['class' => 'exercise_save_now_button mb-4']
        );
    }
    echo Display::div($exerciseActions, ['class' => 'exercise_actions']);
    echo '</div>';

    $i++;
    $prevParent = $currentParent;

    // for sequential exercises
    if (ONE_PER_PAGE == $objExercise->type) {
        // quits the loop
        break;
    }
}

if ($prevParent !== null) {
    echo "</div>\n";
}


if (ALL_ON_ONE_PAGE == $objExercise->type || $forceGrouped) {
    //$currentPageIds = implode(',', $pages[$page - 1]);
    $currentPageIds = implode(',', $questionList);
    echo '<div class="exercise_actions exercise-pagination mb-4">';
    if ($page > 1) {
        $prevUrl = api_get_self() . '?' . $submitBaseQuery . "&page=" . ($page - 1);
        echo '<button type="button" class="btn btn--secondary" '
            . "onclick=\"window.location='$prevUrl'\">"
            . '‹ ' . get_lang('Previous')
            . '</button> ';
    }

    $label = $page < $totalPages
        ? get_lang('Next') . ' ›'
        : get_lang('End test');
    echo '<button type="button" name="save_question_list" '
        . 'data-list="' . $currentPageIds . '" '
        . 'class="btn btn--primary">'
        . $label
        . '</button>';

    echo '</div>';
}
echo '</form>';
if (!in_array($origin, ['learnpath', 'embeddable'])) {
    // So we are not in learnpath tool
    echo '</div>'; //End glossary div
}
Display::display_footer();
