<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors
	Copyright (c) Bart Mollet, Hogeschool Gent

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	This library provides functions for user management.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
==============================================================================
*/
class UserManager
{
	/**
	  * Creates a new user for the platform
	  * @author Hugues Peeters <peeters@ipm.ucl.ac.be>,
	  * 		Roan Embrechts <roan_embrechts@yahoo.com>
	  *
	  * @param string $firstName
	  *        string $lastName
	  *        int    $status
	  *        string $email
	  *        string $loginName
	  *        string $password
	  *        string $official_code	(optional)
	  *        string $phone		(optional)
	  *        string $picture_uri	(optional)
	  *        string $auth_source	(optional)
	  *
	  * @return int     new user id - if the new user creation succeeds
	  *         boolean false otherwise
	  *
	  * @desc The function tries to retrieve $_user['user_id'] from the global space.
	  * if it exists, $_user['user_id'] is the creator id       If       a problem arises,
	  * it stores the error message in global $api_failureList
	  *
	  * @todo Add the user language to the parameters
	  */
	function create_user($firstName, $lastName, $status, $email, $loginName, $password, $official_code = '', $language="english", $phone = '', $picture_uri = '', $auth_source = PLATFORM_AUTH_SOURCE, $expiration_date = '0000-00-00 00:00:00', $active = 1)
	{
		global $_user, $userPasswordCrypted;
		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		if ($_user['user_id'])
		{
			$creator_id = $_user['user_id'];
		}
		else
		{
			$creator_id = '';
		}
		// First check wether the login already exists
		if (! UserManager::is_username_available($loginName))
			return api_set_failure('login-pass already taken');
		//$password = "PLACEHOLDER";
		$password = ($userPasswordCrypted ? md5($password) : $password);
		$sql = "INSERT INTO $table_user
					                SET lastname = '".mysql_real_escape_string($lastName)."',
					                firstname = '".mysql_real_escape_string($firstName)."',
					                username = '".mysql_real_escape_string($loginName)."',
					                status = '".mysql_real_escape_string($status)."',
					                password = '".mysql_real_escape_string($password)."',
					                email = '".mysql_real_escape_string($email)."',
					                official_code	= '".mysql_real_escape_string($official_code)."',
					                picture_uri 	= '".mysql_real_escape_string($picture_uri)."',
					                creator_id  	= '".mysql_real_escape_string($creator_id)."',
					                auth_source = '".mysql_real_escape_string($auth_source)."',
				                    phone = '".mysql_real_escape_string($phone)."',
				                    language = '".mysql_real_escape_string($language)."', 
				                    registration_date = now(),
				                    expiration_date = '".mysql_real_escape_string($expiration_date)."',
									active = '".mysql_real_escape_string($active)."'";
		$result = api_sql_query($sql);
		if ($result)
		{
			//echo "id returned";
			return mysql_insert_id();
		}
		else
		{
			//echo "false - failed" ;
			return false;
		}
	}

	/**
	 * Can user be deleted?
	 * This functions checks if there's a course in which the given user is the
	 * only course administrator. If that is the case, the user can't be
	 * deleted because the course would remain without a course admin.
	 * @param int $user_id The user id
	 * @return boolean true if user can be deleted
	 */
	function can_delete_user($user_id)
	{
		$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$sql = "SELECT * FROM $table_course_user WHERE status = '1' AND user_id = '".$user_id."'";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		while ($course = mysql_fetch_object($res))
		{
			$sql = "SELECT user_id FROM $table_course_user WHERE status='1' AND course_code ='".$course->course_code."'";
			$res2 = api_sql_query($sql,__FILE__,__LINE__);
			if (mysql_num_rows($res2) == 1)
			{
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
	function delete_user($user_id)
	{
		if (!UserManager :: can_delete_user($user_id))
		{
			return false;
		}
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$table_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$table_class_user = Database :: get_main_table(TABLE_MAIN_CLASS_USER);
		$table_course = Database :: get_main_table(TABLE_MAIN_COURSE);
		$table_admin = Database :: get_main_table(TABLE_MAIN_ADMIN);

		// Unsubscribe the user from all groups in all his courses
		$sql = "SELECT * FROM $table_course c, $table_course_user cu WHERE cu.user_id = '".$user_id."' AND c.code = cu.course_code";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		while ($course = mysql_fetch_object($res))
		{
			$table_group = Database :: get_course_table(TABLE_GROUP_USER, $course->db_name);
			$sql = "DELETE FROM $table_group WHERE user_id = '".$user_id."'";
			api_sql_query($sql,__FILE__,__LINE__);
		}

		// Unsubscribe user from all classes
		$sql = "DELETE FROM $table_class_user WHERE user_id = '".$user_id."'";
		api_sql_query($sql,__FILE__,__LINE__);

		// Unsubscribe user from all courses
		$sql = "DELETE FROM $table_course_user WHERE user_id = '".$user_id."'";
		api_sql_query($sql,__FILE__,__LINE__);

		// Delete user picture
		$user_info = api_get_user_info($user_id);
		if(strlen($user_info['picture_uri']) > 0)
		{
			$img_path = api_get_path(SYS_CODE_PATH).'upload/users/'.$user_info['picture_uri'];
			unlink($img_path);
		}

		// Delete the personal course categories
		$course_cat_table = Database::get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
		$sql = "DELETE FROM $course_cat_table WHERE user_id = '".$user_id."'";
		api_sql_query($sql,__FILE__,__LINE__);

		// Delete user from database
		$sql = "DELETE FROM $table_user WHERE user_id = '".$user_id."'";
		api_sql_query($sql,__FILE__,__LINE__);

		// Delete user from the admin table
		$sql = "DELETE FROM $table_admin WHERE user_id = '".$user_id."'";
		api_sql_query($sql,__FILE__,__LINE__);

		// Delete the personal agenda-items from this user
		$agenda_table = Database :: get_user_personal_table(TABLE_PERSONAL_AGENDA);
		$sql = "DELETE FROM $agenda_table WHERE user = '".$user_id."'";
		api_sql_query($sql,__FILE__,__LINE__);

		return true;
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
	 * @param int $creator_id
	 * @return boolean true if the user information was updated
	 */
	function update_user($user_id, $firstname, $lastname, $username, $password = null, $auth_source = null, $email, $status, $official_code, $phone, $picture_uri, $expiration_date, $active, $creator_id= null )
	{
		global $userPasswordCrypted;
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "UPDATE $table_user SET
				lastname='".mysql_real_escape_string($lastname)."',
				firstname='".mysql_real_escape_string($firstname)."',
				username='".mysql_real_escape_string($username)."',";
		if(!is_null($password))
		{
			$password = $userPasswordCrypted ? md5($password) : $password;
			$sql .= " password='".mysql_real_escape_string($password)."',";
		}
		if(!is_null($auth_source))
		{
			$sql .=	" auth_source='".mysql_real_escape_string($auth_source)."',";
		}
		$sql .=	"
				email='".mysql_real_escape_string($email)."',
				status='".mysql_real_escape_string($status)."',
				official_code='".mysql_real_escape_string($official_code)."',
				phone='".mysql_real_escape_string($phone)."',
				picture_uri='".mysql_real_escape_string($picture_uri)."',
				expiration_date='".mysql_real_escape_string($expiration_date)."',
				active='".mysql_real_escape_string($active)."'";
		if(!is_null($creator_id))
		{
			$sql .= ", creator_id='".mysql_real_escape_string($creator_id)."'";
		}
		$sql .=	" WHERE user_id='$user_id'";
		return api_sql_query($sql,__FILE__,__LINE__);
	}

	/**
	 * Check if a username is available
	 * @param string the wanted username
	 * @return boolean true if the wanted username is available
	 */
	function is_username_available($username)
	{
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT username FROM $table_user WHERE username = '".addslashes($username)."'";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		return mysql_num_rows($res) == 0;
	}

	/**
	* @return an array with all users of the platform.
	* @todo optional course code parameter, optional sorting parameters...
	* @deprecated This function isn't used anywhere in the code.
	*/
	function get_user_list()
	{
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$sql_query = "SELECT * FROM $user_table";
		$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
		while ($result = mysql_fetch_array($sql_result))
		{
			$return_array[] = $result;
		}
		return $return_array;
	}
	/**
	 * Get user information
	 * @param string $username The username
	 * @return array All user information as an associative array
	 */
	function get_user_info($username)
	{
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT * FROM $user_table WHERE username='".$username."'";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$user = mysql_fetch_array($res,MYSQL_ASSOC);
		return $user;
	}

	//for survey
	function get_teacher_list($course_id, $sel_teacher='')
	{
		$user_course_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$sql_query = "SELECT * FROM $user_table a, $user_course_table b where a.user_id=b.user_id AND b.status=1 AND b.course_code='$course_id'";
		$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
		echo "<select name=\"author\">";
		while ($result = mysql_fetch_array($sql_result))
		{
			if($sel_teacher==$result[user_id]) $selected ="selected";
			echo "\n<option value=\"".$result[user_id]."\" $selected>".$result[firstname]."</option>";
		}
		echo "</select>";
	}

}
?>