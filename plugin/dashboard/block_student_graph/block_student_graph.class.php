<?php
/**
 * This file is part of student graph block plugin for dashboard, 
 * it should be required inside dashboard controller for showing it into dashboard interface from plattform
 * @package chamilo.dashboard
 * @author Christian Fasanando
 */

/**
 * required files for getting data
 */
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'attendance.lib.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pData.class.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pChart.class.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pCache.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/gradebookitem.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evaluation.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/result.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/linkfactory.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/category.class.php';

/**
 * This class is used like controller for student graph block plugin, 
 * the class name must be registered inside path.info file (e.g: controller = "BlockStudentGraph"), so dashboard controller will be instantiate it
 * @package chamilo.dashboard
 */
class BlockStudentGraph extends Block {

    private $user_id;
	private $students;
	private $path;

	/**
	 * Constructor
	 */	
    public function __construct ($user_id) {    	
    	$this->user_id  = $user_id;    	
    	if (api_is_platform_admin()) {
    		$this->students = UserManager::get_user_list(array('status' => STUDENT));
    	} else if (api_is_drh()) {
    		$this->students =  UserManager::get_users_followed_by_drh($user_id, STUDENT);
    	}
    	$this->path 	= 'block_student_graph';    	  	
    }
    
    /**
     * This method return content html containing information about students and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from dashboard controller 
     * @return array   column and content html
     */
    public function get_block() {
    	
    	global $charset;
    	    	
    	$column = 1;
    	$data   = array();

		/*
		if (api_is_platform_admin()) {
			$student_content_html = $this->get_students_content_html_for_platform_admin();
		} else if (api_is_drh()) {*/
			$students_evaluation_graph = $this->get_students_evaluation_graph();
		//}
		
		$html = '        		
			            <li class="widget color-orange" id="intro">
			                <div class="widget-head">
			                    <h3>'.get_lang('StudentsInformationsGraph').'</h3>
			                    <div class="widget-actions"><a onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">'.Display::return_icon('close.gif',get_lang('Close')).'</a></div>
			                </div>			
			                <div class="widget-content" align="center">			                	
								'.$students_evaluation_graph.'
			                </div>
			            </li>			                        			    
				'; 
    	
    	$data['column'] = $column;
    	$data['content_html'] = $html;
    	    	    	    	
    	return $data;    	    	
    	
    }
    
    /**
 	 * This method return a graph containing informations about students evaluation, it's used inside get_block method for showing it inside dashboard interface
 	 * @return string  img html
 	 */
    public function get_students_evaluation_graph() {
 			
 		$students = $this->students;
 		$attendance = new Attendance();
 		
 		// get data 		
 		$attendances_faults_avg = array();
 		
 		foreach ($students as $student) {	 			
 			$student_id = $student['user_id'];
 			$student_info = api_get_user_info($student_id); 			 			
			// get average of faults in attendances by student	 			
 			$results_faults_avg = $attendance->get_faults_average_inside_courses($student_id);	 	
 			if (!empty($results_faults_avg)) {
 				$attendances_faults_avg[$student_info['username']] = $results_faults_avg['porcent'];	 				
 			} else {
 				$attendances_faults_avg[$student_info['username']] = 0;
 			} 			
 		}
 		 		
 		arsort($attendances_faults_avg);		
		$usernames = array_keys($attendances_faults_avg);		

		// get only until five users
		if (count($usernames) > 5) { array_splice($usernames,5); }
				
		$faults = array();		
		foreach ($usernames as $username) {
			$faults[] = $attendances_faults_avg[$username];
		}

		$graph = '';
		$img_file = '';

		if (is_array($usernames) && count($usernames) > 0) {
			// Defining data   
			$data_set = new pData;  
			$data_set->AddPoint($usernames,"Usuario");  
			$data_set->AddPoint($faults,"Promedio");  
			$data_set->AddAllSeries();
			$data_set->SetXAxisName(get_lang('UserName'));
			$data_set->SetYAxisName(get_lang('AttendancesFaults'));  
			$data_set->SetAbsciseLabelSerie("Usuario");
			$graph_id = $this->user_id.'StudentEvaluationGraph';
			 
			$cache = new pCache();
			// the graph id
			$data = $data_set->GetData();
	    
			if ($cache->IsInCache($graph_id, $data_set->GetData())) {			
				//if we already created the img
				$img_file = $cache->GetHash($graph_id, $data_set->GetData());
			} else {
				
				// Initializing the graph  
				$test = new pChart(365,250);
				$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);  
				$test->setGraphArea(50,30,345,200);  
				$test->drawFilledRoundedRectangle(7,7,371,240,5,240,240,240);  
				$test->drawRoundedRectangle(5,5,373,225,5,230,230,230);  
				$test->drawGraphArea(255,255,255,TRUE);  				 
				$test->setFixedScale(0,100,5);				 
				$test->drawScale($data_set->GetData(),$data_set->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,10,TRUE);     
				$test->drawGrid(4,TRUE,230,230,230,50);
				
				// Drawing bars
				$test->drawBarGraph($data_set->GetData(),$data_set->GetDataDescription(),TRUE);  
				  			  
				// Drawing title
				$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',10);  
				$test->drawTitle(50,22,get_lang('AttendancesFaults'),50,50,50,385);  
				
				$test->writeValues($data_set->GetData(),$data_set->GetDataDescription(),"Promedio");
					 
				$cache->WriteToCache($graph_id, $data_set->GetData(), $test);
				ob_start();
				$test->Stroke();
				ob_end_clean();
				$img_file = $cache->GetHash($graph_id, $data_set->GetData()); 	
						
			} 		
	
			if (!empty($img_file)) {
				$graph = '<img src="'.api_get_path(WEB_ARCHIVE_PATH).$img_file.'">';
			}  
		} else {
			$graph = '<p>'.api_convert_encoding(get_lang('GraphicNotAvailable'),'UTF-8').'</p>';
		}
		
 		return $graph;
 	}
  
    /**
	 * Get number of students
	 * @return int
	 */
	function get_number_of_students() {
		return count($this->students);
	}
    
}

?>