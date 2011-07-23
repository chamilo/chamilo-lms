<?php
/* For licensing terms, see /license.txt */
/**
 * Link backup script
 * @package chamilo.backup
 */
require_once 'Resource.class.php';

/**
 * A WWW-link from the Links-module in a Chamilo-course.
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class Link extends Resource
{
	/**
	 * The title
	 */
	var $title;
	/**
	 * The URL
	 */
	var $url;
	/**
	 * The description
	 */
	var $description;
	/**
	 * Id of this links category
	 */
	var $category_id;
	/**
	 * Display link on course homepage
	 */
	var $on_homepage;
	/**
	 * Create a new Link
	 * @param int $id The id of this link in the Chamilo-course
	 * @param string $title
	 * @param string $url
	 * @param string $description
	 */
	function Link($id,$title,$url,$description,$category_id,$on_homepage)
	{
		parent::Resource($id,RESOURCE_LINK);
		$this->title = $title;
		$this->url = $url;
		$this->description = $description;
		$this->category_id = $category_id;
		$this->on_homepage = $on_homepage;
	}
	/**
	 * Show this resource
	 */
	function show()
	{
		parent::show();
		echo $this->title.' ('.$this->url.')';
	}
}
?>
