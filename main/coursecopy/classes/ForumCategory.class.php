<?php
/* For licensing terms, see /license.txt */
/**
 * Forum category backup class
 * @package chamilo.backup
 */
require_once 'Resource.class.php';

/**
 * A forum-category
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class ForumCategory extends Resource
{
	/**
	 * The title
	 */
	var $title;
	/**
	 * The description
	 */
	var $description;
	/**
	 * The order
	 */
	var $order;
	/**
	 * Locked flag
	 */
	var $locked;
	/**
	 * The session id
	 */
	var $session_id;
	/**
	 * Create a new ForumCategory
	 */
	function ForumCategory($id, $title, $description, $order, $locked, $session_id)
	{
		parent::Resource($id,RESOURCE_FORUMCATEGORY);
		$this->title = $title;
		$this->description = $description;
		$this->order = $order;
		$this->locked = $locked;
		$this->session_id = $session_id;
	}
	/**
	 * Show this resource
	 */
	function show()
	{
		parent::show();
		echo $this->title;
	}
}
