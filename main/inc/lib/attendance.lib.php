<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains class used like library provides functions for attendance tool. It's also used like model to attendance_controller (MVC pattern)
 * @author Christian Fasanando <christian1827@gmail.com>
 * @package chamilo.attendance
 */

/**
 * Attendance can be used to instanciate objects or as a library to manage attendances
 * @package chamilo.attendance
 */

class Attendance
{
	private $session_id;
	private $course_id;
	private $date_time;
	private $name;
	private $description;
	private $attendance_qualify_title;
	private $attendance_weight;


	public function __construct() {}

	/**
	 * Get the total number of attendance inside current course and current session
	 * @see SortableTable#get_total_number_of_items()
	 */
	function get_number_of_attendances() {
		$tbl_attendance = Database :: get_course_table(TABLE_ATTENDANCE);
		$session_id = api_get_session_id();
		$sql = "SELECT COUNT(att.id) AS total_number_of_items FROM $tbl_attendance att WHERE att.active = 1 = att.session_id = '$session_id' ";
		$res = Database::query($sql);
		$res = Database::query($sql);
		$obj = Database::fetch_object($res);
		return $obj->total_number_of_items;
	}

	
	/**
	 * Get attendance list 
	 * @param   string  course code (optional)
	 * @param   int     session id (optional)
	 * @return  array	attendances list
	 */
	function get_attendances_list($course_code = '', $session_id = 0) {		
		// initializing database table and variables
		$tbl_attendance = Database :: get_course_table(TABLE_ATTENDANCE);
		$session_id = intval($session_id);
		$data = array();
		if (!empty($course_code)) {
			$course_info = api_get_course_info($course_code);
			$tbl_attendance = Database :: get_course_table(TABLE_ATTENDANCE, $course_info['dbName']);
		}
		
		// get attendance data
		$sql = "SELECT * FROM $tbl_attendance WHERE session_id='$session_id'";
		$rs  = Database::query($sql);
		if (Database::num_rows($rs) > 0){
			while ($row = Database::fetch_array($rs)) {
				$data[$row['id']] = $row;
			}
		}
		return $data;		
	}

	/**
	 * Get the attendaces to display on the current page (fill the sortable-table)
	 * @param   int     offset of first user to recover
	 * @param   int     Number of users to get
	 * @param   int     Column to sort on
	 * @param   string  Order (ASC,DESC)
	 * @see SortableTable#get_table_data($from)
	 */
	function get_attendance_data($from, $number_of_items, $column, $direction) {
		$tbl_attendance = Database :: get_course_table(TABLE_ATTENDANCE);
		$session_id = api_get_session_id();
	    $column = intval($column);
	    $from = intval($from);
	    $number_of_items = intval($number_of_items);
		if (!in_array($direction, array('ASC','DESC'))) {
	    	$direction = 'ASC';
	    }
		$sql = "SELECT
				att.id AS col0,
				att.name AS col1,
				att.description AS col2,
				att.attendance_qualify_max AS col3
				FROM $tbl_attendance att
				WHERE att.session_id = '$session_id' AND att.active = 1
				ORDER BY col$column $direction LIMIT $from,$number_of_items ";
		$res = Database::query($sql);
		$attendances = array ();

		$param_gradebook = '';
		if (isset($_SESSION['gradebook'])) {
			$param_gradebook = '&gradebook='.$_SESSION['gradebook'];
		}

		while ($attendance = Database::fetch_row($res)) {

			$student_param = '';
			if (api_is_drh() && ($_GET['student_id'])) {
				$student_param = '&student_id='.Security::remove_XSS($_GET['student_id']);	
			}
			
			$attendance[1] = '<a href="index.php?'.api_get_cidreq().'&action=attendance_sheet_list&attendance_id='.$attendance[0].$param_gradebook.$student_param.'">'.$attendance[1].'</a>';
			$attendance[3] = '<center>'.$attendance[3].'</center>';
			if (api_is_allowed_to_edit(null, true)) {
				$actions  = '';
				$actions .= '<center><a href="index.php?'.api_get_cidreq().'&action=attendance_edit&attendance_id='.$attendance[0].$param_gradebook.'">'.Display::return_icon('edit.gif',get_lang('Edit')).'</a>&nbsp;';
				$actions .= '<a onclick="javascript:if(!confirm(\''.get_lang('AreYouSureToDelete').'\')) return false;" href="index.php?'.api_get_cidreq().'&action=attendance_delete&attendance_id='.$attendance[0].$param_gradebook.'">'.Display::return_icon('delete.gif',get_lang('Delete')).'</a></center>';
				$attendances[] = array($attendance[0], $attendance[1], $attendance[2], $attendance[3],$actions);
			} else {
				$attendance[0] = '&nbsp;';
				$attendances[] = array($attendance[0], $attendance[1], $attendance[2], $attendance[3]);
			}
		}
		return $attendances;
	}

	/**
	 * Get the attendaces by id to display on the current page
	 * @param  int     attendance id
	 * @return array   attendance data
	 */
	public function get_attendance_by_id($attendance_id) {
		$tbl_attendance = Database :: get_course_table(TABLE_ATTENDANCE);
		$sesion_id = api_get_session_id();
		$attendance_id = intval($attendance_id);
	    $attendance_data = array();
		$sql = "SELECT *  FROM $tbl_attendance WHERE id = '$attendance_id'";
		$res = Database::query($sql);
		if (Database::num_rows($res) > 0) {
			while ($row = Database::fetch_array($res)) {
				$attendance_data = $row;
			}
		}
		return $attendance_data;
	}

	/**
	 * add attendaces inside table
	 * @param  bool   true for adding link in gradebook or false otherwise (optional)
	 * @return int    last attendance id
	 */
	public function attendance_add($link_to_gradebook = false) {
		global $_course;
		$tbl_attendance	= Database :: get_course_table(TABLE_ATTENDANCE);
		$table_link 	= Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
		$session_id 	= api_get_session_id();
		$user_id 		= api_get_user_id();
		$course_code	= api_get_course_id();
		$title_gradebook= Database::escape_string($this->attendance_qualify_title);
		$value_calification  = 0;
		$weight_calification =	floatval($this->attendance_weight);
		$sql = "INSERT INTO $tbl_attendance
				SET name ='".Database::escape_string($this->name)."',
					description = '".Database::escape_string($this->description)."',
					attendance_qualify_title = '$title_gradebook',
					attendance_weight = '$weight_calification',
					session_id = '$session_id'";
		Database::query($sql);
		$affected_rows = Database::affected_rows();
		$last_id = 0;
		if (!empty($affected_rows)) {
			// save inside item property table
			$last_id = Database::insert_id();
			api_item_property_update($_course, TOOL_ATTENDANCE, $last_id,"AttendanceAdded", $user_id);
		}
		// add link to gradebook
		if ($link_to_gradebook) {
			$description = '';
			$link_id=is_resource_in_course_gradebook($course_code,7,$last_id,$session_id);
			if ($link_id==false) {
				add_resource_to_course_gradebook($course_code, 7, $last_id, $title_gradebook,$weight_calification,$value_calification,$description,time(),1,$session_id);
			} else {
				Database::query('UPDATE '.$table_link.' SET weight='.$weight_calification.' WHERE id='.$link_id.'');
			}
		}
		return $last_id;
	}

	/**
	 * edit attendaces inside table
	 * @param 	int	   attendance id
	 * @param  	bool   true for adding link in gradebook or false otherwise (optional)
	 * @return 	int    affected rows
	 */
	public function attendance_edit($attendance_id, $link_to_gradebook = false) {
		global $_course;
		$tbl_attendance	= Database :: get_course_table(TABLE_ATTENDANCE);
		$table_link 	= Database :: get_main_table(TABLE_MAIN_GRADEBOOK_LINK);
		$session_id 	= api_get_session_id();
		$user_id 		= api_get_user_id();
		$attendance_id	= intval($attendance_id);
		$course_code 	= api_get_course_id();
		$title_gradebook	 = Database::escape_string($this->attendance_qualify_title);
		$value_calification  = 0;
		$weight_calification =	floatval($this->attendance_weight);


		$sql = "UPDATE $tbl_attendance
				SET name ='".Database::escape_string($this->name)."',
					description = '".Database::escape_string($this->description)."',
					attendance_qualify_title = '".$title_gradebook."',
					attendance_weight = '".$weight_calification."'
				WHERE id = '$attendance_id'";
		Database::query($sql);
		$affected_rows = Database::affected_rows();
		if (!empty($affected_rows)) {
			// update row item property table
			$last_id = Database::insert_id();
			api_item_property_update($_course, TOOL_ATTENDANCE, $attendance_id,"AttendanceUpdated", $user_id);
		}

		// add link to gradebook
		if ($link_to_gradebook) {
			$description = '';
			$link_id=is_resource_in_course_gradebook($course_code,7,$attendance_id,$session_id);
			if ($link_id==false) {
				add_resource_to_course_gradebook($course_code, 7, $attendance_id, $title_gradebook,$weight_calification,$value_calification,$description,time(),1,$session_id);
			} else {
				Database::query('UPDATE '.$table_link.' SET weight='.$weight_calification.' WHERE id='.$link_id.'');
			}
		}
		return $affected_rows;
	}

	/**
	 * delete attendaces
	 * @param 	int	   attendance id
	 * @return 	int    affected rows
	 */
	public function attendance_delete($attendance_id) {
		global $_course;
		$tbl_attendance	= Database :: get_course_table(TABLE_ATTENDANCE);
		$user_id 		= api_get_user_id();
		if (is_array($attendance_id)) {
			foreach ($attendance_id as $id) {
				$id	= intval($id);
				$sql = "UPDATE $tbl_attendance SET active = 0 WHERE id = '$id'";
				Database::query($sql);
				$affected_rows = Database::affected_rows();
				if (!empty($affected_rows)) {
					// update row item property table
					api_item_property_update($_course, TOOL_ATTENDANCE, $id,"delete", $user_id);
				}
			}
		} else  {
			$attendance_id	= intval($attendance_id);
			$sql = "UPDATE $tbl_attendance SET active = 0 WHERE id = '$attendance_id'";
			Database::query($sql);
			$affected_rows = Database::affected_rows();
			if (!empty($affected_rows)) {
				// update row item property table
				api_item_property_update($_course, TOOL_ATTENDANCE, $attendance_id,"delete", $user_id);
			}
		}
		return $affected_rows;
	}

	/**
	 * get registered users inside current course
	 * @param 	int	   attendance id for showing attendance result field (optional)
	 * @return 	array  users data
	 */
	public function get_users_rel_course($attendance_id = 0) {
		$current_session_id = api_get_session_id();
		$current_course_id  = api_get_course_id();
		if (!empty($current_session_id)) {
			$a_course_users = CourseManager :: get_user_list_from_course_code($current_course_id, true, $current_session_id,'','lastname');
		} else {
			$a_course_users = CourseManager :: get_user_list_from_course_code($current_course_id, false, 0, '','lastname');
		}
		// get registered users inside current course
		$a_users = array();

		foreach ($a_course_users as $key =>  $value) {
			$uid = intval($value['user_id']);
			$status = $value['status'];
			if ($uid <= 1 || $status == DRH) continue;
			if (!empty($attendance_id)) {
				$attendance_id = intval($attendance_id);
				$user_faults = $this->get_faults_of_user($uid, $attendance_id);
				$value['attendance_result'] = $user_faults['faults'].'/'.$user_faults['total'].' ('.$user_faults['faults_porcent'].'%)';
				$value['result_color_bar'] = $user_faults['color_bar'];
			}

			// user's picture
			$image_path = UserManager::get_user_picture_path_by_id($uid, 'web', false);
			$user_profile = UserManager::get_picture_user($uid, $image_path['file'], 22, USER_IMAGE_SIZE_SMALL, ' width="22" height="22" ');
			if (!api_is_anonymous()) {
				if (!empty($image_path['file'])) {
					$photo = '<center><a class="thickbox" href="'.$image_path['dir'].$image_path['file'].'"  ><img src="'.$user_profile['file'].'" '.$user_profile['style'].' alt="'.api_get_person_name($value['firstname'], $value['lastname']).'"  title="'.api_get_person_name($value['firstname'], $value['lastname']).'" /></a></center>';
				} else {
					$photo = '<center><img src="'.$user_profile['file'].'" '.$user_profile['style'].' alt="'.api_get_person_name($value['firstname'], $value['lastname']).'"  title="'.api_get_person_name($value['firstname'], $value['lastname']).'" /></center>';
				}
			} else {
				$photo = '<center><img src="'.$user_profile['file'].'" '.$user_profile['style'].' alt="'.api_get_person_name($value['firstname'], $value['lastname']).'" title="'.api_get_person_name($value['firstname'], $value['lastname']).'" /></center>';
			}
			$value['photo'] = $photo;
			$a_users[$key] = $value;
		}
		return $a_users;
	}

	/**
	 * add attendaces sheet inside table
	 * @param 	int	   attendance calendar id
	 * @param  	array  present users during current class
	 * @param	int	   attendance id
	 * @return 	int    affected rows
	 */
	public function attendance_sheet_add($calendar_id,$users_present,$attendance_id) {
		$tbl_attendance_sheet 	= Database::get_course_table(TABLE_ATTENDANCE_SHEET);
		$tbl_attendance_calendar= Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
		$tbl_attendance_result 	= Database::get_course_table(TABLE_ATTENDANCE_RESULT);
		$tbl_attendance			= Database::get_course_table(TABLE_ATTENDANCE);

		$calendar_id = intval($calendar_id);
		$attendance_id = intval($attendance_id);
		$users = $this->get_users_rel_course();
		$user_ids = array_keys($users);
		$users_absent = array_diff($user_ids,$users_present);
		$affected_rows = 0;

		// save users present in class
		foreach ($users_present as $user_present) {
			$uid = intval($user_present);
			// check if user already was registered with the $calendar_id
			$sql = "SELECT user_id FROM $tbl_attendance_sheet WHERE user_id='$uid' AND attendance_calendar_id = '$calendar_id'";
			$rs  = Database::query($sql);
			if (Database::num_rows($rs) == 0) {
				$sql = "INSERT INTO $tbl_attendance_sheet SET user_id ='$uid', attendance_calendar_id = '$calendar_id', presence = 1";
				Database::query($sql);
				$affected_rows = Database::affected_rows();
			} else {
				$sql = "UPDATE $tbl_attendance_sheet SET presence = 1 WHERE user_id ='$uid' AND attendance_calendar_id = '$calendar_id'";
				Database::query($sql);
				$affected_rows = Database::affected_rows();
			}
		}

		// save users absent in class
		foreach ($users_absent as $user_absent) {
			$uid = intval($user_absent);
			// check if user already was registered with the $calendar_id
			$sql = "SELECT user_id FROM $tbl_attendance_sheet WHERE user_id='$uid' AND attendance_calendar_id = '$calendar_id'";
			$rs  = Database::query($sql);
			if (Database::num_rows($rs) == 0) {
				$sql = "INSERT INTO $tbl_attendance_sheet SET user_id ='$uid', attendance_calendar_id = '$calendar_id', presence = 0";
				Database::query($sql);
				$affected_rows = Database::affected_rows();
			} else {
				$sql = "UPDATE $tbl_attendance_sheet SET presence = 0 WHERE user_id ='$uid' AND attendance_calendar_id = '$calendar_id'";
				Database::query($sql);
				$affected_rows = Database::affected_rows();
			}
		}

		// update done_attendance inside attendance calendar table
		$sql = "UPDATE $tbl_attendance_calendar SET done_attendance = 1 WHERE  id = '$calendar_id'";
		Database::query($sql);

		// save users' results
		$this->update_users_results($user_ids, $attendance_id);

		return $affected_rows;
	}

	/**
	 * update users' attendance results
	 * @param 	array  registered users inside current course
	 * @param	int	   attendance id
	 * @return 	void
	 */
	public function update_users_results($user_ids, $attendance_id) {
		$tbl_attendance_sheet 	= Database::get_course_table(TABLE_ATTENDANCE_SHEET);
		$tbl_attendance_calendar= Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
		$tbl_attendance_result  = Database::get_course_table(TABLE_ATTENDANCE_RESULT);
		$tbl_attendance			= Database::get_course_table(TABLE_ATTENDANCE);
		$attendance_id = intval($attendance_id);
		// fill results about presence of students
		$attendance_calendar = $this->get_attendance_calendar($attendance_id);
		$calendar_ids = array();
		// get all dates from calendar by current attendance
		foreach ($attendance_calendar as $cal) {
			$calendar_ids[] = $cal['id'];
		}
		// get count of presences by users inside current attendance and save like results
		$count_presences = 0;
		if (count($user_ids) > 0) {
			foreach ($user_ids as $uid) {
				$count_presences = 0;
				if (count($calendar_ids) > 0) {
					$sql = "SELECT count(presence) as count_presences FROM $tbl_attendance_sheet WHERE user_id = '$uid' AND attendance_calendar_id IN(".implode(',',$calendar_ids).") AND presence = 1";
					$rs_count  = Database::query($sql);
					$row_count = Database::fetch_array($rs_count);
					$count_presences = $row_count['count_presences'];
				}
				// save results
				$sql = "SELECT id FROM $tbl_attendance_result WHERE user_id='$uid' AND attendance_id='$attendance_id'";
				$rs_check_result = Database::query($sql);
				if (Database::num_rows($rs_check_result) > 0) {
					// update result
					$sql = "UPDATE $tbl_attendance_result SET score='$count_presences' WHERE user_id='$uid' AND attendance_id='$attendance_id'";
					Database::query($sql);
				} else {
					// insert new result
					$sql = "INSERT INTO $tbl_attendance_result SET user_id='$uid', attendance_id = '$attendance_id', score='$count_presences'";
					Database::query($sql);
				}
			}
		}
		// update attendance qualify max
		$count_done_calendar = $this->get_done_attendance_calendar($attendance_id);
		$sql = "UPDATE $tbl_attendance SET attendance_qualify_max='$count_done_calendar' WHERE id = '$attendance_id'";
		Database::query($sql);
	}

	/**
	 * Get number of done attendances inside current sheet
	 * @param	int	   attendance id
	 * @return 	int	   number of done attendances
	 */
	public function get_done_attendance_calendar($attendance_id) {
		$tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
		$attendance_id = intval($attendance_id);
		$sql = "SELECT count(done_attendance) as count FROM $tbl_attendance_calendar WHERE attendance_id = '$attendance_id' AND done_attendance=1";
		$rs  = Database::query($sql);
		$row = Database::fetch_array($rs);
		$count = $row['count'];
		return $count;
	}

	/**
	 * Get results of faults (absents) by user
	 * @param	int	   user id
	 * @param	int	   attendance id
	 * @return 	array  results containing number of faults, total done attendance, porcent of faults and color depend on result (red, orange)
	 */
	public function get_faults_of_user($user_id, $attendance_id) {
		
		// initializing database table and variables
		$tbl_attendance_result 	= Database::get_course_table(TABLE_ATTENDANCE_RESULT);				
		$user_id = intval($user_id);
		$attendance_id = intval($attendance_id);
		$results = array();
		$attendance_data 		= $this->get_attendance_by_id($attendance_id);
		$total_done_attendance 	= $attendance_data['attendance_qualify_max'];
		$attendance_user_score  = $this->get_user_score($user_id, $attendance_id);
				
		// calculate results
		$faults = $total_done_attendance-$attendance_user_score;
		$faults = $faults > 0 ? $faults:0;
		$faults_porcent = $total_done_attendance > 0 ?round(($faults*100)/$total_done_attendance,0):0;
		$results['faults'] 			= $faults;
		$results['total']			= $total_done_attendance;
		$results['faults_porcent'] 	= $faults_porcent;
		$color_bar = '';
		if ($faults_porcent > 25  ) {
			$color_bar = '#F11';
		} else if ($faults_porcent > 10) {
			$color_bar = '#F90';
		}
		$results['color_bar'] = $color_bar;
		return $results;
	}
	
	/**
	 * Get results of faults average for all courses by user
	 * @param	int	   user id 
	 * @return 	array  results containing number of faults, total done attendance, porcent of faults and color depend on result (red, orange)
	 */
	public function get_faults_average_inside_courses($user_id) {
		
		// get all courses of current user
		$courses = CourseManager::get_courses_list_by_user_id($user_id, true);
		$user_id = intval($user_id);		
		
		$results = array();
		$total_faults = $total_weight = $porcent = 0;
		foreach ($courses as $course) {
			
			$course_code = $course['code'];
			$course_info = api_get_course_info($course_code);
			$tbl_attendance_result 	= Database::get_course_table(TABLE_ATTENDANCE_RESULT, $course_info['dbName']);
			$attendances_by_course = $this->get_attendances_list($course_code);						
			foreach ($attendances_by_course as $attendance) {					
				// get total faults and total weight											
				$attendance_id = $attendance['id'];				
				$total_done_attendance 	= $attendance['attendance_qualify_max'];				
				$sql = "SELECT score FROM $tbl_attendance_result WHERE user_id='$user_id' AND attendance_id='$attendance_id'";
				$rs = Database::query($sql);
				$score = 0;
				if (Database::num_rows($rs) > 0) {
					$row = Database::fetch_array($rs);
					$score = $row['score'];
				}
				$faults = $total_done_attendance-$score;
				$faults = $faults > 0 ? $faults:0;
				$total_faults += $faults;
				$total_weight += $total_done_attendance;														
			}
		}

		$porcent = $total_weight > 0 ?round(($total_faults*100)/$total_weight,0):0;
		$results['faults'] 	= $total_faults;
		$results['total']	= $total_weight;
		$results['porcent'] = $porcent;
		return $results;

	}
	
	/**
	 * Get results of faults average by course
	 * @param	int	   user id 
	 * @return 	array  results containing number of faults, total done attendance, porcent of faults and color depend on result (red, orange)
	 */
	public function get_faults_average_by_course($user_id, $course_code) {

		
		// Database tables and variables
		$course_info = api_get_course_info($course_code);
		$tbl_attendance_result 	= Database::get_course_table(TABLE_ATTENDANCE_RESULT, $course_info['dbName']);		
		$user_id = intval($user_id);				
		$results = array();
		$total_faults = $total_weight = $porcent = 0;
		$attendances_by_course = $this->get_attendances_list($course_code);						
		
		foreach ($attendances_by_course as $attendance) {					
			// get total faults and total weight											
			$attendance_id = $attendance['id'];				
			$total_done_attendance 	= $attendance['attendance_qualify_max'];				
			$sql = "SELECT score FROM $tbl_attendance_result WHERE user_id='$user_id' AND attendance_id='$attendance_id'";
			$rs = Database::query($sql);
			$score = 0;
			if (Database::num_rows($rs) > 0) {
				$row = Database::fetch_array($rs);
				$score = $row['score'];
			}
			$faults = $total_done_attendance-$score;
			$faults = $faults > 0 ? $faults:0;
			$total_faults += $faults;
			$total_weight += $total_done_attendance;														
		}
			
		$porcent = $total_weight > 0 ?round(($total_faults*100)/$total_weight,0):0;
		$results['faults'] 	= $total_faults;
		$results['total']	= $total_weight;
		$results['porcent'] = $porcent;
		return $results;

	}

	/**
	 * Get registered users' attendance sheet inside current course
	 * @param	int	   attendance id
	 * @param	int	   user id for showing data for only one user (optional)
	 * @return 	array  users attendance sheet data
	 */
	public function get_users_attendance_sheet($attendance_id, $user_id = 0) {
		global $dateTimeFormatLong;
		$tbl_attendance_sheet 	= Database::get_course_table(TABLE_ATTENDANCE_SHEET);
		$tbl_attendance_calendar= Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
		$attendance_id = intval($attendance_id);

		$attendance_calendar = $this->get_attendance_calendar($attendance_id);
		$calendar_ids = array();
		// get all dates from calendar by current attendance
		foreach ($attendance_calendar as $cal) {
			$calendar_ids[] = $cal['id'];
		}

		$data = array();
		if (empty($user_id)) {
			// get all registered users inside current course
			$users = $this->get_users_rel_course();
			$user_ids = array_keys($users);
			if (count($calendar_ids) > 0 && count($user_ids) > 0) {
				foreach ($user_ids as $uid) {
					$sql = "SELECT * FROM $tbl_attendance_sheet WHERE user_id = '$uid' AND attendance_calendar_id IN(".implode(',',$calendar_ids).") ";
					$res = Database::query($sql);
					if (Database::num_rows($res) > 0) {
						while ($row = Database::fetch_array($res)) {
							$data[$uid][$row['attendance_calendar_id']]['presence'] = $row['presence'];
						}
					}
				}
			}
		} else {
			// get attendance for current user
			$user_id = intval($user_id);
			if (count($calendar_ids) > 0) {
				$sql = "SELECT cal.date_time, att.presence FROM $tbl_attendance_sheet att INNER JOIN  $tbl_attendance_calendar cal ON cal.id = att.attendance_calendar_id WHERE att.user_id = '$user_id' AND att.attendance_calendar_id IN(".implode(',',$calendar_ids).") ";
				$res = Database::query($sql);
				if (Database::num_rows($res) > 0) {
					while ($row = Database::fetch_array($res)) {
						$row['date_time'] = api_format_date($dateTimeFormatLong, strtotime($row['date_time']));
						$data[$user_id][] = $row;
					}
				}
			}
		}
		return $data;
	}

	/**
	 * Get next attendance calendar without presences (done attendances)
	 * @param	int	attendance id
	 * @return 	int attendance calendar id
	 */
	public function get_next_attendance_calendar_id($attendance_id) {
		$tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
		$attendance_id = intval($attendance_id);
		$sql = "SELECT id FROM $tbl_attendance_calendar WHERE attendance_id = '$attendance_id' AND done_attendance = 0 ORDER BY date_time limit 1";
		$rs = Database::query($sql);
		$next_calendar_id = 0;
		if (Database::num_rows($rs) > 0) {
			$row = Database::fetch_array($rs);
			$next_calendar_id = $row['id'];
		}
		return $next_calendar_id;
	}

	/**
	 * Get user' score from current attendance
	 * @param	int	user id
	 * @param	int attendance id
	 * @return	int score
	 */
	public function get_user_score($user_id, $attendance_id) {
		$tbl_attendance_result 	= Database::get_course_table(TABLE_ATTENDANCE_RESULT);
		$user_id = intval($user_id);
		$attendance_id = intval($attendance_id);
		$sql = "SELECT score FROM $tbl_attendance_result WHERE user_id='$user_id' AND attendance_id='$attendance_id'";
		$rs = Database::query($sql);
		$score = 0;
		if (Database::num_rows($rs) > 0) {
			$row = Database::fetch_array($rs);
			$score = $row['score'];
		}
		return $score;
	}

	/**
	 * Get attendance calendar data by id
	 * @param	int	attendance calendar id
	 * @return	array attendance calendar data
	 */
	public function get_attendance_calendar_by_id($calendar_id) {
		$tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
		$calendar_id = intval($calendar_id);
		$sql = "SELECT * FROM $tbl_attendance_calendar WHERE id = '$calendar_id' ";
		$rs = Database::query($sql);
		$data = array();
		if (Database::num_rows($rs) > 0) {
			while ($row = Database::fetch_array($rs)) {
				$data = $row;
			}
		}
		return $data;
	}

	/**
	 * Get all attendance calendar data inside current attendance
	 * @param	int	attendance id
	 * @return	array attendance calendar data
	 */
	public function get_attendance_calendar($attendance_id) {
		global $dateFormatShort, $timeNoSecFormat;
		$tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
		$attendance_id = intval($attendance_id);
		$sql = "SELECT * FROM $tbl_attendance_calendar WHERE attendance_id = '$attendance_id' ORDER BY date_time ";
		$rs = Database::query($sql);
		$data = array();
		if (Database::num_rows($rs) > 0) {
			while ($row = Database::fetch_array($rs)) {
				$row['date'] = api_format_date($dateFormatShort, strtotime($row['date_time']));
				$row['time'] = api_format_date($timeNoSecFormat, strtotime($row['date_time']));
				$data[] = $row;
			}
		}
		return $data;
	}

	/**
	 * Add new datetime inside attendance calendar table
	 * @param	int	attendance id
	 * @return	int affected rows
	 */
	public function attendant_calendar_add($attendance_id) {
		$tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
		$affected_rows = 0;
		$attendance_id = intval($attendance_id);
		// check if datetime already exists inside the table
		$sql = "SELECT id FROM $tbl_attendance_calendar WHERE date_time='".Database::escape_string($this->date_time)."' AND attendance_id = '$attendance_id'";
		$rs = Database::query($sql);
		if (Database::num_rows($rs) == 0) {
			$sql = "INSERT INTO $tbl_attendance_calendar SET date_time='".Database::escape_string($this->date_time)."', attendance_id = '$attendance_id'";
			Database::query($sql);
			$affected_rows = Database::affected_rows();
		}
		return $affected_rows;
	}

	/**
	 * edit a datetime inside attendance calendar table
	 * @param	int	attendance calendar id
	 * @param	int	attendance id
	 * @return	int affected rows
	 */
	public function attendant_calendar_edit($calendar_id, $attendance_id) {
		$tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
		$affected_rows = 0;
		$attendance_id = intval($attendance_id);
		// check if datetime already exists inside the table
		$sql = "SELECT id FROM $tbl_attendance_calendar WHERE date_time = '".Database::escape_string($this->date_time)."' AND attendance_id = '$attendance_id'";
		$rs = Database::query($sql);
		if (Database::num_rows($rs) == 0) {
			$sql = "UPDATE $tbl_attendance_calendar SET date_time='".Database::escape_string($this->date_time)."' WHERE id = '".intval($calendar_id)."'";
			Database::query($sql);
			$affected_rows = Database::affected_rows();
		}
		return $affected_rows;
	}

	/**
	 * delete a datetime from attendance calendar table
	 * @param	int		attendance calendar id
	 * @param	int		attendance id
	 * @param	bool	true for removing all calendar inside current attendance, false for removing by calendar id
	 * @return	int affected rows
	 */
	public function attendance_calendar_delete($calendar_id, $attendance_id , $all_delete = false) {
		$tbl_attendance_calendar = Database::get_course_table(TABLE_ATTENDANCE_CALENDAR);
		$tbl_attendance_sheet 	 = Database::get_course_table(TABLE_ATTENDANCE_SHEET);
		$session_id = api_get_session_id();
		$attendance_id = intval($attendance_id);
		// get all registered users inside current course
		$users = $this->get_users_rel_course();
		$user_ids = array_keys($users);

		if ($all_delete) {
			$attendance_calendar = $this->get_attendance_calendar($attendance_id);
			$calendar_ids = array();
			// get all dates from calendar by current attendance
			if (!empty($attendance_calendar)) {
				foreach ($attendance_calendar as $cal) {
					// delete all data from attendance sheet
					$sql = "DELETE FROM $tbl_attendance_sheet WHERE attendance_calendar_id = '".intval($cal['id'])."'";
					Database::query($sql);
					// delete data from attendance calendar
					$sql = "DELETE FROM $tbl_attendance_calendar WHERE id = '".intval($cal['id'])."'";
					Database::query($sql);
				}
			}
		} else {
			// delete just one row from attendance sheet by the calendar id
			$sql = "DELETE FROM $tbl_attendance_sheet WHERE attendance_calendar_id = '".intval($calendar_id)."'";
			Database::query($sql);
			// delete data from attendance calendar
			$sql = "DELETE FROM $tbl_attendance_calendar WHERE id = '".intval($calendar_id)."'";
			Database::query($sql);
		}

		$affected_rows = Database::affected_rows();
		// update users' results
		$this->update_users_results($user_ids, $attendance_id);
		return $affected_rows;
	}

	/**
	 * buid a string datetime from array
	 * @param	array	array containing data e.g: e.g: $array('Y'=>'2010',  'F' => '02', 'd' => '10', 'H' => '12', 'i' => '30')
	 * @return	string	date and time e.g: '2010-02-10 12:30:00'
	 */
	public function build_datetime_from_array($array) {
		$year	 = '0000';
		$month = $day = $hours = $minutes = $seconds = '00';
		if (isset($array['Y']) && isset($array['F']) && isset($array['d']) && isset($array['H']) && isset($array['i'])) {
			$year = $array['Y'];
			$month = $array['F'];
			if (intval($month) < 10 ) $month = '0'.$month;
			$day = $array['d'];
			if (intval($day) < 10 ) $day = '0'.$day;
			$hours = $array['H'];
			if (intval($hours) < 10 ) $hours = '0'.$hours;
			$minutes = $array['i'];
			if (intval($minutes) < 10 ) $minutes = '0'.$minutes;
		}
		if (checkdate($month,$day,$year)) {
			$datetime = $year.'-'.$month.'-'.$day.' '.$hours.':'.$minutes.':'.$seconds;
		}
		return $datetime;
	}

	/** Setters for fields of attendances tables **/
	public function set_session_id($session_id) {
		$this->session_id = $session_id;
	}

	public function set_course_id($course_id) {
		$this->course_id = $course_id;
	}

	public function set_date_time($datetime) {
		$this->date_time = $datetime;
	}

	public function set_name($name) {
		$this->name = $name;
	}

	public function set_description($description) {
		$this->description = $description;
	}

	public function set_attendance_qualify_title($attendance_qualify_title) {
		$this->attendance_qualify_title = $attendance_qualify_title;
	}

	public function set_attendance_weight($attendance_weight) {
		$this->attendance_weight = $attendance_weight;
	}

	/** Getters for fields of attendances tables **/
	public function get_session_id() {
		return $this->session_id;
	}

	public function get_course_id() {
		return $this->course_id;
	}

	public function get_date_time() {
		return $this->date_time;
	}

	public function get_name($name) {
		return $this->name;
	}

	public function get_description($description) {
		return $this->description;
	}

	public function get_attendance_qualify_title($attendance_qualify_title) {
		return $this->attendance_qualify_title;
	}

	public function get_attendance_weight($attendance_weight) {
		return $this->attendance_weight;
	}

}
?>
