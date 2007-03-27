<?php
/*
 * Created on 20 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
 
 // name of the language file that needs to be included 
$language_file = array ('registration', 'index', 'tracking', 'exercice');
 $cidReset=true;
 include ('../inc/global.inc.php');
 include_once(api_get_path(LIBRARY_PATH).'tracking.lib.php');
 include_once(api_get_path(LIBRARY_PATH).'export.lib.inc.php');
 include_once(api_get_path(LIBRARY_PATH).'usermanager.lib.php');
 include_once(api_get_path(LIBRARY_PATH).'course.lib.php');
 
 
$export_csv = isset($_GET['export']) && $_GET['export'] == 'csv' ? true : false;
if($export_csv)
{
	ob_start();
}
$csv_content = array();
 
 $this_section = "session_my_space";
 
 $nameTools=get_lang("StudentDetails");
 
 
 
 if(isset($_GET['details']))
 {
 	if(!empty($_GET['origin']) && $_GET['origin'] == 'user_course')
 	{
 		$course_infos = CourseManager :: get_course_information($_GET['course']);
 		$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$course_infos['directory'], 'name' => $course_infos['title']);
 		$interbreadcrumb[] = array ("url" => "../user/user.php?cidReq=".$_GET['course'], "name" => get_lang("Users"));
 	}
 	else if(!empty($_GET['origin']) && $_GET['origin'] == 'tracking_course')
 	{
 		$course_infos = CourseManager :: get_course_information($_GET['course']);
 		$interbreadcrumb[] = array ("url" => api_get_path(WEB_COURSE_PATH).$course_infos['directory'], 'name' => $course_infos['title']);
 		$interbreadcrumb[] = array ("url" => "../tracking/courseLog.php?cidReq=".$_GET['course'].'&studentlist=true', "name" => get_lang("Tracking"));
 	}
 	else
 	{
 		$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
 		$interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang("MyStudents"));
	 	$interbreadcrumb[] = array ("url" => "myStudents.php?student=".$_GET['student'], "name" => get_lang("StudentDetails"));
	 	$nameTools=get_lang("DetailsStudentInCourse");
 	}
 }
 else
 {
 	$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
 	$interbreadcrumb[] = array ("url" => "student.php", "name" => get_lang("MyStudents"));
 }
 
 api_block_anonymous_users();
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
	$sql="SELECT 1 FROM $tbl_course_user WHERE user_id='".$_user["user_id"]."' AND course_code='".$course_code."' AND status='1'";
	$result=api_sql_query($sql);
	if(mysql_result($result)!=1){
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
	$i_user_id=$_GET["user_id"];
}
else
{
	$i_user_id = $_user['user_id'];
}

if(!empty($_GET['student']))
{
	
	echo '<div align="right">
		<a href="#" onclick="window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a>
		<a href="'.$_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&export=csv"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>
	  </div>';
	  
	  
	// is the user online ?
	$statistics_database = Database :: get_statistic_database();
	$a_usersOnline = WhoIsOnline($_GET['student'], $statistics_database, 30);
	foreach($a_usersOnline as $a_online)
	{
		if(in_array($_GET['student'],$a_online))
		{
			$online = get_lang('Yes');
		}
		else
		{
			$online = get_lang('No');
		}
	}
	
	// infos about user
	$a_infosUser = UserManager::get_user_info_by_id($_GET['student']);
	$a_infosUser['name'] = $a_infosUser['firstname'].' '.$a_infosUser['lastname'];
	
	// courses followed by user where we are coach
	$a_courses = Tracking :: get_courses_followed_by_coach($_user['user_id']);
	$avg_student_progress = $avg_student_score = $nb_courses = 0;
	foreach ($a_courses as $key=>$course_code)
	{
		if(!CourseManager::is_user_subscribed_in_course($a_infosUser['user_id'], $course_code, true))
		{
			array_splice($a_courses, $key);
		}
		else
		{
			$nb_courses++;
			$avg_student_progress += Tracking :: get_avg_student_progress($a_infosUser['user_id'],$course_code);
			$avg_student_score += Tracking :: get_avg_student_score($a_infosUser['user_id'],$course_code);
		}
	}
	$avg_student_progress = round($avg_student_progress / $nb_courses,1);
	$avg_student_score = round($avg_student_score / $nb_courses,1);
	$last_connection_date = Tracking::get_last_connection_date($a_infosUser['user_id']);
	$time_spent_on_the_platform = api_time_to_hms(Tracking::get_time_spent_on_the_platform($a_infosUser['user_id']));
	
	// cvs informations
	$csv_content[] = array(get_lang('Informations'));
	$csv_content[] = array(get_lang('Name'), get_lang('Email'), get_lang('Tel'));
	$csv_content[] = array($a_infosUser['name'], $a_infosUser['email'],$a_infosUser['phone']);
	
	$csv_content[] = array();
	
	// csv tracking
	$csv_content[] = array(get_lang('Tracking'));
	$csv_content[] = array(get_lang('LatestLogin'), get_lang('TimeSpentOnThePlatform'), get_lang('Progress'), get_lang('Score'));
	$csv_content[] = array($last_connection_date, $time_spent_on_the_platform , $avg_student_progress.' %',$avg_student_score.' %');
	
?>

	<a name="infosStudent"></a>
	<table class="data_table">
		<tr>
			<td class="border">
				<table width="100%" border="0" >
					<tr>
						
							<?php
								if(!empty($a_infosUser['picture_uri']))
								{
									echo '	<td class="borderRight" width="10%">
												<img src="../upload/users/'.$a_infosUser['picture_uri'].'" width="100" />
											</td>
										 	';
								}
								else{
									echo '	<td class="borderRight" width="10%">
												<img src="../img/unknown.jpg" />
											</td>
										 	'; 
								}
								
							?>
						
						<td class="none" width="40%">
							<table width="100%">
								<tr>
									<th>
										<?php echo get_lang('Informations'); ?>
									</th>
								</tr>
								<tr>
									<td class="none">
										<?php 
											echo get_lang('Name').' : ';
											echo $a_infosUser['name']; 
										?>
									</td>
								</tr>
								<tr>
									<td class="none">
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
									<td class="none">
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
									<td class="none">
										<?php
											echo get_lang('OnLine').' : ';
											echo $online;
										?>
									</td>
								</tr>
							</table>
						</td>
						<td class="borderLeft" width="35%">
							<table width="100%">
								<tr>
									<th>
										<?php echo get_lang('Tracking'); ?>
									</th>
								</tr>
								<tr>
									<td>
										<table>
											<tr>
												<td class="none">
													<?php echo get_lang('LatestLogin') ?>
												</td>
												<td class="none">
													<?php echo $last_connection_date ?>
												</td>
											</tr>
											<tr>
												<td class="none">
													<?php echo get_lang('TimeSpentOnThePlatform') ?>
												</td>
												<td class="none">
													<?php echo $time_spent_on_the_platform ?>
												</td>
											</tr>
											<tr>
												<td class="none">
													<?php echo get_lang('Progress') ?>
												</td>
												<td class="none">
													<?php echo $avg_student_progress.' %' ?>
												</td>
											</tr>
											<tr>
												<td class="none">
													<?php echo get_lang('Score') ?>
												</td>
												<td class="none">
													<?php echo $avg_student_score.' %' ?>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
						</td>
					<?php
							$sendMail = Display::encrypted_mailto_link($a_infosUser['email'], ' '.get_lang('SendMail'));
						
					?>
						<td class="borderLeft" width="15%">
							<table width="100%">
								<tr>
									<th>
										<?php echo get_lang('Actions'); ?>
									</th>
								</tr>
								<tr>
									
										<?php 
											if(!empty($a_infosUser['email']))
											{
												echo "<td class='none'>";
												echo '<img align="absbottom" src="../img/send_mail.gif">&nbsp;'.$sendMail;
												echo "</td>";
											}
											else
											{
												echo "<td class='noLink none'>";
												echo '<img align="absbottom" src="../img/send_mail.gif">&nbsp; <strong> > '.get_lang('SendMail').'</strong>';
												echo "</td>";
											}
										?>
								
								</tr>
							</table>
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
		
				$a_infosCours = CourseManager :: get_course_information($_GET['course']);
			
			$a_date_start = explode('-',$a_infosCours['date_start']);
			$date_start = $a_date_start[2].'/'.$a_date_start[1].'/'.$a_date_start[0];
			$a_date_end = explode('-',$a_infosCours['date_end']);
			$date_end = $a_date_end[2].'/'.$a_date_end[1].'/'.$a_date_end[0];
			$dateSession = get_lang('From').' '.$date_start.' '.get_lang('To').' '.$date_end;
			$tableTitle = $a_infosCours['title'].'&nbsp; | &nbsp;'.get_lang('Tutor').' : '.$a_infosCours['tutor_name'];
			
			$csv_content[] = array();
			$csv_content[] = array($tableTitle);	
				
?>
		<tr class="tableName">
			<td colspan="6">
					<strong><?php echo $tableTitle; ?></strong>
			</td>
		</tr>
		<tr> <!-- line about learnpaths -->
			<td>
				<table class="data_table">
					<tr>
						<th>
							<?php echo get_lang('Learnpaths'); ?>
						</th>
						<th>
							<?php echo get_lang('Time'); ?>
						</th>
						<th>
							<?php echo get_lang('Progress'); ?>
						</th>
						<th>
							<?php echo get_lang('LastConnexion'); ?>
						</th>
						<th>
							<?php echo get_lang('Details'); ?>
						</th>
					</tr>
<?php
				$a_headerLearnpath = array(get_lang('Learnpath'),get_lang('Time'),get_lang('Progress'),get_lang('LastConnexion'));
			
			$sqlLearnpath = "	SELECT lp.name,lp.id
								FROM ".$a_infosCours['db_name'].".".$tbl_course_lp." AS lp
							";

			$resultLearnpath = api_sql_query($sqlLearnpath);
			
			$csv_content[] = array();
			$csv_content[] = array(get_lang('Learnpath'),get_lang('Time'),get_lang('Progress'),get_lang('LastConnexion'));
			
			if(mysql_num_rows($resultLearnpath)>0)
			{
				$i = 0;
				while($a_learnpath = mysql_fetch_array($resultLearnpath))
				{
					$sqlProgress = "SELECT COUNT(DISTINCT lp_item_id) AS nbItem
									FROM ".$a_infosCours['db_name'].".".$tbl_course_lp_view_item." AS item_view
									INNER JOIN ".$a_infosCours['db_name'].".".$tbl_course_lp_view." AS view
										ON item_view.lp_view_id = view.id
										AND view.lp_id = ".$a_learnpath['id']."
										AND view.user_id = ".$_GET['student']."
									WHERE item_view.status = 'completed'
									OR item_view.status = 'passed'
									";
					$resultProgress = api_sql_query($sqlProgress);
					$a_nbItem = mysql_fetch_array($resultProgress);
	
					$sqlTotalItem = "	SELECT	COUNT(item_type) AS totalItem
										FROM ".$a_infosCours['db_name'].".".$tbl_course_lp_item." 
										WHERE lp_id = ".$a_learnpath['id']."
										AND item_type != 'chapter'
										AND item_type != 'dokeos_chapter'"
									;
					$resultItem = api_sql_query($sqlTotalItem);
					$a_totalItem = mysql_fetch_array($resultItem);
					
					$progress = round(($a_nbItem['nbItem'] * 100)/$a_totalItem['totalItem']);
					
					
					
					// calculates time
					$sql = 'SELECT SUM(total_time) 
								FROM '.$a_infosCours['db_name'].'.'.$tbl_course_lp_view_item.' AS item_view
								INNER JOIN '.$a_infosCours['db_name'].'.'.$tbl_course_lp_view.' AS view
									ON item_view.lp_view_id = view.id
									AND view.lp_id = '.$a_learnpath['id'].'
									AND view.user_id = '.$_GET['student'];
					$rs = api_sql_query($sql, __FILE__, __LINE__);
					$total_time = mysql_result($rs, 0, 0);
					
					// calculates last connection time
					$sql = 'SELECT MAX(start_time) 
								FROM '.$a_infosCours['db_name'].'.'.$tbl_course_lp_view_item.' AS item_view
								INNER JOIN '.$a_infosCours['db_name'].'.'.$tbl_course_lp_view.' AS view
									ON item_view.lp_view_id = view.id
									AND view.lp_id = '.$a_learnpath['id'].'
									AND view.user_id = '.$_GET['student'];
					$rs = api_sql_query($sql, __FILE__, __LINE__);
					$start_time = mysql_result($rs, 0, 0);
					
					
					if($i%2==0){
						$s_css_class="row_odd";
					}
					else{
						$s_css_class="row_even";
					}
					
					$i++;
					
					$csv_content[] = array(stripslashes($a_learnpath['name']),api_time_to_hms($total_time),$progress.' %',date('Y-m-d',$start_time));
					
				?>
					<tr class="<?php echo $s_css_class;?>">
						<td>
							<?php echo stripslashes($a_learnpath['name']); ?>
						</td>
						<td align="center">
						<?php echo api_time_to_hms($total_time) ?>
						</td>
						<td align="center">
							<?php echo $progress.' %'; ?>
						</td>
						<td align="center">
							<?php echo date('Y-m-d',$start_time) ?>
						</td>
						<td align="center">
							<a href="lp_tracking.php?course=<?php echo $_GET['course'] ?>&origin=<?php echo $_GET['origin'] ?>&lp_id=<?php echo $a_learnpath['id']?>&student_id=<?php echo $a_infosUser['user_id'] ?>">
								<img src="../img/2rightarrow.gif" border="0" />
							</a>
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
			</td>
		</tr>
		<tr> <!-- line about exercises -->
			<td>
			<table class="data_table">
				<tr>
					<th>
						<?php echo get_lang('Exercices'); ?>
					</th>
					<th>
						<?php echo get_lang('Score') ?>
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
			$sqlExercices = "	SELECT quiz.title,id
								FROM ".$a_infosCours['db_name'].".".$tbl_course_quiz." AS quiz
							";
	
			$resultExercices = api_sql_query($sqlExercices);
			$i = 0;
			if(mysql_num_rows($resultExercices)>0)
			{
				while($a_exercices = mysql_fetch_array($resultExercices))
				{
					$sqlEssais = "	SELECT COUNT(ex.exe_id) as essais
									FROM $tbl_stats_exercices AS ex
									WHERE  ex.exe_cours_id = '".$a_infosCours['code']."'
									AND ex.exe_exo_id = ".$a_exercices['id']
								 ;
					$resultEssais = api_sql_query($sqlEssais);
					$a_essais = mysql_fetch_array($resultEssais);
					
					$sqlScore = "SELECT exe_id, exe_result,exe_weighting
								 FROM $tbl_stats_exercices
								 WHERE exe_user_id = ".$_GET['student']."
								 AND exe_cours_id = '".$a_infosCours['code']."'
								 AND exe_exo_id = ".$a_exercices['id']."
								 ORDER BY exe_date DESC LIMIT 1"
									;
							
					$resultScore = api_sql_query($sqlScore);
					$score = 0; 
					while($a_score = mysql_fetch_array($resultScore))
					{
						$score = $score + $a_score['exe_result'];
						$weighting = $weighting + $a_score['exe_weighting'];
						$exe_id = $a_score['exe_id'];
					}
					$pourcentageScore = round(($score*100)/$weighting);
	
					$weighting = 0;
					
					$csv_content[] = array($a_exercices['title'], $pourcentageScore.' %', $a_essais['essais']);
					
					if($i%2==0){
						$s_css_class="row_odd";
					}
					else{
						$s_css_class="row_even";
					}
					
					$i++;
					
					echo "<tr class='$s_css_class'>
							<td>
						 ";
					echo 		$a_exercices['title'];
					echo "	</td>
						 ";
					echo "	<td align='center'>
						  ";
					echo 		$pourcentageScore.' %';
					echo "	</td>
							<td align='center'>
						 ";
					echo 		$a_essais['essais'];
					echo "	</td>
							<td align='center'>
						 ";
					if($a_essais['essais']>0)
						echo		'<a href="../exercice/exercise_show.php?id='.$exe_id.'&cidReq='.$a_infosCours['code'].'"> <img src="'.api_get_path(WEB_IMG_PATH).'quiz.gif" border="0"> </a>';
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
					
?>					
					</table>
				</td>
			</tr>
		<tr><!-- line about other tools -->
			<td>
			<table class="data_table">
			<?php
			$csv_content[] = array();
			
			$nb_assignments = Tracking :: count_student_assignments($a_infosUser['user_id'], $a_infosCours['code']);
			$messages = Tracking :: count_student_messages($a_infosUser['user_id'], $a_infosCours['code']);
			$links = Tracking :: count_student_visited_links($a_infosUser['user_id'], $a_infosCours['code']);
			$documents = Tracking :: count_student_downloaded_documents($a_infosUser['user_id'], $a_infosCours['code']);
			
			$csv_content[] = array(get_lang('Student_publication'), $nb_assignments);
			$csv_content[] = array(get_lang('Messages'), $messages);
			$csv_content[] = array(get_lang('LinksDetails'), $links);
			$csv_content[] = array(get_lang('DocumentsDetails'), $documents);
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
		if(count($a_courses)>0)
		{
			$csv_content[] = array();
			$csv_content[] = array(get_lang('Course'),get_lang('Time'),get_lang('Progress'),get_lang('Score'));
			foreach($a_courses as $course_code)
			{
				
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
					</td>
					<td align="center" width="10">
						<a href="'.$_SERVER['PHP_SELF'].'?student='.$a_infosUser['user_id'].'&details=true&course='.$course_infos['code'].'#infosStudent"><img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" /></a>
					</td>
				</tr>
				';
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
		<div align="left">
			<a href="<?php echo $_SERVER['PHP_SELF']; ?>?student=<?php echo $a_infosUser['user_id']; ?>#infosStudent"><?php echo get_lang('Back'); ?></a>
		</div>
		<br /><br />
<?php
		}
		if(!empty($_GET['exe_id']))
	{
		$sqlExerciceDetails = " SELECT qq.question, qq.ponderation, qq.id
				 				FROM ".$a_infosCours['db_name'].".".$course_quiz_question." as qq
								INNER JOIN ".$a_infosCours['db_name'].".".$course_quiz_rel_question." as qrq
									ON qrq.question_id = qq.id
									AND qrq.exercice_id = ".$_GET['exe_id']
								
							 ;
				 
		$resultExerciceDetails = api_sql_query($sqlExerciceDetails);
		
		
		$sqlExName = "	SELECT quiz.title
						FROM ".$a_infosCours['db_name'].".".$tbl_course_quiz." AS quiz
					 	WHERE quiz.id = ".$_GET['exe_id']
					 ;
	
		$resultExName = api_sql_query($sqlExName);
		$a_exName = mysql_fetch_array($resultExName);
		
		echo "<table class='data_table'>
			 	<tr>
					<th colspan='2'>
						".$a_exName['title']."
					</th>
				</tr>
             ";
		
		while($a_exerciceDetails = mysql_fetch_array($resultExerciceDetails))
		{
			$sqlAnswer = "	SELECT qa.comment, qa.answer
							FROM  ".$a_infosCours['db_name'].".".$course_quiz_answer." as qa
							WHERE qa.question_id = ".$a_exerciceDetails['id']
					 	 ;
			
			$resultAnswer = api_sql_query($sqlAnswer);
			
			echo "<a name='infosExe'></a>";

			echo"	
			<tr>
				<td colspan='2'>
					<strong>".$a_exerciceDetails['question'].' /'.$a_exerciceDetails['ponderation']."</strong>
				</td>
			</tr>
			";
			while($a_answer = mysql_fetch_array($resultAnswer))
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
	 $a_header = array_merge($a_headerLearnpath,$a_headerExercices,$a_headerProductions);

	
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
 
?>