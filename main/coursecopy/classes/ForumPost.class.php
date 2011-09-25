<?php
/* For licensing terms, see /license.txt */
/**
 * Forum post backup script
 * @package chamilo.backup
 */
/**
 * Code
 */
require_once 'Resource.class.php';

/**
 * A forum-post
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class ForumPost extends Resource
{
	/**
	 * The title
	 */
	var $title;
	/**
	 * The text
	 */
	var $text;
	/**
	 * The time
	 */
	var $post_time;
	/**
	 * Poster id
	 */
	var $poster_id;
	/**
	 * Poster name
	 */
	 var $poster_name;
	 /**
	  * Topic notify
	  */
	 var $topic_notify;
	 /**
	  * Parent post
	  */
	 var $parent_post_id;
	 /**
	  * Topic id
	  */
	 var $topic_id;
	 /**
	  * Forum id
	  */
	 var $forum_id;
	 /**
	  * Visible flag
	  */
	 var $visible;
	 /**
	 * Create a new ForumPost
	 */
	function ForumPost($id, $title, $text, $post_time, $poster_id, $poster_name, $topic_notify, $parent_post_id, $topic_id, $forum_id, $visible)
	{
		parent::Resource($id, RESOURCE_FORUMPOST);
		$this->title = $title;
		$this->text = $text;
		$this->post_time = $post_time;
		$this->poster_id = $poster_id;
		$this->poster_name = $poster_name;
		$this->topic_notify = $topic_notify;
		$this->parent_post_id = $parent_post_id;
		$this->topic_id = $topic_id;
		$this->forum_id = $forum_id;
		$this->visible = $visible;
	}
	/**
	 * Show this resource
	 */
	function show()
	{
		parent::show();
		echo $this->title.' ('.$this->poster_name.', '.$this->post_time.')';
	}
}
