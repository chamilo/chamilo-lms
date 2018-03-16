<?php
/* For licensing terms, see /license.txt */
/**
 * Create course sessions procedure. It creates sessions for courses that haven't it yet.
 * If today is greater than OFFSET, it will create them also for the next quarter.
 *
 * @package chamilo.cron
 *
 * @author Imanol Losada <imanol.losada@beeznest.com>
 */

/**
 * Initialization.
 */
if (php_sapi_name() != 'cli') {
    exit; //do not run from browser
}

require_once __DIR__."/../inc/global.inc.php";

// First day of the current month to create sessions and add courses for the next month (e.g. "07")
define("OFFSET", "15");
/**
 * If no $initialDate is supplied, returns an array with the first and last days of the current
 * month. Otherwise, returns an array with the first and last days of the $initialDate month .
 *
 * @param array $initialDate First day of the month
 *
 * @return array First and last days of the month
 */
function getMonthFirstAndLastDates($initialDate = null)
{
    $startDate = $initialDate ? $initialDate : date("Y-m-01");
    $nextMonthStartDate = date("Y-m-d", api_strtotime($startDate." + 1 month"));
    $endDate = date("Y-m-d", api_strtotime($nextMonthStartDate." - 1 minute"));

    return ['startDate' => $startDate, 'endDate' => $endDate];
}

/**
 * Same as month, but for quarters.
 *
 * @param array $initialDate First day of the quarter
 *
 * @return array First and last days of the quarter
 */
function getQuarterFirstAndLastDates($initialDate = null)
{
    $startDate = $initialDate ? $initialDate : date("Y-m-01");
    $month = getQuarterFirstMonth(getQuarter(date('m', $startDate)));
    $startDate = substr($startDate, 0, 5).$month.'-01';
    $nextQuarterStartDate = date('Y-m-d', api_strtotime($startDate.' + 3 month'));
    $endDate = date('Y-m-d', api_strtotime($nextQuarterStartDate.' - 1 minute'));

    return ['startDate' => $startDate, 'endDate' => $endDate];
}

/**
 * Returns a quarter from a month.
 *
 * @param   string  The month (digit), with or without leading 0
 * @param string $month
 *
 * @return int The yearly quarter (1, 2, 3 or 4) in which this month lies
 */
function getQuarter($month)
{
    $quarter = 1;
    // Remove the leading 0 if any
    if (substr($month, 0, 1) == '0') {
        $month = substr($month, 1);
    }
    // reduce to 4 quarters: 1..3=1; 4..6=2
    switch ($month) {
        case 1:
        case 2:
        case 3:
            $quarter = 1;
            break;
        case 4:
        case 5:
        case 6:
            $quarter = 2;
            break;
        case 7:
        case 8:
        case 9:
            $quarter = 3;
            break;
        case 10:
        case 11:
        case 12:
            $quarter = 4;
            break;
    }

    return $quarter;
}

/**
 * Returns the first month of the quarter.
 *
 * @param   int Quarter
 * @param int $quarter
 *
 * @return string Number of the month, with leading 0
 */
function getQuarterFirstMonth($quarter)
{
    switch ($quarter) {
        case 1:
            return '01';
        case 2:
            return '04';
        case 3:
            return '07';
        case 4:
            return '10';
    }

    return false;
}

/**
 * Get the quarter in Roman letters.
 *
 * @param   int Quarter
 * @param int $quarter
 *
 * @return string Roman letters
 */
function getQuarterRoman($quarter)
{
    switch ($quarter) {
        case 1:
            return 'I';
        case 2:
            return 'II';
        case 3:
            return 'III';
        case 4:
            return 'IV';
    }
}

/**
 * Creates one session per course with $administratorId as the creator and
 * adds it to the session starting on $startDate and finishing on $endDate.
 *
 * @param array $courses         Courses
 * @param int   $administratorId Administrator id
 * @param date  $startDate       First day of the month
 * @param date  $endDate         Last day of the month
 */
function createCourseSessions($courses, $administratorId, $startDate, $endDate)
{
    echo "\n";
    echo $courses ?
        "Creating sessions and adding courses for the period between ".$startDate." and ".$endDate : "Every course is already in session for the period between ".$startDate." and ".$endDate;
    echo "\n=====================================================================================\n\n";
    // Loop through courses creating one session per each and adding them
    foreach ($courses as $course) {
        //$period = date("m/Y", api_strtotime($startDate));
        $month = date("m", api_strtotime($startDate));
        $year = date("Y", api_strtotime($startDate));
        $quarter = getQuarter($month);
        $quarter = getQuarterRoman($quarter);
        $period = $year.'-'.$quarter;
        $sessionName = '['.$period.'] '.$course['title'];
        $sessionId = SessionManager::create_session(
            $sessionName,
            $startDate,
            $endDate,
            null,
            null,
            null,
            null,
            $administratorId,
            0,
            SESSION_INVISIBLE
        );
        SessionManager::add_courses_to_session($sessionId, [$course['id']]);
        echo "Session '".$sessionName."' created.\nCourse '".$course['title']."' added.\n\n";
    }
}

// Starts the script
echo "Starting process...".PHP_EOL;
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
$dates = getQuarterFirstAndLastDates(date('Y-m-').'01');
// Get courses that don't have any session
$courses = CourseManager::getCoursesWithoutSession($dates['startDate'], $dates['endDate']);
createCourseSessions($courses, $administratorId, $dates['startDate'], $dates['endDate']);

// Creates course sessions for the following month
$offsetDay = intval(substr($dates['endDate'], 8, 2)) - OFFSET;
if (date("Y-m-d") >= date(substr($dates['endDate'], 0, 8).$offsetDay)) {
    $dates = getQuarterFirstAndLastDates(date("Y-m-d", api_strtotime(date("Y-m-01")." + 3 month")));
    // Get courses that don't have any session the next month
    $courses = CourseManager::getCoursesWithoutSession($dates['startDate'], $dates['endDate']);
    createCourseSessions($courses, $administratorId, $dates['startDate'], $dates['endDate']);
}
