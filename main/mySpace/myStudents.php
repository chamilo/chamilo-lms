<?php
/* For licensing terms, see /license.txt */
/**
 * Implements the tracking of students in the Reporting pages
 * @package chamilo.reporting
 */
/**
 * Code
 */
// name of the language file that needs to be included
$language_file = array('registration', 'index', 'tracking', 'exercice', 'admin', 'gradebook', 'survey');

//require_once '../inc/global.inc.php';

require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'mySpace/myspace.lib.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/gradebookitem.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evaluation.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/result.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/linkfactory.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/category.class.php';
require_once api_get_path(SYS_CODE_PATH).'survey/survey.lib.php';

api_block_anonymous_users();

if (!api_is_allowed_to_create_course() && !api_is_session_admin() && !api_is_drh()) {
    // Check if the user is tutor of the course
    $user_course_status = CourseManager::get_tutor_in_course_status(api_get_user_id(), api_get_course_int_id());
    if ($user_course_status != 1) {
        api_not_allowed(true);
    }
}

$htmlHeadXtra[] = '<script>
function show_image(image,width,height) {
	width = parseInt(width) + 20;
	height = parseInt(height) + 20;
	window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');
}
</script>';

$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;

if ($export_csv) {
	ob_start();
}
$csv_content = array();

$from_myspace = false;
if (isset ($_GET['from']) && $_GET['from'] == 'myspace') {
	$from_myspace = true;
	$this_section = SECTION_TRACKING;
} else {
	$this_section = SECTION_COURSES;
}

$nameTools = get_lang('StudentDetails');

$course_code = isset($_GET['course']) ? Security :: remove_XSS($_GET['course']) : null;
$courseInfo = api_get_course_info($course_code);

if ($courseInfo) {
    $courseCode = $courseInfo['code'];
    $courseId = $courseInfo['code'];
}

if (isset($_GET['details'])) {
	if (!empty ($_GET['origin']) && $_GET['origin'] == 'user_course') {
		if (empty ($cidReq)) {
			$interbreadcrumb[] = array (
				"url" => api_get_path(WEB_COURSE_PATH) . $courseInfo['directory'],
				'name' => $courseInfo['title']
			);
		}
		$interbreadcrumb[] = array (
			"url" => "../user/user.php?cidReq=" . $courseCode,
			"name" => get_lang("Users")
		);
	} else
		if (!empty ($_GET['origin']) && $_GET['origin'] == 'tracking_course') {
			$interbreadcrumb[] = array (
				"url" => "../tracking/courseLog.php?cidReq=".$courseId.'&id_session=' . (empty ($_SESSION['id_session']) ? '' : $_SESSION['id_session']),
				"name" => get_lang("Tracking")
			);
		} else
			if (!empty ($_GET['origin']) && $_GET['origin'] == 'resume_session') {
				$interbreadcrumb[] = array (
					'url' => '../admin/index.php',
					"name" => get_lang('PlatformAdmin')
				);
				$interbreadcrumb[] = array (
					'url' => "../session/session_list.php",
					"name" => get_lang('SessionList')
				);
				$interbreadcrumb[] = array (
					'url' => "../session/resume_session.php?id_session=" . Security :: remove_XSS($_GET['id_session']),
					"name" => get_lang('SessionOverview')
				);
			} else {
				$interbreadcrumb[] = array (
					"url" => "index.php",
					"name" => get_lang('MySpace')
				);
				if (isset ($_GET['id_coach']) && intval($_GET['id_coach']) != 0) {
					$interbreadcrumb[] = array (
						"url" => "student.php?id_coach=" . Security :: remove_XSS($_GET['id_coach']),
						"name" => get_lang("CoachStudents")
					);
					$interbreadcrumb[] = array (
						"url" => "myStudents.php?student=" . Security :: remove_XSS($_GET['student']) . '&id_coach=' . Security :: remove_XSS($_GET['id_coach']),
						"name" => get_lang("StudentDetails")
					);
				} else {
					$interbreadcrumb[] = array (
						"url" => "student.php",
						"name" => get_lang("MyStudents")
					);
					$interbreadcrumb[] = array (
						"url" => "myStudents.php?student=" . Security :: remove_XSS($_GET['student']),
						"name" => get_lang("StudentDetails")
					);
				}
			}
	$nameTools = get_lang("DetailsStudentInCourse");
} else {
	if (!empty ($_GET['origin']) && $_GET['origin'] == 'resume_session') {
		$interbreadcrumb[] = array (
			'url' => '../session/index.php',
			"name" => get_lang('PlatformAdmin')
		);
		$interbreadcrumb[] = array (
			'url' => "../session/session_list.php",
			"name" => get_lang('SessionList')
		);
		$interbreadcrumb[] = array (
			'url' => "../session/resume_session.php?id_session=" . Security :: remove_XSS($_GET['id_session']),
			"name" => get_lang('SessionOverview')
		);
	} else {
		$interbreadcrumb[] = array (
			"url" => "index.php",
			"name" => get_lang('MySpace')
		);
		if (isset ($_GET['id_coach']) && intval($_GET['id_coach']) != 0) {
			if (isset ($_GET['id_session']) && intval($_GET['id_session']) != 0) {
				$interbreadcrumb[] = array (
					"url" => "student.php?id_coach=" . Security :: remove_XSS($_GET['id_coach']) . "&id_session=" . $_GET['id_session'],
					"name" => get_lang("CoachStudents")
				);
			} else {
				$interbreadcrumb[] = array (
					"url" => "student.php?id_coach=" . Security :: remove_XSS($_GET['id_coach']),
					"name" => get_lang("CoachStudents")
				);
			}
		} else {
			$interbreadcrumb[] = array (
				"url" => "student.php",
				"name" => get_lang("MyStudents")
			);
		}
	}
}

// Database Table Definitions
$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_stats_exercices 		= Database :: get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES);

if (isset($_GET['user_id']) && $_GET['user_id'] != "") {
	$user_id = intval($_GET['user_id']);
} else {
	$user_id = api_get_user_id();
}

$session_id = isset($_GET['id_session']) ? intval($_GET['id_session']) : null;

if (empty($session_id)) {
    $session_id = api_get_session_id();
}

$student_id = isset($_GET['student']) ? intval($_GET['student']) : null;

// Action behaviour
$check= Security::check_token('get');

if ($check) {
	switch ($_GET['action']) {
		case 'reset_lp' :
			$lp_id		= isset($_GET['lp_id'])	 ? intval($_GET['lp_id']):"";
			if (api_is_allowed_to_edit() && !empty($courseInfo) && !empty($lp_id) && !empty($student_id)) {
                delete_student_lp_events($student_id, $lp_id, $courseInfo, $session_id);

				//@todo delete the stats.track_e_exercices records. First implement this http://support.chamilo.org/issues/1334
				$message = Display::return_message(get_lang('LPWasReset'),'success');
			}
            break;
		default:
			break;
	}
	Security::clear_token();
}

// User info
$user_info = api_get_user_info($student_id);

$courses_in_session = array();

//See #4676
$drh_can_access_all_courses = false;

if (api_is_drh() || api_is_platform_admin()) {
    $drh_can_access_all_courses = true;
}

$courses = CourseManager::get_course_list_of_user_as_course_admin(api_get_user_id());

$courses_in_session_by_coach = array();
$sessions_coached_by_user = SessionManager::get_sessions_coached_by_user(api_get_user_id());

//RRHH or session admin
if (api_is_session_admin() || api_is_drh()) {
    $courses = CourseManager::get_courses_followed_by_drh(api_get_user_id());

	$session_by_session_admin = SessionManager::get_sessions_followed_by_drh(api_get_user_id());
	if (!empty($session_by_session_admin)) {
		foreach ($session_by_session_admin as $session_coached_by_user) {
			$courses_followed_by_coach = Tracking :: get_courses_list_from_session($session_coached_by_user['id']);
			$courses_in_session_by_coach[$session_coached_by_user['id']] = $courses_followed_by_coach;
		}
	}
}

// Teacher or admin
if (!empty($sessions_coached_by_user)) {
	foreach ($sessions_coached_by_user as $session_coached_by_user) {
		$sid = intval($session_coached_by_user['id']);
		$courses_followed_by_coach = Tracking :: get_courses_followed_by_coach(api_get_user_id(), $sid);
		$courses_in_session_by_coach[$sid] = $courses_followed_by_coach;
	}
}

$sql = "SELECT c_id FROM $tbl_course_user WHERE relation_type <> ".COURSE_RELATION_TYPE_RRHH." AND user_id = ".intval($user_info['user_id']);
$rs = Database::query($sql);

while ($row = Database :: fetch_array($rs)) {
    if ($drh_can_access_all_courses) {
        $courses_in_session[0][] = $row['c_id'];
    } else {
        if (isset($courses[$row['c_id']])) {
            $courses_in_session[0][] = $row['c_id'];
        }
    }
}

// Get the list of sessions where the user is subscribed as student
$sql = 'SELECT id_session, c_id FROM ' . Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER) . ' WHERE id_user=' . intval($user_info['user_id']);
$rs = Database::query($sql);
$tmp_sessions = array();

while ($row = Database :: fetch_array($rs)) {
	$tmp_sessions[] = $row['id_session'];
    if ($drh_can_access_all_courses) {
        if (in_array($row['id_session'], $tmp_sessions)) {
            $courses_in_session[$row['id_session']][] = $row['c_id'];
        }
    } else {
        if (isset($courses_in_session_by_coach[$row['id_session']])) {
            if (in_array($row['id_session'], $tmp_sessions)) {
                $courses_in_session[$row['id_session']][] = $row['c_id'];
            }
        }
    }
}

if (!empty($student_id)) {
	if (api_is_drh() && !UserManager::is_user_followed_by_drh($student_id, api_get_user_id())) {
		api_not_allowed();
	}
}

Display :: display_header($nameTools);

if (isset($message)) {
    echo $message;
}

if (!empty($student_id)) {

	// Actions bar
	echo '<div class="actions">';
    echo '<a href="javascript: window.back();" ">'.Display::return_icon('back.png', get_lang('Back'),'',ICON_SIZE_MEDIUM).'</a>';
	echo '<a href="javascript: void(0);" onclick="javascript: window.print();">'.Display::return_icon('printer.png', get_lang('Print'),'',ICON_SIZE_MEDIUM).'</a>';
	echo '<a href="' . api_get_self() . '?' . Security :: remove_XSS($_SERVER['QUERY_STRING']) . '&export=csv">'.Display::return_icon('export_csv.png', get_lang('ExportAsCSV'),'',ICON_SIZE_MEDIUM).'</a> ';
	if (!empty ($user_info['email'])) {
		$send_mail = '<a href="mailto:'.$user_info['email'].'">'.Display :: return_icon('mail_send.png', get_lang('SendMail'),'',ICON_SIZE_MEDIUM).'</a>';
	} else {
		$send_mail = Display :: return_icon('mail_send_na.png', get_lang('SendMail'),'',ICON_SIZE_MEDIUM);
	}
	echo $send_mail;
	if (!empty($student_id) && !empty ($_GET['course'])) { //only show link to connection details if course and student were defined in the URL
		echo '<a href="access_details.php?student=' . $student_id . '&course=' . $courseId . '&amp;origin=' . Security :: remove_XSS($_GET['origin']) . '&amp;cidReq='.Security::remove_XSS($_GET['course']).'&amp;id_session='.$session_id.'">' . Display :: return_icon('statistics.png', get_lang('AccessDetails'),'',ICON_SIZE_MEDIUM).'</a>';
	}
	echo '</div>';

	// is the user online ?
    if (Online::user_is_online($_GET['student'])) {
        $online = get_lang('Yes');
    } else {
        $online = get_lang('No');
    }

	// get average of score and average of progress by student
	$avg_student_progress = $avg_student_score = 0;

    $courseId = $courseInfo['real_id'];

	if (!CourseManager :: is_user_subscribed_in_course($user_info['user_id'], $courseId, true)) {
		unset($courses[$key]);
	} else {
		$avg_student_progress = Tracking::get_avg_student_progress($user_info['user_id'], $courseId, array(), $session_id);
		//the score inside the Reporting table
		$avg_student_score 	  = Tracking::get_avg_student_score($user_info['user_id'], $courseId, array(), $session_id);
		//var_dump($avg_student_score);
	}

	$avg_student_progress = round($avg_student_progress, 2);

	// time spent on the course

	$time_spent_on_the_course = api_time_to_hms(Tracking :: get_time_spent_on_the_course($user_info['user_id'], $courseId, $session_id));

	// get information about connections on the platform by student
	$first_connection_date = Tracking :: get_first_connection_date($user_info['user_id']);
	if ($first_connection_date == '') {
		$first_connection_date = get_lang('NoConnexion');
	}

	$last_connection_date = Tracking :: get_last_connection_date($user_info['user_id'], true);
	if ($last_connection_date == '') {
		$last_connection_date = get_lang('NoConnexion');
	}

	// cvs informations
	$csv_content[] = array (
		get_lang('Informations', '')
	);
	$csv_content[] = array (
		get_lang('Name', ''),
		get_lang('Email', ''),
		get_lang('Tel', '')
	);
	$csv_content[] = array (
		$user_info['complete_name'],
		$user_info['email'],
		$user_info['phone']
	);

	$csv_content[] = array ();

	// csv tracking
	$csv_content[] = array (
		get_lang('Tracking', '')
	);
	$csv_content[] = array (
		get_lang('FirstLogin', ''),
		get_lang('LatestLogin', ''),
		get_lang('TimeSpentInTheCourse', ''),
		get_lang('Progress', ''),
		get_lang('Score', '')
	);
	$csv_content[] = array (
		strip_tags($first_connection_date),
		strip_tags($last_connection_date),
		$time_spent_on_the_course,
		$avg_student_progress . '%',
		$avg_student_score
	);

    //Show title
    $coachs_name  = '';
    $session_name = '';
    $nb_login = Tracking :: count_login_per_student($user_info['user_id'], $courseId);
    //get coach and session_name if there is one and if session_mode is activated
    if ($session_id > 0) {

        $session_info  = api_get_session_info($session_id);
        $course_coachs = CourseManager::get_coaches_from_course($session_id, $courseId);

        $nb_login = '';
        if (!empty($course_coachs)) {
            $info_tutor_name = array();
            foreach ($course_coachs as $course_coach) {
                $info_tutor_name[] = api_get_person_name($course_coach['firstname'], $course_coach['lastname']);
            }
            $courseInfo['tutor_name'] = implode(",", $info_tutor_name);
        } /* elseif ($session_coach_id != 0) {
            $session_coach_id = intval($session_info['id_coach']);
            $coach_info = UserManager::get_user_info_by_id($session_coach_id);
            $courseInfo['tutor_name'] = api_get_person_name($coach_info['firstname'], $coach_info['lastname']);
        }*/
        $coachs_name  = isset($courseInfo['tutor_name']) ? $courseInfo['tutor_name'] : null;
        $session_name = $session_info['name'];
    } // end

    $table_title = Display::return_icon('user.png', get_lang('User'), array(), ICON_SIZE_SMALL).$user_info['complete_name'];

    echo Display::page_subheader($table_title);

    echo '<table width="100%" border="0">';
    echo '<tr>';

	$image_array = UserManager :: get_user_picture_path_by_id($user_info['user_id'], 'web', false, true);
	echo '<td class="borderRight" width="10%" valign="top">';

	// get the path,width and height from original picture
	$image_file = $image_array['dir'] . $image_array['file'];
	$big_image = $image_array['dir'] . 'big_' . $image_array['file'];
	$big_image_size = api_getimagesize($big_image);
	$big_image_width = $big_image_size['width'];
	$big_image_height = $big_image_size['height'];
	$url_big_image = $big_image . '?rnd=' . time();
	$img_attributes = 'src="' . $image_file . '?rand=' . time() . '" ' .
	'alt="' . $user_info['complete_name']. '" ' .
	'style="float:' . ($text_dir == 'rtl' ? 'right' : 'left') . '; padding:5px;" ';

	if ($image_array['file'] == 'unknown.jpg') {
		echo '<img ' . $img_attributes . ' />';
	} else {
		echo '<input type="image" ' . $img_attributes . ' onclick="javascript: return show_image(\'' . $url_big_image . '\',\'' . $big_image_width . '\',\'' . $big_image_height . '\');"/>';
	}
	echo '</td>';
?>
		<td width="40%" valign="top">
			<table width="100%" class="data_table">
				<tr>
					<th><?php echo get_lang('Information'); ?></th>
				</tr>
				<tr>
					<td><?php echo get_lang('Name') . ' : '.$user_info['complete_name']; ?></td>
				</tr>
				<tr>
					<td><?php echo get_lang('Email') . ' : ';
					if (!empty ($user_info['email'])) {
						echo '<a href="mailto:' . $user_info['email'] . '">' . $user_info['email'] . '</a>';
					} else {
						echo get_lang('NoEmail');
					} ?>
					</td>
				</tr>
				<tr>
					<td> <?php echo get_lang('Tel') . ' : ';
					if (!empty ($user_info['phone'])) {
						echo $user_info['phone'];
					} else {
						echo get_lang('NoTel');
					}
                    ?>
					</td>
				</tr>
				<tr>
					<td> <?php echo get_lang('OfficialCode') . ' : ';
					if (!empty ($user_info['official_code'])) {
						echo $user_info['official_code'];
					} else {
						echo get_lang('NoOfficialCode');
					}
			?>
					</td>
				</tr>
				<tr>
					<td><?php echo get_lang('OnLine') . ' : '.$online; ?> </td>
				</tr>
			<?php

			// Display timezone if the user selected one and if the admin allows the use of user's timezone
			$timezone = null;
			$timezone_user = UserManager::get_extra_user_data_by_field($user_info['user_id'],'timezone');
			$use_users_timezone = api_get_setting('use_users_timezone', 'timezones');
			if ($timezone_user['timezone'] != null && $use_users_timezone == 'true') {
				$timezone = $timezone_user['timezone'];
			}
			if ($timezone !== null) {
			?>
				<tr>
					<td> <?php echo get_lang('Timezone') . ' : '.$timezone; ?> </td>
				</tr>
			<?php
			}
			?>
			</table>
			</td>

			<td class="borderLeft" width="35%" valign="top">
				<table width="100%" class="data_table">
					<tr>
						<th colspan="2"><?php echo get_lang('Tracking'); ?></th>
					</tr>
					<tr><td align="right"><?php echo get_lang('FirstLogin') ?></td>
						<td align="left"><?php echo $first_connection_date ?></td>
					</tr>
					<tr>
						<td align="right"><?php echo get_lang('LatestLogin') ?></td>
						<td align="left"><?php echo $last_connection_date ?></td>
					</tr>

					<?php if (isset($_GET['details']) && $_GET['details'] == 'true') {?>
					<tr>
						<td align="right"><?php echo get_lang('TimeSpentInTheCourse') ?></td>
						<td align="left"><?php echo  $time_spent_on_the_course ?></td>
					</tr>
					<tr>
						<td align="right"><?php echo get_lang('Progress').' '; Display :: display_icon('info3.gif', get_lang('ScormAndLPProgressTotalAverage'), array ('align' => 'absmiddle', 'hspace' => '3px'));?></td>
						<td align="left"><?php echo $avg_student_progress.'%' ?></td>
					</tr>
					<tr>
						<td align="right"><?php echo get_lang('Score').' '; Display :: display_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), array ('align' => 'absmiddle', 'hspace' => '3px')); ?>
						</td>
						<td align="left"><?php if (is_numeric($avg_student_score)) { echo $avg_student_score.'%';} else { echo $avg_student_score ;}  ?></td>
					</tr>
                    <?php
                        if (!empty($nb_login)) {
                        	echo '<tr><td align="right">'.get_lang('CountToolAccess').'</td>';
                            echo '<td align="left">'.$nb_login.'</td>';
                            echo '</tr>';
                        }
					} ?>
				</table>
			</td>
		</tr>
	</table>

<?php

$table_title = '';

if (!empty($session_id)) {
    $session_name = api_get_session_name($session_id);
	$table_title  = ($session_name? Display::return_icon('session.png', get_lang('Session'), array(), ICON_SIZE_SMALL).' '.$session_name.' ':'');
}
if (!empty($courseInfo['title'])) {
	$table_title .= ($courseInfo ? Display::return_icon('course.png', get_lang('Course'), array(), ICON_SIZE_SMALL).' '.$courseInfo['title'].'  ':'');
}

echo Display::page_subheader($table_title);

if (empty($_GET['details'])) {

	$csv_content[] = array();
	$csv_content[] = array(
        get_lang('Session', ''),
        get_lang('Course', ''),
        get_lang('Time', ''),
        get_lang('Progress', ''),
        get_lang('Score', ''),
        get_lang('AttendancesFaults', ''),
        get_lang('Evaluations')
    );

	$attendance = new Attendance();
	foreach ($courses_in_session as $key => $courses) {
		$session_id   = $key;
		$title = null;
		if (empty($session_id)) {
			$title = Display::return_icon('course.png', get_lang('Courses'), array(), ICON_SIZE_SMALL).' '.get_lang('Courses');
		} else {
            $session_info = api_get_session_info($session_id);
            $session_name = $session_info['name'];
            $date_session = SessionManager::parse_session_dates($session_info);
			$title = Display::return_icon('session.png', get_lang('Session'), array(), ICON_SIZE_SMALL).' '.$session_name.($date_session ?' <small>'.$date_session.'</small>':'');
		}

		// Courses

		echo '<h3>'.$title.'</h3>';
		echo '<table class="data_table">';
		echo '<tr>
				<th>'.get_lang('Course').'</th>
				<th>'.get_lang('Time').'</th>
				<th>'.get_lang('Progress').'</th>
				<th>'.get_lang('Score').'</th>
				<th>'.get_lang('AttendancesFaults').'</th>
				<th>'.get_lang('Evaluations').'</th>
				<th>'.get_lang('Details').'</th>
			</tr>';

		if (!empty($courses)) {
            foreach ($courses as $courseId) {
                if (CourseManager :: is_user_subscribed_in_course($student_id, $courseId, true)) {
                    $courseInfo = api_get_course_info_by_id($courseId);
                    $course_code = $courseInfo['code'];

    				$time_spent_on_course = api_time_to_hms(Tracking :: get_time_spent_on_the_course($user_info['user_id'], $courseId, $session_id));

    				// get average of faults in attendances by student
    				$results_faults_avg = $attendance->get_faults_average_by_course($student_id, $courseId, $session_id);
    				if (!empty($results_faults_avg['total'])) {
    					if (api_is_drh()) {
    						$attendances_faults_avg = '<a title="'.get_lang('GoAttendance').'" href="'.api_get_path(WEB_CODE_PATH).'attendance/index.php?cidReq='.$course_code.'&id_session='.$session_id.'&student_id='.$student_id.'">'.$results_faults_avg['faults'].'/'.$results_faults_avg['total'].' ('.$results_faults_avg['porcent'].'%)</a>';
    					} else {
    						$attendances_faults_avg = $results_faults_avg['faults'].'/'.$results_faults_avg['total'].' ('.$results_faults_avg['porcent'].'%)';
    					}
    				} else {
    					$attendances_faults_avg = '0/0 (0%)';
    				}

    				// get evaluatios by student

    				$cats = Category::load(null, null, $course_code, null, null, $session_id);

    				$scoretotal = array();
    				if (isset($cats) && isset($cats[0])) {
    					if (!empty($session_id)) {
                            $scoretotal= $cats[0]->calc_score($student_id, $course_code, $session_id);
    					} else {
                            $scoretotal= $cats[0]->calc_score($student_id, $course_code);
    					}
    				}

    				$scoretotal_display = '0/0 (0%)';
    				if (!empty($scoretotal)) {
    					$scoretotal_display =  round($scoretotal[0],1).'/'.round($scoretotal[1],1).' ('.round(($scoretotal[0] / $scoretotal[1]) * 100,2) . ' %)';
    				}

    				$progress = Tracking::get_avg_student_progress($user_info['user_id'], $courseId, null, $session_id);
    				$score = Tracking :: get_avg_student_score($user_info['user_id'], $courseId, null, $session_id);
    				$progress = empty($progress) ? '0%' : $progress.'%';
    				$score = empty($score) ? '0%' : $score.'%';

    				$csv_content[] = array (
        				$session_name,
        				$courseInfo['title'],
        				$time_spent_on_course,
        				$progress,
        				$score,
        				$attendances_faults_avg,
        				$scoretotal_display
    				);

    				echo '<tr>
    				<td >'.$courseInfo['title'].'</td>
    				<td >'.$time_spent_on_course .'</td>
    				<td >'.$progress.'</td>
    				<td >'.$score.'</td>
    				<td >'.$attendances_faults_avg.'</td>
                    <td >'.$scoretotal_display.'</td>';

    				if (isset ($_GET['id_coach']) && intval($_GET['id_coach']) != 0) {
    					echo '<td width="10"><a href="'.api_get_self().'?student='.$user_info['user_id'].'&details=true&course='.$courseInfo['code'].'&id_coach='.Security::remove_XSS($_GET['id_coach']).'&origin='.Security::remove_XSS($_GET['origin']).'&id_session='.$session_id.'#infosStudent"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td>';
    				} else {
    					echo '<td width="10"><a href="'.api_get_self().'?student='.$user_info['user_id'].'&details=true&course='.$courseInfo['code'].'&origin='.Security::remove_XSS($_GET['origin']).'&id_session='.$session_id.'#infosStudent"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td>';
    				}
				echo '</tr>';
				}
			}
		} else {
        	echo "<tr><td colspan='5'>".get_lang('NoCourse')."</td></tr>";
		}
		echo '</table>';
	}
} else {
	$csv_content[] = array();
    $csv_content[] = array(str_replace('&nbsp;', '', $table_title));
    $csv_content[] = array();
    $csv_content[] = array(
    	get_lang('Learnpath', ''),
    	get_lang('Time', ''),
    	get_lang('AverageScore', ''),
    	get_lang('LatestScore', ''),
    	get_lang('Progress', ''),
    	get_lang('LastConnexion', '')
    );

    $t_lp = Database :: get_course_table(TABLE_LP_MAIN);
    if (empty($session_id)) {
        $sql_lp = " SELECT lp.name, lp.id FROM $t_lp lp WHERE session_id = 0 AND c_id = $courseId ORDER BY lp.display_order";
    } else {
    	$sql_lp = " SELECT lp.name, lp.id FROM $t_lp lp WHERE c_id = $courseId ORDER BY lp.display_order";
    }
    $rs_lp = Database::query($sql_lp);
    $token = Security::get_token();

    if (Database :: num_rows($rs_lp) > 0) {
    ?>

        <!-- LPs-->
        <table class="data_table">
            <tr>
                <th><?php echo get_lang('Learnpaths');?></th>
                <th><?php echo get_lang('Time').' '; Display :: display_icon('info3.gif', get_lang('TotalTimeByCourse'), array ('align' => 'absmiddle', 'hspace' => '3px')); ?></th>
                <th><?php echo get_lang('AverageScore').' '; Display :: display_icon('info3.gif', get_lang('AverageIsCalculatedBasedInAllAttempts'), array ( 'align' => 'absmiddle', 'hspace' => '3px')); ?></th>
                <th><?php echo get_lang('LatestAttemptAverageScore').' '; Display :: display_icon('info3.gif', get_lang('AverageIsCalculatedBasedInTheLatestAttempts'), array ( 'align' => 'absmiddle', 'hspace' => '3px')); ?></th>
                <th><?php echo get_lang('Progress').' '; Display :: display_icon('info3.gif', get_lang('LPProgressScore'), array ('align' => 'absmiddle','hspace' => '3px')); ?></th>
                <th><?php echo get_lang('LastConnexion').' '; Display :: display_icon('info3.gif', get_lang('LastTimeTheCourseWasUsed'), array ('align' => 'absmiddle','hspace' => '3px')); ?></th>
                <?php
                    echo '<th>'.get_lang('Details').'</th>';
                    if (api_is_allowed_to_edit()) {
                        echo '<th>'.get_lang('ResetLP').'</th>';
                    }
                ?>
              </tr>
        <?php

    	$i = 0;
    	while ($learnpath = Database :: fetch_array($rs_lp)) {

    		$lp_id = intval($learnpath['id']);
    		$lp_name = $learnpath['name'];
    		$any_result = false;

    		// Get progress in lp
    		$progress = Tracking::get_avg_student_progress($student_id, $courseId, array($lp_id), $session_id);

    		if ($progress === null) {
    			$progress = '0%';
    		}  else {
    			$any_result = true;
    		}

    		// Get time in lp
    		$total_time = Tracking::get_time_spent_in_lp($student_id, $courseId, array($lp_id),$session_id);
    		if (!empty($total_time)) $any_result = true;

    		// Get last connection time in lp
    		$start_time = Tracking::get_last_connection_time_in_lp($student_id, $courseId, $lp_id, $session_id);

            if (!empty($start_time)) {
                $start_time = api_convert_and_format_date($start_time, DATE_TIME_FORMAT_LONG);
            } else {
                $start_time =  '-';
            }

    		if (!empty($total_time)) $any_result = true;

    		// Quizz in lp
    		$score = Tracking::get_avg_student_score($student_id, $courseId, array($lp_id),$session_id);

    		// Latest exercise results in a LP
            $score_latest = Tracking :: get_avg_student_score($student_id, $courseId, array($lp_id),$session_id, false, true);

    		if ($i % 2 == 0)
                $css_class = "row_even";
    		else
                $css_class = "row_odd";

    		$i++;

    		// csv export content
    		$csv_content[] = array (
    			api_html_entity_decode(stripslashes($lp_name), ENT_QUOTES, $charset),
    			api_time_to_hms($total_time),
    			$score . '%',
    			$score_latest . '%',
    			$progress.'%',
    			$start_time
    		);

    	    echo '<tr class="'.$css_class.'">';

    		echo Display::tag('td', stripslashes($lp_name));
    		echo Display::tag('td', api_time_to_hms($total_time));

    		if (!is_null($score)) {
    			if (is_numeric($score)) {
                    $score = $score.'%';
                }
    		}
    		echo Display::tag('td', $score);

    	    if (!is_null($score_latest)) {
                if (is_numeric($score_latest)) {
                    $score_latest = $score_latest.'%';
                }
            }
    		echo Display::tag('td', $score_latest);

    		if (is_numeric($progress)) {
    			$progress = $progress.'%';
    		} else {
    			$progress = '-';
    		}

    		echo Display::tag('td', $progress);
    	    //Do not change with api_convert_and_format_date, because this value came from the lp_item_view table
            //which implies several other changes not a priority right now
    		echo Display::tag('td', $start_time);

    		if ($any_result === true) {
    			$from = '';
    			if ($from_myspace) {
    				$from ='&from=myspace';
    			}
    			$link = Display::url(
                    Display::return_icon('2rightarrow.gif'),
                    'lp_tracking.php?course='.Security::remove_XSS($_GET['course']).$from.'&origin='.Security::remove_XSS($_GET['origin']).'&lp_id='.$learnpath['id'].'&student_id='.$user_info['user_id'].'&id_session='.$session_id
                );
                echo Display::tag('td', $link);
    		}

    		if (api_is_allowed_to_edit()) {
    			echo '<td>';
    				if ($any_result === true) {
    					echo '<a href="myStudents.php?action=reset_lp&sec_token='.$token.'&course='.Security::remove_XSS($_GET['course']).'&details='.Security::remove_XSS($_GET['details']).'&origin='.Security::remove_XSS($_GET['origin']).'&lp_id='.$learnpath['id'].'&student='.$user_info['user_id'].'&details=true&id_session='.Security::remove_XSS($_GET['id_session']).'">';
    					echo Display::return_icon('clean.png', get_lang('Clean'), '', ICON_SIZE_SMALL).'</a>';
    					echo '</a>';
    				}
    				echo '</td>';
    			echo '</tr>';
    		}
    		$data_learnpath[$i][] = $lp_name;
    		$data_learnpath[$i][] = $progress . '%';
    	}
    } else {
    	//echo '<tr><td colspan="6">'.get_lang('NoLearnpath').'</td></tr>';
    }
    ?>
    </table>

    <table class="data_table">
        <tr>
            <th><?php echo get_lang('Exercices'); ?></th>
            <th><?php echo get_lang('AverageScore').' '.Display :: return_icon('info3.gif', get_lang('AverageScore'), array('align' => 'absmiddle', 'hspace' => '3px')) ?></th>
            <th><?php echo get_lang('Attempts'); ?></th>
            <th><?php echo get_lang('LatestAttempt'); ?></th>
            <th><?php echo get_lang('AllAttempts'); ?></th>
        </tr>
		<?php

		$csv_content[] = array();
		$csv_content[] = array(
			get_lang('Exercices'),
			get_lang('Score'),
			get_lang('Attempts')
		);

		$t_quiz = Database :: get_course_table(TABLE_QUIZ_TEST);
		$sql_exercices = "SELECT quiz.title, iid FROM " . $t_quiz . " AS quiz
						  WHERE quiz.c_id =  ".$courseId." AND
						  		active='1' AND
								(quiz.session_id = $session_id OR quiz.session_id = 0)
                          ORDER BY quiz.title ASC ";

        $result_exercices = Database::query($sql_exercices);
		$i = 0;
		if (Database :: num_rows($result_exercices) > 0) {
			while ($exercices = Database :: fetch_array($result_exercices)) {
				$exercise_id = intval($exercices['iid']);

				$count_attempts   = Tracking::count_student_exercise_attempts($student_id, $courseId, $exercise_id, 0, 0, $session_id);
				$score_percentage = Tracking::get_avg_student_exercise_score($student_id, $courseId, $exercise_id, $session_id);

				$csv_content[] = array (
					$exercices['title'],
					$score_percentage . '%',
					$count_attempts
				);

				if ($i % 2)
                    $css_class = 'row_odd';
				else
                    $css_class = 'row_even';

				echo '<tr class="'.$css_class.'"><td>'.$exercices['title'].'</td>';
				echo '<td>';

				if ($count_attempts > 0) {
					echo $score_percentage . '%';
				} else {
					echo '-';
					$score_percentage = 0;
				}

				echo '</td>';
				echo '<td>'.$count_attempts.'</td>';
				echo '<td>';

				$sql_last_attempt = 'SELECT exe_id FROM ' . $tbl_stats_exercices . '
				                     WHERE  exe_exo_id      ="'.$exercise_id.'" AND
				                            exe_user_id     ="'.$student_id.'" AND
				                            c_id    = '.$courseId.' AND
                                            session_id      ="'.$session_id.'" AND
				                            status          = "" AND
				                            orig_lp_id      = 0 AND
				                            orig_lp_item_id = 0
				                            ORDER BY exe_date DESC LIMIT 1';
				$result_last_attempt = Database::query($sql_last_attempt);
				if (Database :: num_rows($result_last_attempt) > 0) {
					$id_last_attempt = Database :: result($result_last_attempt, 0, 0);
					if ($count_attempts > 0) {
                        if (api_coach_can_edit_view_results($courseId, $session_id)) {
                            echo '<a href="../exercice/exercise_show.php?id=' . $id_last_attempt . '&cidReq='.$courseInfo['code'].'&session_id='.$session_id.'&student='.$student_id.'&origin='.(empty($_GET['origin'])?'tracking':Security::remove_XSS($_GET['origin'])).'"> <img src="' . api_get_path(WEB_IMG_PATH) . 'quiz.gif" border="0" /> </a>';
                        } else {
                            echo Display::return_icon('quiz_na.png', get_lang('Exercise'));
                        }
                    }
				}
				echo '</td>';
				echo '<td>';
				$all_attempt_url = "../exercice/exercise_report.php?exerciseId=$exercise_id&cidReq=".$courseInfo['code']."&filter_by_user=$student_id&id_session=$session_id";
				echo Display::url(Display::return_icon('test_results.png', get_lang('AllAttempts'), array(), ICON_SIZE_SMALL), $all_attempt_url );
				echo '</td></tr>';
				$data_exercices[$i][] = $exercices['title'];
				$data_exercices[$i][] = $score_percentage . '%';
				$data_exercices[$i][] = $count_attempts;
				$i++;
			}
		} else {
			echo '<tr><td colspan="6">'.get_lang('NoExercise').'</td></tr>';
		}
		echo '</table>';


        //@when using sessions we do not show the survey list
        if (empty($session_id)) {
            $survey_list = survey_manager::get_surveys($courseInfo['code'], $session_id);

            $survey_data = array();
            foreach($survey_list as $survey) {
                $user_list = survey_manager::get_people_who_filled_survey($survey['survey_id'], false, $courseId);
                $survey_done = Display::return_icon("accept_na.png", get_lang('NoAnswer'), array(), ICON_SIZE_SMALL);
                if (in_array($student_id, $user_list)) {
                     $survey_done = Display::return_icon("accept.png", get_lang('Answered'), array(), ICON_SIZE_SMALL);
                }
                $data = array('title' => $survey['title'], 'done' => $survey_done);
                $survey_data[] = $data;
            }

            if (!empty($survey_list)) {

                $table = new HTML_Table(array('class' => 'data_table'));
                $header_names = array(get_lang('Survey'), get_lang('Answered'));
                $row = 0;
                $column = 0;
                foreach ($header_names as $item) {
                    $table->setHeaderContents($row, $column, $item);
                    $column++;
                }
                $row = 1;
                if (!empty($survey_data)) {
                    foreach ($survey_data as $data) {
                        $column = 0;
                        $table->setCellContents($row, $column, $data);
                        //$table->setRowAttributes($row, 'style="text-align:center"');
                        $class = 'class="row_odd"';
                        if($row % 2) {
                            $class = 'class="row_even"';
                        }
                        $table->setRowAttributes($row, $class, true);
                        $column++;
                        $row++;
                    }
                }
                echo $table->toHtml();
            }
         }

	    // line about other tools
		echo '<table class="data_table">';

		$csv_content[] = array ();
		$nb_assignments 		= Tracking::count_student_assignments($student_id, $courseId, $session_id);
		$messages 				= Tracking::count_student_messages($student_id, $courseId, $session_id);
		$links 					= Tracking::count_student_visited_links($student_id, $courseId, $session_id);
		$chat_last_connection 	= Tracking::chat_last_connection($student_id, $courseId, $session_id);
		$documents				= Tracking::count_student_downloaded_documents($student_id, $courseId, $session_id);
		$uploaded_documents		= Tracking::count_student_uploaded_documents($student_id, $courseId, $session_id);

		$csv_content[] = array (
			get_lang('Student_publication'),
			$nb_assignments
		);
		$csv_content[] = array (
			get_lang('Messages'),
			$messages
		);
		$csv_content[] = array (
			get_lang('LinksDetails'),
			$links
		);
		$csv_content[] = array (
			get_lang('DocumentsDetails'),
			$documents
		);
		$csv_content[] = array (
			get_lang('UploadedDocuments'),
			$uploaded_documents
		);
		$csv_content[] = array (
			get_lang('ChatLastConnection'),
			$chat_last_connection
		);
?>
		<tr>
			<th colspan="2"><?php echo get_lang('OtherTools'); ?></th>
		</tr>
		<tr><!-- assignments -->
			<td width="40%"><?php echo get_lang('Student_publication') ?></td>
			<td><?php echo $nb_assignments ?></td>
		</tr>
		<tr><!-- messages -->
			<td><?php echo get_lang('Messages') ?></td>
			<td><?php echo $messages ?></td>
		</tr>
		<tr><!-- links -->
			<td><?php echo get_lang('LinksDetails') ?></td>
			<td><?php echo $links ?></td>
		</tr>
		<tr><!-- downloaded documents -->
			<td><?php echo get_lang('DocumentsDetails') ?></td>
			<td><?php echo $documents ?></td>
		</tr>
        <tr><!-- uploaded documents -->
			<td><?php echo get_lang('UploadedDocuments') ?></td>
			<td><?php echo $uploaded_documents ?></td>
		</tr>
		<tr><!-- Chats -->
			<td><?php echo get_lang('ChatLastConnection') ?></td>
			<td><?php echo $chat_last_connection; ?></td>
		</tr>
	</table>
	</td>
</tr>
</table>

<?php
	} //end details
}
if ($export_csv) {
	ob_end_clean();
	Export :: export_table_csv($csv_content, 'reporting_student');
	exit;
}
Display :: display_footer();
