<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

/**
 * Class CourseSession
 * @author Jhon Hinojosa <jhon.hinojosa@beeznest.com>
 * @package chamilo.backup
 */
class CourseSession extends Coursecopy\Resource
{
    // The title session
	public $title;

	/**
	 * Create a new Session
	 * @param int $id
	 * @param string $title
	 */
	public function __construct($id,$title)
	{
		parent::__construct($id, RESOURCE_SESSION_COURSE);
		$this->title = $title;
	}

	/**
	 * Show this Event
	 */
	function show() {
		parent::show();
		echo $this->title;
	}
}
