<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\TrackEExerciseConfirmation;
use Chamilo\CoreBundle\Entity\TrackEExercises;
use Chamilo\CourseBundle\Entity\CQuizQuestion;
use ChamiloSession as Session;

require_once __DIR__.'/../global.inc.php';
$current_course_tool = TOOL_QUIZ;
$debug = false;

ExerciseLib::logPingForCheckingConnection();

// Check if the user has access to the contextual course/session
api_protect_course_script(true);

$action = $_REQUEST['a'];
$course_id = api_get_course_int_id();
$session_id = isset($_REQUEST['session_id']) ? (int) $_REQUEST['session_id'] : api_get_session_id();
$course_code = isset($_REQUEST['cidReq']) ? $_REQUEST['cidReq'] : api_get_course_id();
$currentUserId = api_get_user_id();
$exeId = isset($_REQUEST['exe_id']) ? $_REQUEST['exe_id'] : 0;

switch ($action) {
    case 'get_exercise_by_course':
        $course_id = (isset($_GET['course_id']) && !empty($_GET['course_id'])) ? (int) $_GET['course_id'] : 0;
        $session_id = (!empty($_GET['session_id'])) ? (int) $_GET['session_id'] : 0;
        $data = [];
        $onlyActiveExercises = !(api_is_platform_admin(true) || api_is_course_admin());
        $results = ExerciseLib::get_all_exercises_for_course_id(
            null,
            $session_id,
            $course_id,
            $onlyActiveExercises
        );

        if (!empty($results)) {
            foreach ($results as $exercise) {
                $data[] = ['id' => $exercise['iid'], 'text' => html_entity_decode($exercise['title'])];
            }
        }

        echo json_encode($data);
        break;
    case 'update_duration':
        if (Session::read('login_as')) {
            if ($debug) {
                error_log("User is 'login as' don't update duration time.");
            }
            exit;
        }

        if (empty($exeId)) {
            if ($debug) {
                error_log('Exe id not provided.');
            }
            exit;
        }

        /** @var Exercise $exerciseInSession */
        $exerciseInSession = Session::read('objExercise');

        if (empty($exerciseInSession)) {
            if ($debug) {
                error_log('Exercise obj not provided.');
            }
            exit;
        }

        // If exercise was updated x seconds before, then don't updated duration.
        $onlyUpdateValue = 10;

        $em = Database::getManager();
        /** @var TrackEExercises $attempt */
        $attempt = $em->getRepository('ChamiloCoreBundle:TrackEExercises')->find($exeId);

        if (empty($attempt)) {
            if ($debug) {
                error_log("Attempt #$exeId doesn't exists.");
            }
            exit;
        }

        $nowObject = api_get_utc_datetime(null, false, true);
        $now = $nowObject->getTimestamp();
        $exerciseId = $attempt->getExeExoId();
        $userId = $attempt->getExeUserId();

        if ($userId != $currentUserId) {
            if ($debug) {
                error_log("User $currentUserId trying to change time for user $userId");
            }
            exit;
        }

        if ($exerciseInSession->iid != $exerciseId) {
            if ($debug) {
                error_log("Cannot update, exercise are different.");
            }
            exit;
        }

        if ($attempt->getStatus() != 'incomplete') {
            if ($debug) {
                error_log('Cannot update exercise is already completed.');
            }
            exit;
        }

        // Check if we are dealing with the same exercise.
        $timeWithOutUpdate = $now - $attempt->getExeDate()->getTimestamp();
        if ($timeWithOutUpdate > $onlyUpdateValue) {
            $key = ExerciseLib::get_time_control_key(
                $exerciseId,
                $attempt->getOrigLpId(),
                $attempt->getOrigLpItemId()
            );
            $durationFromObject = $attempt->getExeDuration();
            $previousTime = Session::read('duration_time_previous');
            if (isset($previousTime[$key]) &&
                !empty($previousTime[$key])
            ) {
                $sessionTime = $previousTime[$key];
                $duration = $sessionTime = $now - $sessionTime;
                if (!empty($durationFromObject)) {
                    $duration += $durationFromObject;
                }
                $duration = (int) $duration;
                if (!empty($duration)) {
                    if ($debug) {
                        error_log("Exe_id: #".$exeId);
                        error_log("Key: $key");
                        error_log("Exercise to update: #$exerciseId of user: #$userId");
                        error_log("Duration time found in DB before update: $durationFromObject");
                        error_log("Current spent time $sessionTime before an update");
                        error_log("Accumulate duration to save in DB: $duration");
                        error_log("End date (UTC) before update: ".$attempt->getExeDate()->format('Y-m-d H:i:s'));
                        error_log("End date (UTC) to be save in DB: ".$nowObject->format('Y-m-d H:i:s'));
                    }
                    $attempt
                        ->setExeDuration($duration)
                        ->setExeDate($nowObject);
                    $em->merge($attempt);
                    $em->flush();
                }
            } else {
                if ($debug) {
                    error_log("Nothing to update, 'duration_time_previous' session not set");
                    error_log("Key: $key");
                }
            }
        } else {
            if ($debug) {
                error_log("Can't update, time was already updated $timeWithOutUpdate seconds ago");
            }
        }

        break;
    case 'get_live_stats':
        if (!api_is_allowed_to_edit(null, true)) {
            break;
        }

        // 1. Setting variables needed by jqgrid
        $exercise_id = (int) $_GET['exercise_id'];
        $page = (int) $_REQUEST['page']; //page
        $limit = (int) $_REQUEST['rows']; //quantity of rows
        $sidx = $_REQUEST['sidx']; //index to filter
        $sord = $_REQUEST['sord']; //asc or desc

        if (!in_array($sidx, ['firstname', 'lastname', 'start_date'])) {
            $sidx = 1;
        }

        if (!in_array($sord, ['asc', 'desc'])) {
            $sord = 'desc';
        }
        // get index row - i.e. user click to sort $sord = $_GET['sord'];
        // get the direction
        if (!$sidx) {
            $sidx = 1;
        }

        $track_exercise = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $track_attempt = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $minutes = (int) $_REQUEST['minutes'];
        $now = time() - 60 * $minutes;
        $now = api_get_utc_datetime($now);

        $where_condition = " orig_lp_id = 0 AND exe_exo_id = $exercise_id AND start_date > '$now' ";
        $sql = "SELECT COUNT(DISTINCT exe_id)
                FROM $track_exercise
                WHERE $where_condition ";
        $result = Database::query($sql);
        $count = Database::fetch_row($result);
        $count = $count[0];

        //3. Calculating first, end, etc
        $total_pages = 0;
        if ($count > 0) {
            if (!empty($limit)) {
                $total_pages = ceil($count / $limit);
            }
        }

        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $start = $limit * $page - $limit;
        if ($start < 0) {
            $start = 0;
        }

        $sql = "SELECT
                    exe_id,
                    exe_user_id,
                    firstname,
                    lastname,
                    aa.status,
                    start_date,
                    exe_result,
                    exe_weighting,
                    exe_result/exe_weighting as score,
                    exe_duration,
                    questions_to_check,
                    orig_lp_id
                FROM $user_table u
                INNER JOIN (
                    SELECT
                        t.exe_id,
                        t.exe_user_id,
                        status,
                        start_date,
                        exe_result,
                        exe_weighting,
                        exe_result/exe_weighting as score,
                        exe_duration,
                        questions_to_check,
                        orig_lp_id
                    FROM  $track_exercise  t
                    LEFT JOIN $track_attempt a
                    ON (a.exe_id = t.exe_id AND t.exe_user_id = a.user_id)
                    WHERE t.status = 'incomplete' AND $where_condition
                    GROUP BY exe_user_id
                ) as aa
                ON aa.exe_user_id = user_id
                ORDER BY $sidx $sord
                LIMIT $start, $limit";

        $result = Database::query($sql);
        $results = [];
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $results[] = $row;
        }

        $oExe = new Exercise();
        $oExe->read($exercise_id);

        $response = new stdClass();
        $response->page = $page;
        $response->total = $total_pages;
        $response->records = $count;
        $i = 0;

        if (!empty($results)) {
            foreach ($results as $row) {
                $sql = "SELECT SUM(count_question_id) as count_question_id
                        FROM (
                            SELECT 1 as count_question_id
                            FROM $track_attempt a
                            WHERE
                              user_id = {$row['exe_user_id']} AND
                              exe_id = {$row['exe_id']}
                            GROUP by question_id
                        ) as count_table";
                $result_count = Database::query($sql);
                $count_questions = Database::fetch_array(
                    $result_count,
                    'ASSOC'
                );
                $count_questions = $count_questions['count_question_id'];

                $row['count_questions'] = $count_questions;

                $response->rows[$i]['id'] = $row['exe_id'];
                if (!empty($oExe->expired_time)) {
                    $remaining = strtotime($row['start_date']) +
                        ($oExe->expired_time * 60) -
                        strtotime(api_get_utc_datetime(time()));
                    $h = floor($remaining / 3600);
                    $m = floor(($remaining - ($h * 3600)) / 60);
                    $s = ($remaining - ($h * 3600) - ($m * 60));
                    $timeInfo = api_convert_and_format_date(
                            $row['start_date'],
                            DATE_TIME_FORMAT_LONG
                        ).' ['.($h > 0 ? $h.':' : '').sprintf("%02d", $m).':'.sprintf("%02d", $s).']';
                } else {
                    $timeInfo = api_convert_and_format_date(
                        $row['start_date'],
                        DATE_TIME_FORMAT_LONG
                    );
                }
                $array = [
                    $row['firstname'],
                    $row['lastname'],
                    $timeInfo,
                    $row['count_questions'],
                    round($row['score'] * 100).'%',
                ];
                $response->rows[$i]['cell'] = $array;
                $i++;
            }
        }
        echo json_encode($response);
        break;
    case 'update_exercise_list_order':
        if (api_is_allowed_to_edit(null, true)) {
            $new_list = $_REQUEST['exercise_list'];
            $table = Database::get_course_table(TABLE_QUIZ_ORDER);
            $counter = 1;
            //Drop all
            $sql = "DELETE FROM $table WHERE session_id = $session_id AND c_id = $course_id";
            Database::query($sql);
            // Insert all
            foreach ($new_list as $new_order_id) {
                Database::insert(
                    $table,
                    [
                        'exercise_order' => $counter,
                        'session_id' => $session_id,
                        'exercise_id' => (int) $new_order_id,
                        'c_id' => $course_id,
                    ]
                );
                $counter++;
            }
            echo Display::return_message(get_lang('Saved'), 'confirmation');
        }
        break;
    case 'update_question_order':
        $course_info = api_get_course_info_by_id($course_id);
        $course_id = $course_info['real_id'];
        $exercise_id = isset($_REQUEST['exercise_id']) ? (int) $_REQUEST['exercise_id'] : null;

        if (empty($exercise_id)) {
            return Display::return_message(get_lang('Error'), 'error');
        }
        if (api_is_allowed_to_edit(null, true)) {
            $new_question_list = $_POST['question_id_list'];
            $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
            $counter = 1;
            foreach ($new_question_list as $new_order_id) {
                Database::update(
                    $TBL_QUESTIONS,
                    ['question_order' => $counter],
                    [
                        'question_id = ? AND c_id = ? AND exercice_id = ? ' => [
                            (int) $new_order_id,
                            $course_id,
                            $exercise_id,
                        ],
                    ]
                )
                ;
                $counter++;
            }
            echo Display::return_message(get_lang('Saved'), 'confirmation');
        }
        break;
    case 'add_question_to_reminder':
        /** @var Exercise $objExercise */
        $objExercise = Session::read('objExercise');

        if (empty($objExercise) || empty($exeId)) {
            echo 0;
            exit;
        }

        $option = isset($_GET['option']) ? $_GET['option'] : '';
        switch ($option) {
            case 'add_all':
                $questionListInSession = Session::read('questionList');
                $objExercise->addAllQuestionToRemind($exeId, $questionListInSession);
                break;
            case 'remove_all':
                $objExercise->removeAllQuestionToRemind($exeId);
                break;
            default:
                $objExercise->editQuestionToRemind(
                    $exeId,
                    $_REQUEST['question_id'],
                    $_REQUEST['action']
                );
                break;
        }

        echo 1;
        exit;

        break;
    case 'check_answers':
        if (false === api_is_allowed_to_session_edit()) {
            echo 'error';
            exit;
        }

        /** @var Exercise $objExercise */
        $objExercise = Session::read('objExercise');
        $questionList = Session::read('questionList');
        $exeId = Session::read('exe_id');

        // If exercise or question is not set then exit.
        if (empty($questionList) || empty($objExercise)) {
            echo 'error';
            exit;
        }

        $statInfo = $objExercise->get_stat_track_exercise_info_by_exe_id($exeId);

        echo Display::page_subheader(get_lang('VerificationOfAnsweredQuestions'));
        echo $objExercise->getReminderTable($questionList, $statInfo, true);
        break;
    case 'save_question_description':
        if (!api_get_configuration_value('allow_quick_question_description_popup')) {
            exit;
        }
        if (!api_is_allowed_to_edit(null, true)) {
            exit;
        }

        /** @var \Exercise $objExercise */
        $objExercise = Session::read('objExercise');
        if (empty($objExercise)) {
            exit;
        }

        $questionId = isset($_REQUEST['question_id']) ? (int) $_REQUEST['question_id'] : null;
        $image = isset($_REQUEST['image']) ? $_REQUEST['image'] : '';

        $questionList = $objExercise->getQuestionList();

        if (!in_array($questionId, $questionList)) {
            echo '0';
            exit;
        }

        $em = Database::getManager();
        $repo = $em->getRepository(CQuizQuestion::class);
        /** @var CQuizQuestion $question */
        $question = $repo->find($questionId);
        if (null !== $question) {
            $question->setDescription('<img src="'.$image.'" />');
            $em->persist($question);
            $em->flush();
            echo 1;
            exit;
        }
        echo 0;
        exit;
        break;
    case 'save_exercise_by_now':
        header('Content-Type: application/json');

        $course_info = api_get_course_info_by_id($course_id);

        if (empty($course_info)) {
            echo json_encode(['error' => true]);
            exit;
        }

        $course_id = $course_info['real_id'];

        // Use have permissions to edit exercises results now?
        if (false === api_is_allowed_to_session_edit()) {
            echo json_encode(['error' => true]);
            if ($debug) {
                error_log(
                    'Exercises attempt '.$exeId.': Failed saving question(s) in course/session '.
                    $course_id.'/'.$session_id.
                    ': The user ('.api_get_user_id().') does not have the permission to access this session now'
                );
            }
            exit;
        }

        // "all" or "simple" strings means that there's one or all questions exercise type
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;

        // Questions choices.
        $choice = isset($_REQUEST['choice']) ? $_REQUEST['choice'] : [];

        // certainty degree choice
        $choiceDegreeCertainty = isset($_REQUEST['choiceDegreeCertainty']) ? $_REQUEST['choiceDegreeCertainty'] : [];

        // Hot spot coordinates from all questions.
        $hot_spot_coordinates = isset($_REQUEST['hotspot']) ? $_REQUEST['hotspot'] : [];

        // the filenames in upload answer type
        $uploadAnswerFileNames = isset($_REQUEST['uploadChoice']) ? $_REQUEST['uploadChoice'] : [];

        // There is a reminder?
        $remind_list = isset($_REQUEST['remind_list']) && !empty($_REQUEST['remind_list'])
            ? array_keys($_REQUEST['remind_list']) : [];

        // Needed in manage_answer.
        $learnpath_id = isset($_REQUEST['learnpath_id']) ? (int) $_REQUEST['learnpath_id'] : 0;
        $learnpath_item_id = isset($_REQUEST['learnpath_item_id']) ? (int) $_REQUEST['learnpath_item_id'] : 0;

        if ($debug) {
            error_log("exe_id = $exeId");
            error_log("type = $type");
            error_log("choice = ".print_r($choice, 1)." ");
            error_log("hot_spot_coordinates = ".print_r($hot_spot_coordinates, 1));
            error_log("remind_list = ".print_r($remind_list, 1));
            error_log("uploadAnswerFileNames = ".print_r($uploadAnswerFileNames, 1));
            error_log("--------------------------------");
        }

        /** @var Exercise $objExercise */
        $objExercise = Session::read('objExercise');

        // Question info.
        $question_id = isset($_REQUEST['question_id']) ? (int) $_REQUEST['question_id'] : null;
        $question_list = Session::read('questionList');

        // If exercise or question is not set then exit.
        if (empty($question_list) || empty($objExercise)) {
            echo json_encode(['error' => true]);
            if ($debug) {
                if (empty($question_list)) {
                    error_log("question_list is empty");
                }
                if (empty($objExercise)) {
                    error_log("objExercise is empty");
                }
            }
            exit;
        }

        if (WhispeakAuthPlugin::questionRequireAuthentify($question_id)) {
            if ($objExercise->type == ONE_PER_PAGE) {
                echo json_encode(['type' => 'one_per_page']);
                exit;
            }

            echo json_encode(['ok' => true]);
            exit;
        } else {
            ChamiloSession::erase(WhispeakAuthPlugin::SESSION_QUIZ_QUESTION);
        }

        // Getting information of the current exercise.
        $exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exeId);
        $exercise_id = $exercise_stat_info['exe_exo_id'];
        $attemptList = [];
        // First time here we create an attempt (getting the exe_id).
        if (!empty($exercise_stat_info)) {
            // We know the user we get the exe_id.
            $exeId = $exercise_stat_info['exe_id'];
            $total_score = $exercise_stat_info['exe_result'];
            // Getting the list of attempts
            $attemptList = Event::getAllExerciseEventByExeId($exeId);
        }

        // No exe id? Can't save answer.
        if (empty($exeId)) {
            // Fires an error.
            echo json_encode(['error' => true]);
            if ($debug) {
                error_log('exe_id is empty');
            }
            exit;
        }

        Session::write('exe_id', $exeId);

        // Updating Reminder algorithm.
        if ($objExercise->type == ONE_PER_PAGE) {
            $bd_reminder_list = explode(',', $exercise_stat_info['questions_to_check']);
            if (empty($remind_list)) {
                $remind_list = $bd_reminder_list;
                $new_list = [];
                foreach ($bd_reminder_list as $item) {
                    if ($item != $question_id) {
                        $new_list[] = $item;
                    }
                }
                $remind_list = $new_list;
            } else {
                if (isset($remind_list[0])) {
                    if (!in_array($remind_list[0], $bd_reminder_list)) {
                        array_push($bd_reminder_list, $remind_list[0]);
                    }
                    $remind_list = $bd_reminder_list;
                }
            }
        }

        // Getting the total weight if the request is simple.
        $total_weight = 0;
        if ($type === 'simple') {
            foreach ($question_list as $my_question_id) {
                $objQuestionTmp = Question::read($my_question_id, $objExercise->course);
                $total_weight += $objQuestionTmp->selectWeighting();
            }
        }
        unset($objQuestionTmp);

        if ($debug) {
            error_log('Starting questions loop in save_exercise_by_now');
        }

        $now = time();
        if ('all' === $type) {
            // Check we have at least one non-empty answer in the array
            // provided by the user's click on the "Finish test" button.
            $atLeastOneAnswer = false;
            foreach ($question_list as $my_question_id) {
                if (!empty($choice[$my_question_id])) {
                    $atLeastOneAnswer = true;
                    break;
                }
            }

            if (!$atLeastOneAnswer) {
                // Check if time is over.
                if ($objExercise->expired_time != 0) {
                    $clockExpiredTime = ExerciseLib::get_session_time_control_key(
                        $objExercise->iid,
                        $learnpath_id,
                        $learnpath_item_id
                    );
                    if (!empty($clockExpiredTime)) {
                        $timeLeft = api_strtotime($clockExpiredTime, 'UTC') - $now;
                        if ($timeLeft <= 0) {
                            // There's no time, but still no answers ...
                            echo json_encode(['ok' => true, 'savedAnswerMessage' => '']);
                            exit;
                        }
                    }
                }

                error_log(
                    'In '.__FILE__.'::action save_exercise_by_now,'.
                    ' from user '.api_get_user_id().
                    ' for track_e_exercises.exe_id = '.$exeId.
                    ', we received an empty set of answers.'.
                    'Preventing submission to avoid overwriting w/ null.'
                );
                echo json_encode(['error' => true]);
                exit;
            }
        }

        // Looping the question list from database (not from the user answer)
        foreach ($question_list as $my_question_id) {
            if ($type === 'simple' && $question_id != $my_question_id) {
                continue;
            }
            $my_choice = isset($choice[$my_question_id]) ? $choice[$my_question_id] : null;
            $objQuestionTmp = Question::read($my_question_id, $objExercise->course);
            $myChoiceDegreeCertainty = null;
            if ($objQuestionTmp->type === MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
                if (isset($choiceDegreeCertainty[$my_question_id])) {
                    $myChoiceDegreeCertainty = $choiceDegreeCertainty[$my_question_id];
                }
            }
            if ($objQuestionTmp->type === UPLOAD_ANSWER) {
                $my_choice = '';
                if (!empty($uploadAnswerFileNames)) {
                    // Clean user upload_answer folder
                    $userUploadAnswerSyspath = UserManager::getUserPathById(api_get_user_id(), 'system').'my_files'.'/upload_answer/'.$exeId.'/'.$my_question_id.'/*';
                    foreach (glob($userUploadAnswerSyspath) as $file) {
                        $filename = basename($file);
                        if (!in_array($filename, $uploadAnswerFileNames[$my_question_id])) {
                            unlink($file);
                        }
                    }
                    $my_choice = implode('|', $uploadAnswerFileNames[$my_question_id]);
                }
            }
            // Getting free choice data.
            if ('all' === $type && in_array($objQuestionTmp->type, [FREE_ANSWER, ORAL_EXPRESSION])) {
                $my_choice = isset($_REQUEST['free_choice'][$my_question_id]) && !empty($_REQUEST['free_choice'][$my_question_id])
                    ? $_REQUEST['free_choice'][$my_question_id]
                    : null;
            }

            if ($type === 'all') {
                // If saving the whole exercise (not only one question),
                // record the sum of individual max scores (called
                // "exe_weighting" in track_e_exercises)
                $total_weight += $objQuestionTmp->selectWeighting();
            }

            // This variable came from exercise_submit_modal.php.
            $hotspot_delineation_result = null;
            if (isset($_SESSION['hotspot_delineation_result']) &&
                isset($_SESSION['hotspot_delineation_result'][$objExercise->selectId()])
            ) {
                $hotspot_delineation_result = $_SESSION['hotspot_delineation_result'][$objExercise->selectId()][$my_question_id];
            }

            if ('simple' === $type) {
                // Getting old attempt in order to decrease the total score.
                $old_result = $objExercise->manage_answer(
                    $exeId,
                    $my_question_id,
                    null,
                    'exercise_show',
                    [],
                    false,
                    true,
                    false,
                    $objExercise->selectPropagateNeg()
                );
                // Removing old score.
                $total_score = $total_score - $old_result['score'];
            }

            $questionDuration = 0;
            if (api_get_configuration_value('allow_time_per_question')) {
                $extraFieldValue = new ExtraFieldValue('question');
                $value = $extraFieldValue->get_values_by_handler_and_field_variable($objQuestionTmp->iid, 'time');
                if (!empty($value) && isset($value['value']) && !empty($value['value'])) {
                    $questionDuration = Event::getAttemptQuestionDuration($exeId, $objQuestionTmp->iid);
                    if (empty($questionDuration)) {
                        echo json_encode(['error' => true]);
                        if ($debug) {
                            error_log("Question duration = 0, in exeId: $exeId, question_id: $my_question_id");
                        }
                        exit;
                    }
                }
            }

            // Deleting old attempt.
            if (isset($attemptList) && !empty($attemptList[$my_question_id])) {
                if ($debug) {
                    error_log("delete_attempt exe_id : $exeId, my_question_id: $my_question_id");
                }
                Event::delete_attempt(
                    $exeId,
                    api_get_user_id(),
                    $course_id,
                    $session_id,
                    $my_question_id
                );
                if (in_array($objQuestionTmp->type, [HOT_SPOT, HOT_SPOT_COMBINATION])) {
                    Event::delete_attempt_hotspot(
                        $exeId,
                        api_get_user_id(),
                        $course_id,
                        $session_id,
                        $my_question_id
                    );
                }

                if (isset($attemptList[$my_question_id]) &&
                    isset($attemptList[$my_question_id]['marks'])
                ) {
                    $total_score -= $attemptList[$my_question_id]['marks'];
                }
            }

            // We're inside *one* question. Go through each possible answer for this question
            if ($objQuestionTmp->type === MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY) {
                $myChoiceTmp = [];
                $myChoiceTmp['choice'] = $my_choice;
                $myChoiceTmp['choiceDegreeCertainty'] = $myChoiceDegreeCertainty;
                $result = $objExercise->manage_answer(
                    $exeId,
                    $my_question_id,
                    $myChoiceTmp,
                    'exercise_result',
                    $hot_spot_coordinates,
                    true,
                    false,
                    false,
                    $objExercise->selectPropagateNeg(),
                    $hotspot_delineation_result,
                    true,
                    false,
                    false,
                    $questionDuration
                );
            } else {
                $result = $objExercise->manage_answer(
                    $exeId,
                    $my_question_id,
                    $my_choice,
                    'exercise_result',
                    $hot_spot_coordinates,
                    true,
                    false,
                    false,
                    $objExercise->selectPropagateNeg(),
                    $hotspot_delineation_result,
                    true,
                    false,
                    false,
                    $questionDuration
                );
            }

            // Adding the new score.
            $total_score += $result['score'];

            if ($debug) {
                error_log("total_score: $total_score ");
                error_log("total_weight: $total_weight ");
            }

            $duration = 0;
            if ($type === 'all') {
                $exercise_stat_info = $objExercise->get_stat_track_exercise_info_by_exe_id($exeId);
            }

            $key = ExerciseLib::get_time_control_key(
                $exercise_id,
                $exercise_stat_info['orig_lp_id'],
                $exercise_stat_info['orig_lp_item_id']
            );

            $durationTime = Session::read('duration_time');
            if (isset($durationTime[$key]) && !empty($durationTime[$key])) {
                if ($debug) {
                    error_log('Session time: '.$durationTime[$key]);
                }
                $duration = $now - $durationTime[$key];
                if (!empty($exercise_stat_info['exe_duration'])) {
                    $duration += $exercise_stat_info['exe_duration'];
                }
                $duration = (int) $duration;
            } else {
                if (!empty($exercise_stat_info['exe_duration'])) {
                    $duration = $exercise_stat_info['exe_duration'];
                }
            }

            if ($debug) {
                error_log('duration to save in DB:'.$duration);
            }
            Session::write('duration_time', [$key => $now]);
            Event::updateEventExercise(
                $exeId,
                $objExercise->selectId(),
                $total_score,
                $total_weight,
                $session_id,
                $exercise_stat_info['orig_lp_id'],
                $exercise_stat_info['orig_lp_item_id'],
                $exercise_stat_info['orig_lp_item_view_id'],
                $duration,
                $question_list,
                'incomplete',
                $remind_list
            );

            if (api_get_configuration_value('allow_time_per_question')) {
                $questionStart = Session::read('question_start', []);
                if (!empty($questionStart)) {
                    if (isset($questionStart[$my_question_id])) {
                        unset($questionStart[$my_question_id]);
                    }
                    array_filter($questionStart);
                    Session::write('question_start', $questionStart);
                }
            }

            HookQuizQuestionAnswered::create()
                ->setEventData(
                    [
                        'exe_id' => (int) $exeId,
                        'quiz' => [
                            'id' => (int) $objExercise->iid,
                            'title' => $objExercise->selectTitle(true),
                        ],
                        'question' => [
                            'id' => (int) $my_question_id,
                            'weight' => (float) $result['weight'],
                        ],
                    ]
                )
                ->notifyQuizQuestionAnswered();

            // Destruction of the Question object
            unset($objQuestionTmp);
            if ($debug) {
                error_log("---------- end question ------------");
            }
        }
        if ($debug) {
            error_log('Finished questions loop in save_exercise_by_now');
        }

        $questionsCount = count(explode(',', $exercise_stat_info['data_tracking']));
        $savedAnswersCount = $objExercise->countUserAnswersSavedInExercise($exeId);

        if ($savedAnswersCount !== $questionsCount) {
            $savedQuestionsMessage = Display::span(
                sprintf(get_lang('XAnswersSavedByUsersFromXTotal'), $savedAnswersCount, $questionsCount),
                ['class' => 'text-warning']
            );
        } else {
            $savedQuestionsMessage = Display::span(
                sprintf(get_lang('XAnswersSavedByUsersFromXTotal'), $savedAnswersCount, $questionsCount),
                ['class' => 'text-success']
            );
        }

        if ($type === 'all') {
            if ($debug) {
                error_log("result: ok - all");
                error_log(" ------ end ajax call ------- ");
            }
            echo json_encode(['ok' => true, 'savedAnswerMessage' => $savedQuestionsMessage]);
            exit;
        }

        if ($objExercise->type == ONE_PER_PAGE) {
            if ($debug) {
                error_log("result: one_per_page");
                error_log(" ------ end ajax call ------- ");
            }
            echo json_encode(['type' => 'one_per_page', 'savedAnswerMessage' => $savedQuestionsMessage]);
            exit;
        }
        if ($debug) {
            error_log("result: ok");
            error_log(" ------ end ajax call ------- ");
        }
        echo json_encode(['ok' => true, 'savedAnswerMessage' => $savedQuestionsMessage]);
        break;
    case 'show_question_attempt':
        $isAllowedToEdit = api_is_allowed_to_edit(null, true, false, false);

        if (!$isAllowedToEdit) {
            api_not_allowed(true);
            exit;
        }

        $questionId = isset($_GET['question']) ? (int) $_GET['question'] : 0;
        $exerciseId = isset($_REQUEST['exercise']) ? (int) $_REQUEST['exercise'] : 0;

        if (!$questionId || !$exerciseId) {
            break;
        }

        $objExercise = new Exercise();
        $objExercise->read($exerciseId);
        $objQuestion = Question::read($questionId);
        $id = '';
        if (api_get_configuration_value('show_question_id')) {
            $id = '<h4>#'.$objQuestion->course['code'].'-'.$objQuestion->iid.'</h4>';
        }
        echo $id;
        echo '<p class="lead">'.$objQuestion->get_question_type_name().'</p>';
        if (in_array($objQuestion->type, [FILL_IN_BLANKS, FILL_IN_BLANKS_COMBINATION])) {
            echo '<script>
                $(function() {
                    $(".selectpicker").selectpicker({});
                });
            </script>';
        }

        // Allows render MathJax elements in a ajax call
        if (api_get_setting('include_asciimathml_script') === 'true') {
            echo '<script> MathJax.Hub.Queue(["Typeset",MathJax.Hub]);</script>';
        }

        if (in_array($objQuestion->type, [HOT_SPOT, HOT_SPOT_COMBINATION])) {
            echo '<script src="'.api_get_path(WEB_LIBRARY_JS_PATH).'hotspot/js/hotspot.js"></script>';
        }

        $attemptList = [];
        if (!empty($exeId)) {
            $attemptList = Event::getAllExerciseEventByExeId($exeId);
        }

        $userChoice = isset($attemptList[$questionId]) ? $attemptList[$questionId] : null;

        ExerciseLib::showQuestion(
            $objExercise,
            $questionId,
            false,
            null,
            null,
            false,
            true,
            $userChoice,
            true
        );
        break;
    case 'show_question':
        $isAllowedToEdit = api_is_allowed_to_edit(null, true, false, false);

        if (!$isAllowedToEdit) {
            api_not_allowed(true);
            exit;
        }

        $questionId = isset($_GET['question']) ? (int) $_GET['question'] : 0;
        $exerciseId = isset($_REQUEST['exercise']) ? (int) $_REQUEST['exercise'] : 0;

        if (!$questionId || !$exerciseId) {
            break;
        }

        $objExercise = new Exercise();
        $objExercise->read($exerciseId);
        $objQuestion = Question::read($questionId);
        $id = '';
        if (api_get_configuration_value('show_question_id')) {
            $id = '<h4>#'.$objQuestion->course['code'].'-'.$objQuestion->iid.'</h4>';
        }
        echo $id;
        echo '<p class="lead">'.$objQuestion->get_question_type_name().'</p>';
        if (in_array($objQuestion->type, [FILL_IN_BLANKS, FILL_IN_BLANKS_COMBINATION])) {
            echo '<script>
                $(function() {
                    $(".selectpicker").selectpicker({});
                });
            </script>';
        }

        // Allows render MathJax elements in a ajax call
        if (api_get_setting('include_asciimathml_script') === 'true') {
            echo '<script> MathJax.Hub.Queue(["Typeset",MathJax.Hub]);</script>';
        }

        ExerciseLib::showQuestion(
            $objExercise,
            $questionId,
            false,
            null,
            null,
            false,
            true,
            false,
            true,
            true
        );
        break;
    case 'get_quiz_embeddable':
        $exercises = ExerciseLib::get_all_exercises_for_course_id(
            api_get_course_info(),
            api_get_session_id(),
            api_get_course_int_id(),
            false
        );

        $exercises = array_filter(
            $exercises,
            function (array $exercise) {
                return ExerciseLib::isQuizEmbeddable($exercise);
            }
        );

        $result = [];

        $codePath = api_get_path(WEB_CODE_PATH);

        foreach ($exercises as $exercise) {
            $title = Security::remove_XSS(api_html_entity_decode($exercise['title']));

            $result[] = [
                'id' => $exercise['iid'],
                'title' => strip_tags($title),
            ];
        }

        header('Content-Type: application/json');
        echo json_encode($result);
        break;
    case 'browser_test':
        $quizCheckButtonEnabled = api_get_configuration_value('quiz_check_button_enable');

        if ($quizCheckButtonEnabled) {
            if (isset($_POST['sleep'])) {
                sleep(2);
            }

            echo 'ok';
        }

        break;
    case 'quiz_confirm_saved_answers':
        if (false === api_get_configuration_value('quiz_confirm_saved_answers')) {
            break;
        }

        $trackConfirmationId = isset($_POST['tc_id']) ? (int) $_POST['tc_id'] : 0;
        $cId = api_get_course_int_id();
        $sessionId = api_get_session_id();
        $userId = api_get_user_id();
        $confirmed = !empty($_POST['quiz_confirm_saved_answers_check']);

        $em = Database::getManager();
        $repo = $em->getRepository('ChamiloCoreBundle:TrackEExerciseConfirmation');

        try {
            if (!$trackConfirmationId) {
                throw new Exception(get_lang('ErrorOccurred'));
            }

            /** @var TrackEExerciseConfirmation $trackConfirmation */
            $trackConfirmation = $repo->findOneBy(
                [
                    'id' => $trackConfirmationId,
                    'userId' => $userId,
                    'courseId' => $cId,
                    'sessionId' => $sessionId,
                ],
                ['createdAt' => 'DESC']
            );

            if (!$trackConfirmation) {
                throw new Exception(get_lang('NotFound'));
            }

            $trackConfirmation
                ->setConfirmed($confirmed)
                ->setUpdatedAt(api_get_utc_datetime(null, false, true));

            $em->persist($trackConfirmation);
            $em->flush();

            http_response_code(200);
        } catch (Exception $exception) {
            http_response_code(500);

            echo Display::return_message($exception->getMessage(), 'error');
        }

        break;
    case 'sign_attempt':
        api_block_anonymous_users();
        if ('true' !== api_get_plugin_setting('exercise_signature', 'tool_enable')) {
            exit;
        }

        $file = isset($_REQUEST['file']) ? $_REQUEST['file'] : '';
        if (empty($exeId) || empty($file)) {
            echo 0;
            exit;
        }

        $file = str_replace(' ', '+', $file);
        $track = ExerciseLib::get_exercise_track_exercise_info($exeId);
        if ($track) {
            $result = ExerciseSignaturePlugin::saveSignature($currentUserId, $track, $file);
            if ($result) {
                echo 1;
                exit;
            }
        }
        echo 0;
        break;
    case 'upload_answer':
        api_block_anonymous_users();

        if (isset($_REQUEST['chunkAction']) && 'send' === $_REQUEST['chunkAction']) {
            // It uploads the files in chunks
            if (!empty($_FILES)) {
                $tempDirectory = api_get_path(SYS_ARCHIVE_PATH);
                $files = $_FILES['files'];
                $fileList = [];
                foreach ($files as $name => $array) {
                    $counter = 0;
                    foreach ($array as $data) {
                        $fileList[$counter][$name] = $data;
                        $counter++;
                    }
                }
                if (!empty($fileList)) {
                    foreach ($fileList as $n => $file) {
                        $tmpFile = disable_dangerous_file(
                            api_replace_dangerous_char($file['name'])
                        );

                        file_put_contents(
                            $tempDirectory.$tmpFile,
                            fopen($file['tmp_name'], 'r'),
                            FILE_APPEND
                        );
                    }
                }
            }
            echo json_encode([
                'files' => $_FILES,
                'errorStatus' => 0,
            ]);
            exit;
        } else {
            if (!empty($_FILES)) {
                $currentDirectory = Security::remove_XSS($_REQUEST['curdirpath']);
                $userId = api_get_user_id();

                // Upload answer path is created inside user personal folder my_files/upload_answer/[exe_id]/[question_id]
                $syspath = UserManager::getUserPathById($userId, 'system').'my_files'.$currentDirectory;
                @mkdir($syspath, api_get_permissions_for_new_directories(), true);
                $webpath = UserManager::getUserPathById($userId, 'web').'my_files'.$currentDirectory;

                $files = $_FILES['files'];
                $fileList = [];
                foreach ($files as $name => $array) {
                    $counter = 0;
                    foreach ($array as $data) {
                        $fileList[$counter][$name] = $data;
                        $counter++;
                    }
                }
                $resultList = [];
                foreach ($fileList as $file) {
                    $json = [];

                    if (isset($_REQUEST['chunkAction']) && 'done' === $_REQUEST['chunkAction']) {
                        // to rename and move the finished file
                        $chunkedFile = api_get_path(SYS_ARCHIVE_PATH).$file['name'];
                        $file['tmp_name'] = $chunkedFile;
                        $file['size'] = filesize($chunkedFile);
                        $file['copy_file'] = true;
                    }

                    $filename = api_replace_dangerous_char($file['name']);
                    $filename = disable_dangerous_file($filename);

                    if (isset($file['copy_file']) && $file['copy_file']) {
                        $uploaded = copy($file['tmp_name'], $syspath.$filename);
                        @unlink($file['tmp_name']);
                    } else {
                        $uploaded = move_uploaded_file($file['tmp_name'], $syspath.$filename);
                    }

                    if ($uploaded) {
                        $title = $filename;
                        $url = $webpath.$filename;
                        $json['name'] = api_htmlentities($title);
                        $json['link'] = Display::url(
                            api_htmlentities($title),
                            api_htmlentities($url),
                            ['target' => '_blank']
                        );
                        $json['url'] = $url;
                        $json['size'] = format_file_size($file['size']);
                        $json['type'] = api_htmlentities($file['type']);
                        $json['result'] = Display::return_icon(
                            'accept.png',
                            get_lang('Uploaded')
                        );
                    } else {
                        $json['name'] = isset($file['name']) ? $filename : get_lang('Unknown');
                        $json['url'] = '';
                        $json['error'] = get_lang('Error');
                    }
                    $resultList[] = $json;
                }
                echo json_encode(['files' => $resultList]);
                exit;
            }
        }
        break;
    default:
        echo '';
}
exit;
