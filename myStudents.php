<?php
/*
 * Dokeos header should come here
 */

/**
 * @todo use the correct database calls. example around line 480 : .$a_infosCours['db_name'].".".$tbl_course_lp_view_item."
 * @todo language variables are sometimes in french: get_lang('Annoter')
 * @todo other variables are sometimes in french: $pourcentageScore
 * @todo variables are sometimes in cammelcase
 */
 
 $langFile = array ('registration', 'index','trad4all', 'tracking');
 $nameTools= get_lang('MyStagiaires');
 require ('main/inc/global.inc.php');
 api_block_anonymous_users();
 Display :: display_header($nameTools);
 
 /*
  * ======================================================================================
  * 	FUNCTIONS
  * ======================================================================================
  */
/**
 * Enter description here...
 *
 * @param unknown_type $a_infosUser
 * @param unknown_type $tableTitle
 * @param unknown_type $a_header
 * @param unknown_type $a_dataLearnpath
 * @param unknown_type $a_dataExercices
 * @param unknown_type $a_dataProduction
 * @return unknown
 * 
 * @author Elixir Interactive http://www.elixir-interactive.com
 * @version 20 july 2006
 */
function exportCsv($a_infosUser,$tableTitle,$a_header,$a_dataLearnpath,$a_dataExercices,$a_dataProduction)
{
	global $archiveDirName;
	
	$fileName = 'test.csv';
	$archivePath 	= api_get_path(SYS_PATH).$archiveDirName.'/';
	$archiveURL 	= api_get_path(WEB_CODE_PATH).'course_info/download.php?archive=';
	
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

/*
 *===============================================================================
 *	MAIN CODE
 *===============================================================================  
 */
 // Database table definitions
 $tbl_user 						= Database :: get_main_table(TABLE_MAIN_USER);
 $tbl_session_user 				= Database :: get_main_table(MAIN_SESSION_USER_TABLE);
 $tbl_session 					= Database :: get_main_table(MAIN_SESSION_TABLE);
 $tbl_session_course 			= Database :: get_main_table(MAIN_SESSION_COURSE_TABLE);
 $tbl_session_course_user 		= Database :: get_main_table(MAIN_SESSION_COURSE_USER_TABLE);
 $tbl_course 					= Database :: get_main_table(TABLE_MAIN_COURSE);
 $tbl_stats_exercices 			= Database :: get_statistic_table(STATISTIC_TRACK_E_EXERCICES_TABLE);
 $course_student_publication 	= Database :: get_course_table(STUDENT_PUBLICATION_TABLE);
 $statistics_database 			= Database :: get_statistic_database();
 
 /**
  * Are these needed? Apparently the correct database calls are not used 
  */
 $tbl_course_lp_view = 'lp_view';
 $tbl_course_lp_view_item = 'lp_item_view';
 $tbl_course_lp_item = 'lp_item';
 $tbl_course_lp = 'lp';
 $tbl_course_quiz = 'quiz';
 $course_quiz_question = 'quiz_question';
 $course_quiz_rel_question = 'quiz_rel_question';
 $course_quiz_answer = 'quiz_answer';



 //api_display_tool_title($nameTools);

?>
<table class="data_table">
	<tr>
		<th>
			<?php echo get_lang('Name'); ?>
		</th>
		<th>
			<?php echo get_lang('Email'); ?>
		</th>
		<th>
			<?php echo get_lang('Progress'); ?>
		</th>
	</tr>
<?php
	$sqlSessions = "SELECT id 
					FROM $tbl_session
					WHERE id_coach = '".$_user['user_id']."'
				   ";
	$resultSessions = api_sql_query($sqlSessions);
	
	$a_sessions = api_store_result($resultSessions);
	
	$sqlSessions = "SELECT DISTINCT id_session as id
			FROM $tbl_session_course
			WHERE id_coach = ".$_user['user_id']."'";
	  
	$resultSessions = api_sql_query($sqlSessions);
	$a_sessions = array_merge($a_sessions,api_store_result($resultSessions));
	
	$a_Students = array();
	
	foreach($a_sessions as $a_session){
		$sqlStudents = "SELECT 	session.id_user, 
								CONCAT(user.lastname,' ',user.firstname) as name, 
								user.email
						FROM $tbl_session_user AS session
						INNER JOIN $tbl_user as user
							ON session.id_user = user.user_id
							AND user.status = 5
						WHERE id_session = ".$a_session['id']."
						ORDER BY  user.lastname"
					   ;
	
		$resultStudents = api_sql_query($sqlStudents);
		
		while($a_student = mysql_fetch_array($resultStudents))
		{
			$a_Students[$a_student['id_user']] = $a_student;
		}
	}
	
	foreach($a_Students as $students)
	{
?>	
	<tr>
		<td>
			<a href="<?php echo $_SERVER['PHP_SELF']; ?>?student=<?php echo $students['id_user']; ?>#infosStudent"><?php echo $students['name']; ?></a>
		</td>
		<td>
			<?php
				if(!empty($students['email'])) 
					echo $students['email'];
				else
					echo get_lang('NoEmail');
			?>
		</td>
		<td>
		</td>
	</tr>
<?php
	}
?>	
</table>
<?php
	if(!empty($_GET['student']))
	{
		
		
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
						FROM $tbl_user as user, $tbl_course AS course
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
		<br/><br/>

		<br/>
		<a name="infosStudent">
		<table class="data_table">
			<tr>
				<td 
					<?php
						if(empty($details))
							echo 'colspan="5"';
						else
							echo 'colspan="6"';	
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
							
							<td class="none">
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
													echo $a_infosUser['email'];
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
								$sendMail = Display::encrypted_mailto_link($a_infosUser['email'], '> '.get_lang('SendMail'));
							
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
													echo $sendMail;
													echo "</td>";
												}
												else
												{
													echo "<td class='noLink none'>";
													echo '<strong> > '.get_lang('SendMail').'</strong>';
													echo "</td>";
												}
											?>
									
									</tr>
									<tr>
										<td class="none">
											<?php echo "<a href=''>".'> '.get_lang('RdvAgenda')."</a>"; ?>
										</td>
									</tr>
									<tr>
										<td class="none">
											<?php echo "<a href=''>".'> '.get_lang('VideoConf')."</a>"; ?>
										</td>
									</tr>
									<tr>
										<td class="none">
											<?php echo "<a href=''>".'> '.get_lang('Chat')."</a>"; ?>
										</td>
									</tr>
									<tr>
										<td class="none">
									
											<?php echo "<a href='".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']."&csv=true#infosStudent'>".'> '.get_lang('ExcelFormat')."</a>"; ?>
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
					?>
						<tr>
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
						
						echo "<tr>
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
							 
						$dataExercices[$i][] = $a_exercices['title'];
						$dataExercices[$i][] = $pourcentageScore.'%';
						$dataExercices[$i][] = $a_essais['essais'];
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
						
						echo "<tr>
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
					
					$sql_progress = "SELECT COUNT( DISTINCT item_view.lp_item_id ) AS nbItem 
									FROM ".$a_cours['db_name'].".".$tbl_course_lp_view_item." AS item_view 
									INNER JOIN ".$a_cours['db_name'].".".$tbl_course_lp_view." AS lpview 
										ON lpview.user_id = ".$_GET['student']." 
									WHERE item_view.status = 'completed' 
								   ";
					//echo $sqlProgress;
					$result_progress = api_sql_query($sql_progress);
					$a_nbItem = mysql_fetch_array($result_progress);
					
					$table = $a_cours['db_name'].'.'.$tbl_course_lp_item;
					$nbTotalItem = Database::count_rows($table);
			
					$totalItem = $totalItem + $nbTotalItem;
					
					$totalProgress = $totalProgress + $a_nbItem['nbItem'];
					
					$progress = round(($a_nbItem['nbItem'] * 100)/$nbTotalItem);
					
	?>			
					<tr>
						<td>
							<?php echo $a_cours['title'].' - '.get_lang('Tutor').' : '.$a_cours['tutor_name']; ?>
						</td>
						<td>
						
						</td>
						<td align="center">
							<?php echo $progress.'%'; ?>
						</td>
						<td align="center">
							<?php echo $pourcentageScore.'%'; ?>
						</td>
						<td align="center">
							<?php 
								if($_user['user_id'] == $a_cours['id_coach'])
								{
									echo '<a href="'.$_SERVER['PHP_SELF'].'?student='.$a_infosUser['user_id'].'&details=true&course='.$a_cours['code'].'#infosStudent"> -> </a>';
								}
							?>
						</td>
					</tr>
	<?php	
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
				$sql_answer = "	SELECT qa.comment, qa.answer
								FROM  ".$a_infosCours['db_name'].".".$course_quiz_answer." as qa
								WHERE qa.question_id = ".$a_exerciceDetails['id']
						 	 ;
				
				$result_answer = api_sql_query(sql_answer);
				
				echo "<a name='infosExe'></a>";
				//print_r($a_exerciceDetails);
				echo"	
				<tr>
					<td colspan='2'>
						<strong>".$a_exerciceDetails['question'].' /'.$a_exerciceDetails['ponderation']."</strong>
					</td>
				</tr>
				";
				while($a_answer = mysql_fetch_array($result_answer))
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