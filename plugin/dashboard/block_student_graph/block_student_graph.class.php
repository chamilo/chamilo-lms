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
			$students_attendance_graph = $this->get_students_attendance_graph();
		//}
		
		$html = '        		
			            <li class="widget color-orange" id="intro">
			                <div class="widget-head">
			                    <h3>'.get_lang('StudentsInformationsGraph').'</h3>
			                    <div class="widget-actions"><a onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">'.Display::return_icon('close.gif',get_lang('Close')).'</a></div>
			                </div>			
			                <div class="widget-content" align="center">			                	
								'.$students_attendance_graph.'
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
    public function get_students_attendance_graph() {
 			
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
			
			
			$data_set->AddPoint($faults,"Promedio");   				// $this->Data = array(0=>array('Promedio'=>57,'Name'=>0), 1=>array('Promedio'=>43,'Name'=>1), 2=>array('Promedio'=>29,'Name'=>2))
			$data_set->AddPoint($usernames,"Usuario"); 				// $this->Data = array(0=>array('Usuario'=>'alumno3','Name'=>0), 1=>array('Usuario'=>'alumno1','Name'=>1), 2=>array('Usuario'=>'alumno2','Name'=>2))
			
			$data_set->AddAllSeries(); 				   				// $this->DataDescription = array('Position'=>'Name', 'Format'=>array('X'=>'number','Y'=>'number'), 'Unit'=>array('X'=>null,'Y'=null),'Values'=>array(0=>'Promedio',1=>'Usuario'))
			
			$data_set->SetXAxisName(get_lang('UserName'));			// $this->DataDescription["Axis"]["X"] = 'UserName'; 			
			$data_set->SetYAxisName(get_lang('AttendancesFaults')); // $this->DataDescription["Axis"]["Y"] = 'AttendancesFaults'; 
			
			$data_set->SetAbsciseLabelSerie("Usuario");   			// $this->DataDescription["Position"] = "Usuario";
			
			
			
			
			// prepare cache for saving image
			$graph_id = $this->user_id.'StudentEvaluationGraph';  	// the graph id			 
			$cache = new pCache();	
					
			$data = $data_set->GetData();	// return $this->DataDescription
			
			if ($cache->IsInCache($graph_id, $data_set->GetData())) {			
				//if we already created the img
				$img_file = $cache->GetHash($graph_id, $data_set->GetData());  // image file with hash
			} else {
																
																
				// Initializing the graph  
				$test = new pChart(365,300);    // Create transparent image 365x300
				
				// $this->FontName = api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf'
				// $this->FontSize = 8
				$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);

				
				
				 $X1 = 50; 
				 $Y1 = 30;
				 $X2 = 345;
				 $Y2 = 200;
				 				 
				//$this->GArea_X1 = $X1;$this->GArea_Y1 = $Y1;$this->GArea_X2 = $X2;$this->GArea_Y2 = $Y2; 
				$test->setGraphArea($X1,$Y1,$X2,$Y2);  
				
				
				$test->drawFilledRoundedRectangle(7,7,371,240,5,240,240,240);  
				
				$test->drawRoundedRectangle(5,5,373,225,5,230,230,230);  
				
				$test->drawGraphArea(255,255,255,TRUE);  				 
				
				
				$test->setFixedScale(0,100,5);				 
								
				$test->drawScale($data_set->GetData(),$data_set->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,10,TRUE);
				
				     
				$test->drawGrid(4,TRUE,230,230,230,50);
				
				// Drawing bars
				//$test->drawBarGraph($data_set->GetData(),$data_set->GetDataDescription(),TRUE);  
				//$test->drawLimitsGraph($data_set->GetData(),$data_set->GetDataDescription(),240,240,240);
				//$test->drawOverlayBarGraph($data_set->GetData(),$data_set->GetDataDescription());
				
				$test->drawHorizontalBarGraph($data_set->GetData(),$data_set->GetDataDescription(),TRUE);
				
				  			  
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