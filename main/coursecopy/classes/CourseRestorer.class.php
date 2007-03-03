<?php


// $Id: CourseRestorer.class.php 11362 2007-03-03 10:14:21Z yannoo $
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
require_once ('LinkCategory.class.php');
require_once ('ForumCategory.class.php');
require_once ('Forum.class.php');
require_once ('ForumTopic.class.php');
require_once ('ForumPost.class.php');
require_once ('CourseDescription.class.php');
require_once ('Learnpath.class.php');
require_once ('mkdirr.php');
require_once ('rmdirr.php');
define('FILE_SKIP', 1);
define('FILE_RENAME', 2);
define('FILE_OVERWRITE', 3);
/**
 * Class to restore items from a course object to a Dokeos-course
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package dokeos.backup
 */
class CourseRestorer
{
	/**
	 * The course-object
	 */
	var $course;
	/**
	 * What to do with files with same name (FILE_SKIP, FILE_RENAME or
	 * FILE_OVERWRITE)
	 */
	var $file_option;
	/**
	 * Create a new CourseRestorer
	 */
	function CourseRestorer($course)
	{
		$this->course = $course;
		$this->file_option = FILE_RENAME;
	}
	/**
	 * Set the file-option
	 * @param constant $options What to do with files with same name (FILE_SKIP,
	 * FILE_RENAME or FILE_OVERWRITE)
	 */
	function set_file_option($option)
	{
		$this->file_option = $option;
	}
	/**
	 * Restore a course.
	 * @param string $destination_course_code The code of the Dokeos-course in
	 * which the resources should be stored. Default: Current Dokeos-course.
	 */
	function restore($destination_course_code = '')
	{
		if ($destination_course_code == '')
		{
			$course_info = api_get_course_info();
			$this->course->destination_db = $course_info['dbName'];
			$this->course->destination_path = $course_info['path'];
		}
		else
		{
			$course_info = Database :: get_course_info($destination_course_code);
			$this->course->destination_db = $course_info['database'];
			$this->course->destination_path = $course_info['directory'];
		}
		$this->restore_links();
		$this->restore_tool_intro();
		$this->restore_events();
		$this->restore_announcements();
		$this->restore_documents();
		$this->restore_scorm_documents();
		$this->restore_course_descriptions();
		//$this->restore_forums();
		$this->restore_quizzes(); // after restore_documents! (for correct import of sound/video)
		$this->restore_learnpaths();
		// Restore the item properties
		$table = Database :: get_course_table(TABLE_ITEM_PROPERTY, $this->course->destination_db);
		foreach ($this->course->resources as $type => $resources)
		{
			foreach ($resources as $id => $resource)
			{
				foreach ($resource->item_properties as $property)
				{
					// First check if there isn't allready a record for this resource
					$sql = "SELECT * FROM $table WHERE tool = '".$property['tool']."' AND ref = '".$resource->destination_id."'";
					$res = api_sql_query($sql,__FILE__,__LINE__);
					if( Database::num_rows($res) == 0)
					{
						// The to_group_id and to_user_id are set to default values as users/groups possibly not exist in the target course
						$sql = "INSERT INTO $table SET
												tool = '".$property['tool']."',
												insert_user_id = '".$property['insert_user_id']."',
												insert_date = '".$property['insert_date']."',
												lastedit_date = '".$property['lastedit_date']."',
												ref = '".$resource->destination_id."',
												lastedit_type = '".$property['lastedit_type']."',
												lastedit_user_id = '".$property['lastedit_user_id']."',
												visibility = '".$property['visibility']."',
												start_visible = '".$property['start_visible']."',
												end_visible = '".$property['end_visible']."',
												to_user_id  = '".$property['to_user_id']."',
												to_group_id = '0'";
												;
						api_sql_query($sql, __FILE__, __LINE__);
					}
				}
			}
		}
		// Restore the linked-resources
		$table = Database :: get_course_table(TABLE_LINKED_RESOURCES, $this->course->destination_db);
		foreach ($this->course->resources as $type => $resources)
		{
			foreach ($resources as $id => $resource)
			{
				$linked_resources = $resource->get_linked_resources();
				foreach ($linked_resources as $to_type => $to_ids)
				{
					foreach ($to_ids as $index => $to_id)
					{
						$to_resource = $this->course->resources[$to_type][$to_id];
						$sql = "INSERT INTO ".$table." SET source_type = '".$type."', source_id = '".$resource->destination_id."', resource_type='".$to_type."', resource_id='".$to_resource->destination_id."' ";
						api_sql_query($sql, __FILE__, __LINE__);
					}
				}
			}
		}
	}
	/**
	 * Restore documents
	 */
	function restore_documents()
	{
		if ($this->course->has_resources(RESOURCE_DOCUMENT))
		{
			$table = Database :: get_course_table(TABLE_DOCUMENT, $this->course->destination_db);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_DOCUMENT] as $id => $document)
			{
				$path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/';
				mkdirr(dirname($path.$document->path), 0755);
				if ($document->file_type == DOCUMENT)
				{
					if (file_exists($path.$document->path))
					{
						switch ($this->file_option)
						{
							case FILE_OVERWRITE :
								copy($this->course->backup_path.'/'.$document->path, $path.$document->path);
								$sql = "SELECT id FROM ".$table." WHERE path='/".substr($document->path, 9)."'";
								$res = api_sql_query($sql, __FILE__, __LINE__);
								$obj = Database::fetch_object($res);
								$this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $obj->id;
								$sql = "UPDATE ".$table." SET comment = '".Database::escape_string($document->comment)."', title='".Database::escape_string($document->title)."', size='".$document->size."' WHERE id = '".$obj->id."'";
								api_sql_query($sql, __FILE__, __LINE__);
								break;
							case FILE_SKIP :
								$sql = "SELECT id FROM ".$table." WHERE path='/".Database::escape_string(substr($document->path, 9))."'";
								$res = api_sql_query($sql, __FILE__, __LINE__);
								$obj = Database::fetch_object($res);
								$this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $obj->id;
								break;
							case FILE_RENAME :
								$i = 1;
								$ext = explode('.', basename($document->path));
								if (count($ext) > 1)
								{
									$ext = array_pop($ext);
									$file_name_no_ext = substr($document->path, 0, - (strlen($ext) + 1));
									$ext = '.'.$ext;
								}
								else
								{
									$ext = '';
									$file_name_no_ext = $document->path;
								}
								$new_file_name = $file_name_no_ext.'_'.$i.$ext;
								$file_exists = file_exists($path.$new_file_name);
								while ($file_exists)
								{
									$i ++;
									$new_file_name = $file_name_no_ext.'_'.$i.$ext;
									$file_exists = file_exists($path.$new_file_name);
								}
								copy($this->course->backup_path.'/'.$document->path, $path.$new_file_name);
								$sql = "INSERT INTO ".$table." SET path = '/".Database::escape_string(substr($new_file_name, 9))."', comment = '".Database::escape_string($document->comment)."', title = '".Database::escape_string($document->title)."' ,filetype='".$document->file_type."', size= '".$document->size."'";
								api_sql_query($sql, __FILE__, __LINE__);
								$this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = Database::get_last_insert_id();
								break;
						} // end switch
					} // end if file exists
					else
					{
						copy($this->course->backup_path.'/'.$document->path, $path.$document->path);
						$sql = "INSERT INTO ".$table." SET path = '/".substr($document->path, 9)."', comment = '".Database::escape_string($document->comment)."', title = '".Database::escape_string($document->title)."' ,filetype='".$document->file_type."', size= '".$document->size."'";
						api_sql_query($sql, __FILE__, __LINE__);
						$this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = Database::get_last_insert_id();
					} // end file doesn't exist
				}
				else
				{
					$sql = "SELECT id FROM ".$table." WHERE path = '/".Database::escape_string(substr($document->path, 9))."'";
					$res = api_sql_query($sql,__FILE__,__LINE__);
					if( Database::num_rows($res)> 0)
					{
						$obj = Database::fetch_object($res);
						$this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = $obj->id;	
					}
					else
					{
						$sql = "INSERT INTO ".$table." SET path = '/".Database::escape_string(substr($document->path, 9))."', comment = '".Database::escape_string($document->comment)."', title = '".Database::escape_string($document->title)."' ,filetype='".$document->file_type."', size= '".$document->size."'";
						api_sql_query($sql, __FILE__, __LINE__);
						$this->course->resources[RESOURCE_DOCUMENT][$id]->destination_id = Database::get_last_insert_id();
					}
				} // end folder
			} // end for each
		}
	}

	/**
	 * Restore scorm documents
	 */
	function restore_scorm_documents()
	{
		if ($this->course->has_resources(RESOURCE_SCORM))
		{
			$resources = $this->course->resources;

			foreach ($resources[RESOURCE_SCORM] as $id => $document)
			{
				$path = api_get_path(SYS_COURSE_PATH).$this->course->destination_path.'/';

				mkdirr(dirname($path.$document->path), 0755);

				if (file_exists($path.$document->path))
				{
					switch ($this->file_option)
					{
						case FILE_OVERWRITE :
							rmdirr($path.$document->path);

							copyDirTo($this->course->backup_path.'/'.$document->path, $path.dirname($document->path), false);

							break;
						case FILE_SKIP :
							break;
						case FILE_RENAME :
							$i = 1;

							$ext = explode('.', basename($document->path));

							if (count($ext) > 1)
							{
								$ext = array_pop($ext);
								$file_name_no_ext = substr($document->path, 0, - (strlen($ext) + 1));
								$ext = '.'.$ext;
							}
							else
							{
								$ext = '';
								$file_name_no_ext = $document->path;
							}

							$new_file_name = $file_name_no_ext.'_'.$i.$ext;
							$file_exists = file_exists($path.$new_file_name);

							while ($file_exists)
							{
								$i ++;
								$new_file_name = $file_name_no_ext.'_'.$i.$ext;
								$file_exists = file_exists($path.$new_file_name);
							}

							rename($this->course->backup_path.'/'.$document->path,$this->course->backup_path.'/'.$new_file_name);

							copyDirTo($this->course->backup_path.'/'.$new_file_name, $path.dirname($new_file_name), false);

							rename($this->course->backup_path.'/'.$new_file_name,$this->course->backup_path.'/'.$document->path);

							break;
					} // end switch
				} // end if file exists
				else
				{
					copyDirTo($this->course->backup_path.'/'.$document->path, $path.dirname($document->path), false);
				}
			} // end for each
		}
	}

	/**
	 * Restore forums
	 */
	function restore_forums()
	{
		if ($this->course->has_resources(RESOURCE_FORUM))
		{
			$table_forum = Database :: get_course_table(TABLE_FORUM, $this->course->destination_db);
			$table_topic = Database :: get_course_table(TABLE_FORUM_POST, $this->course->destination_db);
			$table_post = Database :: get_course_table(TABLE_FORUM_POST, $this->course->destination_db);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_FORUM] as $id => $forum)
			{
				$cat_id = $this->restore_forum_category($forum->category_id);
				$sql = "INSERT INTO ".$table_forum." SET forum_name = '".Database::escape_string($forum->title)."', forum_desc = '".Database::escape_string($forum->description)."', cat_id = '".$cat_id."', forum_access='2'";
				api_sql_query($sql, __FILE__, __LINE__);
				$new_id = Database::get_last_insert_id();
				$this->course->resources[RESOURCE_FORUM][$id]->destination_id = $new_id;
				$forum_topics = 0;
				if (is_array($this->course->resources[RESOURCE_FORUMTOPIC]))
				{
					foreach ($this->course->resources[RESOURCE_FORUMTOPIC] as $topic_id => $topic)
					{
						if ($topic->forum_id == $id)
						{
							$this->restore_topic($topic_id, $new_id);
							$forum_topics ++;
						}
					}
				}
				if ($forum_topics > 0)
				{
					$last_post = $this->course->resources[RESOURCE_FORUMPOST][$forum->last_post];
					$sql = "UPDATE ".$table_forum." SET forum_topics = ".$forum_topics.", forum_last_post_id = ".$last_post->destination_id." WHERE forum_id = '".$new_id."' ";
					api_sql_query($sql, __FILE__, __LINE__);
				}
			}
		}
	}
	/**
	 * Restore forum-categories
	 */
	function restore_forum_category($id)
	{
		$forum_cat_table = Database :: get_course_table(TABLE_FORUM_CATEGORY, $this->course->destination_db);
		$resources = $this->course->resources;
		$forum_cat = $resources[RESOURCE_FORUMCATEGORY][$id];
		if (!$forum_cat->is_restored())
		{
			$sql = "INSERT INTO ".$forum_cat_table." SET cat_title = '".Database::escape_string($forum_cat->title.' ('.$this->course->code.')')."'";
			api_sql_query($sql, __FILE__, __LINE__);
			$new_id = Database::get_last_insert_id();
			$this->course->resources[RESOURCE_FORUMCATEGORY][$id]->destination_id = $new_id;
			return $new_id;
		}
		return $this->course->resources[RESOURCE_FORUMCATEGORY][$id]->destination_id;
	}
	/**
	 * Restore a forum-topic
	 */
	function restore_topic($id, $forum_id)
	{
		$table = Database :: get_course_table(TABLE_FORUM_POST, $this->course->destination_db);
		$resources = $this->course->resources;
		$topic = $resources[RESOURCE_FORUMTOPIC][$id];
		$sql = "INSERT INTO ".$table." SET topic_title = '".Database::escape_string($topic->title)."', topic_time = '".$topic->time."', nom = '".Database::escape_string($topic->lastname)."', prenom = '".Database::escape_string($topic->firstname)."', topic_notify = '".$topic->topic_notify."', forum_id = '".$forum_id."'";
		api_sql_query($sql, __FILE__, __LINE__);
		$new_id = Database::get_last_insert_id();
		$this->course->resources[RESOURCE_FORUMTOPIC][$id]->destination_id = $new_id;
		$topic_replies = -1;
		foreach ($this->course->resources[RESOURCE_FORUMPOST] as $post_id => $post)
		{
			if ($post->topic_id == $id)
			{
				$topic_replies ++;
				$this->restore_post($post_id, $new_id, $forum_id);
			}
		}
		if ($topic_replies >= 0)
		{
			$last_post = $this->course->resources[RESOURCE_FORUMPOST][$topic->last_post];
			$sql = "UPDATE ".$table." SET topic_replies = '".$topic_replies."', topic_last_post_id = ".$last_post->destination_id;
			api_sql_query($sql, __FILE__, __LINE__);
		}
		return $new_id;
	}
	/**
	 * restore a forum-post
	 * @todo restore tree-structure of posts.
	 */
	function restore_post($id, $topic_id, $forum_id)
	{
		$table_post = Database :: get_course_table(TABLE_FORUM_POST, $this->course->destination_db);
		$table_posttext = Database :: get_course_table(TOOL_FORUM_POST_TEXT_TABLE, $this->course->destination_db);
		$resources = $this->course->resources;
		$post = $resources[RESOURCE_FORUMPOST][$id];
		$sql = "INSERT INTO ".$table_post." SET topic_id = '".$topic_id."', post_time = '".$post->post_time."', forum_id = '".$forum_id."', nom = '".Database::escape_string($post->lastname)."', prenom = '".Database::escape_string($post->firstname)."', topic_notify = '".$post->topic_notify."', poster_ip = '".$post->poster_ip."'";
		api_sql_query($sql, __FILE__, __LINE__);
		$new_id = Database::get_last_insert_id();
		$this->course->resources[RESOURCE_FORUMPOST][$id]->destination_id = $new_id;
		$sql = "INSERT INTO ".$table_posttext." SET post_text = '".Database::escape_string($post->text)."', post_title = '".Database::escape_string($post->title)."', post_id = '".$new_id."'";
		api_sql_query($sql, __FILE__, __LINE__);
		return $new_id;
	}
	/**
	 * Restore links
	 */
	function restore_links()
	{
		if ($this->course->has_resources(RESOURCE_LINK))
		{
			$link_table = Database :: get_course_table(TABLE_LINK, $this->course->destination_db);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_LINK] as $id => $link)
			{
				$cat_id = $this->restore_link_category($link->category_id);
				$sql = "SELECT MAX(display_order) FROM  $link_table WHERE category_id='" . Database::escape_string($cat_id). "'";
				$result = api_sql_query($sql, __FILE__, __LINE__);
    			list($max_order) = Database::fetch_array($result);
				$sql = "INSERT INTO ".$link_table." SET url = '".Database::escape_string($link->url)."', title = '".Database::escape_string($link->title)."', description = '".Database::escape_string($link->description)."', category_id='".$cat_id."', on_homepage = '".$link->on_homepage."', display_order='".($max_order+1)."'";
				api_sql_query($sql, __FILE__, __LINE__);
				$this->course->resources[RESOURCE_LINK][$id]->destination_id = Database::get_last_insert_id();
			}
		}
	}
	/**
	 * Restore tool intro
	 */
	function restore_tool_intro()
	{
		if ($this->course->has_resources(RESOURCE_TOOL_INTRO))
		{
			$tool_intro_table = Database :: get_course_table(TABLE_TOOL_INTRO, $this->course->destination_db);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_TOOL_INTRO] as $id => $tool_intro)
			{
				$sql = "DELETE FROM ".$tool_intro_table." WHERE id='".Database::escape_string($tool_intro->id)."'";
				api_sql_query($sql, __FILE__, __LINE__);

				$sql = "INSERT INTO ".$tool_intro_table." SET id='".Database::escape_string($tool_intro->id)."', intro_text = '".Database::escape_string($tool_intro->intro_text)."'";
				api_sql_query($sql, __FILE__, __LINE__);

				$this->course->resources[RESOURCE_TOOL_INTRO][$id]->destination_id = Database::get_last_insert_id();
			}
		}
	}
	/**
	 * Restore a link-category
	 */
	function restore_link_category($id)
	{
		if ($id == 0)
			return 0;
		$link_cat_table = Database :: get_course_table(TABLE_LINK_CATEGORY, $this->course->destination_db);
		$resources = $this->course->resources;
		$link_cat = $resources[RESOURCE_LINKCATEGORY][$id];
		if (!$link_cat->is_restored())
		{
			$sql = "SELECT MAX(display_order) FROM  $link_cat_table";
			$result=api_sql_query($sql,__FILE__,__LINE__);
			list($orderMax)=Database::fetch_array($result,'NUM');
			$display_order=$orderMax+1;
			$sql = "INSERT INTO ".$link_cat_table." SET category_title = '".Database::escape_string($link_cat->title)."', description='".Database::escape_string($link_cat->description)."', display_order='".$display_order."' ";
			api_sql_query($sql, __FILE__, __LINE__);
			$new_id = Database::get_last_insert_id();
			$this->course->resources[RESOURCE_LINKCATEGORY][$id]->destination_id = $new_id;
			return $new_id;
		}
		return $this->course->resources[RESOURCE_LINKCATEGORY][$id]->destination_id;
	}
	/**
	 * Restore events
	 */
	function restore_events()
	{
		if ($this->course->has_resources(RESOURCE_EVENT))
		{
			$table = Database :: get_course_table(TABLE_AGENDA, $this->course->destination_db);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_EVENT] as $id => $event)
			{
				$sql = "INSERT INTO ".$table." SET title = '".Database::escape_string($event->title)."', content = '".Database::escape_string($event->content)."', start_date = '".$event->start_date."', end_date = '".$event->end_date."'";
				api_sql_query($sql, __FILE__, __LINE__);
				$this->course->resources[RESOURCE_EVENT][$id]->destination_id = Database::get_last_insert_id();
			}
		}
	}
	/**
	 * Restore course-description
	 */
	function restore_course_descriptions()
	{
		if ($this->course->has_resources(RESOURCE_COURSEDESCRIPTION))
		{
			$table = Database :: get_course_table(TABLE_COURSE_DESCRIPTION, $this->course->destination_db);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_COURSEDESCRIPTION] as $id => $cd)
			{
				$sql = "INSERT INTO ".$table." SET title = '".Database::escape_string($cd->title)."', content = '".Database::escape_string($cd->content)."'";
				api_sql_query($sql, __FILE__, __LINE__);
				$this->course->resources[RESOURCE_COURSEDESCRIPTION][$id]->destination_id = Database::get_last_insert_id();
			}
		}
	}
	/**
	 * Restore announcements
	 */
	function restore_announcements()
	{
		if ($this->course->has_resources(RESOURCE_ANNOUNCEMENT))
		{
			$table = Database :: get_course_table(TABLE_ANNOUNCEMENT, $this->course->destination_db);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_ANNOUNCEMENT] as $id => $announcement)
			{
				$sql = "INSERT INTO ".$table." " .
						"SET title = '".Database::escape_string($announcement->title)."'," .
							"content = '".Database::escape_string($announcement->content)."', " .
							"end_date = '".$announcement->date."', " .
							"display_order = '".$announcement->display_order."', " .
							"email_sent = '".$announcement->email_sent."'";
				api_sql_query($sql, __FILE__, __LINE__);
				$this->course->resources[RESOURCE_ANNOUNCEMENT][$id]->destination_id = Database::get_last_insert_id();
			}
		}
	}
	/**
	 * Restore Quiz
	 */
	function restore_quizzes()
	{
		if ($this->course->has_resources(RESOURCE_QUIZ))
		{
			$table_qui = Database :: get_course_table(TABLE_QUIZ_TEST, $this->course->destination_db);
			$table_rel = Database :: get_course_table(TABLE_QUIZ_TEST_QUESTION, $this->course->destination_db);
			$table_doc = Database :: get_course_table(TABLE_DOCUMENT, $this->course->destination_db);
			$resources = $this->course->resources;
			foreach ($resources[RESOURCE_QUIZ] as $id => $quiz)
			{
				$doc = '';
				if (strlen($quiz->media) > 0)
				{
					if ($this->course->resources[RESOURCE_DOCUMENT][$quiz->media]->is_restored())
					{
						$sql = "SELECT path FROM ".$table_doc." WHERE id = ".$resources[RESOURCE_DOCUMENT][$quiz->media]->destination_id;
						$doc = api_sql_query($sql, __FILE__, __LINE__);
						$doc = Database::fetch_object($doc);
						$doc = str_replace('/audio/', '', $doc->path);
					}
				}
				$sql = "INSERT INTO ".$table_qui." SET title = '".Database::escape_string($quiz->title)."', description = '".Database::escape_string($quiz->description)."', type = '".$quiz->quiz_type."', random = '".$quiz->random."', active = '".$quiz->active."', sound = '".Database::escape_string($doc)."' ";
				api_sql_query($sql, __FILE__, __LINE__);
				$new_id = Database::get_last_insert_id();
				$this->course->resources[RESOURCE_QUIZ][$id]->destination_id = $new_id;
				foreach ($quiz->question_ids as $index => $question_id)
				{
					$qid = $this->restore_quiz_question($question_id);
					$sql = "INSERT IGNORE INTO ".$table_rel." SET question_id = ".$qid.", exercice_id = ".$new_id."";
					api_sql_query($sql, __FILE__, __LINE__);
				}
			}
		}
	}
	/**
	 * Restore quiz-questions
	 */
	function restore_quiz_question($id)
	{
		$resources = $this->course->resources;
		$question = $resources[RESOURCE_QUIZQUESTION][$id];

		$new_id=0;

		if(is_object($question))
		{
			if ($question->is_restored())
			{
				return $question->destination_id;
			}
			$table_que = Database :: get_course_table(TABLE_QUIZ_QUESTION, $this->course->destination_db);
			$table_ans = Database :: get_course_table(TABLE_QUIZ_ANSWER, $this->course->destination_db);
			$sql = "INSERT INTO ".$table_que." SET question = '".addslashes($question->question)."', description = '".addslashes($question->description)."', ponderation = '".addslashes($question->ponderation)."', position = '".addslashes($question->position)."', type='".addslashes($question->quiz_type)."'";
			api_sql_query($sql, __FILE__, __LINE__);
			$new_id = Database::get_last_insert_id();
			foreach ($question->answers as $index => $answer)
			{
				$sql = "INSERT INTO ".$table_ans." SET id= '". ($index +1)."',question_id = '".$new_id."', answer = '".Database::escape_string($answer['answer'])."', correct = '".$answer['correct']."', comment = '".Database::escape_string($answer['comment'])."', ponderation='".$answer['ponderation']."', position = '".$answer['position']."'";
				api_sql_query($sql, __FILE__, __LINE__);
			}
			$this->course->resources[RESOURCE_QUIZQUESTION][$id]->destination_id = $new_id;
		}

		return $new_id;
	}
	/**
	 * Restore learnpaths
	 */
	function restore_learnpaths()
	{
		if ($this->course->has_resources(RESOURCE_LEARNPATH))
		{
			$table_main 	= Database :: get_course_table(TABLE_LEARNPATH_MAIN, $this->course->destination_db);
			$table_chapter 	= Database :: get_course_table(TABLE_LEARNPATH_CHAPTER, $this->course->destination_db);
			$table_item 	= Database :: get_course_table(TABLE_LEARNPATH_ITEM, $this->course->destination_db);
			$table_tool 	= Database::get_course_table(TABLE_TOOL_LIST, $this->course->destination_db);

			$resources = $this->course->resources;
			$prereq_old = array ();
			$item_old_id = array ();

			foreach ($resources[RESOURCE_LEARNPATH] as $id => $lp)
			{
				$sql = "INSERT INTO ".$table_main." SET learnpath_name = '".Database::escape_string($lp->name)."', learnpath_description = '".Database::escape_string($lp->description)."'";
				api_sql_query($sql, __FILE__, __LINE__);

				$new_lp_id = Database::get_last_insert_id();

				if($lp->visibility)
				{
					$sql = "INSERT INTO $table_tool SET name='".Database::escape_string($lp->name)."', link='learnpath/learnpath_handler.php?learnpath_id=$new_lp_id', image='scormbuilder.gif', visibility='1', admin='0', address='squaregrey.gif'";
					api_sql_query($sql, __FILE__, __LINE__);
				}

				foreach ($lp->get_chapters() as $index => $chapter)
				{
					$sql = "INSERT INTO  ".$table_chapter." SET learnpath_id ='".$new_lp_id."' ,chapter_name='".Database::escape_string($chapter['name'])."', chapter_description='".Database::escape_string($chapter['description'])."',display_order='".$chapter['display_order']."' ";
					api_sql_query($sql, __FILE__, __LINE__);
					$new_chap_id = Database::get_last_insert_id();
					foreach ($chapter['items'] as $index => $item)
					{
						if ($item['id'] != 0)
						{
						 // Link in learnpath have types 'Link _self' or 'Link _blank'. We only need 'Link' here.
						 $type_parts = explode(' ',$item['type']);
						 $item['id'] = $this->course->resources[$type_parts[0]][$item['id']]->destination_id;
						}
						$sql = "INSERT INTO ".$table_item." SET chapter_id='".$new_chap_id."', item_type='".$item['type']."', item_id='".$item['id']."', display_order = '".$item['display_order']."', title = '".Database::escape_string($item['title'])."', description ='".Database::escape_string($item['description'])."', prereq_id='".$item['prereq']."', prereq_type = '".$item['prereq_type']."', prereq_completion_limit = '".$item['prereq_completion_limit']."' ";
						api_sql_query($sql, __FILE__, __LINE__);
						$new_item_id = Database::get_last_insert_id();
						if ($item['prereq'] != '')
						{
							$prereq_old[$new_item_id] = $item['prereq'];
						}
						$item_id_old[$item['ref_id']] = $new_item_id;
					}
				}

				foreach ($prereq_old as $new_item_id => $prereq_old_id)
				{
					$prereq_new_id = $item_id_old[$prereq_old_id];
					$sql = "UPDATE ".$table_item." SET prereq_id = '".$prereq_new_id."' WHERE id = '".$new_item_id."'";
					api_sql_query($sql, __FILE__, __LINE__);
				}

				$this->course->resources[RESOURCE_LEARNPATH][$id]->destination_id = $new_lp_id;
			}
		}
	}
}
?>