<?php
/* For licensing terms, see /license.txt */
/**
*	This library provides functions for the group management.
*	Include/require it in your code to use its functionality.
*	@author Julio Montoya <gugli100@gmail.com>
*	@package chamilo.library
*/
// Group permissions
define('GROUP_PERMISSION_OPEN'	, '1');
define('GROUP_PERMISSION_CLOSED', '2');

// Group user permissions
define('GROUP_USER_PERMISSION_ADMIN'	,'1'); // the admin of a group
define('GROUP_USER_PERMISSION_READER'	,'2'); // a normal user
define('GROUP_USER_PERMISSION_PENDING_INVITATION'	,'3'); 	//   When an admin/moderator invites a user
define('GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER'	,'4'); // an user joins a group
define('GROUP_USER_PERMISSION_MODERATOR'	,'5'); // a moderator
define('GROUP_USER_PERMISSION_ANONYMOUS'	,'6'); // an anonymous user


define('GROUP_IMAGE_SIZE_ORIGINAL',	1);
define('GROUP_IMAGE_SIZE_BIG', 		2);
define('GROUP_IMAGE_SIZE_MEDIUM', 	3);
define('GROUP_IMAGE_SIZE_SMALL', 	4);

define('GROUP_TITLE_LENGTH',       50);

class GroupPortalManager
{
	/**
	  * Creates a new group
	  *
	  * @author Julio Montoya <gugli100@gmail.com>,
	  *
	  * @param	string	The URL of the site
 	  * @param	string  The description of the site
 	  * @param	int		is active or not
	  * @param  int     the user_id of the owner
	  * @return boolean if success
	  */
	public static function add($name, $description, $url, $visibility, $picture='')
	{
		$tms	= time();
		$table 	= Database :: get_main_table(TABLE_MAIN_GROUP);
		$sql 	= "INSERT INTO $table
                SET name 	= '".Database::escape_string($name)."',
                description = '".Database::escape_string($description)."',
                picture_uri = '".Database::escape_string($picture)."',
                url 		= '".Database::escape_string($url)."',
                visibility 	= '".Database::escape_string($visibility)."',
                created_on = FROM_UNIXTIME(".$tms."),
                updated_on = FROM_UNIXTIME(".$tms.")";
		$result = Database::query($sql);
		$return = Database::insert_id();
		return $return;
	}

	/**
	* Updates a group
	* @author Julio Montoya <gugli100@gmail.com>,
	*
	* @param	int 	The id
	* @param	string  The description of the site
	* @param	int		is active or not
	* @param	int     the user_id of the owner
	* @return 	boolean if success
	*/
	public static function update($group_id, $name, $description, $url, $visibility, $picture_uri)
	{
		$group_id = intval($group_id);
		$table = Database::get_main_table(TABLE_MAIN_GROUP);
		$tms = time();
		$sql = "UPDATE $table
             	SET name 	= '".Database::escape_string($name)."',
                description = '".Database::escape_string($description)."',
                picture_uri = '".Database::escape_string($picture_uri)."',
                url 		= '".Database::escape_string($url)."',
                visibility 	= '".Database::escape_string($visibility)."',
                updated_on 	= FROM_UNIXTIME(".$tms.")
                WHERE id = '$group_id'";
		$result = Database::query($sql);
		return $result;
	}


	/**
	* Deletes a group
	* @author Julio Montoya
	* @param int id
	* @return boolean true if success
	* */
	public static function delete($id)
	{
		$id = intval($id);
		$table = Database :: get_main_table(TABLE_MAIN_GROUP);
		$sql= "DELETE FROM $table WHERE id = ".Database::escape_string($id);
		$result = Database::query($sql);
		//deleting all relationship with users and groups
		self::delete_users($id);
		// delete group image
		self::delete_group_picture($id);
		return $result;
	}


	/**
	 * Gets data of all groups
	 * @author Julio Montoya
	 * @param int	visibility
	 * @param int	from which record the results will begin (use for pagination)
	 * @param int	number of items
	 * @return array
	 * */
	public static function get_all_group_data($visibility = GROUP_PERMISSION_OPEN, $from=0, $number_of_items=10)
	{
		$table	= Database :: get_main_table(TABLE_MAIN_GROUP);
		$visibility = intval($visibility);
		$user_condition = '';
		$sql = "SELECT name, description, picture_uri FROM $table WHERE visibility = $visibility ";
		$res = Database::query($sql);
		$data = array ();
		while ($item = Database::fetch_array($res)) {
			$data[] = $item;
		}
		return $data;
	}

	/**
	 * Gets the group data
	 *
	 *
	 */
	public static function get_group_data($group_id)
	{
		$table	= Database :: get_main_table(TABLE_MAIN_GROUP);
		$group_id = intval($group_id);
		$user_condition = '';
		$sql = "SELECT id, name, description, picture_uri, url, visibility  FROM $table WHERE id = $group_id ";
		$res = Database::query($sql);
		$item = array();
		if (Database::num_rows($res)>0) {
			$item = Database::fetch_array($res,'ASSOC');
		}
		return $item;
	}

	/**
	 * Gets the tags from a given group
	 * @param int	group id
	 * @param bool show group links or not
	 *
	 */
	public static function get_group_tags($group_id, $show_tag_links = true)
	{
		$tag					= Database :: get_main_table(TABLE_MAIN_TAG);
		$table_group_rel_tag	= Database :: get_main_table(TABLE_MAIN_GROUP_REL_TAG);
		$group_id 				= intval($group_id);
		$user_condition 		= '';

		$sql = "SELECT tag FROM $tag t INNER JOIN $table_group_rel_tag gt ON (gt.tag_id= t.id) WHERE gt.group_id = $group_id ";
		$res = Database::query($sql);
		$tags = array();
		if (Database::num_rows($res)>0) {
			while ($row = Database::fetch_array($res,'ASSOC')) {
				$tags[] = $row;
			}
		}

		if ($show_tag_links) {
			if (is_array($tags) && count($tags)>0) {
				foreach ($tags as $tag) {
					$tag_tmp[] = '<a href="'.api_get_path(WEB_PATH).'main/social/search.php?q='.$tag['tag'].'">'.$tag['tag'].'</a>';
				}
				if (is_array($tags) && count($tags)>0) {
					$tags= implode(', ',$tag_tmp);
				}
			} else {
				$tags = '';
			}
		}
		return $tags;
	}

	/** Gets the inner join from users and group table
	 * @return int  access url id
	 * @return array   Database::store_result of the result
	 * @author Julio Montoya
	 * */
	public static function get_groups_by_user($user_id = '', $relation_type = GROUP_USER_PERMISSION_READER, $with_image = false) {
		$where = '';
		$table_group_rel_user	= Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);
		$tbl_group				= Database::get_main_table(TABLE_MAIN_GROUP);
		$user_id 				= intval($user_id);

		if ($relation_type == 0) {
			$where_relation_condition = '';
		} else {
			$relation_type 			= intval($relation_type);
			$where_relation_condition = "AND gu.relation_type = $relation_type ";
		}

		$sql = "SELECT g.picture_uri, g.name, g.description, g.id , gu.relation_type
				FROM $tbl_group g
				INNER JOIN $table_group_rel_user gu
				ON gu.group_id = g.id WHERE gu.user_id = $user_id $where_relation_condition ORDER BY created_on desc ";

		$result=Database::query($sql);
		$array = array();
		if (Database::num_rows($result) > 0) {
			while ($row = Database::fetch_array($result, 'ASSOC')) {
				if ($with_image) {
					$picture = self::get_picture_group($row['id'], $row['picture_uri'],80);
					$img = '<img src="'.$picture['file'].'" />';
					$row['picture_uri'] = $img;
				}
				$array[$row['id']] = $row;
			}
		}
		return $array;
	}

	/** Gets the inner join of users and group table
	 * @return int  quantity of records
	 * @return bool show groups with image or not
	 * @return array  with group content
	 * @author Julio Montoya
	 * */
	public static function get_groups_by_popularity($num = 6, $with_image = true) {
		$where = '';
		$table_group_rel_user	= Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);
		$tbl_group				= Database::get_main_table(TABLE_MAIN_GROUP);
		if (empty($num)) {
			$num = 6;
		} else {
			$num = intval($num);
		}
		// only show admins and readers
		$where_relation_condition = " WHERE  gu.relation_type IN ('".GROUP_USER_PERMISSION_ADMIN."' , '".GROUP_USER_PERMISSION_READER."') ";
		$sql = "SELECT DISTINCT count(user_id) as count, g.picture_uri, g.name, g.description, g.id
				FROM $tbl_group g
				INNER JOIN $table_group_rel_user gu
				ON gu.group_id = g.id $where_relation_condition ORDER BY count DESC LIMIT $num";

		$result=Database::query($sql);
		$array = array();
		while ($row = Database::fetch_array($result, 'ASSOC')) {
			if ($with_image) {
				$picture = self::get_picture_group($row['id'], $row['picture_uri'],80);
				$img = '<img src="'.$picture['file'].'" />';
				$row['picture_uri'] = $img;
			}
			$array[$row['id']] = $row;
		}
		return $array;
	}

	/** Gets the last groups created
	 * @return int  quantity of records
	 * @return bool show groups with image or not
	 * @return array  with group content
	 * @author Julio Montoya
	 * */
	public static function get_groups_by_age($num = 6, $with_image = true) {
		$where = '';
		$table_group_rel_user	= Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);
		$tbl_group				= Database::get_main_table(TABLE_MAIN_GROUP);

		if (empty($num)) {
			$num = 6;
		} else {
			$num = intval($num);
		}		
		$where_relation_condition = " WHERE gu.relation_type IN ('".GROUP_USER_PERMISSION_ADMIN."' , '".GROUP_USER_PERMISSION_READER."') ";
		$sql = "SELECT DISTINCT count(user_id) as count, g.picture_uri, g.name, g.description, g.id
				FROM $tbl_group g
				INNER JOIN $table_group_rel_user gu
				ON gu.group_id = g.id $where_relation_condition ORDER BY created_on desc LIMIT $num ";
        
		$result=Database::query($sql);
		$array = array();
		while ($row = Database::fetch_array($result, 'ASSOC')) {
			if ($with_image) {
				$picture = self::get_picture_group($row['id'], $row['picture_uri'],80);
				$img = '<img src="'.$picture['file'].'" />';
				$row['picture_uri'] = $img;
			}
			$array[$row['id']] = $row;
		}
		return $array;
	}

	/**
	 * Gets the group's members
	 * @param int group id
	 * @param bool show image or not of the group
	 * @param array list of relation type use constants
	 * @param int from value
	 * @param int limit
	 * @param array image configuration, i.e array('height'=>'20px', 'size'=> '20px')
	 * @return array list of users in a group
	 */
	public static function get_users_by_group($group_id, $with_image = false, $relation_type = array(), $from = null, $limit = null, $image_conf = array('size'=>USER_IMAGE_SIZE_MEDIUM,'height'=>80)) {
		$where = '';
		$table_group_rel_user	= Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);
		$tbl_user				= Database::get_main_table(TABLE_MAIN_USER);
		$group_id 				= intval($group_id);
		
		if (empty($group_id)){
			return array();
		}		
		
		$limit_text = '';		
		if (isset($from) && isset($limit)) {
		    $from     = intval($from);
            $limit    = intval($limit);        
		    $limit_text = "LIMIT $from, $limit";
		}        

		if (count($relation_type) == 0) {
			$where_relation_condition = '';
		} else {
			$new_relation_type = array();
			foreach($relation_type as $rel) {
				$rel = intval($rel);
				$new_relation_type[] ="'$rel'";
			}
			$relation_type 			= implode(',', $new_relation_type);
			if (!empty($relation_type))
				$where_relation_condition = "AND gu.relation_type IN ($relation_type) ";
		}

		$sql = "SELECT picture_uri as image, u.user_id, u.firstname, u.lastname, relation_type 
    		    FROM $tbl_user u INNER JOIN $table_group_rel_user gu
    			ON (gu.user_id = u.user_id) 
    			WHERE gu.group_id= $group_id $where_relation_condition 
    			ORDER BY relation_type, firstname $limit_text";

		$result = Database::query($sql);
		$array  = array();
		while ($row = Database::fetch_array($result, 'ASSOC')) {
			if ($with_image) {
				$image_path   = UserManager::get_user_picture_path_by_id($row['user_id'], 'web', false, true);
				$picture      = UserManager::get_picture_user($row['user_id'], $image_path['file'], $image_conf['height'], $image_conf['size']);
				$row['image'] = '<img src="'.$picture['file'].'"  '.$picture['style'].'  />';
			}
			$array[$row['user_id']] = $row;
		}
		return $array;
	}

	/**
	 * Gets all the members of a group no matter the relationship for more specifications use get_users_by_group
	 * @param int group id
	 * @return array
	 */
	public static function get_all_users_by_group($group_id)
	{
		$table_group_rel_user	= Database::get_main_table(TABLE_MAIN_USER_REL_GROUP);
		$tbl_user				= Database::get_main_table(TABLE_MAIN_USER);
		$group_id 				= intval($group_id);

		if (empty($group_id)){
			return array();
		}
		$sql="SELECT u.user_id, u.firstname, u.lastname, relation_type FROM $tbl_user u
			INNER JOIN $table_group_rel_user gu
			ON (gu.user_id = u.user_id) WHERE gu.group_id= $group_id ORDER BY relation_type, firstname";

		$result=Database::query($sql);
		$array = array();
		while ($row = Database::fetch_array($result, 'ASSOC')) {
			$array[$row['user_id']] = $row;
		}
		return $array;
	}

	/**
	* Gets the relationship between a group and a User
	* @author Julio Montoya
	* @param int user id
	* @param int group_id
	* @return int 0 if there are not relationship otherwise returns the user group
	* */
	public static function get_user_group_role($user_id, $group_id)
	{
		$table_group_rel_user= Database :: get_main_table(TABLE_MAIN_USER_REL_GROUP);
		$return_value = 0;
		if (!empty($user_id) && !empty($group_id)) {
			$sql	= "SELECT relation_type FROM $table_group_rel_user WHERE group_id = ".intval($group_id)." AND  user_id = ".intval($user_id)." ";
			$result = Database::query($sql);
			if (Database::num_rows($result)>0) {
				$row = Database::fetch_array($result,'ASSOC');
				$return_value = $row['relation_type'];
			}
		}
		return $return_value;
	}


	/**
	 * Add a user into a group
	 * @author Julio Montoya
	 * @param  user_id
	 * @param  url_id
	 * @return boolean true if success
	 * */
	public static function add_user_to_group($user_id, $group_id, $relation_type = GROUP_USER_PERMISSION_READER) {
		$table_url_rel_group = Database :: get_main_table(TABLE_MAIN_USER_REL_GROUP);
		if (!empty($user_id) && !empty($group_id)) {
			$role = self::get_user_group_role($user_id,$group_id);
			if ($role == 0) {
				$sql = "INSERT INTO $table_url_rel_group
           				SET user_id = ".intval($user_id).", group_id = ".intval($group_id).", relation_type = ".intval($relation_type);
				$result = Database::query($sql);
			} elseif ($role == GROUP_USER_PERMISSION_PENDING_INVITATION) {
				//if somebody already invited me I can be added
				self::update_user_role($user_id, $group_id, GROUP_USER_PERMISSION_READER);
			}
		}
		return $result;
	}


	/**
	 * Add a group of users into a group of URLs
	 * @author Julio Montoya
	 * @param  array of user_ids
	 * @param  array of url_ids
	 * */
	public static function add_users_to_groups($user_list, $group_list, $relation_type = GROUP_USER_PERMISSION_READER) {
		$table_url_rel_group = Database :: get_main_table(TABLE_MAIN_USER_REL_GROUP);
		$result_array = array();
		$relation_type = intval($relation_type);

		if (is_array($user_list) && is_array($group_list)) {
			foreach ($group_list as $group_id) {
				foreach ($user_list as $user_id) {
					$role = self::get_user_group_role($user_id,$group_id);
					if ($role == 0) {
						$sql = "INSERT INTO $table_url_rel_group
		               			SET user_id = ".intval($user_id).", group_id = ".intval($group_id).", relation_type = ".intval($relation_type);

						$result = Database::query($sql);
						if ($result)
							$result_array[$group_id][$user_id]=1;
						else
							$result_array[$group_id][$user_id]=0;
					}
				}
			}
		}
		return 	$result_array;
	}

	/**
	* Deletes a group  and user relationship
	* @author Julio Montoya
	* @param int user id
	* @param int relation type (optional)
	* @return boolean true if success
	* */
	public static function delete_users($group_id,$relation_type='')
	{
		$table_	= Database :: get_main_table(TABLE_MAIN_USER_REL_GROUP);
		$condition_relation = "";
		if (!empty($relation_type)) {
			$relation_type = intval($relation_type);
			$condition_relation = " AND relation_type = '$relation_type'";
		}
		$sql	= "DELETE FROM $table_ WHERE group_id = ".intval($group_id).$condition_relation;
		$result = Database::query($sql);
		return $result;
	}

	/**
	* Deletes an url and session relationship
	* @author Julio Montoya
	* @param  char  course code
	* @param  int url id
	* @return boolean true if success
	* */
	public static function delete_user_rel_group($user_id, $group_id) {
		$table = Database :: get_main_table(TABLE_MAIN_USER_REL_GROUP);
		$sql= "DELETE FROM $table WHERE user_id = ".intval($user_id)." AND group_id=".intval($group_id)."  ";
		$result = Database::query($sql);
		return $result;
	}

	/**
	 * Updates the group_rel_user table  with a given user and group ids
	 * @author Julio Montoya
	 * @param int  user id
	 * @param int group id
	 * @param int relation type
	 * */
	public static function update_user_role($user_id, $group_id, $relation_type = GROUP_USER_PERMISSION_READER)
	{
		$table_group_rel_user	= Database :: get_main_table(TABLE_MAIN_USER_REL_GROUP);
		$group_id = intval($group_id);
		$user_id = intval($user_id);

		$sql = "UPDATE $table_group_rel_user
   				SET relation_type = ".intval($relation_type)." WHERE user_id = $user_id AND group_id = $group_id" ;
		$result = Database::query($sql);
	}


	public static function get_group_admin_list($user_id, $group_id)
	{
		$table_group_rel_user	= Database :: get_main_table(TABLE_MAIN_USER_REL_GROUP);
		$group_id = intval($group_id);
		$user_id = intval($user_id);

		$sql = "SELECT user_id FROM  $table_group_rel_user WHERE
   				relation_type = ".GROUP_USER_PERMISSION_ADMIN." AND user_id = $user_id AND group_id = $group_id" ;
		$result = Database::query($sql);
	}


	public static function get_all_group_tags($tag, $from=0, $number_of_items=10) {
		// database table definition

		$group_table 			= Database::get_main_table(TABLE_MAIN_GROUP);
		$table_tag				= Database::get_main_table(TABLE_MAIN_TAG);
		$table_group_tag_values	= Database::get_main_table(TABLE_MAIN_GROUP_REL_TAG);

		//default field_id == 1

		$field_id = 5;

		$tag = Database::escape_string($tag);
		$from = intval($from);
    	$number_of_items = intval($number_of_items);

		// all the information of the field
		$sql = "SELECT g.id, g.name, g.description, g.picture_uri FROM $table_tag t INNER JOIN $table_group_tag_values tv ON (tv.tag_id=t.id)
					 INNER JOIN $group_table g ON(tv.group_id =g.id)
				WHERE tag LIKE '$tag%' AND field_id= $field_id ORDER BY tag";

		$sql .= " LIMIT $from,$number_of_items";

		$result = Database::query($sql);
		$return = array();
		if (Database::num_rows($result)> 0) {
			while ($row = Database::fetch_array($result,'ASSOC')) {
				$return[$row['id']] = $row;
			}
		}

		$keyword = $tag;
		$sql = "SELECT  g.id, g.name, g.description, g.url, g.picture_uri FROM $group_table g";

		//@todo implement groups + multiple urls

		/*
		global $_configuration;
		if ($_configuration['multiple_access_urls'] && api_get_current_access_url_id()!=-1) {
			$access_url_rel_user_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
			$sql.= " INNER JOIN $access_url_rel_user_table url_rel_user ON (u.user_id=url_rel_user.user_id)";
		}*/

		//@todo implement visibility

		if (isset ($keyword)) {
			$sql .= " WHERE (g.name LIKE '%".$keyword."%' OR g.description LIKE '%".$keyword."%'  OR  g.url LIKE '%".$keyword."%' )";
		}

		$direction = 'ASC';
	    if (!in_array($direction, array('ASC','DESC'))) {
	    	$direction = 'ASC';
	    }

	    $column = intval($column);
	    $from = intval($from);
	    $number_of_items = intval($number_of_items);

		//$sql .= " ORDER BY col$column $direction ";
		$sql .= " LIMIT $from,$number_of_items";

		$res = Database::query($sql);
		if (Database::num_rows($res)> 0) {
			while ($row = Database::fetch_array($res,'ASSOC')) {
				if (!in_array($row['id'], $return)) {
					$return[$row['id']] = $row;
				}
			}
		}
		return $return;
	}


	/**
	 * Creates new group pictures in various sizes of a user, or deletes user pfotos.
	 * Note: This method relies on configuration setting from dokeos/main/inc/conf/profile.conf.php
	 * @param	int	The group id
	 * @param	string $file			The common file name for the newly created pfotos. It will be checked and modified for compatibility with the file system.
	 * If full name is provided, path component is ignored.
	 * If an empty name is provided, then old user photos are deleted only, @see UserManager::delete_user_picture() as the prefered way for deletion.
	 * @param	string		$source_file	The full system name of the image from which user photos will be created.
	 * @return	string/bool	Returns the resulting common file name of created images which usually should be stored in database.
	 * When deletion is recuested returns empty string. In case of internal error or negative validation returns FALSE.
	 */
	public static function update_group_picture($group_id, $file = null, $source_file = null) {

		// Validation 1.
		if (empty($group_id)) {
			return false;
		}
		$delete = empty($file);
		if (empty($source_file)) {
			$source_file = $file;
		}

		// Configuration options about user photos.
		require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';

		// User-reserved directory where photos have to be placed.
		$path_info = self::get_group_picture_path_by_id($group_id, 'system', true);
		$path = $path_info['dir'];
		// If this directory does not exist - we create it.
		if (!file_exists($path)) {
			@mkdir($path, api_get_permissions_for_new_directories(), true);
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
			$filename = $group_id.'_'.$filename;
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
				&& $normal->send_image($detected[$type], $path.'big_'.$filename)
				&& $big->send_image($detected[$type], $path.$filename);
		}
		return $ok ? $filename : false;
	}

	/**
	 * Gets the group picture URL or path from group ID (returns an array).
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
	public static function get_group_picture_path_by_id($id, $type = 'none', $preview = false, $anonymous = false) {

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

		$id = intval($id);

		$group_table = Database :: get_main_table(TABLE_MAIN_GROUP);
		$sql = "SELECT picture_uri FROM $group_table WHERE id=".$id;
		$res = Database::query($sql);

		if (!Database::num_rows($res)) {
			return $anonymous ? array('dir' => $base.'img/', 'file' => 'unknown.jpg') : array('dir' => '', 'file' => '');
		}

		$user = Database::fetch_array($res);
		$picture_filename = trim($user['picture_uri']);

		if (api_get_setting('split_users_upload_directory') === 'true') {
			if (!empty($picture_filename)) {
				$dir = $base.'upload/users/groups/'.substr($picture_filename, 0, 1).'/'.$id.'/';
			} elseif ($preview) {
				$dir = $base.'upload/users/groups/'.substr((string)$id, 0, 1).'/'.$id.'/';
			} else {
				$dir = $base.'upload/users/groups/'.$id.'/';
			}
		} else {
			$dir = $base.'upload/users/groups/'.$id.'/';
		}
		if (empty($picture_filename) && $anonymous) {
			return array('dir' => $base.'img/', 'file' => 'unknown.jpg');
		}
		return array('dir' => $dir, 'file' => $picture_filename);
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
     * Gets the current group image
     * @param string group id
     * @param string picture group name
     * @param string height
     * @param string picture size it can be small_,  medium_  or  big_
     * @param string style css
     * @return array with the file and the style of an image i.e $array['file'] $array['style']
     */
   public static function get_picture_group($id, $picture_file, $height, $size_picture = GROUP_IMAGE_SIZE_MEDIUM , $style = '') {
    	$patch_profile = 'upload/users/groups/';
    	$picture = array();
    	$picture['style'] = $style;
    	if ($picture_file == 'unknown.jpg') {
    		$picture['file'] = api_get_path(WEB_CODE_PATH).'img/'.$picture_file;
    		return $picture;
    	}

    	switch ($size_picture) {
    		case GROUP_IMAGE_SIZE_ORIGINAL :
    			$size_picture = '';
    		break;
    		case GROUP_IMAGE_SIZE_BIG :
    			$size_picture = 'big_';
    		break;
    		case GROUP_IMAGE_SIZE_MEDIUM :
    			$size_picture = 'medium_';
    		break;
    		case GROUP_IMAGE_SIZE_SMALL :
    			$size_picture = 'small_';
    		break;
    		default:
    			$size_picture = 'medium_';
    	}

        $image_array_sys = self::get_group_picture_path_by_id($id, 'system', false, true);
        $image_array = self::get_group_picture_path_by_id($id, 'web', false, true);
        $file = $image_array_sys['dir'].$size_picture.$picture_file;
    	if (file_exists($file)) {
            $picture['file'] = $image_array['dir'].$size_picture.$picture_file;
			$picture['style'] = '';
			if ($height > 0) {
				$dimension = api_getimagesize($picture['file']);
				$margin = (($height - $dimension[1]) / 2);
				//@ todo the padding-top should not be here
				$picture['style'] = ' style="padding-top:'.$margin.'px; width:'.$dimension[0].'px; height:'.$dimension[1].';" ';
			}
		} else {
			//$file = api_get_path(SYS_CODE_PATH).$patch_profile.$user_id.'/'.$picture_file;
            $file = $image_array_sys['dir'].$picture_file;
			if (file_exists($file) && !is_dir($file)) {
				$picture['file'] = $image_array['dir'].$picture_file;
			} else {
				$picture['file'] = api_get_path(WEB_CODE_PATH).'img/unknown_group.png';
			}
		}
		return $picture;
    }

	public static function delete_group_picture($group_id) {
		return self::update_group_picture($group_id);
	}


	public static function is_group_admin($group_id, $user_id = 0) {
		if (empty($user_id)) {
			$user_id = api_get_user_id();
		}
		$user_role	= GroupPortalManager::get_user_group_role($user_id, $group_id);
		if (in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN))) {
			return true;
		} else {
			return false;
		}
	}

	public static function is_group_moderator($group_id, $user_id = 0) {
		if (empty($user_id)) {
			$user_id = api_get_user_id();
		}
		$user_role	= GroupPortalManager::get_user_group_role($user_id, $group_id);
		if (in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR))) {
			return true;
		} else {
			return false;
		}
	}

	public static function is_group_member($group_id, $user_id = 0) {
		if (empty($user_id)) {
			$user_id = api_get_user_id();
		}
		$user_role	= GroupPortalManager::get_user_group_role($user_id, $group_id);
		if (in_array($user_role, array(GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR, GROUP_USER_PERMISSION_READER))) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Shows the left column of the group page
	 * @param int group id
	 * @param int user id
	 *
	 */
	public static function show_group_column_information($group_id, $user_id, $show = '') {

		global $relation_group_title, $my_group_role;

		$group_info 	= GroupPortalManager::get_group_data($group_id);
		$picture		= GroupPortalManager::get_picture_group($group_id, $group_info['picture_uri'],160,GROUP_IMAGE_SIZE_MEDIUM);
		$big_image		= GroupPortalManager::get_picture_group($group_id, $group_info['picture_uri'],'',GROUP_IMAGE_SIZE_BIG);
		$tags			= GroupPortalManager::get_group_tags($group_id, true);
		
		$groups_by_user	= GroupPortalManager::get_groups_by_user($user_id, 0);

		//my relation with the group is set here
		$my_group_role = self::get_user_group_role($user_id, $group_id);

		//@todo this must be move to default.css for dev use only
		echo '<style>
				#group_members { width:270px; height:300px; overflow-x:none; overflow-y: auto;}
				.group_member_item { width:100px; height:130px; float:left; margin:5px 5px 15px 5px; }
				.group_member_picture { display:block;
					margin:0;
					overflow:hidden; };
		</style>';

		//Loading group permission

		$links = '';
		switch ($my_group_role) {
			case GROUP_USER_PERMISSION_READER:
				// I'm just a reader
				$relation_group_title = get_lang('IamAReader');
				//$links .= '<li><a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="thickbox" title="'.get_lang('ComposeMessage').'">'.Display::return_icon('compose_message.png', get_lang('NewTopic'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('NewTopic').'</span></a></li>';
				//$links .=  '<li><a href="groups.php?id='.$group_id.'">'.				Display::return_icon('message_list.png', get_lang('MessageList'), array('hspace'=>'6')).'<span class="'.($show=='messages_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MessageList').'</span></a></li>';
				$links .=  '<li><a href="group_invitation.php?id='.$group_id.'">'.	Display::return_icon('invitation_friend.png', get_lang('InviteFriends'), array('hspace'=>'6')).'<span class="'.($show=='invite_friends'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('InviteFriends').'</span></a></li>';
				//$links .=  '<li><a href="group_members.php?id='.$group_id.'">'.		Display::return_icon('member_list.png', get_lang('MemberList'), array('hspace'=>'6')).'<span class="'.($show=='member_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MemberList').'</span></a></li>';
				$links .=  '<li><a href="groups.php?id='.$group_id.'&action=leave&u='.api_get_user_id().'">'.	Display::return_icon('group_leave.png', get_lang('LeaveGroup'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('LeaveGroup').'</span></a></li>';
				break;
			case GROUP_USER_PERMISSION_ADMIN:
				$relation_group_title = get_lang('IamAnAdmin');
				//$links .=  '<li><a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="thickbox" title="'.get_lang('ComposeMessage').'">'.Display::return_icon('compose_message.png', get_lang('NewTopic'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('NewTopic').'</span></a></li>';
				//$links .=  '<li><a href="groups.php?id='.$group_id.'">'.				Display::return_icon('message_list.png', get_lang('MessageList'), array('hspace'=>'6')).'<span class="'.($show=='messages_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MessageList').'</span></a></li>';
				$links .=  '<li><a href="group_edit.php?id='.$group_id.'">'.			Display::return_icon('group_edit.png', get_lang('EditGroup'), array('hspace'=>'6')).'<span class="'.($show=='group_edit'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('EditGroup').'</span></a></li>';
				//$links .=  '<li><a href="group_members.php?id='.$group_id.'">'.		Display::return_icon('member_list.png', get_lang('MemberList'), array('hspace'=>'6')).'<span class="'.($show=='member_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MemberList').'</span></a></li>';
				//if ($group_info['visibility'] == GROUP_PERMISSION_CLOSED) {
					$links .=  '<li><a href="group_waiting_list.php?id='.$group_id.'">'.	Display::return_icon('waiting_list.png', get_lang('WaitingList'), array('hspace'=>'6')).'<span class="'.($show=='waiting_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('WaitingList').'</span></a></li>';
				//}
				$links .=  '<li><a href="group_invitation.php?id='.$group_id.'">'.	Display::return_icon('invitation_friend.png', get_lang('InviteFriends'), array('hspace'=>'6')).'<span class="'.($show=='invite_friends'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('InviteFriends').'</span></a></li>';
				break;
			case GROUP_USER_PERMISSION_PENDING_INVITATION:
//				$links .=  '<li><a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('addd.gif', get_lang('YouHaveBeenInvitedJoinNow'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('YouHaveBeenInvitedJoinNow').'</span></a></li>';
				break;
			case GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER:
				$relation_group_title =  get_lang('WaitingForAdminResponse');
				break;
			case GROUP_USER_PERMISSION_MODERATOR:
				$relation_group_title = get_lang('IamAModerator');
				//$links .=  '<li><a href="'.api_get_path(WEB_CODE_PATH).'social/message_for_group_form.inc.php?view_panel=1&height=400&width=610&&user_friend='.api_get_user_id().'&group_id='.$group_id.'&action=add_message_group" class="thickbox" title="'.get_lang('ComposeMessage').'">'.Display::return_icon('compose_message.png', get_lang('NewTopic'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('NewTopic').'</span></a></li>';
				//$links .=  '<li><a href="groups.php?id='.$group_id.'">'.				Display::return_icon('message_list.png', get_lang('MessageList'), array('hspace'=>'6')).'<span class="'.($show=='messages_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MessageList').'</span></a></li>';
				//$links .=  '<li><a href="group_members.php?id='.$group_id.'">'.		Display::return_icon('member_list.png', get_lang('MemberList'), array('hspace'=>'6')).'<span class="'.($show=='member_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('MemberList').'</span></a></li>';
				if ($group_info['visibility'] == GROUP_PERMISSION_CLOSED) {
					$links .=  '<li><a href="group_waiting_list.php?id='.$group_id.'">'.	Display::return_icon('waiting_list.png', get_lang('WaitingList'), array('hspace'=>'6')).'<span class="'.($show=='waiting_list'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('WaitingList').'</span></a></li>';
				}
				$links .=  '<li><a href="group_invitation.php?id='.$group_id.'">'.	Display::return_icon('invitation_friend.png', get_lang('InviteFriends'), array('hspace'=>'6')).'<span class="'.($show=='invite_friends'?'social-menu-text-active':'social-menu-text4').'" >'.get_lang('InviteFriends').'</span></a></li>';
				break;
			default:
				//$links .=  '<li><a href="groups.php?id='.$group_id.'&action=join&u='.api_get_user_id().'">'.Display::return_icon('addd.gif', get_lang('JoinGroup'), array('hspace'=>'6')).'<span class="social-menu-text4" >'.get_lang('JoinGroup').'</a></span></li>';
			break;
		}

		if (!empty($links)) {
			echo '<div align="center" class="social-menu-title"><span class="social-menu-text1">'.cut($group_info['name'], GROUP_TITLE_LENGTH, true).'</span></div>';
			echo '<ul class="social-menu-groups">';
			echo $links;
			echo '</ul>';
		}

		
		/*
		// my other groups
		if (count($groups_by_user) > 1) {
			echo '<div align="center" class="social-menu-title"><span class="social-menu-text1">'.get_lang('MyOtherGroups').'</span></div>';
			echo '<div align="center">';
				$min_count_groups = 4;
				$i = 1;
				$more_link = false;
				foreach($groups_by_user as $group) {
					if ($group['id'] == $group_id) continue;
					if ($i > $min_count_groups) {
						$more_link = true;
						break;
					}
					$picture = GroupPortalManager::get_picture_group($group['id'], $group['picture_uri'],80);
					echo '<a href="groups.php?id='.$group['id'].'">';
					echo '<img height="44" border="2" align="middle" width="44" vspace="10" class="social-groups-image" src="'.$picture['file'].'"/>';
					echo '<div>'.cut($group['name'],50,true).'</div></a>';
					$i++;
				}
				if ($more_link) {
					//More link
					echo '<div class="mygroups_more" style="margin-top:20px;"><a href="groups.php?view=mygroups">'.get_lang('SeeMore').'</a></div>';
				}
			echo '</div>';
		}
		*/
	}
}
?>