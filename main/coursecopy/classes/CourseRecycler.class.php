<?php
// $Id: CourseRecycler.class.php 5950 2005-08-11 10:52:34Z bmol $
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
require_once ('Course.class.php');
require_once ('rmdirr.php');
/**
 * Class to delete items from a Dokeos-course
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 */
class CourseRecycler
{
	/**
	 * A course-object with the items to delete
	 */
	var $course;
	/**
	 * Create a new CourseRecycler
	 * @param course $course The course-object which contains the items to
	 * delete
	 */
	function CourseRecycler($course)
	{
		$this->course = $course;
	}
	/**
	 * Delete all items from the course.
	 * This deletes all items in the course-object from the current Dokeos-
	 * course
	 */
	function recycle()
	{
		$table_linked_resources = Database :: get_course_table(LINKED_RESOURCES_TABLE, $this->course->destination_db);
		$table_item_properties = Database::get_course_table(ITEM_PROPERTY_TABLE);
		foreach ($this->course->resources as $type => $resources)
		{
			foreach ($resources as $id => $resource)
			{
				$sql = "DELETE FROM ".$table_linked_resources." WHERE (source_type = '".$type."' AND source_id = '".$id."') OR (resource_type = '".$type."' AND resource_id = '".$id."')  ";
				api_sql_query($sql,__FILE__,__LINE__);
				$sql = "DELETE FROM ".$table_item_properties." WHERE tool ='".$resource->get_tool()."' AND ref=".$id;
				api_sql_query($sql);
			}
		}
		$this->recycle_links();
		$this->recycle_link_categories();
		$this->recycle_events();
		$this->recycle_announcements();
		$this->recycle_documents();
		$this->recycle_forums();
		$this->recycle_forum_categories();
		$this->recycle_quizzes();
		$this->recycle_learnpaths();
		$this->recycle_cours_description();
	}
	/**
	 * Delete documents
	 */
	function recycle_documents()
	{
		if ($this->course->has_resources(RESOURCE_DOCUMENT))
		{
			$table = Database :: get_course_table(DOCUMENT_TABLE);
			foreach ($this->course->resources[RESOURCE_DOCUMENT] as $id => $document)
			{
				rmdirr($this->course->backup_path.'/'.$document->path);
			}
			$ids = implode(',', (array_keys($this->course->resources[RESOURCE_DOCUMENT])));
			$sql = "DELETE FROM ".$table." WHERE id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Delete links
	 */
	function recycle_links()
	{
		if ($this->course->has_resources(RESOURCE_LINK))
		{
			$table = Database :: get_course_table(LINK_TABLE);
			$ids = implode(',', (array_keys($this->course->resources[RESOURCE_LINK])));
			$sql = "DELETE FROM ".$table." WHERE id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Delete forums
	 */
	function recycle_forums()
	{
		if ($this->course->has_resources(RESOURCE_FORUM))
		{
			$table_forum = Database :: get_course_table(FORUM_TABLE);
			$table_topic = Database :: get_course_table(FORUM_TOPIC_TABLE);
			$table_post = Database :: get_course_table(FORUM_POST_TABLE);
			$table_posttext = Database :: get_course_table(FORUM_POST_TEXT_TABLE);
			$forum_ids = implode(',', (array_keys($this->course->resources[RESOURCE_FORUM])));
			$sql = "SELECT post_id FROM ".$table_post." WHERE forum_id IN (".$forum_ids.")";
			$res = api_sql_query($sql,__FILE__,__LINE__);
			$post_ids = array ();
			while ($obj = mysql_fetch_object($res))
			{
				$post_ids[] = $obj->post_id;
			}
			$post_ids = implode(',', $post_ids);
			$sql = "DELETE FROM ".$table_posttext." WHERE post_id IN(".$post_ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
			$sql = "DELETE FROM ".$table_post." WHERE forum_id IN(".$forum_ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
			$sql = "DELETE FROM ".$table_topic." WHERE forum_id IN(".$forum_ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
			$sql = "DELETE FROM ".$table_forum." WHERE forum_id IN(".$forum_ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Delete forum-categories
	 * Deletes all forum-categories from current course without forums
	 */
	function recycle_forum_categories()
	{
		$table_forum = Database :: get_course_table(FORUM_TABLE);
		$table_forumcat = Database :: get_course_table(FORUM_CATEGORY_TABLE);
		$sql = "SELECT fc.cat_id FROM ".$table_forumcat." fc LEFT JOIN ".$table_forum." f ON fc.cat_id=f.cat_id WHERE f.forum_id IS NULL";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		while ($obj = mysql_fetch_object($res))
		{
			$sql = "DELETE FROM ".$table_forumcat." WHERE cat_id = ".$obj->cat_id;
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Delete link-categories
	 * Deletes all link-categories from current course without links
	 */
	function recycle_link_categories()
	{
		$link_cat_table = Database :: get_course_table(LINK_CATEGORY_TABLE);
		$link_table = Database :: get_course_table(LINK_TABLE);
		$sql = "SELECT lc.id FROM ".$link_cat_table." lc LEFT JOIN ".$link_table." l ON lc.id=l.category_id WHERE l.id IS NULL";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		while ($obj = mysql_fetch_object($res))
		{
			$sql = "DELETE FROM ".$link_cat_table." WHERE id = ".$obj->id;
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Delete events
	 */
	function recycle_events()
	{
		if ($this->course->has_resources(RESOURCE_EVENT))
		{
			$table = Database :: get_course_table(AGENDA_TABLE);
			$ids = implode(',', (array_keys($this->course->resources[RESOURCE_EVENT])));
			$sql = "DELETE FROM ".$table." WHERE id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Delete announcements
	 */
	function recycle_announcements()
	{
		if ($this->course->has_resources(RESOURCE_ANNOUNCEMENT))
		{
			$table = Database :: get_course_table(ANNOUNCEMENT_TABLE);
			$ids = implode(',', (array_keys($this->course->resources[RESOURCE_ANNOUNCEMENT])));
			$sql = "DELETE FROM ".$table." WHERE id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Recycle quizzes
	 */
	function recycle_quizzes()
	{
		if ($this->course->has_resources(RESOURCE_QUIZ))
		{
			$table_qui = Database :: get_course_table(QUIZ_TEST_TABLE);
			$table_rel = Database :: get_course_table(QUIZ_TEST_QUESTION_TABLE);
			$ids = implode(',', (array_keys($this->course->resources[RESOURCE_QUIZ])));
			$sql = "DELETE FROM ".$table_qui." WHERE id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
			$sql = "DELETE FROM ".$table_rel." WHERE exercice_id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Recycle learnpaths
	 */
	function recycle_learnpaths()
	{
		if ($this->course->has_resources(RESOURCE_LEARNPATH))
		{
			$table_main = Database :: get_course_table(LEARNPATH_MAIN_TABLE);
			$table_chapter = Database :: get_course_table(LEARNPATH_CHAPTER_TABLE);
			$table_item = Database :: get_course_table(LEARNPATH_ITEM_TABLE);
			$table_tool = Database::get_course_table(TOOL_LIST_TABLE);
			foreach($this->course->resources[RESOURCE_LEARNPATH] as $id => $learnpath)
			{
				$sql = "DELETE FROM $table_tool WHERE link='".mysql_real_escape_string('learnpath/learnpath_handler.php?learnpath_id='.$id)."'";
				api_sql_query($sql,__FILE__,__LINE__);	
			}
			$ids = implode(',', (array_keys($this->course->resources[RESOURCE_LEARNPATH])));
			$sql = "SELECT id FROM ".$table_chapter." WHERE learnpath_id IN (".$ids.")";
			$db_result = api_sql_query($sql,__FILE__,__LINE__);
			$chapter_ids = array ();
			while ($chap = mysql_fetch_object($db_result))
			{
				$chapter_ids[] = $chap->id;
			}
			if( count($chapter_ids) > 0 )
			{
				$chap_ids = implode(',', $chapter_ids);
				$sql = "DELETE FROM ".$table_item." WHERE chapter_id IN (".$chap_ids.")";
				api_sql_query($sql,__FILE__,__LINE__);
				$sql = "DELETE FROM ".$table_chapter." WHERE id IN (".$chap_ids.")";
				api_sql_query($sql,__FILE__,__LINE__);
			}
			$sql = "DELETE FROM ".$table_main." WHERE learnpath_id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Delete course description
	 */
	function recycle_cours_description()
	{
		if ($this->course->has_resources(RESOURCE_COURSEDESCRIPTION))
		{
			$table = Database :: get_course_table(COURSE_DESCRIPTION_TABLE);
			$ids = implode(',', (array_keys($this->course->resources[RESOURCE_COURSEDESCRIPTION])));
			$sql = "DELETE FROM ".$table." WHERE id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
}
?>

