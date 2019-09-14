<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.exercise
 *
 * @author Toon Keppens
 *
 * @version $Id: admin.php 10680 2007-01-11 21:26:23Z pcool $
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$TBL_ANSWER = Database::get_course_table(TABLE_QUIZ_ANSWER);
$questionId = intval($_GET['questionId']);
$answerId = intval($_GET['answerId']);

if ($_GET['type'] == "square" || $_GET['type'] == "circle") {
    $hotspot_type = $_GET['type'];
    $hotspot_coordinates = $_GET['x'].";".$_GET['y']."|".$_GET['width']."|".$_GET['height'];
}
if ($_GET['type'] == "poly" || $_GET['type'] == "delineation" || $_GET['type'] == "oar") {
    $hotspot_type = $_GET['type'];
    $tmp_coord = explode(",", $_GET['co']);
    $i = 0;
    $hotspot_coordinates = "";
    foreach ($tmp_coord as $coord) {
        if ($i % 2 == 0) {
            $delimiter = ";";
        } else {
            $delimiter = "|";
        }
        $hotspot_coordinates .= $coord.$delimiter;
        $i++;
    }
    $hotspot_coordinates = api_substr($hotspot_coordinates, 0, -2);
}
$course_id = api_get_course_int_id();
$sql = "UPDATE $TBL_ANSWER SET
            hotspot_coordinates = '".Database::escape_string($hotspot_coordinates)."',
            hotspot_type = '".Database::escape_string($hotspot_type)."'
        WHERE
            c_id = $course_id AND
            id = ".intval($answerId)." AND
            question_id = ".intval($questionId)."
        LIMIT 1 ";
$result = Database::query($sql);
echo "done=done";
