<?php

/* For licensing terms, see /license.txt */

/**
 * @author Toon Keppens
 */
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);

$TBL_ANSWER = Database::get_course_table(TABLE_QUIZ_ANSWER);
$questionId = (int) ($_GET['questionId']);
$answerId = (int) ($_GET['answerId']);

if ('square' == $_GET['type'] || 'circle' == $_GET['type']) {
    $hotspot_type = $_GET['type'];
    $hotspot_coordinates = $_GET['x'].';'.$_GET['y'].'|'.$_GET['width'].'|'.$_GET['height'];
}
if ('poly' == $_GET['type'] || 'delineation' == $_GET['type'] || 'oar' == $_GET['type']) {
    $hotspot_type = $_GET['type'];
    $tmp_coord = explode(',', $_GET['co']);
    $i = 0;
    $hotspot_coordinates = '';
    foreach ($tmp_coord as $coord) {
        if (0 == $i % 2) {
            $delimiter = ';';
        } else {
            $delimiter = '|';
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
            id = ".(int) $answerId.' AND
            question_id = '.(int) $questionId.'
        LIMIT 1 ';
$result = Database::query($sql);
echo 'done=done';
