<?php
/* For licensing terms, see /license.txt */
require_once 'Resource.class.php';
/**
 * Surveys backup script
 * @package chamilo.backup
 */

class Attendance extends Resource {
		
	var $params = array();	
	var $attendance_calendar = array();	
	
	
	/**
	 * Create a new Thematic
	 * 
	 * @param array parameters	
	 */
	public function __construct($params) {
		parent::Resource($params['id'], RESOURCE_ATTENDANCE);
		$this->params = $params;
	}

	public function show() {
		parent::show();
		echo $this->params['name'];
	}
	
	public function add_attendance_calendar($data) {		
		$this->attendance_calendar[] = $data;
	}	
}