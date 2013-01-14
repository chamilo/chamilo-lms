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

/**
 * This class is used like controller for teacher block plugin, 
 * the class name must be registered inside path.info file (e.g: controller = "BlockTeacher"), so dashboard controller will be instantiate it
 * @package chamilo.dashboard
 */
class BlockTeacher extends Block {

    private $user_id;
    private $teachers;
    private $path;
    private $permission = array(DRH);

	/**
	 * Controller
	 */
    public function __construct ($user_id) {    	
    	$this->user_id  = $user_id;
    	$this->path 	= 'block_teacher';
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
			$teacher_content_html = $this->get_teachers_content_html_for_drh();
		//}
		
		$html = '<li class="widget color-blue" id="intro">
                    <div class="widget-head">
                        <h3>'.get_lang('TeachersInformationsList').'</h3>
                        <div class="widget-actions"><a onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">'.Display::return_icon('close.gif',get_lang('Close')).'</a></div>
                    </div>
                    <div class="widget-content">
                        '.$teacher_content_html.'
                    </div>
                </li>';
    	
    	$data['column'] = $column;
    	$data['content_html'] = $html;
    	return $data;    	
    }
    
    /**
 	 * This method return a content html, it's used inside get_block method for showing it inside dashboard interface
 	 * @return string  content html
 	 */
    public function get_teachers_content_html_for_platform_admin() { 	
		$content = '<div style="margin:10px;">';
		$content .= '<h3><font color="#000">'.get_lang('YourTeachers').'</font></h3>';		 		
		if (count($this->teachers) > 0) {
	 		$teachers_table = '<table class="data_table">';
	 		$teachers_table .= '<tr>		
									<th>'.get_lang('User').'</th>
									<th>'.get_lang('TimeSpentOnThePlatform').'</th>			
									<th>'.get_lang('LastConnexion').'</th>													
								</tr>';	 		
	 		$i = 1;
	 		foreach ($this->teachers as $teacher) {
	 			
	 			$teacher_id = $teacher['user_id'];
	 			$firstname 	= $teacher['firstname'];
	 			$lastname 	= $teacher['lastname'];
	 			$username	= $teacher['username'];
	 			
	 			$time_on_platform = api_time_to_hms(Tracking :: get_time_spent_on_the_platform($teacher_id));
	 			$last_connection = Tracking :: get_last_connection_date($teacher_id);	 			
				
				if ($i%2 == 0) {
                    $class_tr = 'row_odd';
                } else {
                    $class_tr = 'row_even';
                }			    		
				$teachers_table .= '<tr class="'.$class_tr.'">		
										<td>'.api_get_person_name($firstname,$lastname).' ('.$username.')</td>
										<td align="right">'.$time_on_platform.'</td>					
										<td align="right">'.$last_connection.'</td>															
									</tr>';				
	 			$i++;
	 		}
	 		$teachers_table .= '</table>';
		} else {
			$teachers_table .= get_lang('ThereIsNoInformationAboutYourTeachers');
		}
	 	$content .= $teachers_table;
 		
 		if (count($this->teachers) > 0) {
			$content .= '<div style="text-align:right;margin-top:10px;"><a href="'.api_get_path(WEB_CODE_PATH).'mySpace/index.php?view=admin">'.get_lang('SeeMore').'</a></div>';
		}
		$content .= '</div>';
 		return $content; 	
	}
	
	public function get_teachers_content_html_for_drh() {  		
 		$content = '<div style="margin:10px;">';
 		$content .= '<h3><font color="#000">'.get_lang('YourTeachers').'</font></h3>'; 		
 		 		 		 	
 		if (count($this->teachers) > 0) { 			
 			$a_last_week = get_last_week();            
 			$last_week 	 = api_convert_and_format_date($a_last_week[0], DATE_FORMAT_SHORT).' '.get_lang('Until').'<br />'.api_convert_and_format_date($a_last_week[6], DATE_FORMAT_SHORT);
 			
	 		$teachers_table = '<table class="data_table">'; 		
	 		$teachers_table .= '<tr>		
									<th>'.get_lang('User').'</th>
									<th>'.get_lang('TimeSpentLastWeek').'<br />'.$last_week.'</th>														
								</tr>';		
	 		$i = 1;
	 		foreach ($this->teachers as $teacher) {
	 			
	 			$teacher_id = $teacher['user_id'];
	 			$firstname  = $teacher['firstname'];
	 			$lastname   = $teacher['lastname'];
				$username	= $teacher['username'];
	 			$time_on_platform = api_time_to_hms(Tracking :: get_time_spent_on_the_platform($teacher_id, 'custom', api_get_utc_datetime($a_last_week[0]), api_get_utc_datetime($a_last_week[6])));
                	 				 			
	 			if ($i%2 == 0) {
                    $class_tr = 'row_odd';
                } else {
                    $class_tr = 'row_even';
                }
	    		$teachers_table .= '<tr class="'.$class_tr.'">
										<td>'.api_get_person_name($firstname,$lastname).' ('.$username.')</td>										
										<td align="right">'.$time_on_platform.'</td>										
									</tr>';	 			
	 			$i++;		
	 		}
	 		$teachers_table .= '</table>';
 		} else {
 			$teachers_table .= get_lang('ThereIsNoInformationAboutYourTeachers');
 		}
  		
  		$content .= $teachers_table;
 		
 		if (count($this->teachers) > 0) {
			$content .= '<div style="text-align:right;margin-top:10px;"><a href="'.api_get_path(WEB_CODE_PATH).'mySpace/teachers.php">'.get_lang('SeeMore').'</a></div>';
		}
		$content .= '</div>';
  		return $content;  		
  	}
 
    /**
	 * Get number of teachers  
	 * @return int
	 */
	function get_number_of_teachers() {
		return count($this->teachers);
	}    
}