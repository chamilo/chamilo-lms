<?php
/**
 * This file is part of teacher graph block plugin for dashboard, 
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

/**
 * This class is used like controller for teacher graph block plugin, 
 * the class name must be registered inside path.info file (e.g: controller = "BlockTeacherGraph"), so dashboard controller will be instantiate it
 * @package chamilo.dashboard
 */
class BlockTeacherGraph extends Block {

    private $user_id;
    private $teachers;
    private $path;

	/**
	 * Controller
	 */
    public function __construct ($user_id) {    	
    	$this->user_id  = $user_id;
    	if (api_is_platform_admin()) {
    		$this->teachers = UserManager::get_user_list(array('status' => COURSEMANAGER));
    	} else if (api_is_drh()) {
    		$this->teachers = UserManager::get_users_followed_by_drh($user_id, COURSEMANAGER);
    	}     	    	
    	$this->path 	= 'block_teacher_graph';  	
    }
    
    /**
     * This method return content html containing information about teachers and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from dashboard controller 
     * @return array   column and content html
     */
    public function get_block() {
    	
    	global $charset;
    	    	
    	$column = 1;
    	$data   = array();
		
		/*if (api_is_platform_admin()) {
			$teacher_content_html = $this->get_teachers_content_html_for_platform_admin();
		} else if (api_is_drh()) {*/
			$teacher_information_graph = $this->get_teachers_information_graph();
		//}
		
		$html = '        		
			            <li class="widget color-blue" id="intro">
			                <div class="widget-head">
			                    <h3>'.get_lang('TeachersInformationsGraph').'</h3>
			                    <div class="widget-actions"><a onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">'.Display::return_icon('close.gif',get_lang('Close')).'</a></div>
			                </div>
			                <div class="widget-content" align="center">
								'.$teacher_information_graph.'
			                </div>
			            </li>		            			    
				'; 
    	
    	$data['column'] = $column;
    	$data['content_html'] = $html;
    	    	    	    	
    	return $data;    	    	
    	
    }
    
    /**
 	 * This method return a content html, it's used inside get_block method for showing it inside dashboard interface
 	 * @return string  content html
 	 */
    public function get_teachers_information_graph() {
 	
	 	$teachers = $this->teachers;
		$graph = '';

 		// get data
 		$time_on_the_platform = array(); 		
 		foreach ($teachers as $teacher) {	 			
 			$teacher_id = $teacher['user_id'];			
 			// get time on platform last week
 			$time_on_platform_last_week = Tracking :: get_time_spent_on_the_platform($teacher_id,true);	 				
 			if (!empty($time_on_platform_last_week)) {
 				$time_on_the_platform[$teacher_id] = $time_on_platform_last_week;	 				
 			} else {
 				$time_on_the_platform[$teacher_id] = 0;
 			} 			
 		}
 		
 		arsort($time_on_the_platform);		
		// get only until five users
		if (count($time_on_the_platform) > 5) { array_splice($time_on_the_platform,5); }
 		
 		$user_ids = array_keys($time_on_the_platform); 		
 		$a_last_week = get_last_week();

		if (is_array($user_ids) && count($user_ids) > 0) {
			$data_set = new pData;  		
			foreach ($user_ids as $user_id) {
				$teacher_info = api_get_user_info($user_id); 
				$username = $teacher_info['username'];
				
				$time_by_days = array();
				foreach ($a_last_week as $day) {				
					$time_on_platform_by_day = Tracking::get_time_spent_on_the_platform($user_id, false, $day);
					$hours = floor($time_on_platform_by_day / 3600);			
					$min = floor(($time_on_platform_by_day - ($hours * 3600)) / 60);
					$time_by_days[] = $min;					
				}
	
				$data_set->AddPoint($time_by_days,$username);	
				$data_set->AddSerie($username);
				
			}
	
			$last_week 	 = date('Y-m-d',$a_last_week[0]).' '.get_lang('To').' '.date('Y-m-d', $a_last_week[6]);
			foreach ($a_last_week as &$weekday) {
				$weekday = date('d/m',$weekday);
			}
					
			$data_set->AddPoint($a_last_week,"Days");						
			$data_set->SetXAxisName($last_week);
			$data_set->SetYAxisName(get_lang('Minutes'));
							
			$data_set->SetAbsciseLabelSerie("Days");
			$graph_id = $this->user_id.'TeacherConnectionsGraph';
			 
			$cache = new pCache();
			// the graph id
			$data = $data_set->GetData();
	    
			if ($cache->IsInCache($graph_id, $data_set->GetData())) {			
				//if we already created the img
				$img_file = $cache->GetHash($graph_id, $data_set->GetData());
			} else {
				
				// Initializing the graph			      
				$test = new pChart(400,280);  
				$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);  
				$test->setGraphArea(65,30,350,200);  
				$test->drawFilledRoundedRectangle(7,7,393,253,5,240,240,240);  
				$test->drawRoundedRectangle(5,5,395,255,5,230,230,230);  
				$test->drawGraphArea(255,255,255,TRUE);  
				$test->drawScale($data_set->GetData(),$data_set->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2,TRUE);  
				$test->drawGrid(4,TRUE,230,230,230,50);  
				
				// Drawing lines
				$test->drawLineGraph($data_set->GetData(),$data_set->GetDataDescription());
				$test->drawPlotGraph($data_set->GetData(),$data_set->GetDataDescription(),3,2,255,255,255);
				
				// Drawing Legend  			 
				$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);  
				$test->drawLegend(320,20,$data_set->GetDataDescription(),204,204,255);  
				
				// Drawing title
				$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',10);  
				$test->drawTitle(50,22,get_lang('TimeSpentOnThePlatformLastWeekByDay'),50,50,50,385);
				 
				$test->writeValues($data_set->GetData(),$data_set->GetDataDescription(),"Days"); 
				 
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
	 * Get number of teachers 
	 * @return int
	 */
	function get_number_of_teachers() {
		return count($this->teachers);
	}
    
}

?>