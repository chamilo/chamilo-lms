<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.backup
 */
/**
 * Code
 */
// Language files that need to be included
$language_file = array('exercice', 'coursebackup', 'admin');

// Setting the global file that gets the general configuration, the databases, the languages, ...
require_once '../inc/global.inc.php';

// Including additional libraries
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once 'classes/CourseBuilder.class.php';
require_once 'classes/CourseRestorer.class.php';
require_once 'classes/CourseSelectForm.class.php';

// Notice for unauthorized people.
if (!api_is_allowed_to_edit()) {
	api_not_allowed(true);
}

// Remove memory and time limits as much as possible as this might be a long process...
if (function_exists('ini_set')) {
	ini_set('memory_limit', '256M');
	ini_set('max_execution_time', 1800);
}

// Breadcrumbs
$interbreadcrumb[] = array ('url' => '../course_info/maintenance.php', 'name' => get_lang('Maintenance'));

// The section (for the tabs)
$this_section = SECTION_COURSES;

// Display the header
Display::display_header(get_lang('CopyCourse'));
//api_display_tool_title($nameTools);

/*	MAIN CODE */

// If a CourseSelectForm is posted or we should copy all resources, then copy them
if ((isset($_POST['action']) && $_POST['action'] == 'course_select_form') || (isset($_POST['copy_option']) && $_POST['copy_option'] == 'full_copy')) {
	if (isset($_POST['action']) && $_POST['action'] == 'course_select_form') {
		$course = CourseSelectForm :: get_posted_course('copy_course');
	} else {
		$cb = new CourseBuilder();
		$course = $cb->build();
	}	
	$cr = new CourseRestorer($course);
	$cr->set_file_option($_POST['same_file_name_option']);
	$cr->restore($_POST['destination_course']);
	Display::display_normal_message(get_lang('CopyFinished').': <a href="'.api_get_course_url($_POST['destination_course']).'">'.$_POST['destination_course'].'</a>',false);
} elseif (isset ($_POST['copy_option']) && $_POST['copy_option'] == 'select_items') {
	$cb = new CourseBuilder();
	$course = $cb->build();	

	$hidden_fields['same_file_name_option'] = $_POST['same_file_name_option'];
	$hidden_fields['destination_course'] = $_POST['destination_course'];
	CourseSelectForm :: display_form($course, $hidden_fields, true);
} else {
	$table_c = Database :: get_main_table(TABLE_MAIN_COURSE);
	$table_cu = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$user_info = api_get_user_info();
	$course_info = api_get_course_info();
	$sql = 'SELECT * FROM '.$table_c.' c, '.$table_cu.' cu WHERE cu.course_code = c.code';
	if (!api_is_platform_admin()) {
		$sql .= ' AND cu.status=1 ';
	}
	$sql .= ' AND target_course_code IS NULL AND cu.user_id = '.$user_info['user_id'].' AND c.code != '."'".$course_info['sysCode']."'".' ORDER BY title ASC';
	$res = Database::query($sql);
	if (Database::num_rows($res) == 0) {
		Display::display_normal_message(get_lang('NoDestinationCoursesAvailable'));
	} else {
?>
	<form method="post" action="copy_course.php">
<?php
	echo get_lang('SelectDestinationCourse');
	echo ' <select name="destination_course"/>';
	while ($obj = Database::fetch_object($res)) {
		echo '<option value="'.$obj->code.'">'.$obj->title.'</option>';
	}
	echo '</select>';
?>
	<br/>
	<br/>
	<input type="radio" class="checkbox" id="copy_option_1" name="copy_option" value="full_copy"/>
	<label for="copy_option_1"><?php echo get_lang('FullCopy'); ?></label>
	<br/>
	<input type="radio" class="checkbox" id="copy_option_2" name="copy_option" value="select_items" checked="checked"/>
	<label for="copy_option_2"><?php echo get_lang('LetMeSelectItems'); ?></label>
	<br/>
	<br/>
	<?php echo get_lang('SameFilename'); ?>
	<blockquote>
	<input type="radio" class="checkbox"  id="same_file_name_option_1" name="same_file_name_option" value="<?php echo FILE_SKIP; ?>"/>
	<label for="same_file_name_option_1"><?php echo  get_lang('SameFilenameSkip'); ?></label>
	<br/>
	<input type="radio" class="checkbox" id="same_file_name_option_2" name="same_file_name_option" value="<?php echo FILE_RENAME; ?>"/>
	<label for="same_file_name_option_2"><?php echo get_lang('SameFilenameRename'); ?></label>
	<br/>
	<input type="radio" class="checkbox"  id="same_file_name_option_3" name="same_file_name_option"  value="<?php echo FILE_OVERWRITE; ?>" checked="checked"/>
	<label for="same_file_name_option_3"><?php echo get_lang('SameFilenameOverwrite'); ?></label>
	</blockquote>
	<br/>
	<button class="save" type="submit"><?php echo get_lang('CopyCourse'); ?></button>
	</form>
<?php
	}
}

/*	FOOTER	*/
Display::display_footer();
