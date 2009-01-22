<?php
/*
==============================================================================
	Dokeos - elearning and course management software
	
	Copyright (c) 2007, various contributors
	
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
/**
==============================================================================
* This file contains functions used by the new upgrade script.
==============================================================================
*/
/*
==============================================================================
		CONSTANTS
==============================================================================
*/

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/**
* see
* - update-db-1.6.x-1.8.0.inc.php
* - update-db-scorm-1.6.x-1.8.0.inc.php
* - migrate-db-1.6.x-1.8.0-post.sql
* - migrate-db-1.6.x-1.8.0-pre.sql
* @todo remove code duplication in this function
*/
function upgrade_16x_to_180($values)
{
	$is_single_database = $values['database_single'];
	$main_database = $values['database_main_db'];
	$tracking_database = $values['database_tracking'];
	$user_database = $values['database_user'];

	/*
		PRE SECTION
		UPGRADES TO GENERAL DATABASES before course upgrades
	*/

	//MAIN database section	
	//Get the list of queries to upgrade the main database
	$main_query_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','main');
	if(count($main_query_list) > 0)
	{
		mysql_select_db($main_database);
		foreach($main_query_list as $this_query)
		{
			mysql_query($this_query);
		}
	}

	//TRACKING database section	
	//Get the list of queries to upgrade the statistics/tracking database
	$tracking_query_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','stats');
	if(count($tracking_query_list) > 0)
	{
		mysql_select_db($tracking_database);
		foreach($tracking_query_list as $this_query)
		{
			mysql_query($this_query);
		}
	}

	//USER database section	
	//Get the list of queries to upgrade the user database
	$user_query_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','user');
	if(count($user_query_list) > 0)
	{
		mysql_select_db($user_database);
		foreach($user_query_list as $this_query)
		{
			mysql_query($this_query);
		}
	}

	/*
		COURSE SECTION
		UPGRADES TO COURSE DATABASES
	*/
	$prefix = '';
    global $singleDbForm, $_configuration;
	if ($singleDbForm)
	{
		$prefix = $_configuration['table_prefix'];
	}
	//get the course databases queries list (c_q_list)
	$course_query_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','course');
	if(count($course_query_list) > 0)
	{
		//upgrade course databases
	}

	/*
		SCORM SECTION
	*/
	//see include('update-db-scorm-1.6.x-1.8.0.inc.php');
	//deploy in separate function!

	/*
		POST SECTION
		UPGRADES TO GENERAL DATABASES after course upgrades
	*/
	$main_query_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-post.sql','main');
	if(count($main_query_list) > 0)
	{
		mysql_select_db($main_database);
		foreach($main_query_list as $this_query)
		{
			mysql_query($this_query);
		}
	}

	$tracking_query_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-post.sql','stats');
	$tracking_query_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','stats');
	if(count($tracking_query_list) > 0)
	{
		mysql_select_db($tracking_database);
		foreach($tracking_query_list as $this_query)
		{
			mysql_query($this_query);
		}
	}

	$user_query_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-post.sql','user');
	if(count($user_query_list) > 0)
	{
		mysql_select_db($user_database);
		foreach($user_query_list as $this_query)
		{
			mysql_query($this_query);
		}
	}

	$prefix = ''; 
	if ($singleDbForm)
	{
		$prefix = $_configuration['table_prefix'];
	}
	//get the course databases queries list (c_q_list)
	$course_query_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','course');
	if(count($course_query_list) > 0)
	{
		//upgrade course databases
		mysql_select_db($main_database);
		$sql_result = mysql_query("SELECT code,db_name,directory,course_language FROM course WHERE target_course_code IS NULL");
		if(mysql_num_rows($sql_result) > 0)
		{
			while($row = mysql_fetch_array($sql_result))
			{
				$course_list[] = $row;
			}
			//for each course in the course list...
			foreach($course_list as $this_course)
			{
				mysql_select_db($this_course['db_name']);
				//... execute the list of course update queries
				foreach($course_query_list as $this_query)
				{
					if ($is_single_database) //otherwise just use the main one
					{
						$query = preg_replace('/^(UPDATE|ALTER TABLE|CREATE TABLE|DROP TABLE|INSERT INTO|DELETE FROM)\s+(\w*)(.*)$/',"$1 $prefix$2$3",$query);
					}
					mysql_query($this_query);
				}
			}
		}
	}
}

/**
* Note - there is no 1.8.1,
* 1.8.2 is the version that came after 1.8.0
* see
* - update-db-1.8.0-1.8.2.inc.php
* - migrate-db-1.8.0-1.8.2-pre.sql
*/
function upgrade_180_to_182($values)
{

}

function upgrade_182_to_183($values)
{
	//no database/file structure changes needed?
}

?>