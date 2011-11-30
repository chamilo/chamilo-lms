<?php
/**
 * Report
 * @package chamilo.tracking
 */
/**
 * Code
 */
$language_file = array ('registration', 'index', 'tracking', 'exercice','survey');
$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'pear/Spreadsheet_Excel_Writer/Writer.php';

require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/question.class.php';

require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';

$this_section = "session_my_space";


$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin || $is_courseCoach || $is_sessionAdmin;

if(!$is_allowedToTrack) {	
	api_not_allowed(true);	
}

$export_to_xls = false;
if (isset($_GET['export'])) {
	$export_to_xls = true;
}
if (api_is_platform_admin() ) {	
	$global = true;
} else {
	$global = false;
}
$global = true;

$session_id = intval($_GET['session_id']);
if (empty($session_id)) {
	$session_id  = 1;
}

$form = new FormValidator('search_simple','POST','','',null,false);

//Get session list
$session_list = SessionManager::get_sessions_list(array(), array('name'));
$my_session_list = array();
foreach($session_list as $sesion_item) {
	$my_session_list[$sesion_item['id']] = $sesion_item['name'];
}
if (count($session_list) == 0) {
	$my_session_list[0] = get_lang('None');
}
$form->addElement('select', 'session_id', get_lang('Sessions'), $my_session_list);
$form->addElement('style_submit_button','submit',get_lang('Filter'));


if (!empty($_REQUEST['score']))	$filter_score = intval($_REQUEST['score']); else $filter_score = 70;
if (!empty($_REQUEST['session_id']))	$session_id = intval($_REQUEST['session_id']); else $session_id = 0;

if (empty($session_id)) {
	$session_id = key($my_session_list);
}
$form->setDefaults(array('session_id'=>$session_id));
$course_list = SessionManager::get_course_list_by_session_id($session_id);

if (!$export_to_xls) {
	Display :: display_header(get_lang("MySpace"));
	echo '<div class="actions" style ="font-size:10pt;" >';	
    
	if ($global) {	
		
		$menu_items[] = Display::url(Display::return_icon('stats.png', get_lang('MyStats'),'',32),api_get_path(WEB_CODE_PATH)."auth/my_progress.php" );
		$menu_items[] = Display::url(Display::return_icon('teacher.png', get_lang('TeacherInterface'), array(), 32), api_get_path(WEB_CODE_PATH).'mySpace/?view=teacher');
		$menu_items[] = Display::return_icon('star_na.png', get_lang('AdminInterface'), array(), 32);
		$menu_items[] = Display::url(Display::return_icon('quiz.png', get_lang('ExamTracking'), array(), 32), api_get_path(WEB_CODE_PATH).'tracking/exams.php');
		
		$nb_menu_items = count($menu_items);
		if($nb_menu_items>1) {
			foreach($menu_items as $key=> $item) {
				echo $item;
			}
			echo '<br />';
		}
	} else {
        
		echo '<div style="float:left; clear:left">
				<a href="courseLog.php?'.api_get_cidreq().'&studentlist=true">'.get_lang('StudentsTracking').'</a>&nbsp;|			
				<a href="courseLog.php?'.api_get_cidreq().'&studentlist=false">'.get_lang('CourseTracking').'</a>&nbsp;';		
		echo '</div>';	
	}
	echo '</div>';
	
	if (api_is_platform_admin()) {
		echo '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace/?view=admin&amp;display=coaches">'.get_lang('DisplayCoaches').'</a> | ';
		echo '<a href="'.api_get_path(WEB_CODE_PATH).'mySpace/?view=admin&amp;display=useroverview">'.get_lang('DisplayUserOverview').'</a>';		
		echo ' | <a href="'.api_get_path(WEB_CODE_PATH).'mySpace/?view=admin&amp;display=sessionoverview">'.get_lang('DisplaySessionOverview').'</a>';
		echo ' | <a href="'.api_get_path(WEB_CODE_PATH).'mySpace/?view=admin&amp;display=courseoverview">'.get_lang('DisplayCourseOverview').'</a>';	
		echo ' | <a href="'.api_get_path(WEB_CODE_PATH).'tracking/question_course_report.php?view=admin">'.get_lang('LPQuestionListResults').'</a>';
			
		echo ' | '.get_lang('LPExerciseResultsBySession').'';	
			
				
	}	
	
	echo '<h2>'.get_lang('LPExerciseResultsBySession').'</h2>';
	$form->display();
	Display::display_normal_message(get_lang('StudentScoreAverageIsCalculatedBaseInAllLPsAndAllAttempts'));
	
		
	//echo '<h3>'.sprintf(get_lang('FilteringWithScoreX'), $filter_score).'%</h3>';
	
//	echo '<a href="'.api_get_self().'?export=1&score='.$filter_score.'&exercise_id='.$exercise_id.'"><img align="absbottom" src="../img/excel.gif">&nbsp;'.get_lang('ExportAsXLS').'</a><br /><br />';
}

	
$users = SessionManager::get_users_by_session($session_id);
$course_average = $course_average_counter = array();

$counter = 0;
$main_result = array();
//Getting course list
foreach ($course_list  as $current_course ) {
	$course_info = api_get_course_info($current_course['code']);
	$_course = $course_info; 
	$attempt_result = array();
	
	//Getting LP list
	$list = new learnpathList('', $current_course['code'], $session_id);
	$lp_list = $list->get_flat_list();
		
	// Looping LPs
	foreach ($lp_list as $lp_id =>$lp) {		
		$exercise_list = get_all_exercises_from_lp($lp_id, $course_info['real_id']);		
		//Looping Chamilo Exercises in LP
		foreach ($exercise_list as $exercise) {
			$exercise_stats = get_all_exercise_event_from_lp($exercise['path'], $course_info['id'], $session_id);
			//Looping Exercise Attempts
			foreach($exercise_stats as $stats) {
				$attempt_result[$stats['exe_user_id']]['result'] += $stats['exe_result'] / $stats['exe_weighting'];		
				$attempt_result[$stats['exe_user_id']]['attempts']++;								
			}			
		}		
	}
	$main_result[$current_course['code']] = $attempt_result;
}

//var_dump($main_result);
$total_average_score = 0;
$total_average_score_count = 0;
if (!empty($users) && is_array($users)) {
	
	$html_result .= '<table  class="data_table">';
	$html_result .= '<tr><th>'.get_lang('User').'</th>';
	foreach($course_list as $item ) {		
		$html_result .= '<th>'.$item['title'].'<br /> '.get_lang('AverageScore').' %</th>';	
	}
	$html_result .= '<th>'.get_lang('AverageScore').' %</th>';
	$html_result .= '<th>'.get_lang('LastConnexionDate').'</th></tr>';	
	
	foreach ($users  as $user) {
		$total_student = 0;
		$counter ++;
		$s_css_class = 'row_even';
		if ($counter % 2 ==0 ) {
			$s_css_class = 'row_odd';
		}
		$html_result .= "<tr class='$s_css_class'>
							<td >";
		$html_result .= $user['firstname'].' '.$user['lastname'];
		$html_result .= "</td>";	
		
		//Getting course list
		
		$counter = 0;
		$total_result_by_user = 0;
		foreach($course_list  as $current_course ) {
			$total_course = 0;			
			$user_info_stat = $main_result[$current_course['code']][$user['user_id']];
			$html_result .= "<td align=\"center\" >";
			if (!empty($user_info_stat['result']) && !empty($user_info_stat['attempts'])) {
				$result =round($user_info_stat['result']/$user_info_stat['attempts'] * 100, 2);
				$total_course +=$result;
				$total_result_by_user +=$result;		
				$course_average[$current_course['code']] += $total_course;
				$course_average_counter[$current_course['code']]++;				
				$result = $result .' ('.$user_info_stat['attempts'].' '.get_lang('Attempts').')';
				$counter++;
			} else {
				$result  = '-';
			}
			$html_result .= $result;
			$html_result .= "</td>";
		}		
		if (empty($counter)) {
			$total_student = '-';
		} else {			
			$total_student = $total_result_by_user/$counter;
			$total_average_score+=$total_student;
			$total_average_score_count++;
		}		
		$string_date=Tracking :: get_last_connection_date($user['user_id'],true);
		$html_result .="<td  align=\"center\">$total_student</td><td>$string_date</td></tr>";		
	}
	
	$html_result .="<tr><th>".get_lang('AverageScore')."</th>";
	$total_average = 0;
	$counter = 0;
	foreach($course_list as $course_item) {
		if (!empty($course_average_counter[$course_item['code']])) {
			$average_per_course = round($course_average[$course_item['code']]/($course_average_counter[$course_item['code']]*100)*100,2);
		} else {
			$average_per_course = '-';
		}
		if (!empty($average_per_course)) {
			$counter++;		
		}
		$total_average = $total_average + $average_per_course;
		$html_result .="<td align=\"center\">$average_per_course</td>";
	}	
	if (!empty($total_average_score_count)) {
		$total_average = round($total_average_score/($total_average_score_count*100)*100,2);
	} else {
		$total_average = '-';
	}
			
	$html_result .='<td align="center">'.$total_average.'</td>';
	$html_result .="<td>-</td>";
	$html_result .="</tr>";
	$html_result .= '</table>';
} else {	
	Display::display_warning_message(get_lang('NoResults'));
}


if (!$export_to_xls) {
	echo $html_result;	
}

$filename = 'exam-reporting-'.date('Y-m-d-h:i:s').'.xls';
if ($export_to_xls) {
	echo $html_result;
	export_complete_report_xls($filename, $export_array);
	exit;
}

function sort_user($a, $b) {
	if (is_numeric($a['score']) && is_numeric($b['score'])) {
		echo $a['score'].' : '.$b['score'];
		echo '<br />';
		if ($a['score'] < $b['score']) {
			return 1;
		}
		return 0;
	}
	return 1;	
}

function export_complete_report_xls($filename, $array) {
		global $charset;
		$workbook = new Spreadsheet_Excel_Writer();
		$workbook ->setTempDir(api_get_path(SYS_ARCHIVE_PATH));
		$workbook->send($filename);
		$workbook->setVersion(8); // BIFF8
		$worksheet =& $workbook->addWorksheet('Report');
		//$worksheet->setInputEncoding(api_get_system_encoding());
		$worksheet->setInputEncoding($charset);
		/*
		$line = 0;
		$column = 1; // Skip the first column (row titles)
		foreach ($array as $elem) {
			$worksheet->write($line, $column, $elem);
			$column++;
		}
		$workbook->close();*/
		exit;
}

Display :: display_footer();
