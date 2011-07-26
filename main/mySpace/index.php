<?php
/* For licensing terms, see /license.txt */

$language_file = array('registration', 'index', 'tracking');

// resetting the course id
$cidReset = true;

require_once '../inc/global.inc.php';

// including additional libraries
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once 'myspace.lib.php';

// the section (for the tabs)
$this_section = SECTION_TRACKING;
unset($_SESSION['this_section']);//for hmtl editor repository

ob_start();

$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
$csv_content = array();
$nameTools = get_lang('MySpace');

$user_id = api_get_user_id();
$nb_sessions = 0;

// access control
api_block_anonymous_users();
if (!$export_csv) {
	Display :: display_header($nameTools);
} else {
	if ($_GET['view'] == 'admin') {
		if($_GET['display'] == 'useroverview') {
			MySpace::export_tracking_user_overview();
			exit;
		} else if($_GET['display'] == 'sessionoverview') {
			MySpace::export_tracking_session_overview();
			exit;
		} else if($_GET['display'] == 'courseoverview') {
			MySpace::export_tracking_course_overview();
			exit;
		}
	}
}

// Database table definitions
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_class 					= Database :: get_main_table(TABLE_MAIN_CLASS);
$tbl_sessions 				= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course 		= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_user 			= Database :: get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_admin					= Database :: get_main_table(TABLE_MAIN_ADMIN);
$tbl_track_cours_access 	= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);


/* * FUNCTIONS */
/*
function count_teacher_courses() {
	global $nb_teacher_courses;
	return $nb_teacher_courses;
}*/

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
	global $nb_sessions;
	return $nb_sessions;
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

/* * MAIN CODE  */

$is_coach 			= api_is_coach();
$is_platform_admin 	= api_is_platform_admin();
$is_drh 			= api_is_drh();
$is_session_admin 	= api_is_session_admin();

if ($is_session_admin) {
	header('location:session.php');
	exit;
}

// Get views
$views = array('admin', 'teacher', 'coach', 'drh');
$view = 'teacher';
if (isset($_GET['view']) && in_array($_GET['view'], $views)) {
	$view = $_GET['view'];
}

$menu_items = array();
$nb_teacher_courses = 0;
$title = null;
global $_configuration;



// interbreadcrumbs
// && isset($_GET['display']) && $_GET['display'] != 'yourstudents'

if (api_is_allowed_to_create_course()) {
	$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']): 0;
	if (!empty($session_id)) {
		$courses = Tracking::get_courses_followed_by_coach($user_id, $session_id);
	} else {
		$courses = CourseManager::get_course_list_of_user_as_course_admin($user_id);
	}

	$nb_teacher_courses = count($courses);	

	$sessions = Tracking::get_sessions_coached_by_user($user_id);	
	$nb_sessions = count($sessions);

	if ($nb_teacher_courses || $nb_sessions) {	    
		if (!$is_coach && !$is_platform_admin) {
			$view = 'teacher';
		}
		if ($view == 'teacher' && empty($session_id)) {
			$menu_items[] = get_lang('TeacherInterface');

			if ($nb_teacher_courses) {
				$title = get_lang('YourCourseList');
			}

		} else {
			if (!empty($session_id)) {
				$session_name = api_get_session_name($session_id);
				$title = Display::return_icon('session.png', get_lang('Session'), array(), 22).' '.$session_name;
			}            
			$menu_items[] = '<a href="'.api_get_self().'?view=teacher">'.get_lang('TeacherInterface').'</a>';            
		}
	}
}

if ($is_coach && $_GET['display'] != 'yourstudents') {
	if ($nb_teacher_courses == 0 && !$is_platform_admin) {
		$view = 'coach';
	}
	if ($view == 'coach') {
		$menu_items[] = get_lang('CoachInterface');
		$title = get_lang('YourStatistics');
	} else {
		$menu_items[] = '<a href="'.api_get_self().'?view=coach">'.get_lang('CoachInterface').'</a>';        
	}
}

if ($is_platform_admin && isset($_GET['display']) && $_GET['display'] != 'yourstudents') {
	if ($nb_teacher_courses == 0 && $nb_sessions == 0) {
		$view = 'admin';
	}
	if ($view == 'admin') {
		$menu_items[] = get_lang('AdminInterface');
		$title = get_lang('CoachList');
        $menu_items[] = '<a href="'.api_get_path(WEB_CODE_PATH).'tracking/exams.php">'.get_lang('ExamTracking').'</a>';
	} else {
		$menu_items[] = '<a href="'.api_get_self().'?view=admin">'.get_lang('AdminInterface').'</a>';
        $menu_items[] = '<a href="'.api_get_path(WEB_CODE_PATH).'tracking/exams.php">'.get_lang('ExamTracking').'</a>';
	}
}

if ($is_drh || (isset($_GET['display']) && $_GET['display'] == 'yourstudents')) {
	$view = 'drh';
	$menu_items[] = get_lang('Students');
	$menu_items[] = '<a href="teachers.php">'.get_lang('Trainers').'</a>';
	$menu_items[] = '<a href="course.php">'.get_lang('Courses').'</a>';
	$menu_items[] = '<a href="session.php">'.get_lang('Sessions').'</a>';
}

// Actions menu
$nb_menu_items = count($menu_items);

if ($nb_teacher_courses > 0 ) {    
	echo '<div id="actions" class="actions">';
               
    if (isset($_GET['display']) && ($_GET['display'] == 'useroverview' || $_GET['display'] == 'sessionoverview' || $_GET['display'] == 'courseoverview')) {
        echo '<a href="'.api_get_self().'?display='.$_GET['display'].'&export=csv&view='.$view.'">';
        echo Display::return_icon("export_csv.png", get_lang('ExportAsCSV'),array(), 32);
        echo '</a>';
    }
    
    if (!empty($session_id)) {
        echo '<a href="javascript: window.back();" ">'.Display::return_icon('back.png', get_lang('Back'),'','32').'</a>';
    }
    echo Display::url(Display::return_icon('stats.png', get_lang('MyStats'),'',32),api_get_path(WEB_CODE_PATH)."auth/my_progress.php" );
    
    
    //echo Display::url(Display::return_icon('printer.png', get_lang('Print'),'',32),"javascript: void(0);", array('onclick'=>'window.print();'));	
    echo '<a href="javascript: void(0);" onclick="javascript: window.print()">'.	Display::return_icon('printer.png', get_lang('Print'),'','32').'</a>';
	
    if (empty($session_id)) {
    	if ($nb_menu_items > 1) {
    		foreach ($menu_items as $key => $item) {
    			echo $item;
    			if ($key != $nb_menu_items - 1) {
    				echo '&nbsp;|&nbsp;';
    			}
    		}
    	}
    } else {
    	//
    }
		
	echo '</div>';
} else {
	echo '<div id="actions" class="actions">';
	echo '<a href="'.api_get_path(WEB_CODE_PATH).'auth/my_progress.php">'.Display::return_icon('stats.png', get_lang('MyStats'),'','32').'</a>';//
	echo '</div>';
	//Display::display_warning_message(get_lang('HaveNoCourse'));
}

echo Display::tag('h2', $title);

if (($is_drh && $view == 'drh') || (isset($_GET['display']) && $_GET['display'] == 'yourstudents')) {
	// get data for human resources manager
	$students = array_keys(UserManager::get_users_followed_by_drh($user_id, STUDENT));
	$courses_of_the_platform = CourseManager :: get_real_course_list();
	foreach ($courses_of_the_platform as $course) {
		$courses[$course['code']] = $course['code'];
	}
}

if ($is_coach && $view == 'coach') {
	$students = Tracking::get_student_followed_by_coach($user_id);
	$courses  = Tracking::get_courses_followed_by_coach($user_id);
}

if ($view == 'coach' || $view == 'drh') {

	$nb_students = count($students);
	$total_time_spent = 0;
	$total_courses = 0;
	$avg_total_progress = 0;
	$avg_results_to_exercises = 0;
	$nb_inactive_students = 0;
	$nb_posts = $nb_assignments = 0;
    
    if (!empty($students))
	foreach ($students as $student_id) {
		// inactive students
		$last_connection_date = Tracking :: get_last_connection_date($student_id, true, true);
		if ($last_connection_date !== false) {
			if (time() - (3600 * 24 * 7) > $last_connection_date) {
				$nb_inactive_students++;
			}
		} else {
			$nb_inactive_students++;
		}

		$total_time_spent += Tracking :: get_time_spent_on_the_platform($student_id);
		$total_courses += Tracking :: count_course_per_student($student_id);
		$avg_student_progress = $avg_student_score = 0;
		$nb_courses_student = 0;
		foreach ($courses as $course_code) {
			if (CourseManager :: is_user_subscribed_in_course($student_id, $course_code, true)) {
				$nb_courses_student++;
				$nb_posts 			   += Tracking :: count_student_messages($student_id, $course_code);
				$nb_assignments 	   += Tracking :: count_student_assignments($student_id, $course_code);
				$avg_student_progress  += Tracking :: get_avg_student_progress($student_id, $course_code);
				$myavg_temp 			= Tracking :: get_avg_student_score($student_id, $course_code);

				 if (is_numeric($myavg_temp))
				 	$avg_student_score += $myavg_temp;

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

	if ($nb_students > 0) {
		// average progress
		$avg_total_progress = $avg_total_progress / $nb_students;
		// average results to the tests
		$avg_results_to_exercises = $avg_results_to_exercises / $nb_students;
		// average courses by student
		$avg_courses_per_student = round($total_courses / $nb_students, 2);
		// average time spent on the platform
		$avg_time_spent = $total_time_spent / $nb_students;
		// average assignments
		$nb_assignments = $nb_assignments / $nb_students;
		// average posts
		$nb_posts = $nb_posts / $nb_students;
	} else {
		$avg_total_progress = null;
		$avg_results_to_exercises = null;
		$avg_courses_per_student = null;
		$avg_time_spent = null;
		$nb_assignments = null;
		$nb_posts = null;
	}

	if ($export_csv) {
		//csv part
		$csv_content[] = array(get_lang('Probationers', ''));
		$csv_content[] = array(get_lang('InactivesStudents', ''), $nb_inactive_students );
		$csv_content[] = array(get_lang('AverageTimeSpentOnThePlatform', ''), $avg_time_spent);
		$csv_content[] = array(get_lang('AverageCoursePerStudent', ''), $avg_courses_per_student);
		$csv_content[] = array(get_lang('AverageProgressInLearnpath', ''), is_null($avg_total_progress) ? null : round($avg_total_progress, 2).'%');
		$csv_content[] = array(get_lang('AverageResultsToTheExercices', ''), is_null($avg_results_to_exercises) ? null : round($avg_results_to_exercises, 2).'%');
		$csv_content[] = array(get_lang('AveragePostsInForum', ''), $nb_posts);
		$csv_content[] = array(get_lang('AverageAssignments', ''), $nb_assignments);
		$csv_content[] = array();
	} else {
		// html part
		echo '
		 <div class="report_section">
			<h4>
				<a href="student.php?display=yourstudents"><img src="'.api_get_path(WEB_IMG_PATH).'students.gif">&nbsp;'.get_lang('Probationers').' ('.$nb_students.')'.'</a>
			</h4>
			<table class="data_table">
				<tr>
					<td>
						'.get_lang('InactivesStudents').'
					</td>
					<td align="right">
						'.$nb_inactive_students.'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AverageTimeSpentOnThePlatform').'
					</td>
					<td align="right">
						'.(is_null($avg_time_spent) ? '' : api_time_to_hms($avg_time_spent)).'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AverageCoursePerStudent').'
					</td>
					<td align="right">
						'.(is_null($avg_courses_per_student) ? '' : $avg_courses_per_student).'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AverageProgressInLearnpath').'
					</td>
					<td align="right">
						'.(is_null($avg_total_progress) ? '' : round($avg_total_progress, 2).'%').'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AverageResultsToTheExercices').'
					</td>
					<td align="right">
						'.(is_null($avg_results_to_exercises) ? '' : round($avg_results_to_exercises, 2).'%').'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AveragePostsInForum').'
					</td>
					<td align="right">
						'.(is_null($nb_posts) ? '' : round($nb_posts, 2)).'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('AverageAssignments').'
					</td>
					<td align="right">
						'.(is_null($nb_assignments) ? '' : round($nb_assignments, 2)).'
					</td>
				</tr>
			</table><br />
			<a href="student.php?display=yourstudents">'.get_lang('SeeStudentList').'</a>
		 </div>';
	}
}

if ($view == 'coach') {
	/**
	 * Infos about sessions of the coach
	 **/
	$sessions = Tracking :: get_sessions_coached_by_user($user_id);
	$nb_sessions = count($sessions);
	$nb_sessions_past = $nb_sessions_future = $nb_sessions_current = 0;
	$courses = array();
	foreach ($sessions as $session) {
		if ($session['date_start'] == '0000-00-00') {
			$nb_sessions_current ++;
		} else {
			$date_start = explode('-', $session['date_start']);
			$time_start = mktime(0, 0, 0, $date_start[1], $date_start[2], $date_start[0]);
			$date_end = explode('-', $session['date_end']);
			$time_end = mktime(0, 0, 0, $date_end[1], $date_end[2], $date_end[0]);
			if ($time_start < time() && time() < $time_end) {
				$nb_sessions_current++;
			} elseif (time() < $time_start) {
				$nb_sessions_future++;
			} elseif (time() > $time_end) {
				$nb_sessions_past++;
			}
		}
		$courses = array_merge($courses, Tracking::get_courses_list_from_session($session['id']));
	}

	if ($nb_sessions > 0) {
		$nb_courses_per_session = round(count($courses) / $nb_sessions, 2);
		$nb_students_per_session = round($nb_students / $nb_sessions, 2);
	} else {
		$nb_courses_per_session = null;
		$nb_students_per_session = null;
	}


	if ($export_csv) {
		//csv part
		$csv_content[] = array(get_lang('Sessions', ''));
		$csv_content[] = array(get_lang('NbActiveSessions', '').';'.$nb_sessions_current);
		$csv_content[] = array(get_lang('NbPastSessions', '').';'.$nb_sessions_past);
		$csv_content[] = array(get_lang('NbFutureSessions', '').';'.$nb_sessions_future);
		$csv_content[] = array(get_lang('NbStudentPerSession', '').';'.$nb_students_per_session);
		$csv_content[] = array(get_lang('NbCoursesPerSession', '').';'.$nb_courses_per_session);
		$csv_content[] = array();
	} else {        
		// html part
		echo '
		 <div class="report_section">
			<h4>
				<a href="session.php"><img src="'.api_get_path(WEB_IMG_PATH).'sessions.gif">&nbsp;'.get_lang('Sessions').' ('.$nb_sessions.')'.'</a>
			</h4>
			<table class="data_table">
				<tr>
					<td>
						'.get_lang('NbActiveSessions').'
					</td>
					<td align="right">
						'.$nb_sessions_current.'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('NbPastSessions').'
					</td>
					<td align="right">
						'.$nb_sessions_past.'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('NbFutureSessions').'
					</td>
					<td align="right">
						'.$nb_sessions_future.'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('NbStudentPerSession').'
					</td>
					<td align="right">
						'.(is_null($nb_students_per_session) ? '' : $nb_students_per_session).'
					</td>
				</tr>
				<tr>
					<td>
						'.get_lang('NbCoursesPerSession').'
					</td>
					<td align="right">
						'.(is_null($nb_courses_per_session) ? '' : $nb_courses_per_session).'
					</td>
				</tr>
			</table>
			<a href="session.php">'.get_lang('SeeSessionList').'</a>
		 </div>';
	 }
}
echo '<div class="clear">&nbsp;</div>';

if (api_is_allowed_to_create_course() && $view == 'teacher') {

	if ($nb_teacher_courses) {
	    //Courses table
	    
		$table = new SortableTable('courses', 'get_number_of_courses' ,array('MySpace','get_course_data'));
		$parameters['view'] = 'teacher';
		$parameters['class'] = 'data_table';
		$table->set_additional_parameters($parameters);
		$table -> set_header(0, get_lang('CourseTitle'), false, 'align="center"');
		$table -> set_header(1, get_lang('NbStudents'), false);
		$table -> set_header(2, get_lang('AvgTimeSpentInTheCourse').Display :: return_icon('info3.gif', get_lang('TimeOfActiveByTraining'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table -> set_header(3, get_lang('AvgStudentsProgress').Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table -> set_header(4, get_lang('AvgCourseScore').Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table -> set_header(5, get_lang('AvgExercisesScore').Display :: return_icon('info3.gif', get_lang('AvgAllUsersInAllCourses'), array('align' => 'absmiddle', 'hspace' => '3px')), false);
		$table -> set_header(6, get_lang('AvgMessages'), false);
		$table -> set_header(7, get_lang('AvgAssignments'), false);
		$table -> set_header(8, get_lang('Details'), false);

		$csv_content[] = array (
			get_lang('CourseTitle', ''),
			get_lang('NbStudents', ''),
			get_lang('AvgTimeSpentInTheCourse', ''),
			get_lang('AvgStudentsProgress', ''),
			get_lang('AvgCourseScore', ''),
			get_lang('AvgExercisesScore', ''),
			get_lang('AvgMessages', ''),
			get_lang('AvgAssignments', '')
		);
        $table->display();
	}

	// Display sessions
	if ($nb_sessions > 0 && !isset($_GET['session_id'])) {
		echo '<h2>'.get_lang('Sessions').'</h2>';
		$table = new SortableTable('tracking_sessions', 'count_sessions_coached');
		$table->set_header(0, get_lang('Title'), false);
		$table->set_header(1, get_lang('Date'), false);
		$table->set_header(2, get_lang('NbCoursesPerSession'), false);
		$table->set_header(3, get_lang('Details'), false);

		$all_data = array();
		foreach ($sessions as $session) {
			$count_courses_in_session = count(Tracking::get_courses_followed_by_coach($user_id, $session['id']));
			$row = array();
			$row[] = $session['name'];

			if ($session['date_start'] != '0000-00-00' && $session['date_end'] != '0000-00-00') {
				$row[] = get_lang('From').' '.api_convert_and_format_date($session['date_start'], DATE_FORMAT_SHORT, date_default_timezone_get()).' '.get_lang('Until').' '.api_convert_and_format_date($session['date_end'], DATE_FORMAT_SHORT, date_default_timezone_get());
			} else {
				$row[] = ' - ';
			}
			$row[] = $count_courses_in_session;
			$row[] = '<a href="'.api_get_self().'?session_id='.$session['id'].'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>';
			$all_data[] = $row;
		}

		if (!isset($tracking_column)) {
			$tracking_column = 0;
		}

		if (isset($_GET['tracking_direction']) &&  $_GET['tracking_direction'] == 'DESC') {
			usort($all_data, 'rsort_sessions');
		} else {
			usort($all_data, 'sort_sessions');
		}

		if ($export_csv) {
			usort($csv_content, 'sort_sessions');
		}

		foreach ($all_data as $row) {
			$table -> addRow($row);
		}

		$table -> setColAttributes(1, array('align' => 'center'));
		$table -> setColAttributes(2, array('align' => 'center'));
		$table -> setColAttributes(3, array('align' => 'center'));
		$table -> display();
	}
}

if ($is_platform_admin && $view == 'admin' && $_GET['display'] != 'yourstudents') {
    
	echo '<a href="'.api_get_self().'?view=admin&amp;display=coaches">'.get_lang('DisplayCoaches').'</a> | ';
	echo '<a href="'.api_get_self().'?view=admin&amp;display=useroverview">'.get_lang('DisplayUserOverview').'</a>';
	if ($_GET['display'] == 'useroverview') {
		echo ' | <a href="'.api_get_self().'?view=admin&amp;display=useroverview&amp;export=options">'.get_lang('ExportUserOverviewOptions').'</a>';
	}
	echo ' | <a href="'.api_get_self().'?view=admin&amp;display=sessionoverview">'.get_lang('DisplaySessionOverview').'</a>';
	echo ' | <a href="'.api_get_self().'?view=admin&amp;display=courseoverview">'.get_lang('DisplayCourseOverview').'</a>';
    
    echo ' | <a href="'.api_get_path(WEB_CODE_PATH).'tracking/question_course_report.php?view=admin">'.get_lang('LPQuestionListResults').'</a>';
    
    echo ' | <a href="'.api_get_path(WEB_CODE_PATH).'tracking/course_session_report.php?view=admin">'.get_lang('LPExerciseResultsBySession').'</a>';
    
    
	echo '<br /><br />';
	if ($_GET['display'] === 'useroverview') {
		MySpace::display_tracking_user_overview();
	} else if($_GET['display'] == 'sessionoverview') {
		MySpace::display_tracking_session_overview();
	} else if($_GET['display'] == 'courseoverview') {
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
		$table = new SortableTable('tracking_list_coaches', 'count_coaches', null, ($is_western_name_order xor $sort_by_first_name) ? 1 : 0);
		$parameters['view'] = 'admin';
		$table->set_additional_parameters($parameters);
		if ($is_western_name_order) {
			$table -> set_header(0, get_lang('FirstName'), true, 'align="center"');
			$table -> set_header(1, get_lang('LastName'), true, 'align="center"');
		} else {
			$table -> set_header(0, get_lang('LastName'), true, 'align="center"');
			$table -> set_header(1, get_lang('FirstName'), true, 'align="center"');
		}
		$table -> set_header(2, get_lang('TimeSpentOnThePlatform'), false);
		$table -> set_header(3, get_lang('LastConnexion'), false, 'align="center"');
		$table -> set_header(4, get_lang('NbStudents'), false);
		$table -> set_header(5, get_lang('CountCours'), false);
		$table -> set_header(6, get_lang('NumberOfSessions'), false);
		$table -> set_header(7, get_lang('Sessions'), false, 'align="center"');

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

			$time_on_platform = api_time_to_hms(Tracking :: get_time_spent_on_the_platform($coaches['user_id']));
			$last_connection = Tracking :: get_last_connection_date($coaches['user_id']);
			$nb_students = count(Tracking :: get_student_followed_by_coach($coaches['user_id']));
			$nb_courses = count(Tracking :: get_courses_followed_by_coach($coaches['user_id']));
			$nb_sessions = count(Tracking :: get_sessions_coached_by_user($coaches['user_id']));

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
			$table_row[] = '<center><a href="session.php?id_coach='.$coaches['user_id'].'"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></center>';
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
			$table -> addRow($row, 'align="right"');
		}

		$table -> updateColAttributes(0, array('align' => 'left'));
		$table -> updateColAttributes(1, array('align' => 'left'));
		$table -> updateColAttributes(3, array('align' => 'left'));
		$table -> updateColAttributes(7, array('align' => 'center'));
		$table -> display();
	}
}

// send the csv file if asked
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