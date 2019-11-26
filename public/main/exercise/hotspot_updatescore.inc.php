<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 *	This file saves every click in the hotspot tool into track_e_hotspots.
 *
 *	@package chamilo.exercise
 *
 * 	@author Toon Keppens
 *
 * 	@version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$courseCode = $_GET['coursecode'];
$questionId = $_GET['questionId'];
$coordinates = $_GET['coord'];
$objExercise = Session::read('objExercise');
$hotspotId = $_GET['hotspotId'];
$exerciseId = $objExercise->selectId();
if ($_GET['answerId'] == "0") { // click is NOT on a hotspot
    $hit = 0;
    $answerId = $hotspotId;

    // remove from session
    unset($_SESSION['exerciseResult'][$questionId][$answerId]);
} else { // user clicked ON a hotspot
    $hit = 1;
    $answerId = $hotspotId;

    // Save into session
    $_SESSION['exerciseResult'][$questionId][$answerId] = $hit;
}

//round-up the coordinates
$coords = explode('/', $coordinates);
$coordinates = '';
foreach ($coords as $coord) {
    list($x, $y) = explode(';', $coord);
    $coordinates .= round($x).';'.round($y).'/';
}
$coordinates = substr($coordinates, 0, -1);

$TBL_TRACK_E_HOTSPOT = Database::get_main_table(TABLE_STATISTIC_TRACK_E_HOTSPOT);

// update db
$update_id = $_SESSION['exerciseResult'][$questionId]['ids'][$answerId];
$sql = "UPDATE $TBL_TRACK_E_HOTSPOT 
        SET coordinate = '".Database::escape_string($coordinates)."'
        WHERE id = ".intval($update_id)." 
        LIMIT 1";
$result = Database::query($sql);
