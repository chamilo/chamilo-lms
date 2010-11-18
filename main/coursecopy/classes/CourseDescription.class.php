<?php
/* For licensing terms, see /license.txt */

require_once 'Resource.class.php';

/**
 * A course description
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class CourseDescription extends Resource
{
	/**
	 * The title
	 */
	var $title;
	/**
	 * The content
	 */
	var $content;
	/**
	 * The description type
	 */
	var $description_type;	
	/**
	 * Create a new course description
	 * @param int $id
	 * @param string $title
	 * @param string $content
	 */
	function CourseDescription($id,$title,$content,$description_type)
	{
		parent::Resource($id,RESOURCE_COURSEDESCRIPTION);
		$this->title = $title;
		$this->content = $content;
		$this->description_type = $description_type;
	}
	/**
	 * Show this Event
	 */
	function show()
	{
		parent::show();
		echo $this->title;
	}
}
?>