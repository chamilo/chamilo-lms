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
*/
function upgrade_16x_to_180($values)
{
	/*
		PRE SECTION
		UPGRADES TO GENERAL DATABASES before course upgrades
	*/

	//MAIN database section	
	//Get the list of queries to upgrade the main database
	$main_query_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','main');
	if(count($main_query_list)>0)
	{
		$main_database = $values['database_main_db'];
		mysql_select_db($main_database);
		
		foreach($main_query_list as $this_query)
		{
			mysql_query($this_query);
		}
	}

	//STATS database section	
	//Get the list of queries to upgrade the statistics/tracking database
	$statistic_query_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','stats');
	if(count($statistic_query_list)>0)
	{
		$statistic_database = $values['database_tracking'];
		mysql_select_db($statistic_database);
		
		foreach($statistic_query_list as $this_query)
		{
			mysql_query($this_query);
		}
	}

	//USER database section	
	//Get the list of queries to upgrade the user database
	$user_query_list = get_sql_file_contents('migrate-db-1.6.x-1.8.0-pre.sql','user');
	if(count($user_query_list)>0)
	{
		$user_database = $values['database_user'];
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

	/*
		POST SECTION
		UPGRADES TO GENERAL DATABASES after course upgrades
	*/
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