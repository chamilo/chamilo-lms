<?php
// name of the language file that needs to be included
$language_file = array('registration','tracking','exercice','admin');

$cidReset = true;

require ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'tracking.lib.php');
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once ('../newscorm/learnpath.class.php');

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
$tbl_course_lp_view 		= Database :: get_course_table(TABLE_LP_VIEW);
$tbl_course_lp_view_item 	= Database :: get_course_table(TABLE_LP_ITEM_VIEW);
$tbl_course_lp 				= Database :: get_course_table(TABLE_LP_MAIN);
$tbl_course_lp_item 		= Database :: get_course_table(TABLE_LP_ITEM);
$tbl_course_quiz 			= Database :: get_course_table(TABLE_QUIZ_TEST);


// get course list
$sql = 'SELECT course_code FROM '.$tbl_course_user.' WHERE user_id='.$_user['user_id'];
$rs = api_sql_query($sql, __FILE__, __LINE__);
$Courses = array();
while($row = Database :: fetch_array($rs))
{
	$Courses[$row['course_code']] = CourseManager::get_course_information($row['course_code']);
}

// get the list of sessions where the user is subscribed as student
$sql = 'SELECT DISTINCT course_code FROM '.Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER).' WHERE id_user='.intval($_user['user_id']);
$rs = api_sql_query($sql, __FILE__, __LINE__);
while($row = Database :: fetch_array($rs))
{
	$Courses[$row['course_code']] = CourseManager::get_course_information($row['course_code']);
}
echo '<div class="actions-title" >';
echo $nameTools;
echo '</div>';
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
  <th><?php 
  echo get_lang('Score');
  Display :: display_icon('info3.gif',get_lang('ScormAndLPTestTotalAverage') , array ('align' => 'absmiddle', 'hspace' => '3px')); 
  ?></th>
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
	$total_time_login=Tracking :: get_time_spent_on_the_course($_user['user_id'], $enreg['code']);
	$time = api_time_to_hms($total_time_login);
	$pourcentageScore = Tracking :: get_average_test_scorm_and_lp ($_user['user_id'], $enreg['code']);
?>

<tr class='<?php echo $i?'row_odd':'row_even'; ?>'>
  	<td>
		<?php echo api_html_entity_decode($enreg['title'],ENT_QUOTES,$charset); ?>
  	</td>

  	<td align='center'>
		<?php echo $time; ?>
  	</td>

  	<td align='center'>
  		<?php echo $progress.'%'; ?>
  	</td>

  	<td align='center'>
		<?php 
		if (!is_null($pourcentageScore)) {
			echo $pourcentageScore.'%'; 
		} else {
			echo '0%';
		}
		?>
  	</td>

  	<td align='center' >
		<?php echo $lastConnexion ?>
  	</td>

  	<td align='center'>
		<a href="<?php echo api_get_self(); ?>?course=<?php echo $enreg['code']; ?>"> <?php Display::display_icon('2rightarrow.gif', get_lang('Details')); ?> </a>
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
		$a_infosCours = CourseManager::get_course_information($course);
		
		//get coach and session_name if there is one and if session_mode is activated
		if(api_get_setting('use_session_mode')=='true')
		{
			$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
			$tbl_session = Database :: get_main_table(TABLE_MAIN_SESSION);
			$tbl_session_course = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
			$tbl_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			
			$sql = 'SELECT id_session 
					FROM '.$tbl_session_course_user.' session_course_user
					WHERE session_course_user.id_user = '.intval($_user['user_id']).'
					AND session_course_user.course_code = "'.Database::escape_string($course).'"
					ORDER BY id_session DESC';
			$rs = api_sql_query($sql,__FILE__,__LINE__);
			
			$row=Database::fetch_array($rs);			
			if (!empty ($row[0]))
			{
				$session_id =intval($row[0]);	
			}
			//$session_id =intval(Database::result($rs,0,0));			
			
			if($session_id>0)
			{
				// get session name and coach of the session
				$sql = 'SELECT name, id_coach FROM '.$tbl_session.' 
						WHERE id='.$session_id;
				$rs = api_sql_query($sql,__FILE__,__LINE__);						
				$session_name = Database::result($rs,0,'name');
				$session_coach_id = intval(Database::result($rs,0,'id_coach'));
				
				// get coach of the course in the session
				$sql = 'SELECT id_coach FROM '.$tbl_session_course.' 
						WHERE id_session='.$session_id.'
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
		} // end if(api_get_setting('use_session_mode')=='true')
		
		$tableTitle = $a_infosCours['title'].' | Coach : '.$a_infosCours['tutor_name'].((!empty($session_name)) ? ' | '.get_lang('Session').' : '.$session_name : '');
		

		?>
		<table class="data_table" width="100%">
			<tr class="tableName">
				<td colspan="4">
					<strong><?php echo $tableTitle; ?></strong>
				</td>
			</tr>
			<tr>
			  <th class="head" style="color:#000"><?php echo get_lang('Learnpath'); ?></th>
			  <th class="head" style="color:#000"><?php echo get_lang('Time'); ?></th>
			  <th class="head" style="color:#000"><?php echo get_lang('Progress'); ?></th>
			  <th class="head" style="color:#000"><?php echo get_lang('LastConnexion'); ?></th>
			</tr>
			<?php
				$sqlLearnpath = "	SELECT lp.name,lp.id
									FROM ".$a_infosCours['db_name'].".".$tbl_course_lp." AS lp
								";

				$resultLearnpath = api_sql_query($sqlLearnpath);

				if(Database::num_rows($resultLearnpath)>0) {
					while($a_learnpath = Database::fetch_array($resultLearnpath)) {

						$progress = learnpath :: get_db_progress($a_learnpath['id'],$_user['user_id'], '%',$a_infosCours['db_name']);

						// calculates last connection time
						$sql = 'SELECT MAX(start_time)
									FROM '.$a_infosCours['db_name'].'.'.$tbl_course_lp_view_item.' AS item_view
									INNER JOIN '.$a_infosCours['db_name'].'.'.$tbl_course_lp_view.' AS view
										ON item_view.lp_view_id = view.id
										AND view.lp_id = '.$a_learnpath['id'].'
										AND view.user_id = '.$_user['user_id'];
						$rs = api_sql_query($sql, __FILE__, __LINE__);
						$start_time = Database::result($rs, 0, 0);

						// calculates time
						$sql = 'SELECT SUM(total_time)
									FROM '.$a_infosCours['db_name'].'.'.$tbl_course_lp_view_item.' AS item_view
									INNER JOIN '.$a_infosCours['db_name'].'.'.$tbl_course_lp_view.' AS view
										ON item_view.lp_view_id = view.id
										AND view.lp_id = '.$a_learnpath['id'].'
										AND view.user_id = '.$_user['user_id'];
						$rs = api_sql_query($sql, __FILE__, __LINE__);
						$total_time = Database::result($rs, 0, 0);


						echo "<tr>
								<td>
							 ";
						echo 		stripslashes($a_learnpath['name']);
						echo "	</td>
								<td align='center'>
							 ";
						echo api_time_to_hms($total_time);
						echo "	</td>
								<td align='center'>
							 ";
						echo		$progress;
						echo "	</td>
								<td align='center' width=180px >
							 ";
						if($start_time!=''){
							echo $lastConnexion;
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
			  <th class="head" style="color:#000"><?php echo get_lang('Exercices'); ?></th>
			  <th class="head" style="color:#000"><?php echo get_lang('Score'); ?></th>
			  <th class="head" style="color:#000"><?php echo get_lang('Attempts'); ?></th>
			  <th class="head" style="color:#000"><?php echo get_lang('Details'); ?></th>
			</tr>

			<?php
				
				$sql='SELECT visibility FROM '.$a_infosCours['db_name'].'.'.TABLE_TOOL_LIST.' WHERE name="quiz"';
				$resultVisibilityTests = api_sql_query($sql);
								
				if (Database::result($resultVisibilityTests,0,'visibility')==1) {				
					$sqlExercices = "	SELECT quiz.title,id, results_disabled
									FROM ".$a_infosCours['db_name'].".".$tbl_course_quiz." AS quiz
									WHERE active='1'";	
	
					$resuktExercices = api_sql_query($sqlExercices);
					if (Database::num_rows($resuktExercices)>0) {						
						while ($a_exercices = Database::fetch_array($resuktExercices)) {								
							$sqlEssais = "	SELECT COUNT(ex.exe_id) as essais
											FROM $tbl_stats_exercices AS ex
											WHERE ex.exe_user_id='".$_user['user_id']."' AND ex.exe_cours_id = '".$a_infosCours['code']."'
											AND ex.exe_exo_id = ".$a_exercices['id']."
											AND orig_lp_id = 0
											AND orig_lp_item_id = 0	"
										 ;
							$resultEssais = api_sql_query($sqlEssais);
							$a_essais = Database::fetch_array($resultEssais);
		
							$sqlScore = "SELECT exe_id , exe_result,exe_weighting
										 FROM $tbl_stats_exercices
										 WHERE exe_user_id = ".$_user['user_id']."
											 AND exe_cours_id = '".$a_infosCours['code']."'
											 AND exe_exo_id = ".$a_exercices['id']."
											 AND orig_lp_id = 0
											 AND orig_lp_item_id = 0			
										ORDER BY exe_date DESC LIMIT 1";
		
							$resultScore = api_sql_query($sqlScore);
							$score = 0;
							while($a_score = Database::fetch_array($resultScore)) {
								$score = $score + $a_score['exe_result'];
								$weighting = $weighting + $a_score['exe_weighting'];
								$exe_id = $a_score['exe_id'];
							}					
							
							if  ($weighting>0) {
								// i.e 10.50%							
								$pourcentageScore = round(($score*100)/$weighting,2);
							} else {
								$pourcentageScore=0;			
							}
		
							$weighting = 0;
		
							echo '<tr>
									<td>';
							echo $a_exercices['title'];
							echo '</td>';								 
																						
							if ($a_exercices['results_disabled']==0) {								
								echo '<td align="center">';	
								if ($a_essais['essais']>0) {
									echo $pourcentageScore.'%';
								} else {
									echo '/';
								}						 
								echo '</td>';								
								echo '<td align="center">';
								echo  $a_essais['essais'];
								echo '</td>
										<td align="center" width="25">';
								if($a_essais['essais']>0)
									echo '<a href="../exercice/exercise_show.php?origin=myprogress&id='.$exe_id.'&cidReq='.$a_infosCours['code'].'&id_session='.Security::remove_XSS($_GET['id_session']).'"> '.Display::return_icon('quiz.gif', get_lang('Quiz')).' </a>';
								echo '</td>';
							} else {
								// we show or not the results if the teacher wants to									
								echo '<td align="center">';							 
								echo get_lang('CantShowResults');
								echo '</td>';								
								echo '<td align="center">';
								echo ' -- ';
								echo '</td>
										<td align="center" width="25">';
								echo ' -- ';
								echo '</td>';
								
							}
							echo '</tr>';
						}
					} else {
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
