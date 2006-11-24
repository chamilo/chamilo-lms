<?php
// $Id: subscribe_user2course.php 10190 2006-11-24 00:23:20Z pcool $
/*
==============================================================================
	Dokeos - elearning and course management software
	
	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert
	
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
*	This script allows platform admins to add users to courses.
*	It displays a list of users and a list of courses;
*	you can select multiple users and courses and then click on
*	'Add to this(these) course(s)'.
*
*	@package dokeos.admin
============================================================================== 
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

$langFile = 'admin';

$cidReset = true;

require ('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

require_once(api_get_path(LIBRARY_PATH).'course.lib.php');
api_protect_admin_script();
/*
-----------------------------------------------------------
	Global constants and variables
-----------------------------------------------------------
*/

$users = $_GET['users'];
$form_sent = 0;
$first_letter_user = '';
$first_letter_course = '';
$courses = array ();
$users = array();

$tbl_course = Database :: get_main_table(TABLE_MAIN_COURSE);
$tbl_user 	= Database :: get_main_table(TABLE_MAIN_USER);

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
$tool_name = get_lang('AddUsersToACourse');
//$interbreadcrumb[] = array ("url" => "index.php", "name" => get_lang('PlatformAdmin'));
Display :: display_header($tool_name);
//api_display_tool_title($tool_name);

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

/*
-----------------------------------------------------------
	React on POSTed request
-----------------------------------------------------------
*/
if ($_POST['formSent'])
{
	$form_sent = $_POST['formSent'];
	$users = is_array($_POST['UserList']) ? $_POST['UserList'] : array() ;
	$courses = is_array($_POST['CourseList']) ? $_POST['CourseList'] : array() ;
	$first_letter_user = $_POST['firstLetterUser'];
	$first_letter_course = $_POST['firstLetterCourse'];

	foreach($users as $key => $value)
	{
		$users[$key] = intval($value);	
	}

	if ($form_sent == 1)
	{
		if ( count($users) == 0 || count($courses) == 0)
		{
			Display :: display_error_message(get_lang('AtLeastOneUserAndOneCourse'));
		}
		else
		{
			foreach ($courses as $course_code)
			{
				foreach ($users as $user_id)
				{
					CourseManager::subscribe_user($user_id,$course_code);
				}
			}
			Display :: display_normal_message(get_lang('UsersAreSubscibedToCourse'));
		}
	}
}

/*
-----------------------------------------------------------
	Display GUI
-----------------------------------------------------------
*/

$sql = "SELECT user_id,lastname,firstname,username FROM $tbl_user WHERE lastname LIKE '".$first_letter_user."%' ORDER BY ". (count($users) > 0 ? "(user_id IN(".implode(',', $users).")) DESC," : "")." lastname";
$result = api_sql_query($sql, __FILE__, __LINE__);
$db_users = api_store_result($result);
$sql = "SELECT code,visual_code,title FROM $tbl_course WHERE visual_code LIKE '".$first_letter_course."%' ORDER BY ". (count($courses) > 0 ? "(code IN('".implode("','", $courses)."')) DESC," : "")." visual_code";
$result = api_sql_query($sql, __FILE__, __LINE__);
$db_courses = api_store_result($result);
?>

<form name="formulaire" method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>" style="margin:0px;">
 <input type="hidden" name="formSent" value="1"/>
  <table border="0" cellpadding="5" cellspacing="0" width="100%">
   <tr>
    <td width="40%" align="center">
     <b><?php echo get_lang('UserList'); ?></b>
     <br/><br/>
     <?php echo get_lang('FirstLetterUser'); ?> : 
     <select name="firstLetterUser" onchange="javascript:document.formulaire.formSent.value='2'; document.formulaire.submit();">
      <option value="">--</option>
      <?php
        echo Display :: get_alphabet_options($first_letter_user);
      ?>
     </select>
    </td>
    <td width="20%">&nbsp;</td>
    <td width="40%" align="center">
     <b><?php echo get_lang('CourseList'); ?> :</b>
     <br/><br/>
     <?php echo get_lang('FirstLetterCourse'); ?> : 
     <select name="firstLetterCourse" onchange="javascript:document.formulaire.formSent.value='2'; document.formulaire.submit();">
      <option value="">--</option>
      <?php
      echo Display :: get_alphabet_options($first_letter_course);
      ?> 
     </select>
    </td>
   </tr>
   <tr>
    <td width="40%" align="center">
     <select name="UserList[]" multiple="multiple" size="20" style="width:230px;">
<?php
foreach ($db_users as $user)
{
?>
	  <option value="<?php echo $user['user_id']; ?>" <?php if(in_array($user['user_id'],$users)) echo 'selected="selected"'; ?>><?php echo $user['lastname'].' '.$user['firstname'].' ('.$user['username'].')'; ?></option>
<?php
}
?>
    </select>
   </td>
   <td width="20%" valign="middle" align="center">
    <input type="submit" value="<?php echo get_lang('AddToThatCourse'); ?> &gt;&gt;"/>
   </td>
   <td width="40%" align="center">
    <select name="CourseList[]" multiple="multiple" size="20" style="width:230px;">
<?php
foreach ($db_courses as $course)
{
?>
	 <option value="<?php echo $course['code']; ?>" <?php if(in_array($course['code'],$courses)) echo 'selected="selected"'; ?>><?php echo '('.$course['visual_code'].') '.$course['title']; ?></option>
<?php
}
?>
    </select>
   </td>
  </tr>
 </table>
</form>
<?php
/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>