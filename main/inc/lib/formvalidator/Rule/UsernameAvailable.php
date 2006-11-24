<?php
// $Id: UsernameAvailable.php 10190 2006-11-24 00:23:20Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software
	
	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) Bart Mollet, Hogeschool Gent
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact address: Dokeos, 44 rue des palais, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
require_once ('HTML/QuickForm/Rule.php');
/**
 * QuickForm rule to check if a username is available
 */
class HTML_QuickForm_Rule_UsernameAvailable extends HTML_QuickForm_Rule
{
	/**
	 * Function to check if a username is available
	 * @see HTML_QuickForm_Rule
	 * @param string $username Wanted username
	 * @param string $current_username 
	 * @return boolean True if username is available
	 */
	function validate($username,$current_username = null)
	{
		$user_table = Database::get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT * FROM $user_table WHERE username = '$username'";
		if(!is_null($current_username))
		{
			$sql .= " AND username != '$current_username'";
		}
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$number = mysql_num_rows($res);
		return $number == 0;
	}
}
?>