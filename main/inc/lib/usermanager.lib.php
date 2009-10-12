<?php // $Id: usermanager.lib.php 22378 2009-07-26 19:58:38Z yannoo $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	This library provides functions for user management.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
==============================================================================
*/

// Constants for user extra field types.
define('USER_FIELD_TYPE_TEXT', 1);
define('USER_FIELD_TYPE_TEXTAREA', 2);
define('USER_FIELD_TYPE_RADIO',3);
define('USER_FIELD_TYPE_SELECT',4);
define('USER_FIELD_TYPE_SELECT_MULTIPLE', 5);
define('USER_FIELD_TYPE_DATE', 6);
define('USER_FIELD_TYPE_DATETIME', 7);
define('USER_FIELD_TYPE_DOUBLE_SELECT', 8);
define('USER_FIELD_TYPE_DIVIDER', 9);

class UserManager {

	private function __construct () {

	}

	/**
	  * Creates a new user for the platform
	  * @author Hugues Peeters <peeters@ipm.ucl.ac.be>,
	  * 		Roan Embrechts <roan_embrechts@yahoo.com>
	  *
	  * @param	string	Firstname
	  * @param	string	Lastname
	  * @param	int   	Status (1 for course tutor, 5 for student, 6 for anonymous)
	  * @param	string	e-mail address
	  * @param	string	Login
	  * @param	string	Password
	  * @param	string	Any official code (optional)
	  * @param	string	User language	(optional)
	  * @param	string	Phone number	(optional)
	  * @param	string	Picture URI		(optional)
	  * @param	string	Authentication source	(optional, defaults to 'platform', dependind on constant)
	  * @param	string	Account expiration date (optional, defaults to '0000-00-00 00:00:00')
	  * @param	int		Whether the account is enabled or disabled by default
 	  * @param	int		The user ID of the person who registered this user (optional, defaults to null)
 	  * @param	int		The department of HR in which the user is registered (optional, defaults to 0)
	  * @return mixed   new user id - if the new user creation succeeds, false otherwise
	  *
	  * @desc The function tries to retrieve $_user['user_id'] from the global space.
	  * if it exists, $_user['user_id'] is the creator id. If a problem arises,
	  * it stores the error message in global $api_failureList
	  */
	public static function create_user($firstName, $lastName, $status, $email, $loginName, $password, $official_code = '', $language = '', $phone = '', $picture_uri = '', $auth_source = PLATFORM_AUTH_SOURCE, $expiration_date = '0000-00-00 00:00:00', $active = 1, $hr_dept_id = 0, $extra = null) {
		global $_user, $userPasswordCrypted;

		$firstName = Security::remove_XSS($firstName);
		$lastName = Security::remove_XSS($lastName);
		$loginName = Security::remove_XSS($loginName);
		$phone = Security::remove_XSS($phone);
		// database table definition
		$table_user = Database::get_main_table(TABLE_MAIN_USER);

		// default langauge
		if ($language == '') {
			$language = api_get_setting('platformLanguage');
		}

		if ($_user['user_id']) {
			$creator_id = $_user['user_id'];
		} else {
			$creator_id = '';
		}
		// First check wether the login already exists
		if (!self::is_username_available($loginName)) {
			return api_set_failure('login-pass already taken');
		}
		//$password = "PLACEHOLDER";
		$password = api_get_encrypted_password($password);
		//$password = ($userPasswordCrypted ? md5($password) : $password);
		$current_date = date('Y-m-d H:i:s', time());
		$sql = "INSERT INTO $table_user
				SET lastname = '".Database::escape_string(trim($lastName))."',
				firstname = '".Database::escape_string(trim($firstName))."',
				username = '".Database::escape_string(trim($loginName))."',
				status = '".Database::escape_string($status)."',
				password = '".Database::escape_string($password)."',
				email = '".Database::escape_string($email)."',
				official_code	= '".Database::escape_string($official_code)."',
				picture_uri 	= '".Database::escape_string($picture_uri)."',
				creator_id  	= '".Database::escape_string($creator_id)."',
				auth_source = '".Database::escape_string($auth_source)."',
				phone = '".Database::escape_string($phone)."',
				language = '".Database::escape_string($language)."',
				registration_date = '".$current_date."',
				expiration_date = '".Database::escape_string($expiration_date)."',
				hr_dept_id = '".Database::escape_string($hr_dept_id)."',
				active = '".Database::escape_string($active)."'";
		$result = Database::query($sql, __FILE__, __LINE__);
		if ($result) {
			//echo "id returned";
			$return = Database::get_last_insert_id();
			global $_configuration;
			require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
			if ($_configuration['multiple_access_urls'] == true) {
				if (api_get_current_access_url_id() != -1) {
					UrlManager::add_user_to_url($return, api_get_current_access_url_id());
				} else {
					UrlManager::add_user_to_url($return, 1);
				}
			} else {
				//we are adding by default the access_url_user table with access_url_id = 1
				UrlManager::add_user_to_url($return, 1);
			}

			// add event to system log
			$time = time();
			$user_id_manager = api_get_user_id();
			event_system(LOG_USER_CREATE, LOG_USER_ID, $return, $time, $user_id_manager);

		} else {
			//echo "false - failed" ;
			$return=false;
		}

		if (is_array($extra) && count($extra) > 0) {
			$res = true;
			foreach($extra as $fname => $fvalue) {
				$res = $res && self::update_extra_field($return, $fname, $fvalue);
			}
		}
		return $return;
	}

	/**
	 * Can user be deleted?
	 * This functions checks if there's a course in which the given user is the
	 * only course administrator. If that is the case, the user can't be
	 * deleted because the course would remain without a course admin.
	 * @param int $user_id The user id
	 * @return boolean true if user can be deleted
	 */
	public static function can_delete_user($user_id) {
		$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$sql = "SELECT * FROM $table_course_user WHERE status = '1' AND user_id = '".$user_id."'";
		$res = Database::query($sql, __FILE__, __LINE__);
		while ($course = Database::fetch_object($res)) {
			$sql = "SELECT user_id FROM $table_course_user WHERE status='1' AND course_code ='".$course->course_code."'";
			$res2 = Database::query($sql, __FILE__, __LINE__);
			if (Database::num_rows($res2) == 1) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Delete a user from the platform
	 * @param int $user_id The user id
	 * @return boolean true if user is succesfully deleted, false otherwise
	 */
	public static function delete_user($user_id) {
		global $_configuration;
		if (!self::can_delete_user($user_id)) {
			return false;
		}
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$table_class_user = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
		$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
		$table_admin = Database :: get_main_table(TABLE_MAIN_ADMIN);
		$table_session_user = Database :: get_main_table(TABLE_MAIN_SESSION_USER);
		$table_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

		// Unsubscribe the user from all groups in all his courses
		$sql = "SELECT * FROM $table_course c, $table_course_user cu WHERE cu.user_id = '".$user_id."' AND c.code = cu.course_code";
		$res = Database::query($sql, __FILE__, __LINE__);
		while ($course = Database::fetch_object($res)) {
			$table_group = Database :: get_course_table(TABLE_GROUP_USER, $course->db_name);
			$sql = "DELETE FROM $table_group WHERE user_id = '".$user_id."'";
			Database::query($sql, __FILE__, __LINE__);
		}

		// Unsubscribe user from all classes
		$sql = "DELETE FROM $table_class_user WHERE user_id = '".$user_id."'";
		Database::query($sql, __FILE__, __LINE__);

		// Unsubscribe user from all courses
		$sql = "DELETE FROM $table_course_user WHERE user_id = '".$user_id."'";
		Database::query($sql, __FILE__, __LINE__);

		// Unsubscribe user from all courses in sessions
		$sql = "DELETE FROM $table_session_course_user WHERE id_user = '".$user_id."'";
		Database::query($sql, __FILE__, __LINE__);

		// Unsubscribe user from all sessions
		$sql = "DELETE FROM $table_session_user WHERE id_user = '".$user_id."'";
		Database::query($sql, __FILE__, __LINE__);

		// Delete user picture
		// TODO: Logic about api_get_setting('split_users_upload_directory') === 'true' , a user has 4 differnt sized photos to be deleted.
		$user_info = api_get_user_info($user_id);
		if (strlen($user_info['picture_uri']) > 0) {
			$img_path = api_get_path(SYS_CODE_PATH).'upload/users/'.$user_id.'/'.$user_info['picture_uri'];
			unlink($img_path);
		}

		// Delete the personal course categories
		$course_cat_table = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
		$sql = "DELETE FROM $course_cat_table WHERE user_id = '".$user_id."'";
		Database::query($sql, __FILE__, __LINE__);

		// Delete user from database
		$sql = "DELETE FROM $table_user WHERE user_id = '".$user_id."'";
		Database::query($sql, __FILE__, __LINE__);

		// Delete user from the admin table
		$sql = "DELETE FROM $table_admin WHERE user_id = '".$user_id."'";
		Database::query($sql, __FILE__, __LINE__);

		// Delete the personal agenda-items from this user
		$agenda_table = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
		$sql = "DELETE FROM $agenda_table WHERE user = '".$user_id."'";
		Database::query($sql, __FILE__, __LINE__);

		$gradebook_results_table = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
		$sql = 'DELETE FROM '.$gradebook_results_table.' WHERE user_id = '.$user_id;
		Database::query($sql, __FILE__, __LINE__);

		$user = Database::fetch_array($res);
		$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		$sqlv = "DELETE FROM $t_ufv WHERE user_id = $user_id";
		$resv = Database::query($sqlv, __FILE__, __LINE__);


		if ($_configuration['multiple_access_urls']) {
			require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';
			$url_id = 1;
			if (api_get_current_access_url_id() != -1) {
				$url_id = api_get_current_access_url_id();
			}
			UrlManager::delete_url_rel_user($user_id, $url_id);
		}

		// add event to system log
		$time = time();
		$user_id_manager = api_get_user_id();
		event_system(LOG_USER_DELETE, LOG_USER_ID, $user_id, $time, $user_id_manager);

		return true;
	}

	/**
	 * Update user information with new openid
	 * @param int $user_id
	 * @param string $openid
	 * @return boolean true if the user information was updated
	 */
	public static function update_openid($user_id, $openid) {
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "UPDATE $table_user SET
				openid='".Database::escape_string($openid)."'";
		$sql .=	" WHERE user_id='$user_id'";
		return Database::query($sql, __FILE__, __LINE__);
	}

	/**
	 * Update user information
	 * @param int $user_id
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $username
	 * @param string $password
	 * @param string $auth_source
	 * @param string $email
	 * @param int $status
	 * @param string $official_code
	 * @param string $phone
	 * @param string $picture_uri
	 * @param int The user ID of the person who registered this user (optional, defaults to null)
	 * @param int The department of HR in which the user is registered (optional, defaults to 0)
	 * @param	array	A series of additional fields to add to this user as extra fields (optional, defaults to null)
	 * @return boolean true if the user information was updated
	 */
	public static function update_user($user_id, $firstname, $lastname, $username, $password = null, $auth_source = null, $email, $status, $official_code, $phone, $picture_uri, $expiration_date, $active, $creator_id = null, $hr_dept_id = 0, $extra = null, $language = 'english') {
		global $userPasswordCrypted;
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "UPDATE $table_user SET
				lastname='".Database::escape_string($lastname)."',
				firstname='".Database::escape_string($firstname)."',
				username='".Database::escape_string($username)."',
				language='".Database::escape_string($language)."',";
		if (!is_null($password)) {
			//$password = $userPasswordCrypted ? md5($password) : $password;
			$password = api_get_encrypted_password($password);
			$sql .= " password='".Database::escape_string($password)."',";
		}
		if (!is_null($auth_source)) {
			$sql .=	" auth_source='".Database::escape_string($auth_source)."',";
		}
		$sql .=	"
				email='".Database::escape_string($email)."',
				status='".Database::escape_string($status)."',
				official_code='".Database::escape_string($official_code)."',
				phone='".Database::escape_string($phone)."',
				picture_uri='".Database::escape_string($picture_uri)."',
				expiration_date='".Database::escape_string($expiration_date)."',
				active='".Database::escape_string($active)."',
				hr_dept_id=".intval($hr_dept_id);
		if (!is_null($creator_id)) {
			$sql .= ", creator_id='".Database::escape_string($creator_id)."'";
		}
		$sql .=	" WHERE user_id='$user_id'";
		$return = Database::query($sql, __FILE__, __LINE__);
		if (is_array($extra) && count($extra) > 0) {
			$res = true;
			foreach($extra as $fname => $fvalue) {
				$res = $res && self::update_extra_field($user_id,$fname,$fvalue);
			}
		}
		return $return;
	}

	/**
	 * Check if a username is available
	 * @param string the wanted username
	 * @return boolean true if the wanted username is available
	 */
	public static function is_username_available($username) {
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT username FROM $table_user WHERE username = '".addslashes($username)."'";
		$res = Database::query($sql, __FILE__, __LINE__);
		return Database::num_rows($res) == 0;
	}

	/**
	 * Creates a username using person's names, i.e. creates jmontoya from Julio Montoya.
	 * @param string $firstname				The first name of the user.
	 * @param string $lastname				The last name of the user.
	 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
	 * @param string $encoding (optional)	The character encoding for the input names. If it is omitted, the platform character set will be used by default.
	 * @return string						Suggests a username that contains only ASCII-letters and digits, without check for uniqueness within the system.
	 * @author Julio Montoya Armas
	 * @author Ivan Tcholakov, 2009 - rework about internationalization.
	 */
	public static function create_username($firstname, $lastname, $language = null, $encoding = null) {
		if (is_null($encoding)) {
			$encoding = api_get_system_encoding();
		}
		if (is_null($language)) {
			$language = api_get_interface_language();
		}
		$firstname = substr(preg_replace(USERNAME_PURIFIER, '', api_transliterate($firstname, '', $encoding)), 0, 1); // The first letter only.
		$lastname = preg_replace(USERNAME_PURIFIER, '', api_transliterate($lastname, '', $encoding));
		$username = api_is_western_name_order(null, $language) ? $firstname.$lastname : $lastname.$firstname;
		if (empty($username)) {
			$username = 'user';
		}
		return strtolower(substr($username, 0, USERNAME_MAX_LENGTH - 3));
	}

	/**
	 * Creates a unique username, using:
	 * 1. the first name and the last name of a user;
	 * 2. an already created username but not checked for uniqueness yet.
	 * @param string $firstname				The first name of a given user. If the second parameter $lastname is NULL, then this
	 * parameter is treated as username which is to be checked for uniqueness and to be modified when it is necessary.
	 * @param string $lastname				The last name of the user.
	 * @param string $language (optional)	The language in which comparison is to be made. If language is omitted, interface language is assumed then.
	 * @param string $encoding (optional)	The character encoding for the input names. If it is omitted, the platform character set will be used by default.
	 * @return string						Returns a username that contains only ASCII-letters and digits, and that is unique within the system.
	 * Note: When the method is called several times with same parameters, its results look like the following sequence: ivan, ivan2, ivan3, ivan4, ...
	 * @author Ivan Tcholakov, 2009
	 */
	public static function create_unique_username($firstname, $lastname = null, $language = null, $encoding = null) {
		if (is_null($lastname)) {
			// In this case the actual input parameter $firstname should contain ASCII-letters and digits only.
			// For making this method tolerant of mistakes, let us transliterate and purify the suggested input username anyway.
			// So, instead of the sentence $username = $firstname; we place the following:
			$username = strtolower(preg_replace(USERNAME_PURIFIER, '', api_transliterate($firstname, '', $encoding)));
		} else {
			$username = self::create_username($firstname, $lastname, $language, $encoding);
		}
		if (!self::is_username_available($username)) {
			$i = 2;
			$temp_username = substr($username, 0, USERNAME_MAX_LENGTH - strlen((string)$i)).$i;
			while (!self::is_username_available($temp_username)) {
				$i++;
				$temp_username = substr($username, 0, USERNAME_MAX_LENGTH - strlen((string)$i)).$i;
			}
			$username = $temp_username;
		}
		return $username;
	}

	/**
	 * Modifies a given username accordingly to the specification for valid characters and length.
	 * @param $username string				The input username.
	 * @param bool $strict (optional)		When this flag is TRUE, the result is guaranteed for full compliance, otherwise compliance may be partial. The default value is FALSE.
	 * @param string $encoding (optional)	The character encoding for the input names. If it is omitted, the platform character set will be used by default.
	 * @return string						The resulting purified username.
	 */
	public function purify_username($username, $strict = false, $encoding = null) {
		if ($strict) {
			// 1. Conversion of unacceptable letters (latinian letters with accents for example) into ASCII letters in order they not to be totally removed.
			// 2. Applying the strict purifier.
			// 3. Length limitation.
			return substr(preg_replace(USERNAME_PURIFIER, '', api_transliterate($username, '', $encoding)), 0, USERNAME_MAX_LENGTH);
		}
		// 1. Applying the shallow purifier.
		// 2. Length limitation.
		return substr(preg_replace(USERNAME_PURIFIER_SHALLOW, '', $username), 0, USERNAME_MAX_LENGTH);
	}

	/**
	 * Checks whether a given username matches to the specification strictly. The empty username is assumed here as invalid.
	 * Mostly this function is to be used in the user interface built-in validation routines for providing feedback while usernames are enterd manually.
	 * @param string $username				The input username.
	 * @param string $encoding (optional)	The character encoding for the input names. If it is omitted, the platform character set will be used by default.
	 * @return bool							Returns TRUE if the username is valid, FALSE otherwise.
	 */
	public function is_username_valid($username, $encoding = null) {
		return !empty($username) && $username == self::purify_username($username, true);
	}

	/**
	 * Checks whether a username is empty. If the username contains whitespace characters, such as spaces, tabulators, newlines, etc.,
	 * it is assumed as empty too. This function is safe for validation unpurified data (during importing).
	 * @param string $username				The given username.
	 * @return bool							Returns TRUE if length of the username exceeds the limit, FALSE otherwise.
	 */
	public static function is_username_empty($username) {
		return (strlen(self::purify_username($username, false)) == 0);
	}

	/**
	 * Checks whether a username is too long or not.
	 * @param string $username				The given username, it should contain only ASCII-letters and digits.
	 * @return bool							Returns TRUE if length of the username exceeds the limit, FALSE otherwise.
	 */
	public static function is_username_too_long($username) {
		return (strlen($username) > USERNAME_MAX_LENGTH);
	}

	/**
	* Get a list of users of which the given conditions match with an = 'cond'
	* @param array $conditions a list of condition (exemple : status=>STUDENT)
	* @param array $order_by a list of fields on which sort
	* @return array An array with all users of the platform.
	* @todo optional course code parameter, optional sorting parameters...
	*/
	public static function get_user_list($conditions = array(), $order_by = array()) {
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$return_array = array();
		$sql_query = "SELECT * FROM $user_table";
		if (count($conditions) > 0) {
			$sql_query .= ' WHERE ';
			foreach ($conditions as $field => $value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
				$sql_query .= $field.' = '.$value;
			}
		}
		if (count($order_by) > 0) {
			$sql_query .= ' ORDER BY '.Database::escape_string(implode(',', $order_by));
		}
		$sql_result = Database::query($sql_query, __FILE__, __LINE__);
		while ($result = Database::fetch_array($sql_result)) {
			$return_array[] = $result;
		}
		return $return_array;
	}

    /**
    * Get a list of users of which the given conditions match with a LIKE '%cond%'
    * @param array $conditions a list of condition (exemple : status=>STUDENT)
    * @param array $order_by a list of fields on which sort
    * @return array An array with all users of the platform.
    * @todo optional course code parameter, optional sorting parameters...
    */
    function get_user_list_like($conditions = array(), $order_by = array()) {
        $user_table = Database :: get_main_table(TABLE_MAIN_USER);
        $return_array = array();
        $sql_query = "SELECT * FROM $user_table";
        if (count($conditions) > 0) {
            $sql_query .= ' WHERE ';
            foreach ($conditions as $field => $value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                $sql_query .= $field.' LIKE \'%'.$value.'%\'';
            }
        }
        if (count($order_by) > 0) {
            $sql_query .= ' ORDER BY '.Database::escape_string(implode(',', $order_by));
        }
        $sql_result = Database::query($sql_query, __FILE__, __LINE__);
        while ($result = Database::fetch_array($sql_result)) {
            $return_array[] = $result;
        }
        return $return_array;
    }

	/**
	 * Get user information
	 * @param 	string 	The username
	 * @return array All user information as an associative array
	 */
	public static function get_user_info($username) {
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT * FROM $user_table WHERE username='".$username."'";
		$res = Database::query($sql, __FILE__, __LINE__);
		if (Database::num_rows($res) > 0) {
			return Database::fetch_array($res);
		}
		return false;
	}

	/**
	 * Get user information
	 * @param	string	The id
	 * @param	boolean	Whether to return the user's extra fields (defaults to false)
	 * @return	array 	All user information as an associative array
	 */
	public static function get_user_info_by_id($user_id, $user_fields = false) {
		$user_id = intval($user_id);
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT * FROM $user_table WHERE user_id=".$user_id;
		$res = Database::query($sql, __FILE__, __LINE__);
		if (Database::num_rows($res) > 0) {
			$user = Database::fetch_array($res);
			$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
			$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
			$sqlf = "SELECT * FROM $t_uf ORDER BY field_order";
			$resf = Database::query($sqlf, __FILE__, __LINE__);
			if (Database::num_rows($resf) > 0) {
				while ($rowf = Database::fetch_array($resf)) {
					$sqlv = "SELECT * FROM $t_ufv WHERE field_id = ".$rowf['id']." AND user_id = ".$user['user_id']." ORDER BY id DESC";
					$resv = Database::query($sqlv, __FILE__, __LINE__);
					if (Database::num_rows($resv) > 0) {
						//There should be only one value for a field and a user
						$rowv = Database::fetch_array($resv);
						$user['extra'][$rowf['field_variable']] = $rowv['field_value'];
					} else {
						$user['extra'][$rowf['field_variable']] = '';
					}
				}
			}
			return $user;
		}
		return false;
	}

	/** Get the teacher list
	 * @param int the course ID
	 * @param array Content the list ID of user_id selected
	 */
	//for survey
	// TODO: Ivan, 14-SEP-2009: It seems that this method is not used at all (it can be located in a test unit only. To be deprecated?
	public static function get_teacher_list($course_id, $sel_teacher = '') {
		$user_course_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$sql_query = "SELECT * FROM $user_table a, $user_course_table b where a.user_id=b.user_id AND b.status=1 AND b.course_code='$course_id'";
		$sql_result = Database::query($sql_query, __FILE__, __LINE__);
		echo "<select name=\"author\">";
		while ($result = Database::fetch_array($sql_result)) {
			if ($sel_teacher == $result['user_id']) $selected ="selected";
			echo "\n<option value=\"".$result['user_id']."\" $selected>".$result['firstname']."</option>";
		}
		echo "</select>";
	}

	/**
	 * Get user picture URL or path from user ID (returns an array).
	 * The return format is a complete path, enabling recovery of the directory
	 * with dirname() or the file with basename(). This also works for the
	 * functions dealing with the user's productions, as they are located in
	 * the same directory.
	 * @param	integer	User ID
	 * @param	string	Type of path to return (can be 'none', 'system', 'rel', 'web')
	 * @param	bool	Whether we want to have the directory name returned 'as if' there was a file or not (in the case we want to know which directory to create - otherwise no file means no split subdir)
	 * @param	bool	If we want that the function returns the /main/img/unknown.jpg image set it at true
	 * @return	array 	Array of 2 elements: 'dir' and 'file' which contain the dir and file as the name implies if image does not exist it will return the unknow image if anonymous parameter is true if not it returns an empty er's
	 */
	public static function get_user_picture_path_by_id($id, $type = 'none', $preview = false, $anonymous = false) {

		switch ($type) {
			case 'system': // Base: absolute system path.
				$base = api_get_path(SYS_CODE_PATH);
				break;
			case 'rel': // Base: semi-absolute web path (no server base).
				$base = api_get_path(REL_CODE_PATH);
				break;
			case 'web': // Base: absolute web path.
				$base = api_get_path(WEB_CODE_PATH);
				break;
			case 'none':
			default: // Base: empty, the result path below will be relative.
				$base = '';
		}

		if (empty($id) || empty($type)) {
			return $anonymous ? array('dir' => $base.'img/', 'file' => 'unknown.jpg') : array('dir' => '', 'file' => '');
		}

		$user_id = intval($id);

		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT picture_uri FROM $user_table WHERE user_id=".$user_id;
		$res = Database::query($sql, __FILE__, __LINE__);

		if (!Database::num_rows($res)) {
			return $anonymous ? array('dir' => $base.'img/', 'file' => 'unknown.jpg') : array('dir' => '', 'file' => '');
		}

		$user = Database::fetch_array($res);
		$picture_filename = trim($user['picture_uri']);

		if (api_get_setting('split_users_upload_directory') === 'true') {
			if (!empty($picture_filename)) {
				$dir = $base.'upload/users/'.substr($picture_filename, 0, 1).'/'.$user_id.'/';
			} elseif ($preview) {
				$dir = $base.'upload/users/'.substr((string)$user_id, 0, 1).'/'.$user_id.'/';
			} else {
				$dir = $base.'upload/users/'.$user_id.'/';
			}
		} else {
			$dir = $base.'upload/users/'.$user_id.'/';
		}

		if (empty($picture_filename) && $anonymous) {
			return array('dir' => $base.'img/', 'file' => 'unknown.jpg');
		}

		return array('dir' => $dir, 'file' => $picture_filename);
	}

	/**
	 * Creates new user pfotos in various sizes of a user, or deletes user pfotos.
	 * Note: This method relies on configuration setting from dokeos/main/inc/conf/profile.conf.php
	 * @param int $user_id			The user internal identitfication number.
	 * @param string $file			The common file name for the newly created pfotos. It will be checked and modified for compatibility with the file system.
	 * If full name is provided, path component is ignored.
	 * If an empty name is provided, then old user photos are deleted only, @see UserManager::delete_user_picture() as the prefered way for deletion.
	 * @param string $source_file	The full system name of the image from which user photos will be created.
	 * @return string/bool			Returns the resulting common file name of created images which usually should be stored in database.
	 * When deletion is recuested returns empty string. In case of internal error or negative validation returns FALSE.
	 */
	public static function update_user_picture($user_id, $file = null, $source_file = null) {

		// Validation 1.
		if (empty($user_id)) {
			return false;
		}
		$delete = empty($file);
		if (empty($source_file)) {
			$source_file = $file;
		}

		// Configuration options about user photos.
		require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';

		// User-reserved directory where photos have to be placed.
		$path_info = self::get_user_picture_path_by_id($user_id, 'system', true);
		$path = $path_info['dir'];
		// If this directory does not exist - we create it.
		if (!file_exists($path)) {
			$perm = api_get_setting('permissions_for_new_directories');
			$perm = octdec(!empty($perm) ? $perm : '0770');
			@mkdir($path, $perm, true);
		}

		// The old photos (if any).
		$old_file = $path_info['file'];

		// Let us delete them.
		if (!empty($old_file)) {
			if (KEEP_THE_OLD_IMAGE_AFTER_CHANGE) {
				$prefix = 'saved_'.date('Y_m_d_H_i_s').'_'.uniqid('').'_';
				@rename($path.'small_'.$old_file, $path.$prefix.'small_'.$old_file);
				@rename($path.'medium_'.$old_file, $path.$prefix.'medium_'.$old_file);
				@rename($path.'big_'.$old_file, $path.$prefix.'big_'.$old_file);
				@rename($path.$old_file, $path.$prefix.$old_file);
			} else {
				@unlink($path.'small_'.$old_file);
				@unlink($path.'medium_'.$old_file);
				@unlink($path.'big_'.$old_file);
				@unlink($path.$old_file);
			}
		}

		// Exit if only deletion has been requested. Return an empty picture name.
		if ($delete) {
			return '';
		}

		// Validation 2.
		$allowed_types = array('jpg', 'jpeg', 'png', 'gif');
		$file = str_replace('\\', '/', $file);
		$filename = (($pos = strrpos($file, '/')) !== false) ? substr($file, $pos + 1) : $file;
		$extension = strtolower(substr(strrchr($filename, '.'), 1));
		if (!in_array($extension, $allowed_types)) {
			return false;
		}

		// This is the common name for the new photos.
		if (KEEP_THE_NAME_WHEN_CHANGE_IMAGE && !empty($old_file)) {
			$old_extension = strtolower(substr(strrchr($old_file, '.'), 1));
			$filename = in_array($old_extension, $allowed_types) ? substr($old_file, 0, -strlen($old_extension)) : $old_file;
			$filename = (substr($filename, -1) == '.') ? $filename.$extension : $filename.'.'.$extension;
		} else {
			$filename = replace_dangerous_char($filename);
			if (PREFIX_IMAGE_FILENAME_WITH_UID) {
				$filename = uniqid('').'_'.$filename;
			}
			// We always prefix user photos with user ids, so on setting
			// api_get_setting('split_users_upload_directory') === 'true'
			// the correspondent directories to be found successfully.
			$filename = $user_id.'_'.$filename;
		}

		// Storing the new photos in 4 versions with various sizes.

		$picture_info = @getimagesize($source_file);
		$type = $picture_info[2];
		$small = self::resize_picture($source_file, 22);
		$medium = self::resize_picture($source_file, 85);
		$normal = self::resize_picture($source_file, 200);
		$big = new image($source_file); // This is the original picture.

		$ok = false;
		$detected = array(1 => 'GIF', 2 => 'JPG', 3 => 'PNG');
		if (in_array($type, array_keys($detected))) {
			$ok = $small->send_image($detected[$type], $path.'small_'.$filename)
				&& $medium->send_image($detected[$type], $path.'medium_'.$filename)
				&& $normal->send_image($detected[$type], $path.$filename)
				&& $big->send_image($detected[$type], $path.'big_'.$filename);
		}
		return $ok ? $filename : false;
	}

	/**
	 * Deletes user pfotos.
	 * Note: This method relies on configuration setting from dokeos/main/inc/conf/profile.conf.php
	 * @param int $user_id			The user internal identitfication number.
	 * @return string/bool			Returns empty string on success, FALSE on error.
	 */
	public static function delete_user_picture($user_id) {
		return self::update_user_picture($user_id);
	}

/*
-----------------------------------------------------------
	PRODUCTIONS FUNCTIONS
-----------------------------------------------------------
*/

	/**
	 * Returns an XHTML formatted list of productions for a user, or FALSE if he
	 * doesn't have any.
	 *
	 * If there has been a request to remove a production, the function will return
	 * without building the list unless forced to do so by the optional second
	 * parameter. This increases performance by avoiding to read through the
	 * productions on the filesystem before the removal request has been carried
	 * out because they'll have to be re-read afterwards anyway.
	 *
	 * @param	$user_id	User id
	 * @param	$force	Optional parameter to force building after a removal request
	 * @return	A string containing the XHTML code to dipslay the production list, or FALSE
	 */
	public static function build_production_list($user_id, $force = false, $showdelete = false) {
		if (!$force && !empty($_POST['remove_production'])) {
			return true; // postpone reading from the filesystem
		}

		$productions = self::get_user_productions($user_id);

		if (empty($productions)) {
			return false;
		}

		$production_path = self::get_user_picture_path_by_id($user_id, 'web', true);
		$production_dir = $production_path['dir'].$user_id.'/';
		$del_image = api_get_path(WEB_CODE_PATH).'img/delete.gif';
		$del_text = get_lang('Delete');
		$production_list = '';
		if (count($productions) > 0) {
			$production_list = '<ul id="productions">';
			foreach ($productions as $file) {
				$production_list .= '<li><a href="'.$production_dir.urlencode($file).'" target="_blank">'.htmlentities($file).'</a>';
				if ($showdelete) {
					$production_list .= '<input type="image" name="remove_production['.urlencode($file).']" src="'.$del_image.'" alt="'.$del_text.'" title="'.$del_text.' '.htmlentities($file).'" onclick="javascript: return confirmation(\''.htmlentities($file).'\');" /></li>';
				}
			}
			$production_list .= '</ul>';
		}

		return $production_list;
	}

	/**
	 * Returns an array with the user's productions.
	 *
	 * @param	$user_id	User id
	 * @return	An array containing the user's productions
	 */
	public static function get_user_productions($user_id) {
		$production_path = self::get_user_picture_path_by_id($user_id, 'system', true);
		$production_repository = $production_path['dir'].$user_id.'/';
		$productions = array();

		if (is_dir($production_repository)) {
			$handle = opendir($production_repository);

			while ($file = readdir($handle)) {
				if ($file == '.' || $file == '..' || $file == '.htaccess' || is_dir($production_repository.$file)) {
					continue; // skip current/parent directory and .htaccess
				}
				if (preg_match('/('.$user_id.'|[0-9a-f]{13}|saved)_.+\.(png|jpg|jpeg|gif)$/i', $file)) {
					// User's photos should not be listed as productions.
					continue;
				}
				$productions[] = $file;
			}
		}

		return $productions; // can be an empty array
	}

	/**
	 * Remove a user production.
	 *
	 * @param	$user_id		User id
	 * @param	$production	The production to remove
	 */
	public static function remove_user_production($user_id, $production) {
		$production_path = self::get_user_picture_path_by_id($user_id, 'system', true);
		unlink($production_path['dir'].$user_id.'/'.$production);
	}

	/**
	 * Update an extra field. This function is called when a user changes his/her profile
	 * and by consequence fills or edits his/her extra fields.
	 *
	 * @param	integer	Field ID
	 * @param	array	Database columns and their new value
	 * @return	boolean	true if field updated, false otherwise
	 */
	public static function update_extra_field($fid, $columns)  {
		//TODO check that values added are values proposed for enumerated field types
		$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$fid = Database::escape_string($fid);
		$sqluf = "UPDATE $t_uf SET ";
		$known_fields = array('id', 'field_variable', 'field_type', 'field_display_text', 'field_default_value', 'field_order', 'field_visible', 'field_changeable', 'field_filter');
		$safecolumns = array();
		foreach ($columns as $index => $newval) {
			if (in_array($index, $known_fields)) {
				$safecolumns[$index] = Database::escape_string($newval);
				$sqluf .= $index." = '".$safecolumns[$index]."', ";
			}
		}
		$time = time();
		$sqluf .= " tms = FROM_UNIXTIME($time) WHERE id='$fid'";
		$resuf = Database::query($sqluf, __FILE__, __LINE__);
		return $resuf;
	}

	/**
	 * Update an extra field value for a given user
	 * @param	integer	User ID
	 * @param	string	Field variable name
	 * @param	string	Field value
	 * @return	boolean	true if field updated, false otherwise
	 */
	public static function update_extra_field_value($user_id, $fname, $fvalue = '') {
		//TODO check that values added are values proposed for enumerated field types
		$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$t_ufo = Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
		$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		$fname = Database::escape_string($fname);
		$fvalues = '';
		if (is_array($fvalue)) {
			foreach($fvalue as $val) {
				$fvalues .= Database::escape_string($val).';';
			}
			if (!empty($fvalues)) {
				$fvalues = substr($fvalues, 0, -1);
			}
		} else {
			$fvalues = Database::escape_string($fvalue);
		}
		$sqluf = "SELECT * FROM $t_uf WHERE field_variable='$fname'";
		$resuf = Database::query($sqluf, __FILE__, __LINE__);
		if (Database::num_rows($resuf) == 1) {
			//ok, the field exists
			// Check if enumerated field, if the option is available
			$rowuf = Database::fetch_array($resuf);
			switch ($rowuf['field_type']) {
				case 3:
				case 4:
				case 5:
					$sqluo = "SELECT * FROM $t_ufo WHERE field_id = ".$rowuf['id'];
					$resuo = Database::query($sqluo, __FILE__, __LINE__);
					$values = split(';',$fvalues);
					if (Database::num_rows($resuo) > 0) {
						$check = false;
						while ($rowuo = Database::fetch_array($resuo)) {
							if (in_array($rowuo['option_value'], $values)) {
								$check = true;
								break;
							}
						}
						if ($check == false) {
							return false; //option value not found
						}
					} else {
						return false; //enumerated type but no option found
					}
					break;
				case 1:
				case 2:
				default:
					break;
			}
			$tms = time();
			$sqlufv = "SELECT * FROM $t_ufv WHERE user_id = $user_id AND field_id = ".$rowuf['id']." ORDER BY id";
			$resufv = Database::query($sqlufv, __FILE__, __LINE__);
			$n = Database::num_rows($resufv);
			if ($n > 1) {
				//problem, we already have to values for this field and user combination - keep last one
				while ($rowufv = Database::fetch_array($resufv)) {
					if ($n > 1) {
						$sqld = "DELETE FROM $t_ufv WHERE id = ".$rowufv['id'];
						$resd = Database::query($sqld, __FILE__, __LINE__);
						$n--;
					}
					$rowufv = Database::fetch_array($resufv);
					if ($rowufv['field_value'] != $fvalues) {
						$sqlu = "UPDATE $t_ufv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = ".$rowufv['id'];
						$resu = Database::query($sqlu, __FILE__, __LINE__);
						return($resu ? true : false);
					}
					return true;
				}
			}
			elseif ($n == 1) {
				//we need to update the current record
				$rowufv = Database::fetch_array($resufv);
				if ($rowufv['field_value'] != $fvalues) {
					$sqlu = "UPDATE $t_ufv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = ".$rowufv['id'];
					//error_log('UM::update_extra_field_value: '.$sqlu);
					$resu = Database::query($sqlu, __FILE__, __LINE__);
					return($resu ? true : false);
				}
				return true;
			} else {
				$sqli = "INSERT INTO $t_ufv (user_id,field_id,field_value,tms) " .
					"VALUES ($user_id,".$rowuf['id'].",'$fvalues',FROM_UNIXTIME($tms))";
				//error_log('UM::update_extra_field_value: '.$sqli);
				$resi = Database::query($sqli, __FILE__, __LINE__);
				return($resi ? true : false);
			}
		} else {
			return false; //field not found
		}
	}

	/**
	 * Get an array of extra fieds with field details (type, default value and options)
	 * @param	integer	Offset (from which row)
	 * @param	integer	Number of items
	 * @param	integer	Column on which sorting is made
	 * @param	string	Sorting direction
	 * @param	boolean	Optional. Whether we get all the fields or just the visible ones
	 * @return	array	Extra fields details (e.g. $list[2]['type'], $list[4]['options'][2]['title']
	 */
	public static function get_extra_fields($from = 0, $number_of_items = 0, $column = 5, $direction = 'ASC', $all_visibility = true) {
		$fields = array();
		$t_uf = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
		$t_ufo = Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
		$columns = array('id', 'field_variable', 'field_type', 'field_display_text', 'field_default_value', 'field_order', 'field_filter', 'tms');

		$sort_direction = '';
		if (in_array(strtoupper($direction), array('ASC', 'DESC'))) {
			$sort_direction = strtoupper($direction);
		}
		$sqlf = "SELECT * FROM $t_uf ";
		if ($all_visibility == false) {
			$sqlf .= " WHERE field_visible = 1 ";
		}
		$sqlf .= " ORDER BY ".$columns[$column]." $sort_direction " ;
		if ($number_of_items != 0) {
			$sqlf .= " LIMIT ".Database::escape_string($from).','.Database::escape_string($number_of_items);
		}

		$resf = Database::query($sqlf, __FILE__, __LINE__);
		if (Database::num_rows($resf) > 0) {
			while($rowf = Database::fetch_array($resf)) {
				$fields[$rowf['id']] = array(
					0 => $rowf['id'],
					1 => $rowf['field_variable'],
					2 => $rowf['field_type'],
					//3 => (empty($rowf['field_display_text']) ? '' : get_lang($rowf['field_display_text'], '')),
					// Temporarily removed auto-translation. Need update to get_lang() to know if translation exists (todo)
					// Ivan, 15-SEP-2009: get_lang() has been modified accordingly in order this issue to be solved.
					3 => (empty($rowf['field_display_text']) ? '' : $rowf['field_display_text']),
					4 => $rowf['field_default_value'],
					5 => $rowf['field_order'],
					6 => $rowf['field_visible'],
					7 => $rowf['field_changeable'],
					8 => $rowf['field_filter'],
					9 => array()
				);

				$sqlo = "SELECT * FROM $t_ufo WHERE field_id = ".$rowf['id']." ORDER BY option_order ASC";
				$reso = Database::query($sqlo, __FILE__, __LINE__);
				if (Database::num_rows($reso) > 0) {
					while ($rowo = Database::fetch_array($reso)) {
						$fields[$rowf['id']][9][$rowo['id']] = array(
							0 => $rowo['id'],
							1 => $rowo['option_value'],
							//2 => (empty($rowo['option_display_text']) ? '' : get_lang($rowo['option_display_text'], '')),
							2 => (empty($rowo['option_display_text']) ? '' : $rowo['option_display_text']),
							3 => $rowo['option_order']
						);
					}
				}
			}
		}

		return $fields;
	}

	/**
	 * Get the list of options attached to an extra field
	 * @param string $fieldname the name of the field
	 * @return array the list of options
	 */
	public static function get_extra_field_options($field_name) {
		$t_uf = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
		$t_ufo = Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);

		$sql = 'SELECT options.*
				FROM '.$t_ufo.' options
					INNER JOIN '.$t_uf.' fields
						ON fields.id = options.field_id
							AND fields.field_variable="'.Database::escape_string($field_name).'"';
		$rs = Database::query($sql, __FILE__, __LINE__);
		return Database::store_result($rs);
	}

	/**
	 * Get the number of extra fields currently recorded
	 * @param	boolean	Optional switch. true (default) returns all fields, false returns only visible fields
	 * @return	integer	Number of fields
	 */
	public static function get_number_of_extra_fields($all_visibility = true) {
		$t_uf = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
		$sqlf = "SELECT * FROM $t_uf ";
		if ($all_visibility == false) {
			$sqlf .= " WHERE field_visible = 1 ";
		}
		$sqlf .= " ORDER BY field_order";
		$resf = Database::query($sqlf, __FILE__, __LINE__);
		return Database::num_rows($resf);
	}

	/**
	  * Creates a new extra field
	  * @param	string	Field's internal variable name
	  * @param	int		Field's type
	  * @param	string	Field's language var name
	  * @param	string	Field's default value
	  * @param	string	Optional comma-separated list of options to provide for select and radio
	  * @return int     new user id - if the new user creation succeeds, false otherwise
	  */
	public static function create_extra_field($fieldvarname, $fieldtype, $fieldtitle, $fielddefault, $fieldoptions = '') {
		// database table definition
		$table_field 		= Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$table_field_options= Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);

		// First check wether the login already exists
		if (self::is_extra_field_available($fieldvarname)) {
			return api_set_failure('login-pass already taken');
		}
		$sql = "SELECT MAX(field_order) FROM $table_field";
		$res = Database::query($sql, __FILE__, __LINE__);
		$order = 0;
		if (Database::num_rows($res) > 0) {
			$row = Database::fetch_array($res);
			$order = $row[0]+1;
		}
		$time = time();
		$sql = "INSERT INTO $table_field
				SET field_type = '".Database::escape_string($fieldtype)."',
				field_variable = '".Database::escape_string($fieldvarname)."',
				field_display_text = '".Database::escape_string($fieldtitle)."',
				field_default_value = '".Database::escape_string($fielddefault)."',
				field_order = '$order',
				tms = FROM_UNIXTIME($time)";
		$result = Database::query($sql);
		if ($result) {
			//echo "id returned";
			$return = Database::get_last_insert_id();
		} else {
			//echo "false - failed" ;
			return false;
		}

		if (!empty($fieldoptions) && in_array($fieldtype, array(USER_FIELD_TYPE_RADIO, USER_FIELD_TYPE_SELECT, USER_FIELD_TYPE_SELECT_MULTIPLE, USER_FIELD_TYPE_DOUBLE_SELECT))) {
			if ($fieldtype == USER_FIELD_TYPE_DOUBLE_SELECT) {
				$twolist = explode('|', $fieldoptions);
				$counter = 0;
				foreach ($twolist as $individual_list) {
					$splitted_individual_list = split(';', $individual_list);
					foreach	($splitted_individual_list as $individual_list_option) {
						//echo 'counter:'.$counter;
						if ($counter == 0) {
							$list[] = $individual_list_option;
						} else {
							$list[] = str_repeat('*', $counter).$individual_list_option;
						}
					}
					$counter++;
				}
			} else {
				$list = split(';', $fieldoptions);
			}
			foreach ($list as $option) {
				$option = Database::escape_string($option);
				$sql = "SELECT * FROM $table_field_options WHERE field_id = $return AND option_value = '".$option."'";
				$res = Database::query($sql, __FILE__, __LINE__);
				if (Database::num_rows($res) > 0) {
					//the option already exists, do nothing
				} else {
					$sql = "SELECT MAX(option_order) FROM $table_field_options WHERE field_id = $return";
					$res = Database::query($sql, __FILE__, __LINE__);
					$max = 1;
					if (Database::num_rows($res) > 0) {
						$row = Database::fetch_array($res);
						$max = $row[0] + 1;
					}
					$time = time();
					$sql = "INSERT INTO $table_field_options (field_id,option_value,option_display_text,option_order,tms) VALUES ($return,'$option','$option',$max,FROM_UNIXTIME($time))";
					$res = Database::query($sql, __FILE__, __LINE__);
					if ($res === false) {
						$return = false;
					}
				}
			}
		}
		return $return;
	}

	/**
	  * Save the changes in the definition of the extra user profile field
	  * The function is called after you (as admin) decide to store the changes you have made to one of the fields you defined
	  *
	  * There is quite some logic in this field
	  * 1.  store the changes to the field (tupe, name, label, default text)
	  * 2.  remove the options and the choices of the users from the database that no longer occur in the form field 'possible values'. We should only remove
	  * 	the options (and choices) that do no longer have to appear. We cannot remove all options and choices because if you remove them all
	  * 	and simply re-add them all then all the user who have already filled this form will loose their selected value.
	  * 3.	we add the options that are newly added
	  *
	  * @example 	current options are a;b;c and the user changes this to a;b;x (removing c and adding x)
	  * 			we first remove c (and also the entry in the option_value table for the users who have chosen this)
	  * 			we then add x
	  * 			a and b are neither removed nor added
	  *
	  * @param 	integer $fieldid		the id of the field we are editing
	  * @param	string	$fieldvarname	the internal variable name of the field
	  * @param	int		$fieldtype		the type of the field
	  * @param	string	$fieldtitle		the title of the field
	  * @param	string	$fielddefault	the default value of the field
	  * @param	string	$fieldoptions	Optional comma-separated list of options to provide for select and radio
	  * @return boolean true
	  *
	  *
	  * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
	  * @version July 2008
	  * @since Dokeos 1.8.6
	  */
	public static function save_extra_field_changes($fieldid, $fieldvarname, $fieldtype, $fieldtitle, $fielddefault, $fieldoptions = '') {
		// database table definition
		$table_field 				= Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$table_field_options		= Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
		$table_field_options_values = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);

		// we first update the field definition with the new values
		$time = time();
		$sql = "UPDATE $table_field
				SET field_type = '".Database::escape_string($fieldtype)."',
				field_variable = '".Database::escape_string($fieldvarname)."',
				field_display_text = '".Database::escape_string($fieldtitle)."',
				field_default_value = '".Database::escape_string($fielddefault)."',
				tms = FROM_UNIXTIME($time)
			WHERE id = '".Database::escape_string($fieldid)."'";
		$result = Database::query($sql, __FILE__, __LINE__);

		// we create an array with all the options (will be used later in the script)
		if ($fieldtype == USER_FIELD_TYPE_DOUBLE_SELECT) {
			$twolist = explode('|', $fieldoptions);
			$counter = 0;
			foreach ($twolist as $individual_list) {
				$splitted_individual_list = split(';', $individual_list);
				foreach	($splitted_individual_list as $individual_list_option) {
					//echo 'counter:'.$counter;
					if ($counter == 0) {
						$list[] = trim($individual_list_option);
					} else {
						$list[] = str_repeat('*', $counter).trim($individual_list_option);
					}
				}
				$counter++;
			}
		} else {
			$templist = split(';', $fieldoptions);
			$list = array_map('trim', $templist);
		}

		// Remove all the field options (and also the choices of the user) that are NOT in the new list of options
		$sql = "SELECT * FROM $table_field_options WHERE option_value NOT IN ('".implode("','", $list)."') AND field_id = '".Database::escape_string($fieldid)."'";
		$result = Database::query($sql, __FILE__, __LINE__);
		$return['deleted_options'] = 0;
		while ($row = Database::fetch_array($result)) {
			// deleting the option
			$sql_delete_option = "DELETE FROM $table_field_options WHERE id='".Database::escape_string($row['id'])."'";
			$result_delete_option = Database::query($sql_delete_option, __FILE__, __LINE__);
			$return['deleted_options']++;

			// deleting the answer of the user who has chosen this option
			$sql_delete_option_value = "DELETE FROM $table_field_options_values WHERE field_id = '".Database::escape_string($fieldid)."' AND field_value = '".Database::escape_string($row['option_value'])."'";
			$result_delete_option_value = Database::query($sql_delete_option_value, __FILE__, __LINE__);
			$return['deleted_option_values'] = $return['deleted_option_values'] + Database::affected_rows();
		}

		// we now try to find the field options that are newly added
		$sql = "SELECT * FROM $table_field_options WHERE field_id = '".Database::escape_string($fieldid)."'";
		$result = Database::query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result)) {
			// we remove every option that is already in the database from the $list
			if (in_array(trim($row['option_display_text']), $list)) {
				$key = array_search(trim($row['option_display_text']), $list);
				unset($list[$key]);
			}
		}

		// we store the new field options in the database
		foreach ($list as $key => $option) {
			$sql = "SELECT MAX(option_order) FROM $table_field_options WHERE field_id = '".Database::escape_string($fieldid)."'";
			$res = Database::query($sql, __FILE__, __LINE__);
			$max = 1;
			if (Database::num_rows($res) > 0) {
				$row = Database::fetch_array($res);
				$max = $row[0] + 1;
			}
			$time = time();
			$sql = "INSERT INTO $table_field_options (field_id,option_value,option_display_text,option_order,tms) VALUES ('".Database::escape_string($fieldid)."','".Database::escape_string($option)."','".Database::escape_string($option)."',$max,FROM_UNIXTIME($time))";
			$result = Database::query($sql, __FILE__, __LINE__);
		}
		return true;
	}

	/**
	 * Check if a field is available
	 * @param	string	the wanted fieldname
	 * @return	boolean	true if the wanted username is available
	 */
	public static function is_extra_field_available($fieldname) {
		$t_uf = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
		$sql = "SELECT * FROM $t_uf WHERE field_variable = '".Database::escape_string($fieldname)."'";
		$res = Database::query($sql, __FILE__, __LINE__);
		return Database::num_rows($res) > 0;
	}

	/**
	 * Gets user extra fields data
	 * @param	integer	User ID
	 * @param	boolean	Whether to prefix the fields indexes with "extra_" (might be used by formvalidator)
	 * @param	boolean	Whether to return invisible fields as well
	 * @param	boolean	Whether to split multiple-selection fields or not
	 * @return	array	Array of fields => value for the given user
	 */
	public static function get_extra_user_data($user_id, $prefix = false, $all_visibility = true, $splitmultiple = false) {
		// A sanity check.
		if (empty($user_id)) {
			$user_id = 0;
		}
		$extra_data = array();
		$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		$user_id = Database::escape_string($user_id);
		$sql = "SELECT f.id as id, f.field_variable as fvar, f.field_type as type FROM $t_uf f ";
		if ($all_visibility == false) {
			$sql .= " WHERE f.field_visible = 1 ";
		}
		$sql .= " ORDER BY f.field_order";
		$res = Database::query($sql, __FILE__, __LINE__);
		if (Database::num_rows($res) > 0) {
			while ($row = Database::fetch_array($res)) {
				$sqlu = "SELECT field_value as fval " .
						" FROM $t_ufv " .
						" WHERE field_id=".$row['id']."" .
						" AND user_id=".$user_id;
				$resu = Database::query($sqlu, __FILE__, __LINE__);
				$fval = '';
				// get default value
				$sql_df = "SELECT field_default_value as fval_df " .
						" FROM $t_uf " .
						" WHERE id=".$row['id'];
				$res_df = Database::query($sql_df, __FILE__, __LINE__);
				if (Database::num_rows($resu) > 0) {
					$rowu = Database::fetch_array($resu);
					$fval = $rowu['fval'];
					if ($row['type'] ==  USER_FIELD_TYPE_SELECT_MULTIPLE) {
						$fval = split(';',$rowu['fval']);
					}
				} else {
					$row_df = Database::fetch_array($res_df);
					$fval = $row_df['fval_df'];
				}
				if ($prefix) {
					if ($row['type'] ==  USER_FIELD_TYPE_RADIO) {
						$extra_data['extra_'.$row['fvar']]['extra_'.$row['fvar']] = $fval;
					} else {
						$extra_data['extra_'.$row['fvar']] = $fval;
					}
				} else {
					if ($row['type'] ==  USER_FIELD_TYPE_RADIO) {
						$extra_data['extra_'.$row['fvar']]['extra_'.$row['fvar']] = $fval;
					} else {
						$extra_data[$row['fvar']] = $fval;
					}
				}
			}
		}

		return $extra_data;
	}

	/** Get extra user data by field
	 * @param int	user ID
	 * @param string the internal variable name of the field
	 * @return array with extra data info of a user i.e array('field_variable'=>'value');
	 */
	public static function get_extra_user_data_by_field($user_id, $field_variable, $prefix = false, $all_visibility = true, $splitmultiple = false) {
		// A sanity check.
		if (empty($user_id)) {
			$user_id = 0;
		}
		$extra_data = array();
		$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		$user_id = Database::escape_string($user_id);
		$sql = "SELECT f.id as id, f.field_variable as fvar, f.field_type as type FROM $t_uf f ";

		$sql .= " WHERE f.field_variable = '$field_variable' ";

		if ($all_visibility == false) {
			$sql .= " AND f.field_visible = 1 ";
		}

		$sql .= " ORDER BY f.field_order";

		$res = Database::query($sql, __FILE__, __LINE__);
		if (Database::num_rows($res) > 0) {
			while ($row = Database::fetch_array($res)) {
				$sqlu = "SELECT field_value as fval " .
						" FROM $t_ufv " .
						" WHERE field_id=".$row['id']."" .
						" AND user_id=".$user_id;
				$resu = Database::query($sqlu, __FILE__, __LINE__);
				$fval = '';
				if (Database::num_rows($resu) > 0) {
					$rowu = Database::fetch_array($resu);
					$fval = $rowu['fval'];
					if ($row['type'] ==  USER_FIELD_TYPE_SELECT_MULTIPLE) {
						$fval = split(';',$rowu['fval']);
					}
				}
				if ($prefix) {
					$extra_data['extra_'.$row['fvar']] = $fval;
				} else {
					$extra_data[$row['fvar']] = $fval;
				}
			}
		}

		return $extra_data;
	}

	/**
	 * Get the extra field information for a certain field (the options as well)
	 * @param  int     The name of the field we want to know everything about
	 * @return array   Array containing all the information about the extra profile field (first level of array contains field details, then 'options' sub-array contains options details, as returned by the database)
	 * @author Julio Montoya
	 * @since Dokeos 1.8.6
	 */
	public static function get_extra_field_information_by_name($field_variable, $fuzzy = false) {
		// database table definition
		$table_field 			= Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$table_field_options	= Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);

		// all the information of the field
		$sql = "SELECT * FROM $table_field WHERE field_variable='".Database::escape_string($field_variable)."'";
		$result = Database::query($sql, __FILE__, __LINE__);
		$return = Database::fetch_array($result);

		// all the options of the field
		$sql = "SELECT * FROM $table_field_options WHERE field_id='".Database::escape_string($return['id'])."' ORDER BY option_order ASC";
		$result = Database::query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result)) {
			$return['options'][$row['id']] = $row;
		}
		return $return;
	}

	/**
	 * Get all the extra field information of a certain field (also the options)
	 *
	 * @param int $field_name the name of the field we want to know everything of
	 * @return array $return containing all th information about the extra profile field
	 * @author Julio Montoya
	 * @since Dokeos 1.8.6
	 */
	public static function get_extra_field_information($field_id) {
		// database table definition
		$table_field 			= Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$table_field_options	= Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);

		// all the information of the field
		$sql = "SELECT * FROM $table_field WHERE id='".Database::escape_string($field_id)."'";
		$result = Database::query($sql, __FILE__, __LINE__);
		$return = Database::fetch_array($result);

		// all the options of the field
		$sql = "SELECT * FROM $table_field_options WHERE field_id='".Database::escape_string($field_id)."' ORDER BY option_order ASC";
		$result = Database::query($sql, __FILE__, __LINE__);
		while ($row = Database::fetch_array($result)) {
			$return['options'][$row['id']] = $row;
		}
		return $return;
	}

	/** Get extra user data by value
	 * @param string the internal variable name of the field
	 * @param string the internal value of the field
	 * @return array with extra data info of a user i.e array('field_variable'=>'value');
	 */

	public static function get_extra_user_data_by_value($field_variable, $field_value, $all_visibility = true) {
		$extra_data = array();
		$table_user_field = Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$table_user_field_values = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		$table_user_field_options = Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
		$where = '';
		/*
		if (is_array($field_variable_array) && is_array($field_value_array)) {
			if (count($field_variable_array) == count($field_value_array)) {
				$field_var_count = count($field_variable_array);
				for ($i = 0; $i < $field_var_count; $i++) {
					if ($i != 0 && $i != $field_var_count) {
						$where.= ' AND ';
					}
					$where.= "field_variable='".Database::escape_string($field_variable_array[$i])."' AND user_field_options.id='".Database::escape_string($field_value_array[$i])."'";
				}
			}

		}*/
		$where = "field_variable='".Database::escape_string($field_variable)."' AND field_value='".Database::escape_string($field_value)."'";

		$sql = "SELECT user_id FROM $table_user_field user_field INNER JOIN $table_user_field_values user_field_values
					ON (user_field.id = user_field_values.field_id)
				WHERE $where";

		if ($all_visibility == true) {
			$sql .= " AND user_field.field_visible = 1 ";
		} else {
			$sql .= " AND user_field.field_visible = 0 ";
		}
		$res = Database::query($sql, __FILE__, __LINE__);
		$result_data = array();
		if (Database::num_rows($res) > 0) {
			while ($row = Database::fetch_array($res)) {
				$result_data[] = $row['user_id'];
			}
		}
		return $result_data;
	}


	/**
	 * Gives a list of [session_category][session_id] for the current user.
	 * @param integer $user_id
	 * @param boolean whether to fill the first element or not (to give space for courses out of categories)
	 * @param boolean  optional true if limit time from session is over, false otherwise
	 * @return array  list of statuses [session_category][session_id]
	 * @todo ensure multiple access urls are managed correctly
	 */
	public static function get_sessions_by_category ($user_id, $fill_first = false, $is_time_over = false) {
		// Database Table Definitions
		$tbl_session_user			= Database :: get_main_table(TABLE_MAIN_SESSION_USER);
		$tbl_session				= Database :: get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_course			= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session_course_user	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		$user_id = intval($user_id);

		$categories = array();
		if ($fill_first) {
			$categories[0] = array();
		}
		/*
		//we filter the courses from the URL
		$join_access_url=$where_access_url='';
		global $_configuration;
		if ($_configuration['multiple_access_urls']==true) {
			$access_url_id = api_get_current_access_url_id();
			if($access_url_id!=-1) {
				$tbl_url_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
				$join_access_url= "LEFT JOIN $tbl_url_course url_rel_course ON url_rel_course.course_code= course.code";
				$where_access_url=" AND access_url_id = $access_url_id ";
			}
		}
		*/
		// get the list of sessions where the user is subscribed as student

		$condition_date_end = "";
		if ($is_time_over) {
			$condition_date_end = " AND date_end < CURDATE() AND date_end != '0000-00-00' ";
		} else {
			$condition_date_end = " AND (date_end >= CURDATE() OR date_end = '0000-00-00') ";
		}

		$sessions_sql = "SELECT DISTINCT id, session_category_id
								FROM $tbl_session_user, $tbl_session
								WHERE id_session=id AND id_user=$user_id $condition_date_end
								ORDER BY session_category_id, date_start, date_end";

		$result = Database::query($sessions_sql,__FILE__,__LINE__);
		while ($row = Database::fetch_array($result)) {
			$categories[$row['session_category_id']][] = $row['id'];
		}

		// get the list of sessions where the user is subscribed as coach in a course $tbl_session_course_user
		/*$sessions_sql = "SELECT DISTINCT id, session_category_id
								FROM $tbl_session as session
								INNER JOIN $tbl_session_course as session_rel_course
									ON session_rel_course.id_session = session.id
									AND session_rel_course.id_coach = $user_id
								ORDER BY session_category_id, date_start, date_end";*/

		$sessions_sql = "SELECT DISTINCT id, session_category_id
								FROM $tbl_session as session
								INNER JOIN $tbl_session_course_user as session_rel_course_user
									ON session_rel_course_user.id_session = session.id
									AND session_rel_course_user.id_user = $user_id
									AND session_rel_course_user.status = 2	$condition_date_end
								ORDER BY session_category_id, date_start, date_end";

		$result = Database::query($sessions_sql,__FILE__,__LINE__);
		while ($row = Database::fetch_array($result)) {
			$categories[$row['session_category_id']][] = $row['id'];
		}

		// get the list of sessions where the user is subscribed as coach
		$sessions_sql = "SELECT DISTINCT id, session_category_id
								FROM $tbl_session as session
								WHERE session.id_coach = $user_id $condition_date_end
								ORDER BY session_category_id, date_start, date_end";

		$result = Database::query($sessions_sql,__FILE__,__LINE__);
		while ($row = Database::fetch_array($result)) {
			$categories[$row['session_category_id']][] = $row['id'];
		}
		return $categories;
	}

	/**
	 * Gives a list of [session_id-course_code] => [status] for the current user.
	 * @param integer $user_id
	 * @return array  list of statuses (session_id-course_code => status)
	 */
	public static function get_personal_session_course_list($user_id) {
		// Database Table Definitions
		$tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
		$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
		$tbl_session 				= Database :: get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_user			= Database :: get_main_table(TABLE_MAIN_SESSION_USER);
		$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_session_course 		= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

		$user_id = Database::escape_string($user_id);
		//we filter the courses from the URL
		$join_access_url = $where_access_url = '';
		global $_configuration;
		if ($_configuration['multiple_access_urls'] == true) {
			$access_url_id = api_get_current_access_url_id();
			if ($access_url_id != -1) {
				$tbl_url_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
				$join_access_url = "LEFT JOIN $tbl_url_course url_rel_course ON url_rel_course.course_code= course.code";
				$where_access_url = " AND access_url_id = $access_url_id ";
			}
		}

		// variable initialisation
		$personal_course_list_sql = '';
		$personal_course_list = array();

		//Courses in which we suscribed out of any session
		/*$personal_course_list_sql = "SELECT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i,
											course.tutor_name t, course.course_language l, course_rel_user.status s, course_rel_user.sort sort,
											course_rel_user.user_course_cat user_course_cat
											FROM    ".$tbl_course."       course,".$main_course_user_table."   course_rel_user
											WHERE course.code = course_rel_user.course_code"."
											AND   course_rel_user.user_id = '".$user_id."'
											ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC,i";*/

		$tbl_user_course_category = Database :: get_user_personal_table(TABLE_USER_COURSE_CATEGORY);

		$personal_course_list_sql = "SELECT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, course.tutor_name t, course.course_language l, course_rel_user.status s, course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
			FROM ".$tbl_course_user." course_rel_user
				LEFT JOIN ".$tbl_course." course
					ON course.code = course_rel_user.course_code
				LEFT JOIN ".$tbl_user_course_category." user_course_category
					ON course_rel_user.user_course_cat = user_course_category.id
				$join_access_url
			WHERE  course_rel_user.user_id = '".$user_id."'  $where_access_url
			ORDER BY user_course_category.sort,course.title,course_rel_user.sort ASC";

		$course_list_sql_result = Database::query($personal_course_list_sql, __FILE__, __LINE__);
		//var_dump($course_list_sql_result); exit;
		while ($result_row = Database::fetch_array($course_list_sql_result)) {
			$personal_course_list[] = $result_row;
		}

		// get the list of sessions where the user is subscribed as student
		$sessions_sql = "SELECT DISTINCT id, name, date_start, date_end
								FROM $tbl_session_user, $tbl_session
								WHERE id_session=id AND id_user=$user_id
								AND (date_start <= CURDATE() AND date_end >= CURDATE() OR date_start='0000-00-00')
								ORDER BY date_start, date_end, name";
		$result = Database::query($sessions_sql,__FILE__,__LINE__);
		$sessions=Database::store_result($result);
		$sessions = array_merge($sessions , Database::store_result($result));


		// get the list of sessions where the user is subscribed as student where visibility = SESSION_VISIBLE_READ_ONLY = 1  SESSION_VISIBLE = 2
		$sessions_out_date_sql = "SELECT DISTINCT id, name, date_start, date_end
								FROM $tbl_session_user, $tbl_session
								WHERE id_session=id AND id_user=$user_id
								AND (date_end <= CURDATE() AND date_end<>'0000-00-00') AND (visibility = ".SESSION_VISIBLE_READ_ONLY." || visibility = ".SESSION_VISIBLE.")
								ORDER BY date_start, date_end, name";
		$result_out_date = Database::query($sessions_out_date_sql,__FILE__,__LINE__);
		$sessions_out_date=Database::store_result($result_out_date);
		$sessions = array_merge($sessions , $sessions_out_date);



		// get the list of sessions where the user is subscribed as coach in a course
		$sessions_sql = "SELECT DISTINCT id, name, date_start, date_end, DATE_SUB(date_start, INTERVAL nb_days_access_before_beginning DAY), ADDDATE(date_end, INTERVAL nb_days_access_after_end DAY)
			FROM $tbl_session as session
				INNER JOIN $tbl_session_course as session_rel_course
					ON session_rel_course.id_session = session.id
						AND session_rel_course.id_coach = $user_id
			WHERE (CURDATE() >= DATE_SUB(date_start, INTERVAL nb_days_access_before_beginning DAY)
				AND CURDATE() <= ADDDATE(date_end, INTERVAL nb_days_access_after_end DAY)
				OR date_start='0000-00-00')
			ORDER BY date_start, date_end, name";

		$result = Database::query($sessions_sql, __FILE__, __LINE__);

		$session_is_coach = Database::store_result($result);

		$sessions = array_merge($sessions, $session_is_coach);

		// get the list of sessions where the user is subscribed as coach
		$sessions_sql = "SELECT DISTINCT id, name, date_start, date_end
			FROM $tbl_session as session
			WHERE session.id_coach = $user_id
				AND (CURDATE() >= DATE_SUB(date_start, INTERVAL nb_days_access_before_beginning DAY)
				AND CURDATE() <= ADDDATE(date_end, INTERVAL nb_days_access_after_end DAY)
				OR date_start='0000-00-00')
			ORDER BY date_start, date_end, name";
		$result = Database::query($sessions_sql, __FILE__, __LINE__);

		$sessions = array_merge($sessions, Database::store_result($result));

		if (api_is_allowed_to_create_course()) {
			foreach($sessions as $enreg) {
				$id_session = $enreg['id'];
				$personal_course_list_sql = "SELECT DISTINCT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, ".(api_is_western_name_order() ? "CONCAT(user.firstname,' ',user.lastname)" : "CONCAT(user.lastname,' ',user.firstname)")." t, email, course.course_language l, 1 sort, category_code user_course_cat, date_start, date_end, session.id as id_session, session.name as session_name
					FROM $tbl_session_course as session_course
						INNER JOIN $tbl_course AS course
							ON course.code = session_course.course_code
						INNER JOIN $tbl_session as session
							ON session.id = session_course.id_session
						LEFT JOIN $tbl_user as user
							ON user.user_id = session_course.id_coach
					WHERE session_course.id_session = $id_session
						AND (session_course.id_coach=$user_id OR session.id_coach=$user_id)
					ORDER BY i";

				$course_list_sql_result = Database::query($personal_course_list_sql, __FILE__, __LINE__);

				while ($result_row = Database::fetch_array($course_list_sql_result)) {
					$result_row['s'] = 2;
					$key = $result_row['id_session'].' - '.$result_row['k'];
					$personal_course_list[$key] = $result_row;
				}
			}
		}

		foreach ($sessions as $enreg) {
			$id_session = $enreg['id'];
			/*$personal_course_list_sql = "SELECT DISTINCT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, CONCAT(user.lastname,' ',user.firstname) t, email, course.course_language l, 1 sort, category_code user_course_cat, date_start, date_end, session.id as id_session, session.name as session_name, IF(session_course.id_coach = ".$user_id.",'2', '5')
										 FROM $tbl_session_course as session_course
										 INNER JOIN $tbl_course AS course
										 	ON course.code = session_course.course_code
										 LEFT JOIN $tbl_user as user
											ON user.user_id = session_course.id_coach
										 INNER JOIN $tbl_session_course_user
											ON $tbl_session_course_user.id_session = $id_session
											AND $tbl_session_course_user.id_user = $user_id
										INNER JOIN $tbl_session  as session
											ON session_course.id_session = session.id
										 WHERE session_course.id_session = $id_session
										 ORDER BY i";
				*/
			// this query is very similar to the above query, but it will check the session_rel_course_user table if there are courses registered to our user or not
			$personal_course_list_sql = "SELECT distinct course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, CONCAT(user.lastname,' ',user.firstname) t, email, course.course_language l, 1 sort, category_code user_course_cat, date_start, date_end, session.id as id_session, session.name as session_name, IF(session_course.id_coach = 3,'2', '5')
										FROM $tbl_session_course_user as session_course_user INNER JOIN $tbl_course AS course
										ON course.code = session_course_user.course_code AND session_course_user.id_session = $id_session INNER JOIN $tbl_session as session ON session_course_user.id_session = session.id
										INNER JOIN $tbl_session_course as session_course
										LEFT JOIN $tbl_user as user ON user.user_id = session_course.id_coach
										WHERE session_course_user.id_user = $user_id  ORDER BY i";

			$course_list_sql_result = Database::query($personal_course_list_sql, __FILE__, __LINE__);

			while ($result_row = Database::fetch_array($course_list_sql_result)) {
				$key = $result_row['id_session'].' - '.$result_row['k'];
				$result_row['s'] = $result_row['14'];

				if (!isset($personal_course_list[$key])) {
					$personal_course_list[$key] = $result_row;
				}
			}
		}
		//print_r($personal_course_list);

		return $personal_course_list;
	}
	/**
	 * Gives a list of courses for the given user in the given session
	 * @param integer $user_id
	 * @return array  list of statuses (session_id-course_code => status)
	 */
	public static function get_courses_list_by_session ($user_id, $session_id) {
		// Database Table Definitions
		$tbl_session 				= Database :: get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_course 		= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

		$user_id = intval($user_id);
		//we filter the courses from the URL
		$join_access_url=$where_access_url='';
		global $_configuration;
		if ($_configuration['multiple_access_urls']==true) {
			$access_url_id = api_get_current_access_url_id();
			if($access_url_id!=-1) {
				$tbl_url_course = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
				$join_access_url= "LEFT JOIN $tbl_url_course url_rel_course ON url_rel_course.course_code= course.code";
				$where_access_url=" AND access_url_id = $access_url_id ";
			}
		}

		// variable initialisation
		$personal_course_list_sql = '';
		$personal_course_list = array();
		$courses = array();

		// this query is very similar to the above query, but it will check the session_rel_course_user table if there are courses registered to our user or not
		$personal_course_list_sql = "SELECT distinct scu.course_code as code
									FROM $tbl_session_course_user as scu
									$join_access_url
									WHERE scu.id_user = $user_id
									AND scu.id_session = $session_id
									$where_access_url
									ORDER BY code";
		$course_list_sql_result = Database::query($personal_course_list_sql, __FILE__, __LINE__);

		if (Database::num_rows($course_list_sql_result)>0) {
			while ($result_row = Database::fetch_array($course_list_sql_result)) {
				$result_row['status'] = 5;
				if (!in_array($result_row['code'],$courses)) {
					$personal_course_list[] = $result_row;
					$courses[] = $result_row['code'];
				}
			}
		}

		if(api_is_allowed_to_create_course()) {
			$personal_course_list_sql = "SELECT DISTINCT sc.course_code as code
										FROM $tbl_session_course as sc, $tbl_session as s
										$join_access_url
										WHERE s.id = $session_id
										AND sc.id_session = s.id
										AND (sc.id_coach=$user_id OR s.id_coach=$user_id)
										$where_access_url
										ORDER BY code";

			$course_list_sql_result = Database::query($personal_course_list_sql, __FILE__, __LINE__);

			if (Database::num_rows($course_list_sql_result)>0) {
				while ($result_row = Database::fetch_array($course_list_sql_result)) {
					$result_row['status'] = 2;
					if (!in_array($result_row['code'],$courses)) {
						$personal_course_list[] = $result_row;
						$courses[] = $result_row['code'];
					}
				}
			}
		}
		return $personal_course_list;
	}

	/**
	 * Get user id from a username
	 * @param	string	Username
	 * @return	int		User ID (or false if not found)
	 */
	public static function get_user_id_from_username($username) {
		$username = Database::escape_string($username);
		$t_user = Database::get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT user_id FROM $t_user WHERE username = '$username'";
		$res = Database::query($sql, __FILE__, __LINE__);
		if ($res === false) { return false; }
		if (Database::num_rows($res) !== 1) { return false; }
		$row = Database::fetch_array($res);
		return $row['user_id'];
	}

	/**
	 * Get the users files upload from his share_folder
	 * @param	string	User ID
	 * @param   string  course directory
	 * @param   int 	deprecated
	 * @return	int		User ID (or false if not found)
	 */
	public static function get_user_upload_files_by_course($user_id, $course, $column = 2) {
		$return = '';
		if (!empty($user_id) && !empty($course)) {
			$user_id = intval($user_id);
			$path = api_get_path(SYS_COURSE_PATH).$course.'/document/shared_folder/sf_user_'.$user_id.'/';
			$web_path = api_get_path(WEB_COURSE_PATH).$course.'/document/shared_folder/sf_user_'.$user_id.'/';
			$file_list = array();

			if (is_dir($path)) {
				$handle = opendir($path);
				while ($file = readdir($handle)) {
					if ($file == '.' || $file == '..' || $file == '.htaccess' || is_dir($path.$file)) {
						continue; // skip current/parent directory and .htaccess
					}
					$file_list[] = $file;
				}
				if (count($file_list) > 0) {
					$return = $course;
					$return .= '<ul>';
				}
				foreach ($file_list as $file) {
					$return .= '<li><a href="'.$web_path.urlencode($file).'" target="_blank">'.htmlentities($file).'</a>';
				}
				$return .= '</ul>';
			}
		}
		return $return;
	}

    /**
     * Gets the API key (or keys) and return them into an array
     * @param   int     Optional user id (defaults to the result of api_get_user_id())
     * @result  array   Non-indexed array containing the list of API keys for this user, or FALSE on error
     */
    public static function get_api_keys($user_id = null, $api_service = 'dokeos') {
    	if ($user_id != strval(intval($user_id))) return false;
        if (empty($user_id)) { $user_id = api_get_user_id(); }
        if ($user_id === false) return false;
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) { return false;}
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT id, api_key FROM $t_api WHERE user_id = ".$user_id." AND api_service='".$api_service."';";
        $res = Database::query($sql, __FILE__, __LINE__);
        if ($res === false) return false; //error during query
        $num = Database::num_rows($res);
        if ($num == 0) return false;
        $list = array();
        while ($row = Database::fetch_array($res)) {
        	$list[$row['id']] = $row['api_key'];
        }
        return $list;
    }

    /**
     * Adds a new API key to the users' account
     * @param   int     Optional user ID (defaults to the results of api_get_user_id())
     * @return  boolean True on success, false on failure
     */
    public static function add_api_key($user_id = null, $api_service = 'dokeos') {
        if ($user_id != strval(intval($user_id))) return false;
        if (empty($user_id)) { $user_id = api_get_user_id(); }
        if ($user_id === false) return false;
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) { return false; }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $md5 = md5((time() + ($user_id * 5)) - rand(10000, 10000)); //generate some kind of random key
        $sql = "INSERT INTO $t_api (user_id, api_key,api_service) VALUES ($user_id,'$md5','$service_name')";
        $res = Database::query($sql, __FILE__, __LINE__);
        if ($res === false) return false; //error during query
        $num = Database::insert_id();
        return ($num == 0) ? false : $num;
    }

    /**
     * Deletes an API key from the user's account
     * @param   int     API key's internal ID
     * @return  boolean True on success, false on failure
     */
    public static function delete_api_key($key_id) {
        if ($key_id != strval(intval($key_id))) return false;
        if ($key_id === false) return false;
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT * FROM $t_api WHERE id = ".$key_id;
        $res = Database::query($sql, __FILE__, __LINE__);
        if ($res === false) return false; //error during query
        $num = Database::num_rows($res);
        if ($num !== 1) return false;
        $sql = "DELETE FROM $t_api WHERE id = ".$key_id;
        $res = Database::query($sql, __FILE__, __LINE__);
        if ($res === false) return false; //error during query
        return true;
    }

    /**
     * Regenerate an API key from the user's account
     * @param   int     user ID (defaults to the results of api_get_user_id())
     * @param   string  API key's internal ID
     * @return  int		num
     */
    public static function update_api_key($user_id, $api_service) {
    	if ($user_id != strval(intval($user_id))) return false;
    	if ($user_id === false) return false;
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) { return false; }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT id FROM $t_api WHERE user_id=".$user_id." AND api_service='".$api_service."'";
        $res = Database::query($sql, __FILE__, __LINE__);
        $num = Database::num_rows($res);
        if ($num == 1) {
        	$id_key = Database::fetch_array($res, 'ASSOC');
        	self::delete_api_key($id_key['id']);
        	$num = self::add_api_key($user_id, $api_service);
        } elseif ($num == 0) {
        	$num = self::add_api_key($user_id);
		}
        return $num;
    }

    /**
     * @param   int     user ID (defaults to the results of api_get_user_id())
     * @param   string	API key's internal ID
     * @return  int		row ID, not return a boolean
     */
    public static function get_api_key_id($user_id, $api_service) {
    	if ($user_id != strval(intval($user_id))) return false;
    	if ($user_id === false) return false;
        $service_name = Database::escape_string($api_service);
        if (is_string($service_name) === false) { return false; }
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT id FROM $t_api WHERE user_id=".$user_id." AND api_service='".$api_service."'";
        $res = Database::query($sql, __FILE__, __LINE__);
        $row = Database::fetch_array($res, 'ASSOC');
        return $row['id'];
    }

    /**
     * Subscribes users to the given session and optionally (default) unsubscribes previous users
     * @param	int		Session ID
     * @param	array	List of user IDs
     * @param	bool	Whether to unsubscribe existing users (true, default) or not (false)
     * @return	void	Nothing, or false on error
     */
    public static function suscribe_users_to_session($id_session, $UserList, $empty_users = true) {

    	if ($id_session != strval(intval($id_session))) return false;
    	foreach ($UserList as $intUser) {
    		if ($intUser != strval(intval($intUser))) return false;
    	}
    	$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    	$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    	$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
    	$sql = "SELECT id_user FROM $tbl_session_rel_user WHERE id_session='$id_session'";
		$result = Database::query($sql, __FILE__, __LINE__);
		$existingUsers = array();
		while($row = Database::fetch_array($result)) {
			$existingUsers[] = $row['id_user'];
		}
		$sql = "SELECT course_code FROM $tbl_session_rel_course WHERE id_session='$id_session'";
		$result = Database::query($sql, __FILE__, __LINE__);

		$CourseList = array();

		while($row = Database::fetch_array($result)) {
			$CourseList[] = $row['course_code'];
		}

		foreach ($CourseList as $enreg_course) {
			// for each course in the session
			$nbr_users = 0;
            $enreg_course = Database::escape_string($enreg_course);
			// delete existing users
			if ($empty_users !== false) {
				foreach ($existingUsers as $existing_user) {
					if(!in_array($existing_user, $UserList)) {
						$sql = "DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course' AND id_user='$existing_user'";
						Database::query($sql, __FILE__, __LINE__);

						if (Database::affected_rows()) {
							$nbr_users--;
						}
					}
				}
			}
			// insert new users into session_rel_course_rel_user and ignore if they already exist
			foreach ($UserList as $enreg_user) {
				if (!in_array($enreg_user, $existingUsers)) {
                    $enreg_user = Database::escape_string($enreg_user);
					$insert_sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$enreg_user')";
					Database::query($insert_sql, __FILE__, __LINE__);

					if (Database::affected_rows()) {
						$nbr_users++;
					}
				}
			}
			// count users in this session-course relation
			$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course'";
			$rs = Database::query($sql, __FILE__, __LINE__);
			list($nbr_users) = Database::fetch_array($rs);
			// update the session-course relation to add the users total
			$update_sql = "UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'";
			Database::query($update_sql, __FILE__, __LINE__);
		}
		// delete users from the session
		if ($empty_users !== false) {
			Database::query("DELETE FROM $tbl_session_rel_user WHERE id_session = $id_session", __FILE__, __LINE__);
		}
		// insert missing users into session
		$nbr_users = 0;
		foreach ($UserList as $enreg_user) {
            $enreg_user = Database::escape_string($enreg_user);
			$nbr_users++;
			$insert_sql = "INSERT IGNORE INTO $tbl_session_rel_user(id_session, id_user) VALUES('$id_session','$enreg_user')";
			Database::query($insert_sql, __FILE__, __LINE__);

		}
		// update number of users in the session
		$nbr_users = count($UserList);
		$update_sql = "UPDATE $tbl_session SET nbr_users= $nbr_users WHERE id='$id_session' ";
		Database::query($update_sql, __FILE__, __LINE__);
    }

    /**
     * Checks if a user_id is platform admin
     * @param   int user ID
     * @return  boolean True if is admin, false otherwise
     * @see main_api.lib.php::api_is_platform_admin() for a context-based check
     */
    function is_admin($user_id) {
        if (empty($user_id) or $user_id != strval(intval($user_id))) { return false; }
        $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);
        $sql = "SELECT * FROM $admin_table WHERE user_id = $user_id";
        $res = Database::query($sql);
        return Database::num_rows($res) === 1;
    }

    /**
     * Get the total count of users
     * @return	mixed	Number of users or false on error
     */
    public static function get_number_of_users() {
        $t_u = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT count(*) FROM $t_u";
        $res = Database::query($sql);
        if (Database::num_rows($res) === 1) {
        	return (int) Database::result($res, 0, 0);
        }
        return false;
    }

    /**
	 * Resize a picture
	 *
	 * @param  string file picture
	 * @param  int size in pixels
	 * @return obj image object
	 */
	public static function resize_picture($file, $max_size_for_picture) {
		if (!class_exists('image')) {
			require_once api_get_path(LIBRARY_PATH).'image.lib.php';
		}
	 	$temp = new image($file);
	 	$picture_infos = api_getimagesize($file);
		if ($picture_infos[0] > $max_size_for_picture) {
			$thumbwidth = $max_size_for_picture;
			if (empty($thumbwidth) or $thumbwidth == 0) {
				$thumbwidth = $max_size_for_picture;
			}
			$new_height = round(($thumbwidth / $picture_infos[0]) * $picture_infos[1]);
			if ($new_height > $max_size_for_picture)
			$new_height = $thumbwidth;
			$temp->resize($thumbwidth, $new_height, 0);
		}
		return $temp;
	}

    /**
     * Gets the current user image
     * @param string user id
     * @param string picture user name
     * @param string height
     * @param string picture size it can be small_,  medium_  or  big_
     * @param string style css
     * @return array with the file and the style of an image i.e $array['file'] $array['style']
     */
   public static function get_picture_user($user_id, $picture_file, $height, $size_picture = 'medium_', $style = '') {
    	$patch_profile = 'upload/users/';
    	$picture = array();
    	$picture['style'] = $style;
    	if ($picture_file == 'unknown.jpg') {
    		$picture['file'] = api_get_path(WEB_CODE_PATH).'img/'.$picture_file;
    		return $picture;
    	}
        $image_array_sys = self::get_user_picture_path_by_id($user_id, 'system', false, true);
        $image_array = self::get_user_picture_path_by_id($user_id, 'web', false, true);
        $file = $image_array_sys['dir'].$size_picture.$picture_file;
    	if (file_exists($file)) {
            $picture['file'] = $image_array['dir'].$size_picture.$picture_file;
			$picture['style'] = '';
			if ($height > 0) {
				$dimension = api_getimagesize($picture['file']);
				$margin = (($height - $dimension[1]) / 2);
				$picture['style'] = ' style="padding-top:'.$margin.'px; width:'.$dimension[0].'px; height:'.$dimension[1].';" ';
			}
		} else {
			//$file = api_get_path(SYS_CODE_PATH).$patch_profile.$user_id.'/'.$picture_file;
            $file = $image_array_sys['dir'].$picture_file;
			if (file_exists($file)) {
				$picture['file'] = $image_array['dir'].$picture_file;
			} else {
				$picture['file'] = api_get_path(WEB_CODE_PATH).'img/unknown.jpg';
			}
		}
		return $picture;
    }

    /**
     * @author Isaac flores <isaac.flores@dokeos.com>
     * @param string The email administrator
     * @param integer The user id
     * @param string The message title
     * @param string The content message
     */
   	  public static function send_message_in_outbox($email_administrator, $user_id, $title, $content) {
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$table_user = Database::get_main_table(TABLE_MAIN_USER);
        $title = api_utf8_decode($title);
        $content = api_utf8_decode($content);
		//message in inbox
		$sql_message_outbox = 'SELECT user_id from '.$table_user.' WHERE email="'.$email_administrator.'" ';
		//$num_row_query = Database::num_rows($sql_message_outbox);
		$res_message_outbox = Database::query($sql_message_outbox, __FILE__, __LINE__);
		$array_users_administrator = array();
		while ($row_message_outbox = Database::fetch_array($res_message_outbox, 'ASSOC')) {
			$array_users_administrator[] = $row_message_outbox['user_id'];
		}
		//allow to insert messages in outbox
		for ($i = 0; $i < count($array_users_administrator); $i++) {
			$sql_insert_outbox = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content ) ".
					" VALUES (".
			 		"'".(int)$user_id."', '".(int)($array_users_administrator[$i])."', '4', '".date('Y-m-d H:i:s')."','".Database::escape_string($title)."','".Database::escape_string($content)."'".
			 		")";
			$rs = Database::query($sql_insert_outbox, __FILE__, __LINE__);
		}
	}
}
