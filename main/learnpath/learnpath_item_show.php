<?php
/*
==============================================================================
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) Denes Nagy (darkden@freemail.hu)
	
	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	See the GNU General Public License for more details.
	
	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
============================================================================== 
* Can show some type of item in LP

* @author   Denes Nagy <darkden@freemail.hu>
* @version  2.0
* @access   public
* @package	dokeos.learnpath
============================================================================== 
*/

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

header('Content-Type: text/html; charset='. $charset);

switch ($type) {
	case "Introduction_text" :

					$TBL_INTRO  = Database::get_course_table(TOOL_INTRO_TABLE);
				    $result = api_sql_query("SELECT * FROM $TBL_INTRO WHERE id=1",__FILE__,__LINE__);
				    $myrow= mysql_fetch_array($result);
				    $intro=$myrow["intro_text"];
					echo "<html><head><link rel=stylesheet type=text/css href='../css/default.css'></head>",
					"<body><br>".$intro."</body></html>";

					break;
}
?>
