<?php
/**
 * This file is part of evaluation graph block plugin for dashboard,
 * it should be required inside dashboard controller for showing it into dashboard interface from plattform
 * @package chamilo.dashboard
 * @author Christian Fasanando
 */

/**
 * required files for getting data
 */
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pData.class.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pChart.class.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pCache.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/gradebookitem.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evaluation.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/result.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/linkfactory.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/flatview_data_generator.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/category.class.php';


/**
 * This class is used like controller for this evaluations graph block plugin,
 * the class name must be registered inside path.info file (e.g: controller = "BlockEvaluationGraph"), so dashboard controller will be instantiate it
 * @package chamilo.dashboard
 */
class BlockEvaluationGraph extends Block {

    private $user_id;
	private $courses;
	private $sessions;
	private $path;
	private $permission = array(DRH, SESSIONADMIN);

	/**
	 * Constructor
	 */
    public function __construct ($user_id) {    	
    	$this->path = 'block_evaluation_graph';
    	$this->user_id 	= $user_id;
    	$this->bg_width = 450;
    	$this->bg_height = 350;
    	if ($this->is_block_visible_for_user($user_id)) {    		
    		/*if (api_is_platform_admin()) {
	    		$this->courses  = CourseManager::get_real_course_list();
	    		$this->sessions = SessionManager::get_sessions_list();
	    	} else {*/	    		
	    		if (!api_is_session_admin()) {
	    			$this->courses  = CourseManager::get_courses_followed_by_drh($user_id);			
	    		} 
	    		$this->sessions = SessionManager::get_sessions_followed_by_drh($user_id);
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
     * This method return content html containing information about sessions and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from dashboard controller
     * @return array   column and content html
     */
    public function get_block() {
	
		global $charset;
    	    	
    	$column = 1;
    	$data   = array();
		
		$evaluations_base_courses_graph = $this->get_evaluations_base_courses_graph();
		$evaluations_courses_in_sessions_graph = $this->get_evaluations_courses_in_sessions_graph();

		$html = '        		
			            <li class="widget color-orange" id="intro">
			                <div class="widget-head">
			                    <h3>'.get_lang('EvaluationsGraph').'</h3>
			                    <div class="widget-actions"><a onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">'.Display::return_icon('close.gif',get_lang('Close')).'</a></div>
			                </div>			
			                <div class="widget-content" align="center">';			                	
			                	if (empty($evaluations_base_courses_graph) && empty($evaluations_courses_in_sessions_graph)) {
			                		$html .= '<p>'.api_convert_encoding(get_lang('GraphicNotAvailable'),'UTF-8').'</p>';
			                	} else {			                		
			                		// display evaluations base courses graph
				                	if (!empty($evaluations_base_courses_graph)) {
										foreach ($evaluations_base_courses_graph as $course_code => $img_html) {
											$html .= '<div><strong>'.$course_code.'</strong></div>';
											$html .= $img_html;
										}
				                	}
				                	// display evaluations base courses graph
				                	if (!empty($evaluations_courses_in_sessions_graph)) {
										foreach ($evaluations_courses_in_sessions_graph as $session_id => $courses) {
											$session_name = api_get_session_name($session_id);
											$html .= '<div><strong>'.$session_name.':'.get_lang('Evaluations').'</strong></div>';										
											foreach ($courses as $course_code => $img_html) {
												$html .= '<div><strong>'.$course_code.'</strong></div>';
												$html .= $img_html;		
											}										
										}
				                	}			                		
			                	}
		$html .=        	'</div>
			            </li>			                        			    
				'; 
    	
    	$data['column'] = $column;
    	$data['content_html'] = $html;
    	
    	return $data;
		
	}

    /**
 	 * This method return a graph containing informations about evaluations inside base courses, it's used inside get_block method for showing it inside dashboard interface
 	 * @return string  img html
 	 */
    public function get_evaluations_base_courses_graph() {				
		$graphs = array();		
		if (!empty($this->courses)) {
			$courses_code = array_keys($this->courses);		
			foreach ($courses_code as $course_code) {						
				$cats = Category::load(null, null, $course_code, null, null, null, false);	
				if (isset($cats)) {
					$alleval = $cats[0]->get_evaluations(null, true, $course_code);
					$alllinks = $cats[0]->get_links(null, true);
					$users = get_all_users($alleval, $alllinks);				
					$datagen = new FlatViewDataGenerator ($users, $alleval, $alllinks);	
					$evaluation_sumary = $datagen->get_evaluation_sumary_results();					
					if (!empty($evaluation_sumary)) {
						$items = array_keys($evaluation_sumary);
						$max = $min = $avg = array();
						foreach ($evaluation_sumary as $evaluation) {
							$max[] = $evaluation['max'];
							$min[] = $evaluation['min'];
							$avg[] = $evaluation['avg'];
						}						
						// Dataset definition   
					    $data_set = new pData;  
					    $data_set->AddPoint($max, "Max");  
					    $data_set->AddPoint($avg, "Avg");
					    $data_set->AddPoint($min, "Min");  	    
					    $data_set->AddPoint($items, "Items");					    
					    $data_set->SetXAxisName(get_lang('EvaluationName'));
						$data_set->SetYAxisName(get_lang('Percentage'));				
						$data_set->AddAllSeries();  
					   	$data_set->RemoveSerie("Items");  
					   	$data_set->SetAbsciseLabelSerie("Items");  				
					    $graph_id = $this->user_id.'StudentEvaluationGraph';			 
						$cache = new pCache();
						// the graph id
						$data = $data_set->GetData();					
						if ($cache->IsInCache($graph_id, $data)) {			
							//if we already created the img
							$img_file = $cache->GetHash($graph_id, $data);
						} else {
							// Initialise the graph  
						    $test = new pChart($this->bg_width,$this->bg_height);  
						    $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);  
						    $test->setGraphArea(50,30,$this->bg_width-75,$this->bg_height-75);  
						    $test->drawFilledRoundedRectangle(7,7,$this->bg_width-20,$this->bg_height-20,5,240,240,240);  
						    $test->drawRoundedRectangle(5,5,$this->bg_width-18,$this->bg_height-18,5,230,230,230);  
						    $test->drawGraphArea(255,255,255,TRUE);  						    
						    $test->setFixedScale(0,100,5);							    
						    $test->drawScale($data_set->GetData(),$data_set->GetDataDescription(),SCALE_ADDALL,150,150,150,TRUE,0,2,TRUE);  						    
						    $test->setColorPalette(0,105,221,34);
							$test->setColorPalette(1,255,135,30);
							$test->setColorPalette(2,255,0,0);						    
						    $test->drawGrid(4,TRUE,230,230,230,50);  						     
						    // Draw the 0 line  
						    $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',6);  
						    $test->drawTreshold(0,143,55,72,TRUE,TRUE);  						     
						    // Draw the bar graph  
						    $test->drawOverlayBarGraph($data_set->GetData(),$data_set->GetDataDescription(), 90);						     
						    // Finish the graph  
						    $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);  
						    $test->drawLegend($this->bg_width-80,20,$data_set->GetDataDescription(),255,255,255);  
						    $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',10);  
						    //$test->drawTitle(50,22,$course_code,50,50,50,185);  					    
						    $test->setColorPalette(0,50,50,50);
							$test->setColorPalette(1,50,50,50);
							$test->setColorPalette(2,50,50,50);						    
						    $test->writeValues($data_set->GetData(),$data_set->GetDataDescription(),array("Min", "Max", "Avg"));					    		   		    
						    $cache->WriteToCache($graph_id, $data_set->GetData(), $test);
							ob_start();
							$test->Stroke();
							ob_end_clean();
							$img_file = $cache->GetHash($graph_id, $data_set->GetData());	
						}						
						if (!empty($img_file)) {
							$graphs[$course_code] = '<img src="'.api_get_path(WEB_ARCHIVE_PATH).$img_file.'">';
						} 					
					}
				}				
			} // end for
		}
    	return $graphs;		
 	}

	/**
 	 * This method return a graph containing informations about evaluations inside courses in sessions, it's used inside get_block method for showing it inside dashboard interface
 	 * @return string  img html
 	 */
    public function get_evaluations_courses_in_sessions_graph() {				
		$graphs = array();
		if (!empty($this->sessions)) {
			$session_ids = array_keys($this->sessions);
			foreach ($session_ids as $session_id) {
				$courses_code = array_keys(Tracking::get_courses_list_from_session($session_id));
				$courses_graph = array();
				foreach ($courses_code as $course_code) {
					$cats = Category::load(null, null, $course_code, null, null, $session_id);
					if (isset($cats)) {
						$alleval = $cats[0]->get_evaluations(null, true, $course_code);												
						$alllinks = $cats[0]->get_links(null, true);
						$users = get_all_users($alleval, $alllinks);
						$datagen = new FlatViewDataGenerator ($users, $alleval, $alllinks);
						$evaluation_sumary = $datagen->get_evaluation_sumary_results();
						if (!empty($evaluation_sumary)) {
							$items = array_keys($evaluation_sumary);
							$max = $min = $avg = array();
							foreach ($evaluation_sumary as $evaluation) {
								$max[] = $evaluation['max'];
								$min[] = $evaluation['min'];
								$avg[] = $evaluation['avg'];
							}							
							// Dataset definition   
						    $data_set = new pData;  
						    $data_set->AddPoint($max, "Max");  
						    $data_set->AddPoint($avg, "Avg");
						    $data_set->AddPoint($min, "Min");  	    
						    $data_set->AddPoint($items, "Items");						    
						    $data_set->SetXAxisName(get_lang('EvaluationName'));
							$data_set->SetYAxisName(get_lang('Percentage'));					
							$data_set->AddAllSeries();  
						   	$data_set->RemoveSerie("Items");  
						   	$data_set->SetAbsciseLabelSerie("Items");  					
						    $graph_id = $this->user_id.'StudentEvaluationGraph';			 
							$cache = new pCache();
							// the graph id
							$data = $data_set->GetData();						
							if ($cache->IsInCache($graph_id, $data)) {			
								//if we already created the img
								$img_file = $cache->GetHash($graph_id, $data);
							} else {
								// Initialise the graph  
							    $test = new pChart($this->bg_width,$this->bg_height);  
							    $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);  
							    $test->setGraphArea(50,30,$this->bg_width-75,$this->bg_height-75);  
							    $test->drawFilledRoundedRectangle(7,7,$this->bg_width-20,$this->bg_height-20,5,240,240,240);  
							    $test->drawRoundedRectangle(5,5,$this->bg_width-18,$this->bg_height-18,5,230,230,230);  
							    $test->drawGraphArea(255,255,255,TRUE);  							    
							    $test->setFixedScale(0,100,5);								    
							    $test->drawScale($data_set->GetData(),$data_set->GetDataDescription(),SCALE_ADDALL,150,150,150,TRUE,0,2,TRUE);  							    
							    $test->setColorPalette(0,105,221,34);
								$test->setColorPalette(1,255,135,30);
								$test->setColorPalette(2,255,0,0);						    
							    $test->drawGrid(4,TRUE,230,230,230,50);  							     
							    // Draw the 0 line  
							    $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',6);  
							    $test->drawTreshold(0,143,55,72,TRUE,TRUE);  							     
							    // Draw the bar graph  
							    $test->drawOverlayBarGraph($data_set->GetData(),$data_set->GetDataDescription(), 100);							     
							    // Finish the graph  
							    $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);  
							    $test->drawLegend($this->bg_width-80,20,$data_set->GetDataDescription(),255,255,255);  
							    $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',10);  
							    $test->setColorPalette(0,50,50,50);
								$test->setColorPalette(1,50,50,50);
								$test->setColorPalette(2,50,50,50);						    
							    $test->writeValues($data_set->GetData(),$data_set->GetDataDescription(),array("Min", "Max", "Avg"));					    		   		    
							    $cache->WriteToCache($graph_id, $data_set->GetData(), $test);
								ob_start();
								$test->Stroke();
								ob_end_clean();
								$img_file = $cache->GetHash($graph_id, $data_set->GetData());	
							}							
							if (!empty($img_file)) {
								$courses_graph[$course_code] = '<img src="'.api_get_path(WEB_ARCHIVE_PATH).$img_file.'">';
							} 					
						}
					}									
				}				
				if (!empty($courses_graph)) {
					$graphs[$session_id] = $courses_graph;	
				}												
			} 
		}					
    	return $graphs;		
 	}

}
?>