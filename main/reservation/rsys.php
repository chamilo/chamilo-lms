<?php
// $Id: rsys.php,v 1.80 2006/05/12 08:48:49 sjacobs Exp $
/*
==============================================================================
    Dokeos - elearning and course management software

    Copyright (c) 2004-2008 Dokeos SPRL
    Copyright (c) Sebastien Jacobs (www.spiritual-coder.com)
    Copyright (c) Kristof Van Steenkiste 
    Copyright (c) Julio Montoya Armas

    For a full list of contributors, see "credits.txt".
    The full license can be read in "license.txt".

    This program is free software; you can redistribute it and/or
    modify it under the terms of the GNU General Public License
    as published by the Free Software Foundation; either version 2
    of the License, or (at your option) any later version.

    See the GNU General Public License for more details.

    Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
    Mail: info@dokeos.com
==============================================================================
*/
/**
    ---------------------------------------------------------------------
    The class-library with all reservation-system specific functionality
    ---------------------------------------------------------------------
 */
class Rsys {
	/**
	 *  Get required database-vars from inc/lib/database.lib.php and load them into the $GLOBALS['_rsys']-array
	 * 
	 */
	function init() {
		// reservation database tables 
		$GLOBALS['_rsys']['dbtables']['item'] 		  	= Database :: get_main_table(TABLE_MAIN_RESERVATION_ITEM);
		$GLOBALS['_rsys']['dbtables']['reservation']  	= Database :: get_main_table(TABLE_MAIN_RESERVATION_RESERVATION);
		$GLOBALS['_rsys']['dbtables']['subscription'] 	= Database :: get_main_table(TABLE_MAIN_RESERVATION_SUBSCRIBTION);
		$GLOBALS['_rsys']['dbtables']['category'] 	 	= Database :: get_main_table(TABLE_MAIN_RESERVATION_CATEGORY);
		$GLOBALS['_rsys']['dbtables']['item_rights'] 	= Database :: get_main_table(TABLE_MAIN_RESERVATION_ITEM_RIGHTS);
	}

	/**
	 *  Get the full tag for a reservation specific database table
	 * 
	 *  @param  -   String  $table      The table-name
	 */
	function getTable($table) {
		return $GLOBALS['_rsys']['dbtables'][$table];
	}

	/**
	 *  Get number of subscriptions of a reservationperiod
	 * 
	 *  @return -   int     The amount of subscriptions
	 */
	function get_num_subscriptions_reservationperiods($res_id) {
		$sql = "SELECT COUNT(*) FROM ".Rsys :: getTable("subscription")." s 
			WHERE s.reservation_id = '".$res_id."'";
		return @ Database::result(api_sql_query($sql, __FILE__, __LINE__), 0, 0);
	}

	/**
	 *  Validates the access to a certain reservation-script
	 * 
	 *  @param  -   String  $section    The section (= script-file)
	 *  @param  -   int     $id         An id (sometimes this is required to get rights for a unique row in the database)
	 */
	function protect_script($section, $id = null) {
		$uid = api_get_user_id();
		switch ($section) {
			case 'm_item' :
				if (!api_is_platform_admin() && Rsys :: check_user_status() <> 1)
					api_protect_admin_script();
				break;
			case 'm_category' :
				api_protect_admin_script();
				break;
			case 'm_reservation' :
				if (!api_is_platform_admin() && Rsys :: check_user_status() <> 1)
					api_protect_admin_script();
				break;
		}
	}

	/**
	 *  Formats a message with a goto-link
	 *  
	 *  @param  -   String  $msg        The message
	 *  @param  -   String  $page       The page-script
	 *  @param  -   String  $pageheader The tag to display as link
	 */
	function get_return_msg($msg, $page, $pageheader) {
		$target_url = api_get_path(WEB_PATH).'main/reservation/'.$page;
		$return = get_lang('GoTo');
		return $msg."<br /><br /><a href=\"$target_url\">$return $pageheader</a>";
	}

	/**
	 *  Formats a message with a goto-link
	 *  
	 *  @param  -   String  $msg        The message
	 *  @param  -   String  $page       The page-script
	 *  @param  -   String  $pageheader The tag to display as link
	 */
	function get_return_msg2($msg, $page, $pageheader) {
		$return = get_lang('GoTo');
		return $msg."<br /><br /><a href=\"$page\">$return $pageheader</a>";
	}

	/**
	 *  Returns a timestamp from a mysql DATETIME
	 *  
	 *  @param  -   String  $dt     DATETIME (0000-00-00 00:00:00)
	 *  @return -   int             timestamp
	 */
	function mysql_datetime_to_timestamp($dt) {
		$yr = strval(substr($dt, 0, 4));
		$mo = strval(substr($dt, 5, 2));
		$da = strval(substr($dt, 8, 2));
		$hr = strval(substr($dt, 11, 2));
		$mi = strval(substr($dt, 14, 2));
		$se = strval(substr($dt, 17, 2));
		return mktime($hr, $mi, $se, $mo, $da, $yr);
	}

	function mysql_datetime_to_array($dt) {
		$offset = strpos($dt, '-');
		$dat['year'] = strval(substr($dt, 0, $offset));
		$dat['month'] = strval(substr($dt, $offset +1, strpos($dt, '-', $offset +1) - ($offset +1)));
		$offset = strpos($dt, '-', $offset +1);
		$dat['day'] = strval(substr($dt, $offset +1, strpos($dt, ' ', $offset +1) - ($offset +1)));
		$offset = strpos($dt, ' ', $offset +1);
		$dat['hour'] = strval(substr($dt, $offset +1, strpos($dt, ':', $offset +1) - ($offset +1)));
		$offset = strpos($dt, ':', $offset +1);
		$dat['minute'] = strval(substr($dt, $offset +1, strpos($dt, ':', $offset +1) - ($offset +1)));
		$offset = strpos($dt, ':', $offset +1);
		$dat['second'] = strval(substr($dt, $offset +1, strlen($dt) - $offset +1));
		return $dat;
	}

	function timestamp_to_datetime($timestamp) {
		return date('Y-m-d H:i:s', $timestamp);
	}

	function check_user_status() {
		$user_info = api_get_user_info(api_get_user_id());
		return $user_info['status'];
	}
	/*
	 ============================================================================================
	
	                                    CATEGORIES
	    
	 ============================================================================================
	*/

	/**
	 *  Adds a category
	 *  
	 *  @param  -   String  $name   The name
	 *  @return -   int             The id
	 */
	function add_category($naam) {
		if (Rsys :: check_category($naam)) {
			$sql = "INSERT INTO ".Rsys :: getTable("category")." (name) VALUES ('".Database::escape_string($naam)."')";
			api_sql_query($sql, __FILE__, __LINE__);
			return Database::get_last_insert_id();
		}
		return false;
	}

	/**
	 *  Controls if the category already exists
	 *  
	 *  @param  -   String  $name   The name
	 *  @return -   boolean         True or False
	 */
	function check_category($name, $id=0) {
		$sql = "SELECT name FROM ".Rsys :: getTable("category")." WHERE LCASE(name)='".strtolower($name)."' AND id<>".Database::escape_string($id)."";
		$Result = api_sql_query($sql, __FILE__, __LINE__);
		return (Database::num_rows($Result) == 0);
	}

	/**
	 *  Edits a category
	 *  
	 *  @param  -   String  $name   The name
	 *  @param  -   int     $id     The id
	 */
	function edit_category($id, $name) {
		if (Rsys :: check_category($name, $id)) {
			$sql = "UPDATE ".Rsys :: getTable("category")." SET name = '".Database::escape_string($name)."' WHERE id =".Database::escape_string($id)."";
			api_sql_query($sql, __FILE__, __LINE__);
			return $id;
		}
		return false;
	}

	/**
	 *  Deletes a category
	 *  
	 *  @param  -   int     $id     The id
	 */
	function delete_category($id) {
		$sql = "SELECT id FROM ".Rsys :: getTable("item")." WHERE category_id=".Database::escape_string($id)."";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if (Database::num_rows($result) == 0) {
			$sql2 = "DELETE FROM ".Rsys :: getTable("category")." WHERE id =".Database::escape_string($id)."";
			api_sql_query($sql2, __FILE__, __LINE__);
			return 0;
		} else {
			return Database::num_rows($result);
		}

	}

	/**
	 *  Gets a category from database (give no param to get ALL categories)
	 * 
	 *  @param  -   int     $id         The id of the category
	 *  @param  -   String  $orderby    (sql) ORDER BY $orderby
	 *  @return -   Array               One or all rows of the category-table
	 */
	function get_category($id = null, $orderby = "name ASC") {
		$sql = "SELECT * FROM ".Rsys :: getTable("category");
		if (!empty ($id))
			$sql .= " WHERE id = ".Database::escape_string($id)."";
		else
			$sql .= " ORDER BY ".$orderby;
		$arr = api_store_result(api_sql_query($sql, __FILE__, __LINE__));
		if (!empty ($id))
			return $arr[0];
		else
			return $arr;
	}

	/**
	 *  Gets all categories that have items in them (for the current user)
	
	 *  @param  -   String  $orderby    (sql) ORDER BY $orderby
	 *  @return -   Array               All rows of the category-table that have items
	 */
	function get_category_with_items($orderby = "c.name ASC") {
		$sql = "SELECT c.* FROM ".Rsys :: getTable("category")." c 
		                INNER JOIN ".Rsys :: getTable("item")." i ON i.category_id =c.id
		                LEFT JOIN ".Rsys :: getTable("item_rights")." ir ON ir.item_id=i.id 
		                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." cl ON ir.class_id=cl.id AND ir.item_id = i.id 
		                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = cl.id 
		                WHERE (cu.user_id='".api_get_user_id()."' AND ir.view_right=1) OR i.creator='".api_get_user_id()."'  OR 1=". (api_is_platform_admin() ? 1 : 0)."
		                GROUP BY c.id ORDER BY ".$orderby;
		
		$arr = api_store_result(api_sql_query($sql, __FILE__, __LINE__));
		return $arr;
	}
	
	/**
	 *  Gets all categories that have items in them (for the current user)
	
	 *  @param  -   String  $orderby    (sql) ORDER BY $orderby
	 *  @return -   Array               All rows of the category-table that have items
	 */
	function get_category_with_items_manager($orderby = "c.name ASC") {
		$sql = "SELECT c.* FROM ".Rsys :: getTable("category")." c 
		                INNER JOIN ".Rsys :: getTable("item")." i ON i.category_id =c.id
		                LEFT JOIN ".Rsys :: getTable("item_rights")." ir ON ir.item_id=i.id 
		                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." cl ON ir.class_id=cl.id AND ir.item_id = i.id 
		                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = cl.id 
		                WHERE (cu.user_id='".api_get_user_id()."' AND (ir.edit_right=1 OR ir.delete_right=1)) OR i.creator='".api_get_user_id()."'  OR 1=". (api_is_platform_admin() ? 1 : 0)."
		                GROUP BY c.id ORDER BY ".$orderby;
		
		$arr = api_store_result(api_sql_query($sql, __FILE__, __LINE__));
		return $arr;
	}

	/**
	 *  Returns categories for a sortable table based on the params
	 * 
	 *  @param  -   int     $from       Index of the first item to return.
	 *  @param  -   int     $per_page   The number of items to return
	 *  @param  -   int     $column     The number of the column on which the data should be sorted
	 *  @param  -   String  $direction  In which order should the data be sorted (ASC or DESC)
	 */
	function get_table_categories($from, $per_page, $column, $direction) {
		$sql = "SELECT id AS col0, name as col1, id AS col2 FROM ".Rsys :: getTable("category");
		if (isset ($_GET['keyword'])) {
			$keyword = Database::escape_string($_GET['keyword']);
			$sql .= " WHERE name LIKE '%".Database::escape_string($keyword)."%' OR id LIKE '%".Database::escape_string($keyword)."%'";
		}
		$sql .= " ORDER BY col".$column." ".$direction." LIMIT ".$from.",".$per_page;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result, 'NUM'))
			$arr[] = $array;
		return $arr;
	}

	/**
	 *  Get number of categories
	 * 
	 *  @return -   int     The amount of categories
	 */
	function get_num_categories() {
		$sql = "SELECT COUNT(id) FROM ".Rsys :: getTable("category");
		if (isset ($_GET['keyword'])) {
			$keyword = Database::escape_string($_GET['keyword']);
			$sql .= " WHERE name LIKE '%".Database::escape_string($keyword)."%' OR id LIKE '%".Database::escape_string($keyword)."%'";
		}
		return @ Database::result(api_sql_query($sql, __FILE__, __LINE__), 0, 0);
	}

	/*
	 ============================================================================================
	
	                                    ITEMS
	    
	 ============================================================================================
	*/

	/**
	 * 	Controls if an item in a certain category already exist
	 *  
	 *  @param  -   String  $name           The name
	 *  @param  -   String  $category 		The category id
	 *  @return -   Boolean               	True or false
	 */
	function check_item($item, $category, $id=0) {
		$sql = "SELECT name FROM ".Rsys :: getTable("item")." 
							WHERE LCASE(name)='".strtolower(Database::escape_string($item))."' 
							AND category_id=".Database::escape_string($category)."
							AND id<>".Database::escape_string($id)."";
		$Result = api_sql_query($sql, __FILE__, __LINE__);
		return (Database::num_rows($Result) == 0);
	}

	/**
	 *  Adds an item
	 *  
	 *  @param  -   String  $name           The name
	 *  @param  -   String  $description    The description
	 *  @param  -   int     $category       The category-ID
	 *  @param  -   String     $courseCode  The course-Code (optional)
	 *  @return -   int                     The id
	 */
	function add_item($name, $description, $category, $course = "") {
		if (Rsys :: check_item($name, $category)) {
			$sql = "INSERT INTO ".Rsys :: getTable("item")." (category_id,course_code,name,description,creator) VALUES ('".Database::escape_string($category)."','".Database::escape_string($course)."','".Database::escape_string($name)."','".Database::escape_string($description)."','".api_get_user_id()."')";
			api_sql_query($sql, __FILE__, __LINE__);
			return Database::get_last_insert_id();
		}
		return false;
	}

	/**
	 *  Edits an item
	 *  
	 *  @param  -   int     $id             The id
	 *  @param  -   String  $name           The name
	 *  @param  -   String  $description    The description
	 *  @param  -   int     $category       The category-ID
	 *  @param  -   String     $courseCode  The course-Code (optional)
	 */
	function edit_item($id, $name, $description, $category, $course = "") {
		if (!Rsys :: item_allow($id, 'edit'))
			return false;
		if (!Rsys :: check_item($name, $category, $id))
			return false;
		$sql = "UPDATE ".Rsys :: getTable("item")." SET category_id='".Database::escape_string($category)."',course_code='".Database::escape_string($course)."',name='".Database::escape_string($name)."',description='".Database::escape_string($description)."' " .
			   "WHERE id =".Database::escape_string($id)."";
		api_sql_query($sql, __FILE__, __LINE__);
		return $id;
	}

	/**
	 *  Deletes an item and all linked item-rights
	 *  
	 *  @param  -   int     $id     The id
	 */
	function delete_item($id) {
		if (!Rsys :: item_allow($id, 'delete'))
			return false;
		$sql = "SELECT id,end_at FROM".Rsys :: getTable('reservation')." WHERE item_id=".Database::escape_string($id)."";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result)) {
			if (Rsys :: mysql_datetime_to_timestamp(date('Y-m-d H:i:s')) <= Rsys :: mysql_datetime_to_timestamp($array[1]))
				$checked = true;
		}
		if (!$checked) {
			$sql = "DELETE FROM ".Rsys :: getTable("item")." WHERE id =".Database::escape_string($id)."";
			api_sql_query($sql, __FILE__, __LINE__);
			$sql = "DELETE FROM ".Rsys :: getTable("item_rights")." WHERE item_id =".Database::escape_string($id)."";
			api_sql_query($sql, __FILE__, __LINE__);
			$sql = "DELETE FROM ".Rsys :: getTable("reservation")." WHERE item_id =".Database::escape_string($id)."";
			api_sql_query($sql, __FILE__, __LINE__);
			return '0';
		} else {
			return Database::num_rows($result);
		}

	}

	function item_allow($item_id, $right) {
		// Creator heeft alle rechten
		switch ($right) {
			case 'm_rights' : // manage rights of item (only for creator and admin)
				$x = '';
				break;
			case 'edit' :
				$x = ' ir.edit_right=1 ';
				break;
			case 'delete' :
				$x = ' ir.delete_right=1 ';
				break;
			case 'm_reservation' :
				$x = ' ir.m_reservation=1 ';
				break;
			case 'view' :
				$x = ' ir.view_right=1 ';
				break;
		}
		$sql = "SELECT i.id FROM ".Rsys :: getTable("item")." i  
		        		LEFT JOIN ".Rsys :: getTable("item_rights")." ir ON ir.item_id=i.id 
		                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id 
		                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
		                WHERE i.id='".$item_id."' AND (". (!empty ($x) ? "(cu.user_id='".api_get_user_id()."' AND ".$x.") OR " : '')." i.creator='".api_get_user_id()."'  OR 1=". (api_is_platform_admin() ? 1 : 0).")";
		return Database::num_rows(api_sql_query($sql, __FILE__, __LINE__)) > 0;
	}

	/**
	 *  Gets an item from the database (give no param to get ALL items)
	 * 
	 *  @param  -   int     $id         The id of the item
	 *  @param  -   String  $orderby    (sql) ORDER BY $orderby
	 *  @return -   Array               The returned rows
	 */
	function get_item($id = null, $orderby = "name ASC") {
		$sql = "SELECT i.* FROM ".Rsys :: getTable("item")." i";
		if (!empty ($id)) {
			if (!Rsys :: item_allow($id, 'view'))
				return false;
			$sql .= " WHERE i.id = '".$id."'";
		} else
			$sql .= " LEFT JOIN ".Rsys :: getTable("item_rights")." ir ON ir.item_id=i.id LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id WHERE (cu.user_id='".api_get_user_id()."' AND ir.view_right=1) OR i.creator='".api_get_user_id()."'  OR 1=". (api_is_platform_admin() ? 1 : 0)."  ORDER BY ".$orderby;
		$arr = api_store_result(api_sql_query($sql, __FILE__, __LINE__));
		if (!empty ($id))
			return $arr[0]; // Return one row only
		else
			return $arr;
	}

	/**
	 *  Returns the blackout-status for an item
	 * 
	 *  @param  -   int     $itemid    The id of the item
	 *  @return -   boolean             true if blackout, false if not
	 */
	function is_blackout($itemid) {
		$sql = "SELECT id FROM ".Rsys :: getTable("item");
		$sql .= " WHERE id = ".Database::escape_string($itemid)." AND blackout=1";
		return Database::num_rows(api_sql_query($sql, __FILE__, __LINE__)) == 1;
	}

	/**
	 *  Gets all items of a certain category from the database
	 * 
	 *  @param  -   int     $id         The id of the category
	 *  @param  -   String  $orderby    (sql) ORDER BY $orderby
	 *  @return -   Array               The returned rows
	 */
	function get_category_items($id, $orderby = "name ASC") {
		$sql = "SELECT * FROM ".Rsys :: getTable("item")." WHERE category_id = ".Database::escape_string($id)." ORDER BY ".$orderby;
		$arr = api_store_result(api_sql_query($sql, __FILE__, __LINE__));
		return $arr;
	}

	/**
	 *  Gets all items of a certain course from the database
	 * 
	 *  @param  -   int     $id         The id of the course
	 *  @param  -   String  $orderby    (sql) ORDER BY $orderby
	 *  @return -   Array               The returned rows
	 */
	function get_course_items($id, $orderby = "name ASC") {
		$sql = "SELECT * FROM ".Rsys :: getTable("item")." WHERE course_id = ".Database::escape_string($id)." ORDER BY ".$orderby;
		$arr = api_store_result(api_sql_query($sql, __FILE__, __LINE__));
		return $arr;
	}

	/**
	 *  Returns items for a sortable table based on the params
	 * 
	 *  @param  -   int     $from       Index of the first item to return.
	 *  @param  -   int     $per_page   The number of items to return
	 *  @param  -   int     $column     The number of the column on which the data should be sorted
	 *  @param  -   String  $direction  In which order should the data be sorted (ASC or DESC)
	 *  @return -   Array               The returned rows
	 */
	function get_table_items($from, $per_page, $column, $direction) {
		$sql = "SELECT i.id AS col0, i.name as col1, i.description AS col2, ca.name AS col3, IF(i.creator='".api_get_user_id()."','".get_lang('Yes')."','".get_lang('No')."') AS col4, i.id AS col5 
						FROM ".Rsys :: getTable("item")." i INNER JOIN ".Rsys :: getTable("category")." ca ON i.category_id = ca.id 
							LEFT JOIN ".Rsys :: getTable("item_rights")." ir ON ir.item_id=i.id 
		                    LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id 
		                    LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
							WHERE ((cu.user_id='".api_get_user_id()."' AND (ir.edit_right=1 OR ir.delete_right=1)) OR i.creator='".api_get_user_id()."'  OR 1=". (api_is_platform_admin() ? 1 : 0).")";

		if (!empty ($_GET['cat']) && $_GET['cat'] <> 0) {
			$sql .= " AND ca.id = '".$_GET['cat']."' ";
		}

		$sql .= " GROUP BY i.id ORDER BY col".$column." ".$direction." LIMIT ".$from.",".$per_page;
		$result = api_sql_query($sql, __FILE__, __LINE__);

		while ($array = Database::fetch_array($result, 'NUM')) {
			if (!$array[4])
				$array[4] = '-';
			$arr[] = $array;
		}
		return $arr;
	}

	/**
	 *  Get number of items
	 * 
	 *  @return -   int     The amount of items
	 */
	function get_num_items() {
		$sql = "SELECT COUNT(DISTINCT i.id) FROM ".Rsys :: getTable("item")." i 
                            LEFT JOIN ".Rsys :: getTable("item_rights")." ir ON ir.item_id=i.id 
                            LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id 
                            LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
                            WHERE ( 1=". (api_is_platform_admin() ? 1 : 0)." 
							OR ((cu.user_id='".api_get_user_id()."' AND (ir.edit_right=1 OR ir.delete_right=1)) OR i.creator='".api_get_user_id()."' ))";

        //if (!empty ($_GET['cat']) && $_GET['cat'] <> 0) {
        //    $sql .= " AND ca.id = '".$_GET['cat']."' ";
        //}
        
        /*$sql .= "   LEFT JOIN ".Rsys :: getTable("item_rights")." ir ON ir.item_id=i.id 
         
		                    LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id 
		                    LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
                          WHERE ((cu.user_id='".api_get_user_id()."' AND (ir.edit_right=1 OR ir.delete_right=1)) OR i.creator='".api_get_user_id()."'  OR 1=". (api_is_platform_admin() ? 1 : 0).")";

		if (isset ($_GET['keyword']) != 0) {
			$keyword = Database::escape_string($_GET['keyword']);
			$sql .= " WHERE i.category_id  LIKE '%".$keyword."%'";
		}*/
		//$sql .= " GROUP BY i.id";
		return @ Database::result(api_sql_query($sql, __FILE__, __LINE__), 0, 0);
	}

	/**
	 *  Returns the rights for an item for sortable table based on the params
	 * 
	 *  @param  -   int     $from       Index of the first item to return.
	 *  @param  -   int     $per_page   The number of items to return
	 *  @param  -   int     $column     The number of the column on which the data should be sorted
	 *  @param  -   String  $direction  In which order should the data be sorted (ASC or DESC)
	 *  @return -   Array               The returned rows
	 */
	function get_table_itemrights($from, $per_page, $column, $direction) {
		$itemid = $_GET['item_id'];
		$sql = "SELECT id, name FROM ".Database :: get_main_table(TABLE_MAIN_CLASS);
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result, 'NUM')) {
			$arr[] = $array;
		}
		$sql = "SELECT item_id, class_id,edit_right,delete_right,m_reservation,view_right
								FROM ".Rsys :: getTable("item_rights")." WHERE item_id=".$itemid;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result, 'NUM')) {
			$arr1[] = $array;
		}
		$count = -1;
		if (count($arr)>0) {
			foreach ($arr as $lijn) {
				$count ++;
				$controle = false;
				$tabel[$count][0] = $itemid."-".$lijn[0];
				$tabel[$count][1] = $lijn[1];
				foreach ($arr1 as $lijn2) {
					if ($lijn2[1] == $lijn[0]) {
						
						if ($lijn2[2] == 0) {
							$tabel[$count][2] = '<img src="../img/wrong.gif" onclick="document.location.href=\'m_item.php?action=m_rights&subaction=switch&class_id='.$lijn[0].'&item_id='.$itemid.'&switch=edit&set=1\'" />';
						} else {
							$tabel[$count][2] = '<img src="../img/right.gif" onclick="document.location.href=\'m_item.php?action=m_rights&subaction=switch&class_id='.$lijn[0].'&item_id='.$itemid.'&switch=edit&set=0\'" />';
						}
						if ($lijn2[3] == 0) {
							$tabel[$count][3] = '<img src="../img/wrong.gif" onclick="document.location.href=\'m_item.php?action=m_rights&subaction=switch&class_id='.$lijn[0].'&item_id='.$itemid.'&switch=delete&set=1\'" />';
						} else {
							$tabel[$count][3] = '<img src="../img/right.gif" onclick="document.location.href=\'m_item.php?action=m_rights&subaction=switch&class_id='.$lijn[0].'&item_id='.$itemid.'&switch=delete&set=0\'" />';
						}
						if ($lijn2[4] == 0) {
							$tabel[$count][4] = '<img src="../img/wrong.gif" onclick="document.location.href=\'m_item.php?action=m_rights&subaction=switch&class_id='.$lijn[0].'&item_id='.$itemid.'&switch=manage&set=1\'" />';
						} else {
							$tabel[$count][4] = '<img src="../img/right.gif" onclick="document.location.href=\'m_item.php?action=m_rights&subaction=switch&class_id='.$lijn[0].'&item_id='.$itemid.'&switch=manage&set=0\'" />';
						}
	                    if ($lijn2[5] == 0) {
       		                 $tabel[$count][5] = '<img src="../img/wrong.gif" onclick="document.location.href=\'m_item.php?action=m_rights&subaction=switch&class_id='.$lijn[0].'&item_id='.$itemid.'&switch=view&set=1\'" />';
                	    } else {
                        	$tabel[$count][5] = '<img src="../img/right.gif" onclick="document.location.href=\'m_item.php?action=m_rights&subaction=switch&class_id='.$lijn[0].'&item_id='.$itemid.'&switch=view&set=0\'" />';
	                    }
			$controle = true;
			}
			}
			if (!$controle) {
				$tabel[$count][2] = '<img src="../img/wrong.gif" onclick="document.location.href=\'m_item.php?action=m_rights&subaction=switch&class_id='.$lijn[0].'&item_id='.$itemid.'&switch=edit&set=1\'" />';
				$tabel[$count][3] = '<img src="../img/wrong.gif" onclick="document.location.href=\'m_item.php?action=m_rights&subaction=switch&class_id='.$lijn[0].'&item_id='.$itemid.'&switch=delete&set=1\'" />';
				$tabel[$count][4] = '<img src="../img/wrong.gif" onclick="document.location.href=\'m_item.php?action=m_rights&subaction=switch&class_id='.$lijn[0].'&item_id='.$itemid.'&switch=manage&set=1\'" />';
				$tabel[$count][5] = '<img src="../img/wrong.gif" onclick="document.location.href=\'m_item.php?action=m_rights&subaction=switch&class_id='.$lijn[0].'&item_id='.$itemid.'&switch=view&set=1\'" />';
			}
			$tabel[$count][6] = $itemid."-".$lijn[0];
			}
		}
		return $tabel;
	}

	function set_new_right($item_id, $class_id, $column, $value) {
		$sql = "SELECT item_id FROM ".Rsys :: getTable("item_rights")."WHERE item_id=".$item_id." AND class_id=".$class_id;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$switcher = Database::num_rows($result);
		if ($switcher > 0) {
			$sql = $sql = "UPDATE ".Rsys :: getTable("item_rights")." SET ".$column."='".Database::escape_string($value)."' WHERE class_id = '".$class_id."' AND item_id ='".$item_id."'";
			api_sql_query($sql, __FILE__, __LINE__);
		} else {
			$sql = "INSERT INTO ".Rsys :: getTable("item_rights")." (item_id,class_id,".$column.") VALUES ('".Database::escape_string($item_id)."','".Database::escape_string($class_id)."','".$value."')";
			api_sql_query($sql, __FILE__, __LINE__);
		}
	}

	/**
	 *  Get number of itemrights
	 * 
	 *  @return -   int     The amount of itemrights
	 */
	function get_num_itemrights() {
		$sql = "SELECT COUNT(id) FROM ".Database :: get_main_table(TABLE_MAIN_CLASS);
		return @ Database::result(api_sql_query($sql, __FILE__, __LINE__), 0, 0);
	}

	/**
	 *  Get all classes where the item hasn't already defined rights for
	 * 
	 *  @param  -   int     $item_id    The id of the item
	 *  @return -   Array               The returned rows
	 */
	function get_itemfiltered_class($item_id) {
		$sql = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_CLASS)." WHERE id NOT IN (SELECT class_id  FROM ".Rsys :: getTable("item_rights")." WHERE item_id='".$item_id."') ORDER BY name ASC, code ASC";
		$arr = api_store_result(api_sql_query($sql, __FILE__, __LINE__));
		return $arr;
	}

	/**
	 *  Get number of classes where the item hasn't already defined rights for
	 * 
	 *  @param  -   int     $item_id    The id of the item
	 *  @return -   int                 The amount
	 */
	function get_num_itemfiltered_class($item_id) {
		$sql = "SELECT COUNT(id) FROM ".Database :: get_main_table(TABLE_MAIN_CLASS)." WHERE id NOT IN (SELECT class_id  FROM ".Rsys :: getTable("item_rights")." WHERE item_id='".$item_id."') ORDER BY name ASC, code ASC";
		return Database::result(api_sql_query($sql, __FILE__, __LINE__), 0, 0);
	}

	/**
	 *  Adds an item-right
	 *  
	 *  @param  -   int     $item_id        Item-ID
	 *  @param  -   int     $class_id       Class-ID
	 *  @param  -   int     $edit           Edit Right
	 *  @param  -   int     $delete         Delete Right
	 *  @param  -   int     $m_reservation  Manage reservations Right
	 */
	function add_item_right($item_id, $class_id, $edit, $delete, $m_reservation) {
		if (!Rsys :: item_allow($item_id, 'm_rights'))
			return false;
		$sql = "INSERT INTO ".Rsys :: getTable("item_rights")." (item_id,class_id,edit_right,delete_right,m_reservation) VALUES ('".Database::escape_string($item_id)."','".Database::escape_string($class_id)."','".Database::escape_string($edit)."','".Database::escape_string($delete)."','".Database::escape_string($m_reservation)."')";
		api_sql_query($sql, __FILE__, __LINE__);
	}

	/**
	 *  Edits an item-right
	 *  
	 *  @param  -   int     $item_id        Item-ID
	 *  @param  -   int     $class_id       Class-ID
	 *  @param  -   int     $edit           Edit Right
	 *  @param  -   int     $delete         Delete Right
	 *  @param  -   int     $m_reservation  Manage reservations Right
	 *  @return -   int                     The id
	 */
	function edit_item_right($item_id, $class_id, $edit, $delete, $m_reservation) {
		if (!Rsys :: item_allow($item_id, 'm_rights'))
			return false;
		$sql = "UPDATE ".Rsys :: getTable("item_rights")." SET edit_right='".Database::escape_string($edit)."', delete_right='".Database::escape_string($delete)."', m_reservation='".Database::escape_string($m_reservation)."'  WHERE class_id = '".$class_id."' AND item_id ='".$item_id."'";
		api_sql_query($sql, __FILE__, __LINE__);
	}

	/**
	 *  Deletes an item-right
	 *  
	 *  @param  -   int     $id     The id
	 */
	function delete_item_right($item_id, $class_id) {
		if (!Rsys :: item_allow($item_id, 'm_rights'))
			return false;
		$sql = "DELETE FROM ".Rsys :: getTable("item_rights")." WHERE item_id='".$item_id."' AND class_id='".$class_id."'";
		api_sql_query($sql, __FILE__, __LINE__);
	}

	function get_class_group($class_id) {
		$sql = "SELECT * FROM ".Database :: get_main_table(TABLE_MAIN_CLASS)." WHERE id='".$class_id."'";
		$arr = api_store_result(api_sql_query($sql, __FILE__, __LINE__));
		return $arr;
	}

	function get_item_rights($item_id, $class_id) {
		$sql = "SELECT * FROM ".Rsys :: getTable('item_rights')." WHERE item_id='".$item_id."' AND class_id='".$class_id."'";
		$arr = api_store_result(api_sql_query($sql, __FILE__, __LINE__));
		return $arr;
	}

	function black_out_changer($item_id) {
		$sql = "SELECT blackout FROM ".Rsys :: getTable("item")." WHERE id='".$item_id."'";
		$Value = api_store_result(api_sql_query($sql, __FILE__, __LINE__));
		($Value[0][0] == 0 ? $changedValue = 1 : $changedValue = 0);
		$sql = "UPDATE ".Rsys :: getTable("item")." SET blackout='".$changedValue."'  WHERE id = '".$item_id."'";
		api_sql_query($sql, __FILE__, __LINE__);
		Rsys :: black_out_notifier($item_id, $Value[0][0]);
		return $changedValue;
	}

	function black_out_notifier($item_id, $value) {
		$sql = "SELECT id, timepicker FROM ".Rsys :: getTable('reservation')."
							 WHERE item_id='".$item_id."' AND subscribers > '0'";
		$value == 1 ? $sql .= " AND end_at >= (NOW()-7000000) " : $sql .= " AND end_at >= NOW()";
		$reservations = api_sql_query($sql, __FILE__, __LINE__);
		while ($reservation = Database::fetch_array($reservations)) {
			$sql = "SELECT user_id FROM ".Rsys :: getTable('subscription')." WHERE reservation_id='".$reservation[0]."'";
			if ($reservation[1] == 1) {
				$sql .= " AND end_at >= NOW() ";
			}
			$subscriptions = api_sql_query($sql, __FILE__, __LINE__);
			while ($subscription = Database::fetch_array($subscriptions)) {
				$user_info = api_get_user_info($subscription[0]);
				$sql2 = "SELECT name FROM ".Rsys :: getTable('item')." WHERE id='".$item_id."'";
				$items = api_sql_query($sql2, __FILE__, __LINE__);
				$item = Database::fetch_array($items);
				$item_name=$item['name'];
				if ($reservation[1] == 0)
				{
					//er wordt geen gebruik gemaakt van een timepicker dus begin en einddatum kan opgehaald worden uit reservation
					$sql2 = "SELECT start_at,end_at FROM ".Rsys :: getTable('reservation')." WHERE id='".$reservation[0]."'";
				}
				else
				{
					//er wordt gebruik gemaakt van een timepicker dus begin en einddatum kan opgehaald worden uit subscriptions
					$sql2 = "SELECT start_at,end_at FROM ".Rsys :: getTable('subscription')." WHERE reservation_id='".$reservation[0]."'";
				}
				$items = api_sql_query($sql2, __FILE__, __LINE__);
				$item = Database::fetch_array($items);
				$begindatum = $item['start_at'];
				$einddatum = $item['end_at'];
				
				if ($value==1) {
					$inhoud = str_replace('#NAME#', $item_name, get_lang('ReservationActive'));
					$inhoud = str_replace('#BEGIN#', $begindatum, $inhoud);
					$inhoud = str_replace('#BEGIN#', $einddatum, $inhoud);				
					$titel = str_replace('#NAME#', $item_name, get_lang('ReservationAvailable'));					
				} else {
					$inhoud = str_replace('#NAME#', $item_name, get_lang('ReservationCancelled'));
					$inhoud = str_replace('#BEGIN#', $begindatum, $inhoud);
					$inhoud = str_replace('#BEGIN#', $einddatum, $inhoud);				
					$titel = str_replace('#NAME#', $item_name, get_lang('ReservationUnavailable'));
				}
				
				
				api_send_mail($user_info['mail'], $titel, $inhoud);
			}
		}
	}

	/*
	 ============================================================================================
	
	                                    RESERVATION PERIODS
	    
	 ============================================================================================
	*/

	function recurrence_list() {
		$arr['1'] = get_lang('EveryDay');
		$arr['7'] = get_lang('EveryWeek');
		//$arr['month'] = get_lang('EveryMonth');
		return $arr;
	}

	function check_date($item_id, $start_date, $end_date, $start_at, $end_at) {
		$sql = "SELECT * FROM ".Rsys :: getTable('reservation')." WHERE item_id='".$item_id."' ORDER BY start_at";
		$result = api_sql_query($sql, __FILE__, __LINE__);

		while ($array = Database::fetch_array($result)) {
			$GLOBALS['start_date'] = $array[5];
			$GLOBALS['end_date'] = $array[6];
			if (Rsys :: mysql_datetime_to_timestamp($array[5]) <= $start_date && Rsys :: mysql_datetime_to_timestamp($array[6]) >= $start_date) {
				return $array[0];
			}
			if (Rsys :: mysql_datetime_to_timestamp($array[5]) <= $end_date && Rsys :: mysql_datetime_to_timestamp($array[6]) >= $end_date)
				return $array[0];
		}

		$sql = "SELECT id, start_at, end_at FROM ".Rsys :: getTable('reservation')." 
										WHERE ((start_at > '".$start_at."' AND 
											  start_at < '".$end_at."') OR
											  (end_at > '".$start_at."' AND 
											  end_at < '".$end_at."') OR (start_at <= '".$start_at."' AND end_at >= '".$end_at."')) AND item_id='".$item_id."'";
		$result = Database::fetch_array(api_sql_query($sql, __FILE__, __LINE__));
		if (count($result) != 0){
			$GLOBALS['start_date'] = $result[1];
			$GLOBALS['end_date'] = $result[2];
			return $result[0];
		}
		return 0;
	}

	function check_date_edit($item_id, $start_date, $end_date, $start_at, $end_at, $reservation_id) {
		$sql = "SELECT * FROM ".Rsys :: getTable('reservation')." WHERE item_id='".$item_id."' AND id <> '".$reservation_id."' ORDER BY start_at";
		$result = api_sql_query($sql, __FILE__, __LINE__);

		while ($array = Database::fetch_array($result)) {
			$GLOBALS['start_date'] = $array[5];
			$GLOBALS['end_date'] = $array[6];
			if (Rsys :: mysql_datetime_to_timestamp($array[5]) < $start_date && Rsys :: mysql_datetime_to_timestamp($array[6]) > $start_date) {
				return $array[0];
			}
			if (Rsys :: mysql_datetime_to_timestamp($array[5]) < $end_date && Rsys :: mysql_datetime_to_timestamp($array[6]) > $end_date)
				return $array[0];
		}

		$sql = "SELECT id FROM ".Rsys :: getTable('reservation')." 
										WHERE ((start_at > '".$start_at."' AND 
											  start_at < '".$end_at."') OR
											  (end_at > '".$start_at."' AND 
											  end_at < '".$end_at."') OR 
											  (start_at <= '".$start_at."' AND 
											  end_at >= '".$end_at."')) AND item_id='".$item_id."' AND id <> '".$reservation_id."'";
		$result = Database::fetch_array(api_sql_query($sql, __FILE__, __LINE__));
		
		if (count($result) != 0){
			$GLOBALS['start_date'] = $result[1];
			$GLOBALS['end_date'] = $result[2];
			return $result[0];
		}
		return 0;
	}

	function get_category_rights() {
		$sql = "SELECT cat.id as catid,cat.name as catname
										FROM ".Rsys :: getTable('category')." cat
										LEFT JOIN ".Rsys :: getTable('item')." i ON cat.id=i.category_id
						                LEFT JOIN ".Rsys :: getTable('item_rights')." ir ON ir.item_id=i.id 
						                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id 
						                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
						                WHERE (cu.user_id='".api_get_user_id()."' AND ir.m_reservation=1 ) OR i.creator='".api_get_user_id()."' OR 1=". (api_is_platform_admin() ? 1 : 0)." ORDER BY cat.name ASC";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result))
			$arr[$array['catid']] = $array['catname'];
		return $arr;
	}

	/**
	 *  Returns an array with items from a category linked to rights(used by m_reservations.php)
	 */
	function get_cat_r_items($category) {
		$sql = "SELECT i.id,i.name as catitem
						                FROM ".Rsys :: getTable('item')." i
										INNER JOIN ".Rsys :: getTable('category')." cat ON cat.id=i.category_id  
						                LEFT JOIN ".Rsys :: getTable('item_rights')." ir ON ir.item_id=i.id 
						                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id 
						                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
						                WHERE ((cu.user_id='".api_get_user_id()."' AND ir.m_reservation=1 ) OR i.creator='".api_get_user_id()."' OR 1=". (api_is_platform_admin() ? 1 : 0).") AND (category_id =".$category.") 
										ORDER BY cat.name ASC, i.name ASC";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result))
			$arr[$array['id']] = $array['catitem'];
		return $arr;
	}

	/**
	 *  Returns an array with [ itemID => "category/item" ] with view_rights (used by reservation.php)
	 */
	function get_cat_items($category) {
		$sql = "SELECT i.id,i.name as catitem
		                                FROM ".Rsys :: getTable('item')." i
		                                INNER JOIN ".Rsys :: getTable('category')." cat ON cat.id=i.category_id  
		                                LEFT JOIN ".Rsys :: getTable('item_rights')." ir ON ir.item_id=i.id 
		                                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id 
		                                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
		                                WHERE ((cu.user_id='".api_get_user_id()."' AND ir.view_right=1 ) OR i.creator='".api_get_user_id()."' OR 1=". (api_is_platform_admin() ? 1 : 0).") AND (category_id =".$category.") 
		                                ORDER BY cat.name ASC, i.name ASC";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result))
			$arr[$array['id']] = $array['catitem'];
		return $arr;
	}

	/**
	 *  Returns the reservations for sortable table based on the params
	 * 
	 *  @param  -   int     $from       Index of the first item to return.
	 *  @param  -   int     $per_page   The number of items to return
	 *  @param  -   int     $column     The number of the column on which the data should be sorted
	 *  @param  -   String  $direction  In which order should the data be sorted (ASC or DESC)
	 *  @return -   Array               The returned rows
	 */
	function get_table_reservations($from, $per_page, $column, $direction) {
		$sql = "SELECT DISTINCT r.id AS col0, i.name AS col1,  DATE_FORMAT(r.start_at,'%Y-%m-%d %H:%i') AS col2, DATE_FORMAT(r.end_at,'%Y-%m-%d %H:%i') AS col3," .
			   "				DATE_FORMAT(r.subscribe_from,'%Y-%m-%d %k:%i') AS col4, DATE_FORMAT(r.subscribe_until,'%Y-%m-%d %k:%i') AS col5,IF(timepicker <> 0, '".get_lang('TimePicker')."',CONCAT(r.subscribers,'/',r.max_users)) AS col6, r.notes AS col7, r.id as col8  
						                FROM ".Rsys :: getTable('reservation')." r
						                INNER JOIN ".Rsys :: getTable('item')." i ON r.item_id=i.id 
						                LEFT JOIN ".Rsys :: getTable('item_rights')." ir ON ir.item_id=i.id 
						                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id 
						                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
						                WHERE ((ir.m_reservation=1 AND cu.user_id='".api_get_user_id()."') OR i.creator='".api_get_user_id()."' OR 1=". (api_is_platform_admin() ? 1 : 0).")";
		if (isset ($_GET['keyword'])) {
			$keyword = Database::escape_string($_GET['keyword']);
			$sql .= "AND (i.name LIKE '%".$keyword."%' OR i.description LIKE '%".$keyword."%' OR r.notes LIKE '%".$keyword."%')";
		}
		$sql .= " ORDER BY col".$column." ".$direction." LIMIT ".$from.",".$per_page;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result, 'NUM')) {		
			$arr[] = $array;			
		}
		return $arr;
	}

	function check_edit_right($id) {
		$sql = "SELECT r.id
						                FROM ".Rsys :: getTable('reservation')." r 
						                INNER JOIN ".Rsys :: getTable('item')." i ON r.item_id=i.id 
						                LEFT JOIN ".Rsys :: getTable('item_rights')." ir ON ir.item_id=i.id 
						                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id 
						                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
						                WHERE ((cu.user_id='".api_get_user_id()."'AND ir.edit_right=1) OR 1=". (api_is_platform_admin() ? 1 : 0).") AND r.id='".$id."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result, 'NUM')) {
			$arr[] = $array;
		}
		return $result;
	}

	function check_delete_right($id) {
		$sql = "SELECT r.id
						                FROM ".Rsys :: getTable('reservation')." r 
						                INNER JOIN ".Rsys :: getTable('item')." i ON r.item_id=i.id 
						                LEFT JOIN ".Rsys :: getTable('item_rights')." ir ON ir.item_id=i.id 
						                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id 
						                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
						                WHERE ((cu.user_id='".api_get_user_id()."'AND ir.delete_right=1) OR 1=". (api_is_platform_admin() ? 1 : 0).") AND r.id='".$id."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result, 'NUM')) {
			$arr[] = $array;
		}
		return $arr;
	}

	function check_auto_accept($id) {
		$sql = "SELECT auto_accept FROM ".Rsys :: getTable('reservation')." WHERE id='".$id."'";
		return Database::result(api_sql_query($sql, __FILE__, __LINE__), 0, 0);
	}

	/**
	 *  Get number of reservations
	 * 
	 *  @return -   int                 The amount
	 */
	function get_num_reservations() {
		$sql = "SELECT COUNT(DISTINCT r.id) 
                FROM ".Rsys :: getTable('reservation')." r 
                LEFT JOIN ".Rsys :: getTable('item')." i ON i.id=r.item_id 
                LEFT JOIN ".Rsys :: getTable('item_rights')." ir ON ir.item_id=r.item_id 
                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = r.item_id 
                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
                WHERE ((ir.m_reservation=1 AND cu.user_id='".api_get_user_id()."') OR i.creator='".api_get_user_id()."' OR 1=". (api_is_platform_admin() ? 1 : 0).')';
        if (isset ($_GET['keyword'])) {
            $keyword = Database::escape_string($_GET['keyword']);
            $sql .= " AND (i.name LIKE '%".$keyword."%' OR i.description LIKE '%".$keyword."%' OR r.notes LIKE '%".$keyword."%')";
        }
		return Database::result(api_sql_query($sql, __FILE__, __LINE__), 0, 0);
	}

	/**
	 *  Adds a reservation
	 * 
	 *  @param  -   $item_id,$auto_accept,$max_users,$start_at,$end_at,$subscribe_until,$notes
	 *  @return -   FALSE if there is something wrong with the dates, a mysql_insert_id() if everything went perfectly
	 */
	function add_reservation($item_id, $auto_accept, $max_users, $start_at, $end_at, $subscribe_from, $subscribe_until, $notes, $timepicker, $min, $max,$subid) {
		$stamp_start = Rsys :: mysql_datetime_to_timestamp($start_at);
		$stamp_end = Rsys :: mysql_datetime_to_timestamp($end_at);
		
		$stamp_start_date = date( 'Y-m-d',$stamp_start);
		$stamp_end_date = date( 'Y-m-d',$stamp_end);
		
		if (Rsys :: check_date($item_id, $stamp_start, $stamp_end, $start_at, $end_at) <> 0)
			return 1;
		if ($subscribe_until != 0) {
			$stamp_until = Rsys :: mysql_datetime_to_timestamp($subscribe_until);
			if ($stamp_until > $stamp_start)
				return 2;
		}
		if ($start_at < (date( 'Y-m-d H:i:s',time())))
				return 3;
		if (($stamp_start_date != $stamp_end_date) && $timepicker == '1')
		{
			return 4;
		}

		if($timepicker == '0')
		{
			if ($min != '0' || $max != '0')
			{
				//kan niet verschillen van 0!
				return 5;
			}
		}
		else
		{ 
			if (!($max==0 && $min==0))
			{
				if ($max < $min)
				{
					return 6;
					//maximum kan niet kleiner zijn dan minimum
				}
				else
				{
					$stamp = ($stamp_end - $stamp_start)/ 60;
					if (($stamp/$max)<1)
					{
						return 7;
						// er past geen blok van het tijdverschil
					}
				}
			}
		}

		$sql = "INSERT INTO ".Rsys :: getTable("reservation")." (item_id,auto_accept,max_users,start_at,end_at,subscribe_from,subscribe_until,notes,timepicker,timepicker_min,timepicker_max,subid) VALUES ('".Database::escape_string($item_id)."','".Database::escape_string($auto_accept)."','". (intval($max_users) > 1 ? $max_users : 1)."','".Database::escape_string($start_at)."','".Database::escape_string($end_at)."','".Database::escape_string($subscribe_from)."','".Database::escape_string($subscribe_until)."','".Database::escape_string($notes)."','".$timepicker."','".$min."','".$max."','". ($subid == 0 ? 0 : $subid)."')";
		api_sql_query($sql, __FILE__, __LINE__);
		return 0;
	}

	/**
	 *  Edits a reservation
	 * 
	 *  @param  -   int     $id     The reservation-ID
	 *  @param  -   $item_id,$auto_accept,$max_users,$start_at,$end_at,$subscribe_until,$notes
	 *  @return -   FALSE if there is something wrong with the dates, TRUE if everything went perfectly
	 *  
	 */
	function edit_reservation($id, $item_id, $auto_accept, $max_users, $start_at, $end_at, $subscribe_from, $subscribe_until, $notes, $timepicker) {
		if (!Rsys :: item_allow($item_id, 'm_reservation'))
			return false;
		$stamp_start = Rsys :: mysql_datetime_to_timestamp($start_at);
		$stamp_end = Rsys :: mysql_datetime_to_timestamp($end_at);

		$stamp_start_date = date( 'Y-m-d',$stamp_start);
		$stamp_end_date = date( 'Y-m-d',$stamp_end);
		if (Rsys :: check_date_edit($item_id, $stamp_start, $stamp_end, $start_at, $end_at, $id) <> 0)
			return 1;
		if ($subscribe_until != 0) {
			$stamp_until = Rsys :: mysql_datetime_to_timestamp($subscribe_until);
			if ($stamp_until > $stamp_start)
				return 2;
		}
		$sql = "SELECT timepicker, subscribers FROM ".Rsys :: getTable("reservation")." WHERE id='".$id."'";
		$result = Database::fetch_array(api_sql_query($sql, __FILE__, __LINE__));
		if ($result[0] == 0 && $result[1] > $max_users) {
			return 3;
		}
		if (($stamp_start_date != $stamp_end_date) && $timepicker == '1')
		{
			return 4;
		}
		if ($auto_accept == 1) {
			$sql = "SELECT dummy FROM ".Rsys :: getTable("subscription")." WHERE reservation_id='".$id."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			while ($array = Database::fetch_array($result, 'NUM')) {
				Rsys :: set_accepted($array[0], 1);
			}
		} else {
			$auto_accept = 0;
		}
		$sql = "UPDATE ".Rsys :: getTable("reservation")." SET item_id='".Database::escape_string($item_id)."',auto_accept='".Database::escape_string($auto_accept)."',max_users='". ($max_users > 1 ? $max_users : 1)."',start_at='".Database::escape_string($start_at)."',end_at='".Database::escape_string($end_at)."',subscribe_from='".Database::escape_string($subscribe_from)."',subscribe_until='".Database::escape_string($subscribe_until)."',notes='".Database::escape_string($notes)."' WHERE id='".$id."'";
		api_sql_query($sql, __FILE__, __LINE__);
		return 0;
	}

	/**
	 *  Deletes a reservation
	 */
	function delete_reservation($id) {
		$sql = "SELECT id FROM ".Rsys :: getTable("reservation")."WHERE id='".$id."' OR subid='".$id."'";
		$result2 = api_sql_query($sql, __FILE__, __LINE__);
		while ($arr = Database::fetch_array($result2, 'NUM')) {
			$sql = "SELECT s.dummy, s.user_id, i.name, r.start_at, r.end_at
											FROM ".Rsys :: getTable("subscription")." s 
											INNER JOIN ".Rsys :: getTable("reservation")." r ON s.reservation_id = r.id
											INNER JOIN ".Rsys :: getTable("item")." i ON r.item_id = i.id
											WHERE s.reservation_id='".$arr[0]."'";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			while ($array = Database::fetch_array($result, 'NUM')) {
				$user_info = api_get_user_info($array[1]);
				api_send_mail($user_info['mail'], str_replace('#NAME#', $array[2], get_lang("ReservationDeleteTitle")), str_replace('#START#', $array[3], str_replace('#END#', $array[4], str_replace('#NAME#', $array[2], get_lang("ReservationDeleteMessage")))));
				$sql = "DELETE FROM ".Rsys :: getTable("subscription")." WHERE dummy='".$array[0]."'";
				api_sql_query($sql, __FILE__, __LINE__);
			}
			$sql = "DELETE FROM ".Rsys :: getTable("reservation")." WHERE id='".$arr[0]."'";
			api_sql_query($sql, __FILE__, __LINE__);
		}
	}

	function is_owner_item($id) {
		$sql = "SELECT creator FROM ".Rsys :: getTable('item')."i ,".Rsys :: getTable('reservation')." r
			where i.id = r.item_id
			and r.id = '".$id."'
			and i.creator ='".api_get_user_id()."'";  
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if (Database::num_rows($result) != 0)
			return 1;
		return 0;
	}
	
	function get_reservation($id) {
		$sql = "SELECT *
                FROM ".Rsys :: getTable('reservation')." r 
                INNER JOIN ".Rsys :: getTable('item')." i ON r.item_id=i.id 
                LEFT JOIN ".Rsys :: getTable('item_rights')." ir ON ir.item_id=i.id 
                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id 
                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
                WHERE (cu.user_id='".api_get_user_id()."' OR 1=". (api_is_platform_admin() ? 1 : 0)." OR 1=".(Rsys :: is_owner_item("$id")? 1 : 0).") AND r.id='".$id."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result, 'NUM'))
			$arr[] = $array;
		return $arr;
	}

	function get_num_subscriptions_overview() {
	
		$sql = "SELECT  COUNT(s.reservation_id)
				FROM ".Rsys :: getTable('subscription')." s, ".Rsys :: getTable('reservation')." r1, ".Database :: get_main_table(TABLE_MAIN_USER)." u," .Rsys :: getTable('item')." i1
				where r1.id = s.reservation_id
				and i1.id = r1.item_id
				and u.user_id = s.user_id
				and s.reservation_id IN 
					(SELECT DISTINCT(r2.id) 
					FROM ".Rsys :: getTable('reservation')." r2 
					LEFT JOIN ".Rsys :: getTable('item')." i2 ON i2.id=r2.item_id 
					LEFT JOIN ".Rsys :: getTable('item_rights')." ir ON ir.item_id=r2.item_id 
					LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = r2.item_id 
					LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
					WHERE ((ir.m_reservation=1 AND cu.user_id='".api_get_user_id()."') 
					OR i2.creator='".api_get_user_id()."' 
					OR 1=". (api_is_platform_admin() ? 1 : 0)."))";
      		if (isset ($_GET['keyword'])) {
            		$keyword = Database::escape_string($_GET['keyword']);
            		$sql .= " AND (i1.name LIKE '%".$keyword."%' or r1.start_at LIKE '%".$keyword."%' or r1.end_at LIKE '%".$keyword."%' or u.lastname LIKE '%".$keyword."%' or u.firstname LIKE '%".$keyword."%' or s.start_at LIKE '%".$keyword."%' or s.end_at LIKE '%".$keyword."%')";
        	}
		return Database::result(api_sql_query($sql, __FILE__, __LINE__), 0, 0);
	}

	function get_table_subcribed_reservations($from, $per_page, $column, $direction) {
		
		$sql = "SELECT  i1.name as col0,c.name as col1, 
				DATE_FORMAT(r1.start_at ,'%Y-%m-%d %H:%i') as col2,
				DATE_FORMAT(r1.end_at ,'%Y-%m-%d %H:%i') as col3, CONCAT(u.lastname,' ',u.firstname) as col4,
				DATE_FORMAT(s.start_at ,'%Y-%m-%d %H:%i')  as col5,
				DATE_FORMAT(s.end_at ,'%Y-%m-%d %H:%i')    as col6, s.accepted as col7
				FROM ".Rsys :: getTable('subscription')." s, ".Rsys :: getTable('reservation')." r1, ".Database :: get_main_table(TABLE_MAIN_USER)." u," .Rsys :: getTable('item')." i1,".Rsys :: getTable('category')." c
				WHERE r1.id = s.reservation_id
				and c.id = i1.category_id
				and i1.id = r1.item_id
				and u.user_id = s.user_id
				and s.reservation_id IN 
					(SELECT DISTINCT(r2.id) 
					FROM ".Rsys :: getTable('reservation')." r2 
					LEFT JOIN ".Rsys :: getTable('item')." i2 ON i2.id=r2.item_id 
					LEFT JOIN ".Rsys :: getTable('item_rights')." ir ON ir.item_id=r2.item_id 
					LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = r2.item_id 
					LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
					WHERE ((ir.m_reservation=1 AND cu.user_id='".api_get_user_id()."') 
					OR i2.creator='".api_get_user_id()."' 
					OR 1=". (api_is_platform_admin() ? 1 : 0)."))";
      		if (isset ($_GET['keyword'])) {
            		$keyword = Database::escape_string($_GET['keyword']);
            		$sql .= " AND (i1.name LIKE '%".$keyword."%' or c.name LIKE '%".$keyword."%' or r1.start_at LIKE '%".$keyword."%' or r1.end_at LIKE '%".$keyword."%' or u.lastname LIKE '%".$keyword."%' or u.firstname LIKE '%".$keyword."%' or s.start_at LIKE '%".$keyword."%' or s.end_at LIKE '%".$keyword."%')";
        	}
		$sql .= " ORDER BY col".$column." ".$direction." LIMIT ".$from.",".$per_page;
		/*$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result, 'NUM'))
			$arr[] = $array;*/
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result, 'NUM')) {
			$row = array();
			$row[] = $array[0];
			$row[] = $array[1];
			$row[] = $array[2];
			$row[] = $array[3];
			$row[] = $array[4];
			if ($array[5]=='0000-00-00 00:00') {				
				$row[] = $array[2];
			}
			else {
				$row[] = $array[5];
			}
			if ($array[6]=='0000-00-00 00:00') {
				$row[] = $array[3];
			}
			else {
				$row[] = $array[6];
			}
			
			if ($array[7]=='1')
			{
				$row[] = get_lang('Yes');
			}
			else {
				$row[] = get_lang('No');
			}
			$arr[] = $row;
		}
		return $arr;
	}

	
	function get_num_waiting_users() {
		$sql = "SELECT COUNT(DISTINCT dummy) FROM ".Rsys :: getTable('subscription');
		if (isset ($_GET['rid'])) {
			$sql .= " WHERE reservation_id = '".$_GET['rid']."'";
		}
		return Database::result(api_sql_query($sql, __FILE__, __LINE__), 0, 0);
	}

	function get_table_waiting_users($from, $per_page, $column, $direction) {
		/*$sql = "SELECT dummy AS col0, CONCAT(u.lastname,' ',u.firstname) AS col1, s.user_id AS col2, accepted AS col3
								 	FROM ".Rsys :: getTable('subscription')." s
								 	INNER JOIN ".Database :: get_main_table(TABLE_MAIN_USER)." u ON s.user_id = u.user_id ";
		if (!empty ($_GET['rid'])) {
			$sql .= " WHERE s.reservation_id = '".$_GET['rid']."'";
		}
		$sql .= " ORDER BY col".$column." ".$direction." LIMIT ".$from.",".$per_page;*/
		$sql = "SELECT dummy AS col0, CONCAT(u.lastname,' ',u.firstname) AS col1, s.user_id AS col2, accepted AS col3, r.start_at, r.end_at, s.start_at, s.end_at
			FROM ".Rsys :: getTable('subscription')." s,".Database :: get_main_table(TABLE_MAIN_USER)." u,".Database :: get_main_table(TABLE_MAIN_RESERVATION_RESERVATION)." r
			where u.user_id = s.user_id
			and s.reservation_id = r.id";
	
		if (!empty ($_GET['rid'])) {
			$sql .= " and r.id = '".$_GET['rid']."'";
		}
		$sql .= " ORDER BY col".$column." ".$direction." LIMIT ".$from.",".$per_page;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result, 'NUM')) {
			$arr[] = $array;
		}
		$count = 0;
		$x = count($arr);
		while ($count < $x) {
			$sql = "SELECT name
					FROM ".Database :: get_main_table(TABLE_MAIN_CLASS)." cl
					INNER JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = cl.id
					WHERE cu.user_id=".$arr[$count][2]." LIMIT 1";
			$result = api_sql_query($sql, __FILE__, __LINE__);
			while ($array = Database::fetch_array($result, 'NUM')) {
				$arr2[] = $array;
			}
			$arr[$count][2] = $arr2[0][0];
			$count ++;
		}
		$count = -1;
		if (is_array($arr)) {
			foreach ($arr as $lijn) {
				$count ++;
				$controle = false;
				$tabel[$count][0] = $lijn[0];
				$tabel[$count][1] = $lijn[1];
				if ($lijn[3] == 0) {
					$tabel[$count][5] = '<img src="../img/wrong.gif" onclick="document.location.href=\'m_reservation.php?action=accept&rid='.$_GET['rid'].'&amp;dummy='.$lijn[0].'&switch=edit&set=1\'" />';
				} else {
					$tabel[$count][5] = '<img src="../img/right.gif" onclick="document.location.href=\'m_reservation.php?action=accept&rid='.$_GET['rid'].'&amp;dummy='.$lijn[0].'&switch=edit&set=0\'" />';
				}
				$tabel[$count][2] = $lijn[2];
				if ($lijn[6] == '0000-00-00 00:00:00' && $lijn[7] == '0000-00-00 00:00:00')
				{
					$tabel[$count][3] = $lijn[4];
					$tabel[$count][4] = $lijn[5];
				}
				else
				{
					$tabel[$count][3] = $lijn[6];
					$tabel[$count][4] = $lijn[7];	
				}
				$tabel[$count][6] = '<img src="../img/wrong.gif" onclick="document.location.href=\'m_reservation.php?action=accept&rid='.$_GET['rid'].'&amp;dummy='.$lijn[0].'&switch=delete\'" />';
			}
		}
		return $tabel;
	}

	function set_accepted($id, $value) {
		global $subscription;
		$sql = "UPDATE ".Rsys :: getTable('subscription')." SET ACCEPTED='".$value."' WHERE dummy='".$id."'";
		api_sql_query($sql, __FILE__, __LINE__);
		$user_info = api_get_user_info($subscription[0]);
		$sql = "SELECT name FROM ".Rsys :: getTable('subscription')." s
								INNER JOIN ".Rsys :: getTable('reservation')." r ON s.reservation_id = r.id
								INNER JOIN ".Rsys :: getTable('item')." i ON r.item_id = i.id
								WHERE dummy='".$id."'";
		$items = api_sql_query($sql, __FILE__, __LINE__);
		$item = Database::fetch_array($items);
		$item_name = $item[0];

		$sql = "SELECT start_at, end_at, timepicker 
			from ".Rsys :: getTable('reservation')." 
			where id in (	SELECT reservation_id 
	    				from ".Rsys :: getTable('subscription')." 
					where dummy ='".$id."')";
		$items = api_sql_query($sql, __FILE__, __LINE__);
		$item = Database::fetch_array($items);
		if ($item['timepicker'] == '1')
		{
			$sql = "SELECT start_at, end_at
	    			from ".Rsys :: getTable('subscription')." 
				where dummy ='".$id."'";
			$items = api_sql_query($sql, __FILE__, __LINE__);
			$item = Database::fetch_array($items);	
		}		
		$begin_datum = $item['start_at'];
		$eind_datum = $item['end_at'];
		
		if ($value==1) {
			$titel = str_replace('#ITEM#', $item_name, get_lang('ReservationAccepted'));
			$inhoud = str_replace('#ITEM#', $item_name, get_lang('ReservationForItemAccepted'));
		} else {
			$titel = str_replace('#ITEM#', $item_name, get_lang('ReservationDenied'));
			$inhoud = str_replace('#ITEM#', $item_name, get_lang('ReservationForDenied'));
		}		
		
		$inhoud = str_replace('#BEGIN', $begin_datum, $inhoud);
		$inhoud = str_replace('#END', $eind_datum, $inhoud);
		api_send_mail($user_info['mail'], $titel, $inhoud);
	}

	/*
	 ============================================================================================
	
	                                    RESERVATION
	    
	 ============================================================================================
	*/

	function check_date_subscription($reservation_id, $start_at, $end_at) {
		$sql = "SELECT id, start_at, end_at FROM ".Rsys :: getTable('reservation')." 
										WHERE start_at > '".$start_at."' AND id='".$reservation_id."' ";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if (Database::num_rows($result) != 0){
			$result2 = Database::fetch_array($result);
			$GLOBALS['start_date'] = $result2[1];
			$GLOBALS['end_date'] = $result2[2];
			return 1;
		}
		
		$sql = "SELECT id, start_at, end_at FROM ".Rsys :: getTable('reservation')." 
										WHERE end_at < '".$end_at."' AND id='".$reservation_id."' ";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if (Database::num_rows($result) != 0){
			$result2 = Database::fetch_array($result);
			$GLOBALS['start_date'] = $result2[1];
			$GLOBALS['end_date'] = $result2[2];
			return 1;
		}
		
		
		$sql = "SELECT * FROM ".Rsys :: getTable('subscription')." WHERE reservation_id='".$reservation_id."' ORDER BY start_at";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result)) {
			$GLOBALS['start_date'] = $array[4];
			$GLOBALS['end_date'] = $array[5];
			if (Rsys :: mysql_datetime_to_timestamp($array[4]) < Rsys :: mysql_datetime_to_timestamp($start_at) && Rsys :: mysql_datetime_to_timestamp($array[5]) > Rsys :: mysql_datetime_to_timestamp($start_at)) {
				return $array[0];
			}
			if (Rsys :: mysql_datetime_to_timestamp($array[4]) < Rsys :: mysql_datetime_to_timestamp($end_at) && Rsys :: mysql_datetime_to_timestamp($array[5]) > Rsys :: mysql_datetime_to_timestamp($end_at))
				return $array[0];
		}
		$sql = "SELECT dummy, start_at ,end_at FROM ".Rsys :: getTable('subscription')." 
										WHERE ((start_at > '".$start_at."' AND 
											  start_at < '".$end_at."') OR
											  (end_at > '".$start_at."' AND 
											  end_at < '".$end_at."')OR 
											  (start_at <= '".$start_at."' AND 
											  end_at >= '".$end_at."')) AND reservation_id='".$reservation_id."' ";
		$result = Database::fetch_array(api_sql_query($sql, __FILE__, __LINE__));
		if (count($result) != 0){
			$GLOBALS['start_date'] = $result[1];
			$GLOBALS['end_date'] = $result[2];
			return $result[0];
		}
		return 0;
	}

	function check_date_month_calendar($date, $itemid) {
		$sql = "SELECT id FROM ".Rsys :: getTable('reservation')."             
				WHERE ((DATE_FORMAT(start_at, '%Y-%m-%e') = '".$date."' OR DATE_FORMAT(end_at, '%Y-%m-%e') = '".$date."' 
				OR (start_at <= '".$date." 00:00:00' AND end_at >= '".$date." 00:00:00' ) OR (start_at>='".$date." 00:00:00' AND start_at<='".$date." 23:59:59')) AND (subscribers < max_users OR timepicker=1)) AND item_id= '".$itemid."'";
		/*
		    WHERE item_id='".$itemid."'  AND 
		                ((start_at<='".$date."' AND end_at>='".$date."') OR (start_at>='".$date."' AND start_at<='".$date."'))";
		
		 
		 */
		$result = api_sql_query($sql, __FILE__, __LINE__);
		if (Database::num_rows($result) != 0)
			return true;
		return false;
	}

	/**
	 *  With this you make a reservartion
	 * 
	 * @param -		int		$reservation_id		The id off the reservation
	 */
	function add_subscription($reservation_id, $user_id, $accepted) {
		$sql = "SELECT user_id FROM ".Rsys :: getTable("subscription")." WHERE user_id='".$user_id."' AND reservation_id='".$reservation_id."'";
		if (Database::num_rows(api_sql_query($sql, __FILE__, __LINE__)) == 0) {
			$sql = "INSERT INTO ".Rsys :: getTable("subscription")." (user_id,reservation_id,accepted) VALUES ('".Database::escape_string($user_id)."','".Database::escape_string($reservation_id)."','". ($accepted ? '1' : '0')."')";
			api_sql_query($sql, __FILE__, __LINE__);
			$sql = "UPDATE ".Rsys :: getTable("reservation")." SET subscribers=subscribers+1 WHERE id='".$reservation_id."'";
			api_sql_query($sql, __FILE__, __LINE__);
			$sql = "SELECT s.user_id, i.name, r.start_at, r.end_at
										FROM ".Rsys :: getTable("subscription")." s
										INNER JOIN ".Rsys :: getTable("reservation")." r ON s.reservation_id = r.id 
										INNER JOIN ".Rsys :: getTable("item")." i ON r.item_id = i.id
										WHERE reservation_id='".$reservation_id."' AND user_id='".$user_id."'";
			$result = api_store_result(api_sql_query($sql, __FILE__, __LINE__));
			$user_info = api_get_user_info();
			$titel = str_replace('#ITEM#', $result[0][1], get_lang("ReservationMadeTitle"));
			$inhoud = str_replace('#ITEM#', $result[0][1], str_replace('#START#', $result[0][2], str_replace('#END#', $result[0][3], get_lang("ReservationMadeMessage"))));
			api_send_mail($user_info['mail'], $titel, $inhoud);
			return 0;
		}
		return 1;
	}

	/**
	 *  With this you make a reservartion
	 * 
	 * @param -		int		$reservation_id		The id off the reservation
	 */
	function add_subscription_timepicker($reservation_id, $user_id, $start_date, $end_date, $accepted, $min, $max) {
		if (Rsys :: check_date_subscription($reservation_id, $start_date, $end_date) <> 0)
			return 1;
		if (!($min==0 && $max ==0)){
			if ((Rsys :: mysql_datetime_to_timestamp($end_date)-Rsys :: mysql_datetime_to_timestamp($start_date)) < ($min*60))
				return 2;
			if ((Rsys :: mysql_datetime_to_timestamp($end_date)-Rsys :: mysql_datetime_to_timestamp($start_date)) > ($max*60))
				return 3;
		}
		$sql = "INSERT INTO ".Rsys :: getTable("subscription")." (user_id,reservation_id,accepted,start_at,end_at) VALUES ('".Database::escape_string($user_id)."','".Database::escape_string($reservation_id)."','". ($accepted ? '1' : '0')."','".$start_date."','".$end_date."')";
		api_sql_query($sql, __FILE__, __LINE__);
		return 0;
	}

	/**
	 *  Delete subscription
	 */
	function delete_subscription($reservation_id, $dummy) {
		$sql = "DELETE FROM ".Rsys :: getTable("subscription")." WHERE dummy='".$dummy."'";
		api_sql_query($sql, __FILE__, __LINE__);
		$sql = "UPDATE ".Rsys :: getTable("reservation")." SET subscribers=subscribers-1 WHERE id='".$reservation_id."'";
		api_sql_query($sql, __FILE__, __LINE__);
	}

	/**
	 *  Returns the subscriptions of the user for a sortable table based on the params
	 * 
	 *  @param  -   int     $from       Index of the first item to return.
	 *  @param  -   int     $per_page   The number of items to return
	 *  @param  -   int     $column     The number of the column on which the data should be sorted
	 *  @param  -   String  $direction  In which order should the data be sorted (ASC or DESC)
	 *  @return -   Array               The returned rows
	 */
	function get_table_subscriptions($from, $per_page, $column, $direction) {
		$sql = "SELECT CONCAT(s.reservation_id,'-',s.dummy) AS col0, i.name AS col1, DATE_FORMAT(s.start_at ,'%Y-%m-%d %H:%i')  AS col2, DATE_FORMAT(s.end_at ,'%Y-%m-%d %H:%i') AS col3, CONCAT(s.reservation_id,'-',s.dummy) AS col4, DATE_FORMAT(r.start_at ,'%Y-%m-%d %H:%i') , DATE_FORMAT(r.end_at ,'%Y-%m-%d %H:%i') , s.accepted,i.blackout
				                FROM ".Rsys :: getTable("subscription")." s 
				                INNER JOIN ".Rsys :: getTable("reservation")." r ON r.id = s.reservation_id
				                INNER JOIN ".Rsys :: getTable("item")." i ON i.id=r.item_id
				                WHERE s.user_id = '".api_get_user_id()."'";
		$sql .= " ORDER BY col".$column." ".$direction." LIMIT ".$from.",".$per_page;
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result, 'NUM'))
		{	$row = array();
			$row[] = $array[0];
			$row[] = $array[1];
			
			if($array[2]=='0000-00-00 00:00' && $array[3]=='0000-00-00 00:00')
			{
				$row[] = $array[5];
				$row[] = $array[6];
			}
			else
			{
				$row[] = $array[2];
				$row[] = $array[3];
			}
			if ($array[8]=='1')
			{
				$row[] = get_lang('Blackout');
			}
			else
			{
				if ($array[7]=='1')
				{
					$row[] = get_lang('Yes');
				}else
				{
					$row[] = get_lang('No');
				}
			}
			$row[] = $array[4];
			$arr[] = $row;
		}
		return $arr;

	}

	/**
	 *  Get number of subscriptions of the user
	 * 
	 *  @return -   int     The amount of itemrights
	 */
	function get_num_subscriptions() {
		$sql = "SELECT COUNT(*) FROM ".Rsys :: getTable("subscription")." s 
						                INNER JOIN ".Rsys :: getTable("reservation")." r ON r.id = s.reservation_id
						                INNER JOIN ".Rsys :: getTable("item")." i ON i.id=r.item_id
						                WHERE s.user_id = '".api_get_user_id()."'";
		return @ Database::result(api_sql_query($sql, __FILE__, __LINE__), 0, 0);
	}

	/**
	 *  Returns $reservation_id=>"START_AT - END_AT"
	 */
	/*function get_item_reservations($item_id){
	    $sql="SELECT r.id AS reservation_id, r.start_at, r.end_at
	            FROM ".Rsys::getTable('reservation')." r 
	            INNER JOIN ".Rsys::getTable('item')." i ON r.item_id=i.id 
	            WHERE i.id='".$item_id."'"; //  AND r.subscribe_until < NOW() // TODO: subscribe_until controle
	    $result=api_sql_query($sql, __FILE__, __LINE__);
	    while($array=Database::fetch_array($result))
	        $arr[$array['reservation_id']]=$array['start_at'].' - '.$array['end_at'];
	    return $arr;        
	}*/

	/**
	 *  Returns ALL reservations of a certain item with start_date between $from and $till
	 * 
	 *  @param  -   String  $from   DateTime
	 *  @param  -   String  $till   DateTime
	 *  @param  -   int     $itemid The itemId
	 *  @return -   Array   ['reservations'][RESERVATION_ID]=all info + array with all corresponding subscriptions
	 *                      ['min_start_at']    = the minimal start_at in all reservations (usefull to build table)
	 *                      ['max_end_at']      = the maximal end_at in all reservations   (usefull to build table)
	 */
	function get_item_reservations($from, $till, $itemid) {
		$sql = "SELECT r.*,i.name as item_name FROM ".Rsys :: getTable('reservation')." r
						                INNER JOIN ".Rsys :: getTable('item')." i ON r.item_id=i.id
						                LEFT JOIN ".Rsys :: getTable('item_rights')." ir ON ir.item_id=i.id 
						                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS)." c ON ir.class_id=c.id AND ir.item_id = i.id 
						                LEFT JOIN ".Database :: get_main_table(TABLE_MAIN_CLASS_USER)." cu ON cu.class_id = c.id 
						                WHERE r.item_id='".$itemid."' AND (((cu.user_id='".api_get_user_id()."' AND ir.view_right=1) OR 1=". (api_is_platform_admin() ? 1 : 0).") AND 
						                (r.start_at<='".$from."' AND r.end_at>='".$from."') OR (r.start_at>='".$from."' AND r.start_at<='".$till."')) ORDER BY start_at ASC";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$max_start_at = -1;
		$min_end_at = -1;
		$ids = '';
		$from_stamp = Rsys :: mysql_datetime_to_timestamp($from);
		$till_stamp = Rsys :: mysql_datetime_to_timestamp($till);
		if (Database::num_rows($result) == 0)
			return false;
		while ($array = Database::fetch_array($result)) {
			$ids .= $array['id'].',';
			$current_start_at = Rsys :: mysql_datetime_to_timestamp($array['start_at']);
			$current_end_at = Rsys :: mysql_datetime_to_timestamp($array['end_at']);
			if ($from_stamp > $current_start_at)
				$array['start_at'] = $from;
			$rarr['reservations'][$array['id']]['info'] = $array;
			/*
			if ($max_start_at == -1 || $current_start_at < $rarr['min_start_at'])
				$rarr['min_start_at'] = $current_start_at;
			if ($current_end_at > $rarr['max_end_at'])
				$rarr['max_end_at'] = $current_end_at;*/
		}
		$ids = substr($ids, 0, -1);
		$sql = "SELECT * FROM ".Rsys :: getTable('subscription')." WHERE reservation_id IN (".$ids.") AND (start_at='0000-00-00 00:00:00' OR (start_at<='".$from."' AND end_at>='".$from."') OR (start_at>='".$from."' AND start_at<='".$till."')) ORDER BY start_at ASC";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result, 'ASSOC')) {
			// echo $array['reservation_id'].': '.$array['start_at'].'-'.$array['end_at'].'<br />';
			if ($rarr['reservations'][$array['reservation_id']]['info']['timepicker']) { 
				$current_start_at = Rsys :: mysql_datetime_to_timestamp($array['start_at']);
				$current_end_at = Rsys :: mysql_datetime_to_timestamp($array['end_at']);
				if ($current_start_at < $from_stamp) //&& $current_end_at>=$from_stamp) || ($current_start_at>=$from_stamp && $current_start_at<=$till_stamp)))
					$array['start_at'] = $from;
				if ($current_end_at > $till_stamp)
					$array['end_at'] = $till;
			}
			$rarr['reservations'][$array['reservation_id']]['subscriptions'][] = $array;
		}
		return $rarr;
	}

	/**
	 *  Returns $reservation_id=>"START_AT - END_AT"
	 */
	function get_item_subfiltered_reservations($item_id) {
		$sql = "SELECT r.id AS reservation_id, r.start_at, r.end_at
						                FROM ".Rsys :: getTable('reservation')." r 
						                INNER JOIN ".Rsys :: getTable('item')." i ON r.item_id=i.id
						                WHERE r.id NOT IN (SELECT s.reservation_id FROM ".Rsys :: getTable('subscription')." s WHERE r.id=s.reservation_id AND s.user_id='".api_get_user_id()."') AND i.id='".$item_id."'"; //  AND r.subscribe_until < NOW() // TODO: subscribe_until controle
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result))
			$arr[$array['reservation_id']] = $array['start_at'].' - '.$array['end_at'];
		return $arr;
	}

	/**
	 *  Returns ALL subscriptions between $from and $till
	 * 
	 *  @param  -   String  $from   DateTime
	 *  @param  -   String  $till   DateTime
	 */
	function get_subscriptions($from, $till) {
		// TODO: only return for current user...
		$sql = "SELECT r.*,s.start_at AS tp_start,s.end_at AS tp_end,s.accepted FROM ".Rsys :: getTable('subscription')." s INNER JOIN ".Rsys :: getTable('reservation')." r ON s.reservation_id = r.id WHERE ((r.timepicker=0 AND r.start_at>='".$from."' AND r.end_at<='".$till."') OR (s.start_at>='".$from."' AND s.end_at<='".$till."'))";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		while ($array = Database::fetch_array($result)) {
			$arr[] = $array;
			if ($arr['timepicker'] == 1) {
				$arr['start_at'] = $arr['tp_start'];
				$arr['end_at'] = $arr['tp_end'];
			}
		}
		return $arr;
	}
	function get_item_id($item_name)
	{	
		$sql = "SELECT id FROM ".Rsys :: getTable('item')." WHERE name='".$item_name."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
		$result_array = Database::fetch_array($result);
		return $result_array['id'];
	}
}
$language_file = 'reservation';
$cidReset = true; 
require_once ('../inc/global.inc.php');
Rsys :: init();
require_once 'rcalendar.php';
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');

/*
$img=imagecreate(1,1);
$color=imagecolorallocate($img, 220, 90, 0);
imagefill($img,0,0,$color);
imagejpeg($img,'../img/px_orange.gif',100);

$img=imagecreate(1,1);
$color=imagecolorallocate($img, 0, 0, 0);
imagefill($img,0,0,$color);
imagejpeg($img,'../img/px_black.gif',100);
*/
?>
