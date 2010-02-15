<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains class used like controller, it should be included inside a dispatcher file (e.g: index.php)
 * @author Christian Fasanando <christian1827@gmail.com>
 * @package chamilo.attendance
 */

/**
 * Controller script. Prepares the common background variables to give to the scripts corresponding to
 * the requested action
 * @package chamilo.attendance 
 */
 
 class AttendanceController 
 {
 	
 	/**
	 * Constructor
	 */
	public function __construct() {		
		$this->toolname = 'attendance';	
		$this->view = new View($this->toolname);			
	}
	
	/**
	 * It's used for listing attendace,
	 * render to attendance_list view
	 * @param boolean   true for listing history (optional)
	 * @param array 	message for showing by action['edit','add','delete'] (optional) 
	 */
	public function attendance_list($history=false,$messages=array()) {
		
		$attendance = new Attendance();        
		$data = array();		
		
		// render to the view
		$this->view->set_data($data);
		$this->view->set_layout('layout'); 
		$this->view->set_template('attendance_list');		       
		$this->view->render();				
	}
	
	/**
	 * It's used for adding attendace,
	 * render to attendance_add or attendance_list view
	 */
	public function attendance_add() {
		
		$attendance = new Attendance();        
		$data = array();
				
		if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {

			$check = Security::check_token();        		
    		if ($check) {
    			$attendance->set_name($_POST['title']);
    			$attendance->set_description($_POST['title']);
    			$attendance->set_attendance_qualify_title($_POST['attendance_qualify_title']);
    			$attendance->set_attendance_weight($_POST['attendance_weight']);
    			$link_to_gradebook = false;    			    			
				if ( isset($_POST['attendance_qualify_gradebook']) && $_POST['attendance_qualify_gradebook'] == 1 ) {
					$link_to_gradebook = true;	
				}    			    			
    			$last_id = $attendance->attendance_add($link_to_gradebook);    			   			
	        	Security::clear_token();	        		
    		}
    		 
    		if ($last_id) {        			
				$this->attendance_sheet('calendar_list',$last_id);     			
			} else {
				$data['error_attendance_add'] = true;
				$this->view->set_data($data);
				$this->view->set_layout('layout'); 
				$this->view->set_template('attendance_add');		       
				$this->view->render();
			}

		} else {
			$this->view->set_data($data);
			$this->view->set_layout('layout'); 
			$this->view->set_template('attendance_add');		       
			$this->view->render();
		}

	} 
	
	/**
	 * It's used for editing attendace,
	 * render to attendance_edit or attendance_list view
	 * @param int	attendance id
	 */
	public function attendance_edit($attendance_id) {		
		$attendance = new Attendance();        
		$data = array();
		$attendance_id = intval($attendance_id);				
		if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
			$check = Security::check_token();        		
    		if ($check) {
    			$attendance->set_name($_POST['title']);
    			$attendance->set_description($_POST['description']);
    			$attendance->set_attendance_qualify_title($_POST['attendance_qualify_title']);
    			$attendance->set_attendance_weight($_POST['attendance_weight']);
    			
    			$link_to_gradebook = false;    			    			
				if ( isset($_POST['attendance_qualify_gradebook']) && $_POST['attendance_qualify_gradebook'] == 1 ) {
					$link_to_gradebook = true;	
				}    			    			
				$affected_rows = $attendance->attendance_edit($attendance_id,$link_to_gradebook);				    			
	        	Security::clear_token();	        		
    		}
    		
    		
    		
    		if ($affected_rows) {
    			$data['message_edit'] = true;
    		}
    		$this->attendance_list();
		} else {
			// default values
			$attendance_data = $attendance->get_attendance_by_id($attendance_id);
			$data['attendance_id'] = $attendance_data['id'];
			$data['title'] = $attendance_data['name'];
			$data['description'] = $attendance_data['description'];
			$data['attendance_qualify_title'] = $attendance_data['attendance_qualify_title'];
			$data['attendance_weight'] = $attendance_data['attendance_weight'];

			$this->view->set_data($data);
			$this->view->set_layout('layout'); 
			$this->view->set_template('attendance_edit');		       
			$this->view->render();
		}
	} 
	
	/**
	 * It's used for delete attendace,
	 * render to attendance_list view
	 * @param int	attendance id
	 */
	public function attendance_delete($attendance_id) {
		$attendance = new Attendance();   
		//$attendance_id = intval($attendance_id);
		if (!empty($attendance_id)) {
			$affected_rows = $attendance->attendance_delete($attendance_id);
		}		
		if ($affected_rows) {        			
			$message['message_attendance_delete'] = true;        			
		}
		$this->attendance_list();	
	}
									
	/**
	 * It's used for controlling attendace sheet (list, add),
	 * render to attendance_sheet view
	 * @param string action
	 * @param int	 attendance id
	 */
	public function attendance_sheet($action, $attendance_id, $student_id = 0) {		
		$attendance = new Attendance();				        
		$data = array();
		$data['attendance_id'] = $attendance_id;
		$data['users_in_course'] = $attendance->get_users_rel_course($attendance_id);
		$data['attendant_calendar'] = $attendance->get_attendance_calendar($attendance_id);

		if (api_is_allowed_to_edit(null, true)) {
			$data['users_presence'] = $attendance->get_users_attendance_sheet($attendance_id);	
		} else {
			if (!empty($student_id)) {
				$user_id = intval($student_id);
			} else {
				$user_id = api_get_user_id();	
			}						
			$data['users_presence'] = $attendance->get_users_attendance_sheet($attendance_id, $user_id);
			$data['faults'] = $attendance->get_faults_of_user($user_id, $attendance_id);
			$data['user_id'] = $user_id;			
		}
				
		$data['next_attendance_calendar_id'] = $attendance->get_next_attendance_calendar_id($attendance_id);
		
		if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {	
			$presences = array();
			$check_values = array();
			$users_present = array();

			if (isset($_POST['check_presence'])) {
				$presences = $_POST['check_presence'];
				$calendar_tmp = array();
				foreach ($presences as $presence) {						
					$presence_split = explode('_',$presence);
					$calendar_id = $presence_split[1];
					$user_id = $presence_split[3];
					$calendar_tmp[] = $calendar_id;					
					if (in_array($calendar_id, $calendar_tmp)) {
						$check_values[$calendar_id][] = $user_id;		
					}
				}
				// save when is present at least one user
				foreach ($check_values as $cal_id => $value) {
					$users_present = $value;				
					$affected_rows = $attendance->attendance_sheet_add($cal_id,$users_present,$attendance_id);								
				}
								
			} else {
				// save attendance done with all absents students				
				if (isset($_POST['datetime_column'])) {
					foreach ($_POST['datetime_column'] as $key=>$date_time_col) {
						$cal_id = $key;
						$affected_rows = $attendance->attendance_sheet_add($cal_id,$users_present,$attendance_id);
					}	
				}
			}		
					
			$data['users_in_course'] = $attendance->get_users_rel_course($attendance_id);
			$data['attendant_calendar'] = $attendance->get_attendance_calendar($attendance_id);
			$data['users_presence'] = $attendance->get_users_attendance_sheet($attendance_id);
			$data['next_attendance_calendar_id'] = $attendance->get_next_attendance_calendar_id($attendance_id);			
		}
		
		$this->view->set_data($data);
		$this->view->set_layout('layout'); 
		$this->view->set_template('attendance_sheet');		       
		$this->view->render(); 
	}

	/**
	 * It's used for controlling attendace calendar (list, add, edit, delete),
	 * render to attendance_calendar view
	 * @param string action (optional, by default 'calendar_list')
	 * @param int	 attendance id (optional)
	 * @param int	 calendar id (optional)
	 */
	public function attendance_calendar($action = 'calendar_list',$attendance_id = 0, $calendar_id = 0) {

		$attendance = new Attendance();
		$calendar_id = intval($calendar_id);       
		$data = array();
		$data['attendance_id'] = $attendance_id;
		$attendance_id = intval($attendance_id);

		if ($action == 'calendar_add') {			
			if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {	
				if (!isset($_POST['cancel'])) {								
					$datetime = $attendance->build_datetime_from_array($_POST['date_time']);
					if (!empty($datetime)) {					
						$attendance->set_date_time($datetime);
						$affected_rows = $attendance->attendant_calendar_add($attendance_id);
					} else {
						$data['error_date'] = true;
					}
					$action = 'calendar_list';
				} else {
					$action = 'calendar_list';
				}
			}			
		} else if ($action == 'calendar_edit') {
			$data['calendar_id'] = $calendar_id;
			if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {					
				if (!isset($_POST['cancel'])) {					
					$datetime = $attendance->build_datetime_from_array($_POST['date_time']);
					$attendance->set_date_time($datetime);				
					$affected_rows = $attendance->attendant_calendar_edit($calendar_id, $attendance_id);
					$data['calendar_id'] = 0;
					$action = 'calendar_list';
				} else {
					$action = 'calendar_list';
				} 			
			}
		} else if ($action == 'calendar_delete') {
			$affected_rows = $attendance->attendance_calendar_delete($calendar_id, $attendance_id);
			$action = 'calendar_list';
		} else if ($action == 'calendar_all_delete') {
			$affected_rows = $attendance->attendance_calendar_delete(0, $attendance_id, true);
			$action = 'calendar_list';
		}

		$data['action'] = $action;				
		$data['attendance_calendar'] = $attendance->get_attendance_calendar($attendance_id);					
		// render to the view
		$this->view->set_data($data);
		$this->view->set_layout('layout'); 
		$this->view->set_template('attendance_calendar');		       
		$this->view->render();				
	}
 	
 }


?>
