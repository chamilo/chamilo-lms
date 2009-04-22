<?php // $Id: footer.inc.php 19990 2009-04-22 20:11:36Z cvargas1 $
 
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

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
==============================================================================
*	This script displays the footer that is below (almost)
*	every Dokeos web page.
*
*	@package dokeos.include
==============================================================================
*/

/**** display of tool_navigation_menu according to admin setting *****/
require_once (api_get_path(LIBRARY_PATH).'course.lib.php');

if(api_get_setting('show_navigation_menu') != 'false')
{

   $course_id = api_get_course_id();
   if ( !empty($course_id) && ($course_id != -1) )
   {
   		if( api_get_setting('show_navigation_menu') != 'icons')
		{
	    	echo '</div> <!-- end #center -->';
    		echo '</div> <!-- end #centerwrap -->';
		}
      	require_once(api_get_path(INCLUDE_PATH)."tool_navigation_menu.inc.php");
      	show_navigation_menu();
   }
}
/***********************************************************************/
?>
 <div class="clear">&nbsp;</div> <!-- 'clearing' div to make sure that footer stays below the main and right column sections -->
</div> <!-- end of #main" started at the end of banner.inc.php -->

<div id="footer"> <!-- start of #footer section -->
<div id="bottom_corner"></div> 
 <div class="copyright">
  <?php global $_configuration; ?>
  <?php echo get_lang("Platform") ?> <a href="http://www.dokeos.com">Dokeos <?php echo $_configuration['dokeos_version']; ?></a> &copy; <?php echo date('Y'); ?>
 </div>

<?php
/*
-----------------------------------------------------------------------------
	Plugins for footer section
-----------------------------------------------------------------------------
*/
api_plugin('footer');
?>
<?php
if (get_setting('show_administrator_data')=="true") {
	
	// platform manager
	echo "<span id=\"platformmanager\">".get_lang("Manager") ?> : <?php echo Display::encrypted_mailto_link(get_setting('emailAdministrator'),get_setting('administratorName')." ".get_setting('administratorSurname'));

	// course manager
	$id_course=api_get_course_id();
	$id_session=api_get_session_id();
	if (isset($id_course) && $id_course!=-1){
		echo "<span id=\"coursemanager\">";
		if ($id_session==0){
			$mail=CourseManager::get_emails_of_tutors_to_course($id_course);
			if (count($mail)>1){
				$bar='&nbsp;|&nbsp;';
				echo '&nbsp;'.get_lang('Teachers')." : ";
			} else {
				$bar='';
				echo '&nbsp;'.get_lang('Teacher')." : ";
			}
			foreach($mail as $value=>$key) {
				foreach($key as $email=>$name){
					echo Display::encrypted_mailto_link($email,$name).$bar;		
				}
			}
		} else {
			$mail=CourseManager::get_email_of_tutor_to_session($id_session);
			echo '&nbsp;'.get_lang('Tutor')." : ";
			foreach($mail as $v=>$k) {
				echo Display::encrypted_mailto_link($v,$k); 
			}
		}
		echo '</span>';
	} 
}
?>&nbsp;
</div> <!-- end of #footer -->
</body>
</html>