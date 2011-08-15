<?php
/* For licensing terms, see /license.txt */
/**
 * 
 * Exercise results from Learning paths
 * 
 * @todo implement pagination
 * @package chamilo.tracking
 */
/**
 * Code
 */

$language_file = array ('registration', 'index', 'tracking', 'exercice','survey');

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'pear/Spreadsheet_Excel_Writer/Writer.php';

$this_section = SECTION_TRACKING;

$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin || $is_courseCoach || $is_sessionAdmin;

if (!$is_allowedToTrack) {
	Display :: display_header(null);
	api_not_allowed();
	Display :: display_footer();
}

$export_to_csv = false;
if (isset($_GET['export'])) {
	$export_to_csv = true;
}

$TBL_EXERCICES			= Database::get_course_table(TABLE_QUIZ_TEST);
$tbl_stats_exercices 	= Database :: get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);

if (api_is_platform_admin() ) {	
	$global = true;
} else {
	$global = false;
}

if ($global) {
	$temp_course_list = CourseManager :: get_courses_list();	
	foreach($temp_course_list  as $temp_course_item) {
		$course_item = CourseManager ::get_course_information($temp_course_item['code']);		
		$course_list[]= array('db_name' =>$course_item['db_name'],'code'=>$course_item['code'], 'title'=>$course_item['title']);	
	}
} else {	
	$current_course['db_name'] 	= $_course['dbName'];
	$current_course['code'] 	= $_course['id'];	
	$course_list = array($current_course);
}

$new_course_select = array();
foreach($course_list as $data) {
    $new_course_select[$data['code']] = $data['title'];
}

$form = new FormValidator('search_simple','POST','','',null,false);
$form->addElement('select','course_code',get_lang('Course'), $new_course_select);
if ($global) {
	$form->addElement('hidden','view','admin');
} else {
	//Get exam lists
	$t_quiz = Database::get_course_table(TABLE_QUIZ_TEST,$_course['db_name']);	
	$sqlExercices = "	SELECT quiz.title,id FROM ".$t_quiz." AS quiz WHERE active='1' ORDER BY quiz.title ASC";
	$resultExercices = Database::query($sqlExercices);	
	$exercise_list[0] = get_lang('All');
	while($a_exercices = Database::fetch_array($resultExercices)) {
		$exercise_list[$a_exercices['id']] = $a_exercices['title'];
	}	
	$form->addElement('select', 'exercise_id', get_lang('Exercise'), $exercise_list);
}
		
//$form->addElement('submit','submit',get_lang('Filter'));
$form->addElement('style_submit_button','submit', get_lang('Filter'),'class="search"' );

if (!empty($_REQUEST['course_code']))	
    $selected_course = $_REQUEST['course_code'];
if (!empty($selected_course)) {
    $selected_course = api_get_course_info($selected_course);
    $course_list = array($selected_course); 
}

if (!$export_to_csv) {
	Display :: display_header(get_lang('Reporting'));
	echo '<div class="actions" style ="font-size:10pt;">';
	if ($global) {
	    	
        echo '<div style="float:right"> <a href="'.api_get_self().'?export=1&score='.$filter_score.'&exercise_id='.$exercise_id.'"><img align="absbottom" src="../img/csv.gif">&nbsp;'.get_lang('ExportAsCSV').'</a>' .
                '<a href="javascript: void(0);" onclick="javascript: window.print()"><img align="absbottom" src="../img/printmgr.gif">&nbsp;'.get_lang('Print').'</a></div>';
        
		$menu_items[] = '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace/?view=teacher">'.get_lang('TeacherInterface').'</a>';
        if (api_is_platform_admin()) {
		  $menu_items[] = '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace/?view=admin">'.get_lang('AdminInterface').'</a>';
        } else {
            $menu_items[] = '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace/?view=coach">'.get_lang('AdminInterface').'</a>';	
        }
		$menu_items[] = get_lang('ExamTracking');		
		$nb_menu_items = count($menu_items);
		if($nb_menu_items>1) {
			foreach($menu_items as $key=> $item) {
				echo $item;
				if($key!=$nb_menu_items-1) {
					echo ' | ';
				}
			}
			echo '<br />';
		}
	} else {
	   echo '<a href="courseLog.php?'.api_get_cidreq().'&studentlist=true">'.get_lang('StudentsTracking').'</a>&nbsp;| 
		     <a href="courseLog.php?'.api_get_cidreq().'&studentlist=false">'.get_lang('CourseTracking').'</a>&nbsp;|&nbsp';
       echo '<a href="courseLog.php?'.api_get_cidreq().'&studentlist=resources">'.get_lang('ResourcesTracking').'</a>';
		echo ' | '.get_lang('ExamTracking').'';
         echo '<a href="'.api_get_self().'?export=1&score='.$filter_score.'&exercise_id='.$exercise_id.'"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsXLS').'</a><br /><br />';		
			
	}	
    echo '</div>';  
	echo '<br /><br />';
	$form->display();		
	//echo '<h3>'.sprintf(get_lang('FilteringWithScoreX'), $filter_score).'%</h3>';
}
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';
$main_result = array();
$session_id = 0;
$user_list = array();
//Getting course list
foreach($course_list  as $current_course ) {
	$course_info = api_get_course_info($current_course['code']);
	$_course = $course_info; 
	
	
	//Getting LP list
	$list = new learnpathList('', $current_course['code'], $session_id);
	$lp_list = $list->get_flat_list();	
		
	// Looping LPs
	$lps = array();
	foreach ($lp_list as $lp_id =>$lp) {		
		$exercise_list = get_all_exercises_from_lp($lp_id, $current_course['db_name']);
		$attempt_result = array();		
		//Looping Chamilo Exercises in LP
		foreach ($exercise_list as $exercise) {
			$exercise_stats = get_all_exercise_event_from_lp($exercise['path'], $course_info['id'], $session_id);
			//Looping Exercise Attempts
			foreach($exercise_stats as $stats) {
				//$attempt_result[$exercise['id']]['users'][$stats['exe_user_id']][$stats['exe_id']] = array('exe_result' =>$stats['exe_result'],'exe_weighting' =>$stats['exe_weighting']);
				$attempt_result[$exercise['id']]['users'][$stats['exe_user_id']][$stats['exe_id']] = $stats;
				$user_list[$stats['exe_user_id']] = $stats['exe_user_id'];
			}			
			$exercise_list_name[$exercise['id']] = $exercise['title'];
		}
		$lps[$lp_id] = array('lp_name' =>$lp['lp_name'], 'exercises' =>$attempt_result);		
		$lp_list_name[$lp_id] = $lp['lp_name'];
	}
	$main_result[$current_course['code']] = $lps;	
}
//echo '<pre>';print_r($main_result);
if (!empty($user_list)) {
    foreach($user_list as $user_id) {
        $user_data = api_get_user_info($user_id);
        $user_list_name[$user_id] = api_get_person_name($user_data['firstname'],$user_data['lastname']);
    }
}
$export_array =  array();
if (!empty($main_result)) {
    
    $html_result .= '<table  class="data_table">';
    $html_result .= '<tr><th>'.get_lang('Course').'</th>';
    $html_result .= '<th>'.get_lang('LearningPath').'</th>';
    $html_result .= '<th>'.get_lang('Exercise').'</th>';
    $html_result .= '<th>'.get_lang('User').'</th>';
    $html_result .= '<th>'.get_lang('Attempt').'</th>';
    $html_result .= '<th>'.get_lang('Date').'</th>';
    $html_result .= '<th>'.get_lang('Results').'</th>';
    $html_result .= '</tr>';        
    
    foreach ($main_result as $course_code => $lps) {
        if (empty($lps)) {
            continue;
        }   
        
        foreach($lps as $lp_id => $lp_data) { 
            $exercises = $lp_data['exercises']; 
            
            foreach($exercises as $exercise_id => $exercise_data) {
                $users = $exercise_data['users'];
                foreach($users as $user_id => $attempts) {
                    $attempt = 1;         
                    foreach($attempts as $exe_id => $attempt_data) {
                        $html_result .= '<tr colspan="">';
                        $html_result .= Display::tag('td', $course_code);
                        $html_result .= Display::tag('td', $lp_list_name[$lp_id]);
                        $html_result .= Display::tag('td', $exercise_list_name[$exercise_id]);
                        $html_result .= Display::tag('td', $user_list_name[$user_id]);
                        $result = $attempt_data['exe_result'].' / '.$attempt_data['exe_weighting'];
                        $html_result .= Display::tag('td', $attempt);                        
                        $html_result .= Display::tag('td', api_get_local_time($attempt_data['exe_date']));
                        $html_result .= Display::tag('td', $result);
                                                 
                        $html_result .= '</tr>';
                        $export_array[]= array($course_code, $lp_list_name[$lp_id], $exercise_list_name[$exercise_id], $user_list_name[$user_id], $attempt, api_get_local_time($attempt_data['exe_date']), $result);
                        $attempt++;
                    }
                }    
            }   
        }
    }
    $html_result .='</table>';
}

if (!$export_to_csv) {
	echo $html_result;	
}
$filename = 'learning_path_results-'.date('Y-m-d-h:i:s').'.xls';
if ($export_to_csv) {
    export_complete_report_csv($filename, $export_array);
	exit;
}
function export_complete_report_csv($filename, $array) {	
        require_once api_get_path(LIBRARY_PATH).'export.lib.inc.php';
        $header[] = array(get_lang('Course'),get_lang('LearningPath'), get_lang('Exercise'), get_lang('User'),get_lang('Attempt'), get_lang('Date'),get_lang('Results'));
        if (!empty($array)) {
            $array = array_merge($header, $array);            
            Export :: export_table_csv($array, $filename);
        }
        exit;
        /*    
         * Too much encoding problems while exporting to XLS, keep it simple 
         * 
         * 
		global $global, $filter_score;
		$workbook = new Spreadsheet_Excel_Writer();
		$workbook ->setTempDir(api_get_path(SYS_ARCHIVE_PATH));
		$workbook->send($filename);
		$workbook->setVersion(8); // BIFF8
		$worksheet =& $workbook->addWorksheet('Report');
		$worksheet->setInputEncoding(api_get_system_encoding());		
		
		$line = 0;
		$column = 0; //skip the first column (row titles)		

		$worksheet->write($line,$column,get_lang('Course'));
		$column++;
		$worksheet->write($line,$column,get_lang('LearningPath'));
		$column++;
		$worksheet->write($line,$column,get_lang('Exercise'));
		$column++;			
		$worksheet->write($line,$column,get_lang('User'));
		$column++;
		$worksheet->write($line,$column,get_lang('Attempt'));
		$column++;
		$worksheet->write($line,$column,get_lang('Date'));
		$column++;	
		$worksheet->write($line,$column,get_lang('Results'));
		$column++;		
		$line++;		
		foreach ($array as $row) {
			$column = 0;
			foreach ($row as $item) {						
				$worksheet->write($line,$column,html_entity_decode(strip_tags($item)));
				$column++;
			}
			$line++;
		}	
		$line++;			
		
		$workbook->close();
		exit;*/
}
Display :: display_footer();
