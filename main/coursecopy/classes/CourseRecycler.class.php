<?php
// $Id: CourseRecycler.class.php 12117 2007-04-24 22:06:36Z pcool $
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
		$table_tool_intro = Database::get_course_table(TABLE_TOOL_INTRO);
		$table_linked_resources = Database :: get_course_table(TABLE_LINKED_RESOURCES, $this->course->destination_db);
		$table_item_properties = Database::get_course_table(TABLE_ITEM_PROPERTY);
		foreach ($this->course->resources as $type => $resources)
		{
			foreach ($resources as $id => $resource)
			{
				$sql = "DELETE FROM ".$table_linked_resources." WHERE (source_type = '".$type."' AND source_id = '".$id."') OR (resource_type = '".$type."' AND resource_id = '".$id."')  ";
				api_sql_query($sql,__FILE__,__LINE__);
				if(is_numeric($id))
				{
					$sql = "DELETE FROM ".$table_item_properties." WHERE tool ='".$resource->get_tool()."' AND ref=".$id;
					api_sql_query($sql, __FILE__, __LINE__);
				}
				elseif ($type == RESOURCE_TOOL_INTRO)
				{
					$sql = "DELETE FROM $table_tool_intro WHERE id='$id'";
					api_sql_query($sql, __FILE__, __LINE__);
				}
			}
		}
		$this->recycle_links();
		$this->recycle_link_categories();
		$this->recycle_events();
		$this->recycle_announcements();
		$this->recycle_documents();
		//$this->recycle_forums();
		//$this->recycle_forum_categories();
		$this->recycle_quizzes();
		$this->recycle_surveys();
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
			$table = Database :: get_course_table(TABLE_DOCUMENT);
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
			$table = Database :: get_course_table(TABLE_LINK);
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
			$table_forum = Database :: get_course_table(TABLE_FORUM);
			$table_thread = Database :: get_course_table(TABLE_FORUM_THREAD);
			$table_post = Database :: get_course_table(TABLE_FORUM_POST);
			$forum_ids = implode(',', (array_keys($this->course->resources[RESOURCE_FORUM])));
			$sql = "DELETE FROM ".$table_post." WHERE forum_id IN(".$forum_ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
			$sql = "DELETE FROM ".$table_thread." WHERE forum_id IN(".$forum_ids.")";
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
		$table_forum = Database :: get_course_table(TABLE_FORUM);
		$table_forumcat = Database :: get_course_table(TABLE_FORUM_CATEGORY);
		$sql = "SELECT fc.cat_id FROM ".$table_forumcat." fc LEFT JOIN ".$table_forum." f ON fc.cat_id=f.forum_category WHERE f.forum_id IS NULL";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		while ($obj = mysql_fetch_object($res))
		{
			$sql = "DELETE FROM ".$table_forumcat." WHERE cat_id = ".$obj->cat_id;
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Delete link-categories
	 * Deletes all empty link-categories (=without links) from current course
	 */
	function recycle_link_categories()
	{
		$link_cat_table = Database :: get_course_table(TABLE_LINK_CATEGORY);
		$link_table = Database :: get_course_table(TABLE_LINK);
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
			$table = Database :: get_course_table(TABLE_AGENDA);
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
			$table = Database :: get_course_table(TABLE_ANNOUNCEMENT);
			$ids = implode(',', (array_keys($this->course->resources[RESOURCE_ANNOUNCEMENT])));
			$sql = "DELETE FROM ".$table." WHERE id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Recycle quizzes - doesn't remove the questions and their answers, as they might still be used later
	 */
	function recycle_quizzes()
	{
		if ($this->course->has_resources(RESOURCE_QUIZ))
		{
			//$table_qui_que = Database :: get_course_table(TABLE_QUIZ_QUESTION);
			//$table_qui_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER);
			$table_qui = Database :: get_course_table(TABLE_QUIZ_TEST);
			$table_rel = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
			$ids = implode(',', (array_keys($this->course->resources[RESOURCE_QUIZ])));
			$sql = "DELETE FROM ".$table_qui." WHERE id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
			$sql = "DELETE FROM ".$table_rel." WHERE exercice_id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Recycle surveys - removes everything
	 */
	function recycle_surveys()
	{
		if ($this->course->has_resources(RESOURCE_SURVEY))
		{
			$table_survey = Database :: get_course_table(TABLE_SURVEY);
			$table_survey_q = Database :: get_course_table(TABLE_SURVEY_QUESTION);
			$table_survey_q_o = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
			$table_survey_a = Database :: get_course_Table(TABLE_SURVEY_ANSWER);
			$table_survey_i = Database :: get_course_table(TABLE_SURVEY_INVITATION);
			$ids = implode(',', (array_keys($this->course->resources[RESOURCE_SURVEY])));
			$sql = "DELETE FROM ".$table_survey_i." ";
			api_sql_query($sql,__FILE__,__LINE__);
			$sql = "DELETE FROM ".$table_survey_a." WHERE survey_id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
			$sql = "DELETE FROM ".$table_survey_q_o." WHERE survey_id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
			$sql = "DELETE FROM ".$table_survey_q." WHERE survey_id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
			$sql = "DELETE FROM ".$table_survey." WHERE survey_id IN(".$ids.")";
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
			$table_main = Database :: get_course_table(TABLE_LP_MAIN);
			$table_item = Database :: get_course_table(TABLE_LP_ITEM);
			$table_view = Database :: get_course_table(TABLE_LP_VIEW);
			$table_iv   = Database :: get_course_table(TABLE_LP_ITEM_VIEW);
			$table_iv_int = Database :: get_course_table(TABLE_LP_IV_INTERACTION);
			$table_tool = Database::get_course_table(TABLE_TOOL_LIST);
			foreach($this->course->resources[RESOURCE_LEARNPATH] as $id => $learnpath)
			{
				//remove links from course homepage
				$sql = "DELETE FROM $table_tool WHERE link LIKE '%lp_controller.php%lp_id=$id%' AND image='scormbuilder.gif'";
				api_sql_query($sql,__FILE__,__LINE__);
				//remove elements from lp_* tables (from bottom-up) by removing interactions, then item_view, then views and items, then paths
				$sql_items = "SELECT id FROM $table_item WHERE lp_id=$id";
				$res_items = api_sql_query($sql_items,__FILE__,__LINE__);
				while ($row_item = Database::fetch_array($res_items))
				{
					//get item views
					$sql_iv = "SELECT id FROM $table_iv WHERE lp_item_id=".$row_item['id'];
					$res_iv = api_sql_query($sql_iv,__FILE__,__LINE__);
					while ($row_iv = Database::fetch_array($res_iv))
					{
						//delete interactions
						$sql_iv_int_del = "DELETE FROM $table_iv_int WHERE lp_iv_id = ".$row_iv['id'];
						$res_iv_int_del = api_sql_query($sql_iv_int_del,__FILE__,__LINE__);
					}
					//delete item views
					$sql_iv_del = "DELETE FROM $table_iv WHERE lp_item_id=".$row_item['id'];
					$res_iv_del = api_sql_query($sql_iv_del,__FILE__,__LINE__);
				}
				//delete items
				$sql_items_del = "DELETE FROM $table_item WHERE lp_id=$id";
				$res_items_del = api_sql_query($sql_items_del,__FILE__,__LINE__);
				//delete views
				$sql_views_del = "DELETE FROM $table_view WHERE lp_id=$id";
				$res_views_del = api_sql_query($sql_views_del,__FILE__,__LINE__);
				//delete lps
				$sql_del = "DELETE FROM $table_main WHERE id = $id";
				$res_del = api_sql_query($sql_del,__FILE__,__LINE__);
			}
		}
	}
	/**
	 * Delete course description
	 */
	function recycle_cours_description()
	{
		if ($this->course->has_resources(RESOURCE_COURSEDESCRIPTION))
		{
			$table = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);
			$ids = implode(',', (array_keys($this->course->resources[RESOURCE_COURSEDESCRIPTION])));
			$sql = "DELETE FROM ".$table." WHERE id IN(".$ids.")";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
}
?>

