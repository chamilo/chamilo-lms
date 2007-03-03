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
require_once('Resource.class.php');
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
	 * Poster firstname
	 */
	 var $firstname;
	 /**
	  * Poster lastname
	  */
	 var $lastname;
	 /**
	  * Topic notify
	  */
	 var $topic_notify;
	 /**
	  * Parent forum
	  */
	 var $forum_id;
	 /**
	  * Last post
	  */
	 var $last_post;
	/**
	 * Create a new ForumTopic
	 */
	function ForumTopic($id,$title,$time,$firstname,$lastname,$topic_notify,$forum_id,$last_post)
	{
		parent::Resource($id,RESOURCE_FORUMTOPIC);
		$this->title = $title;
		$this->time = $time;
		$this->firstname = $firstname;
		$this->lastname = $lastname;
		$this->topic_notify = $topic_notify;
		$this->forum_id = $forum_id;
		$this->last_post = $last_post;
	}
	/**
	 * Show this resource
	 */
	function show()
	{
		parent::show();
		echo $this->title.' ('.$this->firstname.' '.$this->lastname.', '.$this->topic_time.')';	
	}
}
?>