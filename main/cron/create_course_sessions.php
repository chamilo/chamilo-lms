<?php
/* For licensing terms, see /license.txt */
/**
 * Create course sessions procedure. It creates sessions for courses that haven't it yet.
 * If today is greater than OFFSET, it will create them also for the next month.
 * @package chamilo.cron
 * @author Imanol Losada <imanol.losada@beeznest.com>
 */

/**
 * Initialization
 */
if (php_sapi_name() != 'cli') {
    exit; //do not run from browser
}

require_once __DIR__ . "/../inc/global.inc.php";

// First day of the current month to create sessions and add courses for the next month (e.g. "07")
define("OFFSET", "15");
/**
 * If no $initialDate is supplied, returns an array with the first and last days of the current
 * month. Otherwise, returns an array with the first and last days of the $initialDate month .
 * @param   array   First day of the month
 * @return  array   First and last days of the month
 */
function getMonthFirstAndLastDates($initialDate = null) {
    $startDate = $initialDate ? $initialDate : date("Y-m-01");
    $nextMonthStartDate = date("Y-m-d", api_strtotime($startDate." + 1 month"));
    $endDate = date("Y-m-d", api_strtotime($nextMonthStartDate." - 1 minute"));
    return array('startDate' => $startDate, 'endDate' => $endDate);
}

/**
 * Creates one session per course with $administratorId as the creator and
 * adds it to the session starting on $startDate and finishing on $endDate
 * @param   array   Courses
 * @param   int     Administrator id
 * @param   date    First day of the month
 * @param   date    Last day of the month
 * @return  void
 */
function createCourseSessions($courses, $administratorId, $startDate, $endDate) {
    echo "\n";
    echo $courses ?
        "Creating sessions and adding courses for the period between ".$startDate." and ".$endDate :
        "Every course is already in session for the period between ".$startDate." and ".$endDate;
    echo "\n=====================================================================================\n\n";
    // Loop through courses creating one session per each and adding them
    foreach ($courses as $course) {
        $sessionName = $course['title']." (".date("M Y", api_strtotime($startDate)).")";
        $sessionId = SessionManager::create_session(
            $sessionName,
            $startDate,
            $endDate,
            0,
            0,
            null,
            $administratorId,
            0,
            SESSION_INVISIBLE
        );
        SessionManager::add_courses_to_session($sessionId, array($course['code']));
        echo "Session '".$sessionName."' created.\nCourse '".$course['title']."' added.\n\n";
    }
}

// Starts the script

// Get first active administrator
$administrators = array_reverse(UserManager::get_all_administrators());
$lastingAdministrators = count($administrators);
while (!$administrators[$lastingAdministrators - 1]['active'] && $lastingAdministrators > 0) {
    $lastingAdministrators--;
}
if (!$lastingAdministrators) {
    echo "There are no active administrators. Process halted.\n";
    exit;
}
$administratorId = intval($administrators[$lastingAdministrators - 1]['user_id']);

// Creates course sessions for the current month
$dates = getMonthFirstAndLastDates(date('Y-m-').'01');
// Get courses that don't have any session
$courses = CourseManager::getCoursesWithoutSession($dates['startDate'], $dates['endDate']);
createCourseSessions($courses, $administratorId, $dates['startDate'], $dates['endDate']);

// Creates course sessions for the following month
if (date("Y-m-d") >= date("Y-m-".OFFSET)) {
    $dates = getMonthFirstAndLastDates(date("Y-m-d", api_strtotime(date("Y-m-01")." + 1 month")));
    // Get courses that don't have any session the next month
    $courses = CourseManager::getCoursesWithoutSession($dates['startDate'], $dates['endDate']);
    createCourseSessions($courses, $administratorId, $dates['startDate'], $dates['endDate']);
}
