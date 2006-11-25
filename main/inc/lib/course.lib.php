<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Roan Embrechts, Vrije Universiteit Brussel
	Copyright (c) Bart Mollet, Hogeschool Gent
	Copyright (c) Yannick Warnier, Dokeos S.A.

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
* This is the course library for Dokeos.
*
* All main course functions should be placed here.

* Many functions of this library deal with providing support for
* virtual/linked/combined courses (this was already used in several universities
* but not available in standard Dokeos).
*
* The implementation changed, initially a course was a real course
* if target_course_code was 0 , this was changed to NULL.
* There are probably some places left with the wrong code.
*
* @package dokeos.library
==============================================================================
*/
/*
==============================================================================
	DOCUMENTATION
	(list not up to date, you can auto generate documentation with phpDocumentor)

	CourseManager::get_real_course_code_select_html($element_name, $has_size=true, $only_current_user_courses=true)
	CourseManager::check_parameter($parameter, $error_message)
	CourseManager::check_parameter_or_fail($parameter, $error_message)
	CourseManager::is_existing_course_code($wanted_course_code)
	CourseManager::get_real_course_list()
	CourseManager::get_virtual_course_list()

	GENERAL COURSE FUNCTIONS
	CourseManager::get_access_settings($course_code)
	CourseManager::set_course_tool_visibility($tool_table_id, $visibility)
	CourseManager::get_user_in_course_status($user_id, $course_code)
	CourseManager::add_user_to_course($user_id, $course_code)
	CourseManager::get_virtual_course_info($real_course_code)
	CourseManager::is_virtual_course_from_visual_code($visual_code)
	CourseManager::is_virtual_course_from_system_code($system_code)
	CourseManager::get_virtual_courses_linked_to_real_course($real_course_code)
	CourseManager::get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $real_course_code)
	CourseManager::has_virtual_courses_from_code($real_course_code, $user_id)
	CourseManager::get_target_of_linked_course($virtual_course_code)

	TITLE AND CODE FUNCTIONS
	CourseManager::determine_course_title_from_course_info($user_id, $course_info)
	CourseManager::create_combined_name($user_is_registered_in_real_course, $real_course_name, $virtual_course_list)
	CourseManager::create_combined_code($user_is_registered_in_real_course, $real_course_code, $virtual_course_list)

	USER FUNCTIONS
	CourseManager::get_real_course_list_of_user_as_course_admin($user_id)
	CourseManager::get_course_list_of_user_as_course_admin($user_id)

	CourseManager::is_user_subscribed_in_course($user_id, $course_code)
	CourseManager::is_user_subscribed_in_real_or_linked_course($user_id, $course_code)
	CourseManager::get_user_list_from_course_code($course_code)
	CourseManager::get_real_and_linked_user_list($course_code);

	GROUP FUNCTIONS
	CourseManager::get_group_list_of_course($course_code)

	CREATION FUNCTIONS
	CourseManager::attempt_create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category)
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

/*
-----------------------------------------------------------
		Configuration files
-----------------------------------------------------------
*/
include_once (api_get_path(CONFIGURATION_PATH).'add_course.conf.php');

/*
-----------------------------------------------------------
		Libraries
		we assume main_api is also included...
-----------------------------------------------------------
*/

include_once (api_get_path(LIBRARY_PATH).'database.lib.php');
include_once (api_get_path(LIBRARY_PATH).'add_course.lib.inc.php');

/*
-----------------------------------------------------------
		Constants
-----------------------------------------------------------
*/

//LOGIC: course visibility and registration settings
/*
	COURSE VISIBILITY

	MAPPING OLD SETTINGS TO NEW SETTINGS
	-----------------------

	NOT_VISIBLE_NO_SUBSCRIPTION_ALLOWED
	--> COURSE_VISIBILITY_REGISTERED, SUBSCRIBE_NOT_ALLOWED
	NOT_VISIBLE_SUBSCRIPTION_ALLOWED
	--> COURSE_VISIBILITY_REGISTERED, SUBSCRIBE_ALLOWED
	VISIBLE_SUBSCRIPTION_ALLOWED
	--> COURSE_VISIBILITY_OPEN_PLATFORM, SUBSCRIBE_ALLOWED
	VISIBLE_NO_SUBSCRIPTION_ALLOWED
	--> COURSE_VISIBILITY_OPEN_PLATFORM, SUBSCRIBE_NOT_ALLOWED
*/
//OLD SETTINGS
define("NOT_VISIBLE_NO_SUBSCRIPTION_ALLOWED", 0);
define("NOT_VISIBLE_SUBSCRIPTION_ALLOWED", 1);
define("VISIBLE_SUBSCRIPTION_ALLOWED", 2);
define("VISIBLE_NO_SUBSCRIPTION_ALLOWED", 3);

//NEW SETTINGS
//these are now defined in the main_api.lib.php
/*
	COURSE_VISIBILITY_CLOSED
	COURSE_VISIBILITY_REGISTERED
	COURSE_VISIBILITY_OPEN_PLATFORM
	COURSE_VISIBILITY_OPEN_WORLD

	SUBSCRIBE_ALLOWED
	SUBSCRIBE_NOT_ALLOWED
	UNSUBSCRIBE_ALLOWED
	UNSUBSCRIBE_NOT_ALLOWED
*/

/*
-----------------------------------------------------------
	Variables
-----------------------------------------------------------
*/

$TABLECOURSE = Database :: get_main_table(TABLE_MAIN_COURSE);
$TABLECOURSDOMAIN = Database :: get_main_table(TABLE_MAIN_CATEGORY);
$TABLEUSER = Database :: get_main_table(TABLE_MAIN_USER);
$TABLECOURSUSER = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
$TABLEANNOUNCEMENTS = "announcement";
$coursesRepositories = $_configuration['root_sys'];

/*
==============================================================================
		CourseManager CLASS
==============================================================================
*/

/**
 *	@package dokeos.library
 */
class CourseManager
{
	/**
	* Returns all the information of a given coursecode
	* @param string $course_code, the course code
	* @return an array with all the fields of the course table
	* @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	*/
	function get_course_information($course_code)
	{
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$sql="SELECT * FROM ".$course_table." WHERE code='".$course_code."'";
		$sql_result = api_sql_query($sql, __FILE__, __LINE__);
		$result = mysql_fetch_array($sql_result);
		return $result;
	}


	/**
	* Returns the access settings of the course:
	* which visibility;
	* wether subscribing is allowed;
	* wether unsubscribing is allowed.
	*
	* @param string $course_code, the course code
	* @todo for more consistency: use course_info call from database API
	* @return an array with int fields "visibility", "subscribe", "unsubscribe"
	*/
	function get_access_settings($course_code)
	{
		$system_code = $course_info["sysCode"];
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$sql = "SELECT `visibility`, `subscribe`, `unsubscribe` from ".$course_table." where `code` = '".$course_code."'";
		$sql_result = api_sql_query($sql, __FILE__, __LINE__);
		$result = mysql_fetch_array($sql_result);
		return $result;
	}

	/**
	* Returns the status of a user in a course, which is COURSEMANAGER or STUDENT.
	*
	* @return int the status of the user in that course
	*/
	function get_user_in_course_status($user_id, $course_code)
	{
		$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$sql_query = "SELECT * FROM $course_user_table WHERE `course_code` = '$course_code' AND `user_id` = '$user_id'";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = mysql_fetch_array($sql_result);
		return $result["status"];
	}


	/**
	 * Unsubscribe one or more users from a course
	 * @param int|array $user_id
	 * @param string $course_code
	 */
	function unsubscribe_user($user_id, $course_code)
	{
		if(!is_array($user_id))
		{
			$user_id = array($user_id);
		}
		if(count($user_id) == 0)
		{
			return;
		}
		$user_ids = implode(',', $user_id);
		$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
		// Unsubscribe user from all groups in the course
		$sql = "SELECT * FROM $table_course WHERE code = '".$course_code."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$course = mysql_fetch_object($res);
		$table_group = Database :: get_course_table(GROUP_USER_TABLE, $course->db_name);
		$sql = "DELETE FROM $table_group WHERE user_id IN (".$user_ids.")";
		api_sql_query($sql, __FILE__, __LINE__);

		// Unsubscribe user from all blogs in the course
		$table_blog_user = Database::get_course_table(BLOGS_REL_USER_TABLE, $course->db_name);
		$sql = "DELETE FROM  ".$table_blog_user." WHERE user_id IN (".$user_ids.")";
		api_sql_query($sql,__FILE__,__LINE__);
		$table_blogtask_user = Database::get_course_table(BLOGS_TASKS_REL_USER, $course->db_name);
		$sql = "DELETE FROM  ".$table_blogtask_user." WHERE user_id IN (".$user_ids.")";
		api_sql_query($sql,__FILE__,__LINE__);

		// Unsubscribe user from the course
		$sql = "DELETE FROM $table_course_user WHERE user_id IN (".$user_ids.") AND course_code = '".$course_code."'";
		api_sql_query($sql, __FILE__, __LINE__);
	}


	/**
	 * Subscribe a user to a course. No checks are performed here to see if
	 * course subscription is allowed.
	 * @see add_user_to_course
	 */
	function subscribe_user($user_id, $course_code, $status = STUDENT)
	{
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$location_table = Database :: get_main_table(MAIN_LOCATION_TABLE);
		$user_role_table = Database :: get_main_table(MAIN_USER_ROLE_TABLE);

		$status = ($status == STUDENT || $status == COURSEMANAGER) ? $status : STUDENT;
		$role_id = ($status == COURSEMANAGER) ? COURSE_ADMIN : NORMAL_COURSE_MEMBER;

		if (empty ($user_id) || empty ($course_code))
		{
			return false;
		}
		else
		{
			// previously check if the user are already registered on the platform

			$handle = api_sql_query("SELECT status FROM ".$user_table."
														WHERE `user_id` = '$user_id' ");
			if (mysql_num_rows($handle) == 0)
			{
				return false; // the user isn't registered to the platform
			}
			else
			{
				//check if user isn't already subscribed to the course
				$handle = api_sql_query("SELECT * FROM ".$course_user_table."
																	WHERE `user_id` = '$user_id'
																	AND `course_code` ='$course_code'");
				if (mysql_num_rows($handle) > 0)
				{
					return false; // the user is already subscribed to the course
				}
				else
				{
					$max_sort = api_max_sort_value('0', $user_id);
					$add_course_user_entry_sql = "INSERT INTO ".$course_user_table."
										SET `course_code` = '$course_code',
										`user_id`    = '$user_id',
											`status`    = '".$status."',
											`sort`  =   '". ($max_sort +1)."'";
					$result = api_sql_query($add_course_user_entry_sql);
					if ($result)
					{
						return true;
					}
					else
					{
						return false;
					}
				}
			}
		}
	}

	/**
	* Subscribe a user $user_id to a course $course_code.
	* @author Hugues Peeters
	* @author Roan Embrechts
	*
	* @param  int $user_id the id of the user
	* @param  string $course_code the course code
	* @param string $status (optional) The user's status in the course
	*
	* @return boolean true if subscription succeeds, boolean false otherwise.
	* @todo script has ugly ifelseifelseifelseif structure, improve
	*/
	function add_user_to_course($user_id, $course_code, $status = STUDENT)
	{
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

		$status = ($status == STUDENT || $status == COURSEMANAGER) ? $status : STUDENT;
		if (empty($user_id) || empty ($course_code))
		{
			return false;
		}
		else
		{
			// previously check if the user are already registered on the platform

			$handle = api_sql_query("SELECT status FROM ".$user_table."
								WHERE `user_id` = '$user_id' ");
			if (mysql_num_rows($handle) == 0)
			{
				return false; // the user isn't registered to the platform
			}
			else
			{
				//check if user isn't already subscribed to the course
				$handle = api_sql_query("SELECT * FROM ".$course_user_table."
																	WHERE `user_id` = '$user_id'
																	AND `course_code` ='$course_code'");
				if (mysql_num_rows($handle) > 0)
				{
					return false; // the user is already subscribed to the course
				}
				else
				{
					// previously check if subscription is allowed for this course

					$handle = api_sql_query("SELECT code, visibility FROM ".$course_table."
											WHERE  `code` = '$course_code'
											AND  `subscribe` = '".SUBSCRIBE_NOT_ALLOWED."'");

					if (mysql_num_rows($handle) > 0)
					{
						return false; // subscription not allowed for this course
					}
					else
					{
						$max_sort = api_max_sort_value('0', $user_id);
						$add_course_user_entry_sql = "INSERT INTO ".$course_user_table."
											SET `course_code` = '$course_code',
												`user_id`    = '$user_id',
												`status`    = '".$status."',
												`sort`  =   '". ($max_sort +1)."'";
						$result=api_sql_query($add_course_user_entry_sql);
						if ($result)
						{
							return true;
						}
						else
						{
							return false;
						}
					}
				}
			}
		}
	}

	/**
	*	This code creates a select form element to let the user
	*	choose a real course to link to.
	*
	*	A good non-display library should not use echo statements, but just return text/html
	*	so users of the library can choose when to display.
	*
	*	We display the course code, but internally store the course id.
	*
	*	@param boolean $has_size, true the select tag gets a size element, false it stays a dropdownmenu
	*	@param boolean $only_current_user_courses, true only the real courses of which the
	*	current user is course admin are displayed, false all real courses are shown.
	*	@param string $element_name the name of the select element
	*	@return a string containing html code for a form select element.
	* @deprecated Function not in use
	*/
	function get_real_course_code_select_html($element_name, $has_size = true, $only_current_user_courses = true, $user_id)
	{
		if ($only_current_user_courses == true)
		{
			$real_course_list = CourseManager :: get_real_course_list_of_user_as_course_admin($user_id);
		}
		else
		{
			$real_course_list = CourseManager :: get_real_course_list();
		}

		if ($has_size == true)
		{
			$size_element = "size=\"".SELECT_BOX_SIZE."\"";
		}
		else
		{
			$size_element = "";
		}
		$html_code = "<select name=\"$element_name\" $size_element >\n";
		foreach ($real_course_list as $real_course)
		{
			$course_code = $real_course["code"];
			$html_code .= "<option value=\"".$course_code."\">";
			$html_code .= $course_code;
			$html_code .= "</option>\n";
		}
		$html_code .= "</select>\n";

		return $html_code;
	}

	/**
	*	Checks wether a parameter exists.
	*	If it doesn't, the function displays an error message.
	*
	*	@return true if parameter is set and not empty, false otherwise
	*	@todo move function to better place, main_api ?
	*/
	function check_parameter($parameter, $error_message)
	{
		if (!isset ($parameter) || empty ($parameter))
		{
			Display :: display_normal_message($error_message);
			return false;
		}
		return true;
	}

	/**
	*	Lets the script die when a parameter check fails.
	*	@todo move function to better place, main_api ?
	*/
	function check_parameter_or_fail($parameter, $error_message)
	{
		if (!CourseManager :: check_parameter($parameter, $error_message))
			die();
	}

	/**
	*	@return true if there already are one or more courses
	*	with the same code OR visual_code (visualcode), false otherwise
	*/
	function is_existing_course_code($wanted_course_code)
	{
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);

		$sql_query = "SELECT COUNT(*) as number FROM ".$course_table."WHERE `code` = '$wanted_course_code' OR `visual_code` = '$wanted_course_code' ";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = mysql_fetch_array($sql_result);

		if ($result["number"] > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	*	@return an array with the course info of all real courses on the platform
	*/
	function get_real_course_list()
	{
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$sql_query = "SELECT * FROM $course_table WHERE `target_course_code` IS NULL";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);

		while ($result = mysql_fetch_array($sql_result))
		{
			$real_course_list[] = $result;
		}

		return $real_course_list;
	}

	/**
	*	@return an array with the course info of all virtual courses on the platform
	*/
	function get_virtual_course_list()
	{
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$sql_query = "SELECT * FROM $course_table WHERE `target_course_code` IS NOT NULL";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);

		while ($result = mysql_fetch_array($sql_result))
		{
			$virtual_course_list[] = $result;
		}
		return $virtual_course_list;
	}

	/**
	*	@return an array with the course info of the real courses of which
	*	the current user is course admin
	*/
	function get_real_course_list_of_user_as_course_admin($user_id)
	{
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

		$sql_query = "	SELECT *
										FROM $course_table course
										LEFT JOIN $course_user_table course_user
										ON course.`code` = course_user.`course_code`
										WHERE course.`target_course_code` IS NULL
											AND course_user.`user_id` = '$user_id'
											AND course_user.`status` = '1'";

		//api_display_debug_info($sql_query);
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);

		while ($result = mysql_fetch_array($sql_result))
		{
			$result_array[] = $result;
		}

		return $result_array;
	}

	/**
	*	@return an array with the course info of all the courses (real and virtual) of which
	*	the current user is course admin
	*/
	function get_course_list_of_user_as_course_admin($user_id)
	{
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

		$sql_query = "	SELECT *
										FROM $course_table course
										LEFT JOIN $course_user_table course_user
										ON course.`code` = course_user.`course_code`
										WHERE course_user.`user_id` = '$user_id'
											AND course_user.`status` = '1'";

		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);

		while ($result = mysql_fetch_array($sql_result))
		{
			$result_array[] = $result;
		}

		return $result_array;
	}

	/**
	* Find out for which courses the user is registered and determine a visual course code and course title from that.
	* Takes virtual courses into account
	*
	* Default case: the name and code stay what they are.
	*
	* Scenarios:
	* - User is registered in real course and virtual courses; name / code become a mix of all
	* - User is registered in real course only: name stays that of real course
	* - User is registered in virtual course only: name becomes that of virtual course
	* - user is not registered to any of the real/virtual courses: name stays that of real course
	* (I'm not sure about the last case, but this seems not too bad)
	*
	* @author Roan Embrechts
	* @param $user_id, the id of the user
	* @param $course_info, an array with course info that you get using Database::get_course_info($course_system_code);
	* @return an array with indices
	*    $return_result["title"] - the course title of the combined courses
	*    $return_result["code"]  - the course code of the combined courses
	*/
	function determine_course_title_from_course_info($user_id, $course_info)
	{
		$real_course_id = $course_info['system_code'];
		$real_course_info = Database :: get_course_info($real_course_id);
		$real_course_name = $real_course_info["title"];
		$real_course_visual_code = $real_course_info["visual_code"];
		$real_course_real_code = $course_info['system_code'];

		//is the user registered in the real course?
		$table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$sql_query = "SELECT * FROM $table WHERE `user_id` = '$user_id' AND `course_code` = '$real_course_real_code'";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = mysql_fetch_array($sql_result);

		if (!isset ($result) || empty ($result))
		{
			$user_is_registered_in_real_course = false;
		}
		else
		{
			$user_is_registered_in_real_course = true;
		}
		//get a list of virtual courses linked to the current real course
		//and to which the current user is subscribed

		$user_subscribed_virtual_course_list = CourseManager :: get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $real_course_id);

		if (count($user_subscribed_virtual_course_list) > 0)
		{
			$virtual_courses_exist = true;
		}
		else
		{
			$virtual_courses_exist = false;
		}

		//now determine course code and name

		if ($user_is_registered_in_real_course && $virtual_courses_exist)
		{
			$course_info["name"] = CourseManager :: create_combined_name($user_is_registered_in_real_course, $real_course_name, $user_subscribed_virtual_course_list);
			$course_info['official_code'] = CourseManager :: create_combined_code($user_is_registered_in_real_course, $real_course_visual_code, $user_subscribed_virtual_course_list);
		}
		else
			if ($user_is_registered_in_real_course)
			{
				//course name remains real course name
				$course_info["name"] = $real_course_name;
				$course_info['official_code'] = $real_course_visual_code;
			}
			else
				if ($virtual_courses_exist)
				{
					$course_info["name"] = CourseManager :: create_combined_name($user_is_registered_in_real_course, $real_course_name, $user_subscribed_virtual_course_list);
					$course_info['official_code'] = CourseManager :: create_combined_code($user_is_registered_in_real_course, $real_course_visual_code, $user_subscribed_virtual_course_list);
				}
				else
				{
					//course name remains real course name
					$course_info["name"] = $real_course_name;
					$course_info['official_code'] = $real_course_visual_code;
				}

		$return_result["title"] = $course_info["name"];
		$return_result["code"] = $course_info['official_code'];
		return $return_result;
	}

	/**
	* Create a course title based on all real and virtual courses the user is registered in.
	* @param boolean $user_is_registered_in_real_course
	* @param string $real_course_name, the title of the real course
	* @param array $virtual_course_list, the list of virtual courses
	*/
	function create_combined_name($user_is_registered_in_real_course, $real_course_name, $virtual_course_list)
	{
		if ($user_is_registered_in_real_course || count($virtual_course_list) > 1)
		{
			$complete_course_name_before = get_lang("CombinedCourse")." "; //from course_home lang file
		}

		if ($user_is_registered_in_real_course)
		{
			//add real name to result
			$complete_course_name[] = $real_course_name;
		}

		//add course titles of all virtual courses to the list
		foreach ($virtual_course_list as $current_course)
		{
			$complete_course_name[] = $current_course["title"];
		}

		$complete_course_name = $complete_course_name_before.implode(' &amp; ', $complete_course_name);

		return $complete_course_name;
	}

	/**
	*	Create a course code based on all real and virtual courses the user is registered in.
	*/
	function create_combined_code($user_is_registered_in_real_course, $real_course_code, $virtual_course_list)
	{
		$complete_course_code .= "";

		if ($user_is_registered_in_real_course)
		{
			//add real name to result
			$complete_course_code[] = $real_course_code;
		}

		//add course titles of all virtual courses to the list
		foreach ($virtual_course_list as $current_course)
		{
			$complete_course_code[] = $current_course["visual_code"];
		}

		$complete_course_code = implode(' &amp; ', $complete_course_code);

		return $complete_course_code;
	}

	/**
	*	Return course info array of virtual course
	*
	*	Note this is different from getting information about a real course!
	*
	*	@param $real_course_code, the id of the real course which the virtual course is linked to
	*/
	function get_virtual_course_info($real_course_code)
	{
		$table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$sql_query = "SELECT * FROM $table WHERE `target_course_code` = '$real_course_code'";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = array ();
		while ($virtual_course = mysql_fetch_array($sql_result))
		{
			$result[] = $virtual_course;
		}
		return $result;
	}

	/**
	*	@param string $system_code, the system code of the course
	*	@return true if the course is a virtual course, false otherwise
	*/
	function is_virtual_course_from_system_code($system_code)
	{
		$table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$sql_query = "SELECT * FROM $table WHERE `code` = '$system_code'";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = mysql_fetch_array($sql_result);
		$target_number = $result["target_course_code"];

		if ($target_number == NULL)
		{
			return false; //this is not a virtual course
		}
		else
		{
			return true; //this is a virtual course
		}
	}

	/**
	*	What's annoying is that you can't overload functions in PHP.
	*	@return true if the course is a virtual course, false otherwise
	*/
	function is_virtual_course_from_visual_code($visual_code)
	{
		$table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$sql_query = "SELECT * FROM $table WHERE `visual_code` = '$visual_code'";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = mysql_fetch_array($sql_result);
		$target_number = $result["target_course_code"];

		if ($target_number == NULL)
		{
			return false; //this is not a virtual course
		}
		else
		{
			return true; //this is a virtual course
		}
	}

	/**
	* @return true if the real course has virtual courses that the user is subscribed to, false otherwise
	*/
	function has_virtual_courses_from_code($real_course_code, $user_id)
	{
		$user_subscribed_virtual_course_list = CourseManager :: get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $real_course_code);
		$number_of_virtual_courses = count($user_subscribed_virtual_course_list);

		if (count($user_subscribed_virtual_course_list) > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	*	Return an array of arrays, listing course info of all virtual course
	*	linked to the real course ID $real_course_code
	*
	*	@param $real_course_code, the id of the real course which the virtual courses are linked to
	*/
	function get_virtual_courses_linked_to_real_course($real_course_code)
	{
		$table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$sql_query = "SELECT * FROM $table WHERE `target_course_code` = '$real_course_code'";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result_array = array ();
		while ($result = mysql_fetch_array($sql_result))
		{
			$result_array[] = $result;
		}

		return $result_array;
	}

	/**
	* This function returns the course code of the real course
	* to which a virtual course is linked.
	*
	* @param the course code of the virtual course
	* @return the course code of the real course
	*/
	function get_target_of_linked_course($virtual_course_code)
	{
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);

		//get info about the virtual course
		$sql_query = "SELECT * FROM $course_table WHERE `code` = '$virtual_course_code'";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = mysql_fetch_array($sql_result);
		$target_course_code = $result["target_course_code"];

		return $target_course_code;
	}

	/*
	==============================================================================
		USER FUNCTIONS
	==============================================================================
	*/

	/**
	* Return course info array of virtual course
	*
	* @param $user_id, the id (int) of the user
	* @param $course_info, array with info about the course (comes from course table)
	*
	* @return true if the user is registered in the course, false otherwise
	*/
	function is_user_subscribed_in_course($user_id, $course_code)
	{
		$table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

		$sql_query = "SELECT * FROM $table WHERE `user_id` = '$user_id' AND `course_code` = '$course_code'";
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		$result = mysql_fetch_array($sql_result);

		if (!isset ($result) || empty ($result))
		{
			return false; //user is not registered in course
		}
		else
		{
			return true; //user is registered in course
		}
	}

	/**
	*	Is the user subscribed in the real course or linked courses?
	*
	*	@param $user_id, the id (int) of the user
	*	@param $course_info, array with info about the course (comes from course table, see database lib)
	*
	*	@return true if the user is registered in the real course or linked courses, false otherwise
	*/
	function is_user_subscribed_in_real_or_linked_course($user_id, $course_code, $session_id='')
	{
		if($session_id==''){
			$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
			$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

			$sql_query = "	SELECT *
								FROM $course_table course
								LEFT JOIN $course_user_table course_user
								ON course.`code` = course_user.`course_code`
								WHERE course_user.`user_id` = '$user_id' AND ( course.`code` = '$course_code' OR `target_course_code` = '$course_code') ";

			$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
			$result = mysql_fetch_array($sql_result);

			if (!isset ($result) || empty ($result))
			{
				return false; //user is not registered in course
			}
			else
			{
				return true; //user is registered in course
			}
		}
		else {
			// is he subscribed to the course of the session ?
			// Database Table Definitions
			$tbl_sessions			= Database::get_main_table(TABLE_MAIN_SESSION);
			$tbl_sessions_course	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
			$tbl_session_course_user= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			$_cid = $course_info["code"];


			//users
			$sql = "SELECT id_user
					FROM $tbl_session_course_user
					WHERE id_session='".$_SESSION['id_session']."'
					AND id_user='$user_id'";

			$result = api_sql_query($sql,__FILE__,__LINE__);
			if(mysql_num_rows($result))
				return true;

			// is it a course coach ?
			$sql = "SELECT id_coach
					FROM $tbl_sessions_course AS session_course
					WHERE id_session='".$_SESSION['id_session']."'
					AND id_coach = '$user_id'
					AND course_code='$_cid'";

			$result = api_sql_query($sql,__FILE__,__LINE__);
			if(mysql_num_rows($result))
				return true;

			// is it a session coach ?
			$sql = "SELECT id_coach
					FROM $tbl_sessions AS session
					WHERE session.id='".$_SESSION['id_session']."'
					AND id_coach='$user_id'";

			$result = api_sql_query($sql,__FILE__,__LINE__);
			if(mysql_num_rows($result))
				return true;

			return false;

		}
	}

	/**
	*	Return user info array of all users registered in the specified real or virtual course
	*	This only returns the users that are registered in this actual course, not linked courses.
	*
	*	@param string $course_code
	*	@return array with user info
	*/
	function get_user_list_from_course_code($course_code)
	{
		if(api_get_setting('use_session_mode')!='true')
		{
			$table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
			$sql_query = "SELECT * FROM $table WHERE `course_code` = '$course_code' ORDER BY `status`";
		}
		else
		{
			$table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
			$sql_query = "SELECT id_user as user_id FROM $table WHERE `course_code` = '$course_code' AND id_session = '".$_SESSION['id_session']."'";
		}
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);
		while ($course_user_info = mysql_fetch_array($sql_result))
		{
			$user_id = $course_user_info["user_id"];
			$user_info = Database :: get_user_info_from_id($user_id);

			//add extra fields from course_user table
			$user_info["status"] = $course_user_info["status"];
			$user_info["role"] = $course_user_info["role"];
			$user_info["tutor_id"] = $course_user_info["tutor_id"];
			$result_array[] = $user_info;
		}

		return $result_array;
	}

	/**
	*	Return user info array of all users registered in the specified course
	*	this includes the users of the course itsel and the users of all linked courses.
	*
	*	@param array $course_info
	*	@return array with user info
	*/
	function get_real_and_linked_user_list($course_code)
	{
		//get list of virtual courses
		$virtual_course_list = CourseManager :: get_virtual_courses_linked_to_real_course($course_code);

		//get users from real course
		$user_list = CourseManager :: get_user_list_from_course_code($course_code);
		foreach ($user_list as $this_user)
		{
			$complete_user_list[] = $this_user;
		}

		//get users from linked courses
		foreach ($virtual_course_list as $this_course)
		{
			$course_code = $this_course["code"];
			$user_list = CourseManager :: get_user_list_from_course_code($course_code);
			foreach ($user_list as $this_user)
			{
				$complete_user_list[] = $this_user;
			}
		}

		return $complete_user_list;
	}

	/**
	*	Return an array of arrays, listing course info of all courses in the list
	*	linked to the real course $real_course_code, to which the user $user_id is subscribed.
	*
	*	@param $user_id, the id (int) of the user
	*	@param $real_course_code, the id (char) of the real course
	*
	*	@return array of course info arrays
	*/
	function get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $real_course_code)
	{
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$course_user_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

		if(api_get_setting("use_session_mode")!="true"){
			$sql_query = "	SELECT *
								FROM $course_table course
								LEFT JOIN $course_user_table course_user
								ON course.`code` = course_user.`course_code`
								WHERE course.`target_course_code` = '$real_course_code' AND course_user.`user_id` = '$user_id'";
		}
		else {
			$sql_query = "SELECT course.*
			               FROM $course_table
							INNER JOIN ".Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." course_user
								ON course.code = course_user.course_code
								AND course_user.course_code = '$real_course_code'
								AND course_user.id_user = '$user_id'
								AND id_session = '".$_SESSION['id_session']."'";

		}
		$sql_result = api_sql_query($sql_query, __FILE__, __LINE__);

		while ($result = mysql_fetch_array($sql_result))
		{
			$result_array[] = $result;
		}

		return $result_array;
	}

	/*
	==============================================================================
		GROUP FUNCTIONS
	==============================================================================
	*/

	function get_group_list_of_course($course_code)
	{
		$course_info = Database :: get_course_info($course_code);
		$database_name = $course_info['db_name'];
		$group_table = Database :: get_course_table(TABLE_GROUP, $database_name);
		$group_user_table = Database :: get_course_table(GROUP_USER_TABLE, $database_name);

		$sql = "SELECT g.id, g.name, COUNT(gu.id) userNb
								FROM $group_table AS g LEFT JOIN $group_user_table gu
								ON g.id = gu.group_id
								GROUP BY g.id
								ORDER BY g.name";

		$result = api_sql_query($sql, __FILE__, __LINE__) or die(mysql_error());
		while ($group_data = mysql_fetch_array($result))
		{
			$group_list[$group_data['id']] = $group_data;
		}
		return $group_list;
	}

	/**
	*	Checks all parameters needed to create a virtual course.
	*	If they are all set, the virtual course creation procedure is called.
	*
	*	Call this function instead of create_virtual_course
	*/
	function attempt_create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category)
	{
		//better: create parameter list, check the entire list, when false display errormessage
		CourseManager :: check_parameter_or_fail($real_course_code, "Unspecified parameter: real course id.");
		CourseManager :: check_parameter_or_fail($course_title, "Unspecified parameter: course title.");
		CourseManager :: check_parameter_or_fail($wanted_course_code, "Unspecified parameter: wanted course code.");
		CourseManager :: check_parameter_or_fail($course_language, "Unspecified parameter: course language.");
		CourseManager :: check_parameter_or_fail($course_category, "Unspecified parameter: course category.");

		$creation_success = CourseManager :: create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category);

		return $creation_success;
	}

	/**
	*	This function creates a virtual course.
	*	It assumes all parameters have been checked and are not empty.
	*	It checks wether a course with the $wanted_course_code already exists.
	*
	*	Users of this library should consider this function private,
	*	please call attempt_create_virtual_course instead of this one.
	*
	*	NOTE:
	*	The virtual course 'owner' id (the first course admin) is set to the CURRENT user id.
	*	@return true if the course creation succeeded, false otherwise
	*	@todo research: expiration date of a course
	*/
	function create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category)
	{
		global $firstExpirationDelay;
		$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
		$user_id = api_get_user_id();
		$real_course_info = Database :: get_course_info($real_course_code);
		$real_course_code = $real_course_info["system_code"];

		//check: virtual course creation fails if another course has the same
		//code, real or fake.
		if (CourseManager :: is_existing_course_code($wanted_course_code))
		{
			Display :: display_error_message($wanted_course_code." - ".get_lang("CourseCodeAlreadyExists"));
			return false;
		}

		//add data to course table, course_rel_user
		$course_sys_code = $wanted_course_code;
		$course_screen_code = $wanted_course_code;
		$course_repository = $real_course_info["directory"];
		$course_db_name = $real_course_info["db_name"];
		$responsible_teacher = $real_course_info["tutor_name"];
		$faculty_shortname = $course_category;
		// $course_title = $course_title;
		// $course_language = $course_language;
		$teacher_id = $user_id;

		//HACK ----------------------------------------------------------------
		$expiration_date = time() + $firstExpirationDelay;
		//END HACK ------------------------------------------------------------

		register_course($course_sys_code, $course_screen_code, $course_repository, $course_db_name, $responsible_teacher, $faculty_shortname, $course_title, $course_language, $teacher_id, $expiration_date);

		//above was the normal course creation table update call,
		//now one more thing: fill in the target_course_code field

		$sql_query = "UPDATE $course_table SET `target_course_code` = '$real_course_code' WHERE `code` = '$course_sys_code' LIMIT 1 ";
		api_sql_query($sql_query, __FILE__, __LINE__);

		return true;
	}

	/**
	 * Delete a course
	 * This function deletes a whole course-area from the platform. When the
	 * given course is a virtual course, the database and directory will not be
	 * deleted.
	 * When the given course is a real course, also all virtual courses refering
	 * to the given course will be deleted.
	 * @param string $code The code of the course to delete
	 * @todo When deleting a virtual course: unsubscribe users from that virtual
	 * course from the groups in the real course if they are not subscribed in
	 * that real course.
	 * @todo Remove globals
	 */
	function delete_course($code)
	{
		global $_configuration;
		
		$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
		$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$table_course_class = Database :: get_main_table(TABLE_MAIN_COURSE_CLASS);
		$user_role_table = Database :: get_main_table(MAIN_USER_ROLE_TABLE);
		$location_table = Database::get_main_table(MAIN_LOCATION_TABLE);
		$role_right_location_table = Database::get_main_table(MAIN_ROLE_RIGHT_LOCATION_TABLE);

		$sql = "SELECT * FROM $table_course WHERE code='".$code."'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		if (mysql_num_rows($res) == 0)
		{
			return;
		}
		CourseManager :: create_database_dump($code);
		if (!CourseManager :: is_virtual_course_from_system_code($code))
		{
			$virtual_courses = CourseManager :: get_virtual_courses_linked_to_real_course($code);
			foreach ($virtual_courses as $index => $virtual_course)
			{
				// Unsubscribe all classes from the virtual course
				$sql = "DELETE FROM $table_course_class WHERE course_code='".$virtual_course['code']."'";
				api_sql_query($sql, __FILE__, __LINE__);
				// Unsubscribe all users from the virtual course
				$sql = "DELETE FROM $table_course_user WHERE course_code='".$virtual_course['code']."'";
				api_sql_query($sql, __FILE__, __LINE__);
				// Delete the course from the database
				$sql = "DELETE FROM $table_course WHERE code='".$virtual_course['code']."'";
				api_sql_query($sql, __FILE__, __LINE__);
			}
			$sql = "SELECT * FROM $table_course WHERE code='".$code."'";
			$res = api_sql_query($sql, __FILE__, __LINE__);
			$course = mysql_fetch_object($res);
			if (!$_configuration['single_database'])
			{
				$sql = "DROP DATABASE IF EXISTS ".$course->db_name;
				api_sql_query($sql, __FILE__, __LINE__);
			}
			else
			{
				$db_pattern = $_configuration['table_prefix'].$course->db_name.$_configuration['db_glue'];
				$sql = "SHOW TABLES LIKE '$db_pattern%'";
				$result = api_sql_query($sql, __FILE__, __LINE__);
				while (list ($courseTable) = mysql_fetch_row($result))
				{
					api_sql_query("DROP TABLE `$courseTable`", __FILE__, __LINE__);
				}
			}
			$course_dir = api_get_path(SYS_COURSE_PATH).$course->directory;
			$garbage_dir = api_get_path(GARBAGE_PATH).$course->directory.'_'.time();
			rename($course_dir, $garbage_dir);
		}

		// Unsubscribe all classes from the course
		$sql = "DELETE FROM $table_course_class WHERE course_code='".$code."'";
		api_sql_query($sql, __FILE__, __LINE__);
		// Unsubscribe all users from the course
		$sql = "DELETE FROM $table_course_user WHERE course_code='".$code."'";
		api_sql_query($sql, __FILE__, __LINE__);
		// Delete the course from the database
		$sql = "DELETE FROM $table_course WHERE code='".$code."'";
		api_sql_query($sql, __FILE__, __LINE__);
	}

	/**
	 * Creates a file called mysql_dump.sql in the course folder
	 * @param $course_code The code of the course
	 * @todo Implementation for single database
	 */
	function create_database_dump($course_code)
	{
		global $_configuration;
		
		if ($_configuration['single_database'])
		{
			return;
		}
		$sql_dump = '';
		$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
		$sql = "SELECT * FROM $table_course WHERE code = '$course_code'";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$course = mysql_fetch_object($res);
		$sql = "SHOW TABLES FROM $course->db_name";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		while ($table = mysql_fetch_row($res))
		{
			$sql = "SELECT * FROM `$course->db_name`.`$table[0]`";
			$res3 = api_sql_query($sql, __FILE__, __LINE__);
			while ($row = mysql_fetch_assoc($res3))
			{
				foreach ($row as $key => $value)
				{
					$row[$key] = $key."='".addslashes($row[$key])."'";
				}
				$sql_dump .= "\nINSERT INTO $table[0] SET ".implode(', ', $row).';';
			}
		}
		$file_name = api_get_path(SYS_COURSE_PATH).$course->directory.'/mysql_dump.sql';
		$handle = fopen($file_name, 'a+');
		fwrite($handle, $sql_dump);
		fclose($handle);
	}
} //end class CourseManager
?>