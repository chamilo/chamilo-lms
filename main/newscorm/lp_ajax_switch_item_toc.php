<?php
/* For licensing terms, see /license.txt */
/**
 * This script contains the server part of the xajax interaction process. The client part is located
 * in lp_api.php or other api's.
 * This script updated the TOC of the SCORM without updating the SCO's attributes
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
function switch_item_toc($lp_id, $user_id, $view_id, $current_item, $next_item) {
    $debug = 0;
    $return = '';
    if ($debug > 0) { error_log('In xajax_switch_item_toc('.$lp_id.','.$user_id.','.$view_id.','.$current_item.','.$next_item.')', 0); }
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
            if ($debug > 1) { error_log(print_r($oLP, true), 0); }
            if ($debug > 2) { error_log('////Building new lp', 0); }
            unset($oLP);
            $code = api_get_course_id();
            $mylp = & new learnpath($code,$lp_id,$user_id);
        } else {
            if ($debug > 1) { error_log('////Reusing session lp', 0); }
            $mylp = & $oLP;
        }
    }
    $new_item_id = 0;
    switch ($next_item) {
        case 'next':
            $mylp->set_current_item($current_item);
            $mylp->next();
            $new_item_id = $mylp->get_current_item_id();
            if ($debug > 1) { error_log('In {next} - next item is '.$new_item_id.'(current: '.$current_item.')', 0); }
            break;
        case 'previous':
            $mylp->set_current_item($current_item);
            $mylp->previous();
            $new_item_id = $mylp->get_current_item_id();
            if ($debug > 1) { error_log('In {previous} - next item is '.$new_item_id.'(current: '.$current_item.')', 0); }
            break;
        case 'first':
            $mylp->set_current_item($current_item);
            $mylp->first();
            $new_item_id = $mylp->get_current_item_id();
            if ($debug > 1) { error_log('In {first} - next item is '.$new_item_id.'(current: '.$current_item.')', 0); }
            break;
        case 'last':
            break;
        default:
            // Should be filtered to check it's not hacked
            if($next_item == $current_item){
                // If we're opening the same item again.
                $mylp->items[$current_item]->restart();
            }
            $new_item_id = $next_item;
            $mylp->set_current_item($new_item_id);
            if ($debug > 1) { error_log('In {default} - next item is '.$new_item_id.'(current: '.$current_item.')', 0); }
            break;
    }
    $mylp->start_current_item(true);
    if ($mylp->force_commit) {
        $mylp->save_current();
    }
    //$objResponse->addAlert(api_get_path(REL_CODE_PATH).'newscorm/learnpathItem.class.php');
    if (is_object($mylp->items[$new_item_id])) {
        $mylpi = & $mylp->items[$new_item_id];
    } else {
        if ($debug > 1) { error_log('In switch_item_details - generating new item object', 0); }
        $mylpi =& new learnpathItem($new_item_id, $user_id);
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
    $mytotal_time = $mylpi->get_scorm_time('js');
    $mymastery_score = $mylpi->get_mastery_score();
    $mymax_time_allowed = $mylpi->get_max_time_allowed();
    $mylaunch_data = $mylpi->get_launch_data();
    /*
    if ($mylpi->get_type() == 'asset') {
        // Temporary measure to save completion of an asset. Later on, Chamilo should trigger something on unload, maybe... (even though that would mean the last item cannot be completed)
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
            //"saved_lesson_status='not attempted';" .
            "olms.lms_lp_id=".$lp_id.";" .
            "olms.lms_item_id=".$new_item_id.";" .
            "olms.lms_old_item_id=0;" .
            //"lms_been_synchronized=0;" .
            "olms.lms_initialized=0;" .
            //"lms_total_lessons=".$mytotal.";" .
            //"lms_complete_lessons=".$mycomplete.";" .
            //"lms_progress_bar_mode='".$myprogress_mode."';" .
            "olms.lms_view_id=".$view_id.";" .
            "olms.lms_user_id=".$user_id.";" .
            "olms.next_item=".$new_item_id.";" . // This one is very important to replace possible literal strings.
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

    $return .= "update_toc('unhighlight','".$current_item."');".
                "update_toc('highlight','".$new_item_id."');".
                "update_toc('$mylesson_status','".$new_item_id."');".
                "update_progress_bar('$mycomplete','$mytotal','$myprogress_mode');";

    $mylp->set_error_msg('');
    $mylp->prerequisites_match(); // Check the prerequisites are all complete.
    if ($debug > 1) { error_log('Prereq_match() returned '.htmlentities($mylp->error), 0); }
    $_SESSION['scorm_item_id'] = $new_item_id; // Save the new item ID for the exercise tool to use.
    $_SESSION['lpobject'] = serialize($mylp);
    return $return;
    //return $objResponse;
}

echo switch_item_toc($_POST['lid'], $_POST['uid'], $_POST['vid'], $_POST['iid'], $_POST['next']);
