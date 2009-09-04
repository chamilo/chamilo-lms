<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)

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
*	These files are a complete rework of the forum. The database structure is
*	based on phpBB but all the code is rewritten. A lot of new functionalities
*	are added:
* 	- forum categories and forums can be sorted up or down, locked or made invisible
*	- consistent and integrated forum administration
* 	- forum options: 	are students allowed to edit their post?
* 						moderation of posts (approval)
* 						reply only forums (students cannot create new threads)
* 						multiple forums per group
*	- sticky messages
* 	- new view option: nested view
* 	- quoting a message
*
*	@Author Patrick Cool <patrick.cool@UGent.be>, Ghent University
*	@Copyright Ghent University
*	@Copyright Patrick Cool
*
* 	@package dokeos.forum
*/

/**
 **************************************************************************
 *						IMPORTANT NOTICE
 * Please do not change anything is this code yet because there are still
 * some significant code that need to happen and I do not have the time to
 * merge files and test it all over again. So for the moment, please do not
 * touch the code
 * 							-- Patrick Cool <patrick.cool@UGent.be>
 **************************************************************************
 */

/*
==============================================================================
		INIT SECTION
==============================================================================
*/
/*
-----------------------------------------------------------
	Language Initialisation
-----------------------------------------------------------
*/
// name of the language file that needs to be included
$language_file = 'forum';
//$this_section=SECTION_COURSES;
require_once '../inc/global.inc.php';
/* ------------	ACCESS RIGHTS ------------ */
// notice for unauthorized people.
api_protect_course_script(true);

require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
include_once (api_get_path(LIBRARY_PATH).'groupmanager.lib.php');

$nameTools=get_lang('Forum');
?>
<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<title></title>
<style type="text/css" media="screen, projection">
/*<![CDATA[*/
@import "<?php echo api_get_path(WEB_CODE_PATH); ?>css/<?php echo api_get_setting('stylesheets');?>/default.css";
/*]]>*/
</style>
</head>
<body>
<?php

/*
-----------------------------------------------------------
	Including necessary files
-----------------------------------------------------------
*/
require 'forumconfig.inc.php';
require_once 'forumfunction.inc.php';


/*
==============================================================================
		MAIN DISPLAY SECTION
==============================================================================
*/
/*
-----------------------------------------------------------
	Retrieving forum and forum categorie information
-----------------------------------------------------------
*/
// we are getting all the information about the current forum and forum category.
// note pcool: I tried to use only one sql statement (and function) for this
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table
$current_thread=get_thread_information($_GET['thread']); // note: this has to be validated that it is an existing thread
$current_forum=get_forum_information($current_thread['forum_id']); // note: this has to be validated that it is an existing forum.
$current_forum_category=get_forumcategory_information($current_forum['forum_category']);

/*
-----------------------------------------------------------
	Is the user allowed here?
-----------------------------------------------------------
*/
// if the user is not a course administrator and the forum is hidden
// then the user is not allowed here.
if (!api_is_allowed_to_edit(false,true) AND ($current_forum['visibility']==0 OR $current_thread['visibility']==0)) {
	forum_not_allowed_here();
}

/*
-----------------------------------------------------------
	Display Forum Category and the Forum information
-----------------------------------------------------------
*/
// we are getting all the information about the current forum and forum category.
// note pcool: I tried to use only one sql statement (and function) for this
// but the problem is that the visibility of the forum AND forum cateogory are stored in the item_property table

$sql="SELECT * FROM $table_posts posts, $table_users users
		WHERE posts.thread_id='".$current_thread['thread_id']."'
		AND posts.poster_id=users.user_id
		ORDER BY posts.post_id ASC";
$result=api_sql_query($sql, __FILE__, __LINE__);

echo "<table width=\"100%\" cellspacing=\"5\" border=\"0\">\n";
while ($row=Database::fetch_array($result)) {
	echo "\t<tr>\n";
	echo "\t\t<td rowspan=\"2\" class=\"forum_message_left\">";
	if ($row['user_id']=='0') {
		$name=$row['poster_name'];
	} else {
		$name=api_get_person_name($row['firstname'], $row['lastname']);
	}
	echo $name.'<br />';
	echo $row['post_date'].'<br /><br />';

	echo "</td>\n";
	echo "\t\t<td class=\"forum_message_post_title\">".$row['post_title']."</td>\n";
	echo "\t</tr>\n";

	echo "\t<tr>\n";
	echo "\t\t<td class=\"forum_message_post_text\">".$row['post_text']."</td>\n";
	echo "\t</tr>\n";
}
echo "</table>";

?>
</body>
</html>