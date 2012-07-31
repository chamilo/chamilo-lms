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
// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;

// Name of the language file that needs to be included.
$language_file[] = 'learnpath';

require_once 'back_compat.inc.php';

/**
 * Writes an item's new values into the database and returns the operation result
 * @param   integer Learnpath ID
 * @param   integer User ID
 * @param   integer View ID
 * @param   integer Item ID
 * @param   array   Objectives array
 */
function save_objectives($lp_id, $user_id, $view_id, $item_id, $objectives = array()) {
    global $_configuration;
    $debug = 0;
    $return = '';
    if ($debug > 0) { error_log('In xajax_save_objectives('.$lp_id.','.$user_id.','.$view_id.','.$item_id.',"'.(count($objectives) > 0 ? count($objectives) : '').'")', 0); }
    //$objResponse = new xajaxResponse();
    require_once 'learnpath.class.php';
    require_once 'scorm.class.php';
    require_once 'aicc.class.php';
    require_once 'learnpathItem.class.php';
    require_once 'scormItem.class.php';
    require_once 'aiccItem.class.php';
    $mylp = '';
    if (isset($_SESSION['lpobject'])) {
        if ($debug > 1) { error_log('////$_SESSION[lpobject] is set', 0); }
        $oLP =unserialize($_SESSION['lpobject']);
        if (!is_object($oLP)) {
            if ($debug > 2) { error_log(print_r($oLP,true), 0); }
            if ($debug > 2) { error_log('////Building new lp', 0); }
            unset($oLP);
            $code = api_get_course_id();
            $mylp = new learnpath($code, $lp_id, $user_id);
        }else{
            if ($debug > 2) { error_log('////Reusing session lp', 0); }
            $mylp = & $oLP;
        }
    }
    $mylpi =& $mylp->items[$item_id];
    //error_log(__FILE__.' '.__LINE__.' '.print_r($objectives,true), 0);
    if(is_array($objectives) && count($objectives)>0){
        foreach($objectives as $index=>$objective){
            //error_log(__FILE__.' '.__LINE__.' '.$objectives[$index][0], 0);
            $mylpi->add_objective($index,$objectives[$index]);
        }
        $mylpi->write_objectives_to_db();
    }
    //return $objResponse;
    return $return;
}
$objectives = array();
if (isset($_REQUEST['objectives'])) {
    if (is_array($_REQUEST['objectives'])) {
        foreach ($_REQUEST['objectives'] as $idx => $ob) {
            $objectives[$idx] = split(',', substr($ob, 1, -1));
            if (!isset($objectives[$idx][4])) { // Make sure there are 7 elements.
                $objectives[$idx][4] = '';
            }
        }
    }
}
echo save_objectives($_REQUEST['lid'], $_REQUEST['uid'], $_REQUEST['vid'], $_REQUEST['iid'], $objectives);
