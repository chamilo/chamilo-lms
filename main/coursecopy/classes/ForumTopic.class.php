<?php // $Id: ForumTopic.class.php 11365 2007-03-03 10:49:33Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Bart Mollet (bart.mollet@hogent.be)

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
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
