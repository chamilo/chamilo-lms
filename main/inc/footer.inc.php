<?php
/* For licensing terms, see /dokeos_license.txt */

/**
==============================================================================
*	This script displays the footer that is below (almost)
*	every Dokeos web page.
*
*	@package dokeos.include
==============================================================================
*/

/**** display of tool_navigation_menu according to admin setting *****/
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');

if (api_get_setting('show_navigation_menu') != 'false') {

   $course_id = api_get_course_id();
   if (!empty($course_id) && ($course_id != -1)) {
   		if ( api_get_setting('show_navigation_menu') != 'icons') {
	    	echo '</div> <!-- end #center -->';
    		echo '</div> <!-- end #centerwrap -->';
		}
      	require_once api_get_path(INCLUDE_PATH).'tool_navigation_menu.inc.php';
      	show_navigation_menu();
   }
}
/***********************************************************************/
?>
 <div class="clear">&nbsp;</div> <!-- 'clearing' div to make sure that footer stays below the main and right column sections -->
</div> <!-- end of #main" started at the end of banner.inc.php -->

<div class="push"></div>
</div> <!-- end of #wrapper section -->

<div id="footer"> <!-- start of #footer section -->
<div id="bottom_corner"></div>
<div class="copyright">
<?php
global $_configuration;
echo get_lang("Platform"), ' <a href="http://www.dokeos.com" target="_blank">Dokeos ', $_configuration['dokeos_version'], '</a> &copy; ', date('Y');
// Server mode indicator.
if (api_is_platform_admin()) {
	if (api_get_setting('server_type') == 'test') {
		echo ' <a href="'.api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Platform#server_type">';
		echo '<span style="background-color: white; color: red; border: 1px solid red;">&nbsp;Test&nbsp;server&nbsp;mode&nbsp;</span></a>';
	}
}
?>
</div>

<?php
/*
-----------------------------------------------------------------------------
	Plugins for footer section
-----------------------------------------------------------------------------
*/
api_plugin('footer');

if (api_get_setting('show_administrator_data')=='true') {

	// Platform manager
	echo '<span id="platformmanager">', get_lang('Manager'), ' : ', Display::encrypted_mailto_link(api_get_setting('emailAdministrator'), api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname')));

}

if (api_get_setting('show_tutor_data')=='true'){

	// course manager
	$id_course=api_get_course_id();
	$id_session=api_get_session_id();
	if (isset($id_course) && $id_course!=-1) {
		echo '<span id="coursemanager">';
		if ($id_session!=0){
			$mail=CourseManager::get_email_of_tutor_to_session($id_session);
			echo '&nbsp;'.get_lang('Tutor')." : ";
			foreach ($mail as $v=>$k) {
				echo Display::encrypted_mailto_link($v,$k);
			}
		}
		echo '</span>';
	}

}

if (api_get_setting('show_teacher_data')=='true') {
	// course manager
	$id_course=api_get_course_id();
	if (isset($id_course) && $id_course!=-1) {
		echo '<span id="coursemanager">';
		$mail=CourseManager::get_emails_of_tutors_to_course($id_course);
		if (!empty($mail)) {
			if (count($mail)>1){
				$bar='&nbsp;|&nbsp;';
				echo '&nbsp;'.get_lang('Teachers').' : ';
			} else {
				$bar='';
				echo '&nbsp;'.get_lang('Teacher').' : ';
			}
			foreach ($mail as $value=>$key) {
				foreach ($key as $email=>$name){
					echo Display::encrypted_mailto_link($email,$name).$bar;
				}
			}
		}
		echo '</span>';
	}

}


?>&nbsp;
</div> <!-- end of #footer -->
</body>
</html>
