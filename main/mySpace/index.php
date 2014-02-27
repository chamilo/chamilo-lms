<?php
/* For licensing terms, see /license.txt */
/**
 * Homepage for the MySpace directory
 * @package chamilo.reporting
 */
/**
 * code
 */
$language_file = array('registration', 'index', 'tracking', 'admin', 'exercice');

// resetting the course id
$cidReset = true;

require_once '../inc/global.inc.php';

// including additional libraries
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once 'myspace.lib.php';

$htmlHeadXtra[] = api_get_jqgrid_js();
// the section (for the tabs)
$this_section = SECTION_TRACKING;
//for HTML editor repository
unset($_SESSION['this_section']);

ob_start();

$export_csv  = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$display 	 = isset($_GET['display']) ? Security::remove_XSS($_GET['display']) : null;
$csv_content = array();
$nameTools = get_lang('MySpace');

$user_id = api_get_user_id();
$is_coach = api_is_coach($_GET['session_id']); // This is used?

$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

$is_platform_admin 	= api_is_platform_admin();
$is_drh 			= api_is_drh();
$is_session_admin 	= api_is_session_admin();

$count_sessions 	= 0;
$count_courses		= 0;
$title 				= null;

// Access control
api_block_anonymous_users();

if (!$export_csv) {
    Display :: display_header($nameTools);
} else {
    if ($_GET['view'] == 'admin') {
        if ($display == 'useroverview') {
            MySpace::export_tracking_user_overview();
            exit;
        } elseif ($display == 'sessionoverview') {
            MySpace::export_tracking_session_overview();
            exit;
        } elseif ($display == 'courseoverview') {
            MySpace::export_tracking_course_overview();
            exit;
        }
    }
}

// Database table definitions
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_sessions 				= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

/* FUNCTIONS */
function count_coaches()
{
    global $total_no_coaches;
    return $total_no_coaches;
}

function sort_users($a, $b)
{
    return api_strcmp(
        trim(api_strtolower($a[$_SESSION['tracking_column']])),
        trim(api_strtolower($b[$_SESSION['tracking_column']]))
    );
}

function rsort_users($a, $b)
{
    return api_strcmp(
        trim(api_strtolower($b[$_SESSION['tracking_column']])),
        trim(api_strtolower($a[$_SESSION['tracking_column']]))
    );
}

function count_sessions_coached()
{
    global $count_sessions;
    return $count_sessions;
}

function sort_sessions($a, $b)
{
    global $tracking_column;
    if ($a[$tracking_column] > $b[$tracking_column]) {
        return 1;
    } else {
        return -1;
    }
}

function rsort_sessions($a, $b)
{
    global $tracking_column;
    if ($b[$tracking_column] > $a[$tracking_column]) {
        return 1;
    } else {
        return -1;
    }
}

/* MAIN CODE  */

if ($is_session_admin) {
    header('location:session.php');
    exit;
}

// Get views
$views = array('admin', 'teacher', 'coach', 'drh');
$view  = 'teacher';
if (isset($_GET['view']) && in_array($_GET['view'], $views)) {
    $view = $_GET['view'];
}

$menu_items = array();
global $_configuration;

if ($is_platform_admin) {
    if ($view == 'admin') {
        $title = get_lang('CoachList');
        $menu_items[] = Display::url(Display::return_icon('teacher.png', get_lang('TeacherInterface'), array(), ICON_SIZE_MEDIUM), api_get_self().'?view=teacher');
        $menu_items[] = Display::url(Display::return_icon('star_na.png', get_lang('AdminInterface'), array(), ICON_SIZE_MEDIUM), api_get_self().'?view=admin');
        $menu_items[] = Display::url(Display::return_icon('quiz.png', get_lang('ExamTracking'), array(), ICON_SIZE_MEDIUM), api_get_path(WEB_CODE_PATH).'tracking/exams.php');
        $menu_items[] = Display::url(Display::return_icon('statistics.png', get_lang('CurrentCoursesReport'), array(), ICON_SIZE_MEDIUM), api_get_path(WEB_CODE_PATH).'mySpace/current_courses.php');
    } else {
        $menu_items[] = Display::url(Display::return_icon('teacher_na.png', get_lang('TeacherInterface'), array(), ICON_SIZE_MEDIUM), '');
        $menu_items[] = Display::url(Display::return_icon('star.png', get_lang('AdminInterface'), array(), ICON_SIZE_MEDIUM), api_get_self().'?view=admin');
        $menu_items[] = Display::url(Display::return_icon('quiz.png', get_lang('ExamTracking'), array(), ICON_SIZE_MEDIUM), api_get_path(WEB_CODE_PATH).'tracking/exams.php');
        $menu_items[] = Display::url(Display::return_icon('statistics.png', get_lang('CurrentCoursesReport'), array(), ICON_SIZE_MEDIUM), api_get_path(WEB_CODE_PATH).'mySpace/current_courses.php');
    }
}

if ($is_drh) {
	$view = 'drh';
    $menu_items[] = Display::url(Display::return_icon('user_na.png', get_lang('Students'), array(), ICON_SIZE_MEDIUM), '#');
    $menu_items[] = Display::url(Display::return_icon('teacher.png', get_lang('Trainers'), array(), ICON_SIZE_MEDIUM), 'teachers.php');
    $menu_items[] = Display::url(Display::return_icon('course.png', get_lang('Courses'), array(), ICON_SIZE_MEDIUM), 'course.php');
    $menu_items[] = Display::url(Display::return_icon('session.png', get_lang('Sessions'), array(), ICON_SIZE_MEDIUM), 'session.php');
    $menu_items[] = Display::url(Display::return_icon('empty_evaluation.png', get_lang('CompanyReports'), array(), ICON_SIZE_MEDIUM), 'company_reports.php');
    $menu_items[] = Display::url(Display::return_icon('evaluation_rate.png', get_lang('CompanyReportResumed'), array(), ICON_SIZE_MEDIUM), 'company_reports_resumed.php');
}

echo '<div id="actions" class="actions">';
echo '<span style="float:right">';

if ($display == 'useroverview' || $display == 'sessionoverview' || $display == 'courseoverview') {
    echo '<a href="'.api_get_self().'?display='.$display.'&export=csv&view='.$view.'">';
    echo Display::return_icon("export_csv.png", get_lang('ExportAsCSV'), array(), 32);
    echo '</a>';
}
echo '<a href="javascript: void(0);" onclick="javascript: window.print()">'.
    Display::return_icon('printer.png', get_lang('Print'), '', ICON_SIZE_MEDIUM).'</a>';
echo '</span>';

if (!empty($session_id) && !in_array($display, array('accessoverview','lpprogressoverview','progressoverview','exerciseprogress', 'surveyoverview'))) {
    echo '<a href="index.php">'.Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM).'</a>';
    if (!api_is_platform_admin()) {
        if (api_get_setting('add_users_by_coach') == 'true') {
            if ($is_coach) {
                echo "<div align=\"right\">";
                echo '<a href="user_import.php?id_session='.$session_id.'&action=export&amp;type=xml">'.
                        Display::return_icon('excel.gif', get_lang('ImportUserList')).'&nbsp;'.get_lang('ImportUserList').'</a>';
                echo "</div><br />";
            }
        }
    } else {
        echo "<div align=\"right\">";
        echo '<a href="user_import.php?id_session='.$session_id.'&action=export&amp;type=xml">'.
                Display::return_icon('excel.gif', get_lang('ImportUserList')).'&nbsp;'.get_lang('ImportUserList').'</a>';
        echo "</div><br />";
    }
} else {
	echo Display::url(Display::return_icon('stats.png', get_lang('MyStats'),'',ICON_SIZE_MEDIUM), api_get_path(WEB_CODE_PATH)."auth/my_progress.php");
}

// Actions menu
$nb_menu_items = count($menu_items);
if (empty($session_id) || in_array($display, array('accessoverview','lpprogressoverview', 'progressoverview', 'exerciseprogress', 'surveyoverview'))) {
    if ($nb_menu_items > 1) {
        foreach ($menu_items as $key => $item) {
            echo $item;
        }
    }
}

echo '</div>';


// Getting courses followed by a coach (No session courses).
$courses = CourseManager::get_course_list_as_coach($user_id, false);

// Courses with no session:
if (isset($courses[0])) {
    $courses = $courses[0];
}

// If is drh
if ($is_drh) {
    if (api_drh_can_access_all_session_content()) {
        $studentList = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus('drh_all', api_get_user_id());

        $students = array();
        foreach ($studentList as $studentData) {
            $students[] = $studentData['user_id'];
        }
        $courses_of_the_platform = SessionManager::getAllCoursesFromAllSessionFromDrh(api_get_user_id());

        foreach ($courses_of_the_platform as $course) {
            $courses[$course] = $course;
        }
        $sessions = SessionManager::get_sessions_followed_by_drh(api_get_user_id());

    } else {
        $students = array_keys(UserManager::get_users_followed_by_drh($user_id, STUDENT));
        $courses_of_the_platform = CourseManager::get_courses_followed_by_drh($user_id);
        foreach ($courses_of_the_platform as $course) {
            $courses[$course['code']] = $course['code'];
        }
        $sessions = SessionManager::get_sessions_followed_by_drh($user_id);
    }
} else {
    // Getting students from courses and courses in sessions (To show the total students that the user follows)
    $students = CourseManager::get_user_list_from_courses_as_coach($user_id);

    // Sessions for the coach
    $sessions = Tracking::get_sessions_coached_by_user($user_id);
}

// Courses for the user
$count_courses = count($courses);

// Sessions for the user
$count_sessions = count($sessions);

// Students
$nb_students = count($students);

$total_time_spent = 0;
$total_courses = 0;
$avg_total_progress = 0;
$avg_results_to_exercises = 0;
$nb_inactive_students = 0;
$nb_posts = $nb_assignments = 0;

$inactiveTime = time() - (3600 * 24 * 7);

$daysAgo = 7;
if (!empty($students)) {

    $studentIds = array_values($students);
    $nb_students = count($studentIds);

    // Inactive students
    $inactiveUsers = Tracking::getInactiveUsers($studentIds, $daysAgo);
    $totalTimeSpent = Tracking::get_time_spent_on_the_platform($studentIds);

    $posts = Tracking::count_student_messages($studentIds);
    $countAssignments = Tracking::count_student_assignments($studentIds);
    $progress  = Tracking::get_avg_student_progress($studentIds);
    $averageScore = Tracking::getAverageStudentScore($studentIds);

    // average progress
    $avg_total_progress = $progress / $nb_students;
    // average results to the tests
    $avg_results_to_exercises = $averageScore;
    // average assignments
    $nb_assignments = $countAssignments / $nb_students;
    // average posts
    $nb_posts = $posts ;

    $avg_time_spent = $totalTimeSpent;

    $avg_courses_per_student = $count_courses / $nb_students;

    echo Display::page_subheader(get_lang('Overview'));

    echo '<div class="report_section">
            <table class="table table-bordered">
                <tr>
                    <td>'.get_lang('FollowedUsers').'</td>
                    <td align="right">'.$nb_students.'</td>
                </tr>
                <tr>
                    <td>'.get_lang('FollowedCourses').'</td>
                    <td align="right">'.$count_courses.'</td>
                </tr>
                <tr>
                    <td>'.get_lang('FollowedSessions').'</td>
                    <td align="right">'.$count_sessions.'</td>
                </tr>
                </table>';
    echo '</div>';

    echo Display::page_subheader(get_lang('Students').' ('.$nb_students.')');

    if ($export_csv) {
        //csv part
        $csv_content[] = array(get_lang('Students'));
        $csv_content[] = array(get_lang('InactivesStudents'), $nb_inactive_students);
        $csv_content[] = array(get_lang('AverageTimeSpentOnThePlatform'), $avg_time_spent);
        $csv_content[] = array(get_lang('AverageCoursePerStudent'), $avg_courses_per_student);
        $csv_content[] = array(get_lang('AverageProgressInLearnpath'), is_null($avg_total_progress) ? null : round($avg_total_progress, 2).'%');
        $csv_content[] = array(get_lang('AverageResultsToTheExercices'), is_null($avg_results_to_exercises) ? null : round($avg_results_to_exercises, 2).'%');
        $csv_content[] = array(get_lang('AveragePostsInForum'), $nb_posts);
        $csv_content[] = array(get_lang('AverageAssignments'), $nb_assignments);
        $csv_content[] = array();
    } else {
        $lastConnectionDate = api_get_utc_datetime(strtotime('15 days ago'));
        $countActiveUsers = SessionManager::getCountUserTracking(null, 1);
        $countInactiveUsers = SessionManager::getCountUserTracking(null, 0);
        $countSleepingTeachers = SessionManager::getTeacherTracking(api_get_user_id(), 1, $lastConnectionDate, true);
        $countSleepingStudents = SessionManager::getCountUserTracking(null, 1, $lastConnectionDate);

        $form = new FormValidator('search_user', 'get', api_get_path(WEB_CODE_PATH).'mySpace/student.php');
        $form->addElement('text', 'keyword', get_lang('User'));
        $form->addElement('button', 'submit', get_lang('Search'));
        $form->display();

        // html part
        echo '<div class="report_section">
                <table class="table table-bordered">
                    <tr>
                        <td>'.Display::url(get_lang('ActiveUsers'), api_get_path(WEB_CODE_PATH).'mySpace/student.php?active=1').'</td>
                        <td align="right">'.$countActiveUsers.'</td>
                    </tr>
                    <tr>
                        <td>'.Display::url(get_lang('InactiveUsers'), api_get_path(WEB_CODE_PATH).'mySpace/student.php?active=0').'</td>
                        <td align="right">'.$countInactiveUsers.'</td>
                    </tr>
                    <tr>
                        <td>'.Display::url(get_lang('SleepingTeachers'), api_get_path(WEB_CODE_PATH).'mySpace/teachers.php?sleeping_days=15').'</td>
                        <td align="right">'.$countSleepingTeachers.'</td>
                    </tr>
                    <tr>
                        <td>'.Display::url(get_lang('SleepingStudents'), api_get_path(WEB_CODE_PATH).'mySpace/student.php?sleeping_days=15').'</td>
                        <td align="right">'.$countSleepingStudents.'</td>
                    </tr>
                    <tr>
                        <td>'.get_lang('AverageCoursePerStudent').'</td>
                        <td align="right">'.(is_null($avg_courses_per_student) ? '' : round($avg_courses_per_student, 2)).'</td>
                    </tr>
                    <tr>
                        <td>'.get_lang('InactivesStudents').'</td>
                        <td align="right">'.$nb_inactive_students.'</td>
                    </tr>
                    <tr>
                        <td>'.get_lang('AverageTimeSpentOnThePlatform').'</td>
                        <td align="right">'.(is_null($avg_time_spent) ? '' : api_time_to_hms($avg_time_spent)).'</td>
                    </tr>
                    <tr>
                        <td>'.get_lang('AverageProgressInLearnpath').'</td>
                        <td align="right">'.(is_null($avg_total_progress) ? '' : round($avg_total_progress, 2).'%').'</td>
                    </tr>
                    <tr>
                        <td>'.get_lang('AvgCourseScore').'</td>
                        <td align="right">'.(is_null($avg_results_to_exercises) ? '' : round($avg_results_to_exercises, 2).'%').'</td>
                    </tr>
                    <tr>
                        <td>'.get_lang('AveragePostsInForum').'</td>
                        <td align="right">'.(is_null($nb_posts) ? '' : round($nb_posts, 2)).'</td>
                    </tr>
                    <tr>
                        <td>'.get_lang('AverageAssignments').'</td>
                        <td align="right">'.(is_null($nb_assignments) ? '' : round($nb_assignments, 2)).'</td>
                    </tr>
                </table>
                <a class="btn" href="'.api_get_path(WEB_CODE_PATH).'mySpace/student.php">
                '.get_lang('SeeStudentList').'
                </a>
             </div><br />';
    }
}

// Send the csv file if asked
if ($export_csv) {
	ob_end_clean();
	Export :: export_table_csv($csv_content, 'reporting_index');
	exit;
}

if (!$export_csv) {
	Display::display_footer();
}
