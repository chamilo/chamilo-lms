<?php
/*
 * Created on 20 juil. 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
 
 // name of the language file that needs to be included 
$language_file = array ('registration', 'index','trad4all', 'tracking');
 $cidReset=true;
 include ('../inc/global.inc.php');
 
 $this_section = "session_my_space";
 
 $nameTools=get_lang("MyStudents");
 
 if(isset($_GET["user_id"]) && $_GET["user_id"]!="" && !isset($_GET["type"])){
 	$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
 	$interbreadcrumb[] = array ("url" => "teachers.php", "name" => get_lang('Teachers'));
 }
 
 if(isset($_GET["user_id"]) && $_GET["user_id"]!="" && isset($_GET["type"]) && $_GET["type"]=="coach"){
 	$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('MySpace'));
 	$interbreadcrumb[] = array ("url" => "coaches.php", "name" => get_lang('Tutors'));
 }
 
 api_block_anonymous_users();
 Display :: display_header($nameTools);
 
 /*
  * ======================================================================================
  * 	FUNCTIONS
  * ======================================================================================
  */
  
function exportCsv($a_infosUser,$tableTitle,$a_header,$a_dataLearnpath,$a_dataExercices,$a_dataProduction)
{
	global $archiveDirName;
	
	$fileName = 'test.csv';
	$archivePath = api_get_path(SYS_PATH).$archiveDirName.'/';
	$archiveURL = api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';
	
	if(!$open = fopen($archivePath.$fileName,'w+'))
	{
		$message = get_lang('noOpen');
	}
	else
	{
		$info = '';
		
		$info .= $a_infosUser['name'];
		$info .= "\r\n";
		$info .= $a_infosUser['email'];
		$info .= "\r\n";
		$info .= $a_infosUser['phone'];
		/*$info .= "\r\n";
		$info .= $a_infosUser['adresse'];*/
		
		$info .= "\r\n";
		$info .= "\r\n";
		$info .= $tableTitle;
		$info .= "\r\n";
		
		for($i=0;$i<4;$i++)
		{
			$info .= $a_header[$i].';';
		}
		$info .= "\r\n";
		
		foreach($a_dataLearnpath as $a_learnpath)
		{
			foreach($a_learnpath as $learnpath)
			{
				$info .= $learnpath.';';
			}
			$info .= "\r\n";
		}
		
		for($i=4;$i<8;$i++)
		{
			$info .= $a_header[$i].';';
		}
		$info .= "\r\n";
		
		foreach($a_dataExercices as $a_exercice)
		{
			foreach($a_exercice as $exercice)
			{
				$info .= $exercice.';';
			}
			$info .= "\r\n";	
		}
		
		for($i=8;$i<12;$i++)
		{
			$info .= $a_header[$i].';';
		}
		
		$info .= "\r\n";
		
		foreach($a_dataProduction as $a_production)
		{
			foreach($a_production as $production)
			{
				$info .= $production.';';
			}
			$info .= "\r\n";
		}
		fwrite($open,$info);
		fclose($open);
		chmod($fileName,0777);
		$message = get_lang('UsageDatacreated');
		
		header("Location:".$archiveURL.$fileName);
	}
	
	return $message;
}


function calculHours($seconds)
{
	
  //combien d'heures ?
  $hours = floor($seconds / 3600);

  //combien de minutes ?
  $min = floor(($seconds - ($hours * 3600)) / 60);
  if ($min < 10)
    $min = "0".$min;

  //combien de secondes
  $sec = $seconds - ($hours * 3600) - ($min * 60);
  if ($sec < 10)
    $sec = "0".$sec;
        
  //echo $hours."h".$min."m".$sec."s";

	return $hours."h".$min."m".$sec."s" ;

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
$tbl_stats_exercices 		= Database :: get_statistic_table(STATISTIC_TRACK_E_EXERCICES_TABLE);
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

api_display_tool_title($nameTools);

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
	$sqlInfosUser = "	SELECT  user_id,
								CONCAT(firstname,' ',lastname) AS name,
								email,
								phone,
								picture_uri
						FROM $tbl_user
						WHERE user_id = ".$_GET['student']
					;
	$resultInfosUser = api_sql_query($sqlInfosUser);
	$a_infosUser = mysql_fetch_array($resultInfosUser);

	$sqlCours = " 	SELECT DISTINCT course.title,
									course.code,
									course.db_name,
									CONCAT(user.firstname,' ',user.lastname) as tutor_name,
									sessionCourse.id_coach
					FROM $tbl_user as user,$tbl_course AS course
					INNER JOIN $tbl_session_course_user AS course_user
						ON course_user.course_code = course.code
					INNER JOIN $tbl_session_course as sessionCourse
							ON sessionCourse.course_code = course.code
					WHERE course_user.id_user = ".$_GET['student']."
					AND sessionCourse.id_coach = user.user_id
					ORDER BY course.title ASC
				";
		$resultCours = api_sql_query($sqlCours);
		
?>

	<a name="infosStudent"></a>
	<table class="data_table">
		<tr>
			<td 
				<?php
					if(empty($details))
						echo 'colspan="6"';
					else
						echo 'colspan="7"';	
				?>
				class="border">
				<table width="100%" border="0" >
					<tr>
						
							<?php
								if(!empty($a_infosUser['picture_uri']))
								{
									echo '	<td class="borderRight">
												<img src="'.$a_infosUser['picture_uri'].'" />
											</td>
										 ';
								}
							?>
						
						<td class="none" width="60%">
							<table>
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
					<?php
				
						if(!empty($_GET['details']))
						{
							$sendMail = Display::encrypted_mailto_link($a_infosUser['email'], ' '.get_lang('SendMail'));
						
					?>
						<td class="borderLeft">
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
								<tr>
									<td class="none">
										<?php echo "<img align='absbottom' src='../img/meeting_agenda.gif'><a href=''>".'&nbsp; '.get_lang('RdvAgenda')."</a>"; ?>
									</td>
								</tr>
								<tr>
									<td class="none">
										<?php echo "<img align='absbottom' src='../img/visio.gif'><a href=''>".'&nbsp; '.get_lang('VideoConf')."</a>"; ?>
									</td>
								</tr>
								<tr>
									<td class="none">
										<?php echo "<img align='absbottom' src='../img/chat.gif'><a href=''>".'&nbsp; '.get_lang('Chat')."</a>"; ?>
									</td>
								</tr>
								<tr>
									<td class="none">
								
										<?php echo "<img align='absbottom' src='../img/spreadsheet.gif'><a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&csv=true#infosStudent'>".'&nbsp; '.get_lang('ExcelFormat')."</a>"; ?>
									</td>
								</tr>
							</table>
						</td>
					<?php
						}
					?>
					</tr>
				</table>
			</td>
		</tr>
		<tr><td colspan="5" style="border-width: 0px;">&nbsp;</td></tr>
		</a>
<?php
			if(!empty($_GET['details']))
			{
?>			
			<br /><br />
			<div align="left">
				<a href="<?php echo $_SERVER['PHP_SELF']; ?>?student=<?php echo $a_infosUser['user_id']; ?>#infosStudent"><?php echo get_lang('Back'); ?></a>
			</div>
			<br />
<?php
		
				$sqlInfosCourse = "	SELECT 	course.code,
										course.title,
										course.db_name,
										CONCAT(user.firstname,' ',user.lastname) as tutor_name,
										session.date_start,
										session.date_end
								FROM $tbl_user as user, $tbl_course as course
								INNER JOIN $tbl_session_course as sessionCourse
									ON sessionCourse.course_code = course.code
								INNER JOIN $tbl_session AS session
									ON session.id = sessionCourse.id_session
								WHERE sessionCourse.id_coach = user.user_id
								AND course.code= '".$_GET['course']."'
							 ";
	
			$resultInfosCourse = api_sql_query($sqlInfosCourse);
			
			$a_infosCours = mysql_fetch_array($resultInfosCourse);
			
			$a_date_start = explode('-',$a_infosCours['date_start']);
			$date_start = $a_date_start[2].'/'.$a_date_start[1].'/'.$a_date_start[0];
			$a_date_end = explode('-',$a_infosCours['date_end']);
			$date_end = $a_date_end[2].'/'.$a_date_end[1].'/'.$a_date_end[0];
			$dateSession = get_lang('Du').' '.$date_start.' '.get_lang('Au').' '.$date_end;
			$tableTitle = $a_infosCours['title'].'. '.get_lang('Tutor').' : '.$a_infosCours['tutor_name'].'. '.get_lang('Session').' : '.$dateSession;
				
				
?>
		<tr class="tableName">
			<td colspan="6">
					<strong><?php echo $tableTitle; ?></strong>
			</td>
		</tr>
		<tr>
			<th class="head">
				<?php echo get_lang('Learnpath'); ?>
			</th>
			<th class="head" colspan="2">
				<?php echo get_lang('Time'); ?>
			</th>
			<th class="head">
				<?php echo get_lang('Progress'); ?>
			</th>
			<th class="head" colspan="2">
				<?php echo get_lang('LastConnexion'); ?>
			</th>
		</tr>
<?php
				$a_headerLearnpath = array(get_lang('Learnpath'),get_lang('Time'),get_lang('Progress'),get_lang('LastConnexion'));
			
			$sqlLearnpath = "	SELECT lp.name,lp.id
								FROM ".$a_infosCours['db_name'].".".$tbl_course_lp." AS lp
							";

			$resultLearnpath = api_sql_query($sqlLearnpath);
			
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
									";
					$resultProgress = api_sql_query($sqlProgress);
					$a_nbItem = mysql_fetch_array($resultProgress);
	
					$sqlTotalItem = "	SELECT	COUNT(item_type) AS totalItem
										FROM ".$a_infosCours['db_name'].".".$tbl_course_lp_item." 
										WHERE lp_id = ".$a_learnpath['id']
									;
					$resultItem = api_sql_query($sqlTotalItem);
					$a_totalItem = mysql_fetch_array($resultItem);
					
					$progress = round(($a_nbItem['nbItem'] * 100)/$a_totalItem['totalItem']);
					
					if($i%2==0){
						$s_css_class="row_odd";
					}
					else{
						$s_css_class="row_even";
					}
					
					$i++;
					
				?>
					<tr class="<?php echo $s_css_class;?>">
						<td>
							<?php echo $a_learnpath['name']; ?>
						</td>
						<td colspan="2">
						
						</td>
						<td align="center">
							<?php echo $progress.'%'; ?>
						</td>
						<td colspan="2">
						
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
		<tr>
			<th class="head">
				<?php echo get_lang('Exercices'); ?>
			</th>
			<th class="head">
				<?php echo get_lang('Score') ?>
			</th>
			<th class="head">
				<?php echo get_lang('Details'); ?>
			</th>
			<th class="head">
				<?php echo get_lang('Essais'); ?>
			</th>
			<th class="head">
				<?php echo get_lang('Correction'); ?>
			</th>
			<th class="head">
				<?php echo get_lang('Corriger'); ?>
			</th>
		</tr>
<?php
				$a_headerExercices = array(get_lang('Exercices'),get_lang('Score'),get_lang('Essais'),get_lang('Correction'));
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
					
					$sqlScore = "SELECT exe_result,exe_weighting
								 FROM $tbl_stats_exercices
								 WHERE exe_user_id = ".$_GET['student']."
								 AND exe_cours_id = '".$a_infosCours['code']."'
								 AND exe_exo_id = ".$a_exercices['id']
									;
							
					$resultScore = api_sql_query($sqlScore);
					$score = 0; 
					while($a_score = mysql_fetch_array($resultScore))
					{
						$score = $score + $a_score['exe_result'];
						$weighting = $weighting + $a_score['exe_weighting'];
					}
					$pourcentageScore = round(($score*100)/$weighting);
	
					$weighting = 0;
					
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
					echo 		$pourcentageScore.'%';
					echo "	</td>
							<td align='center'>
						 ";
					echo		"<a href='".$_SERVER['PHP_SELF']."?student=".$_GET['student']."&details=true&course=".$_GET['course']."&exe_id=".$a_exercices['id']."#infosExe'> -> </a>";
					echo "	</td>
							<td align='center'>
						 ";
					echo 		$a_essais['essais'];
					echo "	</td>
							<td>
						 ";
					echo "	</td>
							<td align='center'>
						 ";
					echo		"<a href=''> -> </a>";
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
								".get_lang('NoExercice')."
							</td>
						</tr>
					 ";
				}

?>
		<tr>
			<th class="head">
				<?php echo get_lang('Productions'); ?>
			</th>
			<th class="head" colspan="2">
				<?php echo get_lang('LimitDate'); ?>
			</th>
			<th class="head">
				<?php echo get_lang('RemiseDate'); ?>
			</th>
			<th class="head">
				<?php echo get_lang('Remarques'); ?>
			</th>
			<th class="head">
				<?php echo get_lang('Annoter'); ?>
			</th>
		</tr>
<?php

				$a_headerProductions = array(get_lang('Productions'),get_lang('LimitDate'),get_lang('RemiseDate'),get_lang('Remarques'));
			$sqlProduction = "	SELECT title,sent_date
								FROM ".$a_infosCours['db_name'].".".$course_student_publication."
							 ";
	
			$resultProduction = api_sql_query($sqlProduction);
			if(mysql_num_rows($resultProduction)>0)
			{
				$i = 0;
				while($a_production = mysql_fetch_array($resultProduction))
				{
					
					//$tmp_limitDate = $newDate = mktime(0 , 0 , 0 , date("m") , date("d") , date("Y"));
					$tmp_limitDate = $newDate = mktime(0 , 0 , 0 ,8 , 20 , 2006);
					
					$a_sentDate = explode(' ',$a_production['sent_date']);
					$a_sentDate = explode('-',$a_sentDate[0]);
					$tmp_sentDate = mktime(0,0,0,$a_sentDate[1],$a_sentDate[2],$a_sentDate[0]);
					
					$sentDate = $a_sentDate[2].'/'.$a_sentDate[1].'/'.$a_sentDate[0];
					
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
					echo 		$a_production['title'];
					echo "	</td>
						 ";
					echo "	<td align='center' colspan='2'>
						  ";
					
					echo "	</td>
						 ";
					if($tmp_sentDate > $tmp_limitDate)
					{
						echo "<td align='center' class='redText'>";
						$tmp_retard = $tmp_sentDate - $tmp_limitDate;
						$retard = round($tmp_retard/86400);
						echo $retard.' '.get_lang('DayOfDelay');
						echo "</td>";
					}
					else
					{
						echo "<td align='center'>";
						echo	$sentDate;
						echo "</td>";
					}
					echo "	<td align='center'>
						 ";
					$remarque = '';
					if($remarque == '')
					{
						echo "--";
					}
					
					echo "	</td>
							<td align='center'>
						 ";
					echo		"<a href=''> -> </a>";
					echo "	</td>
						  </tr>
						 ";
						 
					$dataProduction[$i][] =  $a_production['title'];
					//$dataProduction[$i][] = $a_production['sent_date'];
					$dataProduction[$i][] =  $a_production['sent_date'];
					//$dataProduction[$i][] =  remarques;
					$i++;
					
				}
			}
			else
			{
				echo "	<tr>	
							<td colspan='6'>
								".get_lang('NoProduction')."
							</td>
						</tr>
					 ";
				}
			
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
			if(mysql_num_rows($resultCours)>0)
			{
				while($a_cours = mysql_fetch_array($resultCours))
				{
				
				if($i_user_id == $a_cours['id_coach']){
						if($i%2==0){
							$s_css_class="row_odd";
					}
					else{
						$s_css_class="row_even";
					}
					
					$i++;
					
					/**
					 * Calcul du score total de l'étudiant sur le cours courant
					 */
					
					$sqlScore = "	SELECT  exe_result,
											exe_weighting
					 				FROM $tbl_stats_exercices
									WHERE exe_user_id = ".$_GET['student']."
					 				AND exe_cours_id = '".$a_cours['code']."'
								";
					$resultScore = api_sql_query($sqlScore);
					$i = 0;
					$score = 0;
					while($a_score = mysql_fetch_array($resultScore))
					{
						$score = $score + $a_score['exe_result'];
						$weighting = $weighting + $a_score['exe_weighting'];
						$i++;
					}
					
					$totalScore = $totalScore + $score;
					$totalWeighting = $totalWeighting + $weighting;
					
					$pourcentageScore = round(($score*100)/$weighting);
					
					$weighting = 0;
					
					/**
					 * Calcul de la progression de l'étudiant sur les learning path du cours courant
					 */
					
					$sqlProgress = "SELECT COUNT( DISTINCT item_view.lp_item_id ) AS nbItem 
									FROM ".$a_cours['db_name'].".".$tbl_course_lp_view_item." AS item_view 
									INNER JOIN ".$a_cours['db_name'].".".$tbl_course_lp_view." AS lpview 
										ON lpview.user_id = ".$_GET['student']." 
									WHERE item_view.status = 'completed' 
								   ";
	
					$resultProgress = api_sql_query($sqlProgress);
					$a_nbItem = mysql_fetch_array($resultProgress);
					
					$table = $a_cours['db_name'].'.'.$tbl_course_lp_item;
					if(mysql_select_db($a_cours['db_name']))
						$nbTotalItem = Database::count_rows($table);
			
					$totalItem = $totalItem + $nbTotalItem;
					
					$totalProgress = $totalProgress + $a_nbItem['nbItem'];
					
					$progress = round(($a_nbItem['nbItem'] * 100)/$nbTotalItem);
					
					
					/**
					 * Calcul du temps passé sur le cours courant
					 */
					
					$tbl_track_lcourse_access = Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
					
					$s_sql_connection_time="SELECT login_course_date, logout_course_date FROM $tbl_track_lcourse_access WHERE user_id ='".$_GET['student']."' AND logout_course_date <> 'null' AND course_code='".$a_cours['code']."'";

					$q_result_connection_time=api_sql_query($s_sql_connection_time);
					
					$i_nb_seconds=0;
					
					while($a_connections=mysql_fetch_array($q_result_connection_time)){
						
						$s_login_date=$a_connections["login_course_date"];
						$s_logout_date=$a_connections["logout_course_date"];
						
						$i_timestamp_login_date=strtotime($s_login_date);
						$i_timestamp_logout_date=strtotime($s_logout_date);
						
						$i_nb_seconds+=($i_timestamp_logout_date-$i_timestamp_login_date);
						
					}
					
					$s_connection_time=calculHours($i_nb_seconds);
					if($s_connection_time=="0h00m00s"){
						$s_connection_time="";
					}
					
					
	?>			
					<tr class="<?php echo $s_css_class;?>">
						<td>
							<?php echo $a_cours['title'].' - '.get_lang('Tutor').' : '.$a_cours['tutor_name']; ?>
						</td>
						<td align="center">
							<?php echo $s_connection_time;?>
						</td>
						<td align="center">
							<?php echo $progress.'%'; ?>
						</td>
						<td align="center">
							<?php echo $pourcentageScore.'%'; ?>
						</td>
						<td align="center">
							<?php 
								
								echo '<a href="'.$_SERVER['PHP_SELF'].'?student='.$a_infosUser['user_id'].'&details=true&course='.$a_cours['code'].'#infosStudent"> -> </a>';
								
							?>
						</td>
					</tr>
<?php				}
				}
			
			$totalPourcentageScore = round(($totalScore*100)/$totalWeighting);
			$progress = round(($totalProgress*100)/$totalItem);
?>
		<tr class='total'>
			<td>
				<strong>Total</strong>
			</td>
			<td>
			</td>
			<td align="center">
				<?php echo $progress.'%'; ?>
			</td>
			<td align="center">
				<?php echo $totalPourcentageScore.'%'; ?>
			</td>
			<td>
			</td>
		</tr>
	<?php
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
	if(!empty($_GET['details']))
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
			//print_r($a_exerciceDetails);
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

	if($_GET['csv'] == "true")
	 {
		$exportResult = exportCsv($a_infosUser,$tableTitle,$a_header,$dataLearnpath,$dataExercices,$dataProduction);
	 	Display :: display_error_message($exportResult);
	 }
	
}
	
/*
==============================================================================
		FOOTER
==============================================================================
*/

Display::display_footer();
 
?>