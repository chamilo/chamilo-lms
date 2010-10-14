<?php
/* For licensing terms, see /license.txt */
// name of the language file that needs to be included
$language_file = array('registration', 'tracking', 'exercice', 'admin');

$cidReset = true;

require_once '../inc/global.inc.php';

require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
global $_configuration;
      
$this_section = SECTION_TRACKING;
$nameTools = get_lang('MyProgress');

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

$tbl_access_rel_session     = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
$tbl_access_rel_course      = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

// get course list
if ($_configuration['multiple_access_urls']) {    
    $sql = 'SELECT cu.course_code FROM '.$tbl_course_user.' cu INNER JOIN '.$tbl_access_rel_course.' a  ON(a.course_code = cu.course_code) WHERE user_id='.intval($_user['user_id']).' AND relation_type<>'.COURSE_RELATION_TYPE_RRHH.' AND access_url_id = '.api_get_current_access_url_id().'';
} else {
	$sql = 'SELECT course_code FROM '.$tbl_course_user.' WHERE user_id='.intval($_user['user_id']).' AND relation_type<>'.COURSE_RELATION_TYPE_RRHH.' ';
}

$rs = Database::query($sql);
$courses = $course_in_session = array();

while($row = Database :: fetch_array($rs)) {
	$courses[$row['course_code']] = CourseManager::get_course_information($row['course_code']);
}

// Get the list of sessions where the user is subscribed as student
if ($_configuration['multiple_access_urls']) {
    $sql = 'SELECT DISTINCT cu.course_code, id_session as session_id FROM '.$tbl_session_course_user.' cu INNER JOIN '.$tbl_access_rel_session.' a  ON(a.session_id = cu.id_session) WHERE id_user='.$_user['user_id'].' AND access_url_id = '.api_get_current_access_url_id().'';
} else {
	$sql = 'SELECT DISTINCT course_code, id_session as session_id FROM '.$tbl_session_course_user.' WHERE id_user='.intval($_user['user_id']);
}

$rs = Database::query($sql);
while($row = Database :: fetch_array($rs)) {
	$course_in_session[$row['session_id']][$row['course_code']] = CourseManager::get_course_information($row['course_code']);
}
/*echo '<div class="actions-title" >';
echo $nameTools;
echo '</div>';*/

if (!empty($courses)) {
?>
<table class="data_table" width="100%">
<tr class="tableName">
	<td colspan="6">
		<h1><?php echo get_lang('MyCourses'); ?></h1>
	</td>
</tr>
<tr>
  <th width="300px"><?php echo get_lang('Course'); ?></th>
  <th><?php echo get_lang('Time'); ?></th>
  <th><?php echo get_lang('Progress'); ?></th>
  <th><?php echo get_lang('Score'); Display :: display_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), array ('align' => 'absmiddle', 'hspace' => '3px')); ?></th>
  <th><?php echo get_lang('LastConnexion'); ?></th>
  <th><?php echo get_lang('Details'); ?></th>
</tr>
<?php
    $i = 0;
    foreach ($courses as $enreg) {
    	$weighting = 0;
        
        $total_time_login      = Tracking :: get_time_spent_on_the_course($_user['user_id'], $enreg['code']);
        $time                  = api_time_to_hms($total_time_login);
        $progress              = Tracking :: get_avg_student_progress($_user['user_id'], $enreg['code']);
        $percentage_score      = Tracking :: get_avg_student_score($_user['user_id'], $enreg['code'], array());
    	$last_connection       = Tracking :: get_last_connection_date_on_the_course($_user['user_id'], $enreg['code']);
    	
    
        if ($enreg['code'] == $_GET['course'] && empty($_GET['session_id'])) {
            echo '<tr class="row_odd" style="background-color:#FBF09D">';
        } else {
            echo '<tr class="row_even">';
        }
        
      	echo '<td>'.$enreg['title'].'</td>';
        
      	echo '<td align="center">'.$time.'</td>';
        echo '<td align="center">'.$progress.'%</td>';
      	
      	echo '<td align="center">';
    	if (is_numeric($percentage_score)) {
    		echo $percentage_score.'%';
    	} else {
    		echo '0%';
    	}	
      	echo '</td>';        
      	echo '<td align="center">'.$last_connection.'</td>';    	
      	echo '<td align="center">';		
    	if ($enreg['code'] == $_GET['course'] && empty($_GET['session_id'])) {
    		echo '<a href="#">';
    		Display::display_icon('2rightarrow_na.gif', get_lang('Details'));
    	} else {
    		echo '<a href="'.api_get_self().'?course='.$enreg['code'].'">';
    		Display::display_icon('2rightarrow.gif', get_lang('Details'));
    	}
    	echo '</a>';
        echo '</td></tr>';
    	$i = $i ? 0 : 1;
    }    
    echo '</table>';
}

if (!empty($course_in_session)) {
?>

<br />
 <h1><?php echo get_lang('Sessions'); ?></h1>
<?php    
    foreach ($course_in_session as $key=>$session) {
        echo '<h2>'.api_get_session_name($key).' </h2>';
        ?>        
        <table class="data_table" width="100%">
        <tr>
          <th width="300px"><?php echo get_lang('Course'); ?></th>
          <th><?php echo get_lang('Time'); ?></th>
          <th><?php echo get_lang('Progress'); ?></th>
          <th><?php echo get_lang('Score'); Display :: display_icon('info3.gif', get_lang('ScormAndLPTestTotalAverage'), array ('align' => 'absmiddle', 'hspace' => '3px')); ?></th>
          <th><?php echo get_lang('LastConnexion'); ?></th>
          <th><?php echo get_lang('Details'); ?></th>
        </tr>
        <?php           
          
        foreach ($session as $enreg) {               
            $weighting = 0;
            $last_connection       = Tracking :: get_last_connection_date_on_the_course($_user['user_id'], $enreg['code'], $key);
            $progress              = Tracking :: get_avg_student_progress($_user['user_id'], $enreg['code'],array(), $key);
            
            $total_time_login      = Tracking :: get_time_spent_on_the_course($_user['user_id'], $enreg['code'], $key);
            $time                  = api_time_to_hms($total_time_login);
            $percentage_score      = Tracking :: get_avg_student_score($_user['user_id'], $enreg['code'], array(), $key);
            
            if ($enreg['code'] == $_GET['course'] && $_GET['session_id'] == $key) {
                echo '<tr  class="row_odd" style="background-color:#FBF09D" >';
            } else {
                echo '<tr  class="row_even">';
            }
            
            
            echo '<td>'.$enreg['title'].' </td>';        
            echo '<td align="center">'.$time.'</td>';
            
            if (is_numeric($progress)) {
                $progress = $progress.'%';
            } else {
                $progress = '0%';
            }
            
            echo '<td align="center">'.$progress.'</td>';
            echo '<td align="center">';
            if (is_numeric($percentage_score)) {
                echo $percentage_score.'%';
            } else {
                echo '0%';
            }   
            echo '</td>';        
            echo '<td align="center">'.$last_connection.'</td>';        
            echo '<td align="center">';     
            if ($enreg['code'] == $_GET['course'] && $_GET['session_id'] == $key) {
                echo '<a href="#">';
                Display::display_icon('2rightarrow_na.gif', get_lang('Details'));
            } else {
                echo '<a href="'.api_get_self().'?course='.$enreg['code'].'&session_id='.$key.'">';
                Display::display_icon('2rightarrow.gif', get_lang('Details'));
            }
            echo '</a>';
            echo '</td>';
            $i = $i ? 0 : 1;
            echo '</tr>';
        }
        echo '</table>';  
    }
    
}
?>
<br /><br />
<?php
/*	Details for one course  */
	if (isset($_GET['course'])) {
        $session_id = $_GET['session_id'];
        //var_dump($session_id);
		$course                     = Database::escape_string($_GET['course']);
		$course_info                = CourseManager::get_course_information($course);
        $tbl_user                   = Database :: get_main_table(TABLE_MAIN_USER);
        $tbl_session                = Database :: get_main_table(TABLE_MAIN_SESSION);
        $tbl_session_course         = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
        $tbl_session_course_user    = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
        $tbl_course_lp_view         = Database :: get_course_table(TABLE_LP_VIEW, $course_info['db_name']);
        $tbl_course_lp_view_item    = Database :: get_course_table(TABLE_LP_ITEM_VIEW, $course_info['db_name']);
        $tbl_course_lp              = Database :: get_course_table(TABLE_LP_MAIN, $course_info['db_name']);
        $tbl_course_lp_item         = Database :: get_course_table(TABLE_LP_ITEM, $course_info['db_name']);
        $tbl_course_quiz            = Database :: get_course_table(TABLE_QUIZ_TEST, $course_info['db_name']);

		//get coach and session_name if there is one and if session_mode is activated
  
        /*
		if (api_get_setting('use_session_mode') == 'true') {
            
            if ($_configuration['multiple_access_urls']) {            
    			$sql = 'SELECT id_session
    					FROM '.$tbl_session_course_user.' session_course_user  INNER JOIN '.$tbl_access_rel_session.' a ON(session_course_user.id_session = a.session_id)
    					WHERE session_course_user.id_user = '.intval($_user['user_id']).'
    					AND session_course_user.course_code = "'.Database::escape_string($course).'" AND access_url_id = '.api_get_current_access_url_id().'
    					ORDER BY id_session DESC';
           
            } else {
                $sql = 'SELECT id_session
                        FROM '.$tbl_session_course_user.' session_course_user
                        WHERE session_course_user.id_user = '.intval($_user['user_id']).'
                        AND session_course_user.course_code = "'.Database::escape_string($course).'"
                        ORDER BY id_session DESC';	
            }
			$rs = Database::query($sql);

			$row = Database::fetch_array($rs);

			if ($session_id > 0) {
				// get session name and coach of the session
				$sql = 'SELECT name, id_coach FROM '.$tbl_session.'
						WHERE id='.$session_id;
				$rs = Database::query($sql);
				$session_name = Database::result($rs, 0, 'name');
				$session_coach_id = intval(Database::result($rs, 0, 'id_coach'));

				$sql = 'SELECT id_user FROM ' . $tbl_session_course_user . '
						WHERE id_session=' . $session_id . '
						AND course_code = "' . Database :: escape_string($course) . '" AND status=2';
				$rs = Database::query($sql);
				$course_coachs = array();
				while ($row_coachs = Database::fetch_array($rs)) {
					$course_coachs[] = $row_coachs['id_user'];
				}

				if (!empty($course_coachs)) {
					$info_tutor_name = array();
					foreach ($course_coachs as $course_coach) {
						$coach_infos = UserManager :: get_user_info_by_id($course_coach);
						$info_tutor_name[] = api_get_person_name($coach_infos['firstname'], $coach_infos['lastname']);
					}
					$course_info['tutor_name'] = implode(",",$info_tutor_name);
				} else if($session_coach_id != 0) {
					$coach_info = UserManager :: get_user_info_by_id($session_coach_id);
					$course_info['tutor_name'] = api_get_person_name($coach_info['firstname'], $coach_info['lastname']);
				}
			}
		} // end if (api_get_setting('use_session_mode') == 'true')*/

		//$tableTitle = $course_info['title'].' | '.get_lang('Coach').' : '.$course_info['tutor_name'].((!empty($session_name)) ? ' | '.get_lang('Session').' : '.$session_name : '');
        
        $session_name = api_get_session_name($session_id);
        $tableTitle = ((!empty($session_name)) ? ' '.get_lang('Session').' : '.$session_name.' | ' : '').''.$course_info['title'];

		?>
		<table class="data_table" width="100%">
			<tr class="tableName">
				<td colspan="4">
					<h3><?php echo $tableTitle; ?></h3>
				</td>
			</tr>
			<tr>
			  <th class="head" style="color:#000"><?php echo get_lang('Learnpath'); ?></th>
			  <th class="head" style="color:#000"><?php echo get_lang('Time'); ?></th>
			  <th class="head" style="color:#000"><?php echo get_lang('Progress'); ?></th>
			  <th class="head" style="color:#000"><?php echo get_lang('LastConnexion'); ?></th>
			</tr>
			<?php
            
                if (empty($session_id)) {
				    $sql_learnpath = "SELECT lp.name,lp.id FROM ".$tbl_course_lp." AS lp  WHERE session_id = 0 ORDER BY lp.display_order";
                } else {
                	$sql_learnpath = "SELECT lp.name,lp.id FROM ".$tbl_course_lp." AS lp ORDER BY lp.display_order";
                }
                
				$result_learnpath = Database::query($sql_learnpath);
				if (Database::num_rows($result_learnpath) > 0) {
					while($learnpath = Database::fetch_array($result_learnpath)) {
                        //$progress = learnpath :: get_db_progress($learnpath['id'], $_user['user_id'], 'abs', $course_info['db_name'], false, $session_id);                        
                        $progress               = Tracking::get_avg_student_progress($_user['user_id'], $course, array($learnpath['id']), $session_id);                        
                        $last_connection_in_lp  = Tracking::get_last_connection_time_in_lp($_user['user_id'], $course, $learnpath['id'], $session_id);
                        $time_spent_in_lp       = Tracking::get_time_spent_in_lp($_user['user_id'], $course, array($learnpath['id']), $session_id);
                        $time_spent_in_lp       = api_time_to_hms($time_spent_in_lp);                            
                                      
						echo "<tr><td>";
						echo $learnpath['name'];
						echo "	</td>
								<td align='center'>";
						echo $time_spent_in_lp;
						echo "	</td>
								<td align='center'>";
                        if (is_numeric($progress)) {
                            $progress = $progress.'%';
                        }
						echo $progress;
						echo "	</td>
								<td align='center' width=180px >";
						                        
						if (!empty($last_connection_in_lp)) {
							echo api_get_utc_datetime($last_connection_in_lp);
						} else {
							echo '-';
						}
						echo "</td></tr>";
                    }
				} else {
					echo '	<tr>
								<td colspan="4" align="center">
									'.get_lang('NoLearnpath').'
								</td>
							</tr>';
				}
                			
				// This code was commented on purpose see BT#924

				/*$sql = 'SELECT visibility FROM '.$course_info['db_name'].'.'.TABLE_TOOL_LIST.' WHERE name="quiz"';
				$result_visibility_tests = Database::query($sql);

				if (Database::result($result_visibility_tests, 0, 'visibility') == 1) {*/
                if (empty($session_id)) {
					$sql_exercices = "SELECT quiz.title,id, results_disabled FROM ".$tbl_course_quiz." AS quiz WHERE active='1' AND session_id = 0";
                } else {
                	$sql_exercices = "SELECT quiz.title,id, results_disabled FROM ".$tbl_course_quiz." AS quiz WHERE active='1'";
                }
    				echo '<tr>
    	  				<th class="head" style="color:#000">'.get_lang('Exercices').'</th>
    	  				<th class="head" style="color:#000">'.get_lang('Score').'</th>
    	  				<th class="head" style="color:#000">'.get_lang('Attempts').'</th>
    	  				<th class="head" style="color:#000">'.get_lang('LatestAttempt').'</th>
    					</tr>';
					$result_exercices = Database::query($sql_exercices);
					if (Database::num_rows($result_exercices) > 0) {
						while ($exercices = Database::fetch_array($result_exercices)) {
                            $score = 0;
                            $weighting = 0;
                            $exercise_stats = get_all_exercise_event($exercices['id'],$course_info['code'], $session_id);
                            $attempts = 0;
                            foreach($exercise_stats as $exercise_stat) {
                            	if ($exercise_stat['exe_user_id'] == $_user['user_id']) {
                                   $score          = $score + $exercise_stat['exe_result'];
                            	   $weighting      = $weighting + $exercise_stat['exe_weighting'];
                                   $exe_id         = $exercise_stat['exe_id'];
                                   $attempts++;
                            	}
                            }                            
							if  ($weighting > 0) {
								// i.e 10.50%
								$percentage_score = round(($score * 100) / $weighting, 2);
							} else {
								$percentage_score = 0;
							}
							echo '<tr>
									<td>';
							echo $exercices['title'];
							echo '</td>';
							if ($exercices['results_disabled'] == 0) {
								echo '<td align="center">';
								if ($attempts > 0) {
									echo $percentage_score.'%';
								} else {
									echo '/';
								}
								echo '</td>';
								echo '<td align="center">';
								echo  $attempts;
								echo '</td>
										<td align="center" width="25">';
								if ($attempts > 0) {
									echo '<a href="../exercice/exercise_show.php?origin=myprogress&id='.$exe_id.'&cidReq='.$course_info['code'].'&id_session='.$session_id.'"> '.Display::return_icon('quiz.gif', get_lang('Quiz')).' </a>';
								}
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
						echo '<tr><td colspan="4" align="center">'.get_lang('NoEx').'</td></tr>';
					}
			?>
		</table>
		<?php
	}
Display :: display_footer();