<?php
/* For licensing terms, see /license.txt */
/**
 * This script contains the server part of the xajax interaction process.
 * The client part is located in lp_api.php or other api's.
 * This is a first attempt at using xajax and AJAX in general,
 * so the code might be a bit unsettling.
 *
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */

// Flag to allow for anonymous user - needs to be set before global.inc.php.
$use_anonymous = true;
require_once __DIR__.'/../inc/global.inc.php';

/**
 * Writes an item's new values into the database and returns the operation result.
 *
 * @param   int Learnpath ID
 * @param   int User ID
 * @param   int View ID
 * @param   int Item ID
 * @param   array   Objectives array
 */
function save_objectives($lp_id, $user_id, $view_id, $item_id, $objectives = [])
{
    $debug = 0;
    $return = '';
    if ($debug > 0) {
        error_log('In xajax_save_objectives('.$lp_id.','.$user_id.','.$view_id.','.$item_id.',"'.(count($objectives) > 0 ? count($objectives) : '').'")', 0);
    }
    $mylp = learnpath::getLpFromSession(api_get_course_id(), $lp_id, $user_id);
    $mylpi = &$mylp->items[$item_id];
    if (is_array($objectives) && count($objectives) > 0) {
        foreach ($objectives as $index => $objective) {
            $mylpi->add_objective($index, $objectives[$index]);
        }
        $mylpi->write_objectives_to_db();
    }

    return $return;
}
$objectives = [];
if (isset($_REQUEST['objectives'])) {
    if (is_array($_REQUEST['objectives'])) {
        foreach ($_REQUEST['objectives'] as $idx => $ob) {
            $objectives[$idx] = explode(',', substr($ob, 1, -1));
            if (!isset($objectives[$idx][4])) {
                // Make sure there are 7 elements.
                $objectives[$idx][4] = '';
            }
        }
    }
}

echo save_objectives(
    $_REQUEST['lid'],
    $_REQUEST['uid'],
    $_REQUEST['vid'],
    $_REQUEST['iid'],
    $objectives
);
