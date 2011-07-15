<?php
/* For licensing terms, see /license.txt */

/**
 * This file is part of student graph block plugin for dashboard, 
 * it should be required inside dashboard controller for showing it into dashboard interface from plattform
 * @package chamilo.dashboard
 * @author Christian Fasanando
 * @author Julio Montoya <gugli100@gmail.com> 
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
require_once api_get_path(LIBRARY_PATH).'pchart/MyHorBar.class.php';
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
	private $permission = array(DRH);

	/**
	 * Constructor
	 */	
    public function __construct ($user_id) {    	
    	$this->user_id  = $user_id;
    	$this->path 	= 'block_student_graph';   
    	if ($this->is_block_visible_for_user($user_id)) {
    		/*if (api_is_platform_admin()) {
	    		$this->students = UserManager::get_user_list(array('status' => STUDENT));
	    	} else if (api_is_drh()) {*/
	    		$this->students =  UserManager::get_users_followed_by_drh($user_id, STUDENT);
	    	//}	
    	} 	    	    	  	
    }
    
    /**
	 * This method check if a user is allowed to see the block inside dashboard interface
	 * @param	int		User id
	 * @return	bool	Is block visible for user
	 */    
    public function is_block_visible_for_user($user_id) {	
    	$user_info = api_get_user_info($user_id);
		$user_status = $user_info['status'];
		$is_block_visible_for_user = false;
    	if (UserManager::is_admin($user_id) || in_array($user_status, $this->permission)) {
    		$is_block_visible_for_user = true;
    	}    	
    	return $is_block_visible_for_user;    	
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
		$students_attendance_graph = $this->get_students_attendance_graph();
		
		$html = '<li class="widget color-orange" id="intro">
	                <div class="widget-head">
	                    <h3>'.get_lang('StudentsInformationsGraph').'</h3>
	                    <div class="widget-actions"><a onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">'.Display::return_icon('close.gif',get_lang('Close')).'</a></div>
	                </div>			
	                <div class="widget-content" align="center">	
	                	<div style="padding:10px;"><strong>'.get_lang('AttendancesFaults').'</strong></div>		                	
						'.$students_attendance_graph.'
	                </div>
	            </li>';    	
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
 		if (is_array($students) && count($students) > 0) {
 			
	 		foreach ($students as $student) {	 			
	 			$student_id = $student['user_id'];
	 			//$student_info = api_get_user_info($student_id); 			 			
				// get average of faults in attendances by student	 			
	 			$results_faults_avg = $attendance->get_faults_average_inside_courses($student_id);
	 				 				
	 			if (!empty($results_faults_avg)) {
	 				$attendances_faults_avg[$student['lastname']] = $results_faults_avg['porcent'];	 				
	 			} else {
	 				$attendances_faults_avg[$student['lastname']] = 0;
	 			} 			
	 		}
 		}
 			
 		arsort($attendances_faults_avg); 
		$usernames = array_keys($attendances_faults_avg);		
		
		$faults = array();		
		foreach ($usernames as $username) {
			$faults[] = $attendances_faults_avg[$username];
		}
		
		$graph = '';
		$img_file = '';

		if (is_array($usernames) && count($usernames) > 0) {
			
			// Defining data
			$data_set = new pData;
								
			$data_set->AddPoint($faults,"Promedio");   				
			$data_set->AddPoint($usernames,"Usuario"); 							
			$data_set->AddAllSeries(); 				   										
			$data_set->SetAbsciseLabelSerie("Usuario");

			// prepare cache for saving image
			$graph_id = $this->user_id.'StudentEvaluationGraph';  	// the graph id			 
			$cache = new pCache();	
					
			$data = $data_set->GetData();	// return $this->DataDescription
			
			if ($cache->IsInCache($graph_id, $data_set->GetData())) {
			//if (0) {
				//if we already created the img
				$img_file = $cache->GetHash($graph_id, $data_set->GetData());  // image file with hash
			} else {					
				
				if (count($usernames) < 5) {					
					$height = 200;						
				} else {				
					$height = (count($usernames)*40); 
				}
																																															
				// Initialise the graph
				$test = new MyHorBar(400,($height+30));
				$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf', 8);
				$test->setGraphArea(100,30,370,$height);
				
				$test->drawFilledRoundedRectangle(7,7,393,$height,5,240,240,240);
				$test->drawRoundedRectangle(5,5,395,$height,5,230,230,230);
				$test->drawGraphArea(255,255,255,TRUE);
				
				//X axis
				$test->setFixedScale(0,100,10);
				//var_dump($data_set->GetDataDescription());
				$test->drawHorScale($data_set->GetData(),$data_set->GetDataDescription(),SCALE_ADDALL,150,150,150,TRUE,0,0,TRUE);				
				$test->setColorPalette(0,255,0,0);				
				$test->drawHorGrid(10,TRUE,230,230,230,50);
				
				// Draw the 0 line
				$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf', 6);
				$test->drawTreshold(0,143,55,72,TRUE,TRUE);

				// Draw the bar graph
				$test->drawHorBarGraph($data_set->GetData(),$data_set->GetDataDescription(),TRUE,50);
				
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