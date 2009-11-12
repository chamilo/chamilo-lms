<?php // $Id: course_virtual.php 20441 2009-05-10 07:39:15Z ivantcholakov $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2005 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Roan Embrechts (Vrije Universiteit Brussel)

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
*	@author Roan Embrechts - initial admin interface
*	@package dokeos.admin
==============================================================================
*/

/*
==============================================================================
		INIT SECTION
==============================================================================
*/

// name of the language file that needs to be included
$language_file = 'admin';
$extra_lang_file = "create_course";

// global settings initialisation
// also provides access to main api (inc/lib/main_api.lib.php)
include("../inc/global.inc.php");
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

if (isset($extra_lang_file)) include(api_get_path(INCLUDE_PATH)."../lang/english/".$extra_lang_file.".inc.php");
if (isset($extra_lang_file)) include(api_get_path(INCLUDE_PATH)."../lang/".$language_interface."/".$extra_lang_file.".inc.php");

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/

include_once(api_get_path(LIBRARY_PATH) . 'course.lib.php');

/*
-----------------------------------------------------------
	Constants
-----------------------------------------------------------
*/

define ("CREATE_VIRTUAL_COURSE_OPTION", "create_virtual_course");
define ("DISPLAY_VIRTUAL_COURSE_LIST_OPTION", "display_virtual_course_list");

define ("FORM_ELEMENT_CODE_SIZE", "20");
define ("FORM_ELEMENT_TEXT_SIZE", "60");
define ("SELECT_BOX_SIZE", "10");

define ("COURSE_TITLE_FORM_NAME", "course_title");
define ("LANGUAGE_SELECT_FORM_NAME" , "course_language");
define ("REAL_COURSE_SELECT_FORM_NAME" , "real_course_code");
define ("WANTED_COURSE_CODE_FORM_NAME" , "wanted_course_code");
define ("COURSE_CATEGORY_FORM_NAME" , "course_category");

/*
-----------------------------------------------------------
	Header
-----------------------------------------------------------
*/

$tool_name = get_lang('AdminManageVirtualCourses'); // title of the page (should come from the language file)

$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));

Display::display_header($tool_name);

/*
==============================================================================
		DISPLAY FUNCTIONS
==============================================================================
*/

function make_strong($text)
{
	return "<strong>" . $text . "</strong>";
}


/**
*	Return a list of language directories.
*	@todo function does not belong here, move to code library,
*	also see infocours.php and index.php which contain a similar function
*/
function get_language_folder_list($dirname)
{
	if($dirname[strlen($dirname)-1]!='/') $dirname.='/';
	$handle=opendir($dirname);
	while ($entries = readdir($handle))
	{
		if ($entries=='.' || $entries=='..' || $entries=='CVS') continue;
		if (is_dir($dirname.$entries))
		{
			$language_list[] = $entries;
		}
	}
	closedir($handle);
	return $language_list;
}

/**
* Displays a select element (drop down menu) so the user can select
* the course language.
* @todo function does not belong here, move to (display?) library,
* @todo language display used apparently no longer existing array, converted to english for now.
* but we should switch to display the real language names.
*/
function display_language_select($element_name)
{
	global $platformLanguage;

	//get language list
	$dirname = api_get_path(SYS_PATH)."main/lang/";
	$language_list = get_language_folder_list($dirname);
	sort($language_list);

	//build array with strings to display
	foreach ($language_list as $this_language)
	{
		$language_to_display[$this_language] = $this_language;
	}

	//sort alphabetically
	//warning: key,value association needs to be maintained --> asort instead of sort
	asort($language_to_display);

	$user_selected_language = $_SESSION["user_language_choice"];
	if (! isset($user_selected_language) ) $user_selected_language = $platformLanguage;

	//display
	echo "<select name=\"$element_name\">";
	foreach ($language_to_display as $key => $value)
	{
		if ($key == $user_selected_language) $option_end = "selected >";
		else $option_end = ">";
		echo "<option value=\"$key\" $option_end";

		echo $value;
		echo "</option>\n";
	}
	echo "</select>";
}

/**
*	This code creates a select form element to let the user
*	choose a real course to link to.
*
*	We display the course code, but internally store the course id.
*/
function display_real_course_code_select($element_name)
{
	$real_course_list = CourseManager::get_real_course_list();

	echo "<select name=\"$element_name\" size=\"".SELECT_BOX_SIZE."\" >\n";
	foreach($real_course_list as $real_course)
	{
		$course_code = $real_course["code"];
		echo "<option value=\"". $course_code ."\">";
		echo $course_code;
		echo "</option>\n";
	}
	echo "</select>\n";
}


function display_create_virtual_course_form()
{
	global $charset;

	$category_table = Database::get_main_table(TABLE_MAIN_CATEGORY);

	$message = make_strong(get_lang('AdminCreateVirtualCourse')) . "<br/>" . get_lang('AdminCreateVirtualCourseExplanation') . "<br/>This feature is in development phase, bug reports welcome.";
	?>
	<p><?php echo $message;	?></p>
	<b><?php echo get_lang('MandatoryFields') ?></b>
	<form method="post" action="<?php echo api_get_self(); ?>">
	<table>
	<tr valign="top">
	<td colspan="2">

	</td>
	</tr>

	<tr valign="top">
	<td align="right">
		<?php
			echo make_strong(get_lang('CourseTitle')) . "&nbsp;";
			echo "</td>";
			echo "<td valign=\"top\">";
			echo "<input type=\"Text\" name=\"".COURSE_TITLE_FORM_NAME."\" size=\"".FORM_ELEMENT_TEXT_SIZE."\" value=\"$valueIntitule\"/><br />".get_lang('Ex') ;
		?>
	</td>
	</tr>

	<tr valign="top">
	<td align="right"><?php echo make_strong(get_lang('CourseFaculty')) . "&nbsp;"; ?> </td>
	<td>
		<?php
			echo "<select name=\"".COURSE_CATEGORY_FORM_NAME."\">";

			$sql_query = "SELECT code, name
									FROM $category_table
									WHERE auth_course_child ='TRUE'
									ORDER BY tree_pos";
			$category_result = Database::query($sql_query, __FILE__, __LINE__);

			while ($current_category = Database::fetch_array($category_result))
			{
				echo "<option value=\"", $current_category["code"], "\"";
				echo ">(", $current_category["code"], ") ", $current_category["name"];
				echo "</option>\n";
			}
		?>
	</select>
	<br /><?php echo make_strong(get_lang('TargetFac'))  . "&nbsp;" ?>
	</td>
	</tr>

	<tr valign="top">
	<td align="right"><?php echo make_strong(get_lang('Code'))  . "&nbsp;" ?> </td>
	<td>
	<?php
	echo "<input type=\"Text\" name=\"".WANTED_COURSE_CODE_FORM_NAME."\" maxlength=\"".FORM_ELEMENT_CODE_SIZE."\" value=\"$valuePublicCode\"/>
	<br/>" . get_lang('Max');
	?>
	</td>
	</tr>

	<tr valign="top">
	<td align="right">
	<?php echo make_strong(get_lang('RealCourseCode'))  . "&nbsp;" ?>
	</td>
	<td>
		<?php
			display_real_course_code_select(REAL_COURSE_SELECT_FORM_NAME);
			//echo "<input type=\"Text\" name=\"real_course_code\" maxlength=\"".FORM_ELEMENT_CODE_SIZE."\" value=\"" . api_htmlentities($valueTitular, ENT_COMPAT, $charset) . "\"/>";
		?>
	</td>
	</tr>

	<tr valign="top">
	<td align="right">
		<?php
			echo make_strong(get_lang('CourseLanguage')) . "&nbsp;";
		?>
	</td>
	<td> <?php  display_language_select(LANGUAGE_SELECT_FORM_NAME); ?>

	</td>
	</tr>
	<tr valign="top">
	<td>
	</td>
	<td>
	<input type="Submit" name="submit_create_virtual_course" value="<?php echo get_lang('Ok')?>"/>
	</td>
	</tr>
	</table>
	</form>
	<?php
}

function display_main_options()
{
	$message = "<ul><li><a href=\"?action=".CREATE_VIRTUAL_COURSE_OPTION."\">".get_lang('CreateVirtualCourse')."</a></li>";
	$message .= "<li><a href=\"?action=".DISPLAY_VIRTUAL_COURSE_LIST_OPTION."\">".get_lang('DisplayListVirtualCourses')."</a></li></ul>";
	echo $message;
}

function display_virtual_course_list()
{
	$course_list = CourseManager::get_virtual_course_list();
	if (! is_array($course_list) )
	{
		//there are no virtual courses
		echo "<i>".get_lang('ThereAreNoVirtualCourses')."</i>";
		return;
	}

	$column_header[] = array(get_lang('Title'),true);
	$column_header[] = array(get_lang('Code'),true);
	$column_header[] = array(get_lang('VisualCode'),true);
	$column_header[] = array(get_lang('LinkedCourseTitle'),true);
	$column_header[] = array(get_lang('LinkedCourseCode'),true);
	$table_data = array();
	for($i = 0; $i < count($course_list); $i++)
	{
		$course_list[$i] = Database::generate_abstract_course_field_names($course_list[$i]);
		$target_course_code = $course_list[$i]["target_course_code"];
		$real_course_info = Database::get_course_info($target_course_code);

		$row = array();
		$row[] = $course_list[$i]["title"];
		$row[] = $course_list[$i]["system_code"];
		$row[] = $course_list[$i]["visual_code"];
		$row[] = $real_course_info["title"];
		$row[]= $real_course_info["system_code"];
		$table_data[] = $row;
	}
	Display::display_sortable_table($column_header,$table_data,array(),array(),array('action'=>$_GET['action']));
}


/*
==============================================================================
		TOOL LOGIC FUNCTIONS
==============================================================================
*/

/**
*	Checks all parameters needed to create a virtual course.
*	If they are all set, the virtual course creation procedure is called.
*	Call this function instead of create_virtual_course
*/
function attempt_create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category)
{
	//better: create parameter list, check the entire list, when false display errormessage
	CourseManager::check_parameter_or_fail($real_course_code, "Unspecified parameter: real course id.");
	CourseManager::check_parameter_or_fail($course_title, "Unspecified parameter: course title.");
	CourseManager::check_parameter_or_fail($wanted_course_code, "Unspecified parameter: wanted course code.");
	CourseManager::check_parameter_or_fail($course_language, "Unspecified parameter: course language.");
	CourseManager::check_parameter_or_fail($course_category, "Unspecified parameter: course category.");

	$message = get_lang('AttemptedCreationVirtualCourse') . "<br/>";
	$message .= get_lang('CourseTitle') . " " . $course_title . "<br/>";
	$message .= get_lang('WantedCourseCode') . " " . $wanted_course_code . "<br/>";
	$message .= get_lang('CourseLanguage') . " " . $course_language . "<br/>";
	$message .= get_lang('CourseFaculty') . " " . $course_category . "<br/>";
	$message .= get_lang('LinkedToRealCourseCode') . " " . $real_course_code . "<br/>";

	Display::display_normal_message($message);

	$creation_success = CourseManager::create_virtual_course( $real_course_code, $course_title, $wanted_course_code, $course_language, $course_category );

	if ($creation_success == true)
	{
		Display::display_normal_message( $course_title . " - " . get_lang('CourseCreationSucceeded') );
		return true;
	}
	return false;
}

/*
==============================================================================
		MAIN CODE
==============================================================================
*/

$action = $_GET["action"];
$attempt_create_virtual_course = $_POST["submit_create_virtual_course"];

//api_display_tool_title($tool_name);

if ( isset($attempt_create_virtual_course) && $attempt_create_virtual_course )
{
	$real_course_code = $_POST[REAL_COURSE_SELECT_FORM_NAME];
	$course_title = $_POST[COURSE_TITLE_FORM_NAME];
	$wanted_course_code = $_POST[WANTED_COURSE_CODE_FORM_NAME];
	$course_language = $_POST[LANGUAGE_SELECT_FORM_NAME];
	$course_category = $_POST[COURSE_CATEGORY_FORM_NAME];

	$message = get_lang('AttemptedCreationVirtualCourse') . "<br/>";
	$message .= get_lang('CourseTitle') . " " . $course_title . "<br/>";
	$message .= get_lang('WantedCourseCode') . " " . $wanted_course_code . "<br/>";
	$message .= get_lang('CourseLanguage') . " " . $course_language . "<br/>";
	$message .= get_lang('CourseFaculty') . " " . $course_category . "<br/>";
	$message .= get_lang('LinkedToRealCourseCode') . " " . $real_course_code . "<br/>";

	Display::display_normal_message($message);

	$creation_success = CourseManager::attempt_create_virtual_course($real_course_code, $course_title, $wanted_course_code, $course_language, $course_category);

	if ($creation_success == true)
	{
		Display::display_normal_message( $course_title . " - " . get_lang('CourseCreationSucceeded') );
	}
	else
	{
		//should display error message
	}
	echo "<br/>";
}


display_main_options();

switch($action)
{
	case CREATE_VIRTUAL_COURSE_OPTION:
							display_create_virtual_course_form();
							break;
	case DISPLAY_VIRTUAL_COURSE_LIST_OPTION:
							display_virtual_course_list();
							break;
}

/*
==============================================================================
		FOOTER
==============================================================================
*/
Display::display_footer();
?>
