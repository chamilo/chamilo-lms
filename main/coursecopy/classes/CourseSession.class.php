<?php 
/* For licensing terms, see /license.txt */
require_once('Resource.class.php');

/*
 * A course session
 *  @author Jhon Hinojosa <jhon.hinojosa@beeznest.com>
 * @package chamilo.backup
 **/
class CourseSession extends Resource {
	var $title; // The title session
	
	/*
	 * Create a new Session
	 * @param int $id
	 * @param string $title
	 */	
	function CourseSession($id,$title) {
		parent::Resource($id,RESOURCE_SESSION_COURSE);
		$this->title = $title;
	}
	
	/*
	 * Show this Event
	 */
	function show() {
		parent::show();
		echo $this->title;	
	}
}
?>
