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
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'attendance.lib.php';
require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pData.class.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pChart.class.php';
require_once api_get_path(LIBRARY_PATH).'pchart/pCache.class.php';

require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/gradebookitem.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/evaluation.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/result.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/linkfactory.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/flatview_data_generator.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/scoredisplay.class.php';
require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/gradebook_functions.inc.php';

require_once api_get_path(SYS_CODE_PATH).'gradebook/lib/be/category.class.php';


/**
 * This class is used like controller for this evaluations graph block plugin,
 * the class name must be registered inside path.info file (e.g: controller = "BlockEvaluationGraph"), so dashboard controller will be instantiate it
 * @package chamilo.dashboard
 */
class BlockEvaluationGraph extends Block {

    private $user_id;
	private $sessions;
	private $path;

	/**
	 * Constructor
	 */
    public function __construct ($user_id) {
    	
    	/*
    	$this->user_id 	= $user_id;
    	if (api_is_platform_admin()) {
    		$this->sessions = SessionManager::get_sessions_list();
    	} else if (api_is_drh()) {
    		$this->sessions = SessionManager::get_sessions_followed_by_drh($user_id);	
    	}    	
    	$this->path = 'block_evaluation_graph';
    	*/
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

		/*
		if (api_is_platform_admin()) {
			$student_content_html = $this->get_students_content_html_for_platform_admin();
		} else if (api_is_drh()) {*/
			$evaluations_graph = $this->get_evaluations_graph();
		//}
		
		$html = '        		
			            <li class="widget color-orange" id="intro">
			                <div class="widget-head">
			                    <h3>'.get_lang('EvaluatiosGraph').'</h3>
			                    <div class="widget-actions"><a onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">'.Display::return_icon('close.gif',get_lang('Close')).'</a></div>
			                </div>			
			                <div class="widget-content" align="center">			                	
								'.$evaluations_graph.'
			                </div>
			            </li>			                        			    
				'; 
    	
    	$data['column'] = $column;
    	$data['content_html'] = $html;
    	
    	return $data;
		
	}

    /**
 	 * This method return a graph containing informations about evaluations, it's used inside get_block method for showing it inside dashboard interface
 	 * @return string  img html
 	 */
    public function get_evaluations_graph() {
		
		
		
		/*
		$graph = '';

		$course_code = 'CURSO1';
		$cats = Category::load(null, null, $course_code, null, null, null, false);		
		
		$alleval = $cats[0]->get_evaluations(null, true, 'CURSO1');
		$alllinks = $cats[0]->get_links(null, true);
		$users = get_all_users($alleval, $alllinks);		
		$printable_data = get_evaluation_sumary_result($users, $alleval, $alllinks);
		
		//var_dump($printable_data);
		
		/*
		$links = array();
		$i = 1;
		foreach ($printable_data[0] as $link) {			
			if ($i>2) $links[$i] = $link;
			$i++;
		}
		
		
		foreach ($printable_data[1] as $score) {			
			$x = 1;
			$scores = array();	
			foreach($score as $sc) {
				if ($x>2) $scores[][$i][] = $sc;
				$x++;	
				
			}
			
		}
		*/
		
		
		// Dataset definition   
	    $data_set = new pData;  
	    $data_set->AddPoint(array(3,15,15,8,15),"Serie1");  
	    $data_set->AddPoint(array(7,8.5,8.5,5,10),"Serie2");  
	    $data_set->AddPoint(array(2,2,2,2,5),"Serie3");
	    $data_set->AddPoint(array('eje1','eje2','forito','tareita','leccion'),"Serie4");
	    
	    //$data_set->AddAllSeries();  
	    //$data_set->SetAbsciseLabelSerie();  
	    
		$data_set->AddAllSeries();  
	   	$data_set->RemoveSerie("Serie4");  
	   	$data_set->SetAbsciseLabelSerie("Serie4");  
	    
	    
	    $data_set->SetSerieName("Maximum","Serie1");  
	    $data_set->SetSerieName("Average","Serie2");  
	    $data_set->SetSerieName("Minimum","Serie3");  
	     
	    
	    $graph_id = $this->user_id.'StudentEvaluationGraph';			 
		$cache = new pCache();
		// the graph id
		$data = $data_set->GetData();
	
		if ($cache->IsInCache($graph_id, $data)) {			
			//if we already created the img
			$img_file = $cache->GetHash($graph_id, $data);
		} else {
			// Initialise the graph  
		    $test = new pChart(450,230);  
		    $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);  
		    $test->setGraphArea(50,30,330,200);  
		    $test->drawFilledRoundedRectangle(7,7,343,223,5,240,240,240);  
		    $test->drawRoundedRectangle(5,5,345,225,5,230,230,230);  
		    $test->drawGraphArea(255,255,255,TRUE);  
		    $test->drawScale($data_set->GetData(),$data_set->GetDataDescription(),SCALE_ADDALL,150,150,150,TRUE,0,2,TRUE);  
		    $test->drawGrid(4,TRUE,230,230,230,50);  
		     
		    // Draw the 0 line  
		    $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',6);  
		    $test->drawTreshold(0,143,55,72,TRUE,TRUE);  
		     
		    // Draw the bar graph  
		    $test->drawStackedBarGraph($data_set->GetData(),$data_set->GetDataDescription(),TRUE);  
		     
		    // Finish the graph  
		    $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',8);  
		    $test->drawLegend(596,150,$data_set->GetDataDescription(),255,255,255);  
		    $test->setFontProperties(api_get_path(LIBRARY_PATH).'pchart/fonts/tahoma.ttf',10);  
		    $test->drawTitle(50,22,"Example 20",50,50,50,185);  
		    
		    $cache->WriteToCache($graph_id, $data_set->GetData(), $test);
			ob_start();
			$test->Stroke();
			ob_end_clean();
			$img_file = $cache->GetHash($graph_id, $data_set->GetData());	
		}
		
		if (!empty($img_file)) {
			$graph = '<img src="'.api_get_path(WEB_ARCHIVE_PATH).$img_file.'">';
		}  
		 
    	return $graph;
		
 	}


}
?>