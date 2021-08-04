<?php

/* For licensing terms, see /license.txt */

/**
 * Script to move users from one course to another if they haven't taken the
 * test in the first course.
 * This script includes logic and side-effects, which is contrary to PSR-1, but
 * it is not considered as a "finished" script to be included in Chamilo.
 * Refs BT#8845
 */
/**
 * Init
 */
exit;
require __DIR__.'/../../main/inc/global.inc.php';
// Define origin and destination courses' code
$originCourse = 'XYZ2014';
$destinationCourse = 'XYZ2014C2';
// Set to true to only show what it would do
$debug = true;

/**
 * Check and move users
 */
$output = moveUserFromCourseToCourse($originCourse, $destinationCourse, $debug);
if (empty($output)) {
    $output = 'No output';
}
/**
 * Output on screen (use in HTTP only for now)
 */
echo '<!DOCTYPE html><html lang="en">'.
    '<head>'.
    '<meta charset="utf-8">'.
    '<link href="'.api_get_path(WEB_CODE_PATH).'css/base.css" media="all" rel="stylesheet" type="text/css" />'.
    '</head>'.
    '<body>'.
    $output.
    '</body></html>';

/**
 * Moves a user from course A to course B "the hard way", by only changing
 * the course_rel_user table. This does not remove any data registered in
 * course A, as per requirements.
 * @param   string $originCourse Origin course's code
 * @param   string $destinationCourse Destination course's code
 * @param   bool   $debug Whether to only show potential action, or to execute them
 * @return  string  Output string
 */
function moveUserFromCourseToCourse($originCourse, $destinationCourse, $debug = true)
{
    $eol = PHP_EOL;
    $output = '';
    if (PHP_SAPI != 'cli') {
        $eol = "<br />".$eol;
    }

    if (empty($originCourse)) {
        return $output;
    } else {
        $originCourse = Database::escape_string($originCourse);
    }
    if (empty($destinationCourse)) {
        return $output;
    } else {
        $destinationCourse = Database::escape_string($destinationCourse);
    }
    $output .= 'Moving students who have no exe results from course '.$originCourse.' to course '.$destinationCourse.$eol;
    $tableCRU = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tableTEE = Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCISES);
    $courseId = api_get_course_int_id($originCourse);
    // Get the users who have passed an exam in the course of origin
    $sql = "SELECT distinct(exe_user_id) FROM $tableTEE
        WHERE c_id = $courseId";
        //AND status != 'incomplete'";
    $output .= "$sql".$eol;
    $res = Database::query($sql);
    $users = array(); // users list in array format
    while ($row = Database::fetch_row($res)) {
        $users[] = $row[0];
    }
    // Now get the list of users subscribed to the course of origin
    $sql = "SELECT user_id
        FROM $tableCRU
        WHERE status = ".STUDENT."
        AND c_id = '$courseId'";
    $output .= "$sql".$eol;
    $res = Database::query($sql);
    $numUsers = Database::num_rows($res);
    if ($numUsers < 1) {
        return $output; //no user registered in first course
    }
    // Now get the list of users subscribed to the course of origin
    $sqlDestination = "SELECT user_id
        FROM $tableCRU
        WHERE status = ".STUDENT."
        AND course_code = '$destinationCourse'";
    $output .= "$sqlDestination".$eol;
    $resDestination = Database::query($sqlDestination);
    $destinationUsers = array();
    while ($row = Database::fetch_assoc($resDestination)) {
        $destinationUsers[] = $row['user_id'];
    }

    // List of users with no attempt
    $noAttemptUsers = array();
    // List of users with an attempt
    $attemptUsers = array();
    $i = 0;
    $output .= '<ul>';
    while ($row = Database::fetch_assoc($res)) {
        $i++;
        // If there are results from
        if (in_array($row['user_id'], $users)) {
            // This user has already attempted
            $u = api_get_user_info($row['user_id']);
            $attemptUsers[$row['user_id']] = $u;
            $output .= '<li class="muted">';
            $output .= $i.' - User '.$u['lastname'].' '.$u['firstname'].' <'.$u['email'].'> has results.';
            $output .= '</li>'.PHP_EOL;
        } else {
            // This user hasn't attempted anything
            $u = api_get_user_info($row['user_id']);
            if (in_array($row['user_id'], $destinationUsers)) {
                $output .= '<li class="muted">';
                $output .= $i.' - User '.$u['lastname'].' '.$u['firstname'].' <'.$u['email'].'> has no results but is already in the destination course.'.$eol;
                $output .= '</li>'.PHP_EOL;
            } else {
                $output .= '<li class="">';
                $output .= $i.' - User '.$u['lastname'].' '.$u['firstname'].' <'.$u['email'].'> has no results and will be moved.'.$eol;
                $noAttemptUsers[$row['user_id']] = $u;
                $output .= '</li>'.PHP_EOL;
            }
        }
    }
    $output .= '</ul>';
    if ($debug) {
        return $output;
    }
    // If not debug mode, execute the move!
    $j = 0;
    foreach ($noAttemptUsers as $userId => $userInfo) {
        // unsubscribe
        $sql = "DELETE FROM $tableCRU WHERE course_code = '$originCourse' AND user_id = $userId";
        $output .= $sql.$eol;
        Database::query($sql);
        $sql = "INSERT INTO $tableCRU (course_code, user_id, status)
          VALUES ('$destinationCourse', $userId, ".STUDENT.")";
        $output .= $sql.$eol;
        Database::query($sql);
        $j++;
    }
    $output .= "$j users have been moved".$eol;
    return $output;
}
