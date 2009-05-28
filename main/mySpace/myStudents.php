<?php //$Id: myStudents.php 21050 2009-05-28 19:09:21Z ivantcholakov $
/* For licensing terms, see /dokeos_license.txt */
/**
 * Implements the tracking of students in the Reporting pages
 * @package dokeos.mySpace
 */
 
 // name of the language file that needs to be included 
$language_file = array ('registration', 'index', 'tracking', 'exercice','admin');
$cidReset=true;
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once '../newscorm/learnpath.class.php';


$htmlHeadXtra[] = '<script type="text/javascript">
				
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

$this_section = "session_my_space";

$nameTools=get_lang("StudentDetails");
//$nameTools=SECTION_PLATFORM_ADMIN;
 
$get_course_code=Security::remove_XSS($_GET['course']);
if (isset($_GET['details'])) {
 	if (!empty($_GET['origin']) && $_GET['origin'] == 'user_course') {
 		$course_infos = CourseManager :: get_course_information($get_course_code);
 		if (empty($cidReq)) {
 			$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$course_infos['directory'], 'name' => $course_infos['title']);
 		}
 		$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".$get_course_code, "name" => get_lang("Users"));
 	} else if (!empty($_GET['origin']) && $_GET['origin'] == 'tracking_course') {
 		$course_infos = CourseManager :: get_course_information($get_course_code);
 		if (empty($cidReq)) {
 			$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$course_infos['directory'], 'name' => $course_infos['title']);
 		}
 		$interbreadcrumb[] = array ("url" => "../tracking/courseLog.php?cidReq=".$get_course_code.'&studentlist=true&id_session='.(empty($_SESSION['id_session'])?'':$_SESSION['id_session']), "name" => get_lang("Tracking"));
 	} else if (!empty($_GET['origin']) && $_GET['origin'] == 'resume_session') {
		$interbreadcrumb[]=array('url' => '../admin/index.php',"name" => get_lang('PlatformAdmin'));
		$interbreadcrumb[]=array('url' => "../admin/session_list.php","name" => get_lang('SessionList'));
		$interbreadcrumb[]=array('url' => "../admin/resume_session.php?id_session=".Security::remove_XSS($_GET['id_session']),"name" => get_lang('SessionOverview'));
 	} else {
 		$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
 		if (isset($_GET['id_coach']) && intval($_GET['id_coach'])!=0) {
 			$interbreadcrumb[] = array ("url" => "student.php?id_coach=".Security::remove_XSS($_GET['id_coach']), "name" => get_lang("CoachStudents"));
 			$interbreadcrumb[] = array ("url" => "myStudents.php?student=".Security::remove_XSS($_GET['student']).'&id_coach='.Security::remove_XSS($_GET['id_coach']), "name" => get_lang("StudentDetails"));
 		} else {
 			$interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang("MyStudents"));
 			$interbreadcrumb[] = array ("url" => "myStudents.php?student=".Security::remove_XSS($_GET['student']), "name" => get_lang("StudentDetails"));
 		}	 	
 	}
 	$nameTools=get_lang("DetailsStudentInCourse");
} else {
 	if (!empty($_GET['origin']) && $_GET['origin'] == 'resume_session') {
		$interbreadcrumb[]=array('url' => '../admin/index.php',"name" => get_lang('PlatformAdmin'));
		$interbreadcrumb[]=array('url' => "../admin/session_list.php","name" => get_lang('SessionList'));
		$interbreadcrumb[]=array('url' => "../admin/resume_session.php?id_session=".Security::remove_XSS($_GET['id_session']),"name" => get_lang('SessionOverview'));
 	} else {
 		$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
	 	if (isset($_GET['id_coach']) && intval($_GET['id_coach'])!=0) {
	 		if (isset($_GET['id_session']) && intval($_GET['id_session'])!=0) {
	 			$interbreadcrumb[] = array ("url" => "student.php?id_coach=".Security::remove_XSS($_GET['id_coach'])."&id_session=".$_GET['id_session'], "name" => get_lang("CoachStudents"));
	 		} else {
	 			$interbreadcrumb[] = array ("url" => "student.php?id_coach=".Security::remove_XSS($_GET['id_coach']), "name" => get_lang("CoachStudents"));
	 		}
	 	} else {
	 			$interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang("MyStudents"));
	 	}
 	}
}
 
api_block_anonymous_users();

if(empty($_SESSION['is_allowedCreateCourse']) && !api_is_coach() && $_user['status']!=DRH && $_user['status']!=SESSIONADMIN){
	api_not_allowed(true);
}

Display :: display_header($nameTools);
 
 /*
  * ======================================================================================
  * 	FUNCTIONS
  * ======================================================================================
  */
  
function calculHours($seconds)
{
	
  //How many hours ?
  $hours = floor($seconds / 3600);

  //How many minutes ?
  $min = floor(($seconds - ($hours * 3600)) / 60);
  if ($min < 10)
    $min = "0".$min;

  //How many seconds
  $sec = $seconds - ($hours * 3600) - ($min * 60);
  if ($sec < 10)
    $sec = "0".$sec;

  return $hours."h".$min."m".$sec."s" ;

}

function is_teacher($course_code){
	global $_user;
	$tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$sql="SELECT 1 FROM $tbl_course_user WHERE user_id='".$_user["user_id"]."' AND course_code='".Database::escape_string($course_code)."' AND status='1'";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	if(Database::result($result)!=1)
	{
		return true;
	}
	else{
		return false;
	}
}


/*
 *===============================================================================
 *	MAIN CODE
 *===============================================================================  
 */
// Database Table Definitions
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_session_user 			= Database :: get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session 				= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_session_course 		= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_stats_exercices 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$tbl_stats_exercices_attempts 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
//$tbl_course_lp_view 		= Database :: get_course_table('lp_view');
//$tbl_course_lp_view_item = Database :: get_course_table('lp_item_view');
//$tbl_course_lp_item 		= Database :: get_course_table('lp_item');

$tbl_course_lp_view = 'lp_view';
$tbl_course_lp_view_item = 'lp_item_view';
$tbl_course_lp_item = 'lp_item';
$tbl_course_lp = 'lp';
$tbl_course_quiz = 'quiz';
$course_quiz_question = 'quiz_question';
$course_quiz_rel_question = 'quiz_rel_question';
$course_quiz_answer = 'quiz_answer';
$course_student_publication = Database::get_course_table(TABLE_STUDENT_PUBLICATION);


if(isset($_GET["user_id"]) && $_GET["user_id"]!="")
{
	$i_user_id=(int)$_GET["user_id"];
}
else
{
	$i_user_id =$_user['user_id'];
}

if(!empty($_GET['student']))
{
	
	$student_id = intval($_GET['student']);
	
	// infos about user
	$a_infosUser = UserManager::get_user_info_by_id($student_id);
	if($_user['status']==DRH && $a_infosUser['hr_dept_id']!=$_user['user_id'])
	{
		api_not_allowed();
	}
	
	$a_infosUser['name'] = $a_infosUser['firstname'].' '.$a_infosUser['lastname'];
	
	// Actions bar
	echo '<div class="actions">';
	echo '<a href="#" onclick="window.print()"><img src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>';
	echo '<a href="'.api_get_self().'?'.Security::remove_XSS($_SERVER['QUERY_STRING']).'&export=csv"><img src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>';
	if(!empty($a_infosUser['email']))
	{
		$sendMail = Display::encrypted_mailto_link($a_infosUser['email'], /*Display::return_icon('send_mail.gif').' '.*/get_lang('SendMail'));
	}
	else 
	{
		$sendMail = Display::return_icon('send_mail.gif',get_lang('SendMail')).' '.get_lang('SendMail');
	}
	echo $sendMail;
	if(!empty($_GET['student']) && !empty($_GET['course']))
	{   //only show link to connection details if course and student were defined in the URL
		echo '<a href="access_details.php?student='.Security::remove_XSS($_GET['student']).'&course='.Security::remove_XSS($_GET['course']).'&amp;origin='.Security::remove_XSS($_GET['origin']).'&amp;cidReq='.Security::remove_XSS($_GET['course']).'">'.Display::return_icon('statistics.gif',get_lang('AccessDetails')).' '.get_lang('AccessDetails').'</a>';
	}
	echo '</div>';
	  	  
	// is the user online ?
	$statistics_database = Database :: get_statistic_database();
	$student_on_line=Security::remove_XSS($_GET['student']);
	$a_usersOnline = WhoIsOnline($student_on_line, $statistics_database, 30);
	foreach($a_usersOnline as $a_online)
	{
		if(in_array($_GET['student'],$a_online))
		{
			$online = get_lang('Yes');
			break;
		}
		else
		{
			$online = get_lang('No');
		}
	}
			
	$avg_student_progress = $avg_student_score = $nb_courses = 0;
	$sql = 'SELECT course_code FROM '.$tbl_course_user.' WHERE user_id='.Database::escape_string($a_infosUser['user_id']);
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	$a_courses = array();
	while($row = Database :: fetch_array($rs))
	{
		$a_courses[$row['course_code']] = $row['course_code'];
	}
	
	// get the list of sessions where the user is subscribed as student
	$sql = 'SELECT DISTINCT course_code FROM '.Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER).' WHERE id_user='.intval($a_infosUser['user_id']);
	$rs = api_sql_query($sql, __FILE__, __LINE__);
	while($row = Database :: fetch_array($rs))
	{
		$a_courses[$row['course_code']] = $row['course_code'];
	}
		
	$course_id=Security::remove_XSS($_GET['course']);
	if(!CourseManager::is_user_subscribed_in_course($a_infosUser['user_id'],$course_id, true))
	{
		unset($a_courses[$key]);
	}
	else
	{
		$nb_courses++;
		$avg_student_progress = Tracking :: get_avg_student_progress($a_infosUser['user_id'],$course_id);
		//the score inside the Reporting table
		$avg_student_score=Tracking::get_average_test_scorm_and_lp($a_infosUser['user_id'],$course_id);
	}
	

	
	$avg_student_progress = round($avg_student_progress,2);
	$avg_student_score = round($avg_student_score,2);
	
	$first_connection_date = Tracking::get_first_connection_date($a_infosUser['user_id']);
	if($first_connection_date==''){
		$first_connection_date=get_lang('NoConnexion');
	}
	
	$last_connection_date = Tracking::get_last_connection_date($a_infosUser['user_id'],true);
	if($last_connection_date==''){
		$last_connection_date=get_lang('NoConnexion');
	}
	
	$time_spent_on_the_course = api_time_to_hms(Tracking :: get_time_spent_on_the_course($a_infosUser['user_id'], $course_id));
	// cvs informations
	$csv_content[] = array(get_lang('Informations'));
	$csv_content[] = array(get_lang('Name'), get_lang('Email'), get_lang('Tel'));
	$csv_content[] = array($a_infosUser['name'], $a_infosUser['email'],$a_infosUser['phone']);
	
	$csv_content[] = array();
	
	// csv tracking
	$csv_content[] = array(get_lang('Tracking'));
	$csv_content[] = array(get_lang('FirstLogin'),get_lang('LatestLogin'), get_lang('TimeSpentInTheCourse'), get_lang('Progress'), get_lang('Score'));
	$csv_content[] = array(strip_tags($first_connection_date),strip_tags($last_connection_date), $time_spent_on_the_course , $avg_student_progress.' %',$avg_student_score.' %');
	
?>
	<a name="infosStudent"></a>
				<table width="100%" border="0" >
					<tr>
						
							<?php							
								$image_array=UserManager::get_user_picture_path_by_id($a_infosUser['user_id'],'web',false, true);																					
								echo '<td class="borderRight" width="10%" valign="top">';								
								
								// get the path,width and height from original picture
								$image_file = $image_array['dir'].$image_array['file'];
								$big_image = $image_array['dir'].'big_'.$image_array['file'];
								$big_image_size = @getimagesize(api_url_to_local_path($big_image));
								$big_image_width= $big_image_size[0];
								$big_image_height= $big_image_size[1];
								$url_big_image = $big_image.'?rnd='.time();
								$img_attributes = 'src="'.$image_file.'?rand='.time().'" '
								.'alt="'.$a_infosUser['lastname'].' '.$a_infosUser['firstname'].'" '
								.'style="float:'.($text_dir == 'rtl' ? 'left' : 'right').'; padding:5px;" ';
															
								if ($image_array['file']=='unknown.jpg') {
								echo '<img '.$img_attributes.' />';
								} else {
								echo '<input type="image" '.$img_attributes.' onclick="return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');"/>';
								}								
								
								echo '</td>';
							?>
						
			<td width="40%" valign="top">
			
				<table width="100%" class="data_table">
								<tr>
									<th>
										<?php echo get_lang('Information'); ?>
									</th>
								</tr>
								<tr>
						<td>
										<?php 
											echo get_lang('Name').' : ';
											echo $a_infosUser['name']; 
										?>
									</td>
								</tr>
								<tr>
						<td>
										<?php
											echo get_lang('Email').' : ';
											if(!empty($a_infosUser['email']))
											{
												echo '<a href="mailto:'.$a_infosUser['email'].'">'.$a_infosUser['email'].'</a>';
											}
											else
											{
												echo get_lang('NoEmail');
											}
										?>
									</td>
								</tr>
								<tr>
						<td>
										<?php
											echo get_lang('Tel').'. ';
											
											if(!empty($a_infosUser['phone']))
											{
												echo $a_infosUser['phone'];
											}
											else
											{
												echo get_lang('NoTel');
											} 
										?>
									</td>
								</tr>
								<tr>
						<td>
										<?php
											echo get_lang('OfficialCode').' : ';
											
											if(!empty($a_infosUser['official_code']))
											{
												echo $a_infosUser['official_code'];
											}
											else 
											{ 
												echo get_lang('NoOfficialCode');
											} 
										?>
									</td>
								</tr>
								<tr>
						<td>
										<?php
											echo get_lang('OnLine').' : ';
											echo $online;
										?>
									</td>
								</tr>
							</table>
						</td>
						<td class="borderLeft" width="35%" valign="top">
				
				<table width="100%" class="data_table">
								<tr>
						<th colspan="2">
										<?php echo get_lang('Tracking'); ?>
									</th>
								</tr>
								<tr>
						<td align="right">
													<?php echo get_lang('FirstLogin') ?>
												</td>
						<td align="left">
													<?php echo $first_connection_date ?>
												</td>
											</tr>
											<tr>
						<td align="right">
													<?php echo get_lang('LatestLogin') ?>
												</td>
						<td align="left">
													<?php echo $last_connection_date ?>
												</td>
											</tr>
											<tr>
						<td align="right">
													<?php echo get_lang('TimeSpentInTheCourse') ?>
												</td>
						<td align="left">
													<?php echo $time_spent_on_the_course ?>
												</td>
											</tr>
											<tr>
						<td align="right">
													<?php 
													echo get_lang('Progress'); 
													Display :: display_icon('info2.gif',get_lang('ScormAndLPProgressTotalAverage') , array ('align' => 'absmiddle', 'hspace' => '3px'));
													?>
												</td>
						<td align="left">
													<?php echo $avg_student_progress.' %' ?>
												</td>
											</tr>
											<tr>
						<td align="right">
													<?php 
													echo get_lang('Score');
													Display :: display_icon('info2.gif',get_lang('ScormAndLPTestTotalAverage') , array ('align' => 'absmiddle', 'hspace' => '3px'));
													?>
												</td>
						<td align="left">
													<?php  echo $avg_student_score.' %' ?>
												</td>
											</tr>
										</table>
									</td>
					</tr>
				</table>

	<table class="data_table">
		<tr>
			<td colspan="5" style="border-width: 0px;">&nbsp;</td>
		</tr>
<?php
			if(!empty($_GET['details']))
			{
				$course_code_info=Security::remove_XSS($_GET['course']);
				$a_infosCours = CourseManager :: get_course_information($course_code_info);
			
				//get coach and session_name if there is one and if session_mode is activated
				if(api_get_setting('use_session_mode')=='true')
				{
					$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
					$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
					$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
					$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
					
					$sql = 'SELECT id_session 
							FROM '.$tbl_session_course_user.' session_course_user
							WHERE session_course_user.id_user = '.intval($a_infosUser['user_id']).'
							AND session_course_user.course_code = "'.Database::escape_string($course_code_info).'"
							ORDER BY id_session DESC';
					$rs = api_sql_query($sql,__FILE__,__LINE__);
					$num_row=Database::num_rows($rs);
					if ($num_row > 0) 
					{
					$le_session_id = intval(Database::result($rs,0,0));					
						if($le_session_id>0)
						{
							// get session name and coach of the session
							$sql = 'SELECT name, id_coach FROM '.$tbl_session.' 
									WHERE id='.$le_session_id;
							$rs = api_sql_query($sql,__FILE__,__LINE__);						
							$session_name = Database::result($rs,0,'name');
							$session_coach_id = intval(Database::result($rs,0,'id_coach'));
							
							// get coach of the course in the session
							$sql = 'SELECT id_coach FROM '.$tbl_session_course.' 
									WHERE id_session='.$le_session_id.'
									AND course_code = "'.Database::escape_string($_GET['course']).'"';
							$rs = api_sql_query($sql,__FILE__,__LINE__);						
							$session_course_coach_id = intval(Database::result($rs,0,0));
	
							if($session_course_coach_id!=0)
							{
								$coach_infos = UserManager :: get_user_info_by_id($session_course_coach_id);
								$a_infosCours['tutor_name'] = $coach_infos['firstname'].' '.$coach_infos['lastname'];
							}
							else if($session_coach_id!=0)
							{
								$coach_infos = UserManager :: get_user_info_by_id($session_coach_id);
								$a_infosCours['tutor_name'] = $coach_infos['firstname'].' '.$coach_infos['lastname'];
							}
						}
					}
				} // end if(api_get_setting('use_session_mode')=='true')
				
				$date_start = '';
				if(!empty($a_infosCours['date_start']))
				{
					$a_date_start = explode('-',$a_infosCours['date_start']);
					$date_start = $a_date_start[2].'/'.$a_date_start[1].'/'.$a_date_start[0];
				}
				$date_end = '';
				if(!empty($a_infosCours['date_end']))
				{	
					$a_date_end = explode('-',$a_infosCours['date_end']);
					$date_end = $a_date_end[2].'/'.$a_date_end[1].'/'.$a_date_end[0];
				}
				$dateSession = get_lang('From').' '.$date_start.' '.get_lang('To').' '.$date_end;
				$nb_login = Tracking :: count_login_per_student($a_infosUser['user_id'], $_GET['course']);
				$tableTitle = $a_infosCours['title'].'&nbsp;|&nbsp;'.get_lang('CountToolAccess').' : '.$nb_login.'&nbsp; | &nbsp;'.get_lang('Tutor').' : '.stripslashes($a_infosCours['tutor_name']).((!empty($session_name)) ? ' | '.get_lang('Session').' : '.$session_name : '');
				
				$csv_content[] = array();
				$csv_content[] = array(str_replace('&nbsp;','',$tableTitle));	
				
?>
		<tr>
			<td colspan="6">
					<strong><?php echo $tableTitle; ?></strong>
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
							Display :: display_icon('info3.gif',get_lang('TotalTimeByCourse') , array ('align' => 'absmiddle', 'hspace' => '3px'));
							?>
						</th>
						<th>
							<?php 
							echo get_lang('Score');
							Display :: display_icon('info3.gif',get_lang('LPTestScore') , array ('align' => 'absmiddle', 'hspace' => '3px'));
							?>
						</th>
						<th>
							<?php 
							echo get_lang('Progress'); 
							Display :: display_icon('info3.gif',get_lang('LPProgressScore') , array ('align' => 'absmiddle', 'hspace' => '3px'));
							?>
						</th>
						<th>
							<?php 
							echo get_lang('LastConnexion');
							Display :: display_icon('info3.gif',get_lang('LastTimeTheCourseWasUsed') , array ('align' => 'absmiddle', 'hspace' => '3px'));
							?>
						</th>
						<th>
							<?php echo get_lang('Details');?>
						</th>
					</tr>
<?php
			$a_headerLearnpath = array(get_lang('Learnpath'),get_lang('Time'),get_lang('Progress'),get_lang('LastConnexion'));
			
			$t_lp = Database::get_course_table(TABLE_LP_MAIN,$a_infosCours['db_name']);
			$t_lpi = Database::get_course_table(TABLE_LP_ITEM,$a_infosCours['db_name']);
			$t_lpv = Database::get_course_table(TABLE_LP_VIEW,$a_infosCours['db_name']);
			$t_lpiv = Database::get_course_table(TABLE_LP_ITEM_VIEW,$a_infosCours['db_name']);
			
			$tbl_stats_exercices = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
			$tbl_stats_attempts= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
			$tbl_quiz_questions= Database :: get_course_table(TABLE_QUIZ_QUESTION,$a_infosCours['db_name']);
	
			$sqlLearnpath = "SELECT lp.name,lp.id
								FROM $t_lp AS lp ORDER BY lp.name ASC
							";

			$resultLearnpath = api_sql_query($sqlLearnpath,__FILE__,__LINE__);
			
			$csv_content[] = array();
			$csv_content[] = array(get_lang('Learnpath'),get_lang('Time'),get_lang('Score'),get_lang('Progress'),get_lang('LastConnexion'));
			
			if(Database::num_rows($resultLearnpath)>0)
			{
				$i = 0;
				while($a_learnpath = Database::fetch_array($resultLearnpath))
				{
					$any_result = false;
					$progress = learnpath :: get_db_progress($a_learnpath['id'],$student_id, '%',$a_infosCours['db_name'],true);
					if($progress === null)
					{
						$progress = '0%';
					}
					else
					{
						$any_result = true;
					}
					
					// calculates time
					$sql = 'SELECT SUM(total_time) 
								FROM '.$t_lpiv.' AS item_view
								INNER JOIN '.$t_lpv.' AS view
									ON item_view.lp_view_id = view.id
									AND view.lp_id = '.$a_learnpath['id'].'
									AND view.user_id = '.intval($_GET['student']);
					$rs = api_sql_query($sql, __FILE__, __LINE__);
					$total_time = 0;
					if(Database::num_rows($rs)>0)
					{
						$total_time = Database::result($rs, 0, 0);
						if($total_time>0) $any_result = true;
					}
				
					// calculates last connection time
					$sql = 'SELECT MAX(start_time) 
								FROM '.$t_lpiv.' AS item_view
								INNER JOIN '.$t_lpv.' AS view
									ON item_view.lp_view_id = view.id
									AND view.lp_id = '.$a_learnpath['id'].'
									AND view.user_id = '.intval($_GET['student']);
					$rs = api_sql_query($sql, __FILE__, __LINE__);
					$start_time = null;
					if(Database::num_rows($rs)>0)
					{
						$start_time = Database::result($rs, 0, 0);
						if($start_time > 0) $any_result = true;
					}
					
					//QUIZZ IN LP
					$score = Tracking::get_avg_student_score(intval($_GET['student']), Database::escape_string($_GET['course']), array($a_learnpath['id']));				
					
					if (empty($score)) {
						//$score = 0;
					}
					if($i%2==0){
						$s_css_class="row_odd";
					}
					else{
						$s_css_class="row_even";
					}
					
					$i++;
					
					$csv_content[] = array(api_html_entity_decode(stripslashes($a_learnpath['name']),ENT_QUOTES,$charset),api_time_to_hms($total_time),$score.'%',$progress,date('Y-m-d',$start_time));
										
				?>
					<tr class="<?php echo $s_css_class;?>">
						<td>
							<?php echo stripslashes($a_learnpath['name']); ?>
						</td>
						<td align="center">
						<?php echo api_time_to_hms($total_time) ?>
						</td>
						<td align="center">
							<?php 
							if(!is_null($score)) {
								 echo $score.' %';
								} else {
									
									if ('0'==$progress{0}) {
										echo '/';
									} else {
										echo '0%';
									}
								 	
								 	$score=0;
							} 
							?>
						</td>
						<td align="center">
							<?php echo $progress ?>
						</td>
						<td align="center">
							<?php if($start_time!='' && $start_time>0) {
									 echo format_locale_date(get_lang('DateFormatLongWithoutDay'),$start_time);
								} else {
								 	echo '-';
								} ?>
						</td>
						<td align="center">
							<?php
							if($any_result === true)
							{
							?>
							<a href="lp_tracking.php?course=<?php echo Security::remove_XSS($_GET['course']) ?>&origin=<?php echo Security::remove_XSS($_GET['origin']) ?>&lp_id=<?php echo $a_learnpath['id']?>&student_id=<?php echo $a_infosUser['user_id'] ?>">
								<img src="../img/2rightarrow.gif" border="0" />
							</a>
							<?php
							}
							?>
						</td>
					</tr>
				
				<?php
				$dataLearnpath[$i][] = $a_learnpath['name'];
				$dataLearnpath[$i][] = $progress.'%';
				$i++;
				}
			
			}
			else
			{
				echo "	<tr>	
							<td colspan='6'>
								".get_lang('NoLearnpath')."
							</td>
						</tr>
					 ";
				}
?>
				</table>

	<!-- line about exercises -->
			<table class="data_table">
				<tr>
					<th>
						<?php echo get_lang('Exercices'); ?>
					</th>
					<th>
						<?php echo get_lang('Score').Display :: return_icon('info3.gif', get_lang('LastScoreTest'), array('align' => 'absmiddle', 'hspace' => '3px')) ?>
					</th>
					<th>
						<?php echo get_lang('Attempts'); ?>
					</th>
					<th>
						<?php echo get_lang('CorrectTest'); ?>
					</th>
				</tr>
			<?php
			$csv_content[] = array();
			$csv_content[] = array(get_lang('Exercices'),get_lang('Score'),get_lang('Attempts'));
						
			$a_infosCours = CourseManager :: get_course_information(Security::remove_XSS($_GET['course']));			
			$t_tool = Database::get_course_table(TABLE_TOOL_LIST,$a_infosCours['db_name']);									
			$sql='SELECT visibility FROM '.$t_tool.' WHERE name="quiz"';			
										
			$resultVisibilityQuizz = api_sql_query($sql,__FILE__,__LINE__);
			$t_quiz = Database::get_course_table(TABLE_QUIZ_TEST,$a_infosCours['db_name']);
			
			if(Database::result($resultVisibilityQuizz,0,'visibility')==1){
			
				$sqlExercices = "	SELECT quiz.title,id
									FROM ".$t_quiz." AS quiz
									WHERE active='1' ORDER BY quiz.title ASC
									";
		
				$resultExercices = api_sql_query($sqlExercices,__FILE__,__LINE__);
				$i = 0;
				$is_student=Security::remove_XSS($_GET['student']);
				if(Database::num_rows($resultExercices)>0)
				{
					while($a_exercices = Database::fetch_array($resultExercices))
					{
						$sqlEssais = "	SELECT COUNT(ex.exe_id) as essais
										FROM $tbl_stats_exercices AS ex
										WHERE  ex.exe_cours_id = '".$a_infosCours['code']."'
										AND ex.exe_exo_id = ".$a_exercices['id']."
										AND orig_lp_id = 0
										AND orig_lp_item_id = 0				
										AND exe_user_id='".Database::escape_string($is_student)."'"
									 ;
						$resultEssais = api_sql_query($sqlEssais,__FILE__,__LINE__);
						$a_essais = Database::fetch_array($resultEssais);
						
						$sqlScore = "SELECT exe_id, exe_result,exe_weighting
									 FROM $tbl_stats_exercices
									 WHERE exe_user_id = ".Database::escape_string($is_student)."
									 AND exe_cours_id = '".$a_infosCours['code']."'
									 AND exe_exo_id = ".$a_exercices['id']."
									 AND orig_lp_id = 0
									 AND orig_lp_item_id = 0
									 ORDER BY exe_date DESC LIMIT 1";
	
						$resultScore = api_sql_query($sqlScore,__FILE__,__LINE__);
						$score = 0; 
						while($a_score = Database::fetch_array($resultScore))
						{
							$score = $score + $a_score['exe_result'];
							$weighting = $weighting + $a_score['exe_weighting'];
							$exe_id = $a_score['exe_id'];
						}
						$pourcentageScore = 0;
						if($weighting!=0) {
							//i.e 10.50 
							$pourcentageScore = round(($score*100)/$weighting,2);
						}
		
						$weighting = 0;
						
						$csv_content[] = array($a_exercices['title'], $pourcentageScore.' %', $a_essais['essais']);
						
						if($i%2==0){
							$s_css_class="row_odd";
						}
						else{
							$s_css_class="row_even";
						}
						
						$i++;
						
				echo '<tr class="'.$s_css_class.'">
								<td>
					 ';
						echo 		$a_exercices['title'];
						echo "	</td>
							 ";
						echo "	<td align='center'>
							  ";
						if ($a_essais['essais']>0 ) {
							echo $pourcentageScore.' %';
						} else {
							echo '/';
							$pourcentageScore=0;
						}

						echo "	</td>
								<td align='center'>
							 ";
						echo 		$a_essais['essais'];
						echo "	</td>
								<td align='center'>
							 ";
						
						$sql_last_attempt='SELECT exe_id FROM '.$tbl_stats_exercices.' WHERE exe_exo_id="'.$a_exercices['id'].'" AND exe_user_id="'.Security::remove_XSS($_GET['student']).'" AND exe_cours_id="'.$a_infosCours['code'].'" AND orig_lp_id = 0 AND orig_lp_item_id = 0 ORDER BY exe_date DESC LIMIT 1';
						$resultLastAttempt = api_sql_query($sql_last_attempt,__FILE__,__LINE__);
						if(Database::num_rows($resultLastAttempt)>0)
						{
							$id_last_attempt=Database::result($resultLastAttempt,0,0);
							
							if($a_essais['essais']>0)
								echo '<a href="../exercice/exercise_show.php?id='.$id_last_attempt.'&cidReq='.$a_infosCours['code'].'&student='.Security::remove_XSS($_GET['student']).'&origin='.(empty($_GET['origin']) ? 'tracking' : Security::remove_XSS($_GET['origin'])).'"> <img src="'.api_get_path(WEB_IMG_PATH).'quiz.gif" border="0" /> </a>';
						}
						echo "	</td>
							  </tr>
							 ";							 
						$dataExercices[$i][] =  $a_exercices['title'];
						$dataExercices[$i][] = $pourcentageScore.'%';
						$dataExercices[$i][] =  $a_essais['essais'];
						//$dataExercices[$i][] =  corrections;
						$i++;
					
					}
				}
				else
				{
					echo "	<tr>	
								<td colspan='6'>
									".get_lang('NoExercise')."
								</td>
							</tr>
						 ";
				}
			}
			else
			{
				echo "	<tr>	
							<td colspan='6'>
								".get_lang('NoExercise')."
							</td>
						</tr>
					 ";
			}
					
?>					
					</table>

	<!-- line about other tools -->
			<table class="data_table">
	<tr>
		<td>
			<?php
			$csv_content[] = array();
			
			$nb_assignments = Tracking :: count_student_assignments($a_infosUser['user_id'], $a_infosCours['code']);
			$messages = Tracking :: count_student_messages($a_infosUser['user_id'], $a_infosCours['code']);
			$links = Tracking :: count_student_visited_links($a_infosUser['user_id'], $a_infosCours['code']);
			$documents = Tracking :: count_student_downloaded_documents($a_infosUser['user_id'], $a_infosCours['code']);
			$chat_last_connection = Tracking::chat_last_connection($a_infosUser['user_id'], $a_infosCours['code']);
	
			$csv_content[] = array(get_lang('Student_publication'), $nb_assignments);
			$csv_content[] = array(get_lang('Messages'), $messages);
			$csv_content[] = array(get_lang('LinksDetails'), $links);
			$csv_content[] = array(get_lang('DocumentsDetails'), $documents);
			$csv_content[] = array(get_lang('ChatLastConnection'), $chat_last_connection);
			
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
<?php			
		}
		else
		{
?>
		<tr>
			<th>
				<?php echo get_lang('Course'); ?>
			</th>
			<th>
				<?php echo get_lang('Time'); ?>
			</th>
			<th>
				<?php echo get_lang('Progress'); ?>
			</th>
			<th>
				<?php echo get_lang('Score'); ?>
			</th>
			<th>
				<?php echo get_lang('Details'); ?>
			</th>
		</tr>
<?php
		if(!api_is_platform_admin(true) && $_user['status']!=DRH){
			// courses followed by user where we are coach
			if(!isset($_GET['id_coach'])){
				$a_courses = Tracking :: get_courses_followed_by_coach($_user['user_id']);
			}
			else{
				$a_courses = Tracking :: get_courses_followed_by_coach(Security::remove_XSS($_GET['id_coach']));
			}
		}
		if(count($a_courses)>0)
		{
			$csv_content[] = array();
			$csv_content[] = array(get_lang('Course'),get_lang('Time'),get_lang('Progress'),get_lang('Score'));
			foreach($a_courses as $course_code)
			{				
				if(CourseManager :: is_user_subscribed_in_course($student_id,$course_code, true)){
					$course_infos = CourseManager :: get_course_information($course_code);
					$time_spent_on_course = api_time_to_hms(Tracking :: get_time_spent_on_the_course($a_infosUser['user_id'], $course_code));
					$progress = Tracking :: get_avg_student_progress($a_infosUser['user_id'], $course_code).' %';
					$score = Tracking :: get_avg_student_score($a_infosUser['user_id'], $course_code).' %';
					$csv_content[] = array($course_infos['title'], $time_spent_on_course, $progress, $score);
					echo '
					<tr>				
						<td align="right">
							'.$course_infos['title'].'
						</td>
						<td align="right">
							'.$time_spent_on_course.'
						</td>
						<td align="right">
							'.$progress.'
						</td>
						<td align="right">
							'.$score.'
						</td>';
						if(isset($_GET['id_coach']) && intval($_GET['id_coach'])!=0){
							echo '<td align="center" width="10">
								<a href="'.api_get_self().'?student='.$a_infosUser['user_id'].'&details=true&course='.$course_infos['code'].'&id_coach='.Security::remove_XSS($_GET['id_coach']).'&origin='.Security::remove_XSS($_GET['origin']).'&id_session='.Security::remove_XSS($_GET['id_session']).'#infosStudent"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>
							</td>';
						}
						else{
							echo '<td align="center" width="10">
								<a href="'.api_get_self().'?student='.$a_infosUser['user_id'].'&details=true&course='.$course_infos['code'].'&origin='.Security::remove_XSS($_GET['origin']).'&id_session='.Security::remove_XSS($_GET['id_session']).'#infosStudent"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>
							</td>';
						}
					echo '</tr>';
				}
				
			}
		}
		else
		{
			echo "<tr>
					<td colspan='5'>
						".get_lang('NoCourse')."
					</td>
				  </tr>
				 ";
		}
		}//end of else !empty($details)
	?>
	</table>
	<br />
<?php 
	if(!empty($_GET['details']) && $_GET['origin'] != 'tracking_course' && $_GET['origin'] != 'user_course')
	{
?>
		
		<br /><br />
<?php
		}
		if(!empty($_GET['exe_id']))
	{
		$t_q = Database::get_course_table(TABLE_QUIZ_TEST,$a_infosCours['db_name']);
		$t_qq = Database::get_course_table(TABLE_QUIZ_QUESTION,$a_infosCours['db_name']);
		$t_qa = Database::get_course_table(TABLE_QUIZ_ANSWER,$a_infosCours['db_name']);
		$t_qtq = Database::get_course_table(TABLE_QUIZ_TEST_QUESTION,$a_infosCours['db_name']);
		$sqlExerciceDetails = " SELECT qq.question, qq.ponderation, qq.id
				 				FROM ".$t_qq." as qq
								INNER JOIN ".$t_qtq." as qrq
									ON qrq.question_id = qq.id
									AND qrq.exercice_id = ".intval($_GET['exe_id']);
				 
		$resultExerciceDetails = api_sql_query($sqlExerciceDetails,__FILE__,__LINE__);
		
		
		$sqlExName = "	SELECT quiz.title
						FROM ".$t_q." AS quiz
					 	WHERE quiz.id = ".intval($_GET['exe_id']);
					 ;
	
		$resultExName = api_sql_query($sqlExName,__FILE__,__LINE__);
		$a_exName = Database::fetch_array($resultExName);
		
		echo "<table class='data_table'>
			 	<tr>
					<th colspan='2'>
						".$a_exName['title']."
					</th>
				</tr>
             ";
		
		while($a_exerciceDetails = Database::fetch_array($resultExerciceDetails))
		{
			$sqlAnswer = "	SELECT qa.comment, qa.answer
							FROM  ".$t_qa." as qa
							WHERE qa.question_id = ".$a_exerciceDetails['id']
					 	 ;
			
			$resultAnswer = api_sql_query($sqlAnswer,__FILE__,__LINE__);
			
			echo "<a name='infosExe'></a>";

			echo"	
			<tr>
				<td colspan='2'>
					<strong>".$a_exerciceDetails['question'].' /'.$a_exerciceDetails['ponderation']."</strong>
				</td>
			</tr>
			";
			while($a_answer = Database::fetch_array($resultAnswer))
			{
				echo"
				<tr>
					<td>
						".$a_answer['answer']."
					</td>
					<td>
				";
				if(!empty($a_answer['comment']))
						echo $a_answer['comment'];
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
	//$a_header = array_merge($a_headerLearnpath,$a_headerExercices,$a_headerProductions);
}
if($export_csv)
{
	ob_end_clean();
	Export :: export_table_csv($csv_content, 'reporting_student');
}
/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
