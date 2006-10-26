<?php
/**
 * @todo use Database :: get_course_table
 * @todo move the tool constants to the appropriate place
 * @todo make config settings out of $forum_setting
 * 
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 * @version february 2006, dokeos 1.8
 */


/*
-----------------------------------------------------------
	Database Variables
-----------------------------------------------------------
*/
$table_categories 		= "`".$_course["dbNameGlu"]."forum_category"."`"; 
$table_forums 			= "`".$_course["dbNameGlu"]."forum_forum"."`"; 
$table_threads 			= "`".$_course["dbNameGlu"]."forum_thread"."`"; 
$table_posts 			= "`".$_course["dbNameGlu"]."forum_post"."`"; 
$table_mailcue			= "`".$_course["dbNameGlu"]."forum_mailcue"."`"; 

$table_item_property 	= Database :: get_course_table(LAST_TOOL_EDIT_TABLE);
$table_users 			= Database :: get_main_table(MAIN_USER_TABLE);

/*
-----------------------------------------------------------
	Constants
-----------------------------------------------------------
*/
define("TOOL_FORUM_CATEGORY",'forum_category');
define("TOOL_FORUM",'forum');
define("TOOL_FORUM_THREAD",'forum_thread');
define("TOOL_FORUM_POST",'forum_post');




/*
-----------------------------------------------------------
	Some configuration settings 
	(these can go to the dokeos config settings afterwards)
-----------------------------------------------------------
*/
// if this setting is true then an I-frame will be displayed when replying 
$forum_setting['show_thread_iframe_on_reply']=true; 
// if this setting is true then students and teachers can check a checkbox so that they receive a mail when somebody replies to the thread
$forum_setting['allow_post_notificiation']=true; 
// when this setting is true then the course admin can post threads that are important. These posts remain on top all the time (until made unsticky)
// these special posts are indicated with a special icon also
$forum_setting['allow_sticky']=true; 
// when this setting is true there will be a column that displays the latest post (date and poster) of the given forum. This requires quite some sql statements that
// might slow down the page with the fora.
// note: I'm currently investigating how it would be possible to increase the performance of this part.
$forum_setting['show_last_post']=false; 

?>