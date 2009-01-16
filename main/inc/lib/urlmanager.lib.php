<?php // $Id: usermanager.lib.php 17705 2009-01-13 20:13:58Z herodoto $
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
// define constants for user extra field types

class UrlManager
{
	/**
	  * Creates a new access to Dokeos 
	  * @author Julio Montoya <gugli100@gmail.com>,
	  *
	  * @param	string	The URL of the site 
 	  * @param	string  The description of the site
 	  * @param	int		is active or not 		
	  * @param int     the user_id of the owner
	  * @return boolean if success
	  */
	function add($url, $description, $active)
	{		
		$tms = time();
		$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql = "INSERT INTO $access_url_table
                SET url = '".Database::escape_string($url)."/',
                description = '".Database::escape_string($description)."',
                active = '".Database::escape_string($active)."',
                created_by = '".Database::escape_string(api_get_user_id())."',
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
	* @return boolean if success 
	*/
	function udpate($url_id, $url, $description, $active) {
		$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$tms = time();		
		$sql = "UPDATE $access_url_table
                SET url = '".Database::escape_string($url)."',
                description = '".Database::escape_string($description)."',
                active = '".Database::escape_string($active)."',
                created_by = '".Database::escape_string(api_get_user_id())."',
                tms = FROM_UNIXTIME(".$tms.") WHERE id = '$url_id'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		return $result;
	}
		
	/**
	 * 
	 * */
	function url_exist($url) {		
		$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql = "SELECT id FROM $access_url_table WHERE url = '".Database::escape_string($url)."' ";	
		$res = api_sql_query($sql,__FILE__,__LINE__); 
		$num = Database::num_rows($res);		
		return $num;
	}
	
	/**
	 * 
	 * */
	function url_id_exist($url) {		
		$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
		$sql = "SELECT id FROM $access_url_table WHERE id = '".Database::escape_string($url)."' ";	
		$res = api_sql_query($sql,__FILE__,__LINE__); 
		$num = Database::num_rows($res);		
		return $num;
	}
	
	
	/**
	 * This function get the quantity of URL 
	 * @author Julio Montoya
	 * @return int count of urls
	 * */
	function url_count() {
		$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);	
		$sql = "SELECT count(id) as count_result FROM $access_url_table";
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
	function get_url_data() {
		$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);	
		$sql = "SELECT id ,  url , description, active  FROM $access_url_table";
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
	function get_url_data_from_id($url_id) {
		$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);	
		$sql = "SELECT id, url, description, active FROM $access_url_table WHERE id = ".Database::escape_string($url_id);
		$res = api_sql_query($sql, __FILE__, __LINE__);		
		$row = Database::fetch_array($res);		
		return $row;
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
		$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);					
		$sql= "DELETE FROM $access_url_table WHERE id = ".Database::escape_string($id)."";
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
		$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);					
		$sql= "SELECT user_id FROM $access_url_rel_user_table WHERE access_url_id = ".Database::escape_string($url_id)." AND  user_id = ".Database::escape_string($user_id)." ";
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
		$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
		$result_array=array();
				
		if (is_array($user_list) && is_array($url_list)){
			foreach ($url_list as $url_id) {				
				foreach ($user_list as $user_id) {
					$count = UrlManager::relation_url_user_exist($user_id,$url_id);															
					if ($count==0) {
						$sql = "INSERT INTO $access_url_rel_user_table
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
	 * @return boolean true  if success
	 * */
	function add_user_to_url($user_id,$url_id=1)
	{		
		$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);		
		$count = UrlManager::relation_url_user_exist($user_id,$url_id);
		if (empty($count)) {					
			$sql = "INSERT INTO $access_url_rel_user_table
           			SET user_id = ".Database::escape_string($user_id).", access_url_id = ".Database::escape_string($url_id);
			$result = api_sql_query($sql, __FILE__, __LINE__);
		}
		return $result; 		
	}
	
	function check_status($url)
	{			
		$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);		  
		$sql = "SELECT id FROM $access_url_table WHERE url = '".$url."'";
		$result = api_sql_query($sql); 
		$access_url_id = Database::result($result, 0, 0);
		return $access_url_id;
	}
	
}
?>