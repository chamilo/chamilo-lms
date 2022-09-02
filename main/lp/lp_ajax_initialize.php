<?php
/* For licensing terms, see /license.txt */

/**
 * This script contains the server part of the xajax interaction process.
 * This script, in particular, enables the process of SCO's initialization. It
 * resets the JavaScript values for each SCO to the current LMS status.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script();

/**
 * Get one item's details.
 *
 * @param   int LP ID
 * @param   int user ID
 * @param   int View ID
 * @param   int Current item ID
 * @param   int New item ID
 *
 * @return string
 */
function initialize_item($lp_id, $user_id, $view_id, $next_item)
{
    $debug = 0;
    $return = '';
    if ($debug) {
        error_log('In initialize_item('.$lp_id.','.$user_id.','.$view_id.','.$next_item.')');
    }
    /*$item_id may be one of:
     * -'next'
     * -'previous'
     * -'first'
     * -'last'
     * - a real item ID
     */
    $mylp = learnpath::getLpFromSession(api_get_course_id(), $lp_id, $user_id);
    $mylp->set_current_item($next_item);
    if ($debug) {
        error_log('In initialize_item() - new item is '.$next_item);
    }

    $mylp->start_current_item(true);

    if (is_object($mylp->items[$next_item])) {
        if ($debug) {
            error_log('In initialize_item - recovering existing item object '.$next_item, 0);
        }
        $mylpi = $mylp->items[$next_item];
    } else {
        if ($debug) {
            error_log('In initialize_item - generating new item object '.$next_item, 0);
        }
        $mylpi = new learnpathItem($next_item, $user_id);
    }

    if ($mylpi) {
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
    $mytotal_time = $mylpi->get_scorm_time('js', null, true);
    $mymastery_score = $mylpi->get_mastery_score();
    $mymax_time_allowed = $mylpi->get_max_time_allowed();
    $mylaunch_data = $mylpi->get_launch_data();
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

    // Obtention des donnees d'objectifs
    $mycoursedb = Database::get_course_table(TABLE_LP_IV_OBJECTIVE);
    $course_id = api_get_course_int_id();
    $mylp_iv_id = $mylpi->db_item_view_id;
    $phpobjectives = [];
    if (!empty($mylp_iv_id)) {
        $sql = "SELECT objective_id, status, score_raw, score_max, score_min
                FROM $mycoursedb
                WHERE lp_iv_id = $mylp_iv_id AND c_id = $course_id
                ORDER BY id ASC;";
        $res = Database::query($sql);
        while ($row = Database::fetch_row($res)) {
            $phpobjectives[] = $row;
        }
    }
    $myobjectives = json_encode($phpobjectives);
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
            //"olms.item_objectives = new Array();" .
            "olms.item_objectives = ".$myobjectives.";".
            "olms.G_lastError = 0;".
            "olms.G_LastErrorMessage = 'No error';".
            "olms.finishSignalReceived = 0;";
    /*
     * and re-initialise the rest (proper to the LMS)
     * -lms_lp_id
     * -lms_item_id
     * -lms_old_item_id
     * -lms_new_item_id
     * -lms_initialized
     * -lms_progress_bar_mode
     * -lms_view_id
     * -lms_user_id
     */
    $mynext = $mylp->get_next_item_id();
    $myprevious = $mylp->get_previous_item_id();
    $myitemtype = $mylpi->get_type();
    $mylesson_mode = $mylpi->get_lesson_mode();
    $mycredit = $mylpi->get_credit();
    $mylaunch_data = $mylpi->get_launch_data();
    $myinteractions_count = $mylpi->get_interactions_count();
    $mycore_exit = $mylpi->get_core_exit();
    $return .=
            "olms.lms_lp_id=".$lp_id.";".
            "olms.lms_item_id=".$next_item.";".
            "olms.lms_old_item_id=0;".
            "olms.lms_initialized=0;".
            "olms.lms_view_id=".$view_id.";".
            "olms.lms_user_id=".$user_id.";".
            "olms.next_item=".$next_item.";".// This one is very important to replace possible literal strings.
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

    $mylp->set_error_msg('');
    $mylp->prerequisites_match(); // Check the prerequisites are all complete.
    if ($debug) {
        error_log('Prereq_match() returned '.htmlentities($mylp->error), 0);
        error_log("return = $return ");
        error_log("mylp->lp_view_session_id: ".$mylp->lp_view_session_id);
    }

    if (isset($mylp->lti_launch_id)) {
        $ltiLaunchId = $mylp->lti_launch_id;
        $return .= "sendLtiLaunch('$ltiLaunchId', '$lp_id');";
    }

    return $return;
}

echo initialize_item(
    $_POST['lid'],
    $_POST['uid'],
    $_POST['vid'],
    $_POST['iid']
);
