<?php
/*
==============================================================================
	Dokeos - elearning and course management software
	
	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>

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
*	This script allows platform admins to add users to urls.
*	It displays a list of users and a list of courses;
*	you can select multiple users and courses and then click on
*	@package dokeos.admin
============================================================================== 
*/

// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require ('../inc/global.inc.php');
$this_section=SECTION_PLATFORM_ADMIN;

require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');
api_protect_admin_script();
if (!$_configuration['multiple_access_urls'])
	header('Location: index.php');
	
/*
-----------------------------------------------------------
	Global constants and variables
-----------------------------------------------------------
*/

$form_sent = 0;
$first_letter_course = '';
$courses = array ();
$url_list = array();
$users = array();

$tbl_access_url_rel_course 	= Database :: get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
$tbl_access_url 			= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);
$tbl_course					= Database :: get_main_table(TABLE_MAIN_COURSE);

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/
$tool_name = get_lang('AddCoursesToURL');
$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ('url' => 'access_urls.php', 'name' => get_lang('MultipleAccessURLs'));

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

Display :: display_header($tool_name);

echo '<div class="actions" style="height:22px;">';
echo '<div style="float:right;">		
		<a href="'.api_get_path(WEB_CODE_PATH).'admin/access_url_edit_courses_to_url.php">'.Display::return_icon('edit.gif',get_lang('AddUserToURL'),'').get_lang('EditCoursesToURL').'</a>												
	  </div><br />';		  
echo '</div>';

api_display_tool_title($tool_name);

if ($_POST['form_sent']) {
	$form_sent = $_POST['form_sent'];
	$courses = is_array($_POST['course_list']) ? $_POST['course_list'] : array() ;
	$url_list = is_array($_POST['url_list']) ? $_POST['url_list'] : array() ;
	$first_letter_course = $_POST['first_letter_course'];

	foreach($users as $key => $value) {
		$users[$key] = intval($value);	
	}

	if ($form_sent == 1) {
		if ( count($courses) == 0 || count($url_list) == 0) {
			Display :: display_error_message(get_lang('AtLeastOneCourseAndOneURL'));
			//header('Location: access_urls.php?action=show_message&message='.get_lang('AtLeastOneUserAndOneURL'));
		} else {
			UrlManager::add_courses_to_urls($courses,$url_list);
			Display :: display_confirmation_message(get_lang('CourseBelongURL'));
			//header('Location: access_urls.php?action=show_message&message='.get_lang('UsersBelongURL'));				
		}
	}
}



/*
-----------------------------------------------------------
	Display GUI
-----------------------------------------------------------
*/

if(empty($first_letter_user))
{
	$sql = "SELECT count(*) as num_courses FROM $tbl_course";
	$result = api_sql_query($sql, __FILE__, __LINE__);
	$num_row = Database::fetch_array($result);	
	if($num_row['num_courses']>1000) 
	{//if there are too much num_courses to gracefully handle with the HTML select list,
	 // assign a default filter on users names
		$first_letter_user = 'A';
	}
	unset($result);
}

$first_letter_course = Database::escape_string($first_letter_course);
$sql = "SELECT code, title FROM $tbl_course
		WHERE title LIKE '".$first_letter_course."%' OR title LIKE '".strtolower($first_letter_course)."%'
		ORDER BY title, code DESC ";
		
$result = api_sql_query($sql, __FILE__, __LINE__);
$db_courses = api_store_result($result);
unset($result);

$sql = "SELECT id, url FROM $tbl_access_url  WHERE active=1 ORDER BY url";
$result = api_sql_query($sql, __FILE__, __LINE__);
$db_urls = api_store_result($result);
unset($result);
?>

<form name="formulaire" method="post" action="<?php echo api_get_self(); ?>" style="margin:0px;">
 <input type="hidden" name="form_sent" value="1"/>
  <table border="0" cellpadding="5" cellspacing="0" width="100%">
   <tr>
    <td width="40%" align="center">
     <b><?php echo get_lang('CourseList'); ?></b>
     <br/><br/>
     <?php echo get_lang('FirstLetterCourse'); ?> : 
     <select name="first_letter_course" onchange="javascript:document.formulaire.form_sent.value='2'; document.formulaire.submit();">
      <option value="">--</option>
      <?php
        echo Display :: get_alphabet_options($first_letter_course);
        echo Display :: get_numeric_options(0,9,$first_letter_course);
      ?>
     </select>
    </td>    
        <td width="20%">&nbsp;</td>
    <td width="40%" align="center">
     <b><?php echo get_lang('URLList'); ?> :</b>     
    </td>       
   </tr>
   <tr>
    <td width="40%" align="center">
     <select name="course_list[]" multiple="multiple" size="20" style="width:230px;">
		<?php
		foreach ($db_courses as $course) {
			?>
			<option value="<?php echo $course['code']; ?>" <?php if(in_array($course['code'],$courses)) echo 'selected="selected"'; ?>><?php echo $course['title'].' ('.$course['code'].')'; ?></option>
			<?php
		}
		?>
    </select>
   </td>
   <td width="20%" valign="middle" align="center">
    <input type="submit" value="<?php echo get_lang('AddCoursesToThatURL'); ?> &gt;&gt;"/>
   </td>
   <td width="40%" align="center">
    <select name="url_list[]" multiple="multiple" size="20" style="width:230px;">
		<?php
		foreach ($db_urls as $url_obj) {
			?>
			<option value="<?php echo $url_obj['id']; ?>" <?php if(in_array($url_obj['id'],$url_list)) echo 'selected="selected"'; ?>><?php echo $url_obj['url']; ?></option>
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