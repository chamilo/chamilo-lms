<?php // $Id: CourseBuilder.class.php 10156 2006-11-23 08:59:13Z elixir_inter $
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
require_once ('Event.class.php');
require_once ('Link.class.php');
require_once ('ToolIntro.class.php');
require_once ('Document.class.php');
require_once ('ScormDocument.class.php');
require_once ('LinkCategory.class.php');
require_once ('CourseDescription.class.php');
require_once ('ForumPost.class.php');
require_once ('ForumTopic.class.php');
require_once ('Forum.class.php');
require_once ('ForumCategory.class.php');
require_once ('Quiz.class.php');
require_once ('QuizQuestion.class.php');
require_once ('Learnpath.class.php');
/**
 * Class which can build a course-object from a Dokeos-course.
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 */
class CourseBuilder
{
	/**
	 * The course
	 */
	var $course;
	/**
	 * Create a new CourseBuilder
	 */
	function CourseBuilder()
	{
		global $_course;
		$this->course = new Course();
		$this->course->code = $_course['official_code'];
		$this->course->path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/';
		$this->course->backup_path = api_get_path(SYS_COURSE_PATH).$_course['path'];
	}
	/**
	 * Get the created course
	 * @return course The course
	 */
	function get_course()
	{
		return $this->course;
	}
	/**
	 * Build the course-object
	 */
	function build()
	{
		$this->build_events();
		$this->build_announcements();
		$this->build_links();
		$this->build_tool_intro();
		//$this->build_forums();
		$this->build_documents();
		$this->build_course_descriptions();
		$this->build_quizzes();
		$this->build_learnpaths();
		$this->build_scorm_documents();
		$table = Database :: get_course_table(LINKED_RESOURCES_TABLE);
		foreach ($this->course->resources as $type => $resources)
		{
			foreach ($resources as $id => $resource)
			{
				$sql = "SELECT * FROM ".$table." WHERE source_type = '".$resource->get_type()."' AND source_id = '".$resource->get_id()."'";
				$res = api_sql_query($sql, __FILE__, __LINE__);
				while ($link = mysql_fetch_object($res))
				{
					$this->course->resources[$type][$id]->add_linked_resource($link->resource_type, $link->resource_id);
				}
			}
		}
		$table = Database :: get_course_table(ITEM_PROPERTY_TABLE);
		foreach ($this->course->resources as $type => $resources)
		{
			foreach ($resources as $id => $resource)
			{
				$tool = $resource->get_tool();
				if ($tool != null)
				{
					$sql = "SELECT * FROM $table WHERE TOOL = '".$tool."' AND ref='".$resource->get_id()."'";
					$res = api_sql_query($sql,__FILE__,__LINE__);
					$all_properties = array ();
					while ($item_property = mysql_fetch_array($res, MYSQL_ASSOC))
					{
						$all_properties[] = $item_property;
					}
					$this->course->resources[$type][$id]->item_properties = $all_properties;
				}
			}
		}
		return $this->course;
	}
	/**
	 * Build the documents
	 */
	function build_documents()
	{
		$table_doc = Database :: get_course_table(DOCUMENT_TABLE);
		$table_prop = Database :: get_course_table(ITEM_PROPERTY_TABLE);
		$sql = 'SELECT * FROM '.$table_doc.' d, '.$table_prop.' p WHERE tool = \''.TOOL_DOCUMENT.'\' AND p.ref = d.id AND p.visibility != 2 ORDER BY path';
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			$doc = new Document($obj->id, $obj->path, $obj->comment, $obj->title, $obj->filetype, $obj->size);
			$this->course->add_resource($doc);
		}
	}
	/**
	 * Build the forums
	 */
	function build_forums()
	{
		$table = Database :: get_course_table(TOOL_FORUM_TABLE);
		$sql = 'SELECT * FROM '.$table;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			$forum = new Forum($obj->forum_id, $obj->forum_name, $obj->forum_description, $obj->cat_id, $obj->forum_last_post_id);
			$this->course->add_resource($forum);
			$this->build_forum_category($obj->cat_id);
		}
		$this->build_forum_topics();
		$this->build_forum_posts();
	}
	/**
	 * Build a forum-category
	 */
	function build_forum_category($id)
	{
		$table = Database :: get_course_table(TOOL_FORUM_CATEGORY_TABLE);
		$sql = 'SELECT * FROM '.$table.' WHERE cat_id = '.$id;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			$forum_category = new ForumCategory($obj->cat_id, $obj->cat_title);
			$this->course->add_resource($forum_category);
		}
	}
	/**
	 * Build the forum-topics
	 */
	function build_forum_topics()
	{
		$table = Database :: get_course_table(TOOL_FORUM_POST_TABLE);
		$sql = 'SELECT * FROM '.$table;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			$forum_topic = new ForumTopic($obj->topic_id, $obj->topic_title, $obj->topic_time, $obj->prenom, $obj->nom, $obj->topic_notify, $obj->forum_id, $obj->topic_last_post_id);
			$this->course->add_resource($forum_topic);
		}
	}
	/**
	 * Build the forum-posts
	 */
	function build_forum_posts()
	{
		$table_post = Database :: get_course_table(TOOL_FORUM_POST_TABLE);
		$table_posttext = Database :: get_course_table(TOOL_FORUM_POST_TEXT_TABLE);
		$sql = 'SELECT * FROM '.$table_post.' p,'.$table_posttext.' pt WHERE p.post_id = pt.post_id';
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			$forum_post = new ForumPost($obj->post_id, $obj->post_title, $obj->post_text, $obj->post_time, $obj->poster_ip, $obj->prenom, $obj->nom, $obj->topic_notify, $obj->parent_id, $obj->topic_id);
			$this->course->add_resource($forum_post);
		}
	}
	/**
	 * Build the links
	 */
	function build_links()
	{
		$table = Database :: get_course_table(LINK_TABLE);
		$table_prop = Database :: get_course_table(ITEM_PROPERTY_TABLE);
		$sql = "SELECT * FROM $table l, $table_prop p WHERE p.ref=l.id AND p.tool = '".TOOL_LINK."' AND p.visibility != 2  ORDER BY l.display_order";
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			$link = new Link($obj->id, $obj->title, $obj->url, $obj->description, $obj->category_id, $obj->on_homepage);
			$this->course->add_resource($link);
			$this->build_link_category($obj->category_id);
		}
	}
	/**
	 * Build tool intro
	 */
	function build_tool_intro()
	{
		$table = Database :: get_course_table(TOOL_INTRO_TABLE);
		$sql = 'SELECT * FROM '.$table;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			$tool_intro = new ToolIntro($obj->id, $obj->intro_text);
			$this->course->add_resource($tool_intro);
		}
	}
	/**
	 * Build a link category
	 */
	function build_link_category($id)
	{
		$link_cat_table = Database :: get_course_table(LINK_CATEGORY_TABLE);
		$sql = 'SELECT * FROM '.$link_cat_table.' WHERE id = '.$id;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			$link_category = new LinkCategory($obj->id, $obj->category_title, $obj->description);
			$this->course->add_resource($link_category);
		}
	}
	/**
	 * Build the Quizzes
	 */
	function build_quizzes()
	{
		$table_qui = Database :: get_course_table(QUIZ_TEST_TABLE);
		$table_rel = Database :: get_course_table(QUIZ_TEST_QUESTION_TABLE);
		$table_doc = Database :: get_course_table(DOCUMENT_TABLE);
		$sql = 'SELECT * FROM '.$table_qui;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			if (strlen($obj->sound) > 0)
			{
				$doc = mysql_fetch_object(api_sql_query("SELECT id FROM ".$table_doc." WHERE path = '/audio/".$obj->sound."'"));
				$obj->sound = $doc->id;
			}
			$quiz = new Quiz($obj->id, $obj->title, $obj->description, $obj->random, $obj->type, $obj->active, $obj->sound);
			$sql = 'SELECT * FROM '.$table_rel.' WHERE exercice_id = '.$obj->id;
			$db_result2 = api_sql_query($sql, __FILE__, __LINE__);
			while ($obj2 = mysql_fetch_object($db_result2))
			{
				$quiz->add_question($obj2->question_id);
			}
			$this->course->add_resource($quiz);
		}
		$this->build_quiz_questions();
	}
	/**
	 * Build the Quiz-Questions
	 */
	function build_quiz_questions()
	{
		$table_que = Database :: get_course_table(QUIZ_QUESTION_TABLE);
		$table_ans = Database :: get_course_table(QUIZ_ANSWER_TABLE);
		$sql = 'SELECT * FROM '.$table_que;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			$question = new QuizQuestion($obj->id, $obj->question, $obj->description, $obj->ponderation, $obj->type, $obj->position);
			$sql = 'SELECT * FROM '.$table_ans.' WHERE question_id = '.$obj->id;
			$db_result2 = api_sql_query($sql, __FILE__, __LINE__);
			while ($obj2 = mysql_fetch_object($db_result2))
			{
				$question->add_answer($obj2->answer, $obj2->correct, $obj2->comment, $obj2->ponderation, $obj2->position);
			}
			$this->course->add_resource($question);
		}
	}
	/**
	 * Build the announcements
	 */
	function build_announcements()
	{
		$table = Database :: get_course_table(ANNOUNCEMENT_TABLE);
		$sql = 'SELECT * FROM '.$table;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			$announcement = new Announcement($obj->id, $obj->title, $obj->content, $obj->end_date,$obj->display_order);
			$this->course->add_resource($announcement);
		}
	}
	/**
	 * Build the events
	 */
	function build_events()
	{
		$table = Database :: get_course_table(AGENDA_TABLE);
		$sql = 'SELECT * FROM '.$table;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			$event = new Event($obj->id, $obj->title, $obj->content, $obj->start_date, $obj->end_date);
			$this->course->add_resource($event);
		}
	}
	/**
	 * Build the course-descriptions
	 */
	function build_course_descriptions()
	{
		$table = Database :: get_course_table(COURSE_DESCRIPTION_TABLE);
		$sql = 'SELECT * FROM '.$table;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = mysql_fetch_object($db_result))
		{
			$cd = new CourseDescription($obj->id, $obj->title, $obj->content);
			$this->course->add_resource($cd);
		}
	}
	/**
	 * Build the learnpaths
	 */
	function build_learnpaths()
	{
		$table_main = Database :: get_course_table(LEARNPATH_MAIN_TABLE);
		$table_chapter = Database :: get_course_table(LEARNPATH_CHAPTER_TABLE);
		$table_item = Database :: get_course_table(LEARNPATH_ITEM_TABLE);
		$table_tool = Database::get_course_table(TOOL_LIST_TABLE);

		$sql = 'SELECT * FROM '.$table_main;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);

		while ($obj = mysql_fetch_object($db_result))
		{
			$sql_chapters = "SELECT * FROM ".$table_chapter." WHERE learnpath_id = ".$obj->learnpath_id."";
			$db_chapters = api_sql_query($sql_chapters);

			$chapters = array ();

			while ($obj_chapter = mysql_fetch_object($db_chapters))
			{
				$chapter['name'] = $obj_chapter->chapter_name;
				$chapter['description'] = $obj_chapter->chapter_description;
				$chapter['display_order'] = $obj_chapter->display_order;

				$chapter['items'] = array();

				$sql_items = "SELECT * FROM ".$table_item." WHERE chapter_id = ".$obj_chapter->id."";
				$db_items = api_sql_query($sql_items);
				while ($obj_item = mysql_fetch_object($db_items))
				{
					$item['type'] = $obj_item->item_type;
					$item['id'] = $obj_item->item_id;
					$item['title'] = $obj_item->title;
					$item['display_order'] = $obj_item->display_order;
					$item['description'] = $obj_item->description;
					$item['prereq'] = $obj_item->prereq_id;
					$item['prereq_type'] = $obj_item->prereq_type;
					$item['prereq_completion_limit'] = $obj_item->prereq_completion_limit;
					$item['ref_id'] = $obj_item->id;
					$chapter['items'][] = $item;
				}
				$chapters[] = $chapter;
			}

			$sql_tool = "SELECT 1 FROM ".$table_tool." WHERE (name='".addslashes($obj->learnpath_name)."' and image='scormbuilder.gif') AND visibility='1'";
			$db_tool = api_sql_query($sql_tool);

			if(mysql_num_rows($db_tool))
			{
				$visibility='1';
			}
			else
			{
				$visibility='0';
			}

			$lp = new Learnpath($obj->learnpath_id, $obj->learnpath_name, $obj->learnpath_description, $visibility, $chapters);

			$this->course->add_resource($lp);
		}
	}

	/**
	 * Build scorm document
	 */
	function build_scorm_documents()
	{
		$i=1;

		if($dir=@opendir($this->course->backup_path.'/scorm'))
		{
			while($file=readdir($dir))
			{
				if(is_dir($this->course->backup_path.'/scorm/'.$file) && !in_array($file,array('.','..')))
				{
					$doc = new ScormDocument($i++, '/'.$file, $file);
					$this->course->add_resource($doc);
				}
			}

			closedir($dir);
		}
	}
}
?>