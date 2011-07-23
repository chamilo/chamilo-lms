<?php
/* For licensing terms, see /license.txt */
/**
 * Forum topic backup script
 * @package chamilo.backup
 */
require_once 'Resource.class.php';

/**
 * A forum-topic/thread
 * @author Bart Mollet <bart.mollet@hogent.be>
 */
class ForumTopic extends Resource
{
	/**
	 * The title
	 */
	var $title;
	/**
	 * The time
	 */
	var $time;
	/**
	 * Poster id
	 */
	 var $topic_poster_id;
	 /**
	 * Poster name
	  */
	 var $topic_poster_name;
	 /**
	  * Parent forum
	  */
	 var $forum_id;
	 /**
	  * Last post
	  */
	 var $last_post;
	 /**
	  * How many replies are there
	  */
	 var $replies;
	 /**
	  * How many times has been viewed
	  */
	 var $views;
	 /**
	  * Sticky or not
	  */
	 var $sticky;
	 /**
	  * Locked or not
	  */
	 var $locked;
	 /**
	  * Date of closing
	  */
	 var $time_closed;

	 // From the Gradebook tool?
	 /**
	  * Weight
	  */
	 var $weight;
	 /**
	  * Weight
	  */
	 var $title_qualify;
	 /**
	  * Weight
	  */
	 var $qualify_max;

	 /**
	 * Create a new ForumTopic
	 */
	function ForumTopic($id, $title, $time, $topic_poster_id, $topic_poster_name, $forum_id, $last_post, $replies, $views, $sticky, $locked, $time_closed, $weight, $title_qualify, $qualify_max)
	{
		parent::Resource($id, RESOURCE_FORUMTOPIC);
		$this->title = $title;
		$this->time = $time;
		$this->topic_poster_id = $topic_poster_id;
		$this->topic_poster_name = $topic_poster_name;
		$this->forum_id = $forum_id;
		$this->last_post = $last_post;
		$this->replies = $replies;
		$this->views = $views;
		$this->sticky = $sticky;
		$this->locked = $locked;
		$this->time_closed = $time_closed;
		$this->weight = $weight;
		$this->title_qualify = $title_qualify;
		$this->qualify_max = $qualify_max;
	}
	/**
	 * Show this resource
	 */
	function show()
	{
		parent::show();
		echo $this->title.' ('.$this->topic_poster_name.', '.$this->topic_time.')';
	}
}
