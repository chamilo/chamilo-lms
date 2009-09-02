<?php // $Id: course_import.php 8216 2006-03-15 16:33:13Z turboke $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL
	Copyright (c) 2005 Bart Mollet <bart.mollet@hogent.be>

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
==============================================================================
* This tool allows platform admins to create courses by uploading a CSV file
* @todo Add some langvars to DLTT
* @package dokeos.admin
==============================================================================
*/
/**
 * validate the imported data
 */
function validate_data($courses) {
	global $_configuration;	
	$dbnamelength = strlen($_configuration['db_prefix']);
	//Ensure the prefix + database name do not get over 40 characters
	$maxlength = 40 - $dbnamelength;

	$errors = array ();
	$coursecodes = array ();
	foreach ($courses as $index => $course) {
		$course['line'] = $index +1;
		//1. check if mandatory fields are set
		$mandatory_fields = array ('Code', 'Title', 'CourseCategory', 'Teacher');
		foreach ($mandatory_fields as $key => $field) {
			if (!isset ($course[$field]) || strlen($course[$field]) == 0)
			{
				$course['error'] = get_lang($field.'Mandatory');
				$errors[] = $course;
			}
		}
		//2. check if code isn't in use
		if (isset ($course['Code']) && strlen($course['Code']) != 0) {
			//2.1 check if code allready used in this CVS-file
			if (isset ($coursecodes[$course['Code']])) {
				$course['error'] = get_lang('CodeTwiceInFile');
				$errors[] = $course;
			} elseif (api_strlen($course['Code']) > $maxlength) {
				$course['error'] = get_lang('Max');
				$errors[] = $course;
			}
			//2.3 check if code allready used in DB
			else {
				$course_table = Database :: get_main_table(TABLE_MAIN_COURSE);
				$sql = "SELECT * FROM $course_table WHERE code = '".Database::escape_string($course['Code'])."'";
				$res = api_sql_query($sql, __FILE__, __LINE__);
				if (Database::num_rows($res) > 0) {
					$course['error'] = get_lang('CodeExists');
					$errors[] = $course;
				}
			}
			$coursecodes[$course['Code']] = 1;
		}
		//3. check if teacher exists
		if (isset ($course['Teacher']) && strlen($course['Teacher']) != 0)
		{
			if (UserManager :: is_username_available($course['Teacher']))
			{
				$course['error'] = get_lang('UnknownTeacher').' ('.$course['Teacher'].')';
				$errors[] = $course;
			}
		}
		//4. check if category exists
		if (isset ($course['CourseCategory']) && strlen($course['CourseCategory']) != 0)
		{
			$category_table = Database :: get_main_table(TABLE_MAIN_CATEGORY);
			$sql = "SELECT * FROM $category_table WHERE code = '".mysql_real_escape_string($course['CourseCategory'])."'";
			$res = api_sql_query($sql, __FILE__, __LINE__);
			if (mysql_num_rows($res) == 0)
			{
				$course['error'] = get_lang('UnkownCategory').' ('.$course['CourseCategory'].')';
				$errors[] = $course;
			}
		}
	}
	return $errors;
}

/**
 * Save the imported data
 * @param   array   List of courses info
 */
function save_data($courses)
{
	global $_configuration, $firstExpirationDelay;
	$msg = '';
	$enabled_languages = api_get_languages();
	$enabled_languages = $enabled_languages["folder"];
	foreach($courses as $index => $course)
	{
		$course_language = $course['Language'];
		if (empty($course_language) || !in_array($course_language, $enabled_languages))
		{
			$course_language = api_get_setting('platformLanguage');
		}
		$keys = define_course_keys($course['Code'], "", $_configuration['db_prefix']);
		$user_table = Database::get_main_table(TABLE_MAIN_USER);
		$sql = "SELECT user_id, ".(api_is_western_name_order(null, $course_language) ? "CONCAT(firstname,' ',lastname)" : "CONCAT(lastname,' ',firstname)")." AS name FROM $user_table WHERE username = '".Database::escape_string($course['Teacher'])."'";
		$res = api_sql_query($sql,__FILE__,__LINE__);
		$teacher = mysql_fetch_object($res);
		$visual_code = $keys["currentCourseCode"];
		$code = $keys["currentCourseId"];
		$db_name = $keys["currentCourseDbName"];
		$directory = $keys["currentCourseRepository"];
		$expiration_date = time() + $firstExpirationDelay;
		prepare_course_repository($directory, $code);
		update_Db_course($db_name);
		fill_course_repository($directory);
		fill_Db_course($db_name, $directory, $course_language, array());
		register_course($code, $visual_code, $directory, $db_name, $teacher->name, $course['CourseCategory'], $course['Title'], $course_language, $teacher->user_id, $expiration_date);
		$msg .= '<a href="'.api_get_path(WEB_COURSE_PATH).$directory.'/">'.$code.'</a> '.get_lang('Created').'<br />';
	}
    if (!empty($msg)) {
        Display::display_normal_message($msg,false);    	
    }
}
/**
 * Read the CSV-file
 * @param string $file Path to the CSV-file
 * @return array All course-information read from the file
 */
function parse_csv_data($file)
{
	$courses = Import :: csv_to_array($file);
	return $courses;
}

$language_file = array ('admin', 'registration','create_course', 'document');

$cidReset = true;

include ('../inc/global.inc.php');
api_protect_admin_script();
require_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
require_once (api_get_path(LIBRARY_PATH).'import.lib.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once (api_get_path(CONFIGURATION_PATH).'add_course.conf.php');
require_once (api_get_path(LIBRARY_PATH).'add_course.lib.inc.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$formSent = 0;
$errorMsg = '';
$defined_auth_sources[] = PLATFORM_AUTH_SOURCE;
if (is_array($extAuthSource))
{
	$defined_auth_sources = array_merge($defined_auth_sources, array_keys($extAuthSource));
}

$tool_name = get_lang('ImportCourses').' CSV';

$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));

set_time_limit(0);
Display :: display_header($tool_name);

if ($_POST['formSent'])
{
	if(empty($_FILES['import_file']['tmp_name']))
	{
		$error_message = get_lang('UplUploadFailed');
		Display :: display_error_message($error_message, false);
	}
	else
	{
		$file_type = $_POST['file_type'];
		$courses = parse_csv_data($_FILES['import_file']['tmp_name']);
		$errors = validate_data($courses);
		if (count($errors) == 0)
		{
			//$users = complete_missing_data($courses);
			save_data($courses);
			//header('Location: user_list.php?action=show_message&message='.urlencode(get_lang('FileImported')));
			//exit ();
		}
	}
}

if (count($errors) != 0)
{
	$error_message = '<ul>';
	foreach ($errors as $index => $error_course)
	{
		$error_message .= '<li>'.get_lang('Line').' '.$error_course['line'].': <b>'.$error_course['error'].'</b>: ';
		$error_message .= $error_course['Code'].' '.$error_course['Title'];
		$error_message .= '</li>';
	}
	$error_message .= '</ul>';
	Display :: display_error_message($error_message, false);
}
?>
<form method="post" action="<?php echo api_get_self(); ?>" enctype="multipart/form-data" style="margin:0px;">
<div class="row"><div class="form_header"><?php echo $tool_name; ?></div></div>
<div class="row">
	<div class="label"><?php echo get_lang('ImportCSVFileLocation');?></div>
	<div class="formw">
		<input type="file" name="import_file"/>
	</div>
</div>
<div class="row">
	<div class="label"></div>
	<div class="formw">
		<button type="submit" class="save" value="<?php echo get_lang('Import'); ?>"><?php echo get_lang('Import'); ?></button>
	</div>
</div>


<input type="hidden" name="formSent" value="1"/>

</form>

<div style="clear: both;"></div>

<p><?php echo get_lang('CSVMustLookLike').' ('.get_lang('MandatoryFields').')'; ?> :</p>

<blockquote>
<pre>
<b>Code</b>;<b>Title</b>;<b>CourseCategory</b>;<b>Teacher</b>;Language
BIO0015;Biology;BIO;username;english
</pre>
</blockquote>

<?php


/*
==============================================================================
		FOOTER
==============================================================================
*/
Display :: display_footer();
?>
