<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains class used like controller for thematic, it should be included inside a dispatcher file (e.g: index.php)
 * 
 * !!! WARNING !!! : ALL DATES IN THIS MODULE ARE STORED IN UTC ! DO NOT CONVERT DURING THE TRANSITION FROM CHAMILO 1.8.x TO 2.0
 * 
 * @author Christian Fasanando <christian1827@gmail.com>
 * @package chamilo.course_progress
 */

/**
 * Thematic Controller script. Prepares the common background variables to give to the scripts corresponding to
 * the requested action
 * @package chamilo.course_progress 
 */

class ThematicController
{
	
	/**
	 * Constructor
	 */
	public function __construct() {		
		$this->toolname = 'course_progress';
		$this->view = new View($this->toolname);			
	}
	
	/**
	 * This method is used for thematic control (update, insert or listing)
	 * @param 	string	Action
	 * render to thematic.php 
	 */	
	public function thematic($action) {		
		$thematic= new Thematic();		        
		$data = array();		
		$error = false;
		$msg_add = false;
		
		// insert or update a thematic		
		if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
			if (isset($_POST['action']) && ($_POST['action'] == 'thematic_add' || $_POST['action'] == 'thematic_edit')) {				
				if (trim($_POST['title']) !== '') {
		    		if ($_POST['thematic_token'] == $_SESSION['thematic_token']) {
                        if (api_is_allowed_to_edit(null, true)) {				    						    			
    		    			$id            = $_POST['thematic_id'];
    		    			$title         = $_POST['title'];
    		    			$content       = $_POST['content'];
    		    			$session_id    = api_get_session_id();
    		    			$thematic->set_thematic_attributes($id, $title, $content, $session_id);	    			
    						$last_id       = $thematic->thematic_save();
    						if ($_POST['action'] == 'thematic_add') {
    							$action = 'thematic_details';
    							if ($last_id) {
    								$data['last_id'] = $last_id;
    							}
    						} else {
    							$action = 'thematic_list';
    						}
                        }									    			
			        	unset($_SESSION['thematic_token']);	        			        			
		    		}
				} else {					
					$error = true;
					$data['error'] = $error;	
					$data['action'] = $_POST['action'];	
					$data['thematic_id'] = $_POST['thematic_id'];
					// render to the view
					$this->view->set_data($data);
					$this->view->set_layout('layout'); 
					$this->view->set_template('thematic');		       
					$this->view->render();					    						    									
				}
			}				    		
		}
		
		//Copy a thematic to a session
		if ($action == 'thematic_copy') {
		    $thematic->copy($_REQUEST['thematic_id']);
		    $action = 'thematic_list';
		}								

		// delete many thematics
		if ($action == 'thematic_delete_select') {
			if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
				if (api_is_allowed_to_edit(null, true)) {				
					$thematic_ids = $_POST['id'];
					$affected_rows = $thematic->thematic_destroy($thematic_ids);
				}
				$action = 'thematic_list';
			}
		}
		
		
		$thematic_id = isset($_GET['thematic_id'])?intval($_GET['thematic_id']):null;

		if (isset($thematic_id)) {				
			// delete a thematic					
			if ($action == 'thematic_delete') {
                if (api_is_allowed_to_edit(null, true)) {
				    $affected_rows = $thematic->thematic_destroy($thematic_id);
                }
				$action = 'thematic_list';
			}				
			// move thematic
			if ($action == 'moveup') {
				$thematic->move_thematic('up', $thematic_id);
				$action = 'thematic_list';
			} else if ($action == 'movedown') {
				$thematic->move_thematic('down', $thematic_id);
				$action = 'thematic_list';
			}					
			$data['thematic_data'] = $thematic->get_thematic_list($thematic_id);				
			$data['thematic_id']   = $thematic_id;										
		}

		if ($action == 'thematic_details') {
			
			if (isset($thematic_id)) {
				$thematic_data[$thematic_id]        = $thematic->get_thematic_list($thematic_id);
				$data['total_average_of_advances']  = $thematic->get_average_of_advances_by_thematic($thematic_id);				
			} else {
				$thematic_data                      = $thematic->get_thematic_list(null, api_get_course_id(), api_get_session_id());				
				
				$data['last_done_thematic_advance'] = $thematic->get_last_done_thematic_advance();
				$data['total_average_of_advances']  = $thematic->get_total_average_of_thematic_advances();
			}
            
            //Second column
			$thematic_plan_data              = $thematic->get_thematic_plan_data();   			 
                   
            //Third column
			$thematic_advance_data           = $thematic->get_thematic_advance_list();
			
			$data['thematic_plan_data']      = $thematic_plan_data;			
			$data['thematic_advance_data']   = $thematic_advance_data;
			$data['thematic_data']           = $thematic_data;
		}
        
        $data['default_thematic_plan_title'] = $thematic->get_default_thematic_plan_title();
        

		$data['action'] = $action;		
		// render to the view
		$this->view->set_data($data);
		$this->view->set_layout('layout'); 
		$this->view->set_template('thematic');		       
		$this->view->render();		
	}
	
	/**
	 * This method is used for thematic plan control (update, insert or listing)
	 * @param 	string	Action
	 * render to thematic_plan.php 
	 */	
	public function thematic_plan($action) {
		$thematic= new Thematic();
	 
		$data = array();
		$error = false;
	
		if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {
    		if (isset($_POST['action']) && ($_POST['action'] == 'thematic_plan_add' || $_POST['action'] == 'thematic_plan_edit')) {            
                if (trim($_POST['title']) !== '') {             
                    if ($_POST['thematic_plan_token'] == $_SESSION['thematic_plan_token']) {
                        if (api_is_allowed_to_edit(null, true)) {                       			    						    				    			
                			$thematic_id         = $_POST['thematic_id'];
                			$title               = $_POST['title'];
                			$description         = $_POST['description'];
                			$description_type    = $_POST['description_type'];
                			$thematic->set_thematic_plan_attributes($thematic_id, $title, $description, $description_type);	    			
            				$affected_rows       = $thematic->thematic_plan_save();			        	
            				unset($_SESSION['thematic_plan_token']);        
            	        	
                            $data['message'] = 'ok';
                        }        	
                        $data['action'] = 'thematic_plan_list';
            		}
                } else {                         		
					$error = true;
					$action                        = $_POST['action'];
					$data['error']                 = $error;						
					$data['thematic_plan_data']    = $thematic->get_thematic_plan_data($_POST['thematic_id'], $_POST['description_type']);
					$data['thematic_id']           = $_POST['thematic_id'];
					$data['description_type']      = $_POST['description_type'];
					$data['action']                = $action;
					$data['default_thematic_plan_title']       = $thematic->get_default_thematic_plan_title();
					$data['default_thematic_plan_icon']        = $thematic->get_default_thematic_plan_icon();
					$data['default_thematic_plan_question']    = $thematic->get_default_question();
					$data['next_description_type']             = $thematic->get_next_description_type($_POST['thematic_id']);
							
					// render to the view
					$this->view->set_data($data);
					$this->view->set_layout('layout'); 
					$this->view->set_template('thematic_plan');		       
					$this->view->render();               									    															
    			}	    			
            } else if($_POST['action'] == 'thematic_plan_list') {
                
                
                $title_list         = $_POST['title'];
                $description_list   = $_POST['description'];
                $description_type   = $_POST['description_type'];
                if (api_is_allowed_to_edit(null, true)) { 
                    for($i=1;$i<count($title_list)+1; $i++) {
                        //if (!empty($description_list[$i])) {
                            $thematic->set_thematic_plan_attributes($_POST['thematic_id'], $title_list[$i], $description_list[$i], $description_type[$i]);                 
                            $affected_rows = $thematic->thematic_plan_save();
                        //}   
                    }
                    $data['message'] = 'ok';
                }
            } 
    	}		
    							
        											
    	if ($action == 'thematic_plan_list') {
    		$data['thematic_plan_data'] = $thematic->get_thematic_plan_data($thematic_id);            
    	}		
    	$thematic_id = intval($_GET['thematic_id']);
    	$description_type = intval($_GET['description_type']);
    
    	if (!empty($thematic_id) && !empty($description_type)) {
    		if ($action == 'thematic_plan_delete') {   
                if (api_is_allowed_to_edit(null, true)) {
                    $affected_rows = $thematic->thematic_plan_destroy($thematic_id, $description_type);
                }				
    			$data['thematic_plan_data'] = $thematic->get_thematic_plan_data($thematic_id);
    			$action = 'thematic_plan_list';
    		} else {         
    			$data['thematic_plan_data'] = $thematic->get_thematic_plan_data($thematic_id, $description_type);	
    		}											
    		$data['thematic_id'] = $thematic_id;
    		$data['description_type'] = $description_type;							
    	} else if (!empty($thematic_id) && $action == 'thematic_plan_list') {
 
    		$data['thematic_plan_data'] = $thematic->get_thematic_plan_data($thematic_id);
    		$data['thematic_id'] = $thematic_id;
    	}
        
    	              
    	$data['thematic_id'] = $thematic_id;
    	$data['action'] = $action;
    	$data['default_thematic_plan_title']      = $thematic->get_default_thematic_plan_title();
    	$data['default_thematic_plan_icon']       = $thematic->get_default_thematic_plan_icon();		
    	$data['next_description_type']            = $thematic->get_next_description_type($thematic_id);		
    	$data['default_thematic_plan_question']   = $thematic->get_default_question();        
        $data['thematic_data']                    = $thematic->get_thematic_list($thematic_id);
        
    			
    	// render to the view
    	$this->view->set_data($data);
    	$this->view->set_layout('layout'); 
    	$this->view->set_template('thematic_plan');		       
    	$this->view->render();
        exit;
    }
    	
    /**
     * This method is used for thematic advance control (update, insert or listing)
     * @param 	string	Action
     * render to thematic_advance.php 
     */	
    public function thematic_advance($action) {
    	
    	$thematic= new Thematic();	
    	$attendance = new Attendance();
    	$data = array();
    			
    	// get data for attendance input select		
    	$attendance_list = $attendance->get_attendances_list();		
    	$attendance_select = array();
    	$attendance_select[0] = get_lang('SelectAnAttendance');
    	foreach ($attendance_list as $attendance_id => $attendance_data) {
    		$attendance_select[$attendance_id] = $attendance_data['name'];
    	}
    
    	if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {				
    		if (isset($_POST['action']) && ($_POST['action'] == 'thematic_advance_add' || $_POST['action'] == 'thematic_advance_edit')) {
        		if (($_POST['start_date_type'] == 1 && empty($_POST['start_date_by_attendance'])) || (!empty($_POST['duration_in_hours']) && !is_numeric($_POST['duration_in_hours'])) ) {	    			
        			
        			if ($_POST['start_date_type'] == 1 && empty($_POST['start_date_by_attendance'])) {
        				$start_date_error = true;
    					$data['start_date_error'] = $start_date_error;	
        			} 
        			
        			if (!empty($_POST['duration_in_hours']) && !is_numeric($_POST['duration_in_hours'])) {
        				$duration_error = true;
    					$data['duration_error'] = $duration_error;	
        			}
    
    				$data['action'] = $_POST['action'];	
    				$data['thematic_id'] = $_POST['thematic_id'];				
    				$data['attendance_select'] = $attendance_select;					
    				if (isset($_POST['thematic_advance_id'])) {
    					$data['thematic_advance_id'] = $_POST['thematic_advance_id'];
    					$thematic_advance_data = $thematic->get_thematic_advance_list($_POST['thematic_advance_id']);
    					$data['thematic_advance_data'] = $thematic_advance_data;		
    				}	
    				// render to the view
    				$this->view->set_data($data);
    				$this->view->set_layout('layout'); 
    				$this->view->set_template('thematic_advance');		       
    				$this->view->render();	    			
        		} else {	
    	    		if ($_POST['thematic_advance_token'] == $_SESSION['thematic_advance_token'] && api_is_allowed_to_edit(null, true)) {						    						    			
    	    			$thematic_advance_id = $_POST['thematic_advance_id'];
    	    			$thematic_id 	= $_POST['thematic_id'];	    			
    	    			$content 		= $_POST['content'];		    			
    	    			$duration		= $_POST['duration_in_hours'];
    	    			if (isset($_POST['start_date_type']) && $_POST['start_date_type'] == 2) {
    	    				$start_date 	= $thematic->build_datetime_from_array($_POST['custom_start_date']);
    	    				$attendance_id 	= 0;	
    	    			} else {
    	    				$start_date 	= $_POST['start_date_by_attendance'];
    	    				$attendance_id 	= $_POST['attendance_select'];
    	    			}		    			
    	    			$thematic->set_thematic_advance_attributes($thematic_advance_id, $thematic_id,  $attendance_id, $content, $start_date, $duration);	    				    								
    					$affected_rows = $thematic->thematic_advance_save();			        	
    		        	if ($affected_rows) {
    		        		// get last done thematic advance before move thematic list
    						$last_done_thematic_advance = $thematic->get_last_done_thematic_advance();
    						// update done advances with de current thematic list
    						if (!empty($last_done_thematic_advance)) {
    							$update_done_advances = $thematic->update_done_thematic_advances($last_done_thematic_advance);
    						}
    		        	}			        	
    					unset($_SESSION['thematic_advance_token']);	        
    		        	$action = 'thematic_advance_list';		
    	    		}
        		}	    		
    		}	    		
    	}		
		
		$thematic_id = intval($_GET['thematic_id']);
		$thematic_advance_id = intval($_GET['thematic_advance_id']);
		$thematic_advance_data = array();
		if (!empty($thematic_advance_id)) {			
			if ($action == 'thematic_advance_delete') {
                if (api_is_allowed_to_edit(null, true)) {
				    $affected_rows = $thematic->thematic_advance_destroy($thematic_advance_id);
                }
				$action = 'thematic_advance_list';				
			} else {
				$thematic_advance_data = $thematic->get_thematic_advance_list($thematic_advance_id);	
			}									
		}
		
		// get calendar select by attendance id
		$calendar_select = array();
		if (!empty($thematic_advance_data)) {			
			if (!empty($thematic_advance_data['attendance_id'])) {
				$attendance_calendar = $attendance->get_attendance_calendar($thematic_advance_data['attendance_id']);				
				if (!empty($attendance_calendar)) {								
					foreach ($attendance_calendar as $calendar) {
						$calendar_select[$calendar['date_time']] = $calendar['date_time'];	
					}					
				}
			}			
		}
		
		$data['action'] = $action;
		$data['thematic_id'] = $thematic_id;
		$data['thematic_advance_id'] = $thematic_advance_id;
		$data['attendance_select'] = $attendance_select;
		$data['thematic_advance_data'] = $thematic_advance_data;
		$data['calendar_select'] = $calendar_select;
		
		// render to the view
		$this->view->set_data($data);
		$this->view->set_layout('layout'); 
		$this->view->set_template('thematic_advance');		       
		$this->view->render();		
	}
}