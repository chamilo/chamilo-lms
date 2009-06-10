<?php // $Id: usermanager.lib.php 21357 2009-06-10 22:40:58Z aportugal $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2009 Dokeos SPRL
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

	Contact: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium, info@dokeos.com
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
// define constants for user extra field types
define('USER_FIELD_TYPE_TEXT',1);
define('USER_FIELD_TYPE_TEXTAREA',2);
define('USER_FIELD_TYPE_RADIO',3);
define('USER_FIELD_TYPE_SELECT',4);
define('USER_FIELD_TYPE_SELECT_MULTIPLE',5);
define('USER_FIELD_TYPE_DATE',6);
define('USER_FIELD_TYPE_DATETIME',7);
define('USER_FIELD_TYPE_DOUBLE_SELECT',8);
define('USER_FIELD_TYPE_DIVIDER',9);

class UserManager
{
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
	  * @param	int	  	User language	(optional)
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
	  * if it exists, $_user['user_id'] is the creator id       If       a problem arises,
	  * it stores the error message in global $api_failureList
	  */
	function create_user($firstName, $lastName, $status, $email, $loginName, $password, $official_code = '', $language='', $phone = '', $picture_uri = '', $auth_source = PLATFORM_AUTH_SOURCE, $expiration_date = '0000-00-00 00:00:00', $active = 1, $hr_dept_id=0, $extra=null)
	{
		global $_user, $userPasswordCrypted;
		
		$firstName=Security::remove_XSS($firstName);
		$lastName=Security::remove_XSS($lastName);
		$loginName=Security::remove_XSS($loginName);
		$phone=Security::remove_XSS($phone);
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
		$password = api_get_encrypted_password($password); 
		//$password = ($userPasswordCrypted ? md5($password) : $password);
		$current_date=date('Y-m-d H:i:s',time());
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
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if ($result) {
			//echo "id returned";			
			$return=Database::get_last_insert_id();
			global $_configuration;
			require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
			if ($_configuration['multiple_access_urls']==true) {											
				if (api_get_current_access_url_id()!=-1)
					UrlManager::add_user_to_url($return, api_get_current_access_url_id());
				else
					UrlManager::add_user_to_url($return, 1);					
			} else {
				//we are adding by default the access_url_user table with access_url_id = 1
				UrlManager::add_user_to_url($return, 1);				
			}			
		} else {
			//echo "false - failed" ;
			$return=false;
		}
		
		if(is_array($extra) AND count($extra)>0) {
			$res = true;
			foreach($extra as $fname => $fvalue) {
				$res = $res && UserManager::update_extra_field($return,$fname,$fvalue);
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
			$img_path = api_get_path(SYS_CODE_PATH).'upload/users/'.$user_id.'/'.$user_info['picture_uri'];
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

		$user = Database::fetch_array($res);
		$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		$sqlv = "DELETE FROM $t_ufv WHERE user_id = $user_id";
		$resv = api_sql_query($sqlv,__FILE__,__LINE__);
		
		global $_configuration;
		if ($_configuration['multiple_access_urls']) {
			require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
			$url_id=1;				
			if (api_get_current_access_url_id()!=-1)
				$url_id=api_get_current_access_url_id();											
			UrlManager::delete_url_rel_user($user_id,$url_id);	
		}
		
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
	 * @param int The user ID of the person who registered this user (optional, defaults to null)
	 * @param int The department of HR in which the user is registered (optional, defaults to 0)
	 * @param	array	A series of additional fields to add to this user as extra fields (optional, defaults to null)
	 * @return boolean true if the user information was updated
	 */
	function update_user($user_id, $firstname, $lastname, $username, $password = null, $auth_source = null, $email, $status, $official_code, $phone, $picture_uri, $expiration_date, $active, $creator_id= null, $hr_dept_id=0, $extra=null,$language='english')
	{
		global $userPasswordCrypted;
		$table_user = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "UPDATE $table_user SET
				lastname='".Database::escape_string($lastname)."',
				firstname='".Database::escape_string($firstname)."',
				username='".Database::escape_string($username)."',
				language='".Database::escape_string($language)."',";
		if(!is_null($password))
		{
			//$password = $userPasswordCrypted ? md5($password) : $password;			
			$password = api_get_encrypted_password($password);  
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
				active='".Database::escape_string($active)."',
				hr_dept_id=".intval($hr_dept_id);
		if(!is_null($creator_id))
		{
			$sql .= ", creator_id='".Database::escape_string($creator_id)."'";
		}
		$sql .=	" WHERE user_id='$user_id'";
		$return = api_sql_query($sql,__FILE__,__LINE__);
		if(is_array($extra) and count($extra)>0)
		{
			$res = true;
			foreach($extra as $fname => $fvalue)
			{
				$res = $res && UserManager::update_extra_field($user_id,$fname,$fvalue);
			}
		}

		return $return;
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
    * Get a list of users of which the given conditions match with an = 'cond'
	* @param array $conditions a list of condition (exemple : status=>STUDENT)
	* @param array $order_by a list of fields on which sort
	* @return array An array with all users of the platform.
	* @todo optional course code parameter, optional sorting parameters...
	*/
	function get_user_list($conditions = array(), $order_by = array()) {
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$return_array = array();
		$sql_query = "SELECT * FROM $user_table";
		if (count($conditions)>0) {
			$sql_query .= ' WHERE ';
			foreach ($conditions as $field=>$value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
				$sql_query .= $field.' = '.$value;
			}
		}
		if (count($order_by)>0) {
			$sql_query .= ' ORDER BY '.Database::escape_string(implode(',',$order_by));
		}
		$sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
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
        if (count($conditions)>0) {
            $sql_query .= ' WHERE ';
            foreach ($conditions as $field=>$value) {
                $field = Database::escape_string($field);
                $value = Database::escape_string($value);
                $sql_query .= $field.' LIKE \'%'.$value.'%\'';
            }
        }
        if (count($order_by)>0) {
            $sql_query .= ' ORDER BY '.Database::escape_string(implode(',',$order_by));
        }
        $sql_result = api_sql_query($sql_query,__FILE__,__LINE__);
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
	 * @param	string	The id
	 * @param	boolean	Whether to return the user's extra fields (defaults to false)
	 * @return	array 	All user information as an associative array
	 */
	function get_user_info_by_id($user_id,$user_fields=false)
	{
		$user_id = intval($user_id);
		$user_table = Database :: get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT * FROM $user_table WHERE user_id=".$user_id;
		$res = api_sql_query($sql,__FILE__,__LINE__);
		if(Database::num_rows($res)>0)
		{
			$user = Database::fetch_array($res);
			$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
			$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
			$sqlf = "SELECT * FROM $t_uf ORDER BY field_order";
			$resf = api_sql_query($sqlf,__FILE__,__LINE__);
			if(Database::num_rows($resf)>0)
			{
				while($rowf = Database::fetch_array($resf))
				{
					$sqlv = "SELECT * FROM $t_ufv WHERE field_id = ".$rowf['id']." AND user_id = ".$user['user_id']." ORDER BY id DESC";
					$resv = api_sql_query($sqlv,__FILE__,__LINE__);
					if(Database::num_rows($resv)>0)
					{
						//There should be only one value for a field and a user
						$rowv = Database::fetch_array($resv);
						$user['extra'][$rowf['field_variable']] = $rowv['field_value'];
					}
					else
					{
						$user['extra'][$rowf['field_variable']] = '';
					}
				}
			}
			
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
		$first .= $user_id.'/';
				
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
		if (!$force && !empty($_POST['remove_production']))
			return true; // postpone reading from the filesystem
	
		$productions = UserManager::get_user_productions($user_id);
	
		if (empty($productions))
			return false;
	
		$production_path = UserManager::get_user_picture_path_by_id($user_id,'web',true);
		$production_dir = $production_path['dir'].$user_id.'/';
		$del_image = api_get_path(WEB_CODE_PATH).'img/delete.gif';
		$del_text = get_lang('Delete');
		$production_list = '';
		if (count($productions)>0) {
			$production_list = '<ul id="productions">';	
			foreach ($productions as $file) {
				$production_list .= '<li><a href="'.$production_dir.urlencode($file).'" target="_blank">'.htmlentities($file).'</a>';
				if ($showdelete) {
					$production_list .= '<input type="image" name="remove_production['.urlencode($file).']" src="'.$del_image.'" alt="'.$del_text.'" title="'.$del_text.' '.htmlentities($file).'" onclick="return confirmation(\''.htmlentities($file).'\');" /></li>';
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
				if ($file == '.' || $file == '..' || $file == '.htaccess' || is_dir($production_repository.$file))
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
	/**
	 * Update an extra field. This function is called when a user changes his/her profile
	 * and by consequence fills or edits his/her extra fields. 
	 * 
	 * @param	integer	Field ID
	 * @param	array	Database columns and their new value
	 * @return	boolean	true if field updated, false otherwise
	 */
	function update_extra_field($fid,$columns) 
	{
		//TODO check that values added are values proposed for enumerated field types
		$t_uf 	= Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$fid 	= Database::escape_string($fid);
		$sqluf = "UPDATE $t_uf SET ";
		$known_fields = array('id','field_variable','field_type','field_display_text','field_default_value','field_order','field_visible','field_changeable','field_filter');
		$safecolumns = array(); 
		foreach ($columns as $index => $newval) {
			if (in_array($index,$known_fields)) {			
				$safecolumns[$index] = Database::escape_string($newval);
				$sqluf .= $index." = '".$safecolumns[$index]."', ";
			}
		}
		$time = time();
		$sqluf .= " tms = FROM_UNIXTIME($time) WHERE id='$fid'";
		$resuf = api_sql_query($sqluf,__FILE__,__LINE__);
		return $resuf;
	}
	/**
	 * Update an extra field value for a given user
	 * @param	integer	User ID
	 * @param	string	Field variable name
	 * @param	string	Field value
	 * @return	boolean	true if field updated, false otherwise
	 */
	function update_extra_field_value($user_id,$fname,$fvalue='')
	{
		//TODO check that values added are values proposed for enumerated field types
		$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$t_ufo = Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
		$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		$fname = Database::escape_string($fname);
		$fvalues = '';
		if(is_array($fvalue))
		{
			foreach($fvalue as $val)
			{
				$fvalues .= Database::escape_string($val).';';
			}
			if(!empty($fvalues))
			{
				$fvalues = substr($fvalues,0,-1);
			}
		}
		else
		{
			$fvalues = Database::escape_string($fvalue);
		}
		$sqluf = "SELECT * FROM $t_uf WHERE field_variable='$fname'";
		$resuf = api_sql_query($sqluf,__FILE__,__LINE__);
		if(Database::num_rows($resuf)==1)
		{ //ok, the field exists
			//	Check if enumerated field, if the option is available 
			$rowuf = Database::fetch_array($resuf);
			switch($rowuf['field_type'])
			{
				case 3:
				case 4:
				case 5:
					$sqluo = "SELECT * FROM $t_ufo WHERE field_id = ".$rowuf['id'];
					$resuo = api_sql_query($sqluo,__FILE__,__LINE__);
					$values = split(';',$fvalues);
					if(Database::num_rows($resuo)>0)
					{
						$check = false;
						while($rowuo = Database::fetch_array($resuo))
						{
							if(in_array($rowuo['option_value'],$values))
							{
								$check = true;
								break;
							}
						}
						if($check == false)
						{
							return false; //option value not found
						}
					}
					else
					{
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
			$resufv = api_sql_query($sqlufv,__FILE__,__LINE__);
			$n = Database::num_rows($resufv);
			if($n>1)
			{
				//problem, we already have to values for this field and user combination - keep last one
				while($rowufv = Database::fetch_array($resufv))
				{
					if($n > 1)
					{
						$sqld = "DELETE FROM $t_ufv WHERE id = ".$rowufv['id'];
						$resd = api_sql_query($sqld,__FILE__,__LINE__);
						$n--;
					}
					$rowufv = Database::fetch_array($resufv);
					if($rowufv['field_value'] != $fvalues)
					{ 
						$sqlu = "UPDATE $t_ufv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = ".$rowufv['id'];
						$resu = api_sql_query($sqlu,__FILE__,__LINE__);
						return($resu?true:false);					
					}
					return true;
				}
			}
			elseif($n==1)
			{
				//we need to update the current record
				$rowufv = Database::fetch_array($resufv);
				if($rowufv['field_value'] != $fvalues)
				{
					$sqlu = "UPDATE $t_ufv SET field_value = '$fvalues', tms = FROM_UNIXTIME($tms) WHERE id = ".$rowufv['id'];
					//error_log('UM::update_extra_field_value: '.$sqlu);
					$resu = api_sql_query($sqlu,__FILE__,__LINE__);
					return($resu?true:false);
				}
				return true;
			}
			else
			{
				$sqli = "INSERT INTO $t_ufv (user_id,field_id,field_value,tms) " .
					"VALUES ($user_id,".$rowuf['id'].",'$fvalues',FROM_UNIXTIME($tms))";
				//error_log('UM::update_extra_field_value: '.$sqli);
				$resi = api_sql_query($sqli,__FILE__,__LINE__);
				return($resi?true:false);
			}
		}
		else
		{
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
	function get_extra_fields($from=0, $number_of_items=0, $column=5, $direction='ASC', $all_visibility=true)
	{
		$fields = array();
		$t_uf = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
		$t_ufo = Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);		
		$columns = array('id','field_variable','field_type','field_display_text','field_default_value','field_order','field_filter','tms');
		
		$sort_direction = '';
		if (in_array(strtoupper($direction),array('ASC','DESC'))) {
			$sort_direction = strtoupper($direction);
		}
		$sqlf = "SELECT * FROM $t_uf ";
		if ($all_visibility==false) {
			$sqlf .= " WHERE field_visible = 1 ";
		}
		$sqlf .= " ORDER BY ".$columns[$column]." $sort_direction " ;
		if ($number_of_items != 0) {
			$sqlf .= " LIMIT ".Database::escape_string($from).','.Database::escape_string($number_of_items);
		}		
		
		$resf = api_sql_query($sqlf,__FILE__,__LINE__);
		if(Database::num_rows($resf)>0) {
			while($rowf = Database::fetch_array($resf)) {				
				$fields[$rowf['id']] = array(
					0=>$rowf['id'],
					1=>$rowf['field_variable'],
					2=>$rowf['field_type'],
					//3=>(empty($rowf['field_display_text'])?'':get_lang($rowf['field_display_text'],'')),
					//temporarily removed auto-translation. Need update to get_lang() to know if translation exists (todo)
					3=>(empty($rowf['field_display_text'])?'':$rowf['field_display_text']),
					4=>$rowf['field_default_value'],
					5=>$rowf['field_order'],
					6=>$rowf['field_visible'],
					7=>$rowf['field_changeable'],
					8=>$rowf['field_filter'],
					9=>array()
				);			
				
				$sqlo = "SELECT * FROM $t_ufo WHERE field_id = ".$rowf['id']." ORDER BY option_order ASC";
				$reso = api_sql_query($sqlo,__FILE__,__LINE__);
				if (Database::num_rows($reso)>0) {
					while ($rowo = Database::fetch_array($reso)) {
						$fields[$rowf['id']][9][$rowo['id']] = array(
							0=>$rowo['id'],
							1=>$rowo['option_value'],
							//2=>(empty($rowo['option_display_text'])?'':get_lang($rowo['option_display_text'],'')),
							2=>(empty($rowo['option_display_text'])?'':$rowo['option_display_text']),
							3=>$rowo['option_order']
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
	function get_extra_field_options($field_name)
	{
		$t_uf = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
		$t_ufo = Database :: get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
		
		$sql = 'SELECT options.* 
				FROM '.$t_ufo.' options 
				INNER JOIN '.$t_uf.' fields
					ON fields.id = options.field_id
					AND fields.field_variable="'.Database::escape_string($field_name).'"';
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		return api_store_result($rs);
	}
	
	
	/**
	 * Get the number of extra fields currently recorded
	 * @param	boolean	Optional switch. true (default) returns all fields, false returns only visible fields
	 * @return	integer	Number of fields
	 */
	function get_number_of_extra_fields($all_visibility=true)
	{
		$t_uf = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
		$sqlf = "SELECT * FROM $t_uf ";
		if($all_visibility == false)
		{
			$sqlf .= " WHERE field_visible = 1 ";
		}
		$sqlf .= " ORDER BY field_order";
		$resf = api_sql_query($sqlf,__FILE__,__LINE__);
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
	function create_extra_field($fieldvarname, $fieldtype, $fieldtitle, $fielddefault, $fieldoptions='')
	{		
		// database table definition
		$table_field 		= Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$table_field_options= Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
		
		// First check wether the login already exists
		if (UserManager::is_extra_field_available($fieldvarname)) 
			return api_set_failure('login-pass already taken');
		$sql = "SELECT MAX(field_order) FROM $table_field";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$order = 0;
		if(Database::num_rows($res)>0)
		{
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
		$result = api_sql_query($sql);
		if ($result)
		{
			//echo "id returned";
			$return=Database::get_last_insert_id();
		}
		else
		{
			//echo "false - failed" ;
			return false;
		}
		
		if(!empty($fieldoptions) && in_array($fieldtype,array(USER_FIELD_TYPE_RADIO,USER_FIELD_TYPE_SELECT,USER_FIELD_TYPE_SELECT_MULTIPLE,USER_FIELD_TYPE_DOUBLE_SELECT)))
		{
			if($fieldtype == USER_FIELD_TYPE_DOUBLE_SELECT)
			{
				$twolist = explode('|', $fieldoptions);
				$counter = 0;
				foreach ($twolist as $individual_list)
				{
					$splitted_individual_list = split(';',$individual_list);
					foreach	($splitted_individual_list as $individual_list_option)				
					{
						//echo 'counter:'.$counter; 
						if ($counter == 0)
						{
							$list[] = $individual_list_option;
						}
						else 
						{
							$list[] = str_repeat('*',$counter).$individual_list_option;
						}
					}
					$counter++;
				}
			}
			else 
			{
				$list = split(';',$fieldoptions);
			}
			foreach($list as $option)
			{
				$option = Database::escape_string($option);
				$sql = "SELECT * FROM $table_field_options WHERE field_id = $return AND option_value = '".$option."'";
				$res = api_sql_query($sql,__FILE__,__LINE__);
				if(Database::num_rows($res)>0)
				{
					//the option already exists, do nothing
				}
				else
				{
					$sql = "SELECT MAX(option_order) FROM $table_field_options WHERE field_id = $return";
					$res = api_sql_query($sql,__FILE__,__LINE__);
					$max = 1;
					if(Database::num_rows($res)>0)
					{
						$row = Database::fetch_array($res);
						$max = $row[0]+1;
					}
					$time = time();
					$sql = "INSERT INTO $table_field_options (field_id,option_value,option_display_text,option_order,tms) VALUES ($return,'$option','$option',$max,FROM_UNIXTIME($time))";
					$res = api_sql_query($sql,__FILE__,__LINE__);
					if($res === false)
					{
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
	function save_extra_field_changes($fieldid, $fieldvarname, $fieldtype, $fieldtitle, $fielddefault, $fieldoptions='')
	{		
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
		$result = api_sql_query($sql,__FILE__,__LINE__);

		// we create an array with all the options (will be used later in the script)
		if ($fieldtype == USER_FIELD_TYPE_DOUBLE_SELECT) {
			$twolist = explode('|', $fieldoptions);
			$counter = 0;
			foreach ($twolist as $individual_list) {
				$splitted_individual_list = split(';',$individual_list);
				foreach	($splitted_individual_list as $individual_list_option) {
					//echo 'counter:'.$counter; 
					if ($counter == 0) {
						$list[] = trim($individual_list_option);
					} else {
						$list[] = str_repeat('*',$counter).trim($individual_list_option);
					}
				}
				$counter++;
			}
		}
		else 
		{
			$templist = split(';',$fieldoptions);
			$list = array_map('trim', $templist);
		}		

		// Remove all the field options (and also the choices of the user) that are NOT in the new list of options
		$sql = "SELECT * FROM $table_field_options WHERE option_value NOT IN ('".implode("','", $list)."') AND field_id = '".Database::escape_string($fieldid)."'";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		$return['deleted_options'] = 0;
		while ($row = Database::fetch_array($result))
		{
			// deleting the option
			$sql_delete_option = "DELETE FROM $table_field_options WHERE id='".Database::escape_string($row['id'])."'";
			$result_delete_option = api_sql_query($sql_delete_option,__FILE__,__LINE__);
			$return['deleted_options']++;
			
			// deleting the answer of the user who has chosen this option
			$sql_delete_option_value = "DELETE FROM $table_field_options_values WHERE field_id = '".Database::escape_string($fieldid)."' AND field_value = '".Database::escape_string($row['option_value'])."'";
			$result_delete_option_value = api_sql_query($sql_delete_option_value,__FILE__,__LINE__);
			$return['deleted_option_values'] = $return['deleted_option_values'] + Database::affected_rows();
		}
		
		// we now try to find the field options that are newly added
		$sql = "SELECT * FROM $table_field_options WHERE field_id = '".Database::escape_string($fieldid)."'";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		while ($row = Database::fetch_array($result))
		{
			// we remove every option that is already in the database from the $list
			if (in_array(trim($row['option_display_text']),$list))
			{
				$key = array_search(trim($row['option_display_text']),$list);
				unset($list[$key]);
			}
		}
		
		// we store the new field options in the database
		foreach ($list as $key=>$option)
		{
			$sql = "SELECT MAX(option_order) FROM $table_field_options WHERE field_id = '".Database::escape_string($fieldid)."'";
			$res = api_sql_query($sql,__FILE__,__LINE__);
			$max = 1;
			if(Database::num_rows($res)>0)
			{
				$row = Database::fetch_array($res);
				$max = $row[0]+1;
			}
			$time = time();
			$sql = "INSERT INTO $table_field_options (field_id,option_value,option_display_text,option_order,tms) VALUES ('".Database::escape_string($fieldid)."','".Database::escape_string($option)."','".Database::escape_string($option)."',$max,FROM_UNIXTIME($time))";				
			$result = api_sql_query($sql,__FILE__,__LINE__);
		}
		return true;
	}	
	
	/**
	 * Check if a field is available
	 * @param	string	the wanted fieldname
	 * @return	boolean	true if the wanted username is available
	 */
	function is_extra_field_available($fieldname)
	{
		$t_uf = Database :: get_main_table(TABLE_MAIN_USER_FIELD);
		$sql = "SELECT * FROM $t_uf WHERE field_variable = '".Database::escape_string($fieldname)."'";
		$res = api_sql_query($sql,__FILE__,__LINE__);
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
	function get_extra_user_data($user_id, $prefix=false, $all_visibility = true, $splitmultiple=false)
	{
		$extra_data = array();
		$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		$user_id = Database::escape_string($user_id);
		$sql = "SELECT f.id as id, f.field_variable as fvar, f.field_type as type FROM $t_uf f ";
		if($all_visibility == false)
		{
			$sql .= " WHERE f.field_visible = 1 ";
		}
		$sql .= " ORDER BY f.field_order";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		if(Database::num_rows($res)>0)
		{
			while($row = Database::fetch_array($res))
			{
				$sqlu = "SELECT field_value as fval " .
						" FROM $t_ufv " .
						" WHERE field_id=".$row['id']."" .
						" AND user_id=".$user_id;
				$resu = api_sql_query($sqlu,__FILE__,__LINE__);
				$fval = '';
				// get default value
				$sql_df = "SELECT field_default_value as fval_df " .
						" FROM $t_uf " .
						" WHERE id=".$row['id'];
				$res_df = api_sql_query($sql_df,__FILE__,__LINE__);						
				if(Database::num_rows($resu)>0)
				{
					$rowu = Database::fetch_array($resu);					
					$fval = $rowu['fval'];
					if($row['type'] ==  USER_FIELD_TYPE_SELECT_MULTIPLE)
					{
						$fval = split(';',$rowu['fval']);
					}					
				} else {					
					$row_df = Database::fetch_array($res_df);
					$fval = $row_df['fval_df'];
				}
				if($prefix)
				{
					if ($row['type'] ==  USER_FIELD_TYPE_RADIO)
					{
						$extra_data['extra_'.$row['fvar']]['extra_'.$row['fvar']] = $fval;
					}
					else
					{
						$extra_data['extra_'.$row['fvar']] = $fval;
					} 
				}
				else
				{
					if ($row['type'] ==  USER_FIELD_TYPE_RADIO)
					{
						$extra_data['extra_'.$row['fvar']]['extra_'.$row['fvar']] = $fval;
					}
					else
					{
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
	
	function get_extra_user_data_by_field($user_id, $field_variable, $prefix=false, $all_visibility = true, $splitmultiple=false)
	{
		$extra_data = array();
		$t_uf = Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$t_ufv = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		$user_id = Database::escape_string($user_id);
		$sql = "SELECT f.id as id, f.field_variable as fvar, f.field_type as type FROM $t_uf f ";
		
		$sql.=" WHERE f.field_variable = '$field_variable' ";
		
		if($all_visibility == false)
		{
			$sql .= " AND f.field_visible = 1 ";
		}
			
		$sql .= " ORDER BY f.field_order";
		
		$res = api_sql_query($sql,__FILE__,__LINE__);
		if(Database::num_rows($res)>0)
		{
			while($row = Database::fetch_array($res))
			{
				$sqlu = "SELECT field_value as fval " .
						" FROM $t_ufv " .
						" WHERE field_id=".$row['id']."" .
						" AND user_id=".$user_id;
				$resu = api_sql_query($sqlu,__FILE__,__LINE__);
				$fval = '';
				if(Database::num_rows($resu)>0)
				{
					$rowu = Database::fetch_array($resu);
					$fval = $rowu['fval'];
					if($row['type'] ==  USER_FIELD_TYPE_SELECT_MULTIPLE)
					{
						$fval = split(';',$rowu['fval']);
					}
				}
				if($prefix)
				{
					$extra_data['extra_'.$row['fvar']] = $fval; 
				}
				else
				{
					$extra_data[$row['fvar']] = $fval; 
				}
			}
		}
				
		return $extra_data;
	}
		
	/**
	 * Get all the extra field information of a certain field (also the options)
	 * @param Int $field_name the name of the field we want to know everything of
	 * @return array $return containing all th information about the extra profile field 
	 * @author Julio Montoya
	 * @since Dokeos 1.8.6
	 */
	function get_extra_field_information_by_name($field_variable)
	{
		// database table definition
		$table_field 			= Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$table_field_options	= Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
		
		// all the information of the field
		$sql = "SELECT * FROM $table_field WHERE field_variable='".Database::escape_string($field_variable)."'";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		$return = Database::fetch_array($result);
		
		// all the options of the field
		$sql = "SELECT * FROM $table_field_options WHERE field_id='".Database::escape_string($return['id'])."' ORDER BY option_order ASC";
		$result = api_sql_query($sql,__FILE__,__LINE__);
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
	function get_extra_field_information($field_id)
	{
		// database table definition
		$table_field 			= Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$table_field_options	= Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
		
		// all the information of the field
		$sql = "SELECT * FROM $table_field WHERE id='".Database::escape_string($field_id)."'";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		$return = Database::fetch_array($result);
		
		// all the options of the field
		$sql = "SELECT * FROM $table_field_options WHERE field_id='".Database::escape_string($field_id)."' ORDER BY option_order ASC";
		$result = api_sql_query($sql,__FILE__,__LINE__);
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
	
	function get_extra_user_data_by_value($field_variable, $field_value, $all_visibility = true)
	{
		$extra_data = array();		
		$table_user_field = Database::get_main_table(TABLE_MAIN_USER_FIELD);
		$table_user_field_values = Database::get_main_table(TABLE_MAIN_USER_FIELD_VALUES);
		$table_user_field_options= Database::get_main_table(TABLE_MAIN_USER_FIELD_OPTIONS);
		$where='';
		/*
		if (is_array($field_variable_array) && is_array($field_value_array)) {
			if (count($field_variable_array) == count($field_value_array)) {
				$field_var_count = count($field_variable_array);
				for ($i = 0; $i<$field_var_count ; $i++) {
					if ($i!=0 && $i!=$field_var_count){
						$where.= ' AND ';
					}					
					$where.= "field_variable='".Database::escape_string($field_variable_array[$i])."' AND user_field_options.id='".Database::escape_string($field_value_array[$i])."'";	
				}	
			}
				
		}*/		
		$where= "field_variable='".Database::escape_string($field_variable)."' AND field_value='".Database::escape_string($field_value)."'";
		
		$sql = "SELECT user_id FROM $table_user_field user_field INNER JOIN $table_user_field_values user_field_values
				ON (user_field.id = user_field_values.field_id)								  
				WHERE $where";

		if($all_visibility == true) {
			$sql .= " AND user_field.field_visible = 1 ";
		} else {
			$sql .= " AND user_field.field_visible = 0 ";
		}			
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$result_data = array();
		if (Database::num_rows($res)>0) {
			while ($row = Database::fetch_array($res)) {
				$result_data[]=$row['user_id'];
			}
		}		
		return $result_data;
	}
	
	
	
	
	/**
	 * Gives a list of [session_id-course_code] => [status] for the current user.
	 * @param integer $user_id
	 * @return array  list of statuses (session_id-course_code => status)
	 */
	function get_personal_session_course_list($user_id)
	{
		// Database Table Definitions
		$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_course 				= Database :: get_main_table(TABLE_MAIN_COURSE);
		$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
		$tbl_session 				= Database :: get_main_table(TABLE_MAIN_SESSION);
		$tbl_session_user			= Database :: get_main_table(TABLE_MAIN_SESSION_USER);
		$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
		$tbl_session_course 		= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
		
		$user_id = Database::escape_string($user_id);
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
	
		//Courses in which we suscribed out of any session
		/*$personal_course_list_sql = "SELECT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i,
											course.tutor_name t, course.course_language l, course_rel_user.status s, course_rel_user.sort sort,
											course_rel_user.user_course_cat user_course_cat
											FROM    ".$tbl_course."       course,".$main_course_user_table."   course_rel_user
											WHERE course.code = course_rel_user.course_code"."
											AND   course_rel_user.user_id = '".$user_id."'
											ORDER BY course_rel_user.user_course_cat, course_rel_user.sort ASC,i";*/
	
		$tbl_user_course_category    = Database :: get_user_personal_table(TABLE_USER_COURSE_CATEGORY);
		
		$personal_course_list_sql = "SELECT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, course.tutor_name t, course.course_language l, course_rel_user.status s, course_rel_user.sort sort, course_rel_user.user_course_cat user_course_cat
										FROM    ".$tbl_course_user." course_rel_user
										LEFT JOIN ".$tbl_course." course
										ON course.code = course_rel_user.course_code
										LEFT JOIN ".$tbl_user_course_category." user_course_category
										ON course_rel_user.user_course_cat = user_course_category.id
										$join_access_url
										WHERE  course_rel_user.user_id = '".$user_id."'  $where_access_url
										ORDER BY i, user_course_category.sort, course_rel_user.sort ASC";
		
		$course_list_sql_result = api_sql_query($personal_course_list_sql, __FILE__, __LINE__);
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
		$result = api_sql_query($sessions_sql,__FILE__,__LINE__);
	
		$sessions=api_store_result($result);
	
		$sessions = array_merge($sessions , api_store_result($result));
	
		// get the list of sessions where the user is subscribed as coach in a course
		$sessions_sql = "SELECT DISTINCT id, name, date_start, date_end, DATE_SUB(date_start, INTERVAL nb_days_access_before_beginning DAY), ADDDATE(date_end, INTERVAL nb_days_access_after_end DAY)
								FROM $tbl_session as session
								INNER JOIN $tbl_session_course as session_rel_course
									ON session_rel_course.id_session = session.id
									AND session_rel_course.id_coach = $user_id
								WHERE 
									( CURDATE() >= DATE_SUB(date_start, INTERVAL nb_days_access_before_beginning DAY) AND
									  CURDATE() <= ADDDATE(date_end, INTERVAL nb_days_access_after_end DAY) OR 
									  date_start='0000-00-00')
								ORDER BY date_start, date_end, name";
		
		$result = api_sql_query($sessions_sql,__FILE__,__LINE__);
	
		$session_is_coach = api_store_result($result);
	
		$sessions = array_merge($sessions , $session_is_coach);
	
		// get the list of sessions where the user is subscribed as coach
		$sessions_sql = "SELECT DISTINCT id, name, date_start, date_end
								FROM $tbl_session as session
								WHERE session.id_coach = $user_id
                                AND
                                ( CURDATE() >= DATE_SUB(date_start, INTERVAL nb_days_access_before_beginning DAY) AND
                                  CURDATE() <= ADDDATE(date_end, INTERVAL nb_days_access_after_end DAY) OR
                                  date_start='0000-00-00')
								ORDER BY date_start, date_end, name";
		$result = api_sql_query($sessions_sql,__FILE__,__LINE__);
	
		$sessions = array_merge($sessions , api_store_result($result));
	
	
		if(api_is_allowed_to_create_course()) {
			foreach($sessions as $enreg) {
				$id_session = $enreg['id'];
				$personal_course_list_sql = "SELECT DISTINCT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, CONCAT(user.lastname,' ',user.firstname) t, email, course.course_language l, 1 sort, category_code user_course_cat, date_start, date_end, session.id as id_session, session.name as session_name
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
	
				$course_list_sql_result = api_sql_query($personal_course_list_sql, __FILE__, __LINE__);
	
				while ($result_row = Database::fetch_array($course_list_sql_result)) {
					$result_row['s'] = 2;
					$key = $result_row['id_session'].' - '.$result_row['k'];
					$personal_course_list[$key] = $result_row;
				}
			}
	
		}
	
		foreach($sessions as $enreg) {
			$id_session = $enreg['id'];
			$personal_course_list_sql = "SELECT DISTINCT course.code k, course.directory d, course.visual_code c, course.db_name db, course.title i, CONCAT(user.lastname,' ',user.firstname) t, email, course.course_language l, 1 sort, category_code user_course_cat, date_start, date_end, session.id as id_session, session.name as session_name, IF(session_course.id_coach = ".$user_id.",'2', '5')
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
	
			$course_list_sql_result = api_sql_query($personal_course_list_sql, __FILE__, __LINE__);
	
			while ($result_row = Database::fetch_array($course_list_sql_result)) {
				$key = $result_row['id_session'].' - '.$result_row['k'];
				$result_row['s'] = $result_row['14'];
	
				if(!isset($personal_course_list[$key])) {
					$personal_course_list[$key] = $result_row;
				}
			}
		}
		//print_r($personal_course_list);
		
		return $personal_course_list;
	}
	/**
	 * Get user id from a username
	 * @param	string	Username
	 * @return	int		User ID (or false if not found)
	 */
	function get_user_id_from_username($username) {
		$username = Database::escape_string($username);
		$t_user = Database::get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT user_id FROM $t_user WHERE username = '$username'";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		if ($res===false) { return false; }
		if (Database::num_rows($res)!==1) { return false; }
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
	function get_user_upload_files_by_course($user_id, $course, $column=2)
	{
		$path = api_get_path(SYS_COURSE_PATH).$course.'/document/shared_folder/sf_user_'.$user_id.'/';
		$web_path = api_get_path(WEB_COURSE_PATH).$course.'/document/shared_folder/sf_user_'.$user_id.'/';    	    	
		$file_list= array();	
		$return = '';
		if (is_dir($path)) {
			$handle = opendir($path);	
			while ($file = readdir($handle)) {
				if ($file == '.' || $file == '..' || $file == '.htaccess' || is_dir($path.$file))
					continue; // skip current/parent directory and .htaccess	
				$file_list[] = $file;
			}			
			if (count($file_list)>0) {
				$return = $course;
				$return .= '<ul>';		
			}
			foreach ($file_list as $file) {
				$return .= '<li><a href="'.$web_path.urlencode($file).'" target="_blank">'.htmlentities($file).'</a>';			
			}	
			$return .= '</ul>';		
		}		
		return $return;
	}
    /**
     * Gets the API key (or keys) and return them into an array
     * @param   int     Optional user id (defaults to the result of api_get_user_id())
     * @result  array   Non-indexed array containing the list of API keys for this user, or FALSE on error
     */
    function get_api_keys($user_id=null,$api_service='dokeos') {
    	if ($user_id != strval(intval($user_id))) return false;
        if (empty($user_id)) { $user_id = api_get_user_id(); }
        if ($user_id === false) return false;
        $service_name=Database::escape_string($api_service);
        if (is_string($service_name)===false) { return false;}
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT id, api_key FROM $t_api WHERE user_id = ".$user_id." AND api_service='".$api_service."';";
        $res = api_sql_query($sql,__FILE__,__LINE__);
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
    function add_api_key($user_id=null,$api_service='dokeos') {
        if ($user_id != strval(intval($user_id))) return false;
        if (empty($user_id)) { $user_id = api_get_user_id(); }
        if ($user_id === false) return false;
        $service_name=Database::escape_string($api_service);
        if (is_string($service_name)===false) { return false;}
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $md5 = md5((time()+($user_id*5))-rand(10000,10000)); //generate some kind of random key
        $sql = "INSERT INTO $t_api (user_id, api_key,api_service) VALUES ($user_id,'$md5','$service_name')";
        $res = api_sql_query($sql,__FILE__,__LINE__);
        if ($res === false) return false; //error during query
        $num = Database::insert_id();
        if ($num == 0) return false;
        return $num;    	
    }
    /**
     * Deletes an API key from the user's account
     * @param   int     API key's internal ID
     * @return  boolean True on success, false on failure
     */
    function delete_api_key($key_id) {
        if ($key_id != strval(intval($key_id))) return false;
        if ($key_id === false) return false;
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql = "SELECT * FROM $t_api WHERE id = ".$key_id;
        $res = api_sql_query($sql,__FILE__,__LINE__);
        if ($res === false) return false; //error during query
        $num = Database::num_rows($res);
        if ($num !== 1) return false;
        $sql = "DELETE FROM $t_api WHERE id = ".$key_id;        
        $res = api_sql_query($sql,__FILE__,__LINE__);
        if ($res === false) return false; //error during query
        return true;        
    }
    /**
     * Regenerate an API key from the user's account
     * @param   int     user ID (defaults to the results of api_get_user_id())
     * @param   string  API key's internal ID
     * @return  int		num
     */
    function update_api_key($user_id,$api_service) {
    	if ($user_id != strval(intval($user_id))) return false;
    	if ($user_id === false) return false;
        $service_name=Database::escape_string($api_service);
        if (is_string($service_name)===false) { return false;}
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql="SELECT id FROM $t_api WHERE user_id=".$user_id." AND api_service='".$api_service."'";
        $res=api_sql_query($sql,__FILE__,__LINE__);
        $num = Database::num_rows($res);
        if ($num==1) {
        	$id_key=Database::fetch_array($res,'ASSOC');
        	self::delete_api_key($id_key['id']);
        	$num=self::add_api_key($user_id,$api_service);
        } elseif ($num==0) {
        	$num=self::add_api_key($user_id);
        }
        return $num;
    }
    /**
     * @param   int     user ID (defaults to the results of api_get_user_id())
     * @param   string	API key's internal ID 
     * @return  int		row ID, not return a boolean
     */
    function get_api_key_id($user_id,$api_service) {
    	if ($user_id != strval(intval($user_id))) return false;
    	if ($user_id === false) return false;
        $service_name=Database::escape_string($api_service);
        if (is_string($service_name)===false) { return false;}
        $t_api = Database::get_main_table(TABLE_MAIN_USER_API_KEY);
        $sql="SELECT id FROM $t_api WHERE user_id=".$user_id." AND api_service='".$api_service."'";
        $res=api_sql_query($sql,__FILE__,__LINE__);
        $row=Database::fetch_array($res,'ASSOC');
        return $row['id'];
    }
    /**
     * Subscribes users to the given session and optionally (default) unsubscribes previous users
     * @param	int		Session ID
     * @param	array	List of user IDs
     * @param	bool	Whether to unsubscribe existing users (true, default) or not (false)
     * @return	void	Nothing, or false on error  
     */
    function suscribe_users_to_session($id_session,$UserList,$empty_users=true){
    	
    	if ($id_session!= strval(intval($id_session))) return false;
    	foreach($UserList as $intUser){
    		if ($intUser!= strval(intval($intUser))) return false;
    	}
    	$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
		$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    	$tbl_session_rel_user = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    	$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
    	$sql = "SELECT id_user FROM $tbl_session_rel_user WHERE id_session='$id_session'";
		$result = api_sql_query($sql,__FILE__,__LINE__);
		$existingUsers = array();
		while($row = Database::fetch_array($result)){
			$existingUsers[] = $row['id_user'];
		}
		$sql = "SELECT course_code FROM $tbl_session_rel_course WHERE id_session='$id_session'";
		$result=api_sql_query($sql,__FILE__,__LINE__);

		$CourseList=array();

		while($row=Database::fetch_array($result)) {
			$CourseList[]=$row['course_code'];
		}

		foreach ($CourseList as $enreg_course) {
			// for each course in the session
			$nbr_users=0;
            $enreg_course = Database::escape_string($enreg_course);
			// delete existing users
			if ($empty_users!==false) {
				foreach ($existingUsers as $existing_user) {
					if(!in_array($existing_user, $UserList)) {
						$sql = "DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course' AND id_user='$existing_user'";
						api_sql_query($sql,__FILE__,__LINE__);
	
						if(Database::affected_rows()) {
							$nbr_users--;
						}
					}
				}
			}
			// insert new users into session_rel_course_rel_user and ignore if they already exist
			foreach ($UserList as $enreg_user) {
				if(!in_array($enreg_user, $existingUsers)) {
                    $enreg_user = Database::escape_string($enreg_user);
					$insert_sql = "INSERT IGNORE INTO $tbl_session_rel_course_rel_user(id_session,course_code,id_user) VALUES('$id_session','$enreg_course','$enreg_user')";
					api_sql_query($insert_sql,__FILE__,__LINE__);

					if(Database::affected_rows()) {
						$nbr_users++;
					}
				}
			}
			// count users in this session-course relation
			$sql = "SELECT COUNT(id_user) as nbUsers FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code='$enreg_course'";
			$rs = api_sql_query($sql, __FILE__, __LINE__);
			list($nbr_users) = Database::fetch_array($rs);
			// update the session-course relation to add the users total
			$update_sql = "UPDATE $tbl_session_rel_course SET nbr_users=$nbr_users WHERE id_session='$id_session' AND course_code='$enreg_course'";
			api_sql_query($update_sql,__FILE__,__LINE__);
		}
		// delete users from the session
		if ($empty_users!==false){
			api_sql_query("DELETE FROM $tbl_session_rel_user WHERE id_session = $id_session",__FILE__,__LINE__);
		}
		// insert missing users into session
		$nbr_users = 0;
		foreach ($UserList as $enreg_user) {
            $enreg_user = Database::escape_string($enreg_user);
			$nbr_users++;
			$insert_sql = "INSERT IGNORE INTO $tbl_session_rel_user(id_session, id_user) VALUES('$id_session','$enreg_user')";
			api_sql_query($insert_sql,__FILE__,__LINE__);

		}
		// update number of users in the session
		$nbr_users = count($UserList);
		$update_sql = "UPDATE $tbl_session SET nbr_users= $nbr_users WHERE id='$id_session' ";
		api_sql_query($update_sql,__FILE__,__LINE__);
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
        if (Database::num_rows($res) === 1) {
        	return true;
        }
        return false;
    }
    /**
     * Get the total count of users
     * @return	mixed	Number of users or false on error
     */
    function get_number_of_users() {
        $t_u = Database::get_main_table(TABLE_MAIN_USER);
        $sql = "SELECT count(*) FROM $t_u";
        $res = Database::query($sql);
        if (Database::num_rows($res) === 1) {
        	return (int) Database::result($res,0,0);
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
	function resize_picture($file, $max_size_for_picture)
	{
	 	$temp = new image($file);
	 	$picture_infos=getimagesize($file);
		if ($picture_infos[0]>$max_size_for_picture) {		
			$thumbwidth = $max_size_for_picture;
			if (empty($thumbwidth) or $thumbwidth==0) {
				$thumbwidth=$max_size_for_picture;
			}
			$new_height = round(($thumbwidth/$picture_infos[0])*$picture_infos[1]);
			if($new_height > $max_size_for_picture)
			$new_height = $thumbwidth;
			$temp->resize($thumbwidth,$new_height,0);
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
    function get_picture_user($user_id, $picture_file, $height, $size_picture = 'medium_', $style = '') {
    	$patch_profile = 'upload/users/';
    	$picture = array();    	
    	$picture['style'] = $style;
    	if ($picture_file == 'unknown.jpg') {
    		$picture['file'] = api_get_path(WEB_CODE_PATH).'img/'.$picture_file; 
    		return $picture;
    	}
    	$file = api_get_path(SYS_CODE_PATH).$patch_profile.$user_id.'/'.$size_picture.$picture_file;
    	if(file_exists($file)) {
			$picture['file'] = api_get_path(WEB_CODE_PATH).$patch_profile.$user_id.'/'.$size_picture.$picture_file;
			$picture['style']=''; 
			if ($height > 0) {
				$dimension = @getimagesize(api_url_to_local_path($picture['file']));
				$margin = (($height - $dimension[1])/2);
				$picture['style'] = ' style="padding-top:'.$margin.'px; width:'.$dimension[0].'px; height:'.$dimension[1].';" ';
			}
		} else {
			$file = api_get_path(SYS_CODE_PATH).$patch_profile.$user_id.'/'.$picture_file;
			if (file_exists($file)) {
				$picture['file'] = api_get_path(WEB_CODE_PATH).$patch_profile.$user_id.'/'.$picture_file;
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
   	  function send_message_in_outbox ($email_administrator,$user_id,$title, $content) {
        global $charset;
		$table_message = Database::get_main_table(TABLE_MESSAGE);
		$table_user = Database::get_main_table(TABLE_MAIN_USER);		
        $title = api_convert_encoding($title,$charset,'UTF-8');
        $content = api_convert_encoding($content,$charset,'UTF-8');
		//message in inbox
		$sql_message_outbox='SELECT user_id from '.$table_user.' WHERE email="'.$email_administrator.'" ';
		//$num_row_query=Database::num_rows($sql_message_outbox);
		$res_message_outbox=Database::query($sql_message_outbox,__FILE__,__LINE__);
		$array_users_administrator=array();
		while ($row_message_outbox=Database::fetch_array($res_message_outbox,'ASSOC')) {
			$array_users_administrator[]=$row_message_outbox['user_id'];
		}
		//allow to insert messages in outbox
		for ($i=0;$i<count($array_users_administrator);$i++) {
			$sql_insert_outbox = "INSERT INTO $table_message(user_sender_id, user_receiver_id, msg_status, send_date, title, content ) ".
					" VALUES (".
			 		"'".(int)$user_id."', '".(int)($array_users_administrator[$i])."', '4', '".date('Y-m-d H:i:s')."','".Database::escape_string($title)."','".Database::escape_string($content)."'".
			 		")";
			$rs = Database::query($sql_insert_outbox,__FILE__,__LINE__);
		}

	} 
}
