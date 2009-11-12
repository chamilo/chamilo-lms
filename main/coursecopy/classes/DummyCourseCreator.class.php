<?php
// $Id: DummyCourseCreator.class.php 15087 2008-04-25 04:37:14Z yannoo $
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

require_once 'Course.class.php';
require_once 'Document.class.php';
require_once 'Event.class.php';
require_once 'Link.class.php';
require_once 'LinkCategory.class.php';
require_once 'ForumCategory.class.php';
require_once 'Forum.class.php';
require_once 'ForumTopic.class.php';
require_once 'ForumPost.class.php';
require_once 'CourseDescription.class.php';
require_once 'Learnpath.class.php';
require_once 'CourseRestorer.class.php';

class DummyCourseCreator
{
	/**
	 * The dummy course
	 */
	var $course;
	/**
	 *
	 */
	var $default_property = array();
	/**
	 * Create the dummy course
	 */
	function create_dummy_course($course_code)
	{
		$this->default_property['insert_user_id'] = '1';
		$this->default_property['insert_date'] = date('Y-m-d H:i:s');
		$this->default_property['lastedit_date'] = date('Y-m-d H:i:s');
		$this->default_property['lastedit_user_id'] = '1';
		$this->default_property['to_group_id'] = '0';
		$this->default_property['to_user_id'] = null;
		$this->default_property['visibility'] = '1';
		$this->default_property['start_visible'] = '0000-00-00 00:00:00';
		$this->default_property['end_visible'] =  '0000-00-00 00:00:00';

		$course = Database::get_course_info($course_code);
		$this->course = new Course();
		$tmp_path = api_get_path(SYS_COURSE_PATH).$course['directory'].'/document/tmp_'.uniqid('');
		@mkdir($tmp_path, 0755, true);
		$this->course->backup_path = $tmp_path;
		$this->create_dummy_links();
		$this->create_dummy_events();
		$this->create_dummy_forums();
		$this->create_dummy_announcements();
		$this->create_dummy_documents();
		$this->create_dummy_learnpaths();
		$cr = new CourseRestorer($this->course);
		$cr->set_file_option(FILE_OVERWRITE);
		$cr->restore($course_code);
		rmdirr($tmp_path);
	}
	/**
	 * Create dummy documents
	 */
	function create_dummy_documents()
	{
		$course = api_get_course_info();
		$course_doc_path = $this->course->backup_path.'/document/';
		$number_of_documents = rand(10, 30);
		$extensions = array ('html', 'doc');
		$directories = array();
		$property = $this->default_property;
		$property['lastedit_type'] = 'DocumentAdded';
		$property['tool'] = TOOL_DOCUMENT;
		$doc_id = 0;
		for ($doc_id = 1; $doc_id < $number_of_documents; $doc_id ++)
		{
			$path = '';
			$doc_type = rand(0, count($extensions) - 1);
			$extension = $extensions[$doc_type];
			$filename = $this->get_dummy_content('title').'_'.$doc_id.'.'.$extension;
			$content = $this->get_dummy_content('text');
			$dirs = rand(0, 3);
			for ($i = 0; $i < $dirs; $i ++)
			{
				$path .= 'directory/';
				$directories[$path] = 1;
			}
			$dir_to_make = $course_doc_path.$path;
			if (!is_dir($dir_to_make))
			{
				@mkdir($dir_to_make, 0755, true);
			}
			$file = $course_doc_path.$path.$filename;
			$fp = fopen($file, 'w');
			fwrite($fp, $content);
			fclose($fp);
			$size = filesize($file);
			$document = new Document($doc_id, '/'.$path.$filename,$this->get_dummy_content('description'),$this->get_dummy_content('title'), 'file', $size);
			$document->item_properties[] = $property;
			$this->course->add_resource($document);
		}
		foreach($directories as $path => $flag)
		{
			$path = substr($path,0,strlen($path)-1);
			$document = new Document($doc_id++,'/'.$path, $this->get_dummy_content('description'),$this->get_dummy_content('title'),'folder',0);
			$property['lastedit_type'] = 'FolderCreated';
			$document->item_properties[] = $property;
			$this->course->add_resource($document);
		}
	}
	/**
	 * Create dummy announcements
	 */
	function create_dummy_announcements()
	{
		$property = $this->default_property;
		$property['lastedit_type'] = 'AnnouncementAdded';
		$property['tool'] = TOOL_ANNOUNCEMENT;
		$number_of_announcements = rand(10, 30);
		for ($i = 0; $i < $number_of_announcements; $i ++)
		{
			$time = mktime(rand(1, 24), rand(1, 60), 0, rand(1, 12), rand(1, 28), intval(date('Y')));
			$date = date('Y-m-d', $time);
			$announcement = new Announcement($i,$this->get_dummy_content('title'),$this->get_dummy_content('text'), $date,0);
			$announcement->item_properties[] = $property;
			$this->course->add_resource($announcement);
		}
	}
	/**
	 * Create dummy events
	 */
	function create_dummy_events()
	{
		$number_of_events = rand(10, 30);
		$property = $this->default_property;
		$property['lastedit_type'] = 'AgendaAdded';
		$property['tool'] = TOOL_CALENDAR_EVENT;
		for ($i = 0; $i < $number_of_events; $i ++)
		{
			$hour = rand(1,24);
			$minute = rand(1,60);
			$second = rand(1,60);
			$day = rand(1,28);
			$month = rand(1,12);
			$year = intval(date('Y'));
			$time = mktime($hour,$minute,$second,$month,$day,$year);
			$start_date = date('Y-m-d H:m:s', $time);
			$hour = rand($hour,24);
			$minute = rand($minute,60);
			$second = rand($second,60);
			$day = rand($day,28);
			$month = rand($month,12);
			$year = intval(date('Y'));
			$time = mktime($hour,$minute,$second,$month,$day,$year);
			$end_date = date('Y-m-d H:m:s', $time);
			$event = new Event($i, $this->get_dummy_content('title'), $this->get_dummy_content('text'), $start_date, $end_date);
			$event->item_properties[] = $property;
			$this->course->add_resource($event);
		}
	}
	/**
	 * Create dummy links
	 */
	function create_dummy_links()
	{
		// create categorys
		$number_of_categories = rand(5, 10);
		for ($i = 0; $i < $number_of_categories; $i ++)
		{
			$linkcat = new LinkCategory($i, $this->get_dummy_content('title'), $this->get_dummy_content('description'),$i);
			$this->course->add_resource($linkcat);
		}
		// create links
		$number_of_links = rand(5, 50);
		$on_homepage = rand(0,20) == 0 ? 1 : 0;
		$property = $this->default_property;
		$property['lastedit_type'] = 'LinkAdded';
		$property['tool'] = TOOL_LINK;
		for ($i = 0; $i < $number_of_links; $i ++)
		{
			$link = new Link($i, $this->get_dummy_content('title'), 'http://www.google.com/search?q='.$this->get_dummy_content('title'), $this->get_dummy_content('description'), rand(0, $number_of_categories -1),$on_homepage);
			$link->item_properties[] = $property;
			$this->course->add_resource($link);
		}
	}
	/**
	 * Create dummy forums
	 */
	function create_dummy_forums()
	{
		$number_of_categories = rand(2, 6);
		$number_of_forums = rand(5, 50);
		$number_of_topics = rand(30, 100);
		$number_of_posts = rand(100, 1000);
		$last_forum_post = array ();
		$last_topic_post = array ();
		// create categorys
		$order = 1;
		for ($i = 1; $i <= $number_of_categories; $i ++)
		{
			$forumcat = new ForumCategory($i, $this->get_dummy_content('title'), $this->get_dummy_content('description'), $order, 0, 0);
			$this->course->add_resource($forumcat);
			$order++;
		}
		// create posts
		for ($post_id = 1; $post_id <= $number_of_posts; $post_id ++)
		{
			$topic_id = rand(1, $number_of_topics);
			$last_topic_post[$topic_id] = $post_id;
			$post = new ForumPost($post_id, $this->get_dummy_content('title'), $this->get_dummy_content('text'), date('Y-m-d H:i:s'), 1, 'Dokeos Administrator', 0, 0, $topic_id, 0, 1);
			$this->course->add_resource($post);
		}
		// create topics
		for ($topic_id = 1; $topic_id <= $number_of_topics; $topic_id ++)
		{
			$forum_id = rand(1, $number_of_forums);
			$last_forum_post[$forum_id] = $last_topic_post[$topic_id];
			$topic = new ForumTopic($topic_id, $this->get_dummy_content('title'), '2005-03-31 12:10:00', 'Dokeos', 'Administrator', 0, $forum_id, $last_topic_post[$topic_id]);
			$this->course->add_resource($topic);
		}
		// create forums
		for ($forum_id = 1; $forum_id <= $number_of_forums; $forum_id ++)
		{
			$forum = new Forum($forum_id, $this->get_dummy_content('title'),$this->get_dummy_content('description') , rand(1, $number_of_categories), $last_forum_post[$forum_id]);
			$this->course->add_resource($forum);
		}
	}
	/**
	 * Create dummy learnpaths
	 */
	function create_dummy_learnpaths()
	{
		$number_of_learnpaths = rand(3,5);
		$global_item_id = 1;
		for($i=1; $i<=$number_of_learnpaths;$i++)
		{
		$chapters = array();
		$number_of_chapters = rand(1,6);
		for($chapter_id = 1; $chapter_id <= $number_of_chapters; $chapter_id++)
		{
			$chapter['name'] = $this->get_dummy_content('title');
			$chapter['description'] = $this->get_dummy_content('description');
			$chapter['display_order'] = $chapter_id;
			$chapter['items'] = array();
			$number_of_items = rand(5,20);
			for( $item_id = 1; $item_id<$number_of_items; $item_id++)
			{
				$types = array(RESOURCE_ANNOUNCEMENT, RESOURCE_EVENT, RESOURCE_DOCUMENT,RESOURCE_LINK,RESOURCE_FORUM,RESOURCE_FORUMPOST,RESOURCE_FORUMTOPIC);
				$type = $types[rand(0,count($types)-1)];
				$resources = $this->course->resources[$type];
				$resource = $resources[rand(0,count($resources)-1)];
				$item = array();
				$item['type'] = $resource->type;
				$item['id'] = $resource->source_id;
				$item['display_order'] = $item_id;
				$item['title'] = $this->get_dummy_content('title');
				$item['description'] = $this->get_dummy_content('description');
				$item['ref_id'] = $global_item_id;
				if( rand(0,5) == 1 && $item_id > 1)
				{
					$item['prereq_type'] = 'i';
					$item['prereq'] = rand($global_item_id - $item_id,$global_item_id-1);
				}
				$chapter['items'][] = $item;
				$global_item_id++;
			}
			$chapters[] = $chapter;
		}
		$lp = new Learnpath($i,$this->get_dummy_content('title'),$this->get_dummy_content('description'),1,$chapters);
		$this->course->add_resource($lp);
		}
	}
	/**
	 * Get dummy titles, descriptions and texts
	 */
	function get_dummy_content($type)
	{
		$dummy_text = 'Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Quisque lectus. Duis sodales. Vivamus et nunc. Phasellus interdum est a lorem. Fusce venenatis luctus lectus. Mauris quis turpis ac erat rhoncus suscipit. Phasellus elit dui, semper at, porta ut, egestas ac, enim. Quisque pellentesque, nisl nec consequat mollis, ipsum justo pellentesque nibh, non faucibus odio ante at lorem. Donec vitae pede ut felis ultricies semper. Suspendisse velit nibh, interdum quis, gravida nec, dapibus ac, leo. Cras id sem ut tellus tincidunt scelerisque. Aenean ac magna feugiat dolor accumsan dignissim. Integer eget nisl.
		Ut sit amet nulla. Vestibulum venenatis posuere mauris. Nullam magna leo, blandit luctus, consequat quis, gravida nec, justo. Nam pede. Etiam ut nisl. In at quam scelerisque sapien faucibus commodo. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Proin mattis lorem quis nunc. Praesent placerat ligula id elit. Aenean blandit, purus sit amet pharetra auctor, libero orci rutrum felis, sit amet sodales mauris ipsum ultricies sapien. Vivamus wisi. Cras elit elit, ullamcorper ac, interdum nec, pulvinar nec, lacus. In lacus. Vivamus auctor, arcu vitae tincidunt porta, eros lacus tristique justo, vitae semper risus neque eget massa. Vivamus turpis.
		Aenean ac wisi non enim aliquam scelerisque. Praesent eget mi. Vestibulum volutpat pulvinar justo. Phasellus sapien ante, pharetra id, bibendum sed, porta non, purus. Maecenas leo velit, luctus quis, porta non, feugiat sit amet, sapien. Proin vitae augue ut massa adipiscing placerat. Morbi ac risus. Proin dapibus eros egestas quam. Fusce fermentum lobortis elit. Duis lectus tellus, convallis nec, lobortis vel, accumsan ut, nunc. Nunc est. Donec ullamcorper laoreet quam.
		Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos hymenaeos. Suspendisse potenti. Mauris mi. Vivamus risus lacus, faucibus sit amet, sollicitudin a, blandit et, justo. In hendrerit. Sed imperdiet, eros at fringilla tempor, turpis augue semper enim, quis rhoncus nibh enim quis dui. Sed massa sapien, mattis et, laoreet sit amet, dignissim nec, urna. Integer laoreet quam quis lectus. Curabitur convallis gravida dui. Nam metus. Ut sit amet augue in nibh interdum scelerisque. Donec venenatis, lacus et pulvinar euismod, libero massa condimentum pede, commodo tristique nunc massa eu quam. Donec vulputate. Aenean in nibh. Phasellus porttitor. Donec molestie, sem ac porttitor vulputate, mauris dui egestas libero, ac lobortis dolor sem vel ligula. Nam vulputate pretium libero. Cras accumsan. Vivamus lacinia sapien sit amet elit.
		Duis bibendum elementum justo. Duis posuere. Fusce nulla odio, posuere eget, condimentum nec, venenatis eu, elit. In hac habitasse platea dictumst. Aenean ac sem in enim imperdiet feugiat. Integer tincidunt lectus at elit. Integer magna lacus, vehicula quis, eleifend eget, suscipit vitae, leo. Nunc porta augue nec enim. Curabitur vehicula volutpat enim. Aliquam consequat. Vestibulum rhoncus tellus vitae erat. Integer est. Quisque fermentum leo nec odio. Suspendisse lobortis sollicitudin augue. Nullam urna mi, suscipit eu, sagittis laoreet, ultrices ac, sem. Aliquam enim tortor, hendrerit non, cursus a, tristique sit amet, sapien. Suspendisse potenti. Aenean semper placerat neque.';
		switch($type)
		{
		 case 'description':
		 	$descriptions = explode(".",$dummy_text);
		 	return $descriptions[rand(0,count($descriptions)-1)];
		 	break;
		 case 'title':
		 	$dummy_text = str_replace(array("\n",'.',',',"\t"),array(' ','','',' '),$dummy_text);
		 	$titles = explode(" ",$dummy_text);
		 	return trim($titles[rand(0,count($titles)-1)]);
		 	break;
		 case 'text':
		 	$texts = explode("\n",$dummy_text);
		 	return $texts[rand(0,count($texts)-1)];
		 	break;
		}
	}
}
?>



