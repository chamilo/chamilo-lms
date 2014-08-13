<?php
/* For licensing terms, see /license.txt */
/**
 * Responses to AJAX calls
 */

require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/question.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/answer.class.php';

use \ChamiloSession as Session;

api_protect_course_script(true);

$action = $_REQUEST['a'];
$course_id = api_get_course_int_id();

if ($debug) {
    error_log("$action ajax call");
}

$session_id = isset($_REQUEST['session_id']) ? intval($_REQUEST['session_id']) : api_get_session_id();
$course_code = isset($_REQUEST['cidReq']) ? $_REQUEST['cidReq'] : api_get_course_id();

switch ($action) {
    case 'get_question':
        /** @var Exercise $objExercise */
        $objExercise = $_SESSION['objExercise'];

        $questionId = $_REQUEST['questionId'];
        $attemptList = isset($_REQUEST['attemptList']) ? $_REQUEST['attemptList'] : null;
        $remindList = isset($_REQUEST['remindList']) ? $_REQUEST['remindList'] : null;

        $i = $_REQUEST['i'];
        $current_question = $_REQUEST['current_question'];
        $questions_in_media = $_REQUEST['questions_in_media'];
        $last_question_in_media = $_REQUEST['last_question_in_media'];
        $realQuestionList = isset($_REQUEST['realQuestionList']) ? $_REQUEST['realQuestionList'] : null;

        $objExercise->renderQuestion(
            $questionId,
            $attemptList,
            $remindList,
            $i,
            $current_question,
            $questions_in_media,
            $last_question_in_media,
            $realQuestionList,
            false
        );
        break;
    case 'get_categories_by_media':
        $questionId = $_REQUEST['questionId'];
        $mediaId = $_REQUEST['mediaId'];
        $exerciseId = $_REQUEST['exerciseId'];
        $question = Question::read($questionId);

        if (empty($mediaId)) {
            echo 0;
            break;
        }
        $categoryId = $question->allQuestionWithMediaHaveTheSameCategory($exerciseId, $mediaId, null, null, true);

        if (!empty($categoryId)) {
            $category = new Testcategory($categoryId);
            echo json_encode(
                array(
                    'title' => $category->title,
                    'value' => $category->id
                )
            );
        } else {
            echo -1;
        }
        break;
    case 'exercise_category_exists':
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'simple';
        $category = new Testcategory(null, null, null, null, $type);
        $category->getCategory($_REQUEST['id']);
        if (empty($category->id)) {
            echo 0;
        } else {
            $courseId = api_get_course_int_id();
            if (isset($courseId)) {
                // Global
                if ($category->c_id == 0) {
                    echo 1;
                    exit;
                } else {
                    // Local
                    if ($category->c_id == $courseId) {
                        echo 1;
                        exit;
                    }
                }
            } else {
                echo 0;
                exit;
            }
        }
        break;
    case 'search_category_parent':
        $type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'simple';
        $filterByGlobal = isset($_REQUEST['filter_by_global']) ? $_REQUEST['filter_by_global'] : null;

        $cat = new Testcategory(null, null, null, null, $type);
        $items = $cat->get_categories_by_keyword($_REQUEST['tag']);

        $courseId = api_get_course_int_id();

        $em = Database::getManager();
        $repo = $em->getRepository('ChamiloCoreBundle:CQuizCategory');

        $json_items = array();
        if (!empty($items)) {
            foreach ($items as $item) {
                if ($item['c_id'] == 0) {
                    if ($filterByGlobal) {
                        $cat = $em->find('ChamiloCoreBundle:CQuizCategory', $item['iid']);
                        $idList = array();
                        if ($cat) {
                            $path = $repo->getPath($cat);
                            if (!empty($path)) {
                                /** @var \Chamilo\Entity\CQuizCategory $cat */
                                foreach ($path as $cat) {
                                    $idList[] = $cat->getIid();
                                }
                            }
                        }

                        if (isset($idList) && !empty($idList)) {
                            if (!in_array($filterByGlobal, $idList)) {
                                continue;
                            }
                        }
                    }
                    $item['title'] .= " [".get_lang('Global')."]";
                } else {
                    if (isset($courseId)) {
                        if ($item['c_id'] != $item['c_id']) {
                            continue;
                        }
                    }
                }
                $json_items[] = array(
                    'key' => $item['iid'],
                    'value' => $item['title']
                );
            }
        }
        echo json_encode($json_items);
        break;
    case 'get_live_stats':
        if (!api_is_allowed_to_edit(null, true)) {
            break;
        }

        // 1. Setting variables needed by jqgrid
        $action         = $_GET['a'];
        $exercise_id    = intval($_GET['exercise_id']);
        $page           = intval($_REQUEST['page']); //page
        $limit          = intval($_REQUEST['rows']); //quantity of rows
        $sidx           = $_REQUEST['sidx'];         //index to filter
        $sord           = $_REQUEST['sord'];         //asc or desc

        if (!in_array($sord, array('asc','desc'))) {
            $sord = 'desc';
        }

        // get index row - i.e. user click to sort $sord = $_GET['sord'];
        // get the direction
        if (!$sidx) {
            $sidx = 1;
        }

        $track_exercise        = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
        $user_table            = Database::get_main_table(TABLE_MAIN_USER);
        $track_attempt         = Database::get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

        $minutes        = intval($_REQUEST['minutes']);
        $now            = time() - 60*$minutes; //1 hour
        $now            = api_get_utc_datetime($now);

        $where_condition = " orig_lp_id = 0 AND exe_exo_id = $exercise_id AND start_date > '$now' ";
        $sql    = "SELECT COUNT(DISTINCT exe_id) FROM $track_exercise WHERE $where_condition ";
        $result = Database::query($sql);

        $count  = Database::fetch_row($result);
        $count  = $count[0];

        //3. Calculating first, end, etc

        $total_pages = 0;
        if ($count > 0) {
            if (!empty($limit)) {
                $total_pages = ceil($count/$limit);
            }
        }
        if ($page > $total_pages) {
            $page = $total_pages;
        }

        $start = $limit * $page - $limit;
        if ($start < 0) {
            $start = 0;
        }

        $sql = "SELECT  exe_id,
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
                    SELECT  t.exe_id, t.exe_user_id, status,
                    start_date, exe_result, exe_weighting, exe_result/exe_weighting as score, exe_duration, questions_to_check, orig_lp_id
                    FROM  $track_exercise  t LEFT JOIN $track_attempt a ON (a.exe_id = t.exe_id AND  t.exe_user_id = a.user_id )
                    WHERE t.status = 'incomplete' AND
                          $where_condition
                    GROUP BY exe_user_id
                ) as aa
                ON aa.exe_user_id = user_id
                ORDER BY $sidx $sord LIMIT $start, $limit";

        $result = Database::query($sql);
        $results = array();
        while ($row = Database::fetch_array($result, 'ASSOC')) {
            $results[] = $row;
        }

        $oExe = new exercise();
        $oExe->read($exercise_id);

        $response = new stdClass();
        $response->page     = $page;
        $response->total    = $total_pages;
        $response->records  = $count;
        $i=0;

        if (!empty($results)) {
            foreach($results as $row) {
                $sql = "SELECT SUM(count_question_id) as count_question_id FROM (
                            SELECT 1 as count_question_id FROM  $track_attempt a
                            WHERE user_id = {$row['exe_user_id']} and exe_id = {$row['exe_id']}
                            GROUP by question_id
                        ) as count_table";
                $result_count = Database::query($sql);
                $count_questions = Database::fetch_array($result_count,'ASSOC');
                $count_questions = $count_questions['count_question_id'];

                $row['count_questions'] = $count_questions;

                $response->rows[$i]['id'] = $row['exe_id'];
                $remaining = strtotime($row['start_date'])+($oExe->expired_time*60) - strtotime(api_get_utc_datetime(time()));
                $h = floor($remaining/3600);
                $m = floor(($remaining - ($h*3600))/60);
                $s = ($remaining - ($h*3600) - ($m*60));
                $array = array(
                    $row['firstname'],
                    $row['lastname'],
                    api_format_date($row['start_date'], DATE_TIME_FORMAT_LONG).' ['.($h>0?$h.':':'').sprintf("%02d",$m).':'.sprintf("%02d",$s).']',
                    $row['count_questions'],
                    round($row['score']*100).'%'
                );
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
            Database::query("DELETE FROM $table WHERE session_id = $session_id AND c_id = $course_id");
            //Insert all
            foreach ($new_list as $new_order_id) {
                Database::insert($table, array('exercise_order' => $counter, 'session_id' => $session_id, 'exercise_id' => intval($new_order_id), 'c_id' => $course_id));
                $counter++;
            }
            Display::display_confirmation_message(get_lang('Saved'));
        }
        break;
    case 'update_question_order':

        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];

        $exercise_id = isset($_REQUEST['exercise_id']) ? $_REQUEST['exercise_id'] : null;

        if (empty($exercise_id)) {
            return Display::display_error_message(get_lang('Error'));
        }
        if (api_is_allowed_to_edit(null, true)) {
            $new_question_list     = $_POST['question_id_list'];
            $TBL_QUESTIONS = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION);
            $counter = 1;
            foreach ($new_question_list as $new_order_id) {
                Database::update(
                    $TBL_QUESTIONS,
                    array('question_order' => $counter),
                    array('question_id = ? AND c_id = ? AND exercice_id = ? '=> array(intval($new_order_id), $course_id, $exercise_id)));
                $counter++;
            }
            Display::display_confirmation_message(get_lang('Saved'));
        }
        break;
    case 'add_question_to_reminder':
    	$objExercise  = $_SESSION['objExercise'];
    	if (empty($objExercise)) {
    		echo 0;
    		exit;
    	} else {
    		$objExercise->edit_question_to_remind($_REQUEST['exe_id'], $_REQUEST['question_id'], $_REQUEST['action']);
    	}
    	break;
    case 'save_exercise_by_now':
        $course_info = api_get_course_info($course_code);
        $course_id = $course_info['real_id'];
        //Use have permissions?
        if (api_is_allowed_to_session_edit()) {

            // "all" or "simple" strings means that there's one or all questions exercise type
            $type                   = isset($_REQUEST['type']) ? $_REQUEST['type'] : null;

            // Questions choices
            $choice                 = isset($_REQUEST['choice']) ? $_REQUEST['choice'] : null;

            // Hotspot coordinates from all questions
            $hot_spot_coordinates   = isset($_REQUEST['hotspot']) ? $_REQUEST['hotspot'] : null;

            // There is a reminder?
            $remind_list            = isset($_REQUEST['remind_list']) && !empty($_REQUEST['remind_list'])? array_keys($_REQUEST['remind_list']) : null;

            // Needed in manage_answer
            $learnpath_id           = isset($_REQUEST['learnpath_id']) ? intval($_REQUEST['learnpath_id']) : 0;
            $learnpath_item_id      = isset($_REQUEST['learnpath_item_id']) ? intval($_REQUEST['learnpath_item_id']) : 0;

            // Attempt id
            $exe_id = $_REQUEST['exe_id'];

            if ($debug) {
                error_log("exe_id = $exe_id ");
                error_log("type = $type ");
                error_log("choice = ".print_r($choice, 1)." ");
                error_log("hot_spot_coordinates = ".print_r($hot_spot_coordinates, 1));
                error_log("remind_list = ".print_r($remind_list, 1));
            }

            // Exercise information.
            /** @var \Exercise $objExercise */
            $objExercise             = isset($_SESSION['objExercise']) ? $_SESSION['objExercise'] : null;

            // Question info.
            $question_id             = isset($_REQUEST['question_id']) ? intval($_REQUEST['question_id']) : null;
            $question_list           = Session::read('question_list_uncompressed');

            // If exercise or question is not set then exit.
            if (empty($question_list) || empty($objExercise)) {
                echo 'error';
                exit;
            }

            // Getting information of the current exercise.
            $exercise_stat_info = $objExercise->getStatTrackExerciseInfoByExeId($exe_id);

            $exercise_id = $exercise_stat_info['exe_exo_id'];

            $attempt_list = array();

            // First time here we create an attempt (getting the exe_id).
            if (empty($exercise_stat_info)) {
            } else {
                // We know the user we get the exe_id.
                $exe_id        = $exercise_stat_info['exe_id'];
                $total_score   = $exercise_stat_info['exe_result'];

                // Getting the list of attempts.
                $attempt_list  = getAllExerciseEventByExeId($exe_id);
            }

            // Updating Reminder algorythme.
            if ($objExercise->type == ONE_PER_PAGE) {
                $bd_reminder_list = explode(',', $exercise_stat_info['questions_to_check']);

                // Fixing reminder order
                $fixedRemindList = array();
                if (!empty($bd_reminder_list)) {
                    foreach ($question_list as $questionId) {
                        if (in_array($questionId, $bd_reminder_list)) {
                            $fixedRemindList[] = $questionId;
                        }
                    }
                }

                $bd_reminder_list = $fixedRemindList;

                if (empty($remind_list)) {
                    $remind_list = $bd_reminder_list;

                    $new_list = array();
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

            // No exe id? Can't save answer!
            if (empty($exe_id)) {
                // Fires an error.
                echo 'error';
                exit;
            } else {
                $_SESSION['exe_id'] = $exe_id;
            }

            // Getting the total weight if the request is simple
            $total_weight = 0;

            if ($type == 'simple') {
                foreach ($question_list as $my_question_id) {
                    $objQuestionTmp  = Question::read($my_question_id, $course_id);
                    $total_weight   += $objQuestionTmp->selectWeighting();
                }
            }

            unset($objQuestionTmp);

            // Looping the question list

            if ($debug) {
                error_log("Looping question list".print_r($question_list, 1));
                error_log("Trying to save question: $question_id ");
            }

            foreach ($question_list as $my_question_id) {

                if ($type == 'simple' && $question_id != $my_question_id) {
                    continue;
                }


                $my_choice = isset($choice[$my_question_id]) ? $choice[$my_question_id] : null;

                if ($debug) {
                    error_log("Saving question_id = $my_question_id ");
                    error_log("my_choice = ".print_r($my_choice, 1)."");
                }

                // Creates a temporary Question object
            	$objQuestionTmp = Question::read($my_question_id, $course_id);

                if ($objExercise->type == ONE_PER_PAGE && $objQuestionTmp->type == UNIQUE_ANSWER) {
                    if (in_array($my_question_id, $remind_list)) {
                        if (empty($my_choice)) {
                            echo 'answer_required';
                            exit;
                        }
                    }
                }

                // Getting free choice data.
            	if ($objQuestionTmp->type  == FREE_ANSWER && $type == 'all') {
            	    $my_choice = isset($_REQUEST['free_choice'][$my_question_id]) && !empty($_REQUEST['free_choice'][$my_question_id])? $_REQUEST['free_choice'][$my_question_id]: null;
            	}

                if ($type == 'all') {
                    $total_weight += $objQuestionTmp->selectWeighting();
                }

                // This variable came from exercise_submit_modal.php
                $hotspot_delineation_result = null;
                if (isset($_SESSION['hotspot_delineation_result']) && isset($_SESSION['hotspot_delineation_result'][$objExercise->selectId()])) {
            	    $hotspot_delineation_result = $_SESSION['hotspot_delineation_result'][$objExercise->selectId()][$my_question_id];
                }

                if ($type == 'simple') {
                    // Getting old attempt in order to decrees the total score.
                    $old_result = $objExercise->manageAnswers(
                        $exe_id,
                        $my_question_id,
                        null,
                        'exercise_show',
                        array(),
                        false,
                        true,
                        false
                    );

                    // Removing old score.
                    $total_score = $total_score - $old_result['score'];
                    if ($debug) {
                        error_log("old score = ".$old_result['score']);
                        error_log("total_score = ".$total_score."");
                    }
                }

                // Deleting old attempt
                if (isset($attempt_list) && !empty($attempt_list[$my_question_id])) {
                    if ($debug) {
                        error_log("delete_attempt  exe_id : $exe_id, my_question_id: $my_question_id");
                    }
                    delete_attempt($exe_id, api_get_user_id(), $course_id, $session_id, $my_question_id);
                    if ($objQuestionTmp->type  == HOT_SPOT) {
            	        delete_attempt_hotspot($exe_id, api_get_user_id(), $course_id, $my_question_id);
                    }
                    if (isset($attempt_list[$my_question_id]) && isset($attempt_list[$my_question_id]['marks'])) {
            	        $total_score  -= $attempt_list[$my_question_id]['marks'];
            	    }
            	}

            	// We're inside *one* question. Go through each possible answer for this question

            	$result = $objExercise->manageAnswers(
                    $exe_id,
                    $my_question_id,
                    $my_choice,
                    'exercise_result',
                    $hot_spot_coordinates,
                    true,
                    false,
                    false,
                    $hotspot_delineation_result
                );

                //Adding the new score
                $total_score += $result['score'];

                if ($debug) {
                    error_log("total_score: $total_score ");
                    error_log("total_weight: $total_weight ");
                }

                $duration = 0;
                $now = time();

                if ($type == 'all') {
                    $exercise_stat_info = $objExercise->getStatTrackExerciseInfoByExeId($exe_id);
                }

                $key = ExerciseLib::get_time_control_key($exercise_id, $exercise_stat_info['orig_lp_id'], $exercise_stat_info['orig_lp_item_id']);

                /*$durationTime = array(
                    'duration_time' => array(
                        $key => time()
                    )
                );*/

                $durationTime = Session::read('duration_time');

                if (isset($durationTime[$key]) && !empty($durationTime[$key])) {
                    $duration = $now - $durationTime[$key];

                    if (!empty($exercise_stat_info['exe_duration'])) {
                        $duration += $exercise_stat_info['exe_duration'];
                    }
                    $duration = intval($duration);
                } else {
                    if (!empty($exercise_stat_info['exe_duration'])) {
                        $duration = $exercise_stat_info['exe_duration'];
                    }
                }

                $durationTime = array(
                    $key => time()
                );

                Session::write('duration_time', $durationTime);
                // $_SESSION['duration_time'][$key] = time();

                update_event_exercise(
                    $exe_id,
                    $objExercise->selectId(),
                    $total_score,
                    $total_weight,
                    $session_id,
                    $exercise_stat_info['orig_lp_id'],
                    $exercise_stat_info['orig_lp_item_id'],
                    $exercise_stat_info['orig_lp_item_view_id'],
                    $duration,
                    'incomplete',
                    $remind_list
                );

                 // Destruction of the Question object
            	unset($objQuestionTmp);
                if ($debug) error_log(" -- end question -- ");
            }
            if ($debug) error_log(" ------ end ajax call ------- ");
        }

        if ($objExercise->type == ONE_PER_PAGE) {
            echo 'one_per_page';
            exit;
        }
        echo 'ok';
        break;
    case 'correct_exercise_result':

        $is_allowedToEdit = api_is_allowed_to_edit(null, true) || api_is_drh();
        $is_tutor = api_is_allowed_to_edit(true);

        //Send student email @todo move this code in a class, library
        if (isset($_POST['comments']) && $_POST['comments'] == 'update' && ($is_allowedToEdit || $is_tutor) && $_POST['exeid'] == strval(intval($_POST['exeid']))) {
            $exeId = intval($_POST['exeid']);

            $TBL_QUESTIONS = Database :: get_course_table(TABLE_QUIZ_QUESTION);
            $TBL_TRACK_EXERCICES = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
            $TBL_TRACK_ATTEMPT = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
            $TBL_TRACK_ATTEMPT_RECORDING = Database :: get_main_table(TABLE_STATISTIC_TRACK_E_ATTEMPT_RECORDING);
            $TBL_LP_ITEM_VIEW = Database :: get_course_table(TABLE_LP_ITEM_VIEW);

            $track_exercise_info = ExerciseLib::get_exercise_track_exercise_info($exeId);

            if (empty($track_exercise_info)) {
                echo 0;
            }

            $test = $track_exercise_info['title'];
            $student_id = $track_exercise_info['exe_user_id'];
            $session_id = $track_exercise_info['session_id'];
            $lp_id = $track_exercise_info['orig_lp_id'];
            //$lp_item_id        = $track_exercise_info['orig_lp_item_id'];
            $lp_item_view_id = $track_exercise_info['orig_lp_item_view_id'];

            $course_info = api_get_course_info();

            // Teacher data
            $teacher_info = api_get_user_info(api_get_user_id());

            $from_name = api_get_person_name(
                $teacher_info['firstname'],
                $teacher_info['lastname'],
                null,
                PERSON_NAME_EMAIL_ADDRESS
            );

            $url = api_get_path(WEB_CODE_PATH).'exercice/result.php?id='.$track_exercise_info['exe_id'].'&'.api_get_cidreq().'&show_headers=1&id_session='.$session_id;

            $commentIds = array();
            $marks = array();
            $comments = array();

            $result = array();
            $values = explode(',', $_POST['vals']);
            if (empty($values)) {
                echo 0;
                exit;
            }

            $countComments = count($commentIds);

            foreach ($values as $questionId) {
                $questionId = intval($questionId);
                $comment = isset($_POST['comments_'.$questionId]) ? $_POST['comments_'.$questionId] : null;
                $mark = isset($_POST['marks_'.$questionId]) ? $_POST['marks_'.$questionId] : null;

                $mark = Database::escape_string($mark);
                $comment = Database::escape_string($comment);

                $query = "UPDATE $TBL_TRACK_ATTEMPT SET marks = '$mark', teacher_comment = '$comment'
                          WHERE question_id = ".$questionId." AND exe_id=".$exeId;
                Database::query($query);

                //Saving results in the track recording table
                $recording_changes = 'INSERT INTO '.$TBL_TRACK_ATTEMPT_RECORDING.' (exe_id, question_id, marks, insert_date, author, teacher_comment)
                                      VALUES ('."'$exeId','".$questionId."','$mark','".api_get_utc_datetime()."','".api_get_user_id()."'".',"'.$comment.'")';
                Database::query($recording_changes);
            }

            $qry = 'SELECT DISTINCT question_id, marks FROM '.$TBL_TRACK_ATTEMPT.' WHERE exe_id = '.$exeId.' GROUP BY question_id';
            $res = Database::query($qry);
            $tot = 0;
            while ($row = Database :: fetch_array($res, 'ASSOC')) {
                $tot += $row['marks'];
            }

            $sql = "UPDATE $TBL_TRACK_EXERCICES SET exe_result = '".floatval($tot)."' WHERE exe_id = ".$exeId;
            Database::query($sql);

            if (isset($_POST['send_notification'])) {
                //@todo move this somewhere else
                $subject = get_lang('ExamSheetVCC');

                $message = '<p>'.get_lang('DearStudentEmailIntroduction').'</p><p>'.get_lang('AttemptVCC');
                $message .= '<h3>'.get_lang('CourseName').'</h3><p>'.Security::remove_XSS($course_info['name']).'';
                $message .= '<h3>'.get_lang('Exercise').'</h3><p>'.Security::remove_XSS($test);

                //Only for exercises not in a LP
                if ($lp_id == 0) {
                    $message .= '<p>'.get_lang('ClickLinkToViewComment').' <a href="#url#">#url#</a><br />';
                }

                $message .= '<p>'.get_lang('Regards').'</p>';
                $message .= $from_name;
                $message = str_replace("#test#", Security::remove_XSS($test), $message);
                $message = str_replace("#url#", $url, $message);
                MessageManager::send_message_simple($student_id, $subject, $message, api_get_user_id());
            }

            $origin = $_POST['origin'];

            // Updating LP score here
            if (isset($origin) && in_array($origin, array('tracking_course', 'user_course', 'correct_exercise_in_lp'))) {
                $sql_update_score = "UPDATE $TBL_LP_ITEM_VIEW SET score = '".floatval($tot)."' WHERE c_id = ".$course_id." AND id = ".$lp_item_view_id;
                Database::query($sql_update_score);
            }
            echo 1;
            exit;
        }
        echo 0;
        break;
    default:
        echo '';
}
exit;
