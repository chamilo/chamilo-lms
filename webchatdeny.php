<?php
/*
============================================================================== 
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos SPRL
	Copyright (c) Denes Nagy
	
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
 * @todo can't this be moved to a different file so that we can delete this file? 
 * 		 Is this still in use? If not, then it should be removed or maybe offered as an extension
 */
/**
============================================================================== 
* Deletes the web-chat request form the user table
============================================================================== 
*/

// name of the language file that needs to be included 
$language_file = "index";

// including necessary files
include_once('./main/inc/global.inc.php');

// table definitions
$track_user_table = Database::get_main_table(TABLE_MAIN_USER);

$sql="update $track_user_table set chatcall_user_id = '', chatcall_date = '', chatcall_text='DENIED' where (user_id = ".$_user['user_id'].")";
$result=api_sql_query($sql,__FILE__,__LINE__);

Display::display_header();

$message=get_lang("RequestDenied")."<br><br><a href='javascript:history.back()'>".get_lang("Back")."</a>";
Display::display_normal_message($message);

/*
============================================================================== 
		FOOTER 
============================================================================== 
*/ 

Display::display_footer();
?>
