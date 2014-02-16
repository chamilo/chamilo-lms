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

//Set Minimun Input Length = 3 used with Select2
$minimumInputLength = 3;

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
function count_coaches() {
	global $total_no_coaches;
	return $total_no_coaches;
}

function sort_users($a, $b) {
	return api_strcmp(trim(api_strtolower($a[$_SESSION['tracking_column']])), trim(api_strtolower($b[$_SESSION['tracking_column']])));
}

function rsort_users($a, $b) {
	return api_strcmp(trim(api_strtolower($b[$_SESSION['tracking_column']])), trim(api_strtolower($a[$_SESSION['tracking_column']])));
}

function count_sessions_coached() {
    global $count_sessions;
    return $count_sessions;
}

function sort_sessions($a, $b) {
    global $tracking_column;
    if ($a[$tracking_column] > $b[$tracking_column]) {
        return 1;
    } else {
        return -1;
    }
}

function rsort_sessions($a, $b) {
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
    Display::return_icon('printer.png', get_lang('Print'),'',ICON_SIZE_MEDIUM).'</a>';
echo '</span>';

if (!empty($session_id) && !in_array($display, array('accessoverview','lpprogressoverview','progressoverview','exerciseprogress', 'surveyoverview'))) {
    echo '<a href="index.php">'.Display::return_icon('back.png', get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
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
	echo Display::url(Display::return_icon('stats.png', get_lang('MyStats'),'',ICON_SIZE_MEDIUM),api_get_path(WEB_CODE_PATH)."auth/my_progress.php");
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

if (empty($session_id)) {

	// Getting courses followed by a coach (No session courses)
    $courses  = CourseManager::get_course_list_as_coach($user_id, false);

    if (isset($courses[0])) {
        $courses = $courses[0];
    }

    // Getting students from courses and courses in sessions (To show the total students that the user follows)
    $students = CourseManager::get_user_list_from_courses_as_coach($user_id);

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
        // Sessions for the coach
        $sessions = Tracking::get_sessions_coached_by_user($user_id);
    }

    // Courses for the user
    $count_courses = count($courses);

    // Sessions for the user
    $count_sessions = count($sessions);

    // Students
    $nb_students = count($students);

    $total_time_spent 			= 0;
    $total_courses 				= 0;
    $avg_total_progress 		= 0;
    $avg_results_to_exercises 	= 0;
    $nb_inactive_students 		= 0;
    $nb_posts = $nb_assignments = 0;

    if (!empty($students)) {
        foreach ($students as $student_id) {
            // inactive students
            $last_connection_date = Tracking::get_last_connection_date($student_id, true, true);

            if ($last_connection_date !== false) {
                if (time() - (3600 * 24 * 7) > $last_connection_date) {
                    $nb_inactive_students++;
                }
            } else {
                $nb_inactive_students++;
            }

            $total_time_spent += Tracking::get_time_spent_on_the_platform($student_id);
            $total_courses += Tracking::count_course_per_student($student_id);
            $avg_student_progress   = 0;
            $avg_student_score      = 0;
            $nb_courses_student     = 0;

            foreach ($courses as $course_code) {
                if (CourseManager :: is_user_subscribed_in_course($student_id, $course_code, true)) {
                    $nb_courses_student++;
                    $nb_posts 			   += Tracking :: count_student_messages($student_id, $course_code);
                    $nb_assignments 	   += Tracking :: count_student_assignments($student_id, $course_code);
                    $avg_student_progress  += Tracking :: get_avg_student_progress($student_id, $course_code);
                    $myavg_temp 			= Tracking :: get_avg_student_score($student_id, $course_code);

                    if (is_numeric($myavg_temp)) {
                        $avg_student_score += $myavg_temp;
                    }

                    if ($nb_posts !== null && $nb_assignments !== null && $avg_student_progress !== null && $avg_student_score !== null) {
                        //if one of these scores is null, it means that we had a problem connecting to the right database, so don't count it in
                        $nb_courses_student++;
                    }
                }
            }

            // average progress of the student
            $avg_student_progress = $nb_courses_student ?$avg_student_progress / $nb_courses_student:0;
            $avg_total_progress += $avg_student_progress;

            // average test results of the student
            $avg_student_score = $avg_student_score?$avg_student_score / $nb_courses_student:0;
            $avg_results_to_exercises += $avg_student_score;
        }
    }

    if ($nb_students > 0 && $view != 'admin') {

        // average progress
        $avg_total_progress = $avg_total_progress / $nb_students;
        // average results to the tests
        $avg_results_to_exercises = $avg_results_to_exercises / $nb_students;
        // average courses by student
        $avg_courses_per_student = round($count_courses / $nb_students, 2);
        // average time spent on the platform
        $avg_time_spent = $total_time_spent / $nb_students;
        // average assignments
        $nb_assignments = $nb_assignments / $nb_students;
        // average posts
        $nb_posts = $nb_posts / $nb_students;

        echo Display::page_subheader('<img src="'.api_get_path(WEB_IMG_PATH).'teachers.gif">&nbsp;'.get_lang('Overview'));

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

        echo Display::page_subheader(Display::return_icon('students.gif').'&nbsp;'.get_lang('Students').' ('.$nb_students.')');

        if ($export_csv) {
            //csv part
            $csv_content[] = array(get_lang('Students', ''));
            $csv_content[] = array(get_lang('InactivesStudents', ''), $nb_inactive_students );
            $csv_content[] = array(get_lang('AverageTimeSpentOnThePlatform', ''), $avg_time_spent);
            $csv_content[] = array(get_lang('AverageCoursePerStudent', ''), $avg_courses_per_student);
            $csv_content[] = array(get_lang('AverageProgressInLearnpath', ''), is_null($avg_total_progress) ? null : round($avg_total_progress, 2).'%');
            $csv_content[] = array(get_lang('AverageResultsToTheExercices', ''), is_null($avg_results_to_exercises) ? null : round($avg_results_to_exercises, 2).'%');
            $csv_content[] = array(get_lang('AveragePostsInForum', ''), $nb_posts);
            $csv_content[] = array(get_lang('AverageAssignments', ''), $nb_assignments);
            $csv_content[] = array();
        } else {

            $countActiveUsers = SessionManager::getCountUserTracking(null, 1);
            $countInactiveUsers = SessionManager::getCountUserTracking(null, 0);

            $lastConnectionDate = api_get_utc_datetime(strtotime('15 days ago'));

            $countSleepingTeachers = SessionManager::getTeacherTracking(api_get_user_id(), 1, $lastConnectionDate, true);
            $countSleepingStudents =SessionManager::getCountUserTracking(null, 1, $lastConnectionDate);

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
                            <td align="right">'.(is_null($avg_courses_per_student) ? '' : $avg_courses_per_student).'</td>
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
                    <a class="btn" href="'.api_get_path(WEB_CODE_PATH).'mySpace/student.php'.'">
                    '.get_lang('SeeStudentList').'
                    </a>
                 </div><br />';
        }
    } else {
        $avg_total_progress = null;
        $avg_results_to_exercises = null;
        $avg_courses_per_student = null;
        $avg_time_spent = null;
        $nb_assignments = null;
        $nb_posts = null;
    }
} else {
    // If is drh
	if ($is_drh) {
        $courses_of_the_platform = CourseManager::get_courses_followed_by_drh($user_id);
        $courses_from_session = SessionManager::get_course_list_by_session_id($session_id);

        $courses = array();
        foreach ($courses_from_session as $course_item) {
            if (api_drh_can_access_all_session_content()) {
                $courses[$course_item['code']] = $course_item['code'];
            } else {
                if (isset($courses_of_the_platform[$course_item['code']])) {
                    $courses[$course_item['code']] = $course_item['code'];
                }
            }
        }

        if (empty($courses)) {
            Display::display_warning_message(get_lang('NoResults'));
        }
	} else {
        $courses = Tracking::get_courses_followed_by_coach($user_id, $session_id);
    }

    //Courses for the user
    $count_courses = count($courses);

    //Sessions for the user
	$count_sessions = count($sessions);
}

if ($count_courses || $count_sessions) {
	//If we are in course
	if (empty($session_id)) {
		if ($count_courses) {
			$title = Display::return_icon('course.gif').' '.get_lang('Courses').' ('.$count_courses.') ';
		}
	} else {
		//If we are in Course Session
		$session_name = api_get_session_name($session_id);
		$title = Display::return_icon('session.png', get_lang('Session'), array(), ICON_SIZE_SMALL).' '.$session_name;
		$menu_items[] = '<a href="'.api_get_self().'?view=teacher">'.get_lang('TeacherInterface').'</a>';
	}
}

if ((api_is_allowed_to_create_course() || api_is_drh()) && in_array($view, array('teacher', 'drh'))) {

	// Courses
	if ($count_courses) {

		echo Display::page_subheader($title);

		$table = new SortableTable('courses_my_space', 'get_number_of_courses', array('MySpace','get_course_data'));
		$parameters['view'] = 'teacher';
		$parameters['class'] = 'data_table';
		$table->set_additional_parameters($parameters);
		$table->set_header(0, get_lang('CourseTitle'), false);
		$table->set_header(1, get_lang('NbStudents'), false);
		$table->set_header(2, get_lang('AvgTimeSpentInTheCourse').' '.Display :: return_icon('info3.gif', get_lang('TimeOfActiveByTraining'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table->set_header(3, get_lang('AvgStudentsProgress').' '.Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table->set_header(4, get_lang('AvgCourseScore').' '.Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table->set_header(5, get_lang('AvgExercisesScore').' '.Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table->set_header(6, get_lang('AvgMessages'), false);
		$table->set_header(7, get_lang('AverageAssignments'), false);
		$table->set_header(8, get_lang('Details'), false);

		$csv_content[] = array (
			get_lang('CourseTitle', ''),
			get_lang('NbStudents', ''),
			get_lang('AvgTimeSpentInTheCourse', ''),
			get_lang('AvgStudentsProgress', ''),
			get_lang('AvgCourseScore', ''),
			get_lang('AvgExercisesScore', ''),
			get_lang('AvgMessages', ''),
			get_lang('AverageAssignments', '')
		);
		$table->display();
	}

	// Display list of sessions

	if ($count_sessions > 0 && !isset($_GET['session_id'])) {
		echo Display::page_subheader(Display::return_icon('session.png').' '.get_lang('Sessions').' ('.$count_sessions.')');

        $url = api_get_path(WEB_AJAX_PATH).'model.ajax.php?a=get_sessions_tracking';

        //The order is important you need to check the the $column variable in the model.ajax.php file
        $columns = array(
            get_lang('Title'),
            get_lang('Date'),
            get_lang('NbCoursesPerSession'),
            get_lang('NbStudentPerSession'),
            get_lang('Details')
        );

        // Column config
        $columnModel   = array(
            array('name'=>'name',               'index'=>'name',        'width'=>'255',   'align'=>'left'),
            array('name'=>'date',                'index'=>'date', 'width'=>'150',  'align'=>'left','sortable'=>'false'),
            array('name'=>'course_per_session',  'index'=>'course_per_session',     'width'=>'150','sortable'=>'false'),
            array('name'=>'student_per_session', 'index'=>'student_per_session',     'width'=>'100','sortable'=>'false'),
            array('name'=>'details',             'index'=>'details',     'width'=>'100','sortable'=>'false'),
        );

        $extraParams = array(
            'autowidth' => 'true',
            'height' => 'auto'
        );

        $js = '<script>
        $(function() {
            '.Display::grid_js('session_tracking', $url, $columns, $columnModel, $extraParams, array(), null, true).'
        });
        </script>';

		$nb_sessions_past = $nb_sessions_current = 0;
		$courses = array();

		foreach ($sessions as $session) {
            $visibility = api_get_session_visibility($session['id']);
            if ($visibility == SESSION_AVAILABLE) {
                $nb_sessions_current ++;
            } else {
                $nb_sessions_past++;
            }
			$courses = array_merge($courses, Tracking::get_courses_list_from_session($session['id']));
		}

        $nb_courses_per_session     = null;
        $nb_students_per_session    = null;

		if ($count_sessions > 0) {
			$nb_courses_per_session = round(count($courses) / $count_sessions, 2);
			$nb_students_per_session = round($nb_students / $count_sessions, 2);
		}

		if ($export_csv) {
			//csv part
			$csv_content[] = array(get_lang('Sessions', ''));
			$csv_content[] = array(get_lang('NbActiveSessions', '').';'.$nb_sessions_current);
			$csv_content[] = array(get_lang('NbInactiveSessions', '').';'.$nb_sessions_past);
            $csv_content[] = array(get_lang('NbCoursesPerSession', '').';'.$nb_courses_per_session);
			$csv_content[] = array(get_lang('NbStudentPerSession', '').';'.$nb_students_per_session);
			$csv_content[] = array();
		} else {
			echo '
			<div class="report_section">
				<table class="table table-bordered">
					<tr>
						<td>'.get_lang('NbActiveSessions').'</td>
						<td align="right">'.$nb_sessions_current.'</td>
					</tr>
					<tr>
						<td>'.get_lang('NbInactiveSessions').'</td>
						<td align="right">'.$nb_sessions_past.'</td>
					</tr>
				</table>
			</div>';
		}
        echo $js;
        echo Display::grid_html('session_tracking');
    }
}

if ($is_platform_admin && in_array($view, array('admin')) && $display != 'yourstudents') {

	echo '<a href="'.api_get_self().'?view=admin&amp;display=coaches">'.get_lang('DisplayCoaches').'</a> | ';
	echo '<a href="'.api_get_self().'?view=admin&amp;display=useroverview">'.get_lang('DisplayUserOverview').'</a>';
	if ($display == 'useroverview') {
		echo ' ( <a href="'.api_get_self().'?view=admin&amp;display=useroverview&amp;export=options">'.get_lang('ExportUserOverviewOptions').'</a> )';
	}
	echo ' | <a href="'.api_get_self().'?view=admin&amp;display=sessionoverview">'.get_lang('DisplaySessionOverview').'</a>';
	echo ' | <a href="'.api_get_self().'?view=admin&amp;display=accessoverview">'.get_lang('DisplayAccessOverview').'</a>';
    echo ' | <a href="'.api_get_self().'?view=admin&amp;display=surveyoverview">'.get_lang('DisplaySurveyOverview').'</a>';
    echo ' | <a href="'.api_get_self().'?view=admin&amp;display=lpprogressoverview">'.get_lang('DisplayLpProgressOverview').'</a>';
    echo ' | <a href="'.api_get_self().'?view=admin&amp;display=progressoverview">'.get_lang('DisplayProgressOverview').'</a>';
    echo ' | <a href="'.api_get_self().'?view=admin&amp;display=exerciseprogress">'.get_lang('DisplayExerciseProgress').'</a>';
	echo ' | <a href="'.api_get_self().'?view=admin&amp;display=courseoverview">'.get_lang('DisplayCourseOverview').'</a>';
    echo ' | <a href="'.api_get_path(WEB_CODE_PATH).'tracking/question_course_report.php?view=admin">'.get_lang('LPQuestionListResults').'</a>';
    echo ' | <a href="'.api_get_path(WEB_CODE_PATH).'tracking/course_session_report.php?view=admin">'.get_lang('LPExerciseResultsBySession').'</a>';
	echo '<br /><br />';


    if ($is_platform_admin && $view == 'admin' && in_array($display, array('accessoverview','lpprogressoverview', 'progressoverview', 'exerciseprogress', 'surveyoverview'))) {
        //selft script
        $self       = api_get_self();
        //ajax path
        $ajax_path  = api_get_path(WEB_AJAX_PATH);
        //script initiatizion
        $script     = '';

        //Session Filter
        $sessionFilter = new FormValidator('session_filter', 'get', '', '', array('class'=> 'form-horizontal'), false);

        switch ($display) {
            case 'coaches':
               $tool_name = get_lang('DisplayCoaches');
                break;
            case 'useroverview':
               $tool_name = get_lang('DisplayUserOverview');
                break;
            case 'sessionoverview':
               $tool_name = get_lang('DisplaySessionOverview');
                break;
            case 'accessoverview':
               $tool_name = get_lang('DisplayAccessOverview');
                break;
            case 'surveyoverview':
               $tool_name = get_lang('DisplaySurveyOverview');
                break;
            case 'lpprogressoverview':
               $tool_name = get_lang('DisplayLpProgressOverview');
                break;
            case 'progressoverview':
               $tool_name = get_lang('DisplayProgressOverview');
                break;
            case 'exerciseprogress':
               $tool_name = get_lang('DisplayExerciseProgress');
                break;
            case 'courseoverview':
               $tool_name = get_lang('DisplayCourseOverview');
                break;
        }

        $sessionFilter->addElement('header', '', $tool_name);
        $a = 'search_course';
        $an = 'search_session';
        $sessionList = array();
        $courseList = array();
        $sessionId = isset($_GET['session_id']) ? $_GET['session_id'] : null;
        $courseId = isset($_GET['course_id']) ? $_GET['course_id'] : null;

        if (!empty($sessionId)) {
            $sessionList = array();
            $sessionInfo = SessionManager::fetch($sessionId);
            $sessionList[] = array('id' => $sessionInfo['id'], 'text' => $sessionInfo['name']);
            $a = 'search_course_by_session';
        }

        if (!empty($courseId)) {
            $courseList = array();
            $courseInfo = api_get_course_info_by_id($courseId);
            $courseList[] = array('id' => $courseInfo['real_id'], 'text' => $courseInfo['name']);
            $an = 'search_session_by_course';
        }

        $url = $ajax_path . 'session.ajax.php?a='. $an . '&course_id=' . $_GET['course_id'];
        $sessionFilter->addElement('select_ajax', 'session_name', get_lang('SearchSession'), null, array('url' => $url, 'defaults' => $sessionList, 'width' => '400px', 'minimumInputLength' => $minimumInputLength));

        //course filter
        /*
        $a = 'search_course';
        if (!empty($_GET['session_id'])) {
           $a = 'search_course_by_session';
        }
        */
        $url = $ajax_path . 'course.ajax.php?a='. $a .'&session_id=' . $_GET['session_id'];
        $sessionFilter->addElement('select_ajax', 'course_name', get_lang('SearchCourse'), null, array('url' => $url, 'defaults' => $courseList, 'width' => '400px', 'minimumInputLength' => $minimumInputLength));
        
        //Exercise filter    
        if (in_array($display, array('exerciseprogress'))) {

            $url = $ajax_path .'course.ajax.php?a=search_exercise_by_course&session_id=' . $_GET['session_id'] . '&course_id=' . $_GET['course_id'];
            $exerciseList = array();
            $exerciseId = isset($_GET['exercise_id']) ? $_GET['exercise_id'] : null;
            if (!empty($exerciseId)) {
                $exerciseList = array();
                $exerciseInfo = current(get_exercise_by_id($exerciseId, $_GET['course_id']));
                $exerciseList[] = array('id' => $exerciseInfo['id'], 'text' => api_html_entity_decode($exerciseInfo['title']));
            }
            $sessionFilter->addElement('select_ajax', 'exercise_name', get_lang('SearchExercise'), null, array('url' => $url, 'defaults' => $exerciseList, 'width' => '400px', 'minimumInputLength' => $minimumInputLength));

        }

        //survey filter
        if (in_array($display, array('surveyoverview'))) {

            $url = $ajax_path . 'course.ajax.php?a=search_survey_by_course&session_id=' . $_GET['session_id'] . '&course_id=' . $_GET['course_id'] . '&survey_id=' . $_GET['survey_id'];
            $surveyList = array();
            $surveyId = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : null;
            $courseId = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
            if (!empty($surveyId)) {
                $course = api_get_course_info_by_id($courseId);
                $surveyList = array();
                $surveyInfo = survey_manager::get_survey($surveyId, 0, $course['code']);
                $surveyInfo['title'] .= ($surveyInfo['anonymous'] == 1) ? ' (' . get_lang('Anonymous') . ')': '';
                $surveyList[] = array('id' => $surveyInfo['survey_id'], 'text' => strip_tags(api_html_entity_decode($surveyInfo['title'])));
            }
            $sessionFilter->addElement('select_ajax', 'survey_name', get_lang('SearchSurvey'), null, array('url' => $url, 'defaults' => $surveyList, 'width' => '400px', 'minimumInputLength' => $minimumInputLength));

        }

        //Student and profile filter
        if (in_array($display, array('accessoverview'))) {

            $url = $ajax_path . 'course.ajax.php?a=search_user_by_course&session_id=' . $_GET['session_id'] . '&course_id=' . $_GET['course_id'];
            $studentList = array();
            $studentId = isset($_GET['student_id']) ? $_GET['student_id'] : null;
            if (!empty($studentId)) {
                $studentList = array();
                $studentInfo = UserManager::get_user_info_by_id($studentId);
                $studentList[] = array('id' => $studentInfo['user_id'], 'text' => $studentInfo['username'] . ' (' . $studentInfo['firstname'] . ' ' . $studentInfo['lastname'] . ')');
            }

            $sessionFilter->addElement('select_ajax', 'student_name', get_lang('SearchStudent'), null, array('url' => $url, 'defaults' => $studentList, 'width' => '400px', 'class' => 'pull-right', 'minimumInputLength' => $minimumInputLength));
            $options = array(
                ''              => get_lang('Select'),
                STUDENT         => get_lang('Student'),
                COURSEMANAGER   => get_lang('CourseManager'),
                DRH             => get_lang('Drh'),
                );
            $sessionFilter->addElement('select', 'profile', get_lang('Profile'),$options, array('id' => 'profile', 'class' => 'pull-left'));

            $script = '
                $("#student_name").on("change", function() {
                        var date_to     = $("#date_to").val();
                        var date_from   = $("#date_from").val();
                        var sessionId   = $("#session_name").val();
                        var courseId    = $("#course_name").val();
                        var studentId   = $("#student_name").val();
                        window.location = "'.$self.'?view=admin&display='.$display.'&session_id="+sessionId+"&course_id="+courseId+"&student_id="+studentId+"&date_to="+date_to+"&date_from="+date_from;
                    });
                    $("#profile").on("change", function() {
                        var date_to     = $("#date_to").val();
                        var date_from   = $("#date_from").val();
                        var sessionId   = $("#session_name").val();
                        var courseId    = $("#course_name").val();
                        var profile     = $("#profile").val();
                        window.location = "'.$self.'?view=admin&display='.$display.'&session_id="+sessionId+"&course_id="+courseId+"&profile="+profile+"&date_to="+date_to+"&date_from="+date_from;
                    });
                    $( "#date_from, #date_to").datepicker({
                        dateFormat:  "yy-mm-dd",
                        onSelect: function( selectedDate ) {
                            var filled = areBothFilled();
                            if (filled) {
                                var date_to     = $("#date_to").val();
                                var date_from   = $("#date_from").val();
                                var sessionId   = $("#session_name").val();
                                var courseId    = $("#course_name").val();
                                var studentId   = $("#student_name").val();
                                window.location = "'.$self.'?view=admin&display='.$display.'&session_id="+sessionId+"&course_id="+courseId+"&student_id="+studentId+"&date_to="+date_to+"&date_from="+date_from;
                            }
                        }
                    });';
        }

        //progress overview and Learning Path progress overview
        /*if (in_array($display, array('progressoverview', 'lpprogressoverview'))) {
            $script = '
                $( "#date_from, #date_to").datepicker({
                    dateFormat:  "yy-mm-dd",
                    onSelect: function( selectedDate ) {
                        var filled = areBothFilled();
                        if (filled) {
                            var date_to     = $("#date_to").val();
                            var date_from   = $("#date_from").val();
                            var sessionId   = $("#session_name").val();
                            var courseId    = $("#course_name").val();
                            window.location = "'.$self.'?view=admin&display='.$display.'&session_id="+sessionId+"&course_id="+courseId+"&date_to="+date_to+"&date_from="+date_from;
                        }
                    }
                });
            ';
        }*/

        //date filter
        if (!in_array($display, array('surveyoverview', 'progressoverview', 'lpprogressoverview'))) {
            $sessionFilter->addElement('text', 'from', get_lang('From'), array('id' => 'date_from', 'value' => (!empty($_GET['date_from']) ? $_GET['date_from'] : ''), 'style' => 'width:75px' ));
            $sessionFilter->addElement('text', 'to', get_lang('Until'), array('id' => 'date_to', 'value' => (!empty($_GET['date_to']) ? $_GET['date_to'] : ''), 'style' => 'width:75px' ));
        }
        $sessionFilter->addElement('submit', '', get_lang('Generate'), 'id="generateReport"');

        echo '<div class="">';
        echo $sessionFilter->return_form();
        echo '</div>';
        $a = 'search_course';
        if (!empty($_GET['session_id'])) {
           $a = 'search_course_by_session';
        }
        $url = $ajax_path . 'course.ajax.php?a='. $a .'&session_id=' . $_GET['session_id'];
        echo '<script>
        $(function() {
            if (display == "lpprogressoverview" || display == "progressoverview" || display == "surveyoverview") {
                if (!isEmpty($("#session_name").val())) {
                    $("#course_name").select2("readonly", true);
                }
                if (!isEmpty($("#course_name").val())) {
                    $("#session_name").select2("readonly", true);
                }
            }

            var display = "' . $display . '"; 
            $("#generateReport").click(function(e){
                url = "'.$self.'?view=admin&display='.$display.'";
                if (!isEmpty($("#session_name").val())) {
                    url = url + "&session_id=" + $("#session_name").val();
                }
                if (!isEmpty($("#course_name").val())) {
                    url = url + "&course_id=" + $("#course_name").val();
                }
                if (!isEmpty($("#student_name").val())) {
                    url = url + "&student_id=" + $("#student_name").val();
                }
                if (!isEmpty($("#profile").val())) {
                    url = url + "&profile=" + $("#profile").val();
                }
                if (!isEmpty($("#survey_name").val())) {
                    url = url + "&survey_id=" + $("#survey_name").val();
                }
                if (!isEmpty($("#exercise_name").val())) {
                    url = url + "&exercise_id=" + $("#exercise_name").val();
                }
                if (!isEmpty($("#date_from").val()) && !isEmpty($("#date_to").val())) {
                    url = url + "&date_from=" + $("#date_from").val() + "&date_to=" + $("#date_to").val();
                }
                window.location = url;
                e.preventDefault();
            });
            $( "#date_from, #date_to").datepicker({
                dateFormat:  "yy-mm-dd"
            });
                        
            $("#session_name").on("change", function() {
                var sessionId = $("#session_name").val();
                var courseId  = $("#course_name").val();
                console.log("session:"+sessionId);
                console.log("course:"+courseId);
                if (isEmpty(sessionId)) {
                        select2("#course_name", "' .  $ajax_path . 'course.ajax.php?a=search_course");
                    if (isEmpty(courseId)) {
                        select2("#session_name", "' .  $ajax_path . 'session.ajax.php?a=search_session");
                    } else {
                        select2("#session_name", "' .  $ajax_path . 'session.ajax.php?a=search_session_by_course&course_id=" + courseId);
                    }
                } else {
                    select2("#course_name", "' .  $ajax_path . 'course.ajax.php?a=search_course_by_session&session_id=" + sessionId);
                }
                //window.location = "'.$self.'?view=admin&display='.$display.'&session_id="+sessionId;
                /*
                if (isEmpty(courseId)) {
                    select2("#course_name", "'. $ajax_path . 'course.ajax.php?a=search_course_by_session&session_id=" + sessionId);
                }
                */
            });

            $("#course_name").on("change", function() {
                var sessionId = $("#session_name").val();
                var courseId = $("#course_name").val();
                var display = "' . $display . '";
                console.log("session:"+sessionId);
                console.log("course:"+courseId);
                if (isEmpty(courseId)) {
                    select2("#session_name", "' .  $ajax_path . 'session.ajax.php?a=search_session");
                    if (isEmpty(sessionId)) {
                        select2("#course_name", "' .  $ajax_path . 'course.ajax.php?a=search_course");
                    } else {
                        select2("#course_name", "' .  $ajax_path . 'course.ajax.php?a=search_course_by_session&session_id=" + sessionId);
                    }
                } else {
                    select2("#session_name", "' .  $ajax_path . 'session.ajax.php?a=search_session_by_course&course_id=" + courseId);
                }
                if (display == "accessoverview" || display == "exerciseprogress") {
                    window.location = "'.$self.'?view=admin&display='.$display.'&session_id="+sessionId+"&course_id="+courseId;
                }
                /*
                if (isEmpty(sessionId)) {
                    select2("#session_name", "' .  $ajax_path . 'session.ajax.php?a=search_session_by_course&course_id=" + courseId);
                }
                */
                if (typeof $("#survey_name") == "object") {
                    var surveyId = $("#survey_name").val();
                    select2("#survey_name", "' . $ajax_path . 'course.ajax.php?a=search_survey_by_course&session_id=" + sessionId + "&course_id=" + courseId + "&survey_id=" + surveyId);
                }
                if (typeof $("#exercise_name") == "object") {
                    var exerciseId = $("#exercise_name").val();
                    select2("#exercise_name", "' . $ajax_path . 'course.ajax.php?a=search_exercise_by_course&session_id=" + sessionId + "&course_id=" + courseId + "&exercise_id=" + exerciseId);
                }
                /*if (typeof $("#student_name") == "object") {
                    var studentId = $("#student_name").val();
                    urlajax = "' . $ajax_path . 'course.ajax.php?a=search_user_by_course&course_id=" + courseId + "&student_id=" + studentId
                    if (!isEmpty(sessionId)) {
                        urlajax = urlajax + "&session_id=" + sessionId;
                        select2("#course_name", "' .  $ajax_path . 'course.ajax.php?a=search_course_by_session&session_id=" + sessionId);
                    }
                    select2("#student_name", urlajax);
                }*/
            });
            ' . $script . '
        });
        function areBothFilled() {
            var returnValue = false;
            if ((document.getElementById("date_from").value != "") && (document.getElementById("date_to").value != "")){
                returnValue = true;
            }
            return returnValue;
        }
        function isEmpty(str) {
            return (!str || 0 === str.length);
        }
        function select2(divId, url) {
            if (typeof $(divId).select2 == "function" && isEmpty($(divId).val())) {
                $(divId).select2("destroy");
            }
            var text = $(divId).select2("data").text;
            var id = $(divId).val();
            $(divId).select2({
                placeholder: "Elegir una opción",
                allowClear: true,
                width: "400px",
                minimumInputLength: ' . $minimumInputLength . ',
                // instead of writing the function to execute the request we use Select2s convenient helper
                ajax: {
                    url: url,
                    dataType: "json",
                    data: function (term, page) {
                        return {
                            q: term, // search term
                            page_limit: 10,
                        };
                    },
                    results: function (data, page) { // parse the results into the format expected by Select2.
                                // since we are using custom formatting functions we do not need to alter remote JSON data
                        return {
                            results: data
                        };
                    }
                },
                initSelection: function (item, callback) {
                    var data = { id: id, text: text };
                    callback(data);
                },
                formatResult: function (item) { return ("<div>" + item.text + "</div>"); },
                formatSelection: function (item) { return (item.text); },
                escapeMarkup: function (m) { return m; }
            });
            $(divId).select2("readonly", false);
        }
        </script>';

    }

	if ($display === 'useroverview') {
		MySpace::display_tracking_user_overview();
	} else if($display == 'sessionoverview') {
		MySpace::display_tracking_session_overview();
	} else if($display == 'accessoverview') {
        if (!empty($_GET['course_id'])) {
            if(!empty($_GET['date_to']) && (!empty($_GET['date_from']))) {
                if (!empty($_GET['student_id'])) {
                    echo MySpace::display_tracking_access_overview($_GET['session_id'], $_GET['course_id'], $_GET['student_id'], '',  $_GET['date_from'], $_GET['date_to']);
                } else if (!empty($_GET['profile'])) {
                    echo MySpace::display_tracking_access_overview($_GET['session_id'], $_GET['course_id'], '', $_GET['profile'], $_GET['date_from'], $_GET['date_to']);
                } else {
                    Display::display_warning_message(get_lang('ChooseStudentOrProfile'));
                }
            } else {
                Display::display_warning_message(get_lang('ChooseStartDateAndEndDate'));
            }
        } else {
            Display::display_warning_message(get_lang('ChooseCourse'));
        }
    } else if($display == 'lpprogressoverview') {
        if (!empty($_GET['session_id'])) {
            if (!empty($_GET['course_id'])) {
                echo MySpace::display_tracking_lp_progress_overview(intval($_GET['session_id']), intval($_GET['course_id']), $_GET['date_from'], $_GET['date_to']);
            } else {
                Display::display_warning_message(get_lang('ChooseCourse'));
            }
        } else {
            Display::display_warning_message(get_lang('ChooseSession'));
        }
    } else if($display == 'progressoverview') {
        if (!empty($_GET['session_id'])) {
            if (!empty($_GET['course_id'])) {
                echo MySpace::display_tracking_progress_overview(intval($_GET['session_id']), intval($_GET['course_id']), $_GET['date_from'], $_GET['date_to']);
            } else {
                Display::display_warning_message(get_lang('ChooseCourse'));
            }
        } else {
            Display::display_warning_message(get_lang('ChooseSession'));
        }
    } else if($display == 'exerciseprogress') {
        if (!empty($_GET['course_id'])) {
            if (!empty($_GET['exercise_id'])) {
                echo MySpace::display_tracking_exercise_progress_overview(intval($_GET['session_id']), intval($_GET['course_id']), intval($_GET['exercise_id']), $_GET['date_from'], $_GET['date_to']);
            } else {
                Display::display_warning_message(get_lang('ChooseExercise'));
            }
        } else {
            Display::display_warning_message(get_lang('ChooseCourse'));
        }
    } else if($display == 'surveyoverview') {
        if (!empty($_GET['session_id'])) {
            if (!empty($_GET['course_id'])) {
                if (!empty($_GET['survey_id'])) {
                    echo MySpace::display_survey_overview(intval($_GET['session_id']), intval($_GET['course_id']), intval($_GET['survey_id']), $_GET['date_from'], $_GET['date_to']);
                } else {
                    Display::display_warning_message(get_lang('ChooseSurvey'));
                }
            } else {
                Display::display_warning_message(get_lang('ChooseCourse'));
            }
        } else {
            Display::display_warning_message(get_lang('ChooseSession'));
        }
	} else if($display == 'courseoverview') {
		MySpace::display_tracking_course_overview();
	} else {
		if ($export_csv) {
			$is_western_name_order = api_is_western_name_order(PERSON_NAME_DATA_EXPORT);
		} else {
			$is_western_name_order = api_is_western_name_order();
		}
		$sort_by_first_name = api_sort_by_first_name();
		$tracking_column = isset($_GET['tracking_list_coaches_column']) ? $_GET['tracking_list_coaches_column'] : ($is_western_name_order xor $sort_by_first_name) ? 1 : 0;
		$tracking_direction = (isset($_GET['tracking_list_coaches_direction']) && in_array(strtoupper($_GET['tracking_list_coaches_direction']), array('ASC', 'DESC', 'ASCENDING', 'DESCENDING', '0', '1'))) ? $_GET['tracking_list_coaches_direction'] : 'DESC';
		// Prepare array for column order - when impossible, use some of user names.
		if ($is_western_name_order) {
			$order = array(0 => 'firstname', 1 => 'lastname', 2 => ($sort_by_first_name ? 'firstname' : 'lastname'), 3 => 'login_date', 4 => ($sort_by_first_name ? 'firstname' : 'lastname'), 5 => ($sort_by_first_name ? 'firstname' : 'lastname'));
		} else {
			$order = array(0 => 'lastname', 1 => 'firstname', 2 => ($sort_by_first_name ? 'firstname' : 'lastname'), 3 => 'login_date', 4 => ($sort_by_first_name ? 'firstname' : 'lastname'), 5 => ($sort_by_first_name ? 'firstname' : 'lastname'));
		}
		$table = new SortableTable('tracking_list_coaches_myspace', 'count_coaches', null, ($is_western_name_order xor $sort_by_first_name) ? 1 : 0);
		$parameters['view'] = 'admin';
		$table->set_additional_parameters($parameters);
		if ($is_western_name_order) {
			$table->set_header(0, get_lang('FirstName'), true);
			$table->set_header(1, get_lang('LastName'), true);
		} else {
			$table->set_header(0, get_lang('LastName'), true);
			$table->set_header(1, get_lang('FirstName'), true);
		}
		$table->set_header(2, get_lang('TimeSpentOnThePlatform'), false);
		$table->set_header(3, get_lang('LastConnexion'), false);
		$table->set_header(4, get_lang('NbStudents'), false);
		$table->set_header(5, get_lang('CountCours'), false);
		$table->set_header(6, get_lang('NumberOfSessions'), false);
		$table->set_header(7, get_lang('Sessions'), false);

		if ($is_western_name_order) {
			$csv_header[] = array (
				get_lang('FirstName', ''),
				get_lang('LastName', ''),
				get_lang('TimeSpentOnThePlatform', ''),
				get_lang('LastConnexion', ''),
				get_lang('NbStudents', ''),
				get_lang('CountCours', ''),
				get_lang('NumberOfSessions', '')
			);
		} else {
			$csv_header[] = array (
				get_lang('LastName', ''),
				get_lang('FirstName', ''),
				get_lang('TimeSpentOnThePlatform', ''),
				get_lang('LastConnexion', ''),
				get_lang('NbStudents', ''),
				get_lang('CountCours', ''),
				get_lang('NumberOfSessions', '')
			);
		}

		$tbl_track_login = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LOGIN);

		$sqlCoachs = "SELECT DISTINCT scu.id_user as id_coach, user_id, lastname, firstname, MAX(login_date) as login_date
			FROM $tbl_user, $tbl_session_course_user scu, $tbl_track_login
			WHERE scu.id_user=user_id AND scu.status=2  AND login_user_id=user_id
			GROUP BY user_id ";

		if ($_configuration['multiple_access_urls']) {
			$tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1) {
				$sqlCoachs = "SELECT DISTINCT scu.id_user as id_coach, user_id, lastname, firstname, MAX(login_date) as login_date
					FROM $tbl_user, $tbl_session_course_user scu, $tbl_track_login , $tbl_session_rel_access_url session_rel_url
					WHERE scu.id_user=user_id AND scu.status=2 AND login_user_id=user_id AND access_url_id = $access_url_id AND session_rel_url.session_id=id_session
					GROUP BY user_id ";
			}
		}
		if (!empty($order[$tracking_column])) {
			$sqlCoachs .= "ORDER BY ".$order[$tracking_column]." ".$tracking_direction;
		}

		$result_coaches = Database::query($sqlCoachs);
		$total_no_coaches = Database::num_rows($result_coaches);
		$global_coaches = array();
		while ($coach = Database::fetch_array($result_coaches)) {
			$global_coaches[$coach['user_id']] = $coach;
		}

		$sql_session_coach = 'SELECT session.id_coach, user_id, lastname, firstname, MAX(login_date) as login_date
			FROM '.$tbl_user.','.$tbl_sessions.' as session,'.$tbl_track_login.'
			WHERE id_coach=user_id AND login_user_id=user_id
			GROUP BY user_id
			ORDER BY login_date '.$tracking_direction;

		if ($_configuration['multiple_access_urls']) {
			$tbl_session_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1) {
				$sql_session_coach = 'SELECT session.id_coach, user_id, lastname, firstname, MAX(login_date) as login_date
					FROM '.$tbl_user.','.$tbl_sessions.' as session,'.$tbl_track_login.' , '.$tbl_session_rel_access_url.' as session_rel_url
					WHERE id_coach=user_id AND login_user_id=user_id  AND access_url_id = '.$access_url_id.' AND  session_rel_url.session_id=session.id
					GROUP BY user_id
					ORDER BY login_date '.$tracking_direction;
			}
		}

		$result_sessions_coach = Database::query($sql_session_coach);
		$total_no_coaches += Database::num_rows($result_sessions_coach);
		while ($coach = Database::fetch_array($result_sessions_coach)) {
			$global_coaches[$coach['user_id']] = $coach;
		}

		$all_datas = array();

		foreach ($global_coaches as $id_coach => $coaches) {

			$time_on_platform   = api_time_to_hms(Tracking :: get_time_spent_on_the_platform($coaches['user_id']));
			$last_connection    = Tracking :: get_last_connection_date($coaches['user_id']);
			$nb_students        = count(Tracking :: get_student_followed_by_coach($coaches['user_id']));
			$nb_courses         = count(Tracking :: get_courses_followed_by_coach($coaches['user_id']));
			$nb_sessions        = count(Tracking :: get_sessions_coached_by_user($coaches['user_id']));

			$table_row = array();
			if ($is_western_name_order) {
				$table_row[] = $coaches['firstname'];
				$table_row[] = $coaches['lastname'];
			} else {
				$table_row[] = $coaches['lastname'];
				$table_row[] = $coaches['firstname'];
			}
			$table_row[] = $time_on_platform;
			$table_row[] = $last_connection;
			$table_row[] = $nb_students;
			$table_row[] = $nb_courses;
			$table_row[] = $nb_sessions;
			$table_row[] = '<a href="session.php?id_coach='.$coaches['user_id'].'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
			$all_datas[] = $table_row;

			if ($is_western_name_order) {
				$csv_content[] = array(
					api_html_entity_decode($coaches['firstname'], ENT_QUOTES, $charset),
					api_html_entity_decode($coaches['lastname'], ENT_QUOTES, $charset),
					$time_on_platform,
					$last_connection,
					$nb_students,
					$nb_courses,
					$nb_sessions
				);
			} else {
				$csv_content[] = array(
					api_html_entity_decode($coaches['lastname'], ENT_QUOTES, $charset),
					api_html_entity_decode($coaches['firstname'], ENT_QUOTES, $charset),
					$time_on_platform,
					$last_connection,
					$nb_students,
					$nb_courses,
					$nb_sessions
				);
			}
		}

		if ($tracking_column != 3) {
			if ($tracking_direction == 'DESC') {
				usort($all_datas, 'rsort_users');
			} else {
				usort($all_datas, 'sort_users');
			}
		}

		if ($export_csv && $tracking_column != 3) {
			usort($csv_content, 'sort_users');
		}
		if ($export_csv) {
			$csv_content = array_merge($csv_header, $csv_content);
		}

		foreach ($all_datas as $row) {
			$table->addRow($row, 'align="right"');
		}
		$table->display();
	}
}

// Send the csv file if asked
if ($export_csv) {
	ob_end_clean();
	Export :: export_table_csv($csv_content, 'reporting_index');
	exit;
}

//footer
if (!$export_csv) {
	Display::display_footer();
}

/**
 * Get number of courses for sortable with pagination
 * @return int
 */
function get_number_of_courses() {
	global $courses;
	return count($courses);
}
