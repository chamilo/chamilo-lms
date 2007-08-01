<?php
// name of the language file that needs to be included
$language_file = array('registration','tracking','exercice');

$cidReset = true;

require ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'tracking.lib.php');

$nameTools=get_lang('MyProgress');

$this_section = 'session_my_progress';

api_block_anonymous_users();

Display :: display_header($nameTools);

// Database table definitions
$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_session 				= Database :: get_main_table(TABLE_MAIN_SESSION);
$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_session_course 		= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_stats_lastaccess 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
$tbl_stats_exercices 		= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
$tbl_course_lp_view 		= Database :: get_course_table('lp_view');
$tbl_course_lp_view_item 	= Database :: get_course_table('lp_item_view');
$tbl_course_lp 				= Database :: get_course_table('lp');
$tbl_course_lp_item 		= Database :: get_course_table('lp_item');
$tbl_course_quiz 			= Database :: get_course_table('quiz');

$result=api_sql_query("SELECT DISTINCT id, name, date_start, date_end FROM session_rel_course_rel_user,session WHERE id_session=id AND id_user=".$_user['user_id']." ORDER BY date_start, date_end, name",__FILE__,__LINE__);

$Sessions=api_store_result($result);

$Courses = array();

foreach($Sessions as $enreg){
	
	$id_session_temp = $enreg['id'];
	
	$sql = "SELECT DISTINCT code,title, CONCAT(lastname, ' ',firstname) coach, username, date_start, date_end, db_name
			FROM $tbl_course , $tbl_session_course
			LEFT JOIN $tbl_user
				ON $tbl_session_course.id_coach = $tbl_user.user_id
			INNER JOIN $tbl_session_course_user
				ON $tbl_session_course_user.id_session = $tbl_session_course.id_session
				AND $tbl_session_course_user.id_user = '".$_user['user_id']."'
			INNER JOIN $tbl_session ON $tbl_session.id = $tbl_session_course.id_session
			WHERE $tbl_session_course.course_code=code
			AND $tbl_session_course.id_session='$id_session_temp'
			ORDER BY title";

	$result=api_sql_query($sql);

	while($a_session_courses = mysql_fetch_array($result)){
		$a_session_courses['id_session'] = $id_session_temp;
		$Courses[$a_session_courses['code']] = $a_session_courses;
	}
	
}

	
$sql = "SELECT DISTINCT code,title, db_name
		FROM $tbl_course as course, $tbl_course_user as course_rel_user
		WHERE course_rel_user.user_id = '".$_user['user_id']."'
		AND course_rel_user.course_code = course.code
		";
$result=api_sql_query($sql);

while($a_courses = mysql_fetch_array($result)){
	$a_courses['id_session'] = 0;
	$Courses[$a_courses['code']] = $a_courses;
}


api_display_tool_title($nameTools);

$now=date('Y-m-d');

?>

<table class="data_table" width="100%">
<tr class="tableName">
	<td colspan="6">
		<strong><?php echo get_lang('MyCourses'); ?></strong>
	</td>
</tr>
<tr>
  <th><?php echo get_lang('Course'); ?></th>
  <th><?php echo get_lang('Time'); ?></th>
  <th><?php echo get_lang('Progress'); ?></th>
  <th><?php echo get_lang('Score'); ?></th>
  <th><?php echo get_lang('LastConnexion'); ?></th>
  <th><?php echo get_lang('Details'); ?></th>
</tr>

<?php
$i = 0;
$totalWeighting = 0;
$totalScore = 0;
$totalItem = 0;
$totalProgress = 0;

foreach($Courses as $enreg)
{
	$weighting = 0;

	$lastConnexion = Tracking :: get_last_connection_date_on_the_course($_user['user_id'],$enreg['code']);

	$progress = Tracking :: get_avg_student_progress($_user['user_id'], $enreg['code']);

	$time = api_time_to_hms(Tracking :: get_time_spent_on_the_course($_user['user_id'], $enreg['code']));

	$pourcentageScore = Tracking :: get_avg_student_score($_user['user_id'], $enreg['code']);

?>

<tr class='<?php echo $i?'row_odd':'row_even'; ?>'>
  	<td>
		<?php echo htmlentities($enreg['title']); ?>
  	</td>

  	<td align='center'>
		<?php echo $time; ?>
  	</td>

  	<td align='center'>
  		<?php echo $progress.'%'; ?>
  	</td>

  	<td align='center'>
		<?php echo $pourcentageScore.'%'; ?>
  	</td>

  	<td align='center'>
		<?php echo $lastConnexion; ?>
  	</td>

  	<td align='center'>
		<a href="<?php echo $SERVER['PHP_SELF']; ?>?id_session=<?php echo $enreg['id_session'] ?>&course=<?php echo $enreg['code']; ?>"> <?php echo '<img src="'.api_get_path(WEB_IMG_PATH).'2rightarrow.gif" border="0" />';?> </a>
  	</td>
</tr>

<?php



	$i=$i ? 0 : 1;
}
?>
</table>

<br/><br/>

<?php
/*
 * **********************************************************************************************
 *
 * 	Details for one course
 *
 * **********************************************************************************************
 */
	if(isset($_GET['course']))
	{
		$course = Database::escape_string($_GET['course']);
		if($_GET['id_session']!=0){
			$sqlInfosCourse = "	SELECT course.code,course.title,course.db_name,CONCAT(user.firstname,' ',user.lastname,' / ',user.email) as tutor_infos
								FROM $tbl_user as user,$tbl_course as course
								INNER JOIN $tbl_session_course as sessionCourse
									ON sessionCourse.course_code = course.code
								WHERE sessionCourse.id_coach = user.user_id
								AND course.code= '".$course."'
							 ";
		}
		else{
			$sqlInfosCourse = "	SELECT course.code,course.title,course.db_name
								FROM $tbl_course as course
								WHERE course.code= '".$course."'
							 ";
		}

		$resultInfosCourse = api_sql_query($sqlInfosCourse);

		$a_infosCours = mysql_fetch_array($resultInfosCourse);
		
		if($_GET['id_session']!=0){
			$tableTitle = $a_infosCours['title'].' - '.get_lang('Tutor').' : '.$a_infosCours['tutor_infos'];
		}
		else{
			$tableTitle = $a_infosCours['title'];
		}

		?>
		<table class="data_table" width="100%">
			<tr class="tableName">
				<td colspan="4">
					<strong><?php echo $tableTitle; ?></strong>
				</td>
			</tr>
			<tr>
			  <th class="head"><?php echo get_lang('Learnpath'); ?></th>
			  <th class="head"><?php echo get_lang('Time'); ?></th>
			  <th class="head"><?php echo get_lang('Progress'); ?></th>
			  <th class="head"><?php echo get_lang('LastConnexion'); ?></th>
			</tr>
			<?php
				$sqlLearnpath = "	SELECT lp.name,lp.id
									FROM ".$a_infosCours['db_name'].".".$tbl_course_lp." AS lp
								";

				$resultLearnpath = api_sql_query($sqlLearnpath);

				if(mysql_num_rows($resultLearnpath)>0)
				{
					while($a_learnpath = mysql_fetch_array($resultLearnpath))
					{
						$sqlProgress = "SELECT COUNT(DISTINCT lp_item_id) AS nbItem
										FROM ".$a_infosCours['db_name'].".".$tbl_course_lp_view_item." AS item_view
										INNER JOIN ".$a_infosCours['db_name'].".".$tbl_course_lp_view." AS view
											ON item_view.lp_view_id = view.id
											AND view.lp_id = ".$a_learnpath['id']."
											AND view.user_id = ".$_user['user_id']."
										WHERE item_view.status = 'completed' OR item_view.status = 'passed'
										";
						$resultProgress = api_sql_query($sqlProgress);
						$a_nbItem = mysql_fetch_array($resultProgress);

						$sqlTotalItem = "	SELECT	COUNT(item_type) AS totalItem
											FROM ".$a_infosCours['db_name'].".".$tbl_course_lp_item."
											WHERE lp_id = ".$a_learnpath['id']."
											AND item_type != 'chapter'
											AND item_type != 'dokeos_chapter'
											AND item_type != 'dir'"
										;
						$resultItem = api_sql_query($sqlTotalItem);
						$a_totalItem = mysql_fetch_array($resultItem);

						$progress = round(($a_nbItem['nbItem'] * 100)/$a_totalItem['totalItem']);


						// calculates last connection time
						$sql = 'SELECT MAX(start_time)
									FROM '.$a_infosCours['db_name'].'.'.$tbl_course_lp_view_item.' AS item_view
									INNER JOIN '.$a_infosCours['db_name'].'.'.$tbl_course_lp_view.' AS view
										ON item_view.lp_view_id = view.id
										AND view.lp_id = '.$a_learnpath['id'].'
										AND view.user_id = '.$_user['user_id'];
						$rs = api_sql_query($sql, __FILE__, __LINE__);
						$start_time = mysql_result($rs, 0, 0);

						// calculates time
						$sql = 'SELECT SUM(total_time)
									FROM '.$a_infosCours['db_name'].'.'.$tbl_course_lp_view_item.' AS item_view
									INNER JOIN '.$a_infosCours['db_name'].'.'.$tbl_course_lp_view.' AS view
										ON item_view.lp_view_id = view.id
										AND view.lp_id = '.$a_learnpath['id'].'
										AND view.user_id = '.$_user['user_id'];
						$rs = api_sql_query($sql, __FILE__, __LINE__);
						$total_time = mysql_result($rs, 0, 0);


						echo "<tr>
								<td>
							 ";
						echo 		stripslashes($a_learnpath['name']);
						echo "	</td>
								<td>
							 ";
						echo api_time_to_hms($total_time);
						echo "	</td>
								<td align='center'>
							 ";
						echo		$progress.'%';
						echo "	</td>
								<td align='center'>
							 ";
						if($start_time!=''){
							echo format_locale_date(get_lang('dateFormatLong'),$start_time);
						}
						else{
							echo '-';
						}
						echo "	</td>
							  </tr>
							 ";
					}

				}
				else
				{
					echo "	<tr>
								<td colspan='4'>
									".get_lang('NoLearnpath')."
								</td>
							</tr>
						 ";
				}



			?>
			<tr>
			  <th class="head"><?php echo get_lang('Exercices'); ?></th>
			  <th class="head"><?php echo get_lang('Score'); ?></th>
			  <th class="head"><?php echo get_lang('Attempts'); ?></th>
			  <th class="head"><?php echo get_lang('Details'); ?></th>
			</tr>

			<?php
				
				$sql='SELECT visibility FROM '.$a_infosCours['db_name'].'.'.TABLE_TOOL_LIST.' WHERE name="quiz"';
				$resultVisibilityTests = api_sql_query($sql);
				
				if(mysql_result($resultVisibilityTests,0,'visibility')==1){
				
					$sqlExercices = "	SELECT quiz.title,id
									FROM ".$a_infosCours['db_name'].".".$tbl_course_quiz." AS quiz
									WHERE active='1'
									";
	
	
					$resuktExercices = api_sql_query($sqlExercices);
					if(mysql_num_rows($resuktExercices)>0){
						while($a_exercices = mysql_fetch_array($resuktExercices))
						{
							$sqlEssais = "	SELECT COUNT(ex.exe_id) as essais
											FROM $tbl_stats_exercices AS ex
											WHERE ex.exe_user_id='".$_user['user_id']."' AND ex.exe_cours_id = '".$a_infosCours['code']."'
											AND ex.exe_exo_id = ".$a_exercices['id']
										 ;
							$resultEssais = api_sql_query($sqlEssais);
							$a_essais = mysql_fetch_array($resultEssais);
		
							$sqlScore = "SELECT exe_id , exe_result,exe_weighting
										 FROM $tbl_stats_exercices
										 WHERE exe_user_id = ".$_user['user_id']."
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
		
							echo "<tr>
									<td>
								 ";
							echo 		$a_exercices['title'];
							echo "	</td>
								 ";
							echo "	<td align='center'>
								  ";
							echo $pourcentageScore.'%';
							echo "	</td>
		
									<td align='center'>
								 ";
							echo 		$a_essais['essais'];
							echo '	</td>
									<td align="center" width="25">
								 ';
							if($a_essais['essais']>0)
								echo '<a href="../exercice/exercise_show.php?origin=student_progress&id='.$exe_id.'&cidReq='.$a_infosCours['code'].'&id_session='.$_GET['id_session'].'"> <img src="'.api_get_path(WEB_IMG_PATH).'quiz.gif" border="0"> </a>';
							echo "	</td>
								  </tr>
								 ";
						}
					}
					else{
						echo '<tr><td colspan="4">'.get_lang('NoEx').'</td></tr>';
					}
				}
				else{
					echo '<tr><td colspan="4">'.get_lang('NoEx').'</td></tr>';
				}

			?>
		</table>
		<?php
	}

Display :: display_footer();
?>
