<?php
/**
 * This file is part of session block plugin for dashboard, 
 * it should be required inside dashboard controller for showing it into dashboard interface from plattform
 * @package chamilo.dashboard
 * @author Christian Fasanando
 */

/**
 * required files for getting data
 */
require_once api_get_path(LIBRARY_PATH).'sessionmanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'course_description.lib.php';

/**
 * This class is used like controller for this session block plugin, 
 * the class name must be registered inside path.info file (e.g: controller = "BlockSession"), so dashboard controller will be instantiate it
 * @package chamilo.dashboard
 */
class BlockSession extends Block {

    private $user_id;
	private $sessions;
	private $path;

	/**
	 * Constructor
	 */
    public function __construct ($user_id) {    	
    	$this->user_id 	= $user_id;
    	$this->sessions = SessionManager::get_assigned_sessions_to_hr_manager($user_id);
    	$this->path 	= 'block_session';   	
    }
    
    /**
     * This method return content html containing information about sessions and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from dashboard controller 
     * @return array   column and content html
     */
    public function get_block() {

		global $charset;
		
    	$column = 2;
    	$data   = array();

		$content = $this->get_content_html();

		$content_html = '        		
			            <li class="widget color-red" id="intro">
			                <div class="widget-head">
			                    <h3>'.get_lang('SessionsInformation').'</h3>
			                    <div class="widget-actions"><a onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">'.Display::return_icon('close.gif',get_lang('Close')).'</a></div>
			                </div>
			
			                <div class="widget-content">
							'.$content.'
			                </div>
			            </li>            			    
				'; 
    	
    	$data['column'] = $column;
    	$data['content_html'] = $content_html;
    	    		
    	return $data;     	    	
    }
    
    /**
 	 * This method return a content html, it's used inside get_block method for showing it inside dashboard interface
 	 * @return string  content html
 	 */
    public function get_content_html() {
 		
 		$content = ''; 		 						
		$sessions = $this->sessions;
		
		$content = '<div style="margin:10px;">';
		$content .= '<h3><font color="#000">'.get_lang('YourTrainingsSessionList').'</font></h3>';
		
		if (count($sessions) > 0) {
			foreach ($sessions as $session) {
				
				$session_id = intval($session['id']);
				$content .= '<div style="margin-top:10px;"><strong>'.$session['name'].'</strong> - '.get_lang('From').' '.$session['date_start'].' '.get_lang('To').' '.$session['date_end'].'</div>';
				$courses = Tracking ::get_courses_list_from_session($session_id);
	
				$courses_table = '';
				if (count($courses)) {
					$courses_table = '<div style="margin:10px;margin-bottom:20px;"><table class="data_table" width:"95%">';
		 			$courses_table .= '<tr>
										<th>'.get_lang('Course').'</th>
										<th width="10%">'.get_lang('Time').'</th>
										<th width="10%">'.get_lang('Progress').'</th>
										<th width="10%">'.get_lang('Score').'</th>
										<th width="10%">'.get_lang('TematicAdvance').'</th>
									</tr>';
					$i = 1;					
					foreach ($courses as $course) {
											
						$course_code = Database::escape_string($course['course_code']);
						$course_info = CourseManager :: get_course_information($course_code);
						
						$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
						
						// students directly subscribed to the course
						$sql = "SELECT id_user  FROM $tbl_session_course_user srcu WHERE  srcu. course_code='$course_code'";					
						$rs = Database::query($sql, __FILE__, __LINE__);
						$users = array();		
						while ($row = Database::fetch_array($rs)) {		
							$users[] = $row['id_user']; 							
						}	
		
						$time_spent_on_course = api_time_to_hms(Tracking :: get_time_spent_on_the_course($users, $course_code));
						$progress = Tracking :: get_avg_student_progress($users, $course_code);
						$score = Tracking :: get_avg_student_score($users, $course_code);
						$progress = empty($progress) ? '0%' : $progress.'%';
						$score = empty($score) ? '0%' : $score.'%';
												
						$tematic_advance_progress = 0;
						$course_description = new CourseDescription();
						$course_description->set_session_id($session_id);
						$tematic_advance = $course_description->get_data_by_description_type(8, $course_code);
						
						if (!empty($tematic_advance)) {
							$tematic_advance_progress = $tematic_advance['progress'];
						}

						if ($i%2 == 0) $class_tr = 'row_odd';
			    		else $class_tr = 'row_even';
						
						$courses_table .= '<tr class="'.$class_tr.'">
												<td align="right">'.$course_info['title'].'</td>
												<td align="right">'.$time_spent_on_course.'</td>
												<td align="right">'.$progress.'</td>
												<td align="right">'.$score.'</td>
												<td align="right">'.$tematic_advance_progress.'%</td>
										   </tr>';	
						$i++;							
					}
					$courses_table .= '</table></div>';
				} else {
					$courses_table .= '<div style="margin:10px;">'.get_lang('ThereAreNoCoursesInformationsInsideThisSession').'</div>';
				}				
				$content .= $courses_table;
			}
		} else {
			$content .= '<div style="margin:20px;">'.get_lang('ThereAreNoInformationsAboutYoursSessions').'</div>';
		}		
		
		if (count($sessions) > 0) {
			$content .= '<div style="text-align:right;margin-top:10px;"><a href="#">'.get_lang('SeeMore').'</a></div>';
		}
				
		$content .= '</div>';

 		return $content;
 	}
 
    /**
	 * Get number of sessions  
	 * @return int
	 */
	function get_number_of_sessions() {
		return count($this->sessions);
	}
	    
}
?>