<?php
/**
 * This file is part of student block plugin for dashboard, 
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
 * This class is used like controller for student block plugin, 
 * the class name must be registered inside path.info file (e.g: controller = "BlockStudent"), so dashboard controller will be instantiate it
 * @package chamilo.dashboard
 */
class BlockStudent extends Block {

    private $user_id;
	private $students;
	private $path;

	/**
	 * Constructor
	 */	
    public function __construct ($user_id) {    	
    	$this->user_id  = $user_id; 
    	$this->students =  UserManager::get_assigned_users_to_hr_manager($user_id, STUDENT);
    	$this->path 	= 'block_student';    	  	
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

		$student_content_html = $this->get_students_content_html();

		$html = '        		
			            <li class="widget color-blue" id="intro">
			                <div class="widget-head">
			                    <h3>Students Informations</h3>
			                    <div class="widget-actions"><a onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">'.Display::return_icon('close.gif',get_lang('Close')).'</a></div>
			                </div>			
			                <div class="widget-content">			                	
								'.$student_content_html.'
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
    public function get_students_content_html() {
 		 		
 		$students = $this->students;
 		$content = ''; 		
 		$content = '<div style="margin:20px;margin-right:30px;">';
		$content .= '<h3><font color="#000">'.get_lang('StudentsOverview').'</font></h3>';
 		 		
 		if (count($students) > 0) {
	 		$students_table = '<div style="margin:10px;width:100%"><table class="data_table" width:"90%">'; 		
	 		$students_table .= '
								<tr>		
									<th width="10%" rowspan="2">'.get_lang('FirtName').'</th>
									<th width="10%" rowspan="2">'.get_lang('LastName').'</th>														
									<th width="30%" colspan="4">'.get_lang('TrainingInformations').'</th>
								</tr>
								<tr>
									<th width="10%">'.get_lang('Training').'</th>
									<th width="10%">'.get_lang('Time').'</th>
									<th width="10%">'.get_lang('Progress').'</th>
									<th width="10%">'.get_lang('Score').'</th>		
								</tr>
							';
	 		
	 		$i = 1;
	 		foreach ($students as $student) {
	 			 			
	 			$courses_by_user = CourseManager::get_courses_list_by_user_id($student['user_id'], true);
	 			$count_courses = count($courses_by_user);
	
				if ($i%2 == 0) $style = ' style="background-color:#F2F2F2" ';
		    	else $style = ' style="background-color:#FFF" ';
	 			$students_table .= '<tr '.$style.'>
										<td rowspan="'.($count_courses+1).'">'.$student['firstname'].'</td>
										<td rowspan="'.($count_courses+1).'">'.$student['lastname'].'</td>												
									</tr>';
	 			
	 			// courses information about the student 			
	 			foreach ($courses_by_user as $course) { 				
	 				$course_code = $course['code'];
	 				$course_title = $course['title']; 				
	 				$time = api_time_to_hms(Tracking :: get_time_spent_on_the_course($student['user_id'], $course_code));
	 				$progress = round(Tracking :: get_avg_student_progress($student['user_id'], $course_code), 2);
	 				$score = round(Tracking :: get_avg_student_score($student['user_id'], $course_code), 2);
	 				
	 				$students_table .= '<tr '.$style.'>
										<td>'.$course_title.'</td>
										<td>'.$time.'</td>
										<td>'.$progress.'</td>
										<td>'.$score.'</td>		
										</tr>';
	 			}
	 			$i++;	
	 		}
	 		$students_table .= '</table></div>';
 		} else { 			
 			$students_table .= '<div style="margin:20px">'.get_lang('ThereAreNoInformationAboutStudents').'</div>';
 		}
 		 		 		
 		$content .= $students_table;
 		
 		if (count($students) > 0) {
			$content .= '<div style="text-align:right;margin:10px;"><a href="#">'.get_lang('SeeMore').'</a></div>';
		}

		$content .= '</div>';

 		return $content;
 	}
  
    /**
	 * Get number of sessions  
	 * @return int
	 */
	function get_number_of_students() {
		return count($this->students);
	}
    
}

?>