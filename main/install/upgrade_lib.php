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

function upgrade_16x_to_180($values)
{
	$main_database = $values['database_main_db'];
	//select database
	mysql_select_db($main_database);
	//actual statements
}

function upgrade_180_to_182($values)
{

}

function upgrade_182_to_183($values)
{
	//no database/file structure changes needed?
}

?>