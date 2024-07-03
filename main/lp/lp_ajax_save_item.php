<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This script contains the server part of the AJAX interaction process.
 * The client part is located * in lp_api.php or other api's.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

/**
 * Writes an item's new values into the database and returns the operation result.
 *
 * @param int    $lp_id                Learnpath ID
 * @param int    $user_id              User ID
 * @param int    $view_id              View ID
 * @param int    $item_id              Item ID
 * @param float  $score                Current score
 * @param float  $max                  Maximum score
 * @param float  $min                  Minimum score
 * @param string $status               Lesson status
 * @param int    $time                 Session time
 * @param string $suspend              Suspend data
 * @param string $location             Lesson location
 * @param array  $interactions         Interactions array
 * @param string $core_exit            Core exit SCORM string
 * @param int    $sessionId            Session ID
 * @param int    $courseId             Course ID
 * @param int    $lmsFinish            Whether the call was issued from SCORM's LMSFinish()
 * @param int    $userNavigatesAway    Whether the user is moving to another item
 * @param int    $statusSignalReceived Whether the SCO called SetValue(lesson_status)
 *
 * @return bool|string|null The resulting JS string
 */
function save_item(
    $lp_id,
    $user_id,
    $view_id,
    $item_id,
    $score = -1.0,
    $max = -1.0,
    $min = -1.0,
    $status = '',
    $time = 0,
    $suspend = '',
    $location = '',
    $interactions = [],
    $core_exit = 'none',
    $sessionId = null,
    $courseId = null,
    $lmsFinish = 0,
    $userNavigatesAway = 0,
    $statusSignalReceived = 0,
    $forceIframeSave = 0
) {
    $debug = 0;
    $return = null;
    $courseCode = api_get_course_id();
    if (!empty($courseId)) {
        $courseInfo = api_get_course_info_by_id($courseId);
        if ($courseInfo) {
            $courseCode = $courseInfo['code'];
        }
    }

    if ($debug > 0) {
        error_log('--------------------------------------');
        error_log('SAVE ITEM - lp_ajax_save_item.php');
        error_log('--------------------------------------');
        error_log("item_id: $item_id - lp_id: $lp_id - user_id: - $user_id - view_id: $view_id - item_id: $item_id");
        error_log("SCORE: $score - max:$max - min: $min - status:$status");
        error_log("TIME: $time - suspend: $suspend - location: $location - core_exit: $core_exit");
        error_log("finish: $lmsFinish - navigatesAway: $userNavigatesAway");
        error_log("courseCode: $courseCode");
    }

    $myLP = learnpath::getLpFromSession($courseCode, $lp_id, $user_id);

    if (!is_a($myLP, 'learnpath')) {
        if ($debug) {
            error_log('mylp variable is not an learnpath object');
        }

        return null;
    }
    $prerequisitesCheck = $myLP->prerequisites_match($item_id);

    /** @var learnpathItem $myLPI */
    if ($myLP->items && isset($myLP->items[$item_id])) {
        $myLPI = $myLP->items[$item_id];
    }

    if (empty($myLPI)) {
        if ($debug > 0) {
            error_log("item #$item_id not found in the items array: ".print_r($myLP->items, 1));
        }

        return null;
    }

    // This functions sets the $this->db_item_view_id variable needed in get_status() see BT#5069
    $myLPI->set_lp_view($view_id);
    $my_type = $myLPI->get_type();

    $saveStatus = true;
    if ('document' === $my_type) {
        $saveStatus = learnpathItem::isLpItemAutoComplete($myLPI->getIid());
        if ($forceIframeSave) {
            $saveStatus = true;
        }
    }

    // Launch the prerequisites check and set error if needed
    if (true !== $prerequisitesCheck) {
        // If prerequisites were not matched, don't update any item info
        if ($debug) {
            error_log("prereq_check failed: ".intval($prerequisitesCheck));
        }

        return null;
    } else {
        if ($debug > 1) {
            error_log('Prerequisites are OK');
        }

        $logInfo = [
            'tool' => TOOL_LEARNPATH,
            'tool_id' => $lp_id,
            'tool_id_detail' => $item_id,
            'action' => 'view',
            'action_details' => $myLP->getCurrentAttempt(),
        ];
        Event::registerLog($logInfo);

        /*$logInfo = [
            'tool' => TOOL_LEARNPATH,
            'tool_id' => $lp_id,
            'tool_id_detail' => $item_id,
            'action' => 'set_status_score',
            'action_details' => $status.':'.$score,
        ];
        Event::registerLog($logInfo);*/

        if (isset($max) && $max != -1) {
            $myLPI->max_score = $max;
            $myLPI->set_max_score($max);
            if ($debug > 1) {
                error_log("Setting max_score: $max");
            }
        }

        if (isset($min) && $min != -1 && $min != 'undefined') {
            $myLPI->min_score = $min;
            if ($debug > 1) {
                error_log("Setting min_score: $min");
            }
        }

        // set_score function used to save the status, but this is not the case anymore
        if (isset($score) && $score != -1) {
            if ($debug > 1) {
                error_log('Calling set_score('.$score.')');
                error_log('set_score changes the status to failed/passed if mastery score is provided');
            }
            $myLPI->set_score($score);
            if ($debug > 1) {
                error_log('Done calling set_score '.$myLPI->get_score());
            }
        } else {
            if ($debug > 1) {
                error_log('Score not updated');
            }
        }

        $statusIsSet = false;
        if ($saveStatus) {
            // Default behaviour.
            if (isset($status) && $status != '' && $status != 'undefined') {
                if ($debug > 1) {
                    error_log('Calling set_status('.$status.')');
                }

                $myLPI->set_status($status);
                $statusIsSet = true;
                if ($debug > 1) {
                    error_log('Done calling set_status: checking from memory: '.$myLPI->get_status(false));
                }
            } else {
                if ($debug > 1) {
                    error_log('Status not updated');
                }
            }
        } else {
            if ($debug > 1) {
                error_log('Status not updated');
            }
        }

        // Set status to completed for hotpotatoes if score > 80%.
        if ($my_type === 'hotpotatoes') {
            if ((empty($status) || $status == 'undefined' || $status == 'not attempted') && $max > 0) {
                if (($score / $max) > 0.8) {
                    $myStatus = 'completed';
                    if ($debug > 1) {
                        error_log('Calling set_status('.$myStatus.') for hotpotatoes');
                    }
                    $myLPI->set_status($myStatus);
                    $statusIsSet = true;
                    if ($debug > 1) {
                        error_log('Done calling set_status for hotpotatoes - now '.$myLPI->get_status(false));
                    }
                }
            } elseif ($status == 'completed' && $max > 0 && ($score / $max) < 0.8) {
                $myStatus = 'failed';
                if ($debug > 1) {
                    error_log('Calling set_status('.$myStatus.') for hotpotatoes');
                }
                $myLPI->set_status($myStatus);
                $statusIsSet = true;
                if ($debug > 1) {
                    error_log('Done calling set_status for hotpotatoes - now '.$myLPI->get_status(false));
                }
            }
        } elseif ($my_type === 'sco') {
            /*
             * This is a specific implementation for SCORM 1.2, matching page 26 of SCORM 1.2's RTE
             * "Normally the SCO determines its own status and passes it to the LMS.
             * 1) If cmi.core.credit is set to "credit" and there is a mastery
             *    score in the manifest (adlcp:masteryscore), the LMS can change
             *    the status to either passed or failed depending on the
             *    student's score compared to the mastery score.
             * 2) If there is no mastery score in the manifest
             *    (adlcp:masteryscore), the LMS cannot override SCO
             *    determined status.
             * 3) If the student is taking the SCO for no-credit, there is no
             *    change to the lesson_status, with one exception.  If the
             *    lesson_mode is "browse", the lesson_status may change to
             *    "browsed" even if the cmi.core.credit is set to no-credit.
             * "
             * Additionally, the LMS behaviour should be:
             * If a SCO sets the cmi.core.lesson_status then there is no problem.
             * However, the SCORM does not force the SCO to set the cmi.core.lesson_status.
             * There is some additional requirements that must be adhered to
             * successfully handle these cases:
             * Upon initial launch
             *   the LMS should set the cmi.core.lesson_status to "not attempted".
             * Upon receiving the LMSFinish() call or the user navigates away,
             *   the LMS should set the cmi.core.lesson_status for the SCO to "completed".
             * After setting the cmi.core.lesson_status to "completed",
             *   the LMS should now check to see if a Mastery Score has been
             *   specified in the cmi.student_data.mastery_score, if supported,
             *   or the manifest that the SCO is a member of.
             *   If a Mastery Score is provided and the SCO did set the
             *   cmi.core.score.raw, the LMS shall compare the cmi.core.score.raw
             *   to the Mastery Score and set the cmi.core.lesson_status to
             *   either "passed" or "failed".  If no Mastery Score is provided,
             *   the LMS will leave the cmi.core.lesson_status as "completed"
             */
            $masteryScore = $myLPI->get_mastery_score();
            if ($masteryScore == -1 || empty($masteryScore)) {
                $masteryScore = false;
            }
            $credit = $myLPI->get_credit();

            /**
             * 1) If cmi.core.credit is set to "credit" and there is a mastery
             *    score in the manifest (adlcp:masteryscore), the LMS can change
             *    the status to either passed or failed depending on the
             *    student's score compared to the mastery score.
             */
            if ($credit === 'credit' &&
                $masteryScore &&
                (isset($score) && $score != -1) &&
                !$statusIsSet && !$statusSignalReceived
            ) {
                if ($score >= $masteryScore) {
                    $myLPI->set_status('passed');
                    if ($debug) {
                        error_log('Set status: passed');
                    }
                } else {
                    $myLPI->set_status('failed');
                    if ($debug) {
                        error_log('Set status: failed');
                    }
                }
                $statusIsSet = true;
            }

            /**
             *  2) If there is no mastery score in the manifest
             *    (adlcp:masteryscore), the LMS cannot override SCO
             *    determined status.
             */
            if (!$statusIsSet && !$masteryScore && !$statusSignalReceived) {
                if (!empty($status)) {
                    if ($debug) {
                        error_log("Set status: $status because: statusSignalReceived ");
                    }
                    $myLPI->set_status($status);
                    $statusIsSet = true;
                }
                //if no status was set directly, we keep the previous one
            }

            /**
             * 3) If the student is taking the SCO for no-credit, there is no
             *    change to the lesson_status, with one exception. If the
             *    lesson_mode is "browse", the lesson_status may change to
             *    "browsed" even if the cmi.core.credit is set to no-credit.
             */
            if (!$statusIsSet && $credit == 'no-credit' && !$statusSignalReceived) {
                $mode = $myLPI->get_lesson_mode();
                if ($mode == 'browse' && $status == 'browsed') {
                    if ($debug) {
                        error_log("Set status: $status because mode browse");
                    }
                    $myLPI->set_status($status);
                    $statusIsSet = true;
                }
                //if no status was set directly, we keep the previous one
            }

            /**
             * If a SCO sets the cmi.core.lesson_status then there is no problem.
             * However, the SCORM does not force the SCO to set the
             * cmi.core.lesson_status.  There is some additional requirements
             * that must be adhered to successfully handle these cases:.
             */
            $LMSUpdateStatus = true;
            if (!api_get_configuration_value('scorm_lms_update_status_all_time') && $myLPI->get_status() !== "not attempted") {
                $LMSUpdateStatus = false;
            }
            if (!$statusIsSet && empty($status) && !$statusSignalReceived && $LMSUpdateStatus) {
                /**
                 * Upon initial launch the LMS should set the
                 * cmi.core.lesson_status to "not attempted".
                 */
                // this case should be handled by LMSInitialize() and xajax_switch_item()
                /**
                 * Upon receiving the LMSFinish() call or the user navigates
                 * away, the LMS should set the cmi.core.lesson_status for the
                 * SCO to "completed".
                 */
                if ($lmsFinish || $userNavigatesAway) {
                    $myStatus = 'completed';
                    $updateStatus = true;
                    // Do not update status if "score as progress" and $userNavigatesAway
                    // The progress will be saved by the scorm BT#16766.
                    if ($userNavigatesAway && !$lmsFinish && $myLP->getUseScoreAsProgress()) {
                        $updateStatus = false;
                    }

                    if ($updateStatus) {
                        /**
                         * After setting the cmi.core.lesson_status to "completed",
                         *   the LMS should now check to see if a Mastery Score has been
                         *   specified in the cmi.student_data.mastery_score, if supported,
                         *   or the manifest that the SCO is a member of.
                         *   If a Mastery Score is provided and the SCO did set the
                         *   cmi.core.score.raw, the LMS shall compare the cmi.core.score.raw
                         *   to the Mastery Score and set the cmi.core.lesson_status to
                         *   either "passed" or "failed".  If no Mastery Score is provided,
                         *   the LMS will leave the cmi.core.lesson_status as "completed”.
                         */
                        if ($masteryScore && (isset($score) && $score != -1)) {
                            if ($score >= $masteryScore) {
                                $myStatus = 'passed';
                            } else {
                                $myStatus = 'failed';
                            }
                        }
                        if ($debug) {
                            error_log("Set status: $myStatus because lmsFinish || userNavigatesAway");
                        }
                        $myLPI->set_status($myStatus);
                        $statusIsSet = true;
                    }
                }
            }
            // End of type=='sco'
        }

        // If no previous condition changed the SCO status, proceed with a generic behaviour
        if ($saveStatus) {
            if (!$statusIsSet && !$statusSignalReceived) {
                // Default behaviour
                if (isset($status) && $status != '' && $status != 'undefined') {
                    if ($debug > 1) {
                        error_log('Calling set_status('.$status.')');
                    }

                    $myLPI->set_status($status);

                    if ($debug > 1) {
                        error_log('Done calling set_status: checking from memory: '.$myLPI->get_status(false));
                    }
                } else {
                    if ($debug > 1) {
                        error_log("Status not updated");
                    }
                }
            }
        } else {
            if ($debug > 1) {
                error_log("Status not updated");
            }
        }

        if (isset($time) && $time != '' && $time != 'undefined') {
            // If big integer, then it's a timestamp, otherwise it's normal scorm time.
            if ($debug > 1) {
                error_log('Calling set_time('.$time.') ');
            }
            if ($time == intval(strval($time)) && $time > 1000000) {
                if ($debug > 1) {
                    error_log("Time is INT");
                }
                $real_time = time() - $time;
                if ($debug > 1) {
                    error_log('Calling $real_time '.$real_time.' ');
                }
                $myLPI->set_time($real_time, 'int');
            } else {
                if ($debug > 1) {
                    error_log("Time is in SCORM format");
                }
                if ($debug > 1) {
                    error_log('Calling $time '.$time.' ');
                }
                $myLPI->set_time($time, 'scorm');
            }
        } else {
            $myLPI->current_stop_time = time();
        }

        if (isset($suspend) && $suspend != '' && $suspend != 'undefined') {
            $myLPI->current_data = $suspend;
        }

        if (isset($location) && $location != '' && $location != 'undefined') {
            $myLPI->set_lesson_location($location);
        }

        // Deal with interactions provided in arrays in the following format:
        // id(0), type(1), time(2), weighting(3), correct_responses(4), student_response(5), result(6), latency(7)
        if (is_array($interactions) && count($interactions) > 0) {
            foreach ($interactions as $index => $interaction) {
                //$mylpi->add_interaction($index,$interactions[$index]);
                //fix DT#4444
                $clean_interaction = str_replace('@.|@', ',', $interactions[$index]);
                $myLPI->add_interaction($index, $clean_interaction);
            }
        }

        if ($core_exit != 'undefined') {
            $myLPI->set_core_exit($core_exit);
        }

        if ($saveStatus) {
            $myLP->save_item($item_id, false);
        }
    }

    $myStatusInDB = $myLPI->get_status(true);
    if ($debug) {
        error_log("Status in DB: $myStatusInDB");
    }

    if ($myStatusInDB != 'completed' &&
        $myStatusInDB != 'passed' &&
        $myStatusInDB != 'browsed' &&
        $myStatusInDB != 'failed'
    ) {
        $myStatusInMemory = $myLPI->get_status(false);
        if ($debug) {
            error_log("myStatusInMemory: $myStatusInMemory");
        }

        if ($myStatusInMemory != $myStatusInDB) {
            $myStatus = $myStatusInMemory;
        } else {
            $myStatus = $myStatusInDB;
        }
    } else {
        $myStatus = $myStatusInDB;
    }

    $myTotal = $myLP->getTotalItemsCountWithoutDirs();
    $myComplete = $myLP->get_complete_items_count();
    $myProgressMode = $myLP->get_progress_bar_mode();
    $myProgressMode = $myProgressMode == '' ? '%' : $myProgressMode;

    if ($debug > 1) {
        error_log("mystatus: $myStatus");
        error_log("myprogress_mode: $myProgressMode");
        error_log("progress: $myComplete / $myTotal");
    }

    if ($saveStatus) {
        if ($myLPI->get_type() !== 'sco') {
            // If this object's JS status has not been updated by the SCORM API, update now.
            $return .= "olms.lesson_status='".$myStatus."';";
        }
        $return .= "update_toc('".$myStatus."','".$item_id."');";
        $update_list = $myLP->get_update_queue();
        foreach ($update_list as $my_upd_id => $my_upd_status) {
            if ($my_upd_id != $item_id) {
                /* Only update the status from other items (i.e. parents and brothers),
                do not update current as we just did it already. */
                $return .= "update_toc('".$my_upd_status."','".$my_upd_id."');";
            }
        }

        $progressBarSpecial = false;
        $scoreAsProgressSetting = api_get_configuration_value('lp_score_as_progress_enable');
        if ($scoreAsProgressSetting === true) {
            $scoreAsProgress = $myLP->getUseScoreAsProgress();
            if ($scoreAsProgress) {
                // Only update score if it was set by scorm.
                if (isset($score) && $score != -1) {
                    $score = $myLPI->get_score();
                    $maxScore = $myLPI->get_max();
                    $return .= "update_progress_bar('$score', '$maxScore', '$myProgressMode');";
                }
                $progressBarSpecial = true;
            }
        }

        if (!$progressBarSpecial) {
            $return .= "update_progress_bar('$myComplete', '$myTotal', '$myProgressMode');";
        }

        if (isset($myLP->lti_launch_id)) {
            $ltiLaunchId = $myLP->lti_launch_id;
            $return .= "sendLtiLaunch('$ltiLaunchId', '$lp_id');";
        }
    }

    if (!Session::read('login_as')) {
        // If $_SESSION['login_as'] is set, then the user is an admin logged as the user.
        $tbl_track_login = Database::get_main_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        $sql = "SELECT login_id, login_date
                FROM $tbl_track_login
                WHERE login_user_id= ".api_get_user_id()."
                ORDER BY login_date DESC
                LIMIT 0,1";

        $q_last_connection = Database::query($sql);
        if (Database::num_rows($q_last_connection) > 0) {
            $current_time = api_get_utc_datetime();
            $row = Database::fetch_array($q_last_connection);
            $i_id_last_connection = $row['login_id'];
            $sql = "UPDATE $tbl_track_login
                    SET logout_date='".$current_time."'
                    WHERE login_id = $i_id_last_connection";
            Database::query($sql);
        }
    }

    if ($myLP->get_type() == 2) {
        $return .= 'update_stats();';
    }

    if ($saveStatus) {
        // To be sure progress is updated.
        $myLP->save_last($score);
        HookLearningPathItemViewed::create()
            ->setEventData(['item_view_id' => $myLPI->db_item_view_id])
            ->notifyLearningPathItemViewed();

        Session::write('lpobject', serialize($myLP));
        Session::write('oLP', $myLP);
    }

    if ($debug > 0) {
        error_log("lp_view_session_id :".$myLP->lp_view_session_id);
        error_log('---------------- lp_ajax_save_item.php : save_item end ----- ');
    }

    $logInfo = [
        'tool' => TOOL_LEARNPATH,
        'tool_id' => $myLP->get_id(),
        'action_details' => $myLP->getCurrentAttempt(),
        'tool_id_detail' => $myLP->get_current_item_id(),
        'action' => 'save_item',
    ];
    Event::registerLog($logInfo);

    return $return;
}

$interactions = [];
if (isset($_REQUEST['interact'])) {
    if (is_array($_REQUEST['interact'])) {
        foreach ($_REQUEST['interact'] as $idx => $interac) {
            $interactions[$idx] = preg_split('/,/', substr($interac, 1, -1));
            if (!isset($interactions[$idx][7])) { // Make sure there are 7 elements.
                $interactions[$idx][7] = '';
            }
        }
    }
}

echo save_item(
    (!empty($_REQUEST['lid']) ? $_REQUEST['lid'] : null),
    (!empty($_REQUEST['uid']) ? $_REQUEST['uid'] : null),
    (!empty($_REQUEST['vid']) ? $_REQUEST['vid'] : null),
    (!empty($_REQUEST['iid']) ? $_REQUEST['iid'] : null),
    (!empty($_REQUEST['s']) ? $_REQUEST['s'] : null),
    (!empty($_REQUEST['max']) ? $_REQUEST['max'] : null),
    (!empty($_REQUEST['min']) ? $_REQUEST['min'] : null),
    (!empty($_REQUEST['status']) ? $_REQUEST['status'] : null),
    (!empty($_REQUEST['t']) ? $_REQUEST['t'] : null),
    (!empty($_REQUEST['suspend']) ? $_REQUEST['suspend'] : null),
    (!empty($_REQUEST['loc']) ? $_REQUEST['loc'] : null),
    $interactions,
    (!empty($_REQUEST['core_exit']) ? $_REQUEST['core_exit'] : ''),
    (!empty($_REQUEST['session_id']) ? $_REQUEST['session_id'] : ''),
    (!empty($_REQUEST['course_id']) ? $_REQUEST['course_id'] : ''),
    (empty($_REQUEST['finish']) ? 0 : 1),
    (empty($_REQUEST['userNavigatesAway']) ? 0 : 1),
    (empty($_REQUEST['statusSignalReceived']) ? 0 : 1),
    isset($_REQUEST['forceIframeSave']) ? (int) $_REQUEST['forceIframeSave'] : 0
);
