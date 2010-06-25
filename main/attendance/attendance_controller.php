<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains class used like controller, it should be included inside a dispatcher file (e.g: index.php)
 * 
 * !!! WARNING !!! : ALL DATES IN THIS MODULE ARE STORED IN UTC ! DO NOT CONVERT DURING THE TRANSITION FROM CHAMILO 1.8.x TO 2.0
 * 
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
			
			if (!empty($_POST['title'])) {
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
	    			$last_id = $attendance->attendance_add($link_to_gradebook);    			   			
		        	Security::clear_token();	        		
	    		}
    			$param_gradebook = '';
				if (isset($_SESSION['gradebook'])) {
					$param_gradebook = '&gradebook='.Security::remove_XSS($_SESSION['gradebook']);
				}
    			header('location:index.php?action=attendance_sheet_list&attendance_id='.$last_id.'&'.api_get_cidreq().$param_gradebook);
    			exit;    			    							     							 
			} else {
				$data['error'] = true;
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
			
			if (!empty($_POST['title'])) {
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
					$last_id = $attendance->attendance_edit($attendance_id,$link_to_gradebook);				    			
		        	Security::clear_token();
		        	
		        	$param_gradebook = '';
					if (isset($_SESSION['gradebook'])) {
						$param_gradebook = '&gradebook='.Security::remove_XSS($_SESSION['gradebook']);
					}
	    			header('location:index.php?action=attendance_list&'.api_get_cidreq().$param_gradebook);
	    			exit;  	        		
	    		}
			} else {
				$data['attendance_id'] = $_POST['attendance_id'];
				$data['error'] = true;
				$this->view->set_data($data);
				$this->view->set_layout('layout'); 
				$this->view->set_template('attendance_edit');		       
				$this->view->render();				
			}
    		
    		
		} else {
			
			// default values
			$attendance_data 		= $attendance->get_attendance_by_id($attendance_id);
			$data['attendance_id'] 	= $attendance_data['id'];
			$data['title'] 			= $attendance_data['name'];
			$data['description'] 	= $attendance_data['description'];
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

			if (isset($_POST['hidden_input'])) {
				foreach ($_POST['hidden_input'] as $cal_id) {
					$users_present = array();
					if (isset($_POST['check_presence'][$cal_id])) {
						$users_present = $_POST['check_presence'][$cal_id];
					}
					$affected_rows = $attendance->attendance_sheet_add($cal_id,$users_present,$attendance_id);					
				}
			}

			$data['users_in_course'] 			 = $attendance->get_users_rel_course($attendance_id);
			$data['attendant_calendar'] 		 = $attendance->get_attendance_calendar($attendance_id);
			$data['users_presence'] 			 = $attendance->get_users_attendance_sheet($attendance_id);
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
										
                                        if (isset($_POST['repeat'])) {
                                            $start_datetime = api_strtotime($attendance->build_datetime_from_array($_POST['date_time']));

                                            
                                            $_POST['end_date_time']['H'] = $_POST['date_time']['H'];
                                            $_POST['end_date_time']['i'] = $_POST['date_time']['i'];

                                            $end_datetime = api_strtotime($attendance->build_datetime_from_array($_POST['end_date_time']));
                                            $checkdate = checkdate($_POST['end_date_time']['F'], $_POST['end_date_time']['d'], $_POST['end_date_time']['Y']);

                                            $repeat_type = $_POST['repeat_type'];
                                            if (($end_datetime > $start_datetime) && $checkdate) {
                                                    $affected_rows = $attendance->attendance_repeat_calendar_add($attendance_id, $start_datetime, $end_datetime, $repeat_type);
                                                    $action = 'calendar_list';
                                                
                                            } else {

                                                if (!$checkdate) {
                                                    $data['error_checkdate'] = true;
                                                } else {
                                                    $data['error_repeat_date'] = true;
                                                }
          
                                                $data['repeat'] = true;                                                
                                                $action = 'calendar_add';
                                            }
                                        } else {
                                            $datetime = $attendance->build_datetime_from_array($_POST['date_time']);
                                            $datetimezone = api_get_utc_datetime($datetime);
                                            if (!empty($datetime)) {
						$attendance->set_date_time($datetimezone);
						$affected_rows = $attendance->attendance_calendar_add($attendance_id);
                                                $action = 'calendar_list';
                                            } else {
                                                    $data['error_date'] = true;
                                                    $action = 'calendar_add';
                                            }

                                        }

				} else {
					$action = 'calendar_list';
				}
			}			
		} else if ($action == 'calendar_edit') {
			$data['calendar_id'] = $calendar_id;
			if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {					
				if (!isset($_POST['cancel'])) {										
					$datetime = $attendance->build_datetime_from_array($_POST['date_time']);									
					$datetimezone = api_get_utc_datetime($datetime);								
					$attendance->set_date_time($datetimezone);					
					$affected_rows = $attendance->attendance_calendar_edit($calendar_id, $attendance_id);
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
