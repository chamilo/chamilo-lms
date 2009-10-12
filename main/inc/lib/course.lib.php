<?php //$id: $
/* For licensing terms, see /dokeos_license.txt
	Copyright (c) Roan Embrechts, Vrije Universiteit Brussel
	Copyright (c) Bart Mollet, Hogeschool Gent
	Copyright (c) Yannick Warnier, Dokeos SPRL
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
require_once api_get_path(CONFIGURATION_PATH).'add_course.conf.php';

/*
-----------------------------------------------------------
		Libraries
		we assume main_api is also included...
-----------------------------------------------------------
*/

require_once api_get_path(LIBRARY_PATH).'database.lib.php';
require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';

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
define('NOT_VISIBLE_NO_SUBSCRIPTION_ALLOWED', 0);
define('NOT_VISIBLE_SUBSCRIPTION_ALLOWED', 1);
define('VISIBLE_SUBSCRIPTION_ALLOWED', 2);
define('VISIBLE_NO_SUBSCRIPTION_ALLOWED', 3);


/*
-----------------------------------------------------------
	Variables
-----------------------------------------------------------
*/

$TABLECOURSE = Database::get_main_table(TABLE_MAIN_COURSE);
$TABLECOURSDOMAIN = Database::get_main_table(TABLE_MAIN_CATEGORY);
$TABLEUSER = Database::get_main_table(TABLE_MAIN_USER);
$TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$TABLEANNOUNCEMENTS = 'announcement';
$coursesRepositories = $_configuration['root_sys'];

/*
==============================================================================
		CourseManager CLASS
==============================================================================
*/

/**
 *	@package dokeos.library
 */
class CourseManager {

	/**
	 * Returns all the information of a given coursecode
	 * @param string $course_code, the course code
	 * @return an array with all the fields of the course table
	 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
	 */
	public static function get_course_information($course_code) {
		return Database::fetch_array(Database::query(
			"SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
			WHERE code='".Database::escape_string($course_code)."'", __FILE__, __LINE__)
		);
	}

	/**
	 * Returns a list of courses. Should work with quickform syntax
	 * @param	integer	Offset (from the 7th = '6'). Optional.
	 * @param	integer	Number of results we want. Optional.
	 * @param	string	The column we want to order it by. Optional, defaults to first column.
	 * @param	string	The direction of the order (ASC or DESC). Optional, defaults to ASC.
	 * @param	string	The visibility of the course, or all by default.
	 * @param	string	If defined, only return results for which the course *title* begins with this string
	 */
	public static function get_courses_list($from = 0, $howmany = 0, $orderby = 1, $orderdirection = 'ASC', $visibility = -1, $startwith = '') {

		$sql = "SELECT code, title FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." ";
		if (!empty($startwith)) {
			$sql .= "WHERE LIKE title '".Database::escape_string($startwith)."%' ";
			if ($visibility !== -1 && $visibility == strval(intval($visibility))) {
				$sql .= " AND visibility = $visibility ";
			}
		} else {
			$sql .= "WHERE 1 ";
			if ($visibility !== -1 && $visibility == strval(intval($visibility))) {
				$sql .= " AND visibility = $visibility ";
			}
		}
		if (!empty($orderby)) {
			$sql .= " ORDER BY ".Database::escape_string($orderby)." ";
		} else {
			$sql .= " ORDER BY 1 ";
		}

		if (!in_array($orderdirection, array('ASC', 'DESC'))) {
			$sql .= 'ASC';
		} else {
			$sql .= Database::escape_string($orderdirection);
		}

		if (!empty($howmany) && is_int($howmany) and $howmany > 0) {
			$sql .= ' LIMIT '.Database::escape_string($howmany);
		} else {
			$sql .= ' LIMIT 1000000'; //virtually no limit
		}
		if (!empty($from)) {
			$from = intval($from);
			$sql .= ' OFFSET '.Database::escape_string($from);
		} else {
			$sql .= ' OFFSET 0';
		}

		return Database::store_result(Database::query($sql, __FILE__, __LINE__));
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
	public static function get_access_settings($course_code) {
		return Database::fetch_array(Database::query(
			"SELECT visibility, subscribe, unsubscribe from ".Database::get_main_table(TABLE_MAIN_COURSE)."
			WHERE code = '".Database::escape_string($course_code)."'", __FILE__, __LINE__)
		);
	}

	/**
	 * Returns the status of a user in a course, which is COURSEMANAGER or STUDENT.
	 * @param   int      User ID
	 * @param   string   Course code
	 * @return int the status of the user in that course
	 */
	public static function get_user_in_course_status($user_id, $course_code) {
		$result = Database::fetch_array(Database::query(
			"SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
			WHERE course_code = '".Database::escape_string($course_code)."' AND user_id = ".Database::escape_string($user_id), __FILE__, __LINE__)
		);
		return $result['status'];
	}

	/**
	 * Unsubscribe one or more users from a course
	 * @param int|array $user_id
	 * @param string $course_code
	 */
	public static function unsubscribe_user($user_id, $course_code) {

		if (!is_array($user_id)) {
			$user_id = array($user_id);
		}
		if (count($user_id) == 0) {
			return;
		}

		$user_ids = implode(',', $user_id);
		$course_code = Database::escape_string($course_code);

		$course = Database::fetch_object(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
				WHERE code = '".$course_code."'", __FILE__, __LINE__));

		// Unsubscribe user from all groups in the course.
		Database::query("DELETE FROM ".Database::get_course_table(TABLE_GROUP_USER, $course->db_name)."
				WHERE user_id IN (".$user_ids.")", __FILE__, __LINE__);
		Database::query("DELETE FROM ".Database::get_course_table(TABLE_GROUP_TUTOR, $course->db_name)."
				WHERE user_id IN (".$user_ids.")", __FILE__, __LINE__);

		// Unsubscribe user from all blogs in the course.
		Database::query("DELETE FROM ".Database::get_course_table(TABLE_BLOGS_REL_USER, $course->db_name)."
				WHERE user_id IN (".$user_ids.")", __FILE__, __LINE__);
		Database::query("DELETE FROM ".Database::get_course_table(TABLE_BLOGS_TASKS_REL_USER, $course->db_name)."
				WHERE user_id IN (".$user_ids.")", __FILE__, __LINE__);

		// Unsubscribe user from the course.
		if (!empty($_SESSION['id_session'])) { // We suppose the session is safe!

			// Delete in table session_rel_course_rel_user
			$my_session_id = Database::escape_string ($_SESSION['id_session']);
			Database::query("DELETE FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
					WHERE id_session ='".$my_session_id."'
						AND course_code = '".Database::escape_string($_SESSION['_course']['id'])."'
						AND id_user IN ($user_ids)", __FILE__, __LINE__);

			// Delete in table session_rel_user
			Database::query("DELETE FROM ".Database::get_main_table(TABLE_MAIN_SESSION_USER)."
					WHERE id_session ='".$my_session_id."'
						AND id_user IN ($user_ids)", __FILE__, __LINE__);

			// Update the table session
			$row = Database::fetch_array(Database::query("SELECT COUNT(*) FROM ".Database::get_main_table(TABLE_MAIN_SESSION_USER)."
					WHERE id_session = '".$my_session_id."'", __FILE__, __LINE__));
			$count = $row[0]; // number of users by session
			$result = Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_SESSION)." SET nbr_users = '$count'
					WHERE id = '".$my_session_id."'", __FILE__, __LINE__);

		} else {

			Database::query("DELETE FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
					WHERE user_id IN (".$user_ids.") AND course_code = '".$course_code."'", __FILE__, __LINE__);

			// add event to system log
			$time = time();
			$user_id = api_get_user_id();
			event_system(LOG_UNSUBSCRIBE_USER_FROM_COURSE, LOG_COURSE_CODE, $course_code, $time, $user_id);
		}
	}

	/**
	 * Subscribe a user to a course. No checks are performed here to see if
	 * course subscription is allowed.
	 * @param   int     User ID
	 * @param   string  Course code
	 * @param   int     Status (STUDENT, COURSEMANAGER, COURSE_ADMIN, NORMAL_COURSE_MEMBER)
	 * @return  bool    True on success, false on failure
	 * @see add_user_to_course
	 */
	public static function subscribe_user($user_id, $course_code, $status = STUDENT) {

		if ($user_id != strval(intval($user_id))) {
			return false; //detected possible SQL injection
		}

		$course_code = Database::escape_string($course_code);
		if (empty ($user_id) || empty ($course_code)) {
			return false;
		}

		$status = ($status == STUDENT || $status == COURSEMANAGER) ? $status : STUDENT;
		$role_id = ($status == COURSEMANAGER) ? COURSE_ADMIN : NORMAL_COURSE_MEMBER;

		// A preliminary check whether the user has bben already registered on the platform.
		if (Database::num_rows(@Database::query("SELECT status FROM ".Database::get_main_table(TABLE_MAIN_USER)."
				WHERE user_id = '$user_id' ", __FILE__, __LINE__)) == 0) {
			return false; // The user has not been registered to the platform.
		}

		// Check whether the user has not been already subscribed to the course.
		if (Database::num_rows(@Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
				WHERE user_id = '$user_id' AND course_code = '$course_code'", __FILE__, __LINE__)) > 0) {
			return false; // The user has been already subscribed to the course.
		}

		if (!empty($_SESSION['id_session'])) {

			// Check whether the user has not already been stored in the session_rel_course_user table
			if (Database::num_rows(@Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
					WHERE course_code = '".$_SESSION['_course']['id']."'
					AND id_session ='".$_SESSION['id_session']."'
					AND id_user = '".$user_id."'", __FILE__, __LINE__)) > 0) {
				return false;
			}

			// Check whether the user has not already been stored in the session_rel_user table
			if (Database::num_rows(@Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_SESSION_USER)."
					WHERE id_session ='".$_SESSION['id_session']."'
					AND id_user = '".$user_id."'", __FILE__, __LINE__)) > 0) {
				return false;
			}

			// Add him/her in the table session_rel_course_rel_user
			@Database::query("INSERT INTO ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
					SET id_session ='".$_SESSION['id_session']."',
					course_code = '".$_SESSION['_course']['id']."',
					id_user = '".$user_id."'", __FILE__, __LINE__);

			// Add him/her in the table session_rel_user
			@Database::query("INSERT INTO ".Database::get_main_table(TABLE_MAIN_SESSION_USER)."
					SET id_session ='".$_SESSION['id_session']."',
					id_user = '".$user_id."'", __FILE__, __LINE__);

			// Update the table session
			$row = Database::fetch_array(@Database::query("SELECT COUNT(*) FROM ".Database::get_main_table(TABLE_MAIN_SESSION_USER)." WHERE id_session = '".$_SESSION['id_session']."'", __FILE__, __LINE__));
			$count = $row[0]; // number of users by session
			$result = @Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_SESSION)." SET nbr_users = '$count' WHERE id = '".$_SESSION['id_session']."'", __FILE__, __LINE__);

		} else {

			$course_sort = self::userCourseSort($user_id, $course_code);
			$result = @Database::query("INSERT INTO ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
					SET course_code = '$course_code',
					user_id = '$user_id',
					status = '".$status."',
					sort = '". ($course_sort)."'", __FILE__, __LINE__);

			// Add event to the system log.
			$time = time();
			$user_id = api_get_user_id();
			event_system(LOG_SUBSCRIBE_USER_TO_COURSE, LOG_COURSE_CODE, $course_code, $time, $user_id);
		}

		return (bool)$result;
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
	 */
	public static function add_user_to_course($user_id, $course_code, $status = STUDENT) {
		$user_table = Database::get_main_table(TABLE_MAIN_USER);
		$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
		$course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);

		$status = ($status == STUDENT || $status == COURSEMANAGER) ? $status : STUDENT;
		if (empty($user_id) || empty($course_code) || ($user_id != strval(intval($user_id)))) {
			return false;
		}
		$course_code = Database::escape_string($course_code);

		// Check in advance whether the user has already been registered on the platform.
		if (Database::num_rows(Database::query("SELECT status FROM ".$user_table." WHERE user_id = '$user_id' ", __FILE__, __LINE__)) == 0) {
			return false; // Thehe user has not been registered to the platform.
		}

		// Check whether the user has already been subscribed to this course.
		if (Database::num_rows(Database::query("SELECT * FROM ".$course_user_table." WHERE user_id = '$user_id' AND course_code = '$course_code'", __FILE__, __LINE__)) > 0) {
			return false; // The user has been subscribed to the course.
		}

		// Check in advance whether subscription is allowed or not for this course.
		if (Database::num_rows(Database::query("SELECT code, visibility FROM ".$course_table." WHERE code = '$course_code' AND subscribe = '".SUBSCRIBE_NOT_ALLOWED."'", __FILE__, __LINE__)) > 0) {
			return false; // Subscription is not allowed for this course.
		}

		// Ok, subscribe the user.
		$max_sort = api_max_sort_value('0', $user_id);
		return (bool)Database::query("INSERT INTO ".$course_user_table."
				SET course_code = '$course_code',
				user_id = '$user_id',
				status = '".$status."',
				sort = '". ($max_sort + 1)."'", __FILE__, __LINE__);
	}

	/**
	 *	Checks wether a parameter exists.
	 *	If it doesn't, the function displays an error message.
	 *
	 *	@return true if parameter is set and not empty, false otherwise
	 *	@todo move function to better place, main_api ?
	 */
	public static function check_parameter($parameter, $error_message) {
		if (empty($parameter)) {
			Display::display_normal_message($error_message);
			return false;
		}
		return true;
	}

	/**
	 *	Lets the script die when a parameter check fails.
	 *	@todo move function to better place, main_api ?
	 */
	public static function check_parameter_or_fail($parameter, $error_message) {
		if (!self::check_parameter($parameter, $error_message)) {
			die();
		}
	}

	/**
	 *	@return true if there already are one or more courses
	 *	with the same code OR visual_code (visualcode), false otherwise
	 */
	// TODO: course_code_exists() is a better name.
	public static function is_existing_course_code($wanted_course_code) {
		$wanted_course_code = Database::escape_string($wanted_course_code);
		$result = Database::fetch_array(Database::query("SELECT COUNT(*) as number FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."WHERE code = '$wanted_course_code' OR visual_code = '$wanted_course_code'", __FILE__, __LINE__));
		return $result['number'] > 0;
	}

	/**
	 *	@return an array with the course info of all real courses on the platform
	 */
	public static function get_real_course_list() {
		$sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." WHERE target_course_code IS NULL", __FILE__, __LINE__);
		while ($result = Database::fetch_array($sql_result)) {
			$real_course_list[] = $result;
		}
		return $real_course_list;
	}

	/**
	 *	@return an array with the course info of all virtual courses on the platform
	 */
	public static function get_virtual_course_list() {
		$sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." WHERE target_course_code IS NOT NULL", __FILE__, __LINE__);
		while ($result = Database::fetch_array($sql_result)) {
			$virtual_course_list[] = $result;
		}
		return $virtual_course_list;
	}

	/**
	 *	@return an array with the course info of the real courses of which
	 *	the current user is course admin
	 */
	public static function get_real_course_list_of_user_as_course_admin($user_id) {
		if ($user_id != strval(intval($user_id))) {
			return array();
		}
		$sql_result = Database::query("SELECT *
				FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." course
				LEFT JOIN ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." course_user
				ON course.code = course_user.course_code
				WHERE course.target_course_code IS NULL
					AND course_user.user_id = '$user_id'
					AND course_user.status = '1'", __FILE__, __LINE__);
		while ($result = Database::fetch_array($sql_result)) {
			$result_array[] = $result;
		}
		return $result_array;
	}

	/**
	 *	@return an array with the course info of all the courses (real and virtual) of which
	 *	the current user is course admin
	 */
	public static function get_course_list_of_user_as_course_admin($user_id) {
		if ($user_id != strval(intval($user_id))) {
			return array();
		}
		$sql_result = Database::query("SELECT *
				FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." course
				LEFT JOIN ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." course_user
				ON course.code = course_user.course_code
				WHERE course_user.user_id = '$user_id'
					AND course_user.status = '1'", __FILE__, __LINE__);
		while ($result = Database::fetch_array($sql_result)) {
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
	 *    $return_result['title'] - the course title of the combined courses
	 *    $return_result['code']  - the course code of the combined courses
	 */
	public static function determine_course_title_from_course_info($user_id, $course_info) {

		if ($user_id != strval(intval($user_id))) {
			return array();
		}

		$real_course_id = $course_info['system_code'];
		$real_course_info = Database::get_course_info($real_course_id);
		$real_course_name = $real_course_info['title'];
		$real_course_visual_code = $real_course_info['visual_code'];
		$real_course_real_code = Database::escape_string($course_info['system_code']);

		//is the user registered in the real course?
		$result = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
				WHERE user_id = '$user_id' AND course_code = '$real_course_real_code'", __FILE__, __LINE__));
		$user_is_registered_in_real_course = !empty($result);

		//get a list of virtual courses linked to the current real course and to which the current user is subscribed
		$user_subscribed_virtual_course_list = self::get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $real_course_id);
		$virtual_courses_exist = count($user_subscribed_virtual_course_list) > 0;

		//now determine course code and name
		if ($user_is_registered_in_real_course && $virtual_courses_exist) {
			$course_info['name'] = self::create_combined_name($user_is_registered_in_real_course, $real_course_name, $user_subscribed_virtual_course_list);
			$course_info['official_code'] = self::create_combined_code($user_is_registered_in_real_course, $real_course_visual_code, $user_subscribed_virtual_course_list);
		}
		elseif ($user_is_registered_in_real_course) {
			//course name remains real course name
			$course_info['name'] = $real_course_name;
			$course_info['official_code'] = $real_course_visual_code;
		}
		elseif ($virtual_courses_exist) {
			$course_info['name'] = self::create_combined_name($user_is_registered_in_real_course, $real_course_name, $user_subscribed_virtual_course_list);
			$course_info['official_code'] = self::create_combined_code($user_is_registered_in_real_course, $real_course_visual_code, $user_subscribed_virtual_course_list);
		} else {
			//course name remains real course name
			$course_info['name'] = $real_course_name;
			$course_info['official_code'] = $real_course_visual_code;
		}

		$return_result['title'] = $course_info['name'];
		$return_result['code'] = $course_info['official_code'];
		return $return_result;
	}

	/**
	 * Create a course title based on all real and virtual courses the user is registered in.
	 * @param boolean $user_is_registered_in_real_course
	 * @param string $real_course_name, the title of the real course
	 * @param array $virtual_course_list, the list of virtual courses
	 */
	public static function create_combined_name($user_is_registered_in_real_course, $real_course_name, $virtual_course_list) {

		$complete_course_name = array();

		if ($user_is_registered_in_real_course) {
			// Add the real name to the result.
			$complete_course_name[] = $real_course_name;
		}

		// Add course titles of all virtual courses.
		foreach ($virtual_course_list as $current_course) {
			$complete_course_name[] = $current_course['title'];
		}

		// 'CombinedCourse' is from course_home language file.
		return (($user_is_registered_in_real_course || count($virtual_course_list) > 1) ? get_lang('CombinedCourse').' ' : '').implode(' &amp; ', $complete_course_name);
	}

	/**
	 *	Create a course code based on all real and virtual courses the user is registered in.
	 */
	public static function create_combined_code($user_is_registered_in_real_course, $real_course_code, $virtual_course_list) {

		$complete_course_code = array();

		if ($user_is_registered_in_real_course) {
			// Add the real code to the result
			$complete_course_code[] = $real_course_code;
		}

		// Add codes of all virtual courses.
		foreach ($virtual_course_list as $current_course) {
			$complete_course_code[] = $current_course['visual_code'];
		}

		return implode(' &amp; ', $complete_course_code);
	}

	/**
	 *	Return course info array of virtual course
	 *
	 *	Note this is different from getting information about a real course!
	 *
	 *	@param $real_course_code, the id of the real course which the virtual course is linked to
	 */
	public static function get_virtual_course_info($real_course_code) {
		$sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
				WHERE target_course_code = '".Database::escape_string($real_course_code)."'", __FILE__, __LINE__);
		$result = array();
		while ($virtual_course = Database::fetch_array($sql_result)) {
			$result[] = $virtual_course;
		}
		return $result;
	}

	/**
	 *	@param string $system_code, the system code of the course
	 *	@return true if the course is a virtual course, false otherwise
	 */
	public static function is_virtual_course_from_system_code($system_code) {
		$result = Database::fetch_array(Database::query("SELECT target_course_code FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
				WHERE code = '".Database::escape_string($system_code)."'", __FILE__, __LINE__));
		return !empty($result['target_course_code']);
	}

	/**
	 *	Returns whether the course code given is a visual code
	 *  @param  string  Visual course code
	 *	@return true if the course is a virtual course, false otherwise
	 */
	public static function is_virtual_course_from_visual_code($visual_code) {
		$result = Database::fetch_array(Database::query("SELECT target_course_code FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
				WHERE visual_code = '".Database::escape_string($visual_code)."'", __FILE__, __LINE__));
		return !empty($result['target_course_code']);
	}

	/**
	 * @return true if the real course has virtual courses that the user is subscribed to, false otherwise
	 */
	public static function has_virtual_courses_from_code($real_course_code, $user_id) {
		return count(self::get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $real_course_code)) > 0;
	}

	/**
	 *	Return an array of arrays, listing course info of all virtual course
	 *	linked to the real course ID $real_course_code
	 *
	 *	@param string The id of the real course which the virtual courses are linked to
	 *  @return array List of courses details
	 */
	public static function get_virtual_courses_linked_to_real_course($real_course_code) {
		$sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
				WHERE target_course_code = '".Database::get_main_table(TABLE_MAIN_COURSE)."'", __FILE__, __LINE__);
		$result_array = array();
		while ($result = Database::fetch_array($sql_result)) {
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
	public static function get_target_of_linked_course($virtual_course_code) {
		//get info about the virtual course
		$result = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
				WHERE code = '".Database::escape_string($virtual_course_code)."'", __FILE__, __LINE__));
		return $result['target_course_code'];
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
	public static function is_user_subscribed_in_course($user_id, $course_code, $in_a_session = false) {
		$user_id = intval($user_id);
		$course_code = Database::escape_string($course_code);

		$result = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
				WHERE user_id = $user_id AND course_code = '$course_code'", __FILE__, __LINE__));
		if (!empty($result)) {
			return true; // The user has been registered in this course.
		}

		if (!$in_a_session) {
			return false; // The user has not been registered in this course.
		}

		if (Database::num_rows(Database::query('SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).
				' WHERE id_user = '.$user_id.' AND course_code="'.$course_code.'"', __FILE__, __LINE__)) > 0) {
			return true;
		}

		if (Database::num_rows(Database::query('SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE).
				' WHERE id_coach = '.$user_id.' AND course_code="'.$course_code.'"', __FILE__, __LINE__)) > 0) {
			return true;
		}

		if (Database::num_rows(Database::query('SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_SESSION).
				' WHERE id='.intval($_SESSION['id_session']).' AND id_coach='.$user_id, __FILE__, __LINE__)) > 0) {
			return true;
		}

		return false;
	}

	/**
	 *	Is the user a teacher in the given course?
	 *
	 *	@param $user_id, the id (int) of the user
	 *	@param $course_code, the course code
	 *
	 *	@return true if the user is a teacher in the course, false otherwise
	 */
	public static function is_course_teacher($user_id, $course_code) {
		if ($user_id != strval(intval($user_id))) {
			return false;
		}
		$sql_result = Database::query('SELECT status FROM '.Database::get_main_table(TABLE_MAIN_COURSE_USER).
				' WHERE course_code="'.Database::escape_string($course_code).'" and user_id="'.$user_id.'"', __FILE__, __LINE__);
		if (Database::num_rows($sql_result) > 0) {
			return Database::result($sql_result, 0, 'status') == 1;
		}
		return false;
	}

	/**
	 *	Is the user subscribed in the real course or linked courses?
	 *
	 *	@param int the id of the user
	 *	@param array info about the course (comes from course table, see database lib)
	 *
	 *	@return true if the user is registered in the real course or linked courses, false otherwise
	 */
	public static function is_user_subscribed_in_real_or_linked_course ($user_id, $course_code, $session_id = '') {

		if ($user_id != strval(intval($user_id))) {
			return false;
		}

		$course_code = Database::escape_string($course_code);

		if ($session_id == '') {
			$result = Database::fetch_array(Database::query("SELECT *
					FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." course
					LEFT JOIN ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." course_user
					ON course.code = course_user.course_code
					WHERE course_user.user_id = '$user_id' AND ( course.code = '$course_code' OR target_course_code = '$course_code')", __FILE__, __LINE__));
			return !empty($result);
		}

		// From here we trust session id.

		// Is he/she subscribed to the session's course?

		// A user?
		if (Database::num_rows(Database::query("SELECT id_user
					FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)."
					WHERE id_session='".$_SESSION['id_session']."'
					AND id_user='$user_id'", __FILE__, __LINE__))) {
			return true;
		}

		// A course coach?
		if (Database::num_rows(Database::query("SELECT id_coach
					FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE)." AS session_course
					WHERE id_session='".$_SESSION['id_session']."'
					AND id_coach = '$user_id'
					AND course_code='$course_code'", __FILE__, __LINE__))) {
			return true;
		}

		// A session coach?
		if (Database::num_rows(Database::query("SELECT id_coach
					FROM ".Database::get_main_table(TABLE_MAIN_SESSION)." AS session
					WHERE session.id='".$_SESSION['id_session']."'
					AND id_coach='$user_id'", __FILE__, __LINE__))) {
			return true;
		}

		return false;
	}

	/**
	 *	Return user info array of all users registered in the specified real or virtual course
	 *	This only returns the users that are registered in this actual course, not linked courses.
	 *
	 *	@param string $course_code
	 *	@return array with user info
	 */
	public static function get_user_list_from_course_code($course_code, $with_session = true, $session_id = 0, $limit = '', $order_by = '') {

		$session_id = intval($session_id);
		$course_code = Database::escape_string($course_code);

		$users = array();

		$where = array();
		$sql = $session_id == 0
			? 'SELECT DISTINCT course_rel_user.status, user.user_id, course_rel_user.role, course_rel_user.tutor_id '
			: 'SELECT DISTINCT user.user_id ';

		$sql .= ' FROM '.Database::get_main_table(TABLE_MAIN_USER).' as user ';

		if (api_get_setting('use_session_mode')=='true' && $with_session) {
			$sql .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER).' as session_course_user
						ON user.user_id = session_course_user.id_user
						AND session_course_user.course_code="'.$course_code.'"';
			if ($session_id != 0) {
				$sql .= ' AND session_course_user.id_session = '.$session_id;
			}
			$where[] = ' session_course_user.course_code IS NOT NULL ';
		}

		if ($session_id == 0) {
			$sql .= ' LEFT JOIN '.Database::get_main_table(TABLE_MAIN_COURSE_USER).' as course_rel_user
						ON user.user_id = course_rel_user.user_id
						AND course_rel_user.course_code="'.$course_code.'"';
			$where[] = ' course_rel_user.course_code IS NOT NULL ';
		}

		$sql .= ' WHERE '.implode(' OR ', $where);

		$sql .= ' '.$order_by.' '.$limit;

		$rs = Database::query($sql, __FILE__, __LINE__);

		while ($user = Database::fetch_array($rs)) {
			$user_info = Database::get_user_info_from_id($user['user_id']);
			$user_info['status'] = $user['status'];
			if (isset($user['role'])) {
				$user_info['role'] = $user['role'];
			}
			if (isset($user['tutor_id'])) {
				$user_info['tutor_id'] = $user['tutor_id'];
			}
			$users[$user['user_id']] = $user_info;
		}

		return $users;
	}

	/**
	 * Get a list of coaches of a course and a session
	 * @param   string  Course code
	 * @param   int     Session ID
	 * @return  array   List of users
	 */
	public static function get_coach_list_from_course_code($course_code, $session_id) {

		if ($session_id != strval(intval($session_id))) {
			return array();
		}

		$course_code = Database::escape_string($course_code);

		$users = array();

		// We get the coach for the given course in a given session.
		$rs = Database::query('SELECT id_coach FROM '.Database::get_main_table(TABLE_MAIN_SESSION_COURSE).
				' WHERE id_session="'.$session_id.'" AND course_code="'.$course_code.'"', __FILE__, __LINE__);
		while ($user = Database::fetch_array($rs)) {
			$user_info = Database::get_user_info_from_id($user['id_coach']);
			$user_info['status'] = $user['status'];
			$user_info['role'] = $user['role'];
			$user_info['tutor_id'] = $user['tutor_id'];
			$user_info['email'] = $user['email'];
			$users[$user['id_coach']] = $user_info;
		}

		// We get the session coach.
		$rs = Database::query('SELECT id_coach FROM '.Database::get_main_table(TABLE_MAIN_SESSION).
				' WHERE id="'.$session_id.'"', __FILE__, __LINE__);
		$user_info = array();
		$session_id_coach = Database::result($rs, 0, 'id_coach');
		$user_info = Database::get_user_info_from_id($session_id_coach);
		$user_info['status'] = $user['status'];
		$user_info['role'] = $user['role'];
		$user_info['tutor_id'] = $user['tutor_id'];
		$user_info['email'] = $user['email'];
		$users[$session_id_coach] = $user_info;

		return $users;
	}


	/**
	 *	Return user info array of all users registered in the specified real or virtual course
	 *	This only returns the users that are registered in this actual course, not linked courses.
	 *
	 *	@param string $course_code
	 *	@param boolean $full list to true if we want sessions students too
	 *	@return array with user id
	 */
	public static function get_student_list_from_course_code($course_code, $with_session = false, $session_id = 0) {
		$session_id = intval($session_id);
		$course_code = Database::escape_string($course_code);

		$students = array();

		if ($session_id == 0) {
			// students directly subscribed to the course
			$rs = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
					WHERE course_code = '$course_code' AND status = 5", __FILE__, __LINE__);
			while ($student = Database::fetch_array($rs)) {
				$students[$student['user_id']] = $student;
			}
		}

		// students subscribed to the course through a session

		if (api_get_setting('use_session_mode') == 'true' && $with_session) {
			$sql_query = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." WHERE course_code = '$course_code'";
			if ($session_id != 0) {
				$sql_query .= ' AND id_session = '.$session_id;
			}
			$rs = Database::query($sql_query, __FILE__, __LINE__);
			while($student = Database::fetch_array($rs)) {
				$students[$student['id_user']] = $student;
			}
		}

		return $students;
	}

	/**
	 *	Return user info array of all teacher-users registered in the specified real or virtual course
	 *	This only returns the users that are registered in this actual course, not linked courses.
	 *
	 *	@param string $course_code
	 *	@return array with user id
	 */
	public static function get_teacher_list_from_course_code($course_code) {

		$course_code = Database::escape_string($course_code);

		// teachers directly subscribed to the course
		$teachers = array();
		// TODO: This query is not optimal.
		$rs = Database::query("SELECT u.user_id, u.lastname, u.firstname, u.email, u.username, u.status " .
				"FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." cu, ".Database::get_main_table(TABLE_MAIN_USER)." u ".
				"WHERE cu.course_code = '$course_code' " .
				"AND cu.status = 1 " .
				"AND cu.user_id = u.user_id", __FILE__, __LINE__);
		while ($teacher = Database::fetch_array($rs)) {
			$teachers[$teacher['user_id']] = $teacher;
		}
		return $teachers;
	}

	/**
	 *	Return user info array of all users registered in the specified course
	 *	this includes the users of the course itsel and the users of all linked courses.
	 *
	 *	@param array $course_info
	 *	@return array with user info
	 */
	public static function get_real_and_linked_user_list($course_code, $with_sessions = true, $session_id = 0) {
		//get list of virtual courses
		$virtual_course_list = self::get_virtual_courses_linked_to_real_course($course_code);

		//get users from real course
		$user_list = self::get_user_list_from_course_code($course_code, $with_sessions, $session_id);
		foreach ($user_list as $this_user) {
			$complete_user_list[] = $this_user;
		}

		//get users from linked courses
		foreach ($virtual_course_list as $this_course) {
			$course_code = $this_course['code'];
			$user_list = self::get_user_list_from_course_code($course_code, $with_sessions, $session_id);
			foreach ($user_list as $this_user) {
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
	public static function get_list_of_virtual_courses_for_specific_user_and_real_course($user_id, $real_course_code) {
		$result_array = array();

		if ($user_id != strval(intval($user_id))) {
			return $result_array;
		}

		$course_code = Database::escape_string($course_code);

		$sql_result = Database::query("SELECT *
				FROM ".Database::get_main_table(TABLE_MAIN_COURSE)." course
				LEFT JOIN ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." course_user
				ON course.code = course_user.course_code
				WHERE course.target_course_code = '$real_course_code' AND course_user.user_id = '$user_id'", __FILE__, __LINE__);

		while ($result = Database::fetch_array($sql_result)) {
			$result_array[] = $result;
		}

		return $result_array;
	}

	/*
	==============================================================================
		GROUP FUNCTIONS
	==============================================================================
	*/

	/**
	 * Get the list of groups from the course
	 * @param   string  Course code
	 * @param   int     Session ID (optional)
	 * @return  array   List of groups info
	 */
	public static function get_group_list_of_course($course_code, $session_id = 0) {
		$course_info = Database::get_course_info($course_code);
		$database_name = $course_info['db_name'];

		$group_list = array();
		$session_condition = $session_id == 0 ? '' : ' WHERE g.session_id IN(0,'.intval($session_id).')';

		$result = Database::query("SELECT g.id, g.name, COUNT(gu.id) userNb
				FROM ".Database::get_course_table(TABLE_GROUP, $database_name)." AS g
				LEFT JOIN ".Database::get_course_table(TABLE_GROUP_USER, $database_name)." gu
				ON g.id = gu.group_id
				$session_condition
				GROUP BY g.id
				ORDER BY g.name", __FILE__, __LINE__);

		while ($group_data = Database::fetch_array($result)) {
			$group_list[$group_data['id']] = $group_data;
		}
		return $group_list;
	}

	/**
	 * Checks all parameters needed to create a virtual course.
	 * If they are all set, the virtual course creation procedure is called.
	 *
	 * Call this function instead of create_virtual_course
	 * @param  string  Course code
	 * @param  string  Course title
	 * @param  string  Wanted course code
	 * @param  string  Course language
	 * @param  string  Course category
	 * @return bool    True on success, false on error
	 */
	public static function attempt_create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category) {
		//better: create parameter list, check the entire list, when false display errormessage
		self::check_parameter_or_fail($real_course_code, 'Unspecified parameter: real course id.');
		self::check_parameter_or_fail($course_title, 'Unspecified parameter: course title.');
		self::check_parameter_or_fail($wanted_course_code, 'Unspecified parameter: wanted course code.');
		self::check_parameter_or_fail($course_language, 'Unspecified parameter: course language.');
		self::check_parameter_or_fail($course_category, 'Unspecified parameter: course category.');

		return self::create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category);
	}

	/**
	 * This function creates a virtual course.
	 * It assumes all parameters have been checked and are not empty.
	 * It checks wether a course with the $wanted_course_code already exists.
	 *
	 * Users of this library should consider this function private,
	 * please call attempt_create_virtual_course instead of this one.
	 *
	 * @note The virtual course 'owner' id (the first course admin) is set to the CURRENT user id.
	 * @param  string  Course code
	 * @param  string  Course title
	 * @param  string  Wanted course code
	 * @param  string  Course language
	 * @param  string  Course category
	 * @return true if the course creation succeeded, false otherwise
	 * @todo research: expiration date of a course
	 */
	public static function create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category) {
		global $firstExpirationDelay;

		$user_id = api_get_user_id();
		$real_course_info = Database::get_course_info($real_course_code);
		$real_course_code = $real_course_info['system_code'];

		//check: virtual course creation fails if another course has the same
		//code, real or fake.
		if (self::is_existing_course_code($wanted_course_code)) {
			Display::display_error_message($wanted_course_code.' - '.get_lang('CourseCodeAlreadyExists'));
			return false;
		}

		//add data to course table, course_rel_user
		$course_sys_code = $wanted_course_code;
		$course_screen_code = $wanted_course_code;
		$course_repository = $real_course_info['directory'];
		$course_db_name = $real_course_info['db_name'];
		$responsible_teacher = $real_course_info['tutor_name'];
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
		Database::query("UPDATE ".Database::get_main_table(TABLE_MAIN_COURSE)." SET target_course_code = '$real_course_code'
				WHERE code = '".Database::escape_string($course_sys_code)."' LIMIT 1 ", __FILE__, __LINE__);

		return true;
	}

	/**
	 * Delete a course
	 * This function deletes a whole course-area from the platform. When the
	 * given course is a virtual course, the database and directory will not be
	 * deleted.
	 * When the given course is a real course, also all virtual courses refering
	 * to the given course will be deleted.
	 * Considering the fact that we remove all traces of the course in the main
	 * database, it makes sense to remove all tracking as well (if stats databases exist)
	 * so that a new course created with this code would not use the remains of an older
	 * course.
	 *
	 * @param string The code of the course to delete
	 * @todo When deleting a virtual course: unsubscribe users from that virtual
	 * course from the groups in the real course if they are not subscribed in
	 * that real course.
	 * @todo Remove globals
	 */
	public static function delete_course($code) {
		global $_configuration;

		$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
		$table_course_user = Database::get_main_table(TABLE_MAIN_COURSE_USER);
		$table_course_class = Database::get_main_table(TABLE_MAIN_COURSE_CLASS);
		$user_role_table = Database::get_main_table(MAIN_USER_ROLE_TABLE);
		$location_table = Database::get_main_table(MAIN_LOCATION_TABLE);
		$role_right_location_table = Database::get_main_table(MAIN_ROLE_RIGHT_LOCATION_TABLE);
		$table_session_course = Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		$table_session_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$table_course_survey = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY);
		$table_course_survey_question = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION);
		$table_course_survey_question_option = Database::get_main_table(TABLE_MAIN_SHARED_SURVEY_QUESTION_OPTION);
		$stats = false;
		if (Database::get_statistic_database() != ''){
			$stats = true;
			$table_stats_hotpots = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_HOTPOTATOES);
			$table_stats_attempt = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ATTEMPT);
			$table_stats_exercises = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_EXERCICES);
			$table_stats_access = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ACCESS);
			$table_stats_lastaccess = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LASTACCESS);
			$table_stats_course_access = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
			$table_stats_online = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_ONLINE);
			$table_stats_default = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DEFAULT);
			$table_stats_downloads = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_DOWNLOADS);
			$table_stats_links = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_LINKS);
			$table_stats_uploads = Database::get_statistic_table(TABLE_STATISTIC_TRACK_E_UPLOADS);
		}
		$code = Database::escape_string($code);
		$sql = "SELECT * FROM $table_course WHERE code='".$code."'";
		$res = Database::query($sql, __FILE__, __LINE__);
		if (Database::num_rows($res) == 0) {
			return;
		}
		$this_course = Database::fetch_array($res);
		$db_name = $this_course['db_name'];
		self::create_database_dump($code);
		if (!self::is_virtual_course_from_system_code($code)) {
			// If this is not a virtual course, look for virtual courses that depend on this one, if any
			$virtual_courses = self::get_virtual_courses_linked_to_real_course($code);
			foreach ($virtual_courses as $index => $virtual_course) {
				// Unsubscribe all classes from the virtual course
				$sql = "DELETE FROM $table_course_class WHERE course_code='".$virtual_course['code']."'";
				Database::query($sql, __FILE__, __LINE__);
				// Unsubscribe all users from the virtual course
				$sql = "DELETE FROM $table_course_user WHERE course_code='".$virtual_course['code']."'";
				Database::query($sql, __FILE__, __LINE__);
				// Delete the course from the sessions tables
				$sql = "DELETE FROM $table_session_course WHERE course_code='".$virtual_course['code']."'";
				Database::query($sql, __FILE__, __LINE__);
				$sql = "DELETE FROM $table_session_course_user WHERE course_code='".$virtual_course['code']."'";
				Database::query($sql, __FILE__, __LINE__);
				// Delete the course from the survey tables
				$sql = "DELETE FROM $table_course_survey WHERE course_code='".$virtual_course['code']."'";
				Database::query($sql, __FILE__, __LINE__);
				$sql = "DELETE FROM $table_course_survey_user WHERE db_name='".$virtual_course['db_name']."'";
				Database::query($sql, __FILE__, __LINE__);
				$sql = "DELETE FROM $table_course_survey_reminder WHERE db_name='".$virtual_course['db_name']."'";
				Database::query($sql, __FILE__, __LINE__);

				// Delete the course from the stats tables
				if ($stats) {
					$sql = "DELETE FROM $table_stats_hotpots WHERE exe_cours_id = '".$virtual_course['code']."'";
					Database::query($sql, __FILE__, __LINE__);
					$sql = "DELETE FROM $table_stats_attempt WHERE course_code = '".$virtual_course['code']."'";
					Database::query($sql, __FILE__, __LINE__);
					$sql = "DELETE FROM $table_stats_exercises WHERE exe_cours_id = '".$virtual_course['code']."'";
					Database::query($sql, __FILE__, __LINE__);
					$sql = "DELETE FROM $table_stats_access WHERE access_cours_code = '".$virtual_course['code']."'";
					Database::query($sql, __FILE__, __LINE__);
					$sql = "DELETE FROM $table_stats_lastaccess WHERE access_cours_code = '".$virtual_course['code']."'";
					Database::query($sql, __FILE__, __LINE__);
					$sql = "DELETE FROM $table_stats_course_access WHERE course_code = '".$virtual_course['code']."'";
					Database::query($sql, __FILE__, __LINE__);
					$sql = "DELETE FROM $table_stats_online WHERE course = '".$virtual_course['code']."'";
					Database::query($sql, __FILE__, __LINE__);
					$sql = "DELETE FROM $table_stats_default WHERE default_cours_code = '".$virtual_course['code']."'";
					Database::query($sql, __FILE__, __LINE__);
					$sql = "DELETE FROM $table_stats_downloads WHERE down_cours_id = '".$virtual_course['code']."'";
					Database::query($sql, __FILE__, __LINE__);
					$sql = "DELETE FROM $table_stats_links WHERE links_cours_id = '".$virtual_course['code']."'";
					Database::query($sql, __FILE__, __LINE__);
					$sql = "DELETE FROM $table_stats_uploads WHERE upload_cours_id = '".$virtual_course['code']."'";
					Database::query($sql, __FILE__, __LINE__);
				}

				// Delete the course from the course table
				$sql = "DELETE FROM $table_course WHERE code='".$virtual_course['code']."'";
				Database::query($sql, __FILE__, __LINE__);
			}
			$sql = "SELECT * FROM $table_course WHERE code='".$code."'";
			$res = Database::query($sql, __FILE__, __LINE__);
			$course = Database::fetch_array($res);
			if (!$_configuration['single_database']) {
				$sql = "DROP DATABASE IF EXISTS ".$course['db_name'];
				Database::query($sql, __FILE__, __LINE__);
			} else {
				//TODO Clean the following code as currently it would probably delete another course
				//similarly named, by mistake...
				$db_pattern = $_configuration['table_prefix'].$course['db_name'].$_configuration['db_glue'];
				$sql = "SHOW TABLES LIKE '$db_pattern%'";
				$result = Database::query($sql, __FILE__, __LINE__);
				while (list ($courseTable) = Database::fetch_array($result)) {
					Database::query("DROP TABLE $courseTable", __FILE__, __LINE__);
				}
			}
			$course_dir = api_get_path(SYS_COURSE_PATH).$course['directory'];
			$archive_dir = api_get_path(SYS_ARCHIVE_PATH).$course['directory'].'_'.time();
			if (is_dir($course_dir)) {
				rename($course_dir, $archive_dir);
			}
		}

		// Unsubscribe all classes from the course
		$sql = "DELETE FROM $table_course_class WHERE course_code='".$code."'";
		Database::query($sql, __FILE__, __LINE__);
		// Unsubscribe all users from the course
		$sql = "DELETE FROM $table_course_user WHERE course_code='".$code."'";
		Database::query($sql, __FILE__, __LINE__);
		// Delete the course from the sessions tables
		$sql = "DELETE FROM $table_session_course WHERE course_code='".$code."'";
		Database::query($sql, __FILE__, __LINE__);
		$sql = "DELETE FROM $table_session_course_user WHERE course_code='".$code."'";
		Database::query($sql, __FILE__, __LINE__);

		$sql = 'SELECT survey_id FROM '.$table_course_survey.' WHERE course_code="'.$code.'"';
		$result_surveys = Database::query($sql, __FILE__, __LINE__);
		while($surveys = Database::fetch_array($result_surveys)) {
			$survey_id = $surveys[0];
			$sql = 'DELETE FROM '.$table_course_survey_question.' WHERE survey_id="'.$survey_id.'"';
			Database::query($sql, __FILE__, __LINE__);
			$sql = 'DELETE FROM '.$table_course_survey_question_option.' WHERE survey_id="'.$survey_id.'"';
			Database::query($sql, __FILE__, __LINE__);
			$sql = 'DELETE FROM '.$table_course_survey.' WHERE survey_id="'.$survey_id.'"';
			Database::query($sql, __FILE__, __LINE__);
		}

		// Delete the course from the stats tables
		if ($stats) {
			$sql = "DELETE FROM $table_stats_hotpots WHERE exe_cours_id = '".$code."'";
			Database::query($sql, __FILE__, __LINE__);
			$sql = "DELETE FROM $table_stats_attempt WHERE course_code = '".$code."'";
			Database::query($sql, __FILE__, __LINE__);
			$sql = "DELETE FROM $table_stats_exercises WHERE exe_cours_id = '".$code."'";
			Database::query($sql, __FILE__, __LINE__);
			$sql = "DELETE FROM $table_stats_access WHERE access_cours_code = '".$code."'";
			Database::query($sql, __FILE__, __LINE__);
			$sql = "DELETE FROM $table_stats_lastaccess WHERE access_cours_code = '".$code."'";
			Database::query($sql, __FILE__, __LINE__);
			$sql = "DELETE FROM $table_stats_course_access WHERE course_code = '".$code."'";
			Database::query($sql, __FILE__, __LINE__);
			$sql = "DELETE FROM $table_stats_online WHERE course = '".$code."'";
			Database::query($sql, __FILE__, __LINE__);
			$sql = "DELETE FROM $table_stats_default WHERE default_cours_code = '".$code."'";
			Database::query($sql, __FILE__, __LINE__);
			$sql = "DELETE FROM $table_stats_downloads WHERE down_cours_id = '".$code."'";
			Database::query($sql, __FILE__, __LINE__);
			$sql = "DELETE FROM $table_stats_links WHERE links_cours_id = '".$code."'";
			Database::query($sql, __FILE__, __LINE__);
			$sql = "DELETE FROM $table_stats_uploads WHERE upload_cours_id = '".$code."'";
			Database::query($sql, __FILE__, __LINE__);
		}

		global $_configuration;
		if ($_configuration['multiple_access_urls'] == true) {
			require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
			$url_id = 1;
			if (api_get_current_access_url_id() != -1) {
				$url_id = api_get_current_access_url_id();
			}
			UrlManager::delete_url_rel_course($code, $url_id);
		}

		// Delete the course from the database
		$sql = "DELETE FROM $table_course WHERE code='".$code."'";
		Database::query($sql, __FILE__, __LINE__);

		// delete extra course fields
		$t_cf 		= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
		$t_cfv 		= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);

		$sql = "SELECT distinct field_id FROM $t_cfv WHERE course_code = '$code'";
		$res_field_ids = @Database::query($sql, __FILE__, __LINE__);

		while($row_field_id = Database::fetch_row($res_field_ids)){
			$field_ids[] = $row_field_id[0];
		}

		//delete from table_course_field_value from a given course_code

		$sql_course_field_value = "DELETE FROM $t_cfv WHERE course_code = '$code'";
		@Database::query($sql_course_field_value, __FILE__, __LINE__);

		$sql = "SELECT distinct field_id FROM $t_cfv";
		$res_field_all_ids = @Database::query($sql, __FILE__, __LINE__);

		while($row_field_all_id = Database::fetch_row($res_field_all_ids)){
			$field_all_ids[] = $row_field_all_id[0];
		}

		if (is_array($field_ids) && count($field_ids) > 0) {
			foreach ($field_ids as $field_id) {
				// check if field id is used into table field value
				if (is_array($field_all_ids)) {
					if (in_array($field_id, $field_all_ids)) {
						continue;
					} else {
						$sql_course_field = "DELETE FROM $t_cf WHERE id = '$field_id'";
						Database::query($sql_course_field, __FILE__, __LINE__);
					}
				}
			}
		}

		// add event to system log
		$time = time();
		$user_id = api_get_user_id();
		event_system(LOG_COURSE_DELETE, LOG_COURSE_CODE, $code, $time, $user_id, $code);

	}

	/**
	 * Creates a file called mysql_dump.sql in the course folder
	 * @param $course_code The code of the course
	 * @todo Implementation for single database
	 */
	public static function create_database_dump($course_code) {
		global $_configuration;

		if ($_configuration['single_database']) {
			return;
		}
		$sql_dump = '';
		$course_code = Database::escape_string($course_code);
		$table_course = Database::get_main_table(TABLE_MAIN_COURSE);
		$sql = "SELECT * FROM $table_course WHERE code = '$course_code'";
		$res = Database::query($sql, __FILE__, __LINE__);
		$course = Database::fetch_array($res);
		$sql = "SHOW TABLES FROM ".$course['db_name'];
		$res = Database::query($sql, __FILE__, __LINE__);
		while ($table = Database::fetch_array($res)) {
			$sql = "SELECT * FROM ".$course['db_name'].".".$table[0]."";
			$res3 = Database::query($sql, __FILE__, __LINE__);
			while ($row = Database::fetch_array($res3)) {
				foreach ($row as $key => $value) {
					$row[$key] = $key."='".addslashes($row[$key])."'";
				}
				$sql_dump .= "\nINSERT INTO $table[0] SET ".implode(', ', $row).';';
			}
		}
		if (is_dir(api_get_path(SYS_COURSE_PATH).$course['directory'])) {
			$file_name = api_get_path(SYS_COURSE_PATH).$course['directory'].'/mysql_dump.sql';
			$handle = fopen($file_name, 'a+');
			if ($handle !== false) {
				fwrite($handle, $sql_dump);
				fclose($handle);
			} else {
				//TODO trigger exception in a try-catch
			}
		}
	}

	/**
	 * Sort courses for a specific user ??
	 * @param   int     User ID
	 * @param   string  Course code
	 * @return  int     Minimum course order
	 * @todo Review documentation
	 */
	public static function userCourseSort($user_id, $course_code) {

		if ($user_id != strval(intval($user_id))) {
			return false;
		}

		$course_code = Database::escape_string($course_code);
		$TABLECOURSE = Database::get_main_table(TABLE_MAIN_COURSE);
		$TABLECOURSUSER = Database::get_main_table(TABLE_MAIN_COURSE_USER);

		$course_title = Database::result(Database::query('SELECT title FROM '.$TABLECOURSE.
				' WHERE code="'.$course_code.'"', __FILE__, __LINE__), 0, 0);

		$result = Database::query('SELECT course.code as code, course.title as title, cu.sort as sort FROM '.$TABLECOURSUSER.' as cu, '.$TABLECOURSE.' as course
				WHERE course.code = cu.course_code
				AND user_id = "'.$user_id.'"
				AND user_course_cat=0 ORDER BY cu.sort', __FILE__, __LINE__);

		$course_title_precedent = '';
		$counter = 0;
		$course_found = false;
		$course_sort = 1;

		while ($courses = Database::fetch_array($result)){

			if ($course_title_precedent == '') {
				$course_title_precedent = $courses['title'];
			}

			if (api_strcasecmp($course_title_precedent, $course_title) < 0) {

				$course_found = true;
				$course_sort = $courses['sort'];

				if ($counter == 0) {
					$sql = 'UPDATE '.$TABLECOURSUSER.' SET sort = sort+1 WHERE user_id= "'.$user_id.'" AND user_course_cat="0" AND sort > "'.$course_sort.'"';
					$course_sort++;
				} else {
					$sql = 'UPDATE '.$TABLECOURSUSER.' SET sort = sort+1 WHERE user_id= "'.$user_id.'" AND user_course_cat="0" AND sort >= "'.$course_sort.'"';
				}

				Database::query($sql, __FILE__, __LINE__);
				break;

			} else {
				$course_title_precedent = $courses['title'];
			}

			$counter++;
		}

		// We must register the course in the beginning of the list
		if (Database::num_rows($result) > 0 && !$course_found) {
			$course_sort = Database::result(Database::query('SELECT min(sort) as min_sort FROM '.$TABLECOURSUSER.
					' WHERE user_id="'.$user_id.'" AND user_course_cat="0"', __FILE__, __LINE__), 0, 0);

			Database::query('UPDATE '.$TABLECOURSUSER.' SET sort = sort+1
					WHERE user_id= "'.$user_id.'" AND user_course_cat="0"', __FILE__, __LINE__);
		}

		return $course_sort;
	}

	/**
	 * create recursively all categories as option of the select passed in paramater.
	 *
	 * @param object $select_element the quickform select where the options will be added
	 * @param string $category_selected_code the option value to select by default (used mainly for edition of courses)
	 * @param string $parent_code the parent category of the categories added (default=null for root category)
	 * @param string $padding the indent param (you shouldn't indicate something here)
	 */
	public static function select_and_sort_categories($select_element, $category_selected_code = '', $parent_code = null , $padding = '') {

		$res = Database::query("SELECT code, name, auth_course_child, auth_cat_child
				FROM ".Database::get_main_table(TABLE_MAIN_CATEGORY)."
				WHERE parent_id ".(is_null($parent_code) ? "IS NULL" : "='".Database::escape_string($parent_code)."'")."
				ORDER BY code", __FILE__, __LINE__);

		while ($cat = Database::fetch_array($res)) {
			$params = $cat['auth_course_child'] == 'TRUE' ? '' : 'disabled';
			$params .= ($cat['code'] == $category_selected_code) ? ' selected' : '';
			$select_element->addOption($padding.'('.$cat['code'].') '.$cat['name'], $cat['code'], $params);
			if ($cat['auth_cat_child']) {
				self::select_and_sort_categories($select_element, $category_selected_code, $cat['code'], $padding.' - ');
			}
		}
	}

	/**
	 * check if course exists
	 * @param string course_code
	 * @param string whether to accept virtual course codes or not
	 * @return true if exists, false else
	 */
	public static function course_exists($course_code, $accept_virtual = false) {
		if ($accept_virtual === true) {
			$sql = 'SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_COURSE).' WHERE code="'.Database::escape_string($course_code).'" OR visual_code="'.Database::escape_string($course_code).'"';
		} else {
			$sql = 'SELECT 1 FROM '.Database::get_main_table(TABLE_MAIN_COURSE).' WHERE code="'.Database::escape_string($course_code).'"';
		}
		return Database::num_rows(Database::query($sql, __FILE__, __LINE__));
	}

	/**
	 * Send an email to tutor after the auth-suscription of a student in your course
	 * @author Carlos Vargas <carlos.vargas@dokeos.com>, Dokeos Latino
	 * @param  int $user_id the id of the user
	 * @param  string $course_code the course code
	 * @param  string $send_to_tutor_also
	 * @return string we return the message that is displayed when the action is succesfull
	 */
	public static function email_to_tutor($user_id, $course_code, $send_to_tutor_also = false) {

		if ($user_id != strval(intval($user_id))) {
			return false;
		}

		$course_code = Database::escape_string($course_code);

		$student = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
				WHERE user_id='".$user_id."'", __FILE__, __LINE__));
		$information = self::get_course_information($course_code);
		$name_course = $information['title'];
		$sql = "SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)." WHERE course_code='".$course_code."'";

		// TODO: Ivan: This is a mistake, please, have a look at it. Intention here is diffcult to be guessed.
		//if ($send_to_tutor_also = true) {
		// Proposed change:
		if ($send_to_tutor_also) {
		//
			$sql .= " AND tutor_id=1";
		} else {
			$sql .= " AND status=1";
		}

		$result = Database::query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result)) {
			$tutor = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
					WHERE user_id='".$row['user_id']."'", __FILE__, __LINE__));
			$emailto		 = $tutor['email'];
			$emailsubject	 = get_lang('NewUserInTheCourse').': '.$name_course;
			$emailbody		 = get_lang('Dear').': '. api_get_person_name($tutor['firstname'], $tutor['lastname'])."\n";
			$emailbody		.= get_lang('MessageNewUserInTheCourse').': '.$name_course."\n";
			$emailbody		.= get_lang('UserName').': '.$student['username']."\n";
			if (api_is_western_name_order()) {
				$emailbody	.= get_lang('FirstName').': '.$student['firstname']."\n";
				$emailbody	.= get_lang('LastName').': '.$student['lastname']."\n";
			} else {
				$emailbody	.= get_lang('LastName').': '.$student['lastname']."\n";
				$emailbody	.= get_lang('FirstName').': '.$student['firstname']."\n";
			}
			$emailbody		.= get_lang('Email').': '.$student['email']."\n\n";
			$recipient_name = api_get_person_name($tutor['firstname'], $tutor['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
			$sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
			$email_admin = api_get_setting('emailAdministrator');
			@api_mail($recipient_name, $emailto, $emailsubject, $emailbody, $sender_name,$email_admin);
		}
	}

	/**
	 * Get list of courses for a given user
	 * @param int       user ID
	 * @param boolean   Whether to include courses from session or not
	 * @return array    List of codes and db names
	 * @author isaac flores paz
	 */
	public static function get_courses_list_by_user_id($user_id, $include_sessions = false) {
		$user_id = intval($user_id);
		$course_list = array();
		$codes = array();

		$result = Database::query('SELECT c.code,c.db_name,c.title
				FROM '.Database::get_main_table(TABLE_MAIN_COURSE).' c
				INNER JOIN '.Database::get_main_table(TABLE_MAIN_COURSE_USER).' cru
				ON c.code=cru.course_code
				WHERE cru.user_id='.$user_id, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result, 'ASSOC')) {
			$course_list[] = $row;
			$codes[] = $row['code'];
		}

		if ($include_sessions === true) {
			$r = Database::query("SELECT distinct(c.code),c.db_name,c.title
					FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER)." s, ".Database::get_main_table(TABLE_MAIN_COURSE)." c
					WHERE id_user = $user_id AND s.course_code=c.code", __FILE__, __LINE__);
			while ($row = Database::fetch_array($r, 'ASSOC')) {
				if (!in_array($row['code'], $codes)) {
					$course_list[] = $row;
				}
			}
		}
		return $course_list;
	}

	/**
	 * Get course ID from a given course directory name
	 * @param   string  Course directory (without any slash)
	 * @return  string  Course code, or false if not found
	 */
	public static function get_course_id_from_path ($path) {
		$path = Database::escape_string(str_replace('.', '', str_replace('/', '', $path)));
		$res = Database::query("SELECT code FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
				WHERE directory LIKE BINARY '$path'", __FILE__, __LINE__);
		if ($res === false) {
			return false;
		}
		if (Database::num_rows($res) != 1) {
			return false;
		}
		$row = Database::fetch_array($res);
		return $row['code'];
	}

	/**
	 * Get course code(s) from visual code
	 * @param   string  Visual code
	 * @return  array   List of codes for the given visual code
	 */
	public static function get_courses_info_from_visual_code($code) {
		$result = array();
		$sql_result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_COURSE)."
				WHERE visual_code = '".Database::escape_string($code)."'", __FILE__, __LINE__);
		while ($virtual_course = Database::fetch_array($sql_result)) {
			$result[] = $virtual_course;
		}
		return $result;
	}

	/**
	 * Get emails of tutors to course
	 * @param string Visual code
	 * @return array List of emails of tutors to course
	 * @author @author Carlos Vargas <carlos.vargas@dokeos.com>, Dokeos Latino
	 * */
	public static function get_emails_of_tutors_to_course($code) {
		$list = array();
		$res = Database::query("SELECT user_id FROM ".Database::get_main_table(TABLE_MAIN_COURSE_USER)."
				WHERE course_code='".Database::escape_string($code)."' AND status=1", __FILE__, __LINE__);
		while ($list_users = Database::fetch_array($res)) {
			$result = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
					WHERE user_id=".$list_users['user_id'], __FILE__, __LINE__);
			while ($row_user = Database::fetch_array($result)){
				$name_teacher = api_get_person_name($row_user['firstname'], $row_user['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
				$list[] = array($row_user['email'] => $name_teacher);
			}
		}
		return $list;
	}

	/**
	 * Get emails of tutors to course
	 * @param string session session
	 * @return string email of tutor to session
	 * @author @author Carlos Vargas <carlos.vargas@dokeos.com>, Dokeos Latino
	 */
	public static function get_email_of_tutor_to_session($session) {
		$row_email = Database::fetch_array(Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_SESSION_COURSE)."
				WHERE id_session='".Database::escape_string($session)."'", __FILE__, __LINE__));
		$result_user = Database::query("SELECT * FROM ".Database::get_main_table(TABLE_MAIN_USER)."
				WHERE user_id=".$row_email['id_coach'], __FILE__, __LINE__);
		while ($row_emails = Database::fetch_array($result_user)) {
			$name_tutor = api_get_person_name($row_emails['firstname'], $row_emails['lastname'], null, PERSON_NAME_EMAIL_ADDRESS);
			$mail_tutor = array($row_emails['email'] => $name_tutor);
		}
		return $mail_tutor;
	}

	/**
	 * Creates a new extra field for a given course
 	 * @param	string	Field's internal variable name
 	 * @param	int		Field's type
 	 * @param	string	Field's language var name
 	 * @return int     new extra field id
 	 */
	public static function create_course_extra_field($fieldvarname, $fieldtype, $fieldtitle) {
		// database table definition
		$t_cfv			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
		$t_cf 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
		$fieldvarname 	= Database::escape_string($fieldvarname);
		$fieldtitle 	= Database::escape_string($fieldtitle);
		$fieldtype = (int)$fieldtype;
		$time = time();
		$sql_field = "SELECT id FROM $t_cf WHERE field_variable = '$fieldvarname'";
		$res_field = Database::query($sql_field, __FILE__, __LINE__);

		$r_field = Database::fetch_row($res_field);

		if (Database::num_rows($res_field) > 0) {
			return $r_field[0];
		}

		// save new fieldlabel into course_field table
		$sql = "SELECT MAX(field_order) FROM $t_cf";
		$res = Database::query($sql, __FILE__, __LINE__);

		$order = 0;
		if (Database::num_rows($res) > 0) {
			$row = Database::fetch_row($res);
			$order = $row[0] + 1;
		}

		$sql = "INSERT INTO $t_cf
									SET field_type = '$fieldtype',
									field_variable = '$fieldvarname',
									field_display_text = '$fieldtitle',
									field_order = '$order',
									tms = FROM_UNIXTIME($time)";
		Database::query($sql, __FILE__, __LINE__);

		return Database::get_last_insert_id();
	}

	/**
	 * Update an extra field value for a given course
	 * @param	integer	Course ID
	 * @param	string	Field variable name
	 * @param	string	Field value
	 * @return	boolean	true if field updated, false otherwise
	 */
	public static function update_course_extra_field_value($course_code, $fname, $fvalue = '') {

		$t_cfv			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD_VALUES);
		$t_cf 			= Database::get_main_table(TABLE_MAIN_COURSE_FIELD);
		$fname = Database::escape_string($fname);
		$course_code = Database::escape_string($course_code);
		$fvalues = '';
		if (is_array($fvalue)) {
			foreach ($fvalue as $val) {
				$fvalues .= Database::escape_string($val).';';
			}
			if (!empty($fvalues)) {
				$fvalues = substr($fvalues, 0, -1);
			}
		} else {
			$fvalues = Database::escape_string($fvalue);
		}

		$sqlcf = "SELECT * FROM $t_cf WHERE field_variable='$fname'";
		$rescf = Database::query($sqlcf, __FILE__, __LINE__);
		if (Database::num_rows($rescf) == 1) {
			// Ok, the field exists
			// Check if enumerated field, if the option is available
			$rowcf = Database::fetch_array($rescf);

			$tms = time();
			$sqlcfv = "SELECT * FROM $t_cfv WHERE course_code = '$course_code' AND field_id = '".$rowcf['id']."' ORDER BY id";
			$rescfv = Database::query($sqlcfv, __FILE__, __LINE__);
			$n = Database::num_rows($rescfv);
			if ($n > 1) {
				//problem, we already have to values for this field and user combination - keep last one
				while ($rowcfv = Database::fetch_array($rescfv)) { // See the TODO note below.
					if ($n > 1) {
						$sqld = "DELETE FROM $t_cfv WHERE id = ".$rowcfv['id'];
						$resd = Database::query($sqld, __FILE__, __LINE__);
						$n--;
					}
					$rowcfv = Database::fetch_array($rescfv);
					if ($rowcfv['field_value'] != $fvalues) {
						$sqlu = "UPDATE $t_cfv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = ".$rowcfv['id'];
						$resu = Database::query($sqlu, __FILE__, __LINE__);
						return ($resu ? true : false);
					}
					return true; // TODO: Sure exit from the function occures in this "while" cycle. Logic should checked. Maybe "if" instead of "while"? It is not clear...
				}
			} elseif ($n == 1) {
				//we need to update the current record
				$rowcfv = Database::fetch_array($rescfv);
				if ($rowcfv['field_value'] != $fvalues) {
					$sqlu = "UPDATE $t_cfv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = ".$rowcfv['id'];
					//error_log('UM::update_extra_field_value: '.$sqlu);
					$resu = Database::query($sqlu, __FILE__, __LINE__);
					return ($resu ? true : false);
				}
				return true;
			} else {
				$sqli = "INSERT INTO $t_cfv (course_code,field_id,field_value,tms) " .
					"VALUES ('$course_code',".$rowcf['id'].",'$fvalues',FROM_UNIXTIME($tms))";
				//error_log('UM::update_extra_field_value: '.$sqli);
				$resi = Database::query($sqli, __FILE__, __LINE__);
				return ($resi ? true : false);
			}
		} else {
			return false; //field not found
		}
	}

	/**
	 * Get the course id of an course by the database name
	 * @param string The database name
	 * @return string The course id
	 */
	public static function get_course_id_by_database_name($db_name) {
		return Database::result(Database::query('SELECT code FROM '.Database::get_main_table(TABLE_MAIN_COURSE).
				' WHERE db_name="'.Database::escape_string($db_name).'"', __FILE__, __LINE__), 0, 'code');
	}

	public static function get_session_category_id_by_session_id($session_id) {
		return Database::result(Database::query('SELECT  sc.id session_category
				FROM '.Database::get_main_table(TABLE_MAIN_SESSION_CATEGORY).' sc
				INNER JOIN '.Database::get_main_table(TABLE_MAIN_SESSION).' s
				ON sc.id=s.session_category_id WHERE s.id="'.Database::escape_string($session_id).'"', __FILE__, __LINE__),
			0, 'session_category');
	}

	/**
	 * Get the database name of a course by the code
	 * @param string The course code
	 * @return string The database name
	 */
	public static function get_name_database_course($course_code) {
		return Database::result(Database::query('SELECT db_name FROM '.Database::get_main_table(TABLE_MAIN_COURSE).
				' WHERE code="'.Database::escape_string($course_code).'"', __FILE__, __LINE__), 0, 'db_name');
	}

	/**
	 * Lists details of the course description
	 * @param array		The course description
	 * @param string	The encoding
	 * @param bool		If true is displayed if false is hidden
	 * @return string 	The course description in html
	 */
	public static function get_details_course_description_html($descriptions, $charset, $action_show = true) {
		if (isset($descriptions) && count($descriptions) > 0) {
			$data = '';
			foreach ($descriptions as $id => $description) {
				$data .= '<div class="sectiontitle">';
				if (api_is_allowed_to_edit() && $action_show == true) {
					//delete
					$data .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;action=delete&amp;description_id='.$description->id.'" onclick="javascript:if(!confirm(\''.addslashes(api_htmlentities(get_lang('ConfirmYourChoice'), ENT_QUOTES, $charset)).'\')) return false;">';
					$data .= Display::return_icon('delete.gif', get_lang('Delete'), array('style' => 'vertical-align:middle;float:right;'));
					$data .= '</a> ';
					//edit
					$data .= '<a href="'.api_get_self().'?'.api_get_cidreq().'&amp;description_id='.$description->id.'">';
					$data .= Display::return_icon('edit.gif', get_lang('Edit'), array('style' => 'vertical-align:middle;float:right; padding-right:4px;'));
					$data .= '</a> ';
				}
				$data .= $description->title;
				$data .= '</div>';
				$data .= '<div class="sectioncomment">';
				$data .= text_filter($description->content);
				$data .= '</div>';
			}
		} else {
			$data .= '<em>'.get_lang('ThisCourseDescriptionIsEmpty').'</em>';
		}

		return $data;
	}

	/*
	==============================================================================
		DEPRECATED METHODS
	==============================================================================
	*/

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
	public static function get_real_course_code_select_html($element_name, $has_size = true, $only_current_user_courses = true, $user_id) {
		if ($only_current_user_courses) {
			$real_course_list = self::get_real_course_list_of_user_as_course_admin($user_id);
		} else {
			$real_course_list = self::get_real_course_list();
		}

		if ($has_size) {
			$size_element = "size=\"".SELECT_BOX_SIZE."\"";
		} else {
			$size_element = "";
		}
		$html_code = "<select name=\"$element_name\" $size_element >\n";
		foreach ($real_course_list as $real_course) {
			$course_code = $real_course["code"];
			$html_code .= "<option value=\"".$course_code."\">";
			$html_code .= $course_code;
			$html_code .= "</option>\n";
		}
		$html_code .= "</select>\n";

		return $html_code;
	}

} //end class CourseManager
