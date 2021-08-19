<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * This script contains the server part of the xajax interaction process. The client part is located
 * in lp_api.php or other api's.
 * This is a first attempt at using xajax and AJAX in general, so the code might be a bit unsettling.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';

/**
 * Get one item's details.
 *
 * @param   int LP ID
 * @param   int user ID
 * @param   int View ID
 * @param   int Current item ID
 * @param   int New item ID
 */
function switch_item_details($lp_id, $user_id, $view_id, $current_item, $next_item)
{
    $debug = 0;
    $return = '';
    if ($debug > 0) {
        error_log('--------------------------------------');
        error_log('SWITCH');
        error_log('Params('.$lp_id.','.$user_id.','.$view_id.','.$current_item.','.$next_item.')');
    }
    //$objResponse = new xajaxResponse();
    /*$item_id may be one of:
     * -'next'
     * -'previous'
     * -'first'
     * -'last'
     * - a real item ID
     */
    $mylp = learnpath::getLpFromSession(api_get_course_id(), $lp_id, $user_id);
    $new_item_id = 0;
    $saveStatus = learnpathItem::isLpItemAutoComplete($current_item);

    switch ($next_item) {
        case 'next':
            $mylp->set_current_item($current_item);
            $mylp->next();
            $new_item_id = $mylp->get_current_item_id();
            if ($debug > 1) {
                error_log('In {next} - next item is '.$new_item_id.'(current: '.$current_item.')');
            }
            break;
        case 'previous':
            $mylp->set_current_item($current_item);
            $mylp->previous();
            $new_item_id = $mylp->get_current_item_id();
            if ($debug > 1) {
                error_log('In {previous} - next item is '.$new_item_id.'(current: '.$current_item.')');
            }
            break;
        case 'first':
            $mylp->set_current_item($current_item);
            $mylp->first();
            $new_item_id = $mylp->get_current_item_id();
            if ($debug > 1) {
                error_log('In {first} - next item is '.$new_item_id.'(current: '.$current_item.')');
            }
            break;
        case 'last':
            break;
        default:
            // Should be filtered to check it's not hacked.
            if ($next_item == $current_item) {
                // If we're opening the same item again.
                $mylp->items[$current_item]->restart();
            }
            $new_item_id = $next_item;
            $mylp->set_current_item($new_item_id);
            if ($debug > 1) {
                error_log('In {default} - next item is '.$new_item_id.'(current: '.$current_item.')');
            }
            break;
    }

    if (WhispeakAuthPlugin::isLpItemMarked($new_item_id)) {
        ChamiloSession::write(
            WhispeakAuthPlugin::SESSION_LP_ITEM,
            ['lp' => $lp_id, 'lp_item' => $new_item_id, 'src' => '']
        );
    }

    $mylp->start_current_item(true);

    if ($saveStatus) {
        if ($mylp->force_commit) {
            $mylp->save_current();
        }
    }

    if (is_object($mylp->items[$new_item_id])) {
        $mylpi = $mylp->items[$new_item_id];
    } else {
        if ($debug > 1) {
            error_log('In switch_item_details - generating new item object', 0);
        }
        $mylpi = new learnpathItem($new_item_id, $user_id);
        $mylpi->set_lp_view($view_id);
    }

    /*
     * now get what's needed by the SCORM API:
     * -score
     * -max
     * -min
     * -lesson_status
     * -session_time
     * -suspend_data
     */
    $myscore = $mylpi->get_score();
    $mymax = $mylpi->get_max();
    if ('' === $mymax) {
        $mymax = "''";
    }
    $mymin = $mylpi->get_min();
    $mylesson_status = $mylpi->get_status();
    $mylesson_location = $mylpi->get_lesson_location();
    $mytotal_time = $mylpi->get_scorm_time('js');
    $mymastery_score = $mylpi->get_mastery_score();
    $mymax_time_allowed = $mylpi->get_max_time_allowed();
    $mylaunch_data = $mylpi->get_launch_data();
    /*
    if ($mylpi->get_type() == 'asset') {
        // Temporary measure to save completion of an asset. Later on,
        // Chamilo should trigger something on unload, maybe...
        // (even though that would mean the last item cannot be completed)
        $mylesson_status = 'completed';
        $mylpi->set_status('completed');
        $mylpi->save();
    }
    */
    $mysession_time = $mylpi->get_total_time();
    $mysuspend_data = $mylpi->get_suspend_data();
    $mylesson_location = $mylpi->get_lesson_location();
    $myic = $mylpi->get_interactions_count();
    $myistring = '';
    for ($i = 0; $i < $myic; $i++) {
        $myistring .= ",[".$i.",'','','','','','','']";
    }
    if (!empty($myistring)) {
        $myistring = substr($myistring, 1);
    }
    /*
     * The following lines should reinitialize the values for the SCO
     * However, due to many complications, we are now relying more on the
     * LMSInitialize() call and its underlying lp_ajax_initialize.php call
     * so this code is technically deprecated (but the change of item_id should
     * remain). However, due to numerous technical issues with SCORM, we prefer
     * leaving it as a double-lock security. If removing, please test carefully
     * with both SCORM and proper learning path tracking.
     */
    $return .=
        "olms.score=".$myscore.";".
        "olms.max=".$mymax.";".
        "olms.min=".$mymin.";".
        "olms.lesson_status='".$mylesson_status."';".
        "olms.lesson_location='".$mylesson_location."';".
        "olms.session_time='".$mysession_time."';".
        "olms.suspend_data='".$mysuspend_data."';".
        "olms.total_time = '".$mytotal_time."';".
        "olms.mastery_score = '".$mymastery_score."';".
        "olms.max_time_allowed = '".$mymax_time_allowed."';".
        "olms.launch_data = '".$mylaunch_data."';".
        "olms.interactions = new Array(".$myistring.");".
        "olms.item_objectives = new Array();".
        "olms.G_lastError = 0;".
        "olms.G_LastErrorMessage = 'No error';".
        "olms.finishSignalReceived = 0;";
    /*
     * and re-initialise the rest
     * -lms_lp_id
     * -lms_item_id
     * -lms_old_item_id
     * -lms_new_item_id
     * -lms_initialized
     * -lms_progress_bar_mode
     * -lms_view_id
     * -lms_user_id
     */
    $mytotal = $mylp->getTotalItemsCountWithoutDirs();
    $mycomplete = $mylp->get_complete_items_count();
    $myprogress_mode = $mylp->get_progress_bar_mode();
    $myprogress_mode = ('' == $myprogress_mode ? '%' : $myprogress_mode);
    $mynext = $mylp->get_next_item_id();
    $myprevious = $mylp->get_previous_item_id();
    $myitemtype = $mylpi->get_type();
    $mylesson_mode = $mylpi->get_lesson_mode();
    $mycredit = $mylpi->get_credit();
    $mylaunch_data = $mylpi->get_launch_data();
    $myinteractions_count = $mylpi->get_interactions_count();
    //$myobjectives_count = $mylpi->get_objectives_count();
    $mycore_exit = $mylpi->get_core_exit();

    $return .=
        //"saved_lesson_status='not attempted';" .
        "olms.lms_lp_id=".$lp_id.";".
        "olms.lms_item_id=".$new_item_id.";".
        "olms.lms_old_item_id=0;".
        //"lms_been_synchronized=0;" .
        "olms.lms_initialized=0;".
        //"lms_total_lessons=".$mytotal.";" .
        //"lms_complete_lessons=".$mycomplete.";" .
        //"lms_progress_bar_mode='".$myprogress_mode."';" .
        "olms.lms_view_id=".$view_id.";".
        "olms.lms_user_id=".$user_id.";".
        "olms.next_item=".$new_item_id.";".// This one is very important to replace possible literal strings.
        "olms.lms_next_item=".$mynext.";".
        "olms.lms_previous_item=".$myprevious.";".
        "olms.lms_item_type = '".$myitemtype."';".
        "olms.lms_item_credit = '".$mycredit."';".
        "olms.lms_item_lesson_mode = '".$mylesson_mode."';".
        "olms.lms_item_launch_data = '".$mylaunch_data."';".
        "olms.lms_item_interactions_count = '".$myinteractions_count."';".
        "olms.lms_item_objectives_count = '".$myinteractions_count."';".
        "olms.lms_item_core_exit = '".$mycore_exit."';".
        "olms.asset_timer = 0;";

    $sessionId = api_get_session_id();
    $updateMinTime = '';
    if (Tracking::minimumTimeAvailable($sessionId, api_get_course_int_id())) {
        $timeLp = $mylp->getAccumulateWorkTime();
        $timeTotalCourse = $mylp->getAccumulateWorkTimeTotalCourse();
        // Minimum connection percentage
        $perc = 100;
        // Time from the course
        $tc = $timeTotalCourse;
        // Percentage of the learning paths
        $pl = 0;
        if (!empty($timeTotalCourse)) {
            $pl = $timeLp / $timeTotalCourse;
        }

        // Minimum time for each learning path
        $time_total = intval($pl * $tc * $perc / 100) * 60;
        $lpTimeList = Tracking::getCalculateTime($user_id, api_get_course_int_id(), $sessionId);
        $lpTime = isset($lpTimeList[TOOL_LEARNPATH][$lp_id]) ? $lpTimeList[TOOL_LEARNPATH][$lp_id] : 0;

        if ($lpTime >= $time_total) {
            $time_spent = $time_total;
        } else {
            $time_spent = $lpTime;
        }

        $hour = (intval($lpTime / 3600)) < 10 ? '0'.intval($lpTime / 3600) : intval($lpTime / 3600);
        $minute = date('i', $lpTime);
        $second = date('s', $lpTime);
        $updateMinTime = "update_time_bar('$time_spent','$time_total','%');".
                         "update_chronometer('$hour','$minute','$second');";
    }

    $return .=
        "update_toc('unhighlight','".$current_item."');".
        "update_toc('highlight','".$new_item_id."');".
        "update_toc('$mylesson_status','".$new_item_id."');".
        "update_progress_bar('$mycomplete','$mytotal','$myprogress_mode');".
        $updateMinTime
    ;
    $return .= 'updateGamificationValues(); ';
    $mylp->set_error_msg('');
    $mylp->prerequisites_match(); // Check the prerequisites are all complete.
    if ($debug > 1) {
        error_log($return);
        error_log('Prereq_match() returned '.htmlentities($mylp->error), 0);
    }
    // Save the new item ID for the exercise tool to use.
    Session::write('scorm_item_id', $new_item_id);
    Session::write('lpobject', serialize($mylp));
    Session::write('oLP', $mylp);

    return $return;
}

echo switch_item_details(
    $_REQUEST['lid'],
    $_REQUEST['uid'],
    $_REQUEST['vid'],
    $_REQUEST['iid'],
    $_REQUEST['next']
);
