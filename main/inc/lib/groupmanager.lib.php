<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)

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
/**
==============================================================================
*	This is the group library for Dokeos.
*	Include/require it in your code to use its functionality.
*
*	@author various authors
*	@author Roan Embrechts (Vrije Universiteit Brussel), virtual courses support + some cleaning
*   @author Bart Mollet (HoGent), all functions in class GroupManager
*   @author Julio Montoya (Dokeos), LOTS of database::escape_string added
*	@package dokeos.library
==============================================================================
*/
require_once 'database.lib.php';
require_once 'course.lib.php';
require_once 'tablesort.lib.php';
require_once 'fileManage.lib.php';
require_once 'fileUpload.lib.php';
/**
 * infinite
 */
define("INFINITE", "99999");
/**
 * No limit on the number of users in a group
 */
define("MEMBER_PER_GROUP_NO_LIMIT", "0");
/**
 * No limit on the number of groups per user
 */
define("GROUP_PER_MEMBER_NO_LIMIT", "0");
/**
 * The tools of a group can have 3 states
 * - not available
 * - public
 * - private
 */
define("TOOL_NOT_AVAILABLE", "0");
define("TOOL_PUBLIC", "1");
define("TOOL_PRIVATE", "2");
/**
 * Constants for the available group tools
 */
define("GROUP_TOOL_FORUM", "0");
define("GROUP_TOOL_DOCUMENTS", "1");
define("GROUP_TOOL_CALENDAR","2");
define("GROUP_TOOL_ANNOUNCEMENT","3");
define("GROUP_TOOL_WORK","4");
define("GROUP_TOOL_WIKI", "5");

/**
 * Fixed id's for group categories
 * - VIRTUAL_COURSE_CATEGORY: in this category groups are created based on the
 *   virtual  course of a course
 * - DEFAULT_GROUP_CATEGORY: When group categories aren't available (platform-
 *   setting),  all groups are created in this 'dummy'-category
 */
define("VIRTUAL_COURSE_CATEGORY", 1);
define("DEFAULT_GROUP_CATEGORY", 2);
/**
 * This library contains some functions for group-management.
 * @author Bart Mollet
 * @package dokeos.library
 * @todo Add $course_code parameter to all functions. So this GroupManager can
 * be used outside a session.
 */
class GroupManager {
	/*==============================================================================
	*	GROUP FUNCTIONS
	  ==============================================================================*/
	  private function __construct() {
	  	
	  }
	/**
	 * Get list of groups for current course.
	 * @param int $category The id of the category from which the groups are
	 * requested
	 * @param string $course_code Default is current course
	 * @return array An array with all information about the groups.
	 */
	public static function get_group_list ($category = null, $course_code = null) {
		global $_user;
		//$isStudentView  = $_REQUEST['isStudentView'];
		$course_db = '';
		$my_user_id=api_get_user_id();
		$my_status_of_user_in_course='';
		if ($course_code != null) {
			$course_info = Database :: get_course_info($course_code);
			$course_db = $course_info['database'];
		} else {
			$my_course_code=api_get_course_id();
		}
		$table_group = Database :: get_course_table(TABLE_GROUP, $course_db);
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
		$table_group_user = Database :: get_course_table(TABLE_GROUP_USER, $course_db);
		$session_id=isset($_SESSION['id_session']) ? $_SESSION['id_session'] : 0;
		$session_condition = intval($session_id)==0 ? '' : ' g.session_id = '.intval($session_id).' ';
		$my_status_of_user_in_course=CourseManager::get_user_in_course_status($my_user_id,$my_course_code);
		
		$is_student_in_session=false;
		if (is_null($my_status_of_user_in_course) || $my_status_of_user_in_course=='') {//into session
			if ($session_id>0) {
				$is_student_in_session=true;
			}
		}

		//COURSEMANAGER or STUDENT
		if ($my_status_of_user_in_course==COURSEMANAGER or api_is_allowed_to_edit()) {
			$sql = "SELECT  g.id ,
						g.name ,
						g.description ,
						g.category_id,
						g.max_student maximum_number_of_members,
						g.secret_directory,
						g.self_registration_allowed,
						g.self_unregistration_allowed,
						g.session_id,
						ug.user_id is_member,
						COUNT(ug2.id) number_of_members
					FROM ".$table_group." `g`
					LEFT JOIN ".$table_group_user." `ug`
					ON `ug`.`group_id` = `g`.`id` AND `ug`.`user_id` = '".$_user['user_id']."'
					LEFT JOIN ".$table_group_user." `ug2`
					ON `ug2`.`group_id` = `g`.`id`";
					
		} elseif ($my_status_of_user_in_course==STUDENT || $is_student_in_session===true || $_SESSION['studentview'] == 'studentview') {
						$sql = "SELECT  g.id ,
						g.name ,
						g.description ,
						g.category_id,
						g.max_student maximum_number_of_members,
						g.secret_directory,
						g.self_registration_allowed,
						g.self_unregistration_allowed,
						g.session_id,
						ug.user_id is_member,
						COUNT(ug2.id) number_of_members
					FROM ".$table_group." `g`
					LEFT JOIN ".$table_group_user." `ug`
					ON `ug`.`group_id` = `g`.`id` AND `ug`.`user_id` = '".$_user['user_id']."'
					LEFT JOIN ".$table_group_user." `ug2`
					ON `ug2`.`group_id` = `g`.`id`";
		}
							
		if ($category != null){
			$sql .= " WHERE g.category_id = '".Database::escape_string($category)."' ";
			if(!empty($session_condition))
				$sql .= 'AND '.$session_condition;
		}
		else if(!empty($session_condition))
			$sql .= 'WHERE '.$session_condition;
		$sql .= " GROUP BY g.id ORDER BY UPPER(g.name)";
		if (!api_is_anonymous()) {
			$groupList = api_sql_query($sql,__FILE__,__LINE__);
		} else {
			return array();
		}

		$groups = array ();
		while ($thisGroup = Database::fetch_array($groupList))
		{
			if ($thisGroup['category_id'] == VIRTUAL_COURSE_CATEGORY)
			{
				$sql = "SELECT title FROM $table_course WHERE code = '".$thisGroup['name']."'";
				$obj = Database::fetch_object(api_sql_query($sql,__FILE__,__LINE__));
				$thisGroup['name'] = $obj->title;
			}
			if($thisGroup['session_id']!=0)
			{
				$sql_session = 'SELECT name FROM '.Database::get_main_table(TABLE_MAIN_SESSION).' WHERE id='.$thisGroup['session_id'];
				$rs_session = api_sql_query($sql_session,__FILE__,__LINE__);
				if (Database::num_rows($rs_session)>0) {
					$thisGroup['session_name'] = Database::result($rs_session,0,0);
				} else {
					//the session has probably been removed, so the group is now orphaned
				}
			}
			$groups[] = $thisGroup;
		}
		return $groups;
	}
	/**
	 * Create a group
	 * @param string $name The name for this group
	 * @param int $tutor The user-id of the group's tutor
	 * @param int $places How many people can subscribe to the new group
	 */
	public static function create_group ($name, $category_id, $tutor, $places) {
		global $_course,$_user;
		isset($_SESSION['id_session'])?$my_id_session = intval($_SESSION['id_session']):$my_id_session=0;
		$currentCourseRepository = $_course['path'];
		$table_group = Database :: get_course_table(TABLE_GROUP);
		$table_forum = Database :: get_course_table(TABLE_FORUM);
		$category = self :: get_category($category_id);
		
		if (intval($places) == 0) //if the amount of users per group is not filled in, use the setting from the category
		{
			$places = $category['max_student'];
		}
		$sql = "INSERT INTO ".$table_group." SET 
				category_id='".Database::escape_string($category_id)."', max_student = '".$places."', doc_state = '".$category['doc_state']."',
				calendar_state = '".$category['calendar_state']."', work_state = '".$category['work_state']."', announcements_state = '".$category['announcements_state']."', forum_state = '".$category['forum_state']."', wiki_state = '".$category['wiki_state']."', self_registration_allowed = '".$category['self_reg_allowed']."',  self_unregistration_allowed = '".$category['self_unreg_allowed']."', session_id='".Database::escape_string($my_id_session)."'";
		api_sql_query($sql,__FILE__,__LINE__);
		$lastId = Database::insert_id();
		/*$secret_directory = uniqid("")."_team_".$lastId;
		while (is_dir(api_get_path(SYS_COURSE_PATH).$currentCourseRepository."/group/$secret_directory"))
		{
			$secret_directory = uniqid("")."_team_".$lastId;
		}
		FileManager :: mkdirs(api_get_path(SYS_COURSE_PATH).$currentCourseRepository."/group/".$secret_directory, 0770);
		*/
		$desired_dir_name= '/'.replace_dangerous_char($name,'strict').'_groupdocs';
		$dir_name = create_unexisting_directory($_course,$_user['user_id'],$lastId,NULL,api_get_path(SYS_COURSE_PATH).$currentCourseRepository.'/document',$desired_dir_name);
		/* Stores the directory path into the group table */
		$sql = "UPDATE ".$table_group." SET   name = '".Database::escape_string($name)."', secret_directory = '".$dir_name."' WHERE id ='".$lastId."'";
		api_sql_query($sql,__FILE__,__LINE__);
		
		// create a forum if needed
		if ($category['forum_state'] >= 0) {
			include_once(api_get_path(SYS_CODE_PATH).'forum/forumconfig.inc.php');
			include_once(api_get_path(SYS_CODE_PATH).'forum/forumfunction.inc.php');
			
			$forum_categories = get_forum_categories();
			$values['forum_title'] = $name;
			$counter = 0;
			foreach ($forum_categories as $key=>$value) {
				if ($counter==0) {
					$forum_category_id = $key;
				}
				$counter++;
			}
			// A sanity check.
			if (empty($forum_category_id)) {
				$forum_category_id = 0;
			}
			$values['forum_category'] = $forum_category_id;
			$values['allow_anonymous_group']['allow_anonymous'] = 0;
			$values['students_can_edit_group']['students_can_edit'] = 0;
			$values['approval_direct_group']['approval_direct'] = 0;
			$values['allow_attachments_group']['allow_attachments'] = 1;
			$values['allow_new_threads_group']['allow_new_threads'] = 1;
			$values['default_view_type_group']['default_view_type']=api_get_setting('default_forum_view');
			$values['group_forum'] = $lastId; 
			if ($category['forum_state'] == '1') {
				$values['public_private_group_forum_group']['public_private_group_forum']='public';
			} elseif  ($category['forum_state'] == '2') {
				$values['public_private_group_forum_group']['public_private_group_forum']='private';
			} elseif  ($category['forum_state'] == '0') {
				$values['public_private_group_forum_group']['public_private_group_forum']='unavailable';
			}
			store_forum($values);
		}
		return $lastId;
	}
	/**
	 * Create subgroups.
	 * This function creates new groups based on an existing group. It will
	 * create the specified number of groups and fill those groups with users
	 * from the base group
	 * @param int $group_id The group from which subgroups have to be created.
	 * @param int $number_of_groups The number of groups that have to be created
	 */
	public static function create_subgroups ($group_id, $number_of_groups) {
		$table_group = Database :: get_course_table(TABLE_GROUP);
		$category_id = self :: create_category('Subgroups', '', TOOL_PRIVATE, TOOL_PRIVATE, 0, 0, 1, 1);
		$users = self :: get_users($group_id);
		$group_ids = array ();
		for ($group_nr = 1; $group_nr <= $number_of_groups; $group_nr ++)
		{
			$group_ids[] = self :: create_group('SUBGROUP '.$group_nr, $category_id, 0, 0);
		}
		$members = array ();
		foreach ($users as $index => $user_id)
		{
			self :: subscribe_users($user_id, $group_ids[$index % $number_of_groups]);
			$members[$group_ids[$index % $number_of_groups]]++;
		}
		foreach ($members as $group_id => $places)
		{
			$sql = "UPDATE $table_group SET max_student = $places WHERE id = $group_id";
			api_sql_query($sql,__FILE__,__LINE__);
		}
	}
	/**
	 * Create groups from all virtual courses in the given course.
	 */
	public static function create_groups_from_virtual_courses() {
		self :: delete_category(VIRTUAL_COURSE_CATEGORY);
		$id = self :: create_category(get_lang('GroupsFromVirtualCourses'), '', TOOL_NOT_AVAILABLE, TOOL_NOT_AVAILABLE, 0, 0, 1, 1);
		$table_group_cat = Database :: get_course_table(TABLE_GROUP_CATEGORY);
		$sql = "UPDATE ".$table_group_cat." SET id=".VIRTUAL_COURSE_CATEGORY." WHERE id=$id";
		api_sql_query($sql,__FILE__,__LINE__);
		$course = api_get_course_info();
		$course['code'] = $course['sysCode'];
		$course['title'] = $course['name'];
		$virtual_courses = CourseManager :: get_virtual_courses_linked_to_real_course($course['sysCode']);
		$group_courses = $virtual_courses;
		$group_courses[] = $course;
		$ids = array ();
		foreach ($group_courses as $index => $group_course)
		{
			$users = CourseManager :: get_user_list_from_course_code($group_course['code']);
			$members = array ();
			foreach ($users as $index => $user)
			{
				if ($user['status'] == 5 && $user['tutor_id'] == 0)
				{
					$members[] = $user['user_id'];
				}
			}
			$id = self :: create_group($group_course['code'], VIRTUAL_COURSE_CATEGORY, 0, count($members));
			self :: subscribe_users($members, $id);
			$ids[] = $id;
		}
		return $ids;
	}
	/**
	 * Create a group for every class subscribed to the current course
	 * @param int $category_id The category in which the groups should be
	 * created
	 */
	public static function create_class_groups ($category_id) {
		global $_course;
		$classes = ClassManager::get_classes_in_course($_course['sysCode']);
		$group_ids = array();
		foreach($classes as $index => $class)
		{
			$users = ClassManager::get_users($class['id']);
			$group_id = self::create_group($class['name'],$category_id,0,count($users));
			$user_ids = array();
			foreach($users as $index_user => $user)
			{
				$user_ids[] = $user['user_id'];
			}
			self::subscribe_users($user_ids,$group_id);
			$group_ids[] = $group_id;
		}
		return $group_ids;
	}

	/**
	 * deletes groups and their data.
	 * @author Christophe Gesche <christophe.gesche@claroline.net>
	 * @author Hugues Peeters <hugues.peeters@claroline.net>
	 * @author Bart Mollet
	 * @param  mixed   $groupIdList - group(s) to delete. It can be a single id
	 *                                (int) or a list of id (array).
	 * @param string $course_code Default is current course
	 * @return integer              - number of groups deleted.
	 */
	public static function delete_groups ($group_ids, $course_code = null) {
		$course_db = '';
		if ($course_code != null)
		{
			$course = Database :: get_course_info($course_code);
			$course['path'] = $course['directory'];
			$course_db = $course['database'];
		}
		else
		{
			$course = api_get_course_info();
		}
		
		// Database table definitions
		$group_table 			= Database :: get_course_table(TABLE_GROUP, $course_db);
		$group_user_table 		= Database :: get_course_table(TABLE_GROUP_USER, $course_db);
		$forum_table 			= Database :: get_course_table(TABLE_FORUM, $course_db);
		$forum_post_table 		= Database :: get_course_table(TABLE_FORUM_POST, $course_db);
		//$forum_post_text_table 	= Database :: get_course_table(TOOL_FORUM_POST_TEXT_TABLE, $course_db);
		$forum_topic_table 		= Database :: get_course_table(TABLE_FORUM_POST, $course_db);
		
		$group_ids = is_array($group_ids) ? $group_ids : array ($group_ids);
		
		if(api_is_course_coach())
		{ //a coach can only delete courses from his session
			for($i=0 ; $i<count($group_ids) ; $i++)
			{
				if(!api_is_element_in_the_session(TOOL_GROUP,$group_ids[$i]))
				{
					array_splice($group_ids,$i,1);
					$i--;
				}
			}
			if(count($group_ids)==0)
				return 0;
		}
		
		
		// define repository for deleted element
		$group_garbage = api_get_path(SYS_ARCHIVE_PATH).$course['path']."/group/";
		$perm = api_get_setting('permissions_for_new_directories');
		$perm = (!empty($perm)?$perm:'0770');
		if (!file_exists($group_garbage))
			FileManager :: mkdirs($group_garbage, $perm);
		// Unsubscribe all users
		self :: unsubscribe_all_users($group_ids);
		$sql = 'SELECT id, secret_directory, session_id FROM '.$group_table.' WHERE id IN ('.implode(' , ', $group_ids).')';
		$db_result = api_sql_query($sql,__FILE__,__LINE__);
		$forum_ids = array ();
		while ($group = Database::fetch_object($db_result))
		{
			// move group-documents to garbage
			$source_directory = api_get_path(SYS_COURSE_PATH).$course['path']."/group/".$group->secret_directory;
			$destination_directory = $group_garbage.$group->secret_directory;
			if (file_exists($source_directory))
			{
				rename($source_directory, $destination_directory);
			}
			//$forum_ids[] = $group->forum_id;
		}
		// delete the groups
		$sql = "DELETE FROM ".$group_table." WHERE id IN ('".implode("' , '", $group_ids)."')";
		api_sql_query($sql,__FILE__,__LINE__);
		
		$sql2 = "DELETE FROM ".$forum_table." WHERE forum_of_group IN ('".implode("' , '", $group_ids)."')";
		api_sql_query($sql2,__FILE__,__LINE__);
		
		return Database::affected_rows();
	}

	/**
	 * Get group properties
	 * @param int $group_id The group from which properties are requested.
	 * @return array All properties. Array-keys are name, tutor_id, description, maximum_number_of_students, directory and visibility of tools
	 */
	public static function get_group_properties ($group_id) {
		if (empty($group_id) or !is_integer(intval($group_id)) ) {
			return null;
		}
		$table_group = Database :: get_course_table(TABLE_GROUP);
		$sql = 'SELECT   *  FROM '.$table_group.' WHERE id = '.Database::escape_string($group_id);
		$db_result = api_sql_query($sql,__FILE__,__LINE__);

			$db_object = Database::fetch_object($db_result);
			$result['id'] = $db_object->id;
			$result['name'] = $db_object->name;
			$result['tutor_id'] = isset($db_object->tutor_id)?$db_object->tutor_id:null;
			$result['description'] = $db_object->description;
			$result['maximum_number_of_students'] = $db_object->max_student;
			$result['doc_state'] = $db_object->doc_state;
			$result['work_state'] = $db_object->work_state;
			$result['calendar_state'] = $db_object->calendar_state;
			$result['announcements_state'] = $db_object->announcements_state;
			$result['forum_state'] = $db_object->forum_state;
			$result['wiki_state'] = $db_object->wiki_state;
			$result['directory'] = $db_object->secret_directory;
			$result['self_registration_allowed'] = $db_object->self_registration_allowed;
			$result['self_unregistration_allowed'] = $db_object->self_unregistration_allowed;

		return $result;
	}
	/**
	 * Set group properties
	 * Changes the group's properties.
	 * @param int		Group Id
	 * @param string 	Group name
	 * @param string	Group description
	 * @param int		Max number of students in group
	 * @param int		Document tool's visibility (0=none,1=private,2=public)
	 * @param int		Work tool's visibility (0=none,1=private,2=public)
	 * @param int		Calendar tool's visibility (0=none,1=private,2=public)
	 * @param int		Announcement tool's visibility (0=none,1=private,2=public)
	 * @param int		Forum tool's visibility (0=none,1=private,2=public)
	 * @param int		Wiki tool's visibility (0=none,1=private,2=public)
	 * @param bool 		Whether self registration is allowed or not
	 * @param bool 		Whether self unregistration is allowed or not
	 * @return bool 	TRUE if properties are successfully changed, false otherwise
	 */
	public static function set_group_properties ($group_id, $name, $description, $maximum_number_of_students, $doc_state, $work_state, $calendar_state, $announcements_state, $forum_state,$wiki_state, $self_registration_allowed, $self_unregistration_allowed) {
		$table_group = Database :: get_course_table(TABLE_GROUP);
		$table_forum = Database :: get_course_table(TABLE_FORUM);
		//$forum_id = get_forums_of_group($group_id);
		$group_id = Database::escape_string($group_id); 
		$sql = "UPDATE ".$table_group."
					SET name='".Database::escape_string(trim($name))."',
					doc_state = '".Database::escape_string($doc_state)."',
					work_state = '".Database::escape_string($work_state)."',
					calendar_state = '".Database::escape_string($calendar_state)."',
					announcements_state = '".Database::escape_string($announcements_state)."',
					forum_state = '".Database::escape_string($forum_state)."',
					wiki_state = '".Database::escape_string($wiki_state)."',
					description='".Database::escape_string(trim($description))."',
					max_student=".Database::escape_string($maximum_number_of_students).",
					self_registration_allowed='".Database::escape_string($self_registration_allowed)."',
					self_unregistration_allowed='".Database::escape_string($self_unregistration_allowed)."'
					WHERE id=".$group_id; 
		$result = api_sql_query($sql,__FILE__,__LINE__);
		//Here we are updating a field in the table forum_forum that perhaps duplicates the table group_info.forum_state cvargas 
		$forum_state = (int) $forum_state;
		$sql2 = "UPDATE ".$table_forum." SET ";
		if ($forum_state===1) {
			$sql2 .= " forum_group_public_private='public' ";
		} elseif ($forum_state===2) {
			$sql2 .= " forum_group_public_private='private' ";
		} elseif ($forum_state===0) {
			$sql2 .= " forum_group_public_private='unavailable' ";
		}
		$sql2 .=" WHERE forum_of_group=".$group_id;
		$result2 = api_sql_query($sql2,__FILE__,__LINE__);
		
		return $result;
	}
	/**
	 * Get the total number of groups for the current course.
	 * @return int The number of groups for the current course.
	 */
	public static function get_number_of_groups() {
		$table_group = Database :: get_course_table(TABLE_GROUP);
		$res = api_sql_query('SELECT COUNT(id) AS number_of_groups FROM '.$table_group);
		$obj = Database::fetch_object($res);
		return $obj->number_of_groups;
	}


	/*==============================================================================
	*	GROUPCATEGORY FUNCTIONS
	  ==============================================================================*/
	/**
	 * Get all categories
	 * @param string $course_code The cours (default = current course)
	 */
	public static function get_categories ($course_code = null) {
		$course_db = '';
		if ($course_code != null)
		{
			$course_info = Database :: get_course_info($course_code);
			$course_db = $course_info['database'];
		}
		$table_group_cat = Database :: get_course_table(TABLE_GROUP_CATEGORY, $course_db);
		$sql = "SELECT * FROM $table_group_cat ORDER BY display_order";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$cats = array ();
		while ($cat = Database::fetch_array($res))
		{
			$cats[] = $cat;
		}
		return $cats;
	}
	/**
	 * Get a group category
	 * @param int $id The category id
	 * @param string $course_code The course (default = current course)
	 */
	public static function get_category ($id, $course_code = null) {
		$course_db = '';
		if ($course_code != null)
		{
			$course_info = Database :: get_course_info($course_code);
			$course_db = $course_info['database'];
		}
		$id = Database::escape_string($id);
		$table_group_cat = Database :: get_course_table(TABLE_GROUP_CATEGORY, $course_db);
		$sql = "SELECT * FROM $table_group_cat WHERE id = $id";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		return Database::fetch_array($res);
	}
	/**
	 * Get the category of a given group
	 * @param int $group_id The id of the group
	 * @param string $course_code The course in which the group is (default =
	 * current course)
	 * @return array The category
	 */
	public static function get_category_from_group ($group_id, $course_code = null) {
		$course_db = '';
		if ($course_code != null)
		{
			$course_info = Database :: get_course_info($course_code);
			$course_db = $course_info['database'];
		}
		$table_group = Database :: get_course_table(TABLE_GROUP, $course_db);
		$table_group_cat = Database :: get_course_table(TABLE_GROUP_CATEGORY, $course_db);
		$group_id = Database::escape_string($group_id);
		$sql = "SELECT gc.* FROM $table_group_cat gc, $table_group g WHERE gc.id = g.category_id AND g.id=$group_id";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$cat = Database::fetch_array($res);
		return $cat;
	}
	/**
	 * Delete a group category
	 * @param int $cat_id The id of the category to delete
	 * @param string $course_code The code in which the category should be
	 * deleted (default = current course)
	 */
	public static function delete_category ($cat_id, $course_code = null) {
		$course_db = '';
		if ($course_code != null)
		{
			$course_info = Database :: get_course_info($course_code);
			$course_db = $course_info['database'];
		}
		$table_group = Database :: get_course_table(TABLE_GROUP, $course_db);
		$table_group_cat = Database :: get_course_table(TABLE_GROUP_CATEGORY, $course_db);
		$cat_id = Database::escape_string($cat_id);
		$sql = "SELECT id FROM $table_group WHERE category_id='".$cat_id."'";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		if (Database::num_rows($res) > 0)
		{
			$groups_to_delete = array ();
			while ($group = Database::fetch_object($res))
			{
				$groups_to_delete[] = $group->id;
			}
			self :: delete_groups($groups_to_delete);
		}
		$sql = "DELETE FROM $table_group_cat WHERE id='".$cat_id."'";
		api_sql_query($sql,__FILE__,__LINE__);
	}
	/**
	 * Create group category
	 * @param string $title The title of the new category
	 * @param string $description The description of the new category
	 * @param bool $self_registration_allowed
	 * @param bool $self_unregistration_allowed
	 * @param int $max_number_of_students
	 * @param int $groups_per_user
	 */
	public static function create_category ($title, $description, $doc_state, $work_state, $calendar_state, $announcements_state, $forum_state, $wiki_state, $self_registration_allowed, $self_unregistration_allowed, $maximum_number_of_students, $groups_per_user) {
		$table_group_category = Database :: get_course_table(TABLE_GROUP_CATEGORY);
		$sql = "SELECT MAX(display_order)+1 as new_order FROM $table_group_category ";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$obj = Database::fetch_object($res);
		if (!isset ($obj->new_order))
		{
			$obj->new_order = 1;
		}
		$sql = "INSERT INTO ".$table_group_category."
					SET title='".Database::escape_string($title)."',
					display_order ='".$obj->new_order."',
					description='".Database::escape_string($description)."',
					doc_state = '".Database::escape_string($doc_state)."',
					work_state = '".Database::escape_string($work_state)."',
					calendar_state = '".Database::escape_string($calendar_state)."',
              		announcements_state = '".Database::escape_string($announcements_state)."',  
              		forum_state = '".Database::escape_string($forum_state)."',
					wiki_state = '".Database::escape_string($wiki_state)."',
					groups_per_user   = '".Database::escape_string($groups_per_user)."',
					self_reg_allowed = '".Database::escape_string($self_registration_allowed)."',
					self_unreg_allowed = '".Database::escape_string($self_unregistration_allowed)."',
					max_student = '".Database::escape_string($maximum_number_of_students)."' ";
		api_sql_query($sql,__FILE__,__LINE__);
		$id = Database::insert_id();
		if ($id == VIRTUAL_COURSE_CATEGORY)
		{
			$sql = "UPDATE  ".$table_group_category." SET id = ". ($id +1)." WHERE id = $id";
			api_sql_query($sql,__FILE__,__LINE__);
			return $id +1;
		}
		return $id;
	}

	/**
	 * Update group category
	 * @param int $id The id of the category
	 * @param string $title The title of the new category
	 * @param string $description The description of the new category
	 * @param bool $self_registration_allowed
	 * @param bool $self_unregistration_allowed
	 * @param int $max_number_of_students
	 * @param int $groups_per_user
	 */
	public static function update_category ($id, $title, $description, $doc_state, $work_state, $calendar_state, $announcements_state, $forum_state, $wiki_state, $self_registration_allowed, $self_unregistration_allowed, $maximum_number_of_students, $groups_per_user) {
		$table_group_category = Database :: get_course_table(TABLE_GROUP_CATEGORY);
		$id = Database::escape_string($id);
		$sql = "UPDATE ".$table_group_category."
				SET title='".Database::escape_string($title)."',
				description='".Database::escape_string($description)."',
				doc_state = '".Database::escape_string($doc_state)."',
				work_state = '".Database::escape_string($work_state)."',
            	calendar_state = '".Database::escape_string($calendar_state)."',
            	announcements_state = '".Database::escape_string($announcements_state)."',
            	forum_state = '".Database::escape_string($forum_state)."',
				wiki_state = '".Database::escape_string($wiki_state)."',
				groups_per_user   = ".Database::escape_string($groups_per_user).",
				self_reg_allowed = '".Database::escape_string($self_registration_allowed)."',
				self_unreg_allowed = '".Database::escape_string($self_unregistration_allowed)."',
				max_student = ".Database::escape_string($maximum_number_of_students)."
				WHERE id=$id";
		api_sql_query($sql,__FILE__,__LINE__);
	}
	
	
	/**
	 * Returns the number of groups of the user with the greatest number of
	 * subscribtions in the given category
	 */
	public static function get_current_max_groups_per_user ($category_id = null, $course_code = null) {
		$course_db = '';
		
		if ($course_code != null)
		{
			$course_info = Database :: get_course_info($course_code);
			$course_db = $course_info['database'];
		}
		$group_table = Database :: get_course_table(TABLE_GROUP, $course_db);
		$group_user_table = Database :: get_course_table(TABLE_GROUP_USER, $course_db);
		$sql = 'SELECT COUNT(gu.group_id) AS current_max FROM '.$group_user_table.' gu, '.$group_table.' g WHERE gu.group_id = g.id ';
		if ($category_id != null) {
			$category_id = Database::escape_string($category_id);
			$sql .= ' AND g.category_id = '.$category_id;
		}
		$sql .= ' GROUP BY gu.user_id ORDER BY current_max DESC LIMIT 1';
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$obj = Database::fetch_object($res);
		return $obj->current_max;
	}
	/**
	 * Swaps the display-order of two categories
	 * @param int $id1 The id of the first category
	 * @param int $id2 The id of the second category
	 */
	public static function swap_category_order ($id1, $id2) {
		$table_group_cat = Database :: get_course_table(TABLE_GROUP_CATEGORY);
		$id1 = Database::escape_string($id1);
		$id2 = Database::escape_string($id2);
		
		$sql = "SELECT id,display_order FROM $table_group_cat WHERE id IN ($id1,$id2)";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$cat1 = Database::fetch_object($res);
		$cat2 = Database::fetch_object($res);
		$sql = "UPDATE $table_group_cat SET display_order=$cat2->display_order WHERE id=$cat1->id";
		api_sql_query($sql,__FILE__,__LINE__);
		$sql = "UPDATE $table_group_cat SET display_order=$cat1->display_order WHERE id=$cat2->id";
		api_sql_query($sql,__FILE__,__LINE__);
	}




	/*==============================================================================
	*	GROUP USERS FUNCTIONS
	  ==============================================================================*/

	/**
	 * Get all users from a given group
	 * @param int $group_id The group
	 */
	public static function get_users ($group_id) {
		$group_user_table = Database :: get_course_table(TABLE_GROUP_USER);
		$group_id = Database::escape_string($group_id);
		$sql = "SELECT user_id FROM $group_user_table WHERE group_id = $group_id";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$users = array ();
		while ($obj = Database::fetch_object($res)) {
			$users[] = $obj->user_id;
		}
		return $users;
	}

	/**
	 * Fill the groups with students.
	 * The algorithm takes care to first fill the groups with the least # of users.
	 *	Analysis
	 *	There was a problem with the "ALL" setting.
	 *	When max # of groups is set to all, the value is sometimes NULL and sometimes ALL
	 *	and in both cased the query does not work as expected.
	 *	Stupid solution (currently implemented: set ALL to a big number (INFINITE) and things are solved :)
	 *	Better solution: that's up to you.
	 *
	 *	Note
	 *	Throughout Dokeos there is some confusion about "course id" and "course code"
	 *	The code is e.g. TEST101, but sometimes a variable that is called courseID also contains a course code string.
	 *	However, there is also a integer course_id that uniquely identifies the course.
	 *	ywarnier:> Now the course_id has been removed (25/1/2005)
	 *	The databases are als very inconsistent in this.
	 *
	 * @author Chrisptophe Gesche <christophe.geshe@claroline.net>,
	 *         Hugues Peeters     <hugues.peeters@claroline.net> - original version
	 * @author Roan Embrechts - virtual course support, code cleaning
	 * @author Bart Mollet - code cleaning, use other GroupManager-functions
	 * @return void
	 */
	public static function fill_groups ($group_ids) {
		$group_ids = is_array($group_ids) ? $group_ids : array ($group_ids);
		
		if(api_is_course_coach())
		{
			for($i=0 ; $i<count($group_ids) ; $i++)
			{
				if(!api_is_element_in_the_session(TOOL_GROUP,$group_ids[$i]))
				{
					array_splice($group_ids,$i,1);
					$i--;
				}
			}
			if(count($group_ids)==0)
				return false;
		}
		
		global $_course;
		$category = self :: get_category_from_group($group_ids[0]);
		$groups_per_user = $category['groups_per_user'];
		$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$group_table = Database :: get_course_table(TABLE_GROUP);
		$group_user_table = Database :: get_course_table(TABLE_GROUP_USER);
		$complete_user_list = CourseManager :: get_real_and_linked_user_list($_course['sysCode']);
        $number_groups_per_user = ($groups_per_user == GROUP_PER_MEMBER_NO_LIMIT ? INFINITE : $groups_per_user);
		/*
		 * Retrieve all the groups where enrollment is still allowed
		 * (reverse) ordered by the number of place available
		 */
		$sql = "SELECT g.id gid, g.max_student-count(ug.user_id) nbPlaces, g.max_student
				FROM ".$group_table." g
				LEFT JOIN  ".$group_user_table." ug
				ON    `g`.`id` = `ug`.`group_id`
				WHERE g.id IN (".implode(',', $group_ids).")
				GROUP BY (`g`.`id`)
				HAVING (nbPlaces > 0 OR g.max_student = ".MEMBER_PER_GROUP_NO_LIMIT.")
				ORDER BY nbPlaces DESC";
		$sql_result = api_sql_query($sql,__FILE__,__LINE__);
		$group_available_place = array ();
		while ($group = Database::fetch_array($sql_result, 'ASSOC'))
		{
			$group_available_place[$group['gid']] = $group['nbPlaces'];
		}
		/*
		 * Retrieve course users (reverse) ordered by the number
		 * of group they are already enrolled
		 */
		for ($i = 0; $i < count($complete_user_list); $i ++)
		{
			
			//find # of groups the user is enrolled in
			$number_of_groups = self :: user_in_number_of_groups($complete_user_list[$i]["user_id"],$category['id']);
			//add # of groups to user list
			$complete_user_list[$i]['number_groups_left'] = $number_groups_per_user - $number_of_groups;
		}
		//first sort by user_id to filter out duplicates
		$complete_user_list = TableSort :: sort_table($complete_user_list, 'user_id');
		$complete_user_list = self :: filter_duplicates($complete_user_list, "user_id");
		$complete_user_list = self :: filter_only_students($complete_user_list);
		//now sort by # of group left
		$complete_user_list = TableSort :: sort_table($complete_user_list, 'number_groups_left', SORT_DESC);
		$userToken = array ();
		foreach ($complete_user_list as $this_user)
		{
			
			if ($this_user['number_groups_left'] > 0)
			{
				$userToken[$this_user['user_id']] = $this_user['number_groups_left'];
			}
		}
		/*
		 * Retrieve the present state of the users repartion in groups
		 */
		$sql = "SELECT user_id uid, group_id gid FROM ".$group_user_table;
		$result = api_sql_query($sql,__FILE__,__LINE__);
		while ($member = Database::fetch_array($result, 'ASSOC'))
		{
			$groupUser[$member['gid']][] = $member['uid'];
		}
		$changed = true;
		while ($changed)
		{
			
			$changed = false;
			reset($group_available_place);
			arsort($group_available_place);
			reset($userToken);
			arsort($userToken);
			foreach ($group_available_place as $group_id => $place)
			{
				foreach ($userToken as $user_id => $places)
				{
					if (self :: can_user_subscribe($user_id, $group_id))
					{
						
						self :: subscribe_users($user_id, $group_id);
						$group_available_place[$group_id]--;
						$userToken[$user_id]--;
						$changed = true;
						break;
					}
				}
				if ($changed)
				{
					break;
				}
			}
		}
	}


	/**
	 * Get the number of students in a group.
	 * @param int $group_id
	 * @return int Number of students in the given group.
	 */
	public static function number_of_students ($group_id) {
		$table_group_user = Database :: get_course_table(TABLE_GROUP_USER);
		$group_id = Database::escape_string($group_id);
		$db_result = api_sql_query('SELECT  COUNT(*) AS number_of_students FROM '.$table_group_user.' WHERE group_id = '.$group_id);
		$db_object = Database::fetch_object($db_result);
		return $db_object->number_of_students;
	}
	/**
	 * Maximum number of students in a group
	 * @param int $group_id
	 * @return int Maximum number of students in the given group.
	 */
	public static function maximum_number_of_students ($group_id) {
		$table_group = Database :: get_course_table(TABLE_GROUP);
		$group_id = Database::escape_string($group_id);
		$db_result = api_sql_query('SELECT   max_student  FROM '.$table_group.' WHERE id = '.$group_id);
		$db_object = Database::fetch_object($db_result);
		if ($db_object->max_student == 0)
		{
			return INFINITE;
		}
		return $db_object->max_student;
	}
	/**
	 * Number of groups of a user
	 * @param int $user_id
	 * @return int The number of groups the user is subscribed in.
	 */
	public static function user_in_number_of_groups ($user_id, $cat_id) {
		$table_group_user = Database :: get_course_table(TABLE_GROUP_USER);
		$table_group = Database :: get_course_table(TABLE_GROUP);
		$user_id = Database::escape_string($user_id);
		$cat_id = Database::escape_string($cat_id);
		
		$sql = 'SELECT  COUNT(*) AS number_of_groups FROM '.$table_group_user.' gu, '.$table_group.' g WHERE gu.user_id = \''.$user_id.'\' AND g.id = gu.group_id AND g.category_id=  \''.$cat_id.'\'';
		$db_result = api_sql_query($sql,__FILE__,__LINE__);
		$db_object = Database::fetch_object($db_result);
		return $db_object->number_of_groups;
	}
	/**
	 * Is sef-registration allowed?
	 * @param int $user_id
	 * @param int $group_id
	 * @return bool TRUE if self-registration is allowed in the given group.
	 */
	public static function is_self_registration_allowed ($user_id, $group_id) {
		if (!$user_id > 0)
			return false;
		$table_group = Database :: get_course_table(TABLE_GROUP);
		$group_id=(int)$group_id;
		if (isset($group_id)) {
			$group_id = Database::escape_string($group_id);
			$sql = 'SELECT  self_registration_allowed FROM '.$table_group.' WHERE id = "'.$group_id.'" ';
			$db_result = api_sql_query($sql,__FILE__,__LINE__);
			$db_object = Database::fetch_object($db_result);
		return $db_object->self_registration_allowed == 1 && self :: can_user_subscribe($user_id, $group_id);
		} else {
			return false;
		}
	}
	/**
	 * Is sef-unregistration allowed?
	 * @param int $user_id
	 * @param int $group_id
	 * @return bool TRUE if self-unregistration is allowed in the given group.
	 */
	public static function is_self_unregistration_allowed ($user_id, $group_id) {
		if (!$user_id > 0)
			return false;
		$table_group = Database :: get_course_table(TABLE_GROUP);
		$group_id = Database::escape_string($group_id);
		$db_result = api_sql_query('SELECT  self_unregistration_allowed FROM '.$table_group.' WHERE id = '.$group_id);
		$db_object = Database::fetch_object($db_result);
		return $db_object->self_unregistration_allowed == 1 && self :: can_user_unsubscribe($user_id, $group_id);
	}
	/**
	 * Is user subscribed in group?
	 * @param int $user_id
	 * @param int $group_id
	 * @return bool TRUE if given user is subscribed in given group
	 */
	public static function is_subscribed ($user_id, $group_id) {
		if(empty($user_id) or empty($group_id)){return false;}
		$table_group_user = Database :: get_course_table(TABLE_GROUP_USER);
		$group_id = Database::escape_string($group_id);
		$user_id = Database::escape_string($user_id);
		$sql = 'SELECT 1 FROM '.$table_group_user.' WHERE group_id = '.$group_id.' AND user_id = '.$user_id;
		$db_result = api_sql_query($sql);
		return Database::num_rows($db_result) > 0;
	}
	/**
	 * Can a user subscribe to a specified group in a course
	 * @param int $user_id
	 * @param int $group_id
	 * @return bool TRUE if given user  can be subscribed in given group
	 */
	public static function can_user_subscribe ($user_id, $group_id) {
		global $_course;
		$course_code = $_course['sysCode'];
		$category = self :: get_category_from_group($group_id);
		$result = CourseManager :: is_user_subscribed_in_real_or_linked_course($user_id, $course_code);
		$result = !self :: is_subscribed($user_id, $group_id);
		$result &= (self :: number_of_students($group_id) < self :: maximum_number_of_students($group_id));
		if ($category['groups_per_user'] == GROUP_PER_MEMBER_NO_LIMIT)
		{
			$category['groups_per_user'] = INFINITE;
		}
		$result &= (self :: user_in_number_of_groups($user_id, $category['id']) < $category['groups_per_user']);
		$result &= !self :: is_tutor($user_id);
		return $result;
	}
	/**
	 * Can a user unsubscribe to a specified group in a course
	 * @param int $user_id
	 * @param int $group_id
	 * @return bool TRUE if given user  can be unsubscribed from given group
	 * @internal for now, same as GroupManager::is_subscribed($user_id,$group_id)
	 */
	public static function can_user_unsubscribe ($user_id, $group_id) {
		$result = self :: is_subscribed($user_id, $group_id);
		return $result;
	}
	/**
	 * Get all subscribed users from a group
	 * @param int $group_id
	 * @return array An array with information of all users from the given group.
	 *               (user_id, firstname, lastname, email)
	 */
	public static function get_subscribed_users ($group_id) {
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$table_group_user = Database :: get_course_table(TABLE_GROUP_USER);
		$order_clause = api_sort_by_first_name() ? ' ORDER BY `u`.`firstname`, `u`.`lastname`' : ' ORDER BY `u`.`lastname`, `u`.`firstname`';
		$group_id = Database::escape_string($group_id);
		$sql = "SELECT `ug`.`id`, `u`.`user_id`, `u`.`lastname`, `u`.`firstname`, `u`.`email`
			FROM ".$table_user." u, ".$table_group_user." ug
			WHERE `ug`.`group_id`='".$group_id."'
			AND `ug`.`user_id`=`u`.`user_id`". $order_clause;
		$db_result = api_sql_query($sql,__FILE__,__LINE__);
		$users = array ();
		while ($user = Database::fetch_object($db_result))
		{
			$member['user_id'] = $user->user_id;
			$member['firstname'] = $user->firstname;
			$member['lastname'] = $user->lastname;
			$member['email'] = $user->email;
			$users[] = $member;
		}
		return $users;
	}

	/**
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * Get all subscribed tutors of a group
	 * @param int $group_id
	 * @return array An array with information of all users from the given group.
	 *               (user_id, firstname, lastname, email)
	 */
	public static function get_subscribed_tutors ($group_id,$id_only=false) {
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);		
		$table_group_tutor = Database :: get_course_table(TABLE_GROUP_TUTOR);
		$order_clause = api_sort_by_first_name() ? ' ORDER BY `u`.`firstname`, `u`.`lastname`' : ' ORDER BY `u`.`lastname`, `u`.`firstname`';
		$group_id = Database::escape_string($group_id);
		$sql = "SELECT `tg`.`id`, `u`.`user_id`, `u`.`lastname`, `u`.`firstname`, `u`.`email`
			FROM ".$table_user." u, ".$table_group_tutor." tg
			WHERE `tg`.`group_id`='".$group_id."'
			AND `tg`.`user_id`=`u`.`user_id`".$order_clause;
		$db_result = api_sql_query($sql,__FILE__,__LINE__);
		$users = array ();
		while ($user = Database::fetch_object($db_result))
		{
			if ($id_only==false)
			{
				$member['user_id'] = $user->user_id;
				$member['firstname'] = $user->firstname;
				$member['lastname'] = $user->lastname;
				$member['email'] = $user->email;
				$users[] = $member;
			}
			else
			{
				$users[]=$user->user_id;
			}
		}
		return $users;
	}
	/**
	 * Subscribe user(s) to a specified group in current course
	 * @param mixed $user_ids Can be an array with user-id's or a single user-id
	 * @param int $group_id
	 * @return bool TRUE if successfull
	 */
	public static function subscribe_users ($user_ids, $group_id) {
		$user_ids = is_array($user_ids) ? $user_ids : array ($user_ids);
		$result = true;
		foreach ($user_ids as $index => $user_id)
		{
			$table_group_user = Database :: get_course_table(TABLE_GROUP_USER);
			$user_id = Database::escape_string($user_id);
			$group_id = Database::escape_string($group_id);
			$sql = "INSERT INTO ".$table_group_user." (user_id, group_id) VALUES ('".$user_id."', '".$group_id."')";
			$result &= api_sql_query($sql,__FILE__,__LINE__);
		}
		return $result;
	}

	/**
	 * Subscribe tutor(s) to a specified group in current course
	 * @param mixed $user_ids Can be an array with user-id's or a single user-id
	 * @param int $group_id
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 * @see subscribe_users. This function is almost an exact copy of that function.
	 * @return bool TRUE if successfull
	 */
	public static function subscribe_tutors ($user_ids, $group_id) {
		$user_ids = is_array($user_ids) ? $user_ids : array ($user_ids);
		$result = true;
		foreach ($user_ids as $index => $user_id)
		{
			$table_group_tutor = Database :: get_course_table(TABLE_GROUP_TUTOR);
			$user_id = Database::escape_string($user_id);
			$group_id = Database::escape_string($group_id);
			
			$sql = "INSERT INTO ".$table_group_tutor." (user_id, group_id) VALUES ('".$user_id."', '".$group_id."')";
			$result &= api_sql_query($sql,__FILE__,__LINE__);
		}
		return $result;
	}

	/**
	 * Unsubscribe user(s) from a specified group in current course
	 * @param mixed $user_ids Can be an array with user-id's or a single user-id
	 * @param int $group_id
	 * @return bool TRUE if successfull
	 */
	public static function unsubscribe_users ($user_ids, $group_id) {
		$user_ids = is_array($user_ids) ? $user_ids : array ($user_ids);
		$table_group_user = Database :: get_course_table(TABLE_GROUP_USER);
		$group_id = Database::escape_string($group_id);
		$result &= api_sql_query('DELETE FROM '.$table_group_user.' WHERE group_id = '.$group_id.' AND user_id IN ('.implode(',', $user_ids).')');
	}
	/**
	 * Unsubscribe all users from one or more groups
	 * @param mixed $group_id Can be an array with group-id's or a single group-id
	 * @return bool TRUE if successfull
	 */
	public static function unsubscribe_all_users ($group_ids) {
		$group_ids = is_array($group_ids) ? $group_ids : array ($group_ids);
		if( count($group_ids) > 0)
		{
			
			if(api_is_course_coach())
			{
				for($i=0 ; $i<count($group_ids) ; $i++)
				{
					if(!api_is_element_in_the_session(TOOL_GROUP,$group_ids[$i]))
					{
						array_splice($group_ids,$i,1);
						$i--;
					}
				}
				if(count($group_ids)==0)
				{
					return false;
				}
			}
			
			$table_group_user = Database :: get_course_table(TABLE_GROUP_USER);
			$sql = 'DELETE FROM '.$table_group_user.' WHERE group_id IN ('.implode(',', $group_ids).')';
			$result = api_sql_query($sql,__FILE__,__LINE__);
			return $result;
		}
		return true;
	}
	/**
	 * Unsubscribe all tutors from one or more groups
	 * @param mixed $group_id Can be an array with group-id's or a single group-id
	 * @see unsubscribe_all_users. This function is almost an exact copy of that function.
	 * @return bool TRUE if successfull
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 */
	public static function unsubscribe_all_tutors ($group_ids) {
		$group_ids = is_array($group_ids) ? $group_ids : array ($group_ids);
		if( count($group_ids) > 0)
		{
			$table_group_tutor = Database :: get_course_table(TABLE_GROUP_TUTOR);
			$sql = 'DELETE FROM '.$table_group_tutor.' WHERE group_id IN ('.implode(',', $group_ids).')';
			$result = api_sql_query($sql,__FILE__,__LINE__);
			return $result;
		}
		return true;
	}

	/**
	 * Is the user a tutor of this group?
	 * @param $user_id the id of the user
	 * @param $group_id the id of the group
	 * @return boolean true/false
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 */
	public static function is_tutor_of_group ($user_id,$group_id) {
		global $_course;

		$table_group_tutor = Database :: get_course_table(TABLE_GROUP_TUTOR);
		$user_id = Database::escape_string($user_id);
		$group_id = Database::escape_string($group_id);
			
		$sql = "SELECT * FROM ".$table_group_tutor." WHERE user_id='".$user_id."' AND group_id='".$group_id."'";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		if (Database::num_rows($result)>0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Is the user part of this group? This can be a tutor or a normal member
	 * you should use this function if the access to a tool or functionality is restricted to the people who are actually in the group
	 * before you had to check if the user was 1. a member of the group OR 2. a tutor of the group. This function combines both
	 * @param $user_id the id of the user
	 * @param $group_id the id of the group
	 * @return boolean true/false
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	*/
	public static function is_user_in_group ($user_id, $group_id) {
		$member = self :: is_subscribed($user_id,$group_id);
		$tutor  = self :: is_tutor_of_group($user_id,$group_id);

		if ($member OR $tutor)
		{
			return true;
		}
		else
		{
			return false;
		}
	}



	/**
	 * Get all tutors for the current course.
	 * @return array An array with firstname, lastname and user_id for all
	 *               tutors in the current course.
	 * @deprecated this function uses the old tutor implementation
	 */
	public static function get_all_tutors () {
		global $_course;
		$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT user.user_id AS user_id, user.lastname AS lastname, user.firstname AS firstname
				FROM ".$user_table." user, ".$course_user_table." cu
				WHERE cu.user_id=user.user_id
				AND cu.tutor_id='1'
				AND cu.course_code='".$_course['sysCode']."'";
		$resultTutor = api_sql_query($sql,__FILE__,__LINE__);
		$tutors = array ();
		while ($tutor = Database::fetch_array($resultTutor))
		{
			$tutors[] = $tutor;
		}
		return $tutors;
	}


	/**
	 * Is user a tutor in current course
	 * @param int $user_id
	 * @return bool TRUE if given user is a tutor in the current course.
	 * @deprecated this function uses the old tutor implementation
	 */
	public static function is_tutor ($user_id) {
		global $_course;
		$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$user_id = Database::escape_string($user_id);		
		
		$sql = "SELECT tutor_id FROM ".$course_user_table."
		             WHERE `user_id`='".$user_id."'
		             AND `course_code`='".$_course['sysCode']."'"."AND tutor_id=1";
		$db_result = api_sql_query($sql,__FILE__,__LINE__);
		$result = (Database::num_rows($db_result) > 0);
		return $result;
	}

	/**
	 * Get all group's from a given course in which a given user is ubscribed
	 * @author  Patrick Cool
	 * @param	 string $course_db: the database of the course you want to
	 * retrieve the groups for
	 * @param integer $user_id: the ID of the user you want to know all its
	 * group memberships
	 */
	public static function get_group_ids ($course_db,$user_id) {
	$groups = array();
	$tbl_group = Database::get_course_table(TABLE_GROUP_USER,$course_db);
	$user_id = Database::escape_string($user_id);
	$sql = "SELECT group_id FROM $tbl_group WHERE user_id = '$user_id'";
	$groupres = api_sql_query($sql);

	// uncommenting causes a bug in Agenda AND announcements because there we check if the return value of this function is an array or not
	//$groups=array();

	if($groupres)
	{
		while ($myrow= Database::fetch_array($groupres))
			$groups[]=$myrow['group_id'];
	}

	return $groups;
	}
	/*
	-----------------------------------------------------------
	Group functions
	these take virtual/linked courses into account when necessary
	-----------------------------------------------------------
	*/
	/**
	*	Get a combined list of all users of the real course $course_code
	*		and all users in virtual courses linked to this course $course_code
	*	Filter user list: remove duplicate users; plus
	*		remove users that
	*		- are already in the current group $group_id;
	*		- do not have student status in these courses;
	*		- are not appointed as tutor (group assistent) for this group;
	*		- have already reached their maximum # of groups in this course.
	*
	*	Originally to get the correct list of users a big SQL statement was used,
	*	but this has become more complicated now there is not just one real course but many virtual courses.
	*	Still, that could have worked as well.
	*
	*	@version 1.1.3
	*	@author Roan Embrechts
	*/
	public static function get_complete_list_of_users_that_can_be_added_to_group ($course_code, $group_id) {
		global $_course, $_user;
		$category = self :: get_category_from_group($group_id, $course_code);
		$number_of_groups_limit = $category['groups_per_user'] == GROUP_PER_MEMBER_NO_LIMIT ? INFINITE : $category['groups_per_user'];
		$real_course_code = $_course['sysCode'];
		$real_course_info = Database :: get_course_info($real_course_code);
		$real_course_user_list = CourseManager :: get_user_list_from_course_code($virtual_course_code);
		//get list of all virtual courses
		$user_subscribed_course_list = CourseManager :: get_list_of_virtual_courses_for_specific_user_and_real_course($_user['user_id'], $real_course_code);
		//add real course to the list
		$user_subscribed_course_list[] = $real_course_info;
		if (!is_array($user_subscribed_course_list))
			return;
		//for all courses...
		foreach ($user_subscribed_course_list as $this_course)
		{
			$this_course_code = $this_course["code"];
			$course_user_list = CourseManager :: get_user_list_from_course_code($this_course_code);
			//for all users in the course
			foreach ($course_user_list as $this_user)
			{
				$user_id = $this_user["user_id"];
				$loginname = $this_user["username"];
				$lastname = $this_user["lastname"];
				$firstname = $this_user["firstname"];
				$status = $this_user["status"];
				//$role =  $this_user["role"];
				$tutor_id = $this_user["tutor_id"];
				$full_name = api_get_person_name($firstname, $lastname);
				if ($lastname == "" || $firstname == '')
					$full_name = $loginname;
				$complete_user["user_id"] = $user_id;
				$complete_user["full_name"] = $full_name;
				$complete_user['firstname'] = $firstname;
				$complete_user['lastname'] = $lastname;
				$complete_user["status"] = $status;
				$complete_user["tutor_id"] = $tutor_id;
				$student_number_of_groups = self :: user_in_number_of_groups($user_id, $category['id']);
				//filter: only add users that have not exceeded their maximum amount of groups
				if ($student_number_of_groups < $number_of_groups_limit)
				{
					$complete_user_list[] = $complete_user;
				}
			}
		}
		if (is_array($complete_user_list))
		{
			//sort once, on array field "full_name"
			$complete_user_list = TableSort :: sort_table($complete_user_list, "full_name");
			//filter out duplicates, based on field "user_id"
			$complete_user_list = self :: filter_duplicates($complete_user_list, "user_id");
			$complete_user_list = self :: filter_users_already_in_group($complete_user_list, $group_id);
			//$complete_user_list = self :: filter_only_students($complete_user_list);
		}
		return $complete_user_list;
	}
	/**
	*	Filter out duplicates in a multidimensional array
	*	by comparing field $compare_field.
	*
	*	@param $user_array_in list of users (must be sorted).
	*	@param string $compare_field, the field to be compared
	*/
	public static function filter_duplicates ($user_array_in, $compare_field) {
		$total_number = count($user_array_in);
		$user_array_out[0] = $user_array_in[0];
		$count_out = 0;
		for ($count_in = 1; $count_in < $total_number; $count_in ++)
		{
			if ($user_array_in[$count_in][$compare_field] != $user_array_out[$count_out][$compare_field])
			{
				$count_out ++;
				$user_array_out[$count_out] = $user_array_in[$count_in];
			}
		}
		return $user_array_out;
	}
	/**
	*	Filters from the array $user_array_in the users already in the group $group_id.
	*/
	public static function filter_users_already_in_group ($user_array_in, $group_id) {
		foreach ($user_array_in as $this_user)
		{
			if (!self :: is_subscribed($this_user['user_id'], $group_id))
			{
				$user_array_out[] = $this_user;
			}
		}
		return $user_array_out;
	}
	/**
	* Remove all users that are not students and all users who have tutor status
	* from  the list.
	*/
	public static function filter_only_students ($user_array_in) {
		$user_array_out = array ();
		foreach ($user_array_in as $this_user)
		{
			if ($this_user['status'] == STUDENT && $this_user['tutor_id'] == 0)
			{
				$user_array_out[] = $this_user;
			}
		}
		return $user_array_out;
	}
	/**
	 * Check if a user has access to a certain group tool
	 * @param int $user_id The user id
	 * @param int $group_id The group id
	 * @param constant $tool The tool to check the access rights. This should be
	 * one of constants: GROUP_TOOL_DOCUMENTS
	 * @return bool True if the given user has access to the given tool in the
	 * given course.
	 */
	public static function user_has_access ($user_id, $group_id, $tool) {
		switch ($tool)
		{
			case GROUP_TOOL_FORUM :
				$state_key = 'forum_state';
				break;
			case GROUP_TOOL_DOCUMENTS :
				$state_key = 'doc_state';
				break;
			case GROUP_TOOL_CALENDAR :
				$state_key = 'calendar_state';
				break;
			case GROUP_TOOL_ANNOUNCEMENT :
				$state_key = 'announcements_state';
				break;
			case GROUP_TOOL_WORK :
				$state_key = 'work_state';
				break;
			case GROUP_TOOL_WIKI :
				$state_key = 'wiki_state';
				break; 
			default:
				return false;
		}
		$group = self :: get_group_properties($group_id);
		if ($group[$state_key] == TOOL_NOT_AVAILABLE)
		{
			return false;
		}
		elseif ($group[$state_key] == TOOL_PUBLIC)
		{
			return true;
		}
		elseif (api_is_allowed_to_edit(false,true))
		{
			return true;
		}
		elseif($group['tutor_id'] == $user_id)
		{
			return true;
		}
		else
		{
			return self :: is_subscribed($user_id, $group_id);
		}
	}
	/**
	 * Get all groups where a specific user is subscribed
	 */
	public static function get_user_group_name ($user_id) {
		
		$table_group_user=Database::get_course_table(TABLE_GROUP_USER);
		$table_group=Database::get_course_table(TABLE_GROUP);
		$user_id = Database::escape_string($user_id);		
		$sql_groups = 'SELECT name FROM '.$table_group.' g,'.$table_group_user.' gu WHERE gu.user_id="'.$user_id.'" AND gu.group_id=g.id';
		$res = api_sql_query($sql_groups,__FILE__,__LINE__);
		
		$groups=array();
	    while($group = Database::fetch_array($res))
	    {
	    	$groups[] .= $group['name'];
	    }
	    return $groups;
	}
}
?>
