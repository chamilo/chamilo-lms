<?php
/* For licensing terms, see /license.txt */
require_once 'Resource.class.php';
/**
 * Surveys backup script
 * @package chamilo.backup
 */

class Thematic extends Resource
{
	
	/**
	 * The title
	 */
	var $title;
	
	/**
	 * The Content
	 */
	var $content;
	
	/**
	 * The display order
	 */
	var $display_order;
	
	/**
	 * Active
	 */
	var $active;
	
	var $session_id;
	
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
		$this->title 		= $params['title'];
		$this->content		= $params['content'];
		$this->author 		= $params['display_order'];
		$this->active		= $params['active'];
		$this->session_id	= $params['session_id'];
		$this->params 		= $params;		
	}

	public function show() {
		parent::show();
		echo $this->title;
	}
	
	public function add_thematic_advance($data) {		
		$this->thematic_advance_list[] = $data;
	}
	
	public function add_thematic_plan($data) {
		$this->thematic_plan_list[] = $data;
	}
}