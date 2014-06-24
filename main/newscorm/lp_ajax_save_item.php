<?php
/* For licensing terms, see /license.txt */

/**
 * This script contains the server part of the xajax interaction process. The client part is located
 * in lp_api.php or other api's.
 * This is a first attempt at using xajax and AJAX in general, so the code might be a bit unsettling.
 * @package chamilo.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Code
 */

use \ChamiloSession as Session;

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

// Name of the language file that needs to be included.
$language_file[] = 'learnpath';

require_once 'back_compat.inc.php';
require_once 'learnpath.class.php';
require_once 'scorm.class.php';
require_once 'aicc.class.php';
require_once 'learnpathItem.class.php';
require_once 'scormItem.class.php';
require_once 'aiccItem.class.php';

/**
 * Writes an item's new values into the database and returns the operation result
 * @param   integer Learnpath ID
 * @param   integer User ID
 * @param   integer View ID
 * @param   integer Item ID
 * @param   double  Current score
 * @param   double  Maximum score
 * @param   double  Minimum score
 * @param   string  Lesson status
 * @param   string  Session time
 * @param   string  Suspend data
 * @param   string  Lesson location
 * @param   array   Interactions array
 * @param   string  Core exit SCORM string
 */
function save_item($lp_id, $user_id, $view_id, $item_id, $score = -1, $max = -1, $min = -1, $status = '', $time = 0, $suspend = '', $location = '', $interactions = array(), $core_exit = 'none', $sessionId = null, $courseId = null)
{
    global $debug;
    $return = null;

    if ($debug > 0) {
        error_log('lp_ajax_save_item.php : save_item() params: ');
        error_log("item_id: $item_id");
        error_log("lp_id: $lp_id - user_id: - $user_id - view_id: $view_id - item_id: $item_id");
        error_log("score: $score - max:$max - min: $min - status:$status - time:$time - suspend: $suspend - location: $location - core_exit: $core_exit");
    }

    $mylp = null;
    $lpobject = Session::read('lpobject');
    if (!is_object($lpobject) && isset($sessionId) && isset($courseId)) {
        $lpobject = new learnpathItem($lp_id, $user_id, $courseId);
    }
    if (isset($lpobject)) {
        if (is_object($lpobject)) {
            $mylp = $lpobject;
        } else {
            $oLP = unserialize($lpobject);
            if ($debug) error_log("lpobject was set");
            if (!is_object($oLP)) {
                unset($oLP);
                $code = api_get_course_id();
                $mylp = new learnpath($code, $lp_id, $user_id);
                if ($debug) error_log("Creating learnpath");
            } else {
                $mylp = $oLP;
                if ($debug) error_log("Loading learnpath from unserialize");
            }
        }
    } else {
        if ($debug) {
            error_log("lpobject was not set");
        }
    }

    if (!is_a($mylp, 'learnpath')) {
        if ($debug) {
            error_log("mylp variable is not an learnpath object");
        }
        return null;
    }

    $prereq_check = $mylp->prerequisites_match($item_id);

    /** @var learnpathItem $mylpi */
    $mylpi = $mylp->items[$item_id];

    if (empty($mylpi)) {
        if ($debug > 0) {
            error_log("item #$item_id not found in the items array: ".print_r($mylp->items, 1));
        }
        return false;
    }

    // This functions sets the $this->db_item_view_id variable needed in get_status() see BT#5069
    $mylpi->set_lp_view($view_id);

    // Launch the prerequisites check and set error if needed
    if ($prereq_check !== true) {
        // If prerequisites were not matched, don't update any item info
        if ($debug) {
            error_log("prereq_check: ".intval($prereq_check));
        }

        return $return;
    } else {
        if ($debug > 1) { error_log('Prerequisites are OK'); }

        if (isset($max) && $max != -1) {
            $mylpi->max_score = $max;
            $mylpi->set_max_score($max);
            if ($debug > 1) { error_log("Setting max_score: $max"); }
        }

        if (isset($min) && $min != -1 && $min != 'undefined') {
            $mylpi->min_score = $min;
            if ($debug > 1) { error_log("Setting min_score: $min"); }
        }

        // set_score function already saves the status
        if (isset($score) && $score != -1) {
            if ($debug > 1) { error_log('Calling set_score('.$score.')', 0); }
            if ($debug > 1) { error_log('set_score changes the status to failed/passed if mastery score is provided', 0); }

            $mylpi->set_score($score);

            if ($debug > 1) { error_log('Done calling set_score '.$mylpi->get_score(), 0); }
        } else {
            if ($debug > 1) { error_log("Score not updated"); }

            // Default behaviour
            if (isset($status) && $status != '' && $status != 'undefined') {
                if ($debug > 1) { error_log('Calling set_status('.$status.')', 0); }

                $mylpi->set_status($status);

                if ($debug > 1) { error_log('Done calling set_status: checking from memory: '.$mylpi->get_status(false), 0); }
            } else {
                if ($debug > 1) { error_log("Status not updated"); }
            }
        }

        // Hack to set status to completed for hotpotatoes if score > 80%.
        $my_type = $mylpi->get_type();

        if ($my_type == 'hotpotatoes') {
            if ((empty($status) || $status == 'undefined' || $status == 'not attempted') && $max > 0) {
                if (($score/$max) > 0.8) {
                    $mystatus = 'completed';
                    if ($debug > 1) { error_log('Calling set_status('.$mystatus.') for hotpotatoes', 0); }
                    $mylpi->set_status($mystatus);
                    if ($debug > 1) { error_log('Done calling set_status for hotpotatoes - now '.$mylpi->get_status(false), 0); }
                }
            } elseif ($status == 'completed' && $max > 0 && ($score/$max) < 0.8) {
                $mystatus = 'failed';
                if ($debug > 1) { error_log('Calling set_status('.$mystatus.') for hotpotatoes', 0); }
                $mylpi->set_status($mystatus);
                if ($debug > 1) { error_log('Done calling set_status for hotpotatoes - now '.$mylpi->get_status(false), 0); }
            }
        }

        if (isset($time) && $time != '' && $time != 'undefined') {
            // If big integer, then it's a timestamp, otherwise it's normal scorm time.
            if ($debug > 1) { error_log('Calling set_time('.$time.') ', 0); }
            if ($time == intval(strval($time)) && $time > 1000000) {
                if ($debug > 1) { error_log("Time is INT"); }
                $real_time = time() - $time;
                if ($debug > 1) { error_log('Calling $real_time '.$real_time.' ', 0); }
                $mylpi->set_time($real_time, 'int');
            } else {
                if ($debug > 1) { error_log("Time is in SCORM format"); }
                if ($debug > 1) { error_log('Calling $time '.$time.' ', 0); }
                $mylpi->set_time($time, 'scorm');
            }
            //if ($debug > 1) { error_log('Done calling set_time - now '.$mylpi->get_total_time(), 0); }
        } else {
            $time = $mylpi->get_total_time();
        }

        if (isset($suspend) && $suspend != '' && $suspend != 'undefined') {
            $mylpi->current_data = $suspend; //escapetxt($suspend);
        }

        if (isset($location) && $location != '' && $location!='undefined') {
            $mylpi->set_lesson_location($location);
        }

        // Deal with interactions provided in arrays in the following format:
        // id(0), type(1), time(2), weighting(3), correct_responses(4), student_response(5), result(6), latency(7)
        if (is_array($interactions) && count($interactions) > 0) {
            foreach ($interactions as $index => $interaction) {
                //$mylpi->add_interaction($index,$interactions[$index]);
                //fix DT#4444
                $clean_interaction = str_replace('@.|@', ',', $interactions[$index]);
                $mylpi->add_interaction($index, $clean_interaction);
            }
        }

        if ($core_exit != 'undefined') {
            $mylpi->set_core_exit($core_exit);
        }
        $mylp->save_item($item_id, false);
    }

    $mystatus_in_db = $mylpi->get_status(true);
    if ($debug) error_log("Status in DB: $mystatus_in_db");

    if ($mystatus_in_db != 'completed' && $mystatus_in_db != 'passed' && $mystatus_in_db != 'browsed' && $mystatus_in_db != 'failed') {
         $mystatus_in_memory = $mylpi->get_status(false);
         if ($mystatus_in_memory != $mystatus_in_db) {
             $mystatus = $mystatus_in_memory;
         } else {
             $mystatus = $mystatus_in_db;
         }
    } else {
        $mystatus = $mystatus_in_db;
    }

    $mytotal         = $mylp->get_total_items_count_without_chapters();
    $mycomplete      = $mylp->get_complete_items_count();
    $myprogress_mode = $mylp->get_progress_bar_mode();
    $myprogress_mode = $myprogress_mode == '' ? '%' : $myprogress_mode;

    if ($debug > 1) { error_log("mystatus: $mystatus", 0); }
    if ($debug > 1) { error_log("myprogress_mode: $myprogress_mode", 0); }
    if ($debug > 1) { error_log("progress: $mycomplete / $mytotal", 0); }

    //$_SESSION['lpobject'] = serialize($mylp);

    if ($mylpi->get_type() != 'sco') {
        // If this object's JS status has not been updated by the SCORM API, update now.
        $return .= "olms.lesson_status='".$mystatus."';";
    }
    $return .= "update_toc('".$mystatus."','".$item_id."');";
    $update_list = $mylp->get_update_queue();

    foreach ($update_list as $my_upd_id => $my_upd_status)  {
        if ($my_upd_id != $item_id) { // Only update the status from other items (i.e. parents and brothers), do not update current as we just did it already.
            $return .= "update_toc('".$my_upd_status."','".$my_upd_id."');";
        }
    }
    $return .= "update_progress_bar('$mycomplete', '$mytotal', '$myprogress_mode');";

    if ($debug > 0) {
        $return .= "logit_lms('Saved data for item ".$item_id.", user ".$user_id." (status=".$mystatus.")',2);";
    }

    if (!isset($_SESSION['login_as'])) {
        // If $_SESSION['login_as'] is set, then the user is an admin logged as the user.
        $tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

        $sql_last_connection = "SELECT login_id, login_date
            FROM $tbl_track_login
            WHERE login_user_id='".api_get_user_id()."'
            ORDER BY login_date DESC LIMIT 0,1";

        $q_last_connection = Database::query($sql_last_connection);
        if (Database::num_rows($q_last_connection) > 0) {
            $current_time = api_get_utc_datetime();
            $row = Database::fetch_array($q_last_connection);
            $i_id_last_connection = $row['login_id'];
            $s_sql_update_logout_date = "UPDATE $tbl_track_login SET logout_date='".$current_time."' WHERE login_id='$i_id_last_connection'";
            Database::query($s_sql_update_logout_date);
        }
    }

    if ($mylp->get_type() == 2) {
         $return .= "update_stats();";
    }

    //To be sure progress is updated
    $mylp->save_last();

    Session::write('lpobject', serialize($mylp));
    if ($debug > 0) { error_log('---------------- lp_ajax_save_item.php : save_item end ----- '); }

    return $return;
}

$interactions = array();
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
    (!empty($_REQUEST['lid'])?$_REQUEST['lid']:null),
    (!empty($_REQUEST['uid'])?$_REQUEST['uid']:null),
    (!empty($_REQUEST['vid'])?$_REQUEST['vid']:null),
    (!empty($_REQUEST['iid'])?$_REQUEST['iid']:null),
    (!empty($_REQUEST['s'])?$_REQUEST['s']:null),
    (!empty($_REQUEST['max'])?$_REQUEST['max']:null),
    (!empty($_REQUEST['min'])?$_REQUEST['min']:null),
    (!empty($_REQUEST['status'])?$_REQUEST['status']:null),
    (!empty($_REQUEST['t'])?$_REQUEST['t']:null),
    (!empty($_REQUEST['suspend'])?$_REQUEST['suspend']:null),
    (!empty($_REQUEST['loc'])?$_REQUEST['loc']:null),
    $interactions,
    (!empty($_REQUEST['core_exit'])?$_REQUEST['core_exit']:''),
    '',
    (!empty($_REQUEST['session_id'])?$_REQUEST['session_id']:''),
    (!empty($_REQUEST['course_id'])?$_REQUEST['course_id']:'')
);
