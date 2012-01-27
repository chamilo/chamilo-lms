<?php
/* For licensing terms, see /license.txt */
require_once 'Resource.class.php';
/**
 * Thematic backup script
 * @package chamilo.backup
 */

class Thematic extends Resource {
	/** All params
	 * */
	var $params = array();
	
	var $thematic_advance_list = array();
	
	var $thematic_plan_list = array();
	
	/**
	 * Create a new Thematic
	 * 
	 * @param array parameters	
	 */
	public function __construct($params) {
		parent::Resource($params['id'], RESOURCE_THEMATIC);
		$this->params 		= $params;
	}

	public function show() {
		parent::show();
		echo $this->params['title'];
	}
	
	public function add_thematic_advance($data) {		
		$this->thematic_advance_list[] = $data;
	}
	
	public function add_thematic_plan($data) {
		$this->thematic_plan_list[] = $data;
	}
}