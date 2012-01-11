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
require_once api_get_path(LIBRARY_PATH).'pchart/pData.class.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pChart.class.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pCache.class.php';



/**
 * This class is used like controller for teacher graph block plugin, 
 * the class name must be registered inside path.info file (e.g: controller = "BlockTeacherGraph"), so dashboard controller will be instantiate it
 * @package chamilo.dashboard
 */
class BlockTeacherGraph extends Block {

    private $user_id;
    private $teachers;
    private $path;
    private $permission = array(DRH);

	/**
	 * Controller
	 */
    public function __construct ($user_id) {    	
    	$this->user_id  = $user_id;
    	$this->path 	= 'block_teacher_graph';
    	if ($this->is_block_visible_for_user($user_id)) {
    		/*if (api_is_platform_admin()) {
	    		$this->teachers = UserManager::get_user_list(array('status' => COURSEMANAGER));
	    	} else {*/
	    		$this->teachers = UserManager::get_users_followed_by_drh($user_id, COURSEMANAGER);
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
			                	<div style="padding:10px;"><strong>'.get_lang('TimeSpentOnThePlatformLastWeekByDay').'</strong></div>
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

 		$user_ids = array_keys($teachers); 		
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
			$days_on_week = array();			
			foreach ($a_last_week as $weekday) {
				$days_on_week[] = date('d/m',$weekday);
			}
					
			$data_set->AddPoint($days_on_week,"Days");						
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
				$bg_width = 440;
				$bg_height = 350;
				$test = new pChart($bg_width+10,$bg_height+20);  
				$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);  
				$test->setGraphArea(65,30,$bg_width-70,$bg_height-50);  
				$test->drawFilledRoundedRectangle(7,7,$bg_width,$bg_height,5,240,240,240);  
				$test->drawRoundedRectangle(5,5,$bg_width+2,$bg_height+2,5,230,230,230);  
				$test->drawGraphArea(255,255,255,TRUE);  
				$test->drawScale($data_set->GetData(),$data_set->GetDataDescription(),SCALE_NORMAL,150,150,150,TRUE,0,2,TRUE);  
				$test->drawGrid(4,TRUE,230,230,230,50);  
				
				// Drawing lines
				//$test->drawLineGraph($data_set->GetData(),$data_set->GetDataDescription());
				$test->drawFilledCubicCurve($data_set->GetData(),$data_set->GetDataDescription(),.1,30);
				//$test->drawPlotGraph($data_set->GetData(),$data_set->GetDataDescription(),3,2,255,255,255);
				
				// Drawing Legend  			 
				$test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);  
				$test->drawLegend($bg_width-80,20,$data_set->GetDataDescription(),204,204,255);  
								 
				$test->writeValues($data_set->GetData(),$data_set->GetDataDescription(),array("Days")); 
								 
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