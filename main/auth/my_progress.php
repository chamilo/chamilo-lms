<?php
// name of the language file that needs to be included
$language_file = array('registration','tracking');

$cidReset = true;

$nameTools="Ma progression";

require ('../inc/global.inc.php');

if(isset($_GET['id_session']))
{
	$id_session = intval($_GET['id_session']);

	$_SESSION['id_session']=$id_session;
}
elseif(isset($_SESSION['id_session']))
{
	$id_session=$_SESSION['id_session'];
}
else
{
	$id_session=0;
}

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
$tbl_stats_exercices 		= Database :: get_statistic_table(STATISTIC_TRACK_E_EXERCICES_TABLE);
$tbl_course_lp_view 		= Database :: get_course_table('lp_view');
$tbl_course_lp_view_item 	= Database :: get_course_table('lp_item_view');
$tbl_course_lp 				= Database :: get_course_table('lp');
$tbl_course_lp_item 		= Database :: get_course_table('lp_item');
$tbl_course_quiz 			= Database :: get_course_table('quiz');

$result=api_sql_query("SELECT DISTINCT id, name, date_start, date_end FROM session_rel_course_rel_user,session WHERE id_session=id AND id_user=".$_user['user_id']." ORDER BY date_start, date_end, name",__FILE__,__LINE__);

$Sessions=api_store_result($result);

$Courses=array();

if($id_session)
{
   /*
	$result=api_sql_query("SELECT code, title, CONCAT(user.lastname,' ',user.firstname) coach, email
							FROM $tbl_session_course_user AS session_course_user, $tbl_session_course AS session_course, $tbl_course AS course, $tbl_user AS user
							WHERE session_course_user.id_session='$id_session'
							AND session_course_user.id_user='".$_user['user_id']."'
							AND session_course_user.course_code=course.code
							AND session_course_user.id_session=session_course.id_session
							AND session_course_user.course_code=session_course.course_code
							AND session_course.id_coach=user.user_id
							ORDER BY title",__FILE__,__LINE__);
	*/
			
	$sql = "SELECT DISTINCT code,title, CONCAT(lastname, ' ',firstname) coach, username, date_start, date_end, db_name
			FROM $tbl_course , $tbl_session_course
			LEFT JOIN $tbl_user
				ON $tbl_session_course.id_coach = $tbl_user.user_id
			INNER JOIN $tbl_session_course_user
				ON $tbl_session_course_user.id_session = $tbl_session_course.id_session
				AND $tbl_session_course_user.id_user = '".$_user['user_id']."'
			INNER JOIN $tbl_session ON $tbl_session.id = $tbl_session_course.id_session
			WHERE $tbl_session_course.course_code=code
			AND $tbl_session_course.id_session='$id_session'
			ORDER BY title";

	$result=api_sql_query($sql);
	
	$Courses=api_store_result($result);
}

api_display_tool_title($nameTools);

$now=date('Y-m-d');
?>

Commencez par sélectionner une session de cours ci-dessous.<br><br>
Vous pourrez ensuite suivre votre progression pour chaque cours auquel vous êtes inscrit.<br><br>

<form method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="margin: 0px;">
<center>
Session de cours :
<select name="id_session">
<option value="0">---------- Choisissez ----------</option>

<?php
$date_start=$date_end=$now;

foreach($Sessions as $enreg)
{
	if($enreg['id'] == $id_session)
	{
		$date_start=$enreg['date_start'];
		$date_end=$enreg['date_end'];
	}
	$enreg['date_start']=explode('-',$enreg['date_start']);
	$enreg['date_start']=$enreg['date_start'][2].'/'.$enreg['date_start'][1].'/'.$enreg['date_start'][0];

	$enreg['date_end']=explode('-',$enreg['date_end']);
	$enreg['date_end']=$enreg['date_end'][2].'/'.$enreg['date_end'][1].'/'.$enreg['date_end'][0];
	
?>

<option value="<?php echo $enreg['id']; ?>" <?php if($enreg['id'] == $id_session) echo 'selected="selected"'; ?> ><?php echo htmlentities($enreg['name']); if($date_start!='0000-00-00') { ?> (du <?php echo $enreg['date_start']; ?> au <?php echo $enreg['date_end']; ?>)<?php } ?></option>

<?php
}

unset($Sessions);
?>

</select>
<input type="submit" value="Valider">
</center>
</form>

<br><br>

<table class="data_table" width="100%">
<tr class="tableName">
	<td colspan="6">
		<strong><?php echo get_lang('MyLearnpath'); ?></strong>
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
	$sqlTime = "SELECT total_time
				FROM ".$enreg['db_name'].'.'.$tbl_course_lp_view_item." AS lpi
				INNER JOIN ".$enreg['db_name'].'.'.$tbl_course_lp_view." AS lpv
					ON lpv.lp_id = lpi.lp_view_id
					AND lpv.user_id = ".$_user['user_id']
				;
	$result = api_sql_query($sqlTime);
	while($totalTime = mysql_fetch_array($result))
	{
		//print_r($totalTime);
	}
	
	$sqlScore = "SELECT exe_result,exe_weighting
				 FROM $tbl_stats_exercices
				 WHERE exe_user_id = ".$_user['user_id']."
				 AND exe_cours_id = '".$enreg['code']."'
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
	
	$sqlLastAccess = "	SELECT access_date
						FROM $tbl_stats_lastaccess
						WHERE access_user_id = ".$_user['user_id']."
						AND access_cours_code = '".$enreg['code']."'
						ORDER BY access_date DESC LIMIT 0,1"
					;
	$result = api_sql_query($sqlLastAccess);
	$lastAccess = mysql_fetch_array($result);
	
	if(!empty($lastAccess['access_date']))
	{
		$a_lastConnexion = explode(' ',$lastAccess['access_date']);
		$a_date = explode('-',$a_lastConnexion[0]);
		$a_heure = explode(':',$a_lastConnexion[1]);
		$lastConnexion = $a_date[2]."/".$a_date[1]."/".$a_date[0];
		$lastAccessTms  = mktime ( $a_heure[0], $a_heure[1] ,$a_heure[2] ,$a_date[1], $a_date[2],$a_date[0]);
	}
	else
	{
		$lastConnexion = get_lang('NoConnexion');
	}
	$sqlProgress = "SELECT COUNT(DISTINCT item_view.lp_item_id) AS nbItem
					FROM ".$enreg['db_name'].".".$tbl_course_lp_view_item." AS item_view
					INNER JOIN ".$enreg['db_name'].".".$tbl_course_lp_view." AS view
						ON view.user_id = ".$_user['user_id']."
					WHERE item_view.status = 'completed'
					";
	$resultProgress = api_sql_query($sqlProgress);
	$a_nbItem = mysql_fetch_array($resultProgress);
	
	$table = $enreg['db_name'].'.'.$tbl_course_lp_item;
	$nbTotalItem = Database :: count_rows($table);

	$totalItem = $totalItem + $nbTotalItem;
	$totalProgress = $totalProgress + $a_nbItem['nbItem'];
	
	$progress = round(($a_nbItem['nbItem'] * 100)/$nbTotalItem);
	
	/*$time = $lastAccessTms - $firstAccessTms;
	
	if($time >= 60)
	{
		$minute = round($time/60);
		if($minute >= 60)
		{
			$heure = round($minute/60);
			$minute = $minute - round((60*(($time/60)/60)));
			if($minute == 0)
			{
				$minute = '00';
			}
		}
		else
		{
			$heure = 0;
		}
		$temps = $heure.'h'.$minute;
	}
	else
	{
		$temps = '0h00';
	}
	$totalTime .= $time; */
?>

<tr class='<?php echo $i?'row_odd':'row_even'; ?>'>
  	<td>
		<?php echo htmlentities($enreg['title']); ?>
  	</td>
  
  	<td align='center'>
		<?php echo $temps; ?>
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
		<a href="<?php echo $SERVER['PHP_SELF']; ?>?course=<?php echo $enreg['code']; ?>"> -> </a>
  	</td>
</tr>

<?php



	$i=$i ? 0 : 1;
}

unset($Courses);

/*if($totalTime >= 60)
{
	 $minute = round($totalTime / 60);

	if($minute >= 60)
	{
		$heure = round($minute / 60);
		$minute = $minute - round((60*(($time/60)/60)));
		if($minute == 0)
		{
			$minute = '00';
		}
			
	}
	else
	{
		$heure = 0;
	}
	
	$totalTemps = $heure.'h'.$minute;
}
else
{
	$totalTemps = '0h00';
}*/

$totalPourcentageScore = round(($totalScore*100)/$totalWeighting);
$progress = round(($totalProgress*100)/$totalItem);

?>
<tr class='total'>
  	<td>
		<strong><?php echo get_lang('Total'); ?></strong>
  	</td>
  
  	<td align='center'>
		<?php echo $totalTemps; ?>
  	</td>

  	<td align='center'>
		<?php echo $progress.'%'; ?>
  	</td>

  	<td align='center'>
		<?php echo $totalPourcentageScore.'%'; ?>
  	</td>

  	<td>
  	</td>

  	<td>
  	</td>
</tr>
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
		$sqlInfosCourse = "	SELECT course.code,course.title,course.db_name,CONCAT(user.firstname,' ',user.lastname,' / ',user.email) as tutor_infos
							FROM $tbl_user as user,$tbl_course as course
							INNER JOIN $tbl_session_course as sessionCourse
								ON sessionCourse.course_code = course.code
							WHERE sessionCourse.id_coach = user.user_id
							AND course.code= '".$_GET['course']."'
						 ";
		
		$resultInfosCourse = api_sql_query($sqlInfosCourse);
		
		$a_infosCours = mysql_fetch_array($resultInfosCourse);
		$tableTitle = $a_infosCours['title'].' - '.get_lang('Tutor').' : '.$a_infosCours['tutor_infos'];
		
		
		
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
						
						
						echo "<tr>
								<td>
							 ";
						echo 		$a_learnpath['name'];
						echo "	</td>
								<td>
							 ";
						echo "	</td>
								<td align='center'>
							 ";
						echo		$progress.'%';
						echo "	</td>
								<td align='center'>
							 ";
						echo		"unknown";
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
			  <th class="head"><?php echo get_lang('Essais'); ?></th>
			  <th class="head"><?php echo get_lang('Correction'); ?></th>
			</tr>
			
			<?php
			
				$sqlExercices = "	SELECT quiz.title,id
								FROM ".$a_infosCours['db_name'].".".$tbl_course_quiz." AS quiz
							";
		
					
				$resuktExercices = api_sql_query($sqlExercices);
				while($a_exercices = mysql_fetch_array($resuktExercices))
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
								 WHERE exe_user_id = ".$_user['user_id']."
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
					echo $pourcentageScore.'%';
					echo "	</td>
					
							<td align='center'>
						 ";
					echo 		$a_essais['essais'];
					echo "	</td>
							<td>
						 ";
					echo "	</td>
						  </tr>
						 ";
				}
				
			
			?>
		</table>
		<?php
	}

Display :: display_footer();
?>
