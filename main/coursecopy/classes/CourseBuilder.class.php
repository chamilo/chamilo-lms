<?php
/* For licensing terms, see /license.txt */

require_once 'Course.class.php';
require_once 'Event.class.php';
require_once 'Link.class.php';
require_once 'ToolIntro.class.php';
require_once 'Document.class.php';
require_once 'ScormDocument.class.php';
require_once 'LinkCategory.class.php';
require_once 'CourseDescription.class.php';
require_once 'ForumPost.class.php';
require_once 'ForumTopic.class.php';
require_once 'Forum.class.php';
require_once 'ForumCategory.class.php';
require_once 'Quiz.class.php';
require_once 'QuizQuestion.class.php';
require_once 'CourseCopyLearnpath.class.php';
require_once 'Survey.class.php';
require_once 'SurveyQuestion.class.php';
require_once 'Glossary.class.php';
require_once 'CourseSession.class.php';
require_once 'wiki.class.php';
require_once 'Thematic.class.php';
require_once 'Attendance.class.php';

/**
 * Class which can build a course-object from a Chamilo-course.
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
class CourseBuilder {
	/**
	 * The course
	 */
	var $course;
	/**
	 * Create a new CourseBuilder
	 */
	function CourseBuilder($type='', $course = null) {
		global $_course;
		
		if (!empty($course['official_code'])){
			$_course = $course;
		}	
		
		$this->course = new Course();
		$this->course->code = $_course['official_code'];
		$this->course->type = $type;
		$this->course->path = api_get_path(SYS_COURSE_PATH).$_course['path'].'/';
		$this->course->backup_path = api_get_path(SYS_COURSE_PATH).$_course['path'];
		$this->course->encoding = api_get_system_encoding(); //current platform encoding
		$this->course->db_name  = $_course['dbName'];
		$this->course->info     = $_course;
	}
	/**
	 * Get the created course
	 * @return course The course
	 */
	function get_course() {
		return $this->course;
	}
	
	/**
	 * Build the course-object
	 * 
	 * @param int      session_id
	 * @param string   course_code
	 * @param bool     true if you want to get the elements that exists in the course and 
	 *                 in the session, (session_id = 0 or session_id = X) 
	 */
	function build($session_id = 0, $course_code = '', $with_base_content = false) {
		$course_info	= api_get_course_info($course_code);
		
		$course_id 		= $course_info['real_id'];
		
		$table_link       = Database :: get_course_table(TABLE_LINKED_RESOURCES);
		$table_properties = Database :: get_course_table(TABLE_ITEM_PROPERTY);
		
		if (!empty($session_id) && !empty($course_code)) {
			
			$this->build_documents($session_id, $course_code, $with_base_content);
			$this->build_quizzes($session_id, $course_code,   $with_base_content);
			$this->build_glossary($session_id, $course_code,  $with_base_content);
			$this->build_learnpaths($session_id, $course_code,$with_base_content);
			$this->build_links($session_id, $course_code, $with_base_content);
			$this->build_course_descriptions($session_id, $course_code, $with_base_content);
			$this->build_wiki($session_id, $course_code, $with_base_content);
			//$this->build_thematic($session_id, $course_code, $with_base_content);
			$this->build_thematic();			
			$this->build_attendance();
		} else {			

			$this->build_events();
			$this->build_announcements();
			$this->build_links();
			$this->build_tool_intro();
			$this->build_documents();
			$this->build_course_descriptions();
			$this->build_wiki();
			$this->build_quizzes();
			$this->build_learnpaths();
			$this->build_surveys();
			$this->build_glossary();
			$this->build_thematic();
			$this->build_attendance();
		}

		//TABLE_LINKED_RESOURCES is the "resource" course table, which is deprecated, apparently

		foreach ($this->course->resources as $type => $resources) {
			foreach ($resources as $id => $resource) {
				$sql = "SELECT * FROM ".$table_link." WHERE c_id = $course_id AND source_type = '".$resource->get_type()."' AND source_id = '".$resource->get_id()."'";
				$res = Database::query($sql);
				while ($link = Database::fetch_object($res)) {
					$this->course->resources[$type][$id]->add_linked_resource($link->resource_type, $link->resource_id);
				}
			}
		}

		foreach ($this->course->resources as $type => $resources) {
			foreach ($resources as $id => $resource)
			{
				$tool = $resource->get_tool();
				if ($tool != null) {
					$sql = "SELECT * FROM $table_properties WHERE c_id = $course_id AND TOOL = '".$tool."' AND ref='".$resource->get_id()."'";
					$res = Database::query($sql);
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
	function build_documents($session_id = 0, $course_code = '', $with_base_content = false) {

		$course_info 	= api_get_course_info($course_code);
		$course_id 		= $course_info['real_id'];
		
		$table_doc   = Database::get_course_table(TABLE_DOCUMENT);
		$table_prop  = Database::get_course_table(TABLE_ITEM_PROPERTY);
		
		if (!empty($course_code) && !empty($session_id)) {			

			$session_id  = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);                
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }
			if (!empty($this->course->type) && $this->course->type=='partial') {
			    $sql = "SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size FROM $table_doc d, $table_prop p 
			            WHERE 	d.c_id = $course_id AND 
			            		p.c_id = $course_id AND
			            		tool = '".TOOL_DOCUMENT."' AND 
			            		p.ref = d.id AND 
			            		p.visibility != 2 AND 
			            		path NOT LIKE '/images/gallery%' 
			            		$session_condition 
			    		ORDER BY path";
			} else {
	        	$sql = "SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size FROM $table_doc d, $table_prop p 
	        	        WHERE 	d.c_id = $course_id AND 
			            		p.c_id = $course_id AND
	        					tool = '".TOOL_DOCUMENT."' AND 
	        					p.ref = d.id AND p.visibility != 2 $session_condition 
	        			ORDER BY path";
			}
 
			$db_result = Database::query($sql);
			while ($obj = Database::fetch_object($db_result)) {
				$doc = new Document($obj->id, $obj->path, $obj->comment, $obj->title, $obj->filetype, $obj->size);
				$this->course->add_resource($doc);
			}
		} else {
			
			if (!empty($this->course->type) && $this->course->type=='partial')
        		$sql = 'SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size FROM '.$table_doc.' d, '.$table_prop.' p 
						WHERE 	d.c_id = '.$course_id.' AND 
			            		p.c_id = '.$course_id.' AND
								tool = \''.TOOL_DOCUMENT.'\' AND p.ref = d.id AND p.visibility != 2 AND path  NOT LIKE \'/images/gallery%\' AND d.session_id = 0 ORDER BY path';
	        else
	        	$sql = 'SELECT d.id, d.path, d.comment, d.title, d.filetype, d.size  
	        			FROM '.$table_doc.' d, '.$table_prop.' p
	        			WHERE 	d.c_id = '.$course_id.' AND 
			            		p.c_id = '.$course_id.' AND 
	        					tool = \''.TOOL_DOCUMENT.'\' AND p.ref = d.id AND p.visibility != 2 AND d.session_id = 0 ORDER BY path';
 
			$db_result = Database::query($sql);
			while ($obj = Database::fetch_object($db_result)) {
				$doc = new Document($obj->id, $obj->path, $obj->comment, $obj->title, $obj->filetype, $obj->size);
				$this->course->add_resource($doc);
			}
		}
	}
	/**
	 * Build the forums
	 */
	function build_forums() {
		$table = Database :: get_course_table(TABLE_FORUM);
		$course_id = api_get_course_int_id();		
		$sql = "SELECT * FROM $table WHERE c_id = $course_id ";
		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result)) {
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
	function build_forum_category($id) {
		$table = Database :: get_course_table(TABLE_FORUM_CATEGORY);
		$course_id = api_get_course_int_id();
		$sql = "SELECT * FROM $table WHERE c_id = $course_id AND cat_id = $id";
		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result)) {
			$forum_category = new ForumCategory($obj->cat_id, $obj->cat_title, $obj->cat_comment, $obj->cat_order, $obj->locked, $obj->session_id);
			$this->course->add_resource($forum_category);
		}
	}
    
	/**
	 * Build the forum-topics
	 */
	function build_forum_topics() {
		$table = Database :: get_course_table(TABLE_FORUM_THREAD);
		$course_id = api_get_course_int_id();		
		$sql = "SELECT * FROM $table WHERE c_id = $course_id ";
		$db_result = Database::query($sql);
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
		$table = Database :: get_course_table(TABLE_FORUM_POST);
		$course_id = api_get_course_int_id();		
		$sql = "SELECT * FROM $table WHERE c_id = $course_id ";
		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result)) {
			$forum_post = new ForumPost($obj->post_id, $obj->post_title, $obj->post_text, $obj->post_date, $obj->poster_id, $obj->poster_name, $obj->post_notification, $obj->post_parent_id, $obj->thread_id, $obj->forum_id, $obj->visible);
			$this->course->add_resource($forum_post);
		}
	}
	
	/**
	 * Build the links
	 */
	function build_links($session_id = 0, $course_code = '', $with_base_content = false) {
		$course_info = api_get_course_info($course_code);
		$course_id 		= $course_info['real_id'];
		
		
		$table = Database :: get_course_table(TABLE_LINK);
		$table_prop = Database :: get_course_table(TABLE_ITEM_PROPERTY);
		
		if (!empty($session_id) && !empty($course_code)) {			
			$session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);                
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }  
			$sql = "SELECT  l.id, l.title, l.url, l.description, l.category_id, l.on_homepage 
					FROM $table l, $table_prop p
					WHERE l.c_id = $course_id AND p.c_id = $course_id AND p.ref=l.id AND p.tool = '".TOOL_LINK."' AND p.visibility != 2 $session_condition  
					ORDER BY l.display_order";
		} else {		
			$sql = "SELECT l.id, l.title, l.url, l.description, l.category_id, l.on_homepage  
					FROM $table l, $table_prop p 
					WHERE l.c_id = $course_id AND p.c_id = $course_id AND p.ref=l.id AND p.tool = '".TOOL_LINK."' AND p.visibility != 2 AND l.session_id = 0 
					ORDER BY l.display_order";
		}

		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result)) {
			$link = new Link($obj->id, $obj->title, $obj->url, $obj->description, $obj->category_id, $obj->on_homepage);
			$this->course->add_resource($link);

			if (!empty($course_code)) {
				$res = $this->build_link_category($obj->category_id,$course_code);
			} else {
				$res = $this->build_link_category($obj->category_id);
			}

			if($res > 0) {
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
		$course_id = api_get_course_int_id();		
		$sql = "SELECT * FROM $table WHERE c_id = $course_id ";
		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result)) {
			$tool_intro = new ToolIntro($obj->id, $obj->intro_text);
			$this->course->add_resource($tool_intro);
		}
	}
	/**
	 * Build a link category
	 */
	function build_link_category($id, $course_code = '') {
		$course_info = api_get_course_info($course_code);
		$course_id 		= $course_info['real_id'];
		
		$link_cat_table = Database :: get_course_table(TABLE_LINK_CATEGORY);

		$sql = "SELECT * FROM $link_cat_table WHERE c_id = $course_id AND id = $id";
		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result)) {
			$link_category = new LinkCategory($obj->id, $obj->category_title, $obj->description, $obj->display_order);
			$this->course->add_resource($link_category);
			return $id;
		}
		return 0;
	}
	/**
	 * Build the Quizzes
	 */
	function build_quizzes($session_id = 0, $course_code = '', $with_base_content = false) {
		$course_info = api_get_course_info($course_code);
		$table_qui = Database :: get_course_table(TABLE_QUIZ_TEST);
		$table_rel = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
		$table_doc = Database :: get_course_table(TABLE_DOCUMENT);
		
		$course_id 		= $course_info['real_id'];		
		
		if (!empty($course_code) && !empty($session_id)) {		    
		    $session_id  = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);                
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }
			$session_id = intval($session_id);
			$sql = "SELECT * FROM $table_qui WHERE c_id = $course_id AND active >=0 $session_condition"; //select only quizzes with active = 0 or 1 (not -1 which is for deleted quizzes)
		} else {			
			$sql = "SELECT * FROM $table_qui WHERE c_id = $course_id AND active >=0 AND session_id = 0"; //select only quizzes with active = 0 or 1 (not -1 which is for deleted quizzes)
		}

		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result)) {
			if (strlen($obj->sound) > 0) {
				$doc = Database::fetch_object(Database::query("SELECT id FROM ".$table_doc." WHERE c_id = $course_id AND path = '/audio/".$obj->sound."'"));
				$obj->sound = $doc->id;
			}
			$quiz = new Quiz($obj->id, $obj->title, $obj->description, $obj->random, $obj->type, $obj->active, $obj->sound, $obj->max_attempt, 
			                 $obj->results_disabled, $obj->access_condition, $obj->start_time, $obj->end_time, $obj->feedback_type, $obj->random_answers, $obj->expired_time, $obj->session_id);
			$sql = 'SELECT * FROM '.$table_rel.' WHERE c_id = '.$course_id.' AND exercice_id = '.$obj->id;
			$db_result2 = Database::query($sql);
			while ($obj2 = Database::fetch_object($db_result2)) {
				$quiz->add_question($obj2->question_id, $obj2->question_order);
			}
			$this->course->add_resource($quiz);
		}

		if (!empty($course_code)) {
			$this->build_quiz_questions($course_code);
		} else {
			$this->build_quiz_questions();
		}
	}
	
	/**
	 * Build the Quiz-Questions
	 */
	function build_quiz_questions($course_code = '') {
		$course_info = api_get_course_info($course_code);
		$course_id 		= $course_info['real_id'];
				
		$table_qui = Database :: get_course_table(TABLE_QUIZ_TEST);
		$table_rel = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
		$table_que = Database :: get_course_table(TABLE_QUIZ_QUESTION);
		$table_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER);

		// Building normal tests.
		$sql = "SELECT * FROM $table_que WHERE c_id = $course_id ";
		
		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result)) {
			$question = new QuizQuestion($obj->id, $obj->question, $obj->description, $obj->ponderation, $obj->type, $obj->position, $obj->picture,$obj->level, $obj->extra);
			$sql = 'SELECT * FROM '.$table_ans.' WHERE question_id = '.$obj->id;
			$db_result2 = Database::query($sql);
			while ($obj2 = Database::fetch_object($db_result2)) {
				$question->add_answer($obj2->id, $obj2->answer, $obj2->correct, $obj2->comment, $obj2->ponderation, $obj2->position, $obj2->hotspot_coordinates, $obj2->hotspot_type);
			}
			$this->course->add_resource($question);
		}

		// Building a fictional test for collecting orphan questions.
		$build_orphan_questions = !empty($_POST['recycle_option']); // When a course is emptied this option should be activated (true).
		$sql = "SELECT * FROM $table_que as questions LEFT JOIN $table_rel as quizz_questions 
				ON questions.id=quizz_questions.question_id LEFT JOIN $table_qui as exercices 
				ON exercice_id=exercices.id 
				WHERE	questions.c_id = $course_id  AND 
						quizz_questions.c_id = $course_id  AND
						exercices.c_id = $course_id  AND
						quizz_questions.exercice_id IS NULL OR 
						exercices.active = -1"; // active = -1 means "deleted" test.
		$db_result = Database::query($sql);
		if (Database::num_rows($db_result) > 0) {
			$build_orphan_questions = true;
			while ($obj = Database::fetch_object($db_result)) {
				$question = new QuizQuestion($obj->id, $obj->question, $obj->description, $obj->ponderation, $obj->type, $obj->position, $obj->picture,$obj->level, $obj->extra);
				$sql = "SELECT * FROM $table_ans WHERE c_id = $course_id AND question_id = ".$obj->id;
				$db_result2 = Database::query($sql);
				while ($obj2 = Database::fetch_object($db_result2)) {
					$question->add_answer($obj2->id, $obj2->answer, $obj2->correct, $obj2->comment, $obj2->ponderation, $obj2->position, $obj2->hotspot_coordinates, $obj2->hotspot_type);
				}
				$this->course->add_resource($question);
			}
		}
		if ($build_orphan_questions) {
			//$this->course->add_resource(new Quiz(-1, get_lang('OrphanQuestions', ''), '', 0, 0, 1, '', 0));
		}
	}
	/**
	 * Build the orphan questions
	 */
	function build_quiz_orphan_questions()
	{
		$table_qui = Database :: get_course_table(TABLE_QUIZ_TEST);
		$table_rel = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION);
		$table_que = Database :: get_course_table(TABLE_QUIZ_QUESTION);
		$table_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER);
		
		$course_id = api_get_course_int_id();
		

		$sql = 'SELECT * FROM '.$table_que.' as questions LEFT JOIN '.$table_rel.' as quizz_questions 
				ON questions.id=quizz_questions.question_id LEFT JOIN '.$table_qui.' as exercices 
				ON exercice_id=exercices.id 
				WHERE 	questions.c_id = '.$course_id.'  AND 
						quizz_questions.c_id = '.$course_id.' AND
						exercices.c_id = '.$course_id.' AND
						quizz_questions.exercice_id IS NULL OR 
						exercices.active = -1';
		$db_result = Database::query($sql);
		if (Database::num_rows($db_result) > 0) {
			$orphan_questions = new Quiz(-1, get_lang('OrphanQuestions', ''), '', 0, 0, 1, '', 0); // Tjis is the fictional test for collecting orphan questions.
			$this->course->add_resource($orphan_questions);
			while ($obj = Database::fetch_object($db_result))
			{
				$question = new QuizQuestion($obj->id, $obj->question, $obj->description, $obj->ponderation, $obj->type, $obj->position, $obj->picture,$obj->level,$obj->extra);
				$sql = 'SELECT * FROM '.$table_ans.' WHERE question_id = '.$obj->id;
				$db_result2 = Database::query($sql);
				while ($obj2 = Database::fetch_object($db_result2))
				{
					$question->add_answer($obj2->id, $obj2->answer, $obj2->correct, $obj2->comment, $obj2->ponderation, $obj2->position, $obj2->hotspot_coordinates, $obj2->hotspot_type);
				}
				$this->course->add_resource($question);
			}
		}
	}
	/**
	 * Build the Surveys
	 */
	function build_surveys() {
		$table_survey = Database :: get_course_table(TABLE_SURVEY);
		$table_question = Database :: get_course_table(TABLE_SURVEY_QUESTION);
		$course_id = api_get_course_int_id();
		
		$sql = 'SELECT * FROM '.$table_survey.' WHERE c_id = '.$course_id.' AND session_id = 0 ';
		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result))
		{
			$survey = new Survey($obj->survey_id, $obj->code,$obj->title,
								$obj->subtitle, $obj->author, $obj->lang,
								$obj->avail_from, $obj->avail_till, $obj->is_shared,
								$obj->template, $obj->intro, $obj->surveythanks,
								$obj->creation_date, $obj->invited, $obj->answered,
								$obj->invite_mail, $obj->reminder_mail);
			$sql = 'SELECT * FROM '.$table_question.' WHERE survey_id = '.$obj->survey_id;
			$db_result2 = Database::query($sql);
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
		
		$course_id = api_get_course_int_id();
		
		$sql = 'SELECT * FROM '.$table_que.' WHERE c_id = '.$course_id.'  ';
		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result))
		{
			$question = new SurveyQuestion($obj->question_id, $obj->survey_id,
											$obj->survey_question, $obj->survey_question_comment,
											$obj->type, $obj->display, $obj->sort,
											$obj->shared_question_id, $obj->max_value);
			$sql = 'SELECT * FROM '.$table_opt.' WHERE c_id = '.$course_id.' AND question_id = '."'".$obj->question_id."'";
			$db_result2 = Database::query($sql);
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
	function build_announcements() {
		$table = Database :: get_course_table(TABLE_ANNOUNCEMENT);
		$course_id = api_get_course_int_id();
		
		$sql = 'SELECT * FROM '.$table.' WHERE c_id = '.$course_id.' AND session_id = 0';
		$db_result = Database::query($sql);
		$table_attachment = Database :: get_course_table(TABLE_ANNOUNCEMENT_ATTACHMENT);
		while ($obj = Database::fetch_object($db_result)) {			
			$sql = 'SELECT path, comment, filename, size  FROM '.$table_attachment.' WHERE c_id = '.$course_id.' AND announcement_id = '.$obj->id.'';
			$result = Database::query($sql);
			$attachment_obj = Database::fetch_object($result);
			$att_path = $att_filename = $att_size = $atth_comment = '';
			if (!empty($attachment_obj)) {
				$att_path 		= $attachment_obj->path;
				$att_filename 	= $attachment_obj->filename;
				$att_size 		= $attachment_obj->size;
				$atth_comment 	= $attachment_obj->comment;
			}			
			$announcement = new Announcement($obj->id, $obj->title, $obj->content, $obj->end_date,$obj->display_order,$obj->email_sent, $att_path, $att_filename, $att_size, $atth_comment);			
			$this->course->add_resource($announcement);
			
		}
	}
	/**
	 * Build the events
	 */
	function build_events()
	{
		$table = Database :: get_course_table(TABLE_AGENDA);
		$course_id = api_get_course_int_id();
		
		$sql = 'SELECT * FROM '.$table.' WHERE c_id = '.$course_id.' AND session_id = 0';
		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result)) {
			$table_attachment = Database :: get_course_table(TABLE_AGENDA_ATTACHMENT);
			$sql = 'SELECT path, comment, filename, size  FROM '.$table_attachment.' WHERE c_id = '.$course_id.' AND agenda_id = '.$obj->id.'';
			$result = Database::query($sql);
			
			$attachment_obj = Database::fetch_object($result);			
			$att_path = $att_filename = $att_size = $atth_comment = '';
			if (!empty($attachment_obj)) {
				$att_path 		= $attachment_obj->path;
				$att_filename 	= $attachment_obj->filename;
				$att_size 		= $attachment_obj->size;
				$atth_comment 	= $attachment_obj->comment;
			}
				
			$event = new Event($obj->id, $obj->title, $obj->content, $obj->start_date, $obj->end_date, $att_path, $att_filename, $att_size, $atth_comment);						
			$this->course->add_resource($event);
		}
		
	}
	/**
	 * Build the course-descriptions
	 */
	function build_course_descriptions($session_id = 0,$course_code = '', $with_base_content = false) {
		$course_info = api_get_course_info($course_code);
		$course_id 		= $course_info['real_id'];
		
		$table = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);
		
		if (!empty($session_id) && !empty($course_code)) {
			
			$session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);                
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }  
           
			$sql = 'SELECT * FROM '.$table. ' WHERE c_id = '.$course_id.' '.$session_condition;
		} else {
			$table = Database :: get_course_table(TABLE_COURSE_DESCRIPTION);
			$sql = 'SELECT * FROM '.$table. ' WHERE c_id = '.$course_id.'  AND session_id = 0';
		}

		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result)) {
			$cd = new CourseDescription($obj->id, $obj->title, $obj->content, $obj->description_type);
			$this->course->add_resource($cd);
		}
	}
	/**
	 * Build the learnpaths
	 */
	function build_learnpaths($session_id = 0,$course_code = '', $with_base_content = false) {
		
		$course_info 	= api_get_course_info($course_code);
		$course_id 		= $course_info['real_id'];
		$table_main 	= Database::get_course_table(TABLE_LP_MAIN);
		$table_item 	= Database::get_course_table(TABLE_LP_ITEM);
		$table_tool 	= Database::get_course_table(TABLE_TOOL_LIST);
		
		if (!empty($session_id) && !empty($course_code)) {
			$session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);                
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }            
			$sql = 'SELECT * FROM '.$table_main.' WHERE c_id = '.$course_id.'  '.$session_condition;
		} else {			
			$sql = 'SELECT * FROM '.$table_main.' WHERE c_id = '.$course_id.' AND session_id = 0';
		}		 
		$db_result = Database::query($sql);

		while ($obj = Database::fetch_object($db_result)) {
			$items = array();
			$sql_items = "SELECT * FROM ".$table_item." WHERE c_id = '$course_id' AND lp_id = ".$obj->id."";
			$db_items = Database::query($sql_items);
			while ($obj_item = Database::fetch_object($db_items)) {
				$item['id']				   = $obj_item->id;
				$item['item_type'] 		   = $obj_item->item_type;
				$item['ref'] 			   = $obj_item->ref;
				$item['title'] 			   = $obj_item->title;
				$item['description'] 	   = $obj_item->description;
				$item['path'] 			   = $obj_item->path;
				$item['min_score'] 		   = $obj_item->min_score;
				$item['max_score'] 		   = $obj_item->max_score;
				$item['mastery_score'] 	   = $obj_item->mastery_score;
				$item['parent_item_id']    = $obj_item->parent_item_id;
				$item['previous_item_id']  = $obj_item->previous_item_id;
				$item['next_item_id'] 	   = $obj_item->next_item_id;
				$item['display_order']     = $obj_item->display_order;
				$item['prerequisite']	   = $obj_item->prerequisite;
				$item['parameters'] 	   = $obj_item->parameters;
				$item['launch_data'] 	   = $obj_item->launch_data;
				$item['audio'] 			   = $obj_item->audio;				
				$items[] = $item;
			}

			$sql_tool = "SELECT id FROM ".$table_tool." WHERE c_id = $course_id AND  (link LIKE '%lp_controller.php%lp_id=".$obj->id."%' and image='scormbuilder.gif') AND visibility='1'";
			$db_tool = Database::query($sql_tool);

			if(Database::num_rows($db_tool)) {
				$visibility='1';
			} else {
				$visibility='0';
			}

			$lp = new CourseCopyLearnpath($obj->id,
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
								$obj->author,
								$obj->preview_image,
								$obj->use_max_score,
								$obj->autolunch,
								$obj->created_on,
								$obj->modified_on,
								$obj->publicated_on,
								$obj->expired_on,														
								$obj->session_id,
								$items);
			$this->course->add_resource($lp);
		}

		//save scorm directory (previously build_scorm_documents())
		$i = 1;
		if ($dir=@opendir($this->course->backup_path.'/scorm')) {
			while($file=readdir($dir)) {
				if(is_dir($this->course->backup_path.'/scorm/'.$file) && !in_array($file,array('.','..'))) {
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
	function build_glossary($session_id = 0, $course_code = '', $with_base_content = false) {
		$course_info 	= api_get_course_info($course_code);
		$table_glossary = Database :: get_course_table(TABLE_GLOSSARY);
		$course_id 		= $course_info['real_id'];		
		
		if (!empty($session_id) && !empty($course_code)) {			
			$session_id = intval($session_id);
		    if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);                
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }
            //@todo check this queries are the same ... 
	        if (!empty($this->course->type) && $this->course->type=='partial') {
	        	$sql = 'SELECT * FROM '.$table_glossary.' g WHERE g.c_id = '.$course_id.' '.$session_condition;
	        } else {
	        	$sql = 'SELECT * FROM '.$table_glossary.' g WHERE g.c_id = '.$course_id.' '.$session_condition;
	        }
		} else {
			$table_glossary = Database :: get_course_table(TABLE_GLOSSARY);
			//@todo check this queries are the same ... ayayay 
	        if (!empty($this->course->type) && $this->course->type=='partial') {
	        	$sql = 'SELECT * FROM '.$table_glossary.' g WHERE g.c_id = '.$course_id.' AND session_id = 0';
	        } else {
	        	$sql = 'SELECT * FROM '.$table_glossary.' g WHERE g.c_id = '.$course_id.' AND session_id = 0';
	        }
		}
		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result)) {
			$doc = new Glossary($obj->glossary_id, $obj->name, $obj->description, $obj->display_order);
			$this->course->add_resource($doc);
		}
	}

	/*
	 * build session course by jhon
	 * */
	function build_session_course(){
		$tbl_session = Database::get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		$list_course = Database::get_course_list();
		$list = array();
		foreach($list_course as $_course) {
			$this->course = new Course();
			$this->course->code = $_course['code'];
			$this->course->type = 'partial';
			$this->course->path = api_get_path(SYS_COURSE_PATH).$_course['directory'].'/';
			$this->course->backup_path = api_get_path(SYS_COURSE_PATH).$_course['directory'];
			$this->course->encoding = api_get_system_encoding(); //current platform encoding
			$code_course = $_course['code'];
			$sql_session = "SELECT id, name, course_code  FROM $tbl_session_course
				INNER JOIN  $tbl_session ON id_session = id
				WHERE course_code = '$code_course' ";
			$query_session = Database::query($sql_session);
			while ($rows_session = Database::fetch_assoc($query_session)) {
				$session = new CourseSession($rows_session['id'], $rows_session['name']);
				$this->course->add_resource($session);
			}
			$list[] = $this->course;
		}
		return $list;
	}

	function build_wiki($session_id = 0, $course_code = 0, $with_base_content = false) {
		$course_info 	= api_get_course_info($course_code);
		$tbl_wiki 		= Database::get_course_table(TABLE_WIKI);
		
		$course_id 		= $course_info['real_id'];
				
		if (!empty($session_id) && !empty($course_code)) {			
			 $session_id = intval($session_id);
            if ($with_base_content) {
                $session_condition = api_get_session_condition($session_id, true, true);                
            } else {
                $session_condition = api_get_session_condition($session_id, true);
            }            
	        $sql = 'SELECT * FROM ' . $tbl_wiki . ' WHERE c_id = '.$course_id.' '.$session_condition;
		} else {
			$tbl_wiki = Database::get_course_table(TABLE_WIKI);
	        $sql = 'SELECT * FROM ' . $tbl_wiki . ' WHERE c_id = '.$course_id.' AND session_id = 0';
		}
		$db_result = Database::query($sql);
		while ($obj = Database::fetch_object($db_result)) {
			$wiki = new Wiki($obj->id, $obj->page_id, $obj->reflink, $obj->title, $obj->content, $obj->user_id, $obj->group_id, $obj->dtime, $obj->progress, $obj->version);
			$this->course->add_resource($wiki);
		}
	}
	
	/**
	* Build the Surveys
	*/
	function build_thematic() {
		$table_thematic			= Database :: get_course_table(TABLE_THEMATIC);
		$table_thematic_advance = Database :: get_course_table(TABLE_THEMATIC_ADVANCE);
		$table_thematic_plan    = Database :: get_course_table(TABLE_THEMATIC_PLAN);
		
		$course_id = api_get_course_int_id();
		
		
		$sql = 'SELECT * FROM '.$table_thematic.' WHERE c_id = '.$course_id.' AND session_id = 0 ';
		$db_result = Database::query($sql);
		while ($row = Database::fetch_array($db_result,'ASSOC')) {
			$thematic = new Thematic($row);
			$sql = 'SELECT * FROM '.$table_thematic_advance.' WHERE c_id = '.$course_id.' AND thematic_id = '.$row['id'];
			
			$result = Database::query($sql);
			while ($sub_row = Database::fetch_array($result,'ASSOC')) {				
				$thematic->add_thematic_advance($sub_row);
			}			
			$sql = 'SELECT * FROM '.$table_thematic_plan.' WHERE c_id = '.$course_id.' AND thematic_id = '.$row['id'];
				
			$result = Database::query($sql);
			while ($sub_row = Database::fetch_array($result,'ASSOC')) {
				$thematic->add_thematic_plan($sub_row);
			}			
			$this->course->add_resource($thematic);			
		}		
	}	
	
	/**
	* Build the Surveys
	*/
	function build_attendance() {
		$table_attendance			= Database :: get_course_table(TABLE_ATTENDANCE);
		$table_attendance_calendar  = Database :: get_course_table(TABLE_ATTENDANCE_CALENDAR);
		
		$course_id = api_get_course_int_id();
		
		
		$sql = 'SELECT * FROM '.$table_attendance.' WHERE c_id = '.$course_id.' AND session_id = 0 ';
		$db_result = Database::query($sql);
		while ($row = Database::fetch_array($db_result,'ASSOC')) {
			$obj = new Attendance($row);
			$sql = 'SELECT * FROM '.$table_attendance_calendar.' WHERE c_id = '.$course_id.' AND attendance_id = '.$row['id'];
				
			$result = Database::query($sql);
			while ($sub_row = Database::fetch_array($result,'ASSOC')) {
				$obj->add_attendance_calendar($sub_row);
			}
			$this->course->add_resource($obj);
		}
	}
	
}
