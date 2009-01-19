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
*	This library provides functions for the URL management.
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
                SET url 	= '".Database::escape_string($url)."/',
                description = '".Database::escape_string($description)."',
                active 		= '".Database::escape_string($active)."',
                created_by 	= '".Database::escape_string(api_get_user_id())."',
                tms = FROM_UNIXTIME(".$tms.")";
		$result = api_sql_query($sql, __FILE__, __LINE__);
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
		$result = api_sql_query($sql, __FILE__, __LINE__);
		return $result;
	}
		
	/**
	 * 
	 * */
	function url_exist($url)
	{		
		$table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql = "SELECT id FROM $table_access_url WHERE url = '".Database::escape_string($url)."' ";	
		$res = api_sql_query($sql,__FILE__,__LINE__); 
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
		$res = api_sql_query($sql,__FILE__,__LINE__); 
		$num = Database::num_rows($res);		
		return $num;
	}	
	
	/**
	 * This function get the quantity of URL 
	 * @author Julio Montoya
	 * @return int count of urls
	 * */
	function url_count() 
	{
		$table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);	
		$sql = "SELECT count(id) as count_result FROM $table_access_url";
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$url = Database::fetch_row($res);
		$result = $url['0'];	
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
		$res = api_sql_query($sql, __FILE__, __LINE__);
		$urls = array ();
		while ($url = Database::fetch_row($res))
		{
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
		$res = api_sql_query($sql, __FILE__, __LINE__);		
		$row = Database::fetch_array($res);		
		return $row;
	}
	
	/** Gets the inner join of users and urls table
	 * @author Julio Montoya
	 * @return int  access url id
	 * @return array   api_store_result of the result
	 * */
	function get_url_rel_user_data($access_url_id='')
	{
		$where ='';
		$table_url_rel_user	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);	
		$tbl_user 			= Database :: get_main_table(TABLE_MAIN_USER);
		
		if (!empty($access_url_id))
			$where ="WHERE $table_url_rel_user.access_url_id = ".Database::escape_string($access_url_id);
			
		$sql="SELECT u.user_id, lastname, firstname, username, access_url_id
				FROM $tbl_user u 
				INNER JOIN $table_url_rel_user
				ON $table_url_rel_user.user_id = u.user_id
				$where
				ORDER BY lastname,firstname,username";
	
		$result=api_sql_query($sql,__FILE__,__LINE__);	
		$Users=api_store_result($result);
		return $Users;		
	}
	
	
	/**
	 * Sets the status of an URL 1 or 0 
	 * @author Julio Montoya
	 * @param string lock || unlock
	 * @param int url id
	 * */
	function set_url_status($status,$url_id)
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
			$result = api_sql_query($sql, __FILE__, __LINE__);
		}		
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
		$sql= "DELETE FROM $table_access_url WHERE id = ".Database::escape_string($id)."";
		$result = api_sql_query($sql,  __FILE__, __LINE__);
		return $result;
	}
	
		
	/**
	* Deletes an url  
	* @author Julio Montoya
	* @param int user id
	* @param int url id
	* @return boolean true if success
	* */
	function relation_url_user_exist($user_id, $url_id)
	{
		$table_url_rel_user= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);					
		$sql= "SELECT user_id FROM $table_url_rel_user WHERE access_url_id = ".Database::escape_string($url_id)." AND  user_id = ".Database::escape_string($user_id)." ";
		$result = api_sql_query($sql,  __FILE__, __LINE__);
		$num = Database::num_rows($result);				
		return $num;
	}
	
	/**
	 * Add a group of users into a group of URLs
	 * @author Julio Montoya
	 * @param  array of user_ids
	 * @param  array of url_ids
	 * */
	function add_users_to_urls($user_list,$url_list)
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
						$result = api_sql_query($sql, __FILE__, __LINE__);
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
	 * Add a user into a url
	 * @author Julio Montoya
	 * @param  user_id
	 * @param  url_id
	 * @return boolean true if success
	 * */
	function add_user_to_url($user_id,$url_id=1)
	{		
		$table_url_rel_user= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);		
		$count = UrlManager::relation_url_user_exist($user_id,$url_id);
		if (empty($count)) {					
			$sql = "INSERT INTO $table_url_rel_user
           			SET user_id = ".Database::escape_string($user_id).", access_url_id = ".Database::escape_string($url_id);
			$result = api_sql_query($sql, __FILE__, __LINE__);
		}
		return $result; 		
	}
	
	
	/**
	* Deletes an url and user relationship
	* @author Julio Montoya
	* @param int url id
	* @return boolean true if success
	* */
	function delete_url_rel_user($user_id, $url_id)
	{
		$table_url_rel_user= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);					
		$sql= "DELETE FROM $table_url_rel_user WHERE user_id = ".Database::escape_string($user_id)." AND access_url_id=".Database::escape_string($url_id)."  ";
		$result = api_sql_query($sql,  __FILE__, __LINE__);
		return $result;
	} 
	
	/**
	 * Updates the url_rel_user table  with a given user list
	 * @author Julio Montoya
	 * @param array user list
	 * @param int access_url_id  
	 * */
	function update_urls_rel_user($user_list,$access_url_id)
	{
		$table_access_url	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$table_url_rel_user	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);		
				
		$sql = "SELECT user_id FROM $table_url_rel_user WHERE access_url_id=".Database::escape_string($access_url_id);
		$result = api_sql_query($sql,__FILE__,__LINE__ );
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
	 * 
	 * */
	function get_url_id($url)
	{			
		$table_access_url= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);		  
		$sql = "SELECT id FROM $table_access_url WHERE url = '".Database::escape_string($url)."'";
		$result = api_sql_query($sql); 
		$access_url_id = Database::result($result, 0, 0);
		return $access_url_id;
	}	
}
?>