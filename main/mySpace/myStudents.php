<?php
//$Id: myStudents.php 21874 2009-07-08 08:45:18Z herodoto $
/* For licensing terms, see /dokeos_license.txt */
/**
 * Implements the tracking of students in the Reporting pages
 * @package dokeos.mySpace
 */

// name of the language file that needs to be included
$language_file = array('registration', 'index', 'tracking', 'exercice', 'admin', 'gradebook');


require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'mySpace/myspace.lib.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/gradebookitem.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evaluation.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/result.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/linkfactory.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/category.class.php';
require_once api_get_path(LIBRARY_PATH).'attendance.lib.php';
require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';

$htmlHeadXtra[] = '<script type="text/javascript">

function show_image(image,width,height) {
	width = parseInt(width) + 20;
	height = parseInt(height) + 20;
	window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');
}

</script>';

$export_csv = isset ($_GET['export']) && $_GET['export'] == 'csv' ? true : false;

if ($export_csv) {
	ob_start();
}
$csv_content = array ();

$from_myspace = false;
if (isset ($_GET['from']) && $_GET['from'] == 'myspace') {
	$from_myspace = true;
	$this_section = "session_my_space";
} else {
	$this_section = SECTION_COURSES;
}

$nameTools = get_lang("StudentDetails");
$cidReset = true;
$get_course_code = Security :: remove_XSS($_GET['course']);
if (isset ($_GET['details'])) {
	if (!empty ($_GET['origin']) && $_GET['origin'] == 'user_course') {
		$course_info = CourseManager :: get_course_information($get_course_code);
		if (empty ($cidReq)) {
			$interbreadcrumb[] = array (
				"url" => api_get_path(WEB_COURSE_PATH) . $course_info['directory'],
				'name' => $course_info['title']
			);
		}
		$interbreadcrumb[] = array (
			"url" => "../user/user.php?cidReq=" . $get_course_code,
			"name" => get_lang("Users")
		);
	} else
		if (!empty ($_GET['origin']) && $_GET['origin'] == 'tracking_course') {
			$course_info = CourseManager :: get_course_information($get_course_code);
			if (empty ($cidReq)) {
				//$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$course_info['directory'], 'name' => $course_info['title']);
			}
			$interbreadcrumb[] = array (
				"url" => "../tracking/courseLog.php?cidReq=" . $get_course_code . '&studentlist=true&id_session=' . (empty ($_SESSION['id_session']) ? '' : $_SESSION['id_session']),
				"name" => get_lang("Tracking")
			);
		} else
			if (!empty ($_GET['origin']) && $_GET['origin'] == 'resume_session') {
				$interbreadcrumb[] = array (
					'url' => '../admin/index.php',
					"name" => get_lang('PlatformAdmin')
				);
				$interbreadcrumb[] = array (
					'url' => "../admin/session_list.php",
					"name" => get_lang('SessionList')
				);
				$interbreadcrumb[] = array (
					'url' => "../admin/resume_session.php?id_session=" . Security :: remove_XSS($_GET['id_session']),
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
			'url' => '../admin/index.php',
			"name" => get_lang('PlatformAdmin')
		);
		$interbreadcrumb[] = array (
			'url' => "../admin/session_list.php",
			"name" => get_lang('SessionList')
		);
		$interbreadcrumb[] = array (
			'url' => "../admin/resume_session.php?id_session=" . Security :: remove_XSS($_GET['id_session']),
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

api_block_anonymous_users();

if (!api_is_allowed_to_edit() && !api_is_coach() && !api_is_drh() && $_user['status'] != SESSIONADMIN && !api_is_platform_admin(true)) {
	api_not_allowed(true);
}

Display :: display_header($nameTools);

/*
 *===============================================================================
 *	MAIN CODE
 *===============================================================================
 */
// Database Table Definitions
$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
$tbl_session_user = Database :: get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_stats_exercices = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$tbl_stats_exercices_attempts = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);

$tbl_course_lp_view = 'lp_view';
$tbl_course_lp_view_item = 'lp_item_view';
$tbl_course_lp_item = 'lp_item';
$tbl_course_lp = 'lp';
$tbl_course_quiz = 'quiz';
$course_quiz_question = 'quiz_question';
$course_quiz_rel_question = 'quiz_rel_question';
$course_quiz_answer = 'quiz_answer';
$course_student_publication = Database :: get_course_table(TABLE_STUDENT_PUBLICATION);

if (isset($_GET['user_id']) && $_GET['user_id'] != "") {
	$user_id = intval($_GET['user_id']);
} else {
	$user_id = $_user['user_id'];
}

$session_id = intval($_GET['id_session']);
if (!empty ($_GET['student'])) {

	$student_id = intval($_GET['student']);

	// infos about user
	$info_user = UserManager :: get_user_info_by_id($student_id);
	if (api_is_drh() && !UserManager::is_user_followed_by_drh($student_id, $_user['user_id'])) {
		api_not_allowed();
	}

	$info_user['name'] = api_get_person_name($info_user['firstname'], $info_user['lastname']);

	// Actions bar
	echo '<div class="actions">';
	echo '<a href="javascript: void(0);" onclick="javascript: window.print();"><img src="../img/printmgr.gif">&nbsp;' . get_lang('Print') . '</a>';
	echo '<a href="' . api_get_self() . '?' . Security :: remove_XSS($_SERVER['QUERY_STRING']) . '&export=csv"><img src="../img/excel.gif">&nbsp;' . get_lang('ExportAsCSV') . '</a>';
	if (!empty ($info_user['email'])) {
		$send_mail = Display :: return_icon('send_mail.gif', get_lang('SendMail')) . ' ' . Display :: encrypted_mailto_link($info_user['email'], get_lang('SendMail'));
	} else {
		$send_mail = Display :: return_icon('send_mail.gif', get_lang('SendMail')) . ' ' . get_lang('SendMail');
	}
	echo $send_mail;
	if (!empty ($_GET['student']) && !empty ($_GET['course'])) { //only show link to connection details if course and student were defined in the URL
		echo '<a href="access_details.php?student=' . Security :: remove_XSS($_GET['student']) . '&course=' . Security :: remove_XSS($_GET['course']) . '&amp;origin=' . Security :: remove_XSS($_GET['origin']) . '&amp;cidReq='.Security::remove_XSS($_GET['course']).'&amp;id_session='.$session_id.'">' . Display :: return_icon('statistics.gif', get_lang('AccessDetails')) . ' ' . get_lang('AccessDetails') . '</a>';
	}
	echo '</div>';

	// is the user online ?
	$student_online = Security :: remove_XSS($_GET['student']);
	$users_online = WhoIsOnline(30);
	foreach ($users_online as $online) {
		if (in_array($_GET['student'], $online)) {
			$online = get_lang('Yes');
			break;
		} else {
			$online = get_lang('No');
		}
	}
		
	// get average of score and average of progress by student
	$avg_student_progress = $avg_student_score = $nb_courses = 0;
	$course_id = Security :: remove_XSS($_GET['course']);	
	if (!CourseManager :: is_user_subscribed_in_course($info_user['user_id'], $course_id, true)) {
		unset ($courses[$key]);
	} else {
		$nb_courses++;
		$avg_student_progress = Tracking::get_avg_student_progress($info_user['user_id'], $course_id, array(), $session_id);
		//the score inside the Reporting table
		$avg_student_score = Tracking::get_avg_student_score($info_user['user_id'], $course_id, array(), $session_id);	
	}
	$avg_student_progress = round($avg_student_progress, 2);
	$avg_student_score = round($avg_student_score, 2);

	// time spent on the course
	$time_spent_on_the_course = api_time_to_hms(Tracking :: get_time_spent_on_the_course($info_user['user_id'], $course_id, $session_id));
	
	// get information about connections on the platform by student
	$first_connection_date = Tracking :: get_first_connection_date($info_user['user_id']);
	if ($first_connection_date == '') {
		$first_connection_date = get_lang('NoConnexion');
	}

	$last_connection_date = Tracking :: get_last_connection_date($info_user['user_id'], true);
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
		$info_user['name'],
		$info_user['email'],
		$info_user['phone']
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
		$avg_student_score . '%'
	);
?>
	<a name="infosStudent"></a>
				<table width="100%" border="0" >
					<tr>
<?php
	$image_array = UserManager :: get_user_picture_path_by_id($info_user['user_id'], 'web', false, true);
	echo '<td class="borderRight" width="10%" valign="top">';

	// get the path,width and height from original picture
	$image_file = $image_array['dir'] . $image_array['file'];
	$big_image = $image_array['dir'] . 'big_' . $image_array['file'];
	$big_image_size = api_getimagesize($big_image);
	$big_image_width = $big_image_size[0];
	$big_image_height = $big_image_size[1];
	$url_big_image = $big_image . '?rnd=' . time();
	$img_attributes = 'src="' . $image_file . '?rand=' . time() . '" ' .
	'alt="' . api_get_person_name($info_user['lastname'], $info_user['firstname']) . '" ' .
	'style="float:' . ($text_dir == 'rtl' ? 'left' : 'right') . '; padding:5px;" ';

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
						<td>
				<?php
						echo get_lang('Name') . ' : ';
						echo $info_user['name'];
				?>
						</td>
					</tr>
					<tr>
						<td>
				<?php
						echo get_lang('Email') . ' : ';
						if (!empty ($info_user['email'])) {
							echo '<a href="mailto:' . $info_user['email'] . '">' . $info_user['email'] . '</a>';
						} else {
							echo get_lang('NoEmail');
						}
				?>
						</td>
					</tr>
					<tr>
						<td>
				<?php
						echo get_lang('Tel') . ' : ';
						if (!empty ($info_user['phone'])) {
							echo $info_user['phone'];
						} else {
							echo get_lang('NoTel');
						}
				?>
						</td>
					</tr>
					<tr>
						<td>
				<?php
						echo get_lang('OfficialCode') . ' : ';
						if (!empty ($info_user['official_code'])) {
							echo $info_user['official_code'];
						} else {
							echo get_lang('NoOfficialCode');
						}
				?>
						</td>
					</tr>
					<tr>
						<td>
				<?php
						echo get_lang('OnLine') . ' : ';
						echo $online;
				?>
						</td>
					</tr>
<?php
// Display timezone if the user selected one and if the admin allows the use of user's timezone
$timezone = null;
$timezone_user = UserManager::get_extra_user_data_by_field($info_user['user_id'],'timezone');
$use_users_timezone = api_get_setting('use_users_timezone', 'timezones');
if ($timezone_user['timezone'] != null && $use_users_timezone == 'true') {
	$timezone = $timezone_user['timezone'];
}
if ($timezone !== null) {
?>
	<tr>
		<td>
		<?php
			echo get_lang('Timezone') . ' : ';
			echo $timezone;
		?>
		</td>
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
						<td align="right">
										<?php
											echo get_lang('Progress');
											Display :: display_icon('info3.gif', get_lang('ScormAndLPProgressTotalAverage'), array ('align' => 'absmiddle', 'hspace' => '3px'));
										?>
						</td>
						<td align="left"><?php echo $avg_student_progress.'%' ?></td>
					</tr>
					<tr>
						<td align="right">
										<?php
											echo get_lang('Score');
											Display :: display_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), array ('align' => 'absmiddle', 'hspace' => '3px'));
										?>
						</td>
						<td align="left"><?php  echo $avg_student_score.'%' ?></td>
					</tr>
					<?php } ?>
				</table>
			</td>
		</tr>
	</table>
	
<?php

	if (!empty ($_GET['details'])) { ?>

		<table class="data_table">
		<tr>
			<td colspan="5" style="border-width: 0px;">&nbsp;</td>
		</tr>
		
		<?php
		$course_code = $_GET['course'];
		$info_course = CourseManager :: get_course_information($course_code);		
		$coachs_name  = '';
		$session_name = '';		
		$nb_login = Tracking :: count_login_per_student($info_user['user_id'], $_GET['course']);
		//get coach and session_name if there is one and if session_mode is activated
		if (api_get_setting('use_session_mode') == 'true' && $session_id > 0) {
			
			$session_info  = api_get_session_info($session_id);			
			$course_coachs = api_get_coachs_from_course($session_id,$course_code);
			$nb_login = '';			
			if (!empty($course_coachs)) {
				$info_tutor_name = array();
				foreach ($course_coachs as $course_coach) {
					$info_tutor_name[] = api_get_person_name($course_coach['firstname'], $course_coach['lastname']);
				}
				$info_course['tutor_name'] = implode(",",$info_tutor_name);
			} elseif ($session_coach_id != 0) {					
				$session_coach_id = intval($session_info['id_coach']);		
				$coach_info = UserManager::get_user_info_by_id($session_coach_id);
				$info_course['tutor_name'] = api_get_person_name($coach_info['firstname'], $coach_info['lastname']);
			}
			$coachs_name  = $info_course['tutor_name'];
			$session_name = $session_info['name'];
		} // end if(api_get_setting('use_session_mode')=='true')

		$table_title = $info_course['title'].($nb_login?'&nbsp;|&nbsp;'.get_lang('CountToolAccess').' : '.$nb_login:'').($session_name?'&nbsp;|&nbsp;'.get_lang('Session').' : '.ucfirst($session_name):'').($coachs_name?'&nbsp;|&nbsp;'.get_lang('Tutor').' : '.stripslashes($coachs_name):'');

		$csv_content[] = array ();
		$csv_content[] = array (str_replace('&nbsp;', '', $table_title));
?>
		<tr>
			<td colspan="6">
					<strong><?php echo $table_title; ?></strong>
			</td>
		</tr>
	</table>

	<!-- line about learnpaths -->
				<table class="data_table">
					<tr>
						<th>
							<?php echo get_lang('Learnpaths');?>
						</th>
						<th>
							<?php

		echo get_lang('Time');
		Display :: display_icon('info3.gif', get_lang('TotalTimeByCourse'), array (
			'align' => 'absmiddle',
			'hspace' => '3px'
		));
?>
						</th>
						<th>
							<?php

		echo get_lang('Score');
		Display :: display_icon('info3.gif', get_lang('LPTestScore'), array (
			'align' => 'absmiddle',
			'hspace' => '3px'
		));
?>
						</th>
						<th>
							<?php

		echo get_lang('Progress');
		Display :: display_icon('info3.gif', get_lang('LPProgressScore'), array (
			'align' => 'absmiddle',
			'hspace' => '3px'
		));
?>
						</th>
						<th>
							<?php

		echo get_lang('LastConnexion');
		Display :: display_icon('info3.gif', get_lang('LastTimeTheCourseWasUsed'), array (
			'align' => 'absmiddle',
			'hspace' => '3px'
		));
?>
						</th>
						<th>
							<?php echo get_lang('Details');?>
						</th>
					</tr>
<?php

		$headerLearnpath = array (
			get_lang('Learnpath'),
			get_lang('Time'),
			get_lang('Progress'),
			get_lang('LastConnexion')
		);

		$t_lp = Database :: get_course_table(TABLE_LP_MAIN, $info_course['db_name']);
		$tbl_stats_exercices = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
		$tbl_quiz_questions = Database :: get_course_table(TABLE_QUIZ_QUESTION, $info_course['db_name']);

		// csv export headers
		$csv_content[] = array ();
		$csv_content[] = array (
			get_lang('Learnpath', ''),
			get_lang('Time', ''),
			get_lang('Score', ''),
			get_lang('Progress', ''),
			get_lang('LastConnexion', '')
		);

		$sql_lp = "	SELECT lp.name, lp.id FROM $t_lp lp WHERE lp.session_id = $session_id ORDER BY lp.name ASC";
		$rs_lp = Database::query($sql_lp);

		if (Database :: num_rows($rs_lp) > 0) {
			$i = 0;
			while ($learnpath = Database :: fetch_array($rs_lp)) {
				
				$lp_id = intval($learnpath['id']);
				$lp_name = $learnpath['name'];
				$any_result = false;
				
				// Get progress in lp				
				$progress = Tracking::get_avg_student_progress($student_id, $course_code, array($lp_id));
				
				if ($progress === null) $progress = '0%';
				else $any_result = true;
				
				// Get time in lp
				$total_time = Tracking::get_time_spent_in_lp($student_id, $course_code, array($lp_id));				
				if (!empty($total_time)) $any_result = true;
				
				// Get last connection time in lp
				$start_time = Tracking::get_last_connection_time_in_lp($student_id, $course_code, $lp_id);
				if (!empty($total_time)) $any_result = true;
			
				// Quizz in lp
				$score = Tracking :: get_avg_student_score($student_id, $course_code, array($lp_id));

				if ($i % 2 == 0) $css_class = "row_odd";
				else $css_class = "row_even";
		
				$i++;

				// csv export content
				$csv_content[] = array (
					api_html_entity_decode(stripslashes($lp_name), ENT_QUOTES, $charset),
					api_time_to_hms($total_time),
					$score . '%',
					$progress,
					api_convert_and_format_date($start_time, "%Y-%m-%d")
				);
?>
					<tr class="<?php echo $css_class;?>">
						<td>
							<?php echo stripslashes($lp_name); ?>
						</td>
						<td align="center">
						<?php echo api_time_to_hms($total_time) ?>
						</td>
						<td align="center">
							<?php

				if (!is_null($score)) {
					echo $score . '%';
				} else {
					if ('0' == $progress {
						0 }) {
						echo '/';
					} else {
						echo '0%';
					}
					$score = 0;
				}
?>
						</td>
						<td align="center">
							<?php echo $progress ?>
						</td>
						<td align="center">
							<?php
				if ($start_time != '' && $start_time > 0) {
					echo api_convert_and_format_date($start_time, DATE_FORMAT_SHORT);
				} else {
					echo '-';
				}
?>
						</td>
						<td align="center">
							<?php
				if ($any_result === true) {
					$from = '';
					if ($from_myspace) {
						$from ='&from=myspace';
					}
?>
					<a href="lp_tracking.php?course=<?php echo Security::remove_XSS($_GET['course']).$from; ?>&origin=<?php echo Security::remove_XSS($_GET['origin']) ?>&lp_id=<?php echo $learnpath['id']?>&student_id=<?php echo $info_user['user_id'] ?>&session_id=<?php echo $session_id ?>">
						<img src="../img/2rightarrow.gif" border="0" />
					</a>
					<?php
				}
?>
						</td>
					</tr>
				<?php
				$data_learnpath[$i][] = $lp_name;
				$data_learnpath[$i][] = $progress . '%';
				$i++;
			}
		} else {
			echo '<tr><td colspan="6">'.get_lang('NoLearnpath').'</td></tr>';
		}
?>
				</table>

	<!-- line about exercises -->
			<table class="data_table">
				<tr>
					<th><?php echo get_lang('Exercices'); ?></th>
					<th><?php echo get_lang('Score').Display :: return_icon('info3.gif', get_lang('LastScoreTest'), array('align' => 'absmiddle', 'hspace' => '3px')) ?></th>
					<th><?php echo get_lang('Attempts'); ?></th>
					<th><?php echo get_lang('CorrectTest'); ?></th>
				</tr>
			<?php

		$csv_content[] = array ();
		$csv_content[] = array (
			get_lang('Exercices'),
			get_lang('Score'),
			get_lang('Attempts')
		);

		$t_tool = Database :: get_course_table(TABLE_TOOL_LIST, $info_course['db_name']);
		$sql = 'SELECT visibility FROM ' . $t_tool . ' WHERE name="quiz"';
		$result_visibility_quizz = Database::query($sql);		
		$t_quiz = Database :: get_course_table(TABLE_QUIZ_TEST, $info_course['db_name']);

		if (Database :: result($result_visibility_quizz, 0, 'visibility') == 1) {

			$sql_exercices = "SELECT quiz.title,id
												FROM " . $t_quiz . " AS quiz
												WHERE active='1' AND quiz.session_id = $session_id ORDER BY quiz.title ASC
												";

			$result_exercices = Database::query($sql_exercices);
			$i = 0;
			if (Database :: num_rows($result_exercices) > 0) {
				while ($exercices = Database :: fetch_array($result_exercices)) {					
					$exercise_id = intval($exercices['id']);
					$count_attempts   = Tracking::count_student_exercise_attempts($student_id, $course_code, $exercise_id);
					$score_percentage = Tracking::get_avg_student_exercise_score($student_id, $course_code, $exercise_id);

					$csv_content[] = array (
						$exercices['title'],
						$score_percentage . '%',
						$count_attempts
					);

					if ($i % 2 == 0) $css_class = 'row_odd';
					else $css_class = 'row_even';

					echo '<tr class="'.$css_class.'"><td>'.$exercices['title'].'</td>';
					
					echo '<td align="center">';
												
					if ($count_attempts > 0) {
						echo $score_percentage . '%';
					} else {
						echo '/';
						$score_percentage = 0;
					}

					echo '</td>';
					echo '<td align="center">'.$count_attempts.'</td>';
					echo '<td align="center">';

					$sql_last_attempt = 'SELECT exe_id FROM ' . $tbl_stats_exercices . ' WHERE exe_exo_id="'.$exercise_id.'" AND exe_user_id="'.$student_id.'" AND exe_cours_id="'.$course_code.'" AND orig_lp_id = 0 AND orig_lp_item_id = 0 ORDER BY exe_date DESC LIMIT 1';
					$result_last_attempt = Database::query($sql_last_attempt);
					if (Database :: num_rows($result_last_attempt) > 0) {
						$id_last_attempt = Database :: result($result_last_attempt, 0, 0);
						if ($count_attempts > 0)
							echo '<a href="../exercice/exercise_show.php?id=' . $id_last_attempt . '&cidReq='.$course_code.'&student='.$student_id.'&origin='.(empty($_GET['origin'])?'tracking':Security::remove_XSS($_GET['origin'])).'"> <img src="' . api_get_path(WEB_IMG_PATH) . 'quiz.gif" border="0" /> </a>';
					}
					echo '</td></tr>';
					$data_exercices[$i][] = $exercices['title'];
					$data_exercices[$i][] = $score_percentage . '%';
					$data_exercices[$i][] = $count_attempts;
					$i++;

				}
			} else {
				echo '<tr><td colspan="6">'.get_lang('NoExercise').'</td></tr>';
			}
		} else {
			echo '<tr><td colspan="6">'.get_lang('NoExercise').'</td></tr>';
		}
?>
					</table>

	<!-- line about other tools -->
			<table class="data_table">
	<tr>
		<td>
			<?php

		$csv_content[] = array ();
		$nb_assignments 		= Tracking::count_student_assignments($student_id, $course_code, $session_id);
		$messages 				= Tracking::count_student_messages($student_id, $course_code, $session_id);
		$links 					= Tracking::count_student_visited_links($student_id, $course_code, $session_id);
		$chat_last_connection 	= Tracking::chat_last_connection($student_id, $course_code, $session_id);			
		$documents				= Tracking::count_student_downloaded_documents($student_id, $course_code, $session_id);

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
			get_lang('ChatLastConnection'),
			$chat_last_connection
		);
?>
				<tr>
					<th colspan="2">
						<?php echo get_lang('OtherTools'); ?>
					</th>
				</tr>
				<tr><!-- assignments -->
					<td width="40%">
						<?php echo get_lang('Student_publication') ?>
					</td>
					<td>
						<?php echo $nb_assignments ?>
					</td>
				</tr>
				<tr><!-- messages -->
					<td>
						<?php echo get_lang('Messages') ?>
					</td>
					<td>
						<?php echo $messages ?>
					</td>
				</tr>
				<tr><!-- links -->
					<td>
						<?php echo get_lang('LinksDetails') ?>
					</td>
					<td>
						<?php echo $links ?>
					</td>
				</tr>
				<tr><!-- documents -->
					<td>
						<?php echo get_lang('DocumentsDetails') ?>
					</td>
					<td>
						<?php echo $documents ?>
					</td>
				</tr>
				<tr><!-- Chats -->
					<td>
						<?php echo get_lang('ChatLastConnection') ?>
					</td>
					<td>
						<?php echo $chat_last_connection; ?>
					</td>
				</tr>
			</table>
			</td>
		</tr>
		</table>
	
	</table>
	<br />
<?php

	} else {
		
		$courses_in_session = array ();
		if (!api_is_platform_admin(true) && $_user['status'] != DRH) {
			// courses followed by user where we are coach
			if (!isset ($_GET['id_coach'])) {				
				$sessions_coached_by_user = Tracking::get_sessions_coached_by_user($_user['user_id']);
				foreach ($sessions_coached_by_user as $session_coached_by_user) {
					$sid = intval($session_coached_by_user['id']);
					$courses_followed_by_coach = Tracking :: get_courses_followed_by_coach($_user['user_id'], $sid);
					$courses_in_session[$sid] = $courses_followed_by_coach;						
				}
			} else {				
				$sessions_coached_by_user = Tracking::get_sessions_coached_by_user(intval($_GET['id_coach']));
				foreach ($sessions_coached_by_user as $session_coached_by_user) {
					$sid = intval($session_coached_by_user['id']);
					$courses_followed_by_coach = Tracking :: get_courses_followed_by_coach(intval($_GET['id_coach']), $sid);
					$courses_in_session[$sid] = $courses_followed_by_coach;						
				}
			}
		} else {
			$sql = 'SELECT course_code FROM ' . $tbl_course_user . ' WHERE relation_type<>'.COURSE_RELATION_TYPE_RRHH.' AND user_id=' . Database :: escape_string($info_user['user_id']);
			$rs = Database::query($sql);
			
			while ($row = Database :: fetch_array($rs)) {
				$courses_in_session[0][] = $row['course_code'];
			}
		
			// get the list of sessions where the user is subscribed as student
			$sql = 'SELECT id_session, course_code FROM ' . Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER) . ' WHERE id_user=' . intval($info_user['user_id']);
			$rs = Database::query($sql);
			$tmp_sessions = array();
			while ($row = Database :: fetch_array($rs)) {
				$tmp_sessions[] = $row['id_session'];
				if (in_array($row['id_session'],$tmp_sessions)) {
					$courses_in_session[$row['id_session']][] = $row['course_code'];	
				}
			}
		}

		$csv_content[] = array ();
		$csv_content[] = array (
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
			
			$session_id = $key;
			$session_info = api_get_session_info($session_id);
			$session_name = $session_info['name']; 

			$date_start = '';
			if (!empty ($session_info['date_start'])) {
				$date_start = explode('-', $session_info['date_start']);
				$date_start = $date_start[2] . '/' . $date_start[1] . '/' . $date_start[0];
			}
			$date_end = '';
			if (!empty ($session_info['date_end'])) {
				$date_end = explode('-', $session_info['date_end']);
				$date_end = $date_end[2] . '/' . $date_end[1] . '/' . $date_end[0];
			}
			
			$date_session = get_lang('From') . ' ' . $date_start . ' ' . get_lang('To') . ' ' . $date_end;

			$title = '';
			if (empty($session_id)) {
				$title = get_lang('TrainingsWithoutSessions');
			} else {
				$title = ucfirst($session_name).($date_session?' ('.$date_session.')':'');
			}
			
			// courses
			
			echo '<h4>'.$title.'</h4>';
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
				foreach ($courses as $course_code) {
					if (CourseManager :: is_user_subscribed_in_course($student_id, $course_code, true)) {
						$course_info = CourseManager :: get_course_information($course_code);

						
						$time_spent_on_course = api_time_to_hms(Tracking :: get_time_spent_on_the_course($info_user['user_id'], $course_code, $session_id));
						
						// get average of faults in attendances by student	 						 			
			 			$results_faults_avg = $attendance->get_faults_average_by_course($student_id, $course_code, $session_id);
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
			 			if (isset($cats)) {
			 				if (!empty($session_id)) {
			 					$scoretotal= $cats[0]->calc_score($student_id, $course_code, $session_id);	
			 				} else {
			 					$scoretotal= $cats[0]->calc_score($student_id, $course_code);
			 				}
			 			} 	

			 			$scoretotal_display = '0/0 (0%)';
			 			if (!empty($scoretotal)) {
			 				/*
			 				if (api_is_drh()) {			 								 					
			 					$session_param = !empty($session_id)?'&id_session='.$session_id:'';
			 					$scoretotal_display =  '<a title="'.get_lang('GoAssessments').'" href="'.api_get_path(WEB_CODE_PATH).'gradebook/index.php?cidReq='.$course_code.'&id_session='.$session_id.'&student_id='.$student_id.'">'.round($scoretotal[0],2).'/'.round($scoretotal[1],2).'('.round(($scoretotal[0] / $scoretotal[1]) * 100,2) . ' %)</a>';	
			 				} else {
			 					$scoretotal_display =  round($scoretotal[0],2).'/'.round($scoretotal[1],2).'('.round(($scoretotal[0] / $scoretotal[1]) * 100,2) . ' %)';
			 				}
			 				*/
			 				$scoretotal_display =  round($scoretotal[0],2).'/'.round($scoretotal[1],2).'('.round(($scoretotal[0] / $scoretotal[1]) * 100,2) . ' %)';
			 			} 
			 			
						$progress = Tracking::get_avg_student_progress($info_user['user_id'], $course_code, null, $session_id);
						$score = Tracking :: get_avg_student_score($info_user['user_id'], $course_code, null, $session_id);
						$progress = empty($progress) ? '0%' : $progress.'%';
						$score = empty($score) ? '0%' : $score.'%';
												
						$csv_content[] = array (
							$session_name,
							$course_info['title'],
							$time_spent_on_course,
							$progress,
							$score,
							$attendances_faults_avg,
							$scoretotal_display
						);
						
						echo '
								<tr>
									<td align="right">'.$course_info['title'].'</td>
									<td align="right">'.$time_spent_on_course .'</td>									
									<td align="right">'.$progress.'</td>											
									<td align="right">'.$score.'</td>
									<td align="right">'.$attendances_faults_avg.'</td>
									<td align="right">'.$scoretotal_display.'</td>';
												
						if (isset ($_GET['id_coach']) && intval($_GET['id_coach']) != 0) {
							echo '<td align="center" width="10"><a href="'.api_get_self().'?student='.$info_user['user_id'].'&details=true&course='.$course_info['code'].'&id_coach='.Security::remove_XSS($_GET['id_coach']).'&origin='.Security::remove_XSS($_GET['origin']).'&id_session='.$session_id.'#infosStudent"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td>';
						} else {
							echo '<td align="center" width="10"><a href="'.api_get_self().'?student='.$info_user['user_id'].'&details=true&course='.$course_info['code'].'&origin='.Security::remove_XSS($_GET['origin']).'&id_session='.$session_id.'#infosStudent"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a></td>';
						}
						echo '</tr>';
					}
	
				}
			} else {
					echo "<tr><td colspan='5'>".get_lang('NoCourse')."</td></tr>";
			}
			echo '</table>'; 
												
		}
	} //end of else !empty($details)
?>
	
<?php

	if (!empty ($_GET['details']) && $_GET['origin'] != 'tracking_course' && $_GET['origin'] != 'user_course') {
?>

		<br /><br />
<?php

	}
	if (!empty ($_GET['exe_id'])) {
		$t_q = Database :: get_course_table(TABLE_QUIZ_TEST, $info_course['db_name']);
		$t_qq = Database :: get_course_table(TABLE_QUIZ_QUESTION, $info_course['db_name']);
		$t_qa = Database :: get_course_table(TABLE_QUIZ_ANSWER, $info_course['db_name']);
		$t_qtq = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION, $info_course['db_name']);
		$sql_exercice_details = "SELECT qq.question, qq.ponderation, qq.id
						 				FROM " . $t_qq . " as qq
										INNER JOIN " . $t_qtq . " as qrq
											ON qrq.question_id = qq.id
											AND qrq.exercice_id = " . intval($_GET['exe_id']);

		$result_exercice_details = Database::query($sql_exercice_details);

		$sql_ex_name = "SELECT quiz.title
								FROM " . $t_q . " AS quiz
							 	WHERE quiz.id = " . intval($_GET['exe_id']);
		;

		$resultExName = Database::query($sql_ex_name);
		$exName = Database :: fetch_array($resultExName);

		echo "<table class='data_table'>
					 	<tr>
							<th colspan='2'>
								" . $exName['title'] . "
							</th>
						</tr>
		             ";

		while ($exerciceDetails = Database :: fetch_array($result_exercice_details)) {
			$sqlAnswer = "	SELECT qa.comment, qa.answer
										FROM  " . $t_qa . " as qa
										WHERE qa.question_id = " . $exerciceDetails['id'];

			$resultAnswer = Database::query($sqlAnswer);

			echo "<a name='infosExe'></a>";

			echo "
						<tr>
							<td colspan='2'>
								<strong>" . $exerciceDetails['question'] . ' /' . $exerciceDetails['ponderation'] . "</strong>
							</td>
						</tr>
						";
			while ($answer = Database :: fetch_array($resultAnswer)) {
				echo "
								<tr>
									<td>
										" . $answer['answer'] . "
									</td>
									<td>
								";
				if (!empty ($answer['comment']))
					echo $answer['comment'];
				else
					echo get_lang('NoComment');
				echo "
									</td>
								</tr>
								";
			}
		}
		echo "</table>";
	}
	//YW - commented out because it doesn't seem to be used
	//$header = array_merge($headerLearnpath,$headerExercices,$headerProductions);
}
if ($export_csv) {
	ob_end_clean();
	Export :: export_table_csv($csv_content, 'reporting_student');
	exit;
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
