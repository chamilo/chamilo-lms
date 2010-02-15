<?php
/**
 * This file is part of teacher block plugin for dashboard, 
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
 * This class is used like controller for teacher block plugin, 
 * the class name must be registered inside path.info file (e.g: controller = "BlockTeacher"), so dashboard controller will be instantiate it
 * @package chamilo.dashboard
 */
class BlockTeacher extends Block {

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
    	$this->path 	= 'block_teacher';  	
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
		
		if (api_is_platform_admin()) {
			$teacher_content_html = $this->get_teachers_content_html_for_platform_admin();
		} else if (api_is_drh()) {
			$teacher_content_html = $this->get_teachers_content_html_for_drh();
		}
		
		$html = '        		
			            <li class="widget color-blue" id="intro">
			                <div class="widget-head">
			                    <h3>Teachers Informations</h3>
			                    <div class="widget-actions"><a onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">'.Display::return_icon('close.gif',get_lang('Close')).'</a></div>
			                </div>
			                <div class="widget-content">
								'.$teacher_content_html.'
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
    public function get_teachers_content_html_for_platform_admin() {
 	
	 	$teachers = $this->teachers;
		$content = ''; 		
		$content = '<div style="margin:10px;">';
		$content .= '<h3><font color="#000">'.get_lang('YourTeachers').'</font></h3>';
		 		
		if (count($teachers) > 0) {
	 		$teachers_table = '<table class="data_table" width:"95%">'; 		
	 		$teachers_table .= '
								<tr>		
									<th>'.get_lang('FirtName').'</th>
									<th>'.get_lang('LastName').'</th>
									<th>'.get_lang('TimeSpentOnThePlatform').'</th>					
									<th>'.get_lang('LastConnexion').'</th>													
								</tr>								
							';
	 		
	 		$i = 1;
	 		foreach ($teachers as $teacher) {
	 			
	 			$teacher_id = $teacher['user_id'];
	 			$firtname = $teacher['firstname'];
	 			$lastname = $teacher['lastname'];
	 			$time_on_platform = api_time_to_hms(Tracking :: get_time_spent_on_the_platform($teacher_id));
	 			$last_connection = Tracking :: get_last_connection_date($teacher_id);	 			
				
				if ($i%2 == 0) $class_tr = 'row_odd';
			    else $class_tr = 'row_even';
			    		
				$teachers_table .= '
									<tr class="'.$class_tr.'">		
										<td>'.$firtname.'</td>
										<td>'.$lastname.'</td>
										<td>'.$time_on_platform.'</td>					
										<td>'.$last_connection.'</td>															
									</tr>								
									';				
	 			$i++;
	 		}
	 		$teachers_table .= '</table>';
		} else {
			$teachers_table .= get_lang('ThereAreNoInformationAboutTeachers');
		}
	 	
	 	
	 	$content .= $teachers_table;
 		
 		if (count($teachers) > 0) {
			$content .= '<div style="text-align:right;margin:10px;"><a href="'.api_get_path(WEB_CODE_PATH).'mySpace/index.php?view=admin">'.get_lang('SeeMore').'</a></div>';
		}

		$content .= '</div>';	
 	
 		return $content;
 	
	}
	
	public function get_teachers_content_html_for_drh() {  		
  		
  		$teachers = $this->teachers;
 		$content = ''; 		
 		$content = '<div style="margin:10px;">';
 		$content .= '<h3><font color="#000">'.get_lang('YourTeachers').'</font></h3>';
 		 		
 		if (count($teachers) > 0) {
	 		$teachers_table = '<table class="data_table" width:"95%">'; 		
	 		$teachers_table .= '
								<tr>		
									<th>'.get_lang('FirtName').'</th>
									<th>'.get_lang('LastName').'</th>
									<th>'.get_lang('Time').'</th>														
									<th>'.get_lang('Email').'</th>
								</tr>								
							';
	 		
	 		$i = 1;
	 		foreach ($teachers as $teacher) {
	 			
	 			$teacher_id = $teacher['user_id'];
	 			$firstname  = $teacher['firstname'];
	 			$lastname   = $teacher['lastname'];
	 			$time_on_platform = api_time_to_hms(Tracking :: get_time_spent_on_the_platform($teacher_id));
	 			$email		= $teacher['email'];
	 			
	 			if ($i%2 == 0) $class_tr = 'row_odd';
	    		else $class_tr = 'row_even';
	    		$teachers_table .= '<tr class="'.$class_tr.'">
										<td>'.$firstname.'</td>
										<td>'.$lastname.'</td>										
										<td>'.$time_on_platform.'</td>
										<td>'.$email.'</td>
									</tr>';
	 			
	 			$i++;		
	 		}
	 		$teachers_table .= '</table>';
 		} else {
 			$teachers_table .= get_lang('ThereAreNoInformationAboutTeachers');
 		}
  		
  		$content .= $teachers_table;
 		
 		if (count($teachers) > 0) {
			$content .= '<div style="text-align:right;margin:10px;"><a href="'.api_get_path(WEB_CODE_PATH).'mySpace/teachers.php">'.get_lang('SeeMore').'</a></div>';
		}
		$content .= '</div>';

  		return $content;	
  		
  	}
 
    /**
	 * Get number of sessions  
	 * @return int
	 */
	function get_number_of_teachers() {
		return count($this->teachers);
	}
    
}

?>