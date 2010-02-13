<?php
/**
 * This file is part of course block plugin for dashboard,
 * it should be required inside dashboard controller for showing it into dashboard interface from plattform
 * @package chamilo.dashboard
 * @author Christian Fasanando
 */

/**
 * required files for getting data
 */
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'tracking.lib.php';
require_once api_get_path(LIBRARY_PATH).'course_description.lib.php';

/**
 * This class is used like controller for this course block plugin,
 * the class name must be registered inside path.info file (e.g: controller = "BlockCourse"), so dashboard controller will be instantiate it
 * @package chamilo.dashboard
 */
class BlockCourse extends Block {

	private $user_id;
	private $courses;
	private $path;

	/**
	 * Constructor
	 */
    public function __construct ($user_id) {
    	$this->user_id = $user_id;
    	$this->courses = CourseManager::get_assigned_courses_to_hr_manager($user_id);
    	$this->path = 'block_course';
    }

    /**
     * This method return content html containing information about courses and its position for showing it inside dashboard interface
     * it's important to use the name 'get_block' for beeing used from dashboard controller
     * @return array   column and content html
     */
    public function get_block() {

    	global $charset;

    	$column = 2;
    	$data   = array();
		$content = '';
		$data_table = '';
		$content = $this->get_content_html();
		$html = '
		            <li class="widget color-green" id="intro">
		                <div class="widget-head">
		                    <h3>Courses Informations</h3>
		                    <div class="widget-actions"><a onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'),ENT_QUOTES,$charset)).'\')) return false;" href="index.php?action=disable_block&path='.$this->path.'">'.Display::return_icon('close.gif',get_lang('Close')).'</a></div>
		                </div>
		                <div class="widget-content">
		                   '.$content.'
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
 	public function get_content_html() {

 		$course_data = $this->get_course_information_data();
 		$content = '';
 		if (!empty($course_data)) {
	    	$data_table = '<table class="data_table" width:"95%">';
	    	$data_table .= '<tr>
	    						<th>'.get_lang('CourseTitle').'</th>
	    						<th width="10%">'.get_lang('NbStudents').'</th>
	    						<th width="10%">'.get_lang('AvgTimeSpentInTheCourse').'</th>
	    						<th width="10%">'.get_lang('AvgStudentsProgress').'</th>
	    						<th width="10%">'.get_lang('AvgCourseScore').'</th>
	    						<th width="10%">'.get_lang('AvgExercisesScore').'</th>
	    						<th width="10%">'.get_lang('ThematicAdvance').'</th>
	    					</tr>';
	    	$i = 1;
	    	foreach ($course_data as $course) {
	    		if ($i%2 == 0) $class_tr = 'row_odd';
	    		else $class_tr = 'row_even';
	    		$data_table .= '<tr class="'.$class_tr.'">';
	    		foreach ($course as $cell) {
	    			$data_table .= '<td align="right">'.$cell.'</td>';
	    		}
	    		$data_table .= '</tr>';
	    		$i++;
	    	}
	    	$data_table .= '</table>';
		} else {
			$data_table .= get_lang('ThereAreNoInformationsAboutYoursCourses');
		}

		$content .= '<div style="margin:15px;"><h3>'.get_lang('YourCourseList').'</h3>';
		$content .= $data_table;
		if (!empty($course_data)) {
			$content .= '<div style="text-align:right;margin-top:10px;"><a href="#">'.get_lang('SeeMore').'</a></div>';
		}
		$content .= '</div>';

 		return $content;
 	}

    /**
	 * Get number of courses
	 * @return int
	 */
	function get_number_of_courses() {
		return count($this->courses);
	}

	/**
	 * Get course information data
	 * @return array
	 */
	function get_course_information_data() {
		$tbl_course = Database::get_main_table(TABLE_MAIN_COURSE);
		$tbl_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);

		$a_course_students  = array();
		$course_data = array();
		$courses = $this->courses;

		foreach ($courses as $row_course) {

			$course_code = $row_course['code'];
			$avg_assignments_in_course = $avg_messages_in_course = $nb_students_in_course = $avg_progress_in_course = $avg_score_in_course = $avg_time_spent_in_course = $avg_score_in_exercise = 0;

			// students directly subscribed to the course
			$sql = "SELECT user_id FROM $tbl_course_user as course_rel_user WHERE course_rel_user.status='5' AND course_rel_user.course_code='$course_code'";
			$rs = Database::query($sql);
			$users = array();
			while ($row = Database::fetch_array($rs)) {
				$users[] = $row['user_id'];
			}
			if (count($users) > 0) {
				$nb_students_in_course = count($users);
				$avg_time_spent_in_course  = Tracking::get_time_spent_on_the_course($users, $course_code);
				$avg_progress_in_course = Tracking::get_avg_student_progress($users, $course_code);
				$avg_score_in_course = Tracking :: get_avg_student_score($users, $course_code);
				$avg_score_in_exercise = Tracking::get_avg_student_exercise_score($users, $course_code);

				$avg_time_spent_in_course = api_time_to_hms($avg_time_spent_in_course / $nb_students_in_course);
				$avg_progress_in_course = round($avg_progress_in_course / $nb_students_in_course, 2);
				$avg_score_in_course = round($avg_score_in_course / $nb_students_in_course, 2);
				$avg_score_in_exercise = round($avg_score_in_exercise / $nb_students_in_course, 2);
			} else {
				$avg_time_spent_in_course = null;
				$avg_progress_in_course = null;
				$avg_score_in_course = null;
				$avg_score_in_exercise = null;
			}

			$tematic_advance_progress = 0;
			$course_description = new CourseDescription();
			$course_description->set_session_id(0);
			$tematic_advance = $course_description->get_data_by_description_type(8, $course_code);

			if (!empty($tematic_advance)) {
				$tematic_advance_progress = $tematic_advance['progress'];
			}

			$table_row = array();
			$table_row[] = $row_course['title'];
			$table_row[] = $nb_students_in_course;
			$table_row[] = $avg_time_spent_in_course;
			$table_row[] = is_null($avg_progress_in_course) ? '' : $avg_progress_in_course.'%';
			$table_row[] = is_null($avg_score_in_course) ? '' : $avg_score_in_course.'%';
			$table_row[] = is_null($avg_score_in_exercise) ? '' : $avg_score_in_exercise.'%';
			$table_row[] = $tematic_advance_progress.'%';
			$course_data[] = $table_row;
		}

		return $course_data;
	}


}
?>