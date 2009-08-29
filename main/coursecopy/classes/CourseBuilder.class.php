<?php
/* For licensing terms, see /dokeos_license.txt */
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
require_once ('Survey.class.php');
require_once ('SurveyQuestion.class.php');
require_once ('Glossary.class.php');
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
	function CourseBuilder($type='')
	{
		global $_course;
		$this->course = new Course();
		$this->course->code = $_course['official_code'];
		$this->course->type = $type;
		$this->course->path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/';
		$this->course->backup_path = api_get_path(SYS_COURSE_PATH).$_course['path'];
		$this->course->encoding = api_get_system_encoding(); //current platform encoding 
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
		// Enabled by Ivan Tcholakov, 27-AUG-2009.
		////$this->build_forums();
		$this->build_forums();
		//
		$this->build_documents();
		$this->build_course_descriptions();
		$this->build_quizzes();
		$this->build_learnpaths();
		$this->build_surveys();
		$this->build_glossarys();
		//TABLE_LINKED_RESOURCES is the "resource" course table, which is deprecated, apparently
		$table = Database :: get_course_table(TABLE_LINKED_RESOURCES);
		foreach ($this->course->resources as $type => $resources) {
			foreach ($resources as $id => $resource) {
				$sql = "SELECT * FROM ".$table." WHERE source_type = '".$resource->get_type()."' AND source_id = '".$resource->get_id()."'";
				$res = api_sql_query($sql, __FILE__, __LINE__);
				while ($link = Database::fetch_object($res)) {
					$this->course->resources[$type][$id]->add_linked_resource($link->resource_type, $link->resource_id);
				}
			}
		}
		$table = Database :: get_course_table(TABLE_ITEM_PROPERTY);
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
					while ($item_property = Database::fetch_array($res))
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
		$table_doc = Database :: get_course_table(TABLE_DOCUMENT);
		$table_prop = Database :: get_course_table(TABLE_ITEM_PROPERTY);	
			
        if (!empty($this->course->type) && $this->course->type=='partial')        	
        	$sql = 'SELECT * FROM '.$table_doc.' d, '.$table_prop.' p WHERE tool = \''.TOOL_DOCUMENT.'\' AND p.ref = d.id AND p.visibility != 2 AND path NOT LIKE \'/images/gallery%\' ORDER BY path';
        else
        	$sql = 'SELECT * FROM '.$table_doc.' d, '.$table_prop.' p WHERE tool = \''.TOOL_DOCUMENT.'\' AND p.ref = d.id AND p.visibility != 2 ORDER BY path';
		
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
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
		$table = Database :: get_course_table(TABLE_FORUM);
		$sql = 'SELECT * FROM '.$table;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
		{
			$forum = new Forum($obj->forum_id, $obj->forum_title, $obj->forum_comment, $obj->forum_category, $obj->forum_last_post, $obj->forum_threads, $obj->forum_posts, $obj->allow_anonymous, $obj->allow_edit, $obj->approval_direct_post, $obj->allow_attachements, $obj->allow_new_threads, $obj->default_view, $obj->forum_of_group, $obj->forum_group_public_private, $obj->forum_order, $obj->locked, $obj->session_id, $obj->forum_image);
			$this->course->add_resource($forum);
			$this->build_forum_category($obj->forum_category);
		}
		$this->build_forum_topics();
		$this->build_forum_posts();
	}
	/**
	 * Build a forum-category
	 */
	function build_forum_category($id)
	{
		$table = Database :: get_course_table(TABLE_FORUM_CATEGORY);
		$sql = 'SELECT * FROM '.$table.' WHERE cat_id = '.$id;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
		{
			$forum_category = new ForumCategory($obj->cat_id, $obj->cat_title, $obj->cat_comment, $obj->cat_order, $obj->locked, $obj->session_id);
			$this->course->add_resource($forum_category);
		}
	}
	/**
	 * Build the forum-topics
	 */
	function build_forum_topics()
	{
		$table = Database :: get_course_table(TABLE_FORUM_THREAD);
		$sql = 'SELECT * FROM '.$table;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
		{
			$forum_topic = new ForumTopic($obj->thread_id, $obj->thread_title, $obj->thread_date, $obj->thread_poster_id, $obj->thread_poster_name, $obj->forum_id, $obj->thread_last_post, $obj->thread_replies, $obj->thread_views, $obj->thread_sticky, $obj->locked, $obj->thread_close_date, $obj->thread_weight, $obj->thread_title_qualify, $obj->thread_qualify_max);
			$this->course->add_resource($forum_topic);
		}
	}
	/**
	 * Build the forum-posts
	 * TODO: All tree structure of posts should be built, attachments for example.
	 */
	function build_forum_posts()
	{
		$table_post = Database :: get_course_table(TABLE_FORUM_POST);
		$sql = 'SELECT * FROM '.$table_post;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
		{
			$forum_post = new ForumPost($obj->post_id, $obj->post_title, $obj->post_text, $obj->post_date, $obj->poster_id, $obj->poster_name, $obj->post_notification, $obj->post_parent_id, $obj->thread_id, $obj->forum_id, $obj->visible);
			$this->course->add_resource($forum_post);
		}
	}
	/**
	 * Build the links
	 */
	function build_links()
	{
		$table = Database :: get_course_table(TABLE_LINK);
		$table_prop = Database :: get_course_table(TABLE_ITEM_PROPERTY);
		$sql = "SELECT * FROM $table l, $table_prop p WHERE p.ref=l.id AND p.tool = '".TOOL_LINK."' AND p.visibility != 2  ORDER BY l.display_order";
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
		{
			$link = new Link($obj->id, $obj->title, $obj->url, $obj->description, $obj->category_id, $obj->on_homepage);
			$this->course->add_resource($link);
			$res = $this->build_link_category($obj->category_id);
			if($res > 0)
			{
				$this->course->resources[RESOURCE_LINK][$obj->id]->add_linked_resource(RESOURCE_LINKCATEGORY, $obj->category_id);
			}
		}
	}
	/**
	 * Build tool intro
	 */
	function build_tool_intro()
	{
		$table = Database :: get_course_table(TABLE_TOOL_INTRO);
		$sql = 'SELECT * FROM '.$table;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
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
		$link_cat_table = Database :: get_course_table(TABLE_LINK_CATEGORY);
		$sql = 'SELECT * FROM '.$link_cat_table.' WHERE id = '.$id;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
		{
			$link_category = new LinkCategory($obj->id, $obj->category_title, $obj->description, $obj->display_order);
			$this->course->add_resource($link_category);
			return $id;
		}
		return 0;
	}
	/**
	 * Build the Quizzes
	 */
	function build_quizzes()
	{
		$table_qui = Database :: get_course_table(TABLE_QUIZ_TEST);
		$table_rel = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
		$table_doc = Database :: get_course_table(TABLE_DOCUMENT);
		$sql = 'SELECT * FROM '.$table_qui.' WHERE active >=0'; //select only quizzes with active = 0 or 1 (not -1 which is for deleted quizzes)
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
		{
			if (strlen($obj->sound) > 0)
			{
				$doc = Database::fetch_object(api_sql_query("SELECT id FROM ".$table_doc." WHERE path = '/audio/".$obj->sound."'"));
				$obj->sound = $doc->id;
			}
			$quiz = new Quiz($obj->id, $obj->title, $obj->description, $obj->random, $obj->type, $obj->active, $obj->sound, $obj->attempts);
			$sql = 'SELECT * FROM '.$table_rel.' WHERE exercice_id = '.$obj->id;
			$db_result2 = api_sql_query($sql, __FILE__, __LINE__);
			while ($obj2 = Database::fetch_object($db_result2))
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
		$table_que = Database :: get_course_table(TABLE_QUIZ_QUESTION);
		$table_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER);
		$sql = 'SELECT * FROM '.$table_que;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
		{
			$question = new QuizQuestion($obj->id, $obj->question, $obj->description, $obj->ponderation, $obj->type, $obj->position, $obj->picture,$obj->level);
			$sql = 'SELECT * FROM '.$table_ans.' WHERE question_id = '.$obj->id;
			$db_result2 = api_sql_query($sql, __FILE__, __LINE__);
			while ($obj2 = Database::fetch_object($db_result2))
			{
				$question->add_answer($obj2->answer, $obj2->correct, $obj2->comment, $obj2->ponderation, $obj2->position, $obj2->hotspot_coordinates, $obj2->hotspot_type);
			}
			$this->course->add_resource($question);
		}
	}
	/**
	 * Build the Surveys
	 */
	function build_surveys()
	{
		$table_survey = Database :: get_course_table(TABLE_SURVEY);
		$table_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$sql = 'SELECT * FROM '.$table_survey;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
		{
			$survey = new Survey($obj->survey_id, $obj->code,$obj->title,
								$obj->subtitle, $obj->author, $obj->lang,
								$obj->avail_from, $obj->avail_till, $obj->is_shared,
								$obj->template, $obj->intro, $obj->surveythanks,
								$obj->creation_date, $obj->invited, $obj->answered,
								$obj->invite_mail, $obj->reminder_mail);
			$sql = 'SELECT * FROM '.$table_question.' WHERE survey_id = '.$obj->survey_id;
			$db_result2 = api_sql_query($sql, __FILE__, __LINE__);
			while ($obj2 = Database::fetch_object($db_result2))
			{
				$survey->add_question($obj2->question_id);
			}
			$this->course->add_resource($survey);
		}
		$this->build_survey_questions();
	}
	/**
	 * Build the Survey Questions
	 */
	function build_survey_questions()
	{
		$table_que = Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$table_opt = Database :: get_course_table(TABLE_SURVEY_QUESTION_OPTION);
		$sql = 'SELECT * FROM '.$table_que;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
		{
			$question = new SurveyQuestion($obj->question_id, $obj->survey_id,
											$obj->survey_question, $obj->survey_question_comment,
											$obj->type, $obj->display, $obj->sort,
											$obj->shared_question_id, $obj->max_value);
			$sql = 'SELECT * FROM '.$table_opt.' WHERE question_id = '."'".$obj->question_id."'";
			$db_result2 = api_sql_query($sql, __FILE__, __LINE__);
			while ($obj2 = Database::fetch_object($db_result2))
			{
				$question->add_answer($obj2->option_text, $obj2->sort);
			}
			$this->course->add_resource($question);
		}
	}
	/**
	 * Build the announcements
	 */
	function build_announcements()
	{
		$table = Database :: get_course_table(TABLE_ANNOUNCEMENT);
		$sql = 'SELECT * FROM '.$table;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
		{
			$announcement = new Announcement($obj->id, $obj->title, $obj->content, $obj->end_date,$obj->display_order,$obj->email_sent);
			$this->course->add_resource($announcement);
		}
	}
	/**
	 * Build the events
	 */
	function build_events()
	{
		$table = Database :: get_course_table(TABLE_AGENDA);
		$sql = 'SELECT * FROM '.$table;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
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
		$table = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);
		$sql = 'SELECT * FROM '.$table;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
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
		$table_main 	= Database :: get_course_table(TABLE_LP_MAIN);
		$table_item 	= Database :: get_course_table(TABLE_LP_ITEM);
		$table_tool 	= Database::get_course_table(TABLE_TOOL_LIST);

		$sql = 'SELECT * FROM '.$table_main;
		$db_result = api_sql_query($sql, __FILE__, __LINE__);

		while ($obj = Database::fetch_object($db_result))
		{

			$items = array();
			$sql_items = "SELECT * FROM ".$table_item." WHERE lp_id = ".$obj->id."";
			$db_items = api_sql_query($sql_items);
			while ($obj_item = Database::fetch_object($db_items))
			{
				$item['id'] = $obj_item->id;
				$item['item_type'] = $obj_item->item_type;
				$item['ref'] = $obj_item->ref;
				$item['title'] = $obj_item->title;
				$item['description'] = $obj_item->description;
				$item['path'] = $obj_item->path;
				$item['min_score'] = $obj_item->min_score;
				$item['max_score'] = $obj_item->max_score;
				$item['mastery_score'] = $obj_item->mastery_score;
				$item['parent_item_id'] = $obj_item->parent_item_id;
				$item['previous_item_id'] = $obj_item->previous_item_id;
				$item['next_item_id'] = $obj_item->next_item_id;
				$item['display_order'] = $obj_item->display_order;
				$item['prerequisite'] = $obj_item->prerequisite;
				$item['parameters'] = $obj_item->parameters;
				$item['launch_data'] = $obj_item->launch_data;
				$items[] = $item;
			}

			$sql_tool = "SELECT id FROM ".$table_tool." WHERE (link LIKE '%lp_controller.php%lp_id=".$obj->id."%' and image='scormbuilder.gif') AND visibility='1'";
			$db_tool = api_sql_query($sql_tool);

			if(Database::num_rows($db_tool))
			{
				$visibility='1';
			}
			else
			{
				$visibility='0';
			}

			$lp = new Learnpath($obj->id,
								$obj->lp_type,
								$obj->name,
								$obj->path,
								$obj->ref,
								$obj->description,
								$obj->content_local,
								$obj->default_encoding,
								$obj->default_view_mod,
								$obj->prevent_reinit,
								$obj->force_commit,
								$obj->content_maker,
								$obj->display_order,
								$obj->js_lib,
								$obj->content_license,
								$obj->debug,
								$visibility,
								$items);

			$this->course->add_resource($lp);
		}

		//save scorm directory (previously build_scorm_documents())
		$i = 1;
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
	
	/**
	 * Build the glossarys
	 */
	function build_glossarys() {
		$table_glossary = Database :: get_course_table(TABLE_GLOSSARY);
		
        if (!empty($this->course->type) && $this->course->type=='partial')        	
        	$sql = 'SELECT * FROM '.$table_glossary.' g ';
        else
        	$sql = 'SELECT * FROM '.$table_glossary.' g ';
		
		$db_result = api_sql_query($sql, __FILE__, __LINE__);
		while ($obj = Database::fetch_object($db_result))
		{
			$doc = new Glossary($obj->glossary_id, $obj->name, $obj->description, $obj->display_order);
			$this->course->add_resource($doc);
		}
	}	
}
