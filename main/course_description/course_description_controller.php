<?php
/* For licensing terms, see /license.txt */

/**
 * This file contains class used like controller, it should be included inside a dispatcher file (e.g: index.php)
 * @author Christian Fasanando <christian1827@gmail.com>
 * @package chamilo.course_description
 */

/**
 * Controller script. Prepares the common background variables to give to the scripts corresponding to
 * the requested action
 * @package chamilo.course_description 
 */
class CourseDescriptionController { // extends Controller {	
		
	private $toolname;    
	private $view; 
	
	/**
	 * Constructor
	 */
	public function __construct() {		
		$this->toolname = 'course_description';	
		$this->view = new View($this->toolname);			
	}

	/**
	 * It's used for listing course description,
	 * render to listing view
	 * @param boolean   	true for listing history (optional)
	 * @param array 	message for showing by action['edit','add','destroy'] (optional) 
	 */
	public function listing($history=false, $messages=array()) {
		$course_description = new CourseDescription();
		$session_id = api_get_session_id();
		$course_description->set_session_id($session_id);        
		$data = array();		

		$course_description_data = $course_description->get_description_data();	
        	
		$data['descriptions'] = $course_description_data['descriptions'];
		$data['default_description_titles'] = $course_description->get_default_description_title();
		$data['default_description_title_editable'] = $course_description->get_default_description_title_editable();
		$data['default_description_icon'] = $course_description->get_default_description_icon();		
		$data['messages'] = $messages;
		
		// render to the view
		$this->view->set_data($data);
		$this->view->set_layout('layout'); 
		$this->view->set_template('listing');		       
		$this->view->render();				
	}
	
	/**
	 * It's used for editing a course description,
	 * render to listing or edit view
	 * @param int description type
	 */
	public function edit($id, $description_type) {
		$course_description = new CourseDescription();
		$session_id = api_get_session_id();
		$course_description->set_session_id($session_id);		
		$data = array();      
        $data['id'] = $id;
        if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {    	
        	if (!empty($_POST['title']) && !empty($_POST['contentDescription'])) {
        		
        		$check = Security::check_token();        		
        		if ($check) {
        			$title = $_POST['title'];
		        	if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
						$content = WCAG_Rendering::prepareXHTML();
					} else {
						$content = $_POST['contentDescription'];
					}        	
		        	$description_type = $_POST['description_type'];
                    $id         = $_POST['id'];
		        	$progress   = $_POST['progress'];
                    
					$course_description->set_description_type($description_type);
		    		$course_description->set_title($title);
		    		$course_description->set_content($content);
                    
		    		$course_description->set_progress($progress);                   
		           			        		
		        	$thematic_advance = $course_description->get_data_by_id($id);			        		        		
                    
                    if (!empty($thematic_advance)) {		   		        			
                        $course_description->set_id($id);
                        $affected_rows = $course_description->update();	        			
                    } else {
                        $affected_rows = $course_description->insert();
                    }
		        	Security::clear_token();		        		        		
        		}
        		
        		if ($affected_rows) {        			
					$message['edit'] = true;        			
				}
				$this->listing(false,$message);		        		        		
        		      		
        	} else {
        		$data['error'] = 1;
        		$data['default_description_titles'] = $course_description->get_default_description_title();
				$data['default_description_title_editable'] = $course_description->get_default_description_title_editable();
				$data['default_description_icon'] = $course_description->get_default_description_icon();
				$data['question'] = $course_description->get_default_question();
				$data['information'] = $course_description->get_default_information();
        		$data['description_title'] = $_POST['title'];
        		$data['description_content'] = $_POST['contentDescription'];
        		$data['description_type'] = $_POST['description_type'];
        		$data['progress'] = $_POST['progress'];
        		$data['descriptions'] = $course_description->get_data_by_id($_POST['id']);
        		// render to the view
				$this->view->set_data($data);
				$this->view->set_layout('layout');
				$this->view->set_template('edit');			        
				$this->view->render();        		
        	}
        } else {
            
            $data['default_description_titles'] = $course_description->get_default_description_title();
            $data['default_description_title_editable'] = $course_description->get_default_description_title_editable();
            $data['default_description_icon'] = $course_description->get_default_description_icon();
            $data['question'] = $course_description->get_default_question();
            $data['information'] = $course_description->get_default_information();
            
            $data['description_type'] = $description_type;
            
			if (!empty($id)) {				
				if (isset($_GET['id_session'])) {
					$session_id = intval($_GET['id_session']);
				}				
        		$course_description_data        = $course_description->get_data_by_id($id, null, $session_id);        		        		
                $data['description_type']       = $course_description_data['description_type'];
				$data['description_title']      = $course_description_data['description_title'];
        		$data['description_content']    = $course_description_data['description_content'];        		
        		$data['progress']               = $course_description_data['progress'];        		
        		$data['descriptions']           = $course_description->get_data_by_description_type($description_type, null, $session_id);
        	}
        	// render to the view        					
			$this->view->set_data($data);
			$this->view->set_layout('layout');
			$this->view->set_template('edit');			        
			$this->view->render();        	
        }
	}
	
	/**
	 * It's used for adding a course description,
	 * render to listing or add view
	 */
	public function add() {
		$course_description = new CourseDescription();
		$session_id = api_get_session_id();
		$course_description->set_session_id($session_id);
		
		$data = array();            
        if (strtoupper($_SERVER['REQUEST_METHOD']) == "POST") {        	
        	if (!empty($_POST['title']) && !empty($_POST['contentDescription'])) {
        		
        		$check = Security::check_token();        		
        		if ($check) {
        			$title = $_POST['title'];
		        	if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
						$content = WCAG_Rendering::prepareXHTML();
					} else {
						$content = $_POST['contentDescription'];
					}        	
		        	$description_type = $_POST['description_type'];	
		        	if ($description_type >= ADD_BLOCK) {        		
		        		$course_description->set_description_type($description_type);
		        		$course_description->set_title($title);
		        		$course_description->set_content($content);
						$affected_rows = $course_description->insert(api_get_course_int_id());
		        	}
		        	Security::clear_token();	
        		} 
        		if ($affected_rows) {        			
					$message['add'] = true;        			
				}
				$this->listing(false,$message);	        			
        	} else {
        		$data['error'] = 1;
        		$data['default_description_titles'] = $course_description->get_default_description_title();
				$data['default_description_title_editable'] = $course_description->get_default_description_title_editable();
				$data['default_description_icon'] = $course_description->get_default_description_icon();
				$data['question'] = $course_description->get_default_question();
				$data['information'] = $course_description->get_default_information();
        		$data['description_title'] = $_POST['title'];
        		$data['description_content'] = $_POST['contentDescription'];
        		$data['description_type'] = $_POST['description_type'];        		
				$this->view->set_data($data);
				$this->view->set_layout('layout');
				$this->view->set_template('add');			        
				$this->view->render();
        	}        	        
        } else {											
			$data['default_description_titles'] = $course_description->get_default_description_title();
			$data['default_description_title_editable'] = $course_description->get_default_description_title_editable();
			$data['default_description_icon'] = $course_description->get_default_description_icon();
			$data['question'] = $course_description->get_default_question();
			$data['information'] = $course_description->get_default_information();
			$data['description_type'] = $course_description->get_max_description_type(); 				
			// render to the view
			$this->view->set_data($data);
			$this->view->set_layout('layout'); 
			$this->view->set_template('add');			       
			$this->view->render(); 	
        }
	}
	
	/**
	 * It's used for destroy a course description,
	 * render to listing view
	 * @param int description type
	 */
	public function destroy($id) {		
		$course_description = new CourseDescription();
		$session_id = api_get_session_id();
		$course_description->set_session_id($session_id);		
		if (!empty($id)) {
			$course_description->set_id($id);
			$affected_rows = $course_description->delete();
		}		
		if ($affected_rows) {        			
			$message['destroy'] = true;        			
		}
		$this->listing(false, $message);		
	}
}