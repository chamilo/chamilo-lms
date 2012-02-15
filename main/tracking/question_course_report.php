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

require_once api_get_path(SYS_CODE_PATH).'exercice/exercise.class.php';
require_once api_get_path(SYS_CODE_PATH).'exercice/question.class.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'pear/Spreadsheet_Excel_Writer/Writer.php';

require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpath.class.php';
require_once api_get_path(SYS_CODE_PATH).'newscorm/learnpathList.class.php';

$this_section = "session_my_space";

$is_allowedToTrack = $is_courseAdmin || $is_platformAdmin || $is_courseCoach || $is_sessionAdmin;

if(!$is_allowedToTrack) {
	Display :: display_header(null);
	api_not_allowed();
	Display :: display_footer();
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

$course_list = $course_select_list = array();
$course_select_list[0] = get_lang('None');

$htmlHeadXtra[] = '
<script type="text/javascript">
function load_courses() {	
	document.search_simple.submit();
}
</script>	';

$session_id = intval($_REQUEST['session_id']);

if (empty($session_id)) {
	$temp_course_list = CourseManager :: get_courses_list();
} else {
	$temp_course_list = SessionManager::get_course_list_by_session_id($session_id);	
}
	
foreach($temp_course_list  as $temp_course_item) {
	$course_item = CourseManager ::get_course_information($temp_course_item['code']);    		
	$course_list[]= array('db_name' =>$course_item['db_name'],'code'=>$course_item['code'], 'title'=>$course_item['title'], 'visual_code'=>$course_item['visual_code']);
	$course_select_list[$temp_course_item['code']]	= $course_item['title'];
}

//Get session list
$session_list = SessionManager::get_sessions_list(array(), array('name'));

$my_session_list = array();
$my_session_list[0] = get_lang('None');
foreach($session_list as $sesion_item) {
	$my_session_list[$sesion_item['id']] = $sesion_item['name'];
}

$form = new FormValidator('search_simple','POST','','',null,false);
$form->addElement('select', 'session_id', get_lang('Sessions'), $my_session_list, array('id'=>'session_id', 'onchange'=>'load_courses();'));
$form->addElement('select', 'course_code',get_lang('Courses'), $course_select_list);
$form->addElement('style_submit_button','submit_form', get_lang('Filter'));

if (!empty($_REQUEST['course_code']))	$course_code = $_REQUEST['course_code']; else $course_code = '';
if (empty($course_code)) {
	$course_code = 0;
}

$form->setDefaults(array('course_code'=>(string)$course_code));
$course_info = api_get_course_info($course_code);
//var_dump($session_id);
if (!empty($course_info)) {	
	$list = new learnpathList('', $course_code);
	$lp_list = $list->get_flat_list();	
	$_course = $course_info;	
	$main_question_list = array();
	foreach ($lp_list as $lp_id =>$lp) {
		$exercise_list = get_all_exercises_from_lp($lp_id, $course_info['real_id']);	
        //var_dump($exercise_list);	
		foreach ($exercise_list as $exercise) {		
			$my_exercise = new Exercise();			
			//$my_exercise->read($exercise['ref']);
			$my_exercise->read($exercise['path']);
			$question_list = $my_exercise->selectQuestionList();			
								
			$exercise_stats = get_all_exercise_event_from_lp($exercise['path'],$course_info['id'], $session_id);			
			//echo '<pre>'; print_r($exercise_stats);		
				
			foreach($question_list  as $question_id) {
				$question_data = Question::read($question_id);
                ///var_dump($question_data);
				$main_question_list[$question_id] = $question_data;		
				$quantity_exercises = 0;
				$question_result = 0;
                //echo '<pre>';
				//print_r($exercise_stats);
				foreach($exercise_stats as $stats) {
					if (!empty($stats['question_list'])) {
						foreach($stats['question_list'] as $my_question_stat) {
                           // var_dump($my_question_stat);							
							if ($question_id == $my_question_stat['question_id']) {				
								//var_dump($my_question_stat);		
								$question_result =  $question_result + $my_question_stat['marks'];		
					//			var_dump($my_question_stat['marks']);
								$quantity_exercises++;					
							}
						}
					}
				}
                //echo $question_id;
                //var_dump($question_result.' - '.$quantity_exercises.$main_question_list[$question_id]->weighting);
                if(!empty($quantity_exercises)) {
				    $main_question_list[$question_id]->results =(($question_result / ($quantity_exercises)) ) ; // Score % average
                } else {
                    $main_question_list[$question_id]->results = 0;
                }
				$main_question_list[$question_id]->quantity = $quantity_exercises;	
            
			}
		}
	}
}

//var_dump($main_question_list);

//var_dump($main_question_list);
//$course_list = SessionManager::get_course_list_by_session_id($session_id);

if (!$export_to_xls) {
	
	Display :: display_header(get_lang("MySpace"));
	echo '<div class="actions" style ="font-size:10pt;" >';
	if ($global) {
				
		$menu_items[] = Display::url(Display::return_icon('stats.png', get_lang('MyStats'),'',ICON_SIZE_MEDIUM),api_get_path(WEB_CODE_PATH)."auth/my_progress.php" );
		$menu_items[] = Display::url(Display::return_icon('teacher.png', get_lang('TeacherInterface'), array(), 32), api_get_path(WEB_CODE_PATH).'mySpace/?view=teacher');
		$menu_items[] = Display::return_icon('star_na.png', get_lang('AdminInterface'), array(), 32);		
		$menu_items[] = Display::url(Display::return_icon('quiz.png', get_lang('ExamTracking'), array(), 32), api_get_path(WEB_CODE_PATH).'tracking/exams.php');
		
		$nb_menu_items = count($menu_items);
		if($nb_menu_items>1) {
			foreach($menu_items as $key=> $item) {
				echo $item;			
			}			
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
		echo ' | '.get_lang('LPQuestionListResults');
		echo ' | <a href="'.api_get_path(WEB_CODE_PATH).'tracking/course_session_report.php?view=admin">'.get_lang('LPExerciseResultsBySession').'</a>';	
				
	}		
	echo '<br />';
	echo '<h2>'.get_lang('LPQuestionListResults').'</h2>';
	
	$form->display();
	//Display::display_normal_message(get_lang('QuestionsAreTakenFromLPExercises'));
	
	if (empty($course_code)) {
		Display::display_warning_message(get_lang('PleaseSelectACourse'));	
	}	
}

$course_average = array();

$counter = 0;

if (!empty($main_question_list) && is_array($main_question_list)) {
	$html_result .= '<table  class="data_table">';
	$html_result .= '<tr><th>'.get_lang('Question').Display :: return_icon('info3.gif', get_lang('QuestionsAreTakenFromLPExercises'), array('align' => 'absmiddle', 'hspace' => '3px')).'</th>';	
	$html_result .= '<th>'.$course_info['visual_code'].' '.get_lang('AverageScore').Display :: return_icon('info3.gif', get_lang('AllStudentsAttemptsAreConsidered'), array('align' => 'absmiddle', 'hspace' => '3px')).' </th>';
    $html_result .= '<th>'.get_lang('Quantity').'</th>';	
	
	foreach($main_question_list  as $question) {
		$total_student = 0;
		$counter ++;
		$s_css_class = 'row_even';
		if ($counter % 2 ==0 ) {
			$s_css_class = 'row_odd';
		}
		$html_result .= "<tr class='$s_css_class'>
							<td >";
		$question_title = trim($question->question);
		if (empty($question_title)) {			
			$html_result .= get_lang('Untitled').' '.get_lang('Question').' #'.$question->id;
		} else {				
			$html_result .= $question->question;
		}
		
		$html_result .= "</td>";
		
		$html_result .= "<td align=\"center\" >";						
		$html_result .= round($question->results, 2).' / '.$question->weighting;
		$html_result .= "</td>";
        
        $html_result .= "<td align=\"center\" >";                       
        $html_result .= $question->quantity;
        $html_result .= "</td>";
        
	}
	$html_result .="</tr>";
	$html_result .= '</table>';
} else {	
	if (!empty($course_code)) {
		Display::display_warning_message(get_lang('NoResults'));
	}
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
