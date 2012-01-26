<?php
/* For licensing terms, see /license.txt */

/**
 * This script contains the server part of the xajax interaction process. The client part is located
 * in lp_api.php or other api's.
 * This script, in particular, enables the process of SCO's initialization. It
 * resets the JavaScript values for each SCO to the current LMS status.
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Code
 */
// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

// Name of the language file that needs to be included.
$language_file[] = 'learnpath';

require_once 'back_compat.inc.php';

/**
 * Get one item's details
 * @param   integer LP ID
 * @param   integer user ID
 * @param   integer View ID
 * @param   integer Current item ID
 * @param   integer New item ID
 */
function initialize_item($lp_id, $user_id, $view_id, $next_item) {
    $debug = 0;
    $return = '';
    if ($debug > 0) { error_log('In initialize_item('.$lp_id.','.$user_id.','.$view_id.','.$next_item.')', 0); }
    //$objResponse = new xajaxResponse();
    /*$item_id may be one of:
     * -'next'
     * -'previous'
     * -'first'
     * -'last'
     * - a real item ID
     */
    require_once 'learnpath.class.php';
    require_once 'scorm.class.php';
    require_once 'aicc.class.php';
    require_once 'learnpathItem.class.php';
    require_once 'scormItem.class.php';
    require_once 'aiccItem.class.php';
    $mylp = '';
    if (isset($_SESSION['lpobject'])) {
        if ($debug > 1) { error_log('////$_SESSION[lpobject] is set', 0); }
        $oLP =& unserialize($_SESSION['lpobject']);
        if (!is_object($oLP)) {
            if ($debug > 1) { error_log(print_r($oLP,true), 0); }
            if ($debug > 2) { error_log('////Building new lp', 0); }
            unset($oLP);
            $code = api_get_course_id();
            $mylp = new learnpath($code,$lp_id,$user_id);
        } else {
            if ($debug > 1) { error_log('////Reusing session lp', 0); }
            $mylp = & $oLP;
        }
    }
    $mylp->set_current_item($next_item);
    if ($debug > 1) { error_log('In initialize_item() - new item is '.$next_item, 0); }
    $mylp->start_current_item(true);
    /*
    if ($mylp->force_commit) {
        $mylp->save_current();
    }
    */
    //$objResponse->addAlert(api_get_path(REL_CODE_PATH).'newscorm/learnpathItem.class.php');
    if (is_object($mylp->items[$next_item])) {
        if ($debug > 1) { error_log('In initialize_item - recovering existing item object '.$next_item, 0); }
        $mylpi = & $mylp->items[$next_item];
    } else {
        if ($debug > 1) { error_log('In initialize_item - generating new item object '.$next_item, 0); }
        $mylpi =& new learnpathItem($next_item, $user_id);
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
    if ($mymax === '') { $mymax = "''"; }
    $mymin = $mylpi->get_min();
    $mylesson_status = $mylpi->get_status();
    $mylesson_location = $mylpi->get_lesson_location();
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
	$phpobjectives = array();
	$mycoursedb = Database::get_course_table(TABLE_LP_IV_OBJECTIVE);
	$mylp_iv_id = $mylpi->db_item_view_id;
	$sql = "SELECT objective_id, status, score_raw, score_max, score_min
		FROM ".$mycoursedb."
		WHERE lp_iv_id = ".$mylp_iv_id."
		ORDER BY id ASC;";
	$res = mysql_query($sql);
	while ($row = mysql_fetch_row($res)) {
		$phpobjectives[] = $row;	
	}
	$myobjectives = json_encode($phpobjectives);
    $return .=
            "olms.score=".$myscore.";" .
            "olms.max=".$mymax.";" .
            "olms.min=".$mymin.";" .
            "olms.lesson_status='".$mylesson_status."';" .
            "olms.lesson_location='".$mylesson_location."';" .
            "olms.session_time='".$mysession_time."';" .
            "olms.suspend_data='".$mysuspend_data."';" .
            "olms.total_time = '".$mytotal_time."';" .
            "olms.mastery_score = '".$mymastery_score."';" .
            "olms.max_time_allowed = '".$mymax_time_allowed."';" .
            "olms.launch_data = '".$mylaunch_data."';" .
            "olms.interactions = new Array(".$myistring.");" .
            //"olms.item_objectives = new Array();" .
            "olms.item_objectives = ".$myobjectives.";" .
	    "olms.G_lastError = 0;" .
            "olms.G_LastErrorMessage = 'No error';" ;
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
    $mytotal = $mylp->get_total_items_count_without_chapters();
    $mycomplete = $mylp->get_complete_items_count();
    $myprogress_mode = $mylp->get_progress_bar_mode();
    $myprogress_mode = ($myprogress_mode == '' ? '%' : $myprogress_mode);
    $mynext = $mylp->get_next_item_id();
    $myprevious = $mylp->get_previous_item_id();
    $myitemtype = $mylpi->get_type();
    $mylesson_mode = $mylpi->get_lesson_mode();
    $mycredit = $mylpi->get_credit();
    $mylaunch_data = $mylpi->get_launch_data();
    $myinteractions_count = $mylpi->get_interactions_count();
    $myobjectives_count = $mylpi->get_objectives_count();
    $mycore_exit = $mylpi->get_core_exit();

    $return .=
            "olms.lms_lp_id=".$lp_id.";" .
            "olms.lms_item_id=".$next_item.";" .
            "olms.lms_old_item_id=0;" .
            "olms.lms_initialized=0;" .
            "olms.lms_view_id=".$view_id.";" .
            "olms.lms_user_id=".$user_id.";" .
            "olms.next_item=".$next_item.";" . // This one is very important to replace possible literal strings.
            "olms.lms_next_item=".$mynext.";" .
            "olms.lms_previous_item=".$myprevious.";" .
            "olms.lms_item_type = '".$myitemtype."';" .
            "olms.lms_item_credit = '".$mycredit."';" .
            "olms.lms_item_lesson_mode = '".$mylesson_mode."';" .
            "olms.lms_item_launch_data = '".$mylaunch_data."';" .
            "olms.lms_item_interactions_count = '".$myinteractions_count."';" .
            "olms.lms_item_objectives_count = '".$myinteractions_count."';" .
            "olms.lms_item_core_exit = '".$mycore_exit."';" .
            "olms.asset_timer = 0;";

    $mylp->set_error_msg('');
    $mylp->prerequisites_match(); // Check the prerequisites are all complete.
    if ($debug > 1) { error_log('Prereq_match() returned '.htmlentities($mylp->error), 0); }
    //$_SESSION['scorm_item_id'] = $new_item_id; // Save the new item ID for the exercise tool to use/
    //$_SESSION['lpobject'] = serialize($mylp);
    return $return;
}

echo initialize_item($_POST['lid'], $_POST['uid'], $_POST['vid'], $_POST['iid']);
