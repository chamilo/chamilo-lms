<?php //$id$
/**
 * This script contains the server part of the xajax interaction process. The
 * client part is located in lp_api.php or other api's.
 * This script exists exclusively to comply with the SCORM 1.2 rules notes that
 * say that if the SCO doesn't give a status throughout its execution, then the
 * status should be automatically set to 'completed', then the score should be
 * evaluated against mastery_score and, if a raw score and mastery_score value
 * are set, assign the final status based on that.
 * As such, this script will:
 * 1 - check the current status for the given element is still 'not attempted'
 * 2 - check if there is a mastery score
 * 3 - check if there is a raw score
 * 4 - if 2 or 3 are false, change the status to 'completed', else compare
 * whether the raw score is higher than the mastery score. If not, the status
 * will be set to 'failed', if yes, the status will be set to 'passed'
 * 5 - update the status in the table of contents
 * @package dokeos.learnpath
 * @author Yannick Warnier <yannick.warnier@dokeos.com>
 */
//flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;
// name of the language file that needs to be included
$language_file[] = 'learnpath';
require_once('back_compat.inc.php');
/**
 * Writes an item's new values into the database and returns the operation result
 * @param   integer Learnpath ID
 * @param   integer User ID
 * @param   integer View ID
 * @param   integer Item ID
 * @return  string  JavaScript operations to execute as soon as returned
 */
function last_update_status($lp_id,$user_id,$view_id,$item_id)
{
    error_log(__LINE__);
    global $_configuration;
    $debug=0;
    $return = '';
    if($debug>0){error_log('In last_update_status('.$lp_id.','.$user_id.','.$view_id.','.$item_id.')',0);}
    require_once('learnpath.class.php');
    require_once('scorm.class.php');
    require_once('learnpathItem.class.php');
    require_once('scormItem.class.php');
    $mylp = '';
    if(isset($_SESSION['lpobject']))
    {
        if($debug>1){error_log('////$_SESSION[lpobject] is set',0);}
        $oLP =& unserialize($_SESSION['lpobject']);
        if(!is_object($oLP)){
            if($debug>2){error_log(print_r($oLP,true),0);}
            if($debug>2){error_log('////Building new lp',0);}
            unset($oLP);
            $code = api_get_course_id();
            $mylp = & new learnpath($code,$lp_id,$user_id);
        }else{
            if($debug>2){error_log('////Reusing session lp',0);}
            $mylp = & $oLP;
        }
    }
    error_log(__LINE__);

    // this function should only be used for SCORM paths
    if ($mylp->get_type()!=2) {
        return;
    }
    $prereq_check = $mylp->prerequisites_match($item_id);
    $mystatus = '';
    if ($prereq_check === true) {
        error_log(__LINE__);
    //launch the prerequisites check and set error if needed
        $mylpi =& $mylp->items[$item_id];

        $mystatus_in_db = $mylpi->get_status(true);
        error_log($mystatus_in_db);
        if ($mystatus_in_db == 'not attempted' or $mystatus_in_db == '') {
        error_log(__LINE__);
            $mystatus = 'completed';
        	$mastery_score = $mylpi->get_mastery_score();
            if ($mastery_score != -1) {
        error_log(__LINE__);
            	$score = $mylpi->get_score();
                if ($score != 0 && $score >= $mastery_score) {
        error_log(__LINE__);
                	$mystatus = 'passed';
                } else {
        error_log(__LINE__);
                	$mystatus = 'failed';
                }
            }
            error_log(__LINE__);
            $mylpi->set_status($mystatus);
            $mylp->save_item($item_id,false);
        } else {
            error_log(__LINE__);
            return $return;
        }
    } else {
        error_log(__LINE__);
        return $return;
    }
    error_log(__LINE__);
    $mytotal = $mylp->get_total_items_count_without_chapters();
    $mycomplete = $mylp->get_complete_items_count();
    $myprogress_mode = $mylp->get_progress_bar_mode();
    $myprogress_mode = ($myprogress_mode==''?'%':$myprogress_mode);
    $return .= "update_toc('".$mystatus."','".$item_id."','no');";
    error_log('Return is now '.$return);
    $update_list = $mylp->get_update_queue();
    foreach ($update_list as $my_upd_id => $my_upd_status) {
        if ($my_upd_id != $item_id) { //only update the status from other items (i.e. parents and brothers), do not update current as we just did it already
            $return .= "update_toc('".$my_upd_status."','".$my_upd_id."','no');";
        }
    }
    $return .= "update_progress_bar('$mycomplete','$mytotal','$myprogress_mode');";
    $return .="update_stats();";
    return $return;
    //return $objResponse;
}
error_log(__LINE__);
echo last_update_status(
            $_GET['lid'],
            $_GET['uid'],
            $_GET['vid'],
            $_GET['iid']);