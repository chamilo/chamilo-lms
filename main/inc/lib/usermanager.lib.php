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
	function create_user($firstName, $lastName, $status, $email, $loginName, $password, $official_code = '', $language='', $phone = '', $picture_uri = '', $auth_source = PLATFORM_AUTH_SOURCE, $expiration_date = '0000-00-00 00:00:00', $active = 1)
	{
		global $_user, $userPasswordCrypted;
		
		// database table definition
		$table_user = Database::get_main_table(TABLE_MAIN_USER);
		
		// default langauge
		if ($language=='')
		{
			$language = api_get_setting('platformLanguage');
		}
		
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
					                SET lastname = '".Database::escape_string($lastName)."',
					                firstname = '".Database::escape_string($firstName)."',
					                username = '".Database::escape_string($loginName)."',
					                status = '".Database::escape_string($status)."',
					                password = '".Database::escape_string($password)."',
					                email = '".Database::escape_string($email)."',
					                official_code	= '".Database::escape_string($official_code)."',
					                picture_uri 	= '".Database::escape_string($picture_uri)."',
					                creator_id  	= '".Database::escape_string($creator_id)."',
					                auth_source = '".Database::escape_string($auth_source)."',
				                    phone = '".Database::escape_string($phone)."',
				                    language = '".Database::escape_string($language)."', 
				                    registration_date = now(),
				                    expiration_date = '".Database::escape_string($expiration_date)."',
									active = '".Database::escape_string($active)."'";
		$result = api_sql_query($sql);
		if ($result)
		{
			//echo "id returned";
			return Database::get_last_insert_id();
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
		while ($course = Database::fetch_object($res))
		{
			$sql = "SELECT user_id FROM $table_course_user WHERE status='1' AND course_code ='".$course->course_code."'";
			$res2 = api_sql_query($sql,__FILE__,__LINE__);
			if (Database::num_rows($res2) == 1)
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
		$table_session_user = Database :: get_main_table(TABLE_MAIN_SESSION_USER);
		$table_session_course_user = Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

		// Unsubscribe the user from all groups in all his courses
		$sql = "SELECT * FROM $table_course c, $table_course_user cu WHERE cu.user_id = '".$user_id."' AND c.code = cu.course_code";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		while ($course = Database::fetch_object($res))
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
		
		// Unsubscribe user from all courses in sessions
		$sql = "DELETE FROM $table_session_course_user WHERE id_user = '".$user_id."'";
		api_sql_query($sql,__FILE__,__LINE__);
		
		// Unsubscribe user from all sessions
		$sql = "DELETE FROM $table_session_user WHERE id_user = '".$user_id."'";
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

		$gradebook_results_table = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);
		$sql = 'DELETE FROM '.$gradebook_results_table.' WHERE user_id = '.$user_id;
		api_sql_query($sql, __FILE__, __LINE__);
		
		return true;
	}

	/**
	 * Update user information with new openid
	 * @param int $user_id
	 * @param string $openid
	 * @return boolean true if the user information was updated
	 */
	function update_openid($user_id, $openid)
	{
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "UPDATE $table_user SET
				openid='".Database::escape_string($openid)."'";
		$sql .=	" WHERE user_id='$user_id'";
		return api_sql_query($sql,__FILE__,__LINE__);
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
				lastname='".Database::escape_string($lastname)."',
				firstname='".Database::escape_string($firstname)."',
				username='".Database::escape_string($username)."',";
		if(!is_null($password))
		{
			$password = $userPasswordCrypted ? md5($password) : $password;
			$sql .= " password='".Database::escape_string($password)."',";
		}
		if(!is_null($auth_source))
		{
			$sql .=	" auth_source='".Database::escape_string($auth_source)."',";
		}
		$sql .=	"
				email='".Database::escape_string($email)."',
				status='".Database::escape_string($status)."',
				official_code='".Database::escape_string($official_code)."',
				phone='".Database::escape_string($phone)."',
				picture_uri='".Database::escape_string($picture_uri)."',
				expiration_date='".Database::escape_string($expiration_date)."',
				active='".Database::escape_string($active)."'";
		if(!is_null($creator_id))
		{
			$sql .= ", creator_id='".Database::escape_string($creator_id)."'";
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
		return Database::num_rows($res) == 0;
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
		while ($result = Database::fetch_array($sql_result))
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
		if(Database::num_rows($res)>0)
		{
			$user = Database::fetch_array($res);
		}
		else
		{
			$user = false;
		}
		return $user;
	}
	
	/**
	 * Get user information
	 * @param string $id The id
	 * @return array All user information as an associative array
	 */
	function get_user_info_by_id($user_id)
	{
		$user_id = intval($user_id);
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT * FROM $user_table WHERE user_id=".$user_id;
		$res = api_sql_query($sql,__FILE__,__LINE__);
		if(Database::num_rows($res)>0)
		{
			$user = Database::fetch_array($res);
		}
		else
		{
			$user = false;
		}
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
		while ($result = Database::fetch_array($sql_result))
		{
			if($sel_teacher==$result['user_id']) $selected ="selected";
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
	 * @param	string	Type of path to return (can be 'none','system','rel','web')
	 * @param	bool	Whether we want to have the directory name returned 'as if' there was a file or not (in the case we want to know which directory to create - otherwise no file means no split subdir)
	 * @param	bool	If we want that the function returns the /main/img/unknown.jpg image set it at true 
	 * @return	array 	Array of 2 elements: 'dir' and 'file' which contain the dir and file as the name implies if image does not exist it will return the unknow image if anonymous parameter is true if not it returns an empty array
	 */
	function get_user_picture_path_by_id($id,$type='none',$preview=false,$anonymous=false)
	{
		if(empty($id) or empty($type))
		{
			if ($anonymous) 
			{
				$dir='';
				switch($type)
				{
					case 'system': //return the complete path to the file, from root
						$dir = api_get_path(SYS_CODE_PATH).'img/';
						break;
					case 'rel': //return the relative path to the file, from the Dokeos base dir
						$dir = api_get_path(REL_CODE_PATH).'img/';
						break;
					case 'web': //return the complete web URL to the file 
						$dir = api_get_path(WEB_CODE_PATH).'img/';
						break;
					case 'none': //return only the picture_uri (as is, without subdir)
					default:
						break;
				}
				$file_anonymous='unknown.jpg';
				return array('dir'=>$dir,'file'=>$file_anonymous);
			}
			else 
			{
				return array('dir'=>'','file'=>'');
			}			
		}
		
		$user_id = intval($id);
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT picture_uri FROM $user_table WHERE user_id=".$user_id;
		$res = api_sql_query($sql,__FILE__,__LINE__);
		
		$user=array();
		
		if(Database::num_rows($res)>0)
		{
			$user = Database::fetch_array($res);			
		}
		else
		{									
			if ($anonymous) 
			{
				$dir='';
				switch($type)
				{
					case 'system': //return the complete path to the file, from root
						$dir = api_get_path(SYS_CODE_PATH).'img/';
						break;
					case 'rel': //return the relative path to the file, from the Dokeos base dir
						$dir = api_get_path(REL_CODE_PATH).'img/';
						break;
					case 'web': //return the complete web URL to the file 
						$dir = api_get_path(WEB_CODE_PATH).'img/';
						break;
					case 'none': //return only the picture_uri (as is, without subdir)
					default:
						break;
				}
				$file_anonymous='unknown.jpg';
				return array('dir'=>$dir,'file'=>$file_anonymous);
			}
			else 
			{
				return array('dir'=>'','file'=>'');	
			}		
		}
				
		$path = trim($user['picture_uri']);
		
		if (empty($path)) 
		{			
			if ($anonymous) 
			{			
				switch($type)
				{
					case 'system': //return the complete path to the file, from root
						$dir = api_get_path(SYS_CODE_PATH).'img/';
						break;
					case 'rel': //return the relative path to the file, from the Dokeos base dir
						$dir = api_get_path(REL_CODE_PATH).'img/';
						break;
					case 'web': //return the complete web URL to the file 
						$dir = api_get_path(WEB_CODE_PATH).'img/';
						break;
					case 'none': //return only the picture_uri (as is, without subdir)
					default:
						break;
				}
				$file_anonymous='unknown.jpg';
				return array('dir'=>$dir,'file'=>$file_anonymous);			
			}
		}		
		
		$dir = '';
		$first = '';
		
		if(api_get_setting('split_users_upload_directory') === 'true')
		{
			if(!empty($path))
			{
				$first = substr($path,0,1).'/';
			}
			elseif($preview==true)
			{
				$first = substr(''.$user_id,0,1).'/';
			}
		}
		else
		{
			$first = $user_id.'/';
		}
				
		switch($type)
		{
			case 'system': //return the complete path to the file, from root
				$dir = api_get_path(SYS_CODE_PATH).'upload/users/'.$first;
				break;
			case 'rel': //return the relative path to the file, from the Dokeos base dir
				$dir = api_get_path(REL_CODE_PATH).'upload/users/'.$first;
				break;
			case 'web': //return the complete web URL to the file 
				$dir = api_get_path(WEB_CODE_PATH).'upload/users/'.$first;
				break;
			case 'none': //return only the picture_uri (as is, without subdir)
			default:
				break;
		}
		return array('dir'=>$dir,'file'=>$path);
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
	function build_production_list($user_id, $force = false, $showdelete=false)
	{
		if (!$force && $_POST['remove_production'])
			return true; // postpone reading from the filesystem
	
		$productions = UserManager::get_user_productions($user_id);
	
		if (empty($productions))
			return false;
	
		$production_path = UserManager::get_user_picture_path_by_id($user_id,'web',true);
		$production_dir = $production_path['dir'].$user_id.'/';
		$del_image = api_get_path(WEB_CODE_PATH).'img/delete.gif';
		$del_text = get_lang('Delete');
	
		$production_list = '<ul id="productions">';
	
		foreach ($productions as $file)
		{
			$production_list .= '<li><a href="'.$production_dir.urlencode($file).'" target="_blank">'.htmlentities($file).'</a>';
			if ($showdelete)
			{
				$production_list .= '<input type="image" name="remove_production['.urlencode($file).']" src="'.$del_image.'" alt="'.$del_text.'" title="'.$del_text.' '.htmlentities($file).'" onclick="return confirmation(\''.htmlentities($file).'\');" /></li>';
			}
		}
	
		$production_list .= '</ul>';
	
		return $production_list;
	}
	
	/**
	 * Returns an array with the user's productions.
	 *
	 * @param	$user_id	User id
	 * @return	An array containing the user's productions
	 */
	function get_user_productions($user_id)
	{
		$production_path = UserManager::get_user_picture_path_by_id($user_id,'system',true);
		$production_repository = $production_path['dir'].$user_id.'/';
		$productions = array();
	
		if (is_dir($production_repository))
		{
			$handle = opendir($production_repository);
	
			while ($file = readdir($handle))
			{
				if ($file == '.' || $file == '..' || $file == '.htaccess')
					continue; // skip current/parent directory and .htaccess
	
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
	function remove_user_production($user_id, $production)
	{
		$production_path = UserManager::get_user_picture_path_by_id($user_id,'system',true);
		unlink($production_path['dir'].$user_id.'/'.$production);
	}
}
?>