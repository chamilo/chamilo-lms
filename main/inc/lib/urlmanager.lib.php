<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>

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
*	This library provides functions for the access_url management.
*	Include/require it in your code to use its functionality.
*
*	@package dokeos.library
==============================================================================
*/
class UrlManager
{
	/**
	  * Creates a new url access to Dokeos
	  *
	  * @author Julio Montoya <gugli100@gmail.com>,
	  *
	  * @param	string	The URL of the site
 	  * @param	string  The description of the site
 	  * @param	int		is active or not
	  * @param  int     the user_id of the owner
	  * @return boolean if success
	  */
	function add($url, $description, $active)
	{
		$tms = time();
		$table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql = "INSERT INTO $table_access_url
                SET url 	= '".Database::escape_string($url)."',
                description = '".Database::escape_string($description)."',
                active 		= '".Database::escape_string($active)."',
                created_by 	= '".Database::escape_string(api_get_user_id())."',
                tms = FROM_UNIXTIME(".$tms.")";
		$result = Database::query($sql, __FILE__, __LINE__);
		return $result;
	}

	/**
	* Updates an URL access to Dokeos
	* @author Julio Montoya <gugli100@gmail.com>,
	*
	* @param	int 	The url id
	* @param	string  The description of the site
	* @param	int		is active or not
	* @param	int     the user_id of the owner
	* @return 	boolean if success
	*/
	function udpate($url_id, $url, $description, $active)
	{
		$table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$tms = time();
		$sql = "UPDATE $table_access_url
                SET url 	= '".Database::escape_string($url)."',
                description = '".Database::escape_string($description)."',
                active 		= '".Database::escape_string($active)."',
                created_by 	= '".Database::escape_string(api_get_user_id())."',
                tms 		= FROM_UNIXTIME(".$tms.")
                WHERE id = '$url_id'";
		$result = Database::query($sql, __FILE__, __LINE__);
		return $result;
	}


	/**
	* Deletes an url
	* @author Julio Montoya
	* @param int url id
	* @return boolean true if success
	* */
	function delete($id)
	{
		$table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql= "DELETE FROM $table_access_url WHERE id = ".Database::escape_string($id);
		$result = Database::query($sql,  __FILE__, __LINE__);
		return $result;
	}

	/**
	 *
	 * */
	function url_exist($url)
	{
		$table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql = "SELECT id FROM $table_access_url WHERE url = '".Database::escape_string($url)."' ";
		$res = Database::query($sql,__FILE__,__LINE__);
		$num = Database::num_rows($res);
		return $num;
	}

	/**
	 *
	 * */
	function url_id_exist($url)
	{
		$table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql = "SELECT id FROM $table_access_url WHERE id = '".Database::escape_string($url)."' ";
		$res = Database::query($sql,__FILE__,__LINE__);
		$num = Database::num_rows($res);
		return $num;
	}

	/**
	 * This function get the quantity of URLs
	 * @author Julio Montoya
	 * @return int count of urls
	 * */
	function url_count()
	{
		$table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql = "SELECT count(id) as count_result FROM $table_access_url";
		$res = Database::query($sql, __FILE__, __LINE__);
		$url = Database::fetch_array($res,'ASSOC');
		$result = $url['count_result'];
		return $result;
	}

	/**
	 * Gets the id, url, description, and active status of ALL URLs
	 * @author Julio Montoya
	 * @return array
	 * */
	function get_url_data()
	{
		$table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql = "SELECT id, url, description, active  FROM $table_access_url";
		$res = Database::query($sql, __FILE__, __LINE__);
		$urls = array ();
		while ($url = Database::fetch_array($res)) {
			$urls[] = $url;
		}
		return $urls;
	}

	/**
	 * Gets the id, url, description, and active status of ALL URLs
	 * @author Julio Montoya
	 * @return array
	 * */
	function get_url_data_from_id($url_id)
	{
		$table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql = "SELECT id, url, description, active FROM $table_access_url WHERE id = ".Database::escape_string($url_id);
		$res = Database::query($sql, __FILE__, __LINE__);
		$row = Database::fetch_array($res);
		return $row;
	}

	/** Gets the inner join of users and urls table
	 * @author Julio Montoya
	 * @return int  access url id
	 * @return array   Database::store_result of the result
	 * */
	function get_url_rel_user_data($access_url_id='')
	{
		$where = '';
		$table_url_rel_user	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		$tbl_user 			= Database :: get_main_table(TABLE_MAIN_USER);
		if (!empty($access_url_id)) {
			$where ="WHERE $table_url_rel_user.access_url_id = ".Database::escape_string($access_url_id);
		}
		$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname, username' : ' ORDER BY lastname, firstname, username';
		$sql="SELECT u.user_id, lastname, firstname, username, access_url_id
			FROM $tbl_user u
			INNER JOIN $table_url_rel_user
			ON $table_url_rel_user.user_id = u.user_id
			$where".$order_clause;
		$result=Database::query($sql,__FILE__,__LINE__);
		$users=Database::store_result($result);
		return $users;
	}


	 /** Gets the inner join of access_url and the course table
	 * @author Julio Montoya
	 * @return int  access url id
	 * @return array   Database::store_result of the result
	 * */
	function get_url_rel_course_data($access_url_id='')
	{
		$where ='';
		$table_url_rel_course	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
		$tbl_course 			= Database :: get_main_table(TABLE_MAIN_COURSE);

		if (!empty($access_url_id))
			$where ="WHERE $table_url_rel_course.access_url_id = ".Database::escape_string($access_url_id);

		$sql="SELECT course_code, title, access_url_id
				FROM $tbl_course u
				INNER JOIN $table_url_rel_course
				ON $table_url_rel_course.course_code = code
				$where
				ORDER BY title, code";

		$result=Database::query($sql,__FILE__,__LINE__);
		$courses=Database::store_result($result);
		return $courses;
	}

	/** Gets the inner join of access_url and the session table
	 * @author Julio Montoya
	 * @return int  access url id
	 * @return array   Database::store_result of the result
	 * */
	function get_url_rel_session_data($access_url_id='')
	{
		$where ='';
		$table_url_rel_session	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
		$tbl_session 			= Database :: get_main_table(TABLE_MAIN_SESSION);

		if (!empty($access_url_id))
			$where ="WHERE $table_url_rel_session.access_url_id = ".Database::escape_string($access_url_id);

		$sql="SELECT id, name, access_url_id
				FROM $tbl_session u
				INNER JOIN $table_url_rel_session
				ON $table_url_rel_session.session_id = id
				$where
				ORDER BY name, id";

		$result=Database::query($sql,__FILE__,__LINE__);
		$sessions=Database::store_result($result);
		return $sessions;
	}



	/**
	 * Sets the status of an URL 1 or 0
	 * @author Julio Montoya
	 * @param string lock || unlock
	 * @param int url id
	 * */
	function set_url_status($status, $url_id)
	{
		$url_table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		if ($status=='lock') {
			$status_db='0';
		}
		if ($status=='unlock') {
			$status_db='1';
		}
		if(($status_db=='1' OR $status_db=='0') AND is_numeric($url_id)) {
			$sql="UPDATE $url_table SET active='".Database::escape_string($status_db)."' WHERE id='".Database::escape_string($url_id)."'";
			$result = Database::query($sql, __FILE__, __LINE__);
		}
	}

	/**
	* Checks the relationship between an URL and a User (return the num_rows)
	* @author Julio Montoya
	* @param int user id
	* @param int url id
	* @return boolean true if success
	* */
	function relation_url_user_exist($user_id, $url_id)
	{
		$table_url_rel_user= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		$sql= "SELECT user_id FROM $table_url_rel_user WHERE access_url_id = ".Database::escape_string($url_id)." AND  user_id = ".Database::escape_string($user_id)." ";
		$result = Database::query($sql,  __FILE__, __LINE__);
		$num = Database::num_rows($result);
		return $num;
	}

	/**
	* Checks the relationship between an URL and a Course (return the num_rows)
	* @author Julio Montoya
	* @param int user id
	* @param int url id
	* @return boolean true if success
	* */
	function relation_url_course_exist($course_id, $url_id)
	{
		$table_url_rel_course= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
		$sql= "SELECT course_code FROM $table_url_rel_course WHERE access_url_id = ".Database::escape_string($url_id)." AND course_code = '".Database::escape_string($course_id)."'";
		$result = Database::query($sql,  __FILE__, __LINE__);
		$num = Database::num_rows($result);
		return $num;
	}


	/**
	* Checks the relationship between an URL and a Session (return the num_rows)
	* @author Julio Montoya
	* @param int user id
	* @param int url id
	* @return boolean true if success
	* */
	function relation_url_session_exist($session_id, $url_id)
	{
		$table_url_rel_session= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
		$sql= "SELECT session_id FROM $table_url_rel_session WHERE access_url_id = ".Database::escape_string($url_id)." AND session_id = ".Database::escape_string($session_id);
		$result = Database::query($sql,  __FILE__, __LINE__);
		$num = Database::num_rows($result);
		return $num;
	}


	/**
	 * Add a group of users into a group of URLs
	 * @author Julio Montoya
	 * @param  array of user_ids
	 * @param  array of url_ids
	 * */
	function add_users_to_urls($user_list, $url_list)
	{
		$table_url_rel_user= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		$result_array=array();

		if (is_array($user_list) && is_array($url_list)){
			foreach ($url_list as $url_id) {
				foreach ($user_list as $user_id) {
					$count = UrlManager::relation_url_user_exist($user_id,$url_id);
					if ($count==0) {
						$sql = "INSERT INTO $table_url_rel_user
		               			SET user_id = ".Database::escape_string($user_id).", access_url_id = ".Database::escape_string($url_id);
						$result = Database::query($sql, __FILE__, __LINE__);
						if($result)
							$result_array[$url_id][$user_id]=1;
						else
							$result_array[$url_id][$user_id]=0;
					}
				}
			}
		}
		return 	$result_array;
	}


	/**
	 * Add a group of courses into a group of URLs
	 * @author Julio Montoya
	 * @param  array of course ids
	 * @param  array of url_ids
	 * */
	function add_courses_to_urls($course_list,$url_list)
	{
		$table_url_rel_course= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
		$result_array=array();

		if (is_array($course_list) && is_array($url_list)){
			foreach ($url_list as $url_id) {
				foreach ($course_list as $course_code) {
					$count = UrlManager::relation_url_course_exist($course_code,$url_id);
					if ($count==0) {
						$sql = "INSERT INTO $table_url_rel_course
		               			SET course_code = '".Database::escape_string($course_code)."', access_url_id = ".Database::escape_string($url_id);
						$result = Database::query($sql, __FILE__, __LINE__);
						if($result)
							$result_array[$url_id][$course_code]=1;
						else
							$result_array[$url_id][$course_code]=0;
					}
				}
			}
		}
		return 	$result_array;
	}


	/**
	 * Add a group of sessions into a group of URLs
	 * @author Julio Montoya
	 * @param  array of session ids
	 * @param  array of url_ids
	 * */
	function add_sessions_to_urls($session_list,$url_list)
	{
		$table_url_rel_session= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
		$result_array=array();

		if (is_array($session_list) && is_array($url_list)){
			foreach ($url_list as $url_id) {
				foreach ($session_list as $session_id) {
					$count = UrlManager::relation_url_session_exist($session_id,$url_id);
					if ($count==0) {
						$sql = "INSERT INTO $table_url_rel_session
		               			SET session_id = ".Database::escape_string($session_id).", access_url_id = ".Database::escape_string($url_id);
						$result = Database::query($sql, __FILE__, __LINE__);
						if($result)
							$result_array[$url_id][$session_id]=1;
						else
							$result_array[$url_id][$session_id]=0;
					}
				}
			}
		}
		return 	$result_array;
	}



	/**
	 * Add a user into a url
	 * @author Julio Montoya
	 * @param  user_id
	 * @param  url_id
	 * @return boolean true if success
	 * */
	function add_user_to_url($user_id, $url_id=1)
	{
		$table_url_rel_user= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		if (empty($url_id)) $url_id=1;
		$count = UrlManager::relation_url_user_exist($user_id,$url_id);
		if (empty($count)) {
			$sql = "INSERT INTO $table_url_rel_user
           			SET user_id = ".Database::escape_string($user_id).", access_url_id = ".Database::escape_string($url_id);
			$result = Database::query($sql, __FILE__, __LINE__);
		}
		return $result;
	}

	function add_course_to_url($course_code, $url_id=1)
	{
		$table_url_rel_course= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
		if (empty($url_id)) $url_id=1;
		$count = UrlManager::relation_url_course_exist($course_code,$url_id);
		if (empty($count)) {
			$sql = "INSERT INTO $table_url_rel_course
           			SET course_code = '".Database::escape_string($course_code)."', access_url_id = ".Database::escape_string($url_id);
			$result = Database::query($sql, __FILE__, __LINE__);
		}
		return $result;
	}


	function add_session_to_url($session_id, $url_id=1)
	{
		$table_url_rel_session= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
		if (empty($url_id)) $url_id=1;
		$count = UrlManager::relation_url_session_exist($session_id,$url_id);
		if (empty($count)) {
			$sql = "INSERT INTO $table_url_rel_session
           			SET session_id = ".Database::escape_string($session_id).", access_url_id = ".Database::escape_string($url_id);
			$result = Database::query($sql, __FILE__, __LINE__);
		}
		return $result;
	}


	/**
	* Deletes an url and user relationship
	* @author Julio Montoya
	* @param int user id
	* @param int url id
	* @return boolean true if success
	* */
	function delete_url_rel_user($user_id, $url_id)
	{
		$table_url_rel_user= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		$sql= "DELETE FROM $table_url_rel_user WHERE user_id = ".Database::escape_string($user_id)." AND access_url_id=".Database::escape_string($url_id)."  ";
		$result = Database::query($sql,  __FILE__, __LINE__);
		return $result;
	}

	/**
	* Deletes an url and course relationship
	* @author Julio Montoya
	* @param  char  course code
	* @param  int url id
	* @return boolean true if success
	* */
	function delete_url_rel_course($course_code, $url_id)
	{
		$table_url_rel_course= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
		$sql= "DELETE FROM $table_url_rel_course WHERE course_code = '".Database::escape_string($course_code)."' AND access_url_id=".Database::escape_string($url_id)."  ";
		$result = Database::query($sql,  __FILE__, __LINE__);
		return $result;
	}

	/**
	* Deletes an url and session relationship
	* @author Julio Montoya
	* @param  char  course code
	* @param  int url id
	* @return boolean true if success
	* */
	function delete_url_rel_session($session_id, $url_id)
	{
		$table_url_rel_session = Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);
		$sql= "DELETE FROM $table_url_rel_session WHERE session_id = ".Database::escape_string($session_id)." AND access_url_id=".Database::escape_string($url_id)."  ";
		$result = Database::query($sql,  __FILE__, __LINE__);
		return $result;
	}


	/**
	 * Updates the access_url_rel_user table  with a given user list
	 * @author Julio Montoya
	 * @param array user list
	 * @param int access_url_id
	 * */
	function update_urls_rel_user($user_list,$access_url_id)
	{
		$table_access_url	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$table_url_rel_user	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

		$sql = "SELECT user_id FROM $table_url_rel_user WHERE access_url_id=".Database::escape_string($access_url_id);
		$result = Database::query($sql,__FILE__,__LINE__ );
		$existingUsers = array();

		while($row = Database::fetch_array($result)){
			$existingUsers[] = $row['user_id'];
		}

		//adding users
		foreach($user_list as $enreg_user) {
			if(!in_array($enreg_user, $existingUsers)) {
				UrlManager::add_user_to_url($enreg_user,$access_url_id);
			}
		}
		//deleting old users
		foreach($existingUsers as $existing_user) {
			if(!in_array($existing_user, $user_list)) {
				UrlManager::delete_url_rel_user($existing_user,$access_url_id);
			}
		}
	}


	/**
	 * Updates the access_url_rel_course table  with a given user list
	 * @author Julio Montoya
	 * @param array user list
	 * @param int access_url_id
	 * */
	function update_urls_rel_course($course_list,$access_url_id)
	{
		$table_course			= Database :: get_main_table(TABLE_MAIN_COURSE);
		$table_url_rel_course	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);

		$sql = "SELECT course_code FROM $table_url_rel_course WHERE access_url_id=".Database::escape_string($access_url_id);
		$result = Database::query($sql,__FILE__,__LINE__ );
		$existing_courses = array();

		while($row = Database::fetch_array($result)){
			$existing_courses[] = $row['course_code'];
		}

		//adding courses
		foreach($course_list as $course) {
			if(!in_array($course, $existing_courses)) {
				UrlManager::add_course_to_url($course,$access_url_id);
			}
		}

		//deleting old courses
		foreach($existing_courses as $existing_course) {
			if(!in_array($existing_course, $course_list)) {
				UrlManager::delete_url_rel_course($existing_course,$access_url_id);
			}
		}
	}

	/**
	 * Updates the access_url_rel_session table with a given user list
	 * @author Julio Montoya
	 * @param array user list
	 * @param int access_url_id
	 * */
	function update_urls_rel_session($session_list,$access_url_id)
	{
		$table_session	= Database :: get_main_table(TABLE_MAIN_SESSION);
		$table_url_rel_session	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_SESSION);

		$sql = "SELECT session_id FROM $table_url_rel_session WHERE access_url_id=".Database::escape_string($access_url_id);
		$result = Database::query($sql,__FILE__,__LINE__ );
		$existing_sessions = array();

		while($row = Database::fetch_array($result)){
			$existing_sessions[] = $row['session_id'];
		}

		//adding users
		foreach($session_list as $session) {
			if(!in_array($session, $existing_sessions)) {
				UrlManager::add_session_to_url($session,$access_url_id);
			}
		}

		//deleting old users
		foreach($existing_sessions as $existing_session) {
			if(!in_array($existing_session, $session_list)) {
				UrlManager::delete_url_rel_session($existing_session,$access_url_id);
			}
		}
	}


	function get_access_url_from_user($user_id) {
		$table_url_rel_user	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		$table_url	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql = "SELECT url, access_url_id FROM $table_url_rel_user url_rel_user INNER JOIN $table_url u
			    ON (url_rel_user.access_url_id = u.id)
			    WHERE user_id = ".Database::escape_string($user_id);
		$result = Database::query($sql,  __FILE__, __LINE__);
		$url_list = Database::store_result($result);
		return $url_list;
	}

	/**
	 *
	 * */
	function get_url_id($url)
	{
		$table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql = "SELECT id FROM $table_access_url WHERE url = '".Database::escape_string($url)."'";
		$result = Database::query($sql);
		$access_url_id = Database::result($result, 0, 0);
		return $access_url_id;
	}
}
?>