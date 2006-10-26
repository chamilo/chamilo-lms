<?php
/*
 * Created on 30 mai 2006 by Elixir Interactive http://www.elixir-interactive.com
 */
 $langFile = array ('courses', 'index');
 include("main/inc/global.inc.php");
 include_once (api_get_path(LIBRARY_PATH)."/system_announcements.lib.php");

 $tool_name = get_lang("SystemAnnouncements"); // title of the page (should come from the language file)
 Display::display_header($tool_name);
 
 if(isset($_GET['start']))
 {
 	$start = (int)$_GET['start'];
 }
 else
 {
 	$start = 0;
 }
 
 if (isset($_uid))
 {
 	$visibility = api_is_allowed_to_create_course() ? VISIBLE_TEACHER : VISIBLE_STUDENT;
 	SystemAnnouncementManager :: display_all_announcements($visibility, $announcement, $start, $_uid);
 }
 else
 {
 	SystemAnnouncementManager :: display_all_announcements(VISIBLE_GUEST, $announcement, $start);
 }
 Display::display_footer();
?>
