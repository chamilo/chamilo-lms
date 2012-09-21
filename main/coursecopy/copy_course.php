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
$current_course_tool  = TOOL_COURSE_MAINTENANCE;
api_protect_course_script(true);


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
    //ini_set('post_max_size', "512M");
}

// Breadcrumbs
$interbreadcrumb[] = array('url' => '../course_info/maintenance.php', 'name' => get_lang('Maintenance'));

// The section (for the tabs)
$this_section = SECTION_COURSES;

// Display the header
Display::display_header(get_lang('CopyCourse'));
echo Display::page_header(get_lang('CopyCourse'));

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

    $hidden_fields = array();
	$hidden_fields['same_file_name_option'] = $_POST['same_file_name_option'];
	$hidden_fields['destination_course']    = $_POST['destination_course'];
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
        $options = array();
        while ($obj = Database::fetch_object($res)) {
            $options[$obj->code] = $obj->title;
        }

        $form = new FormValidator('copy_course', 'post', 'copy_course.php');
        $form->addElement('header','' );
        $form->addElement('select','destination_course', get_lang('SelectDestinationCourse'), $options);

        $group = array();
        $group[] = $form->createElement('radio', 'copy_option', null, get_lang('FullCopy'), 'full_copy');
        $group[] = $form->createElement('radio', 'copy_option', null, get_lang('LetMeSelectItems'), 'select_items');
        $form->addGroup($group, '', get_lang('SelectOptionForBackup'));

        $group = array();
        $group[] = $form->createElement('radio', 'same_file_name_option', null, get_lang('SameFilenameSkip'), FILE_SKIP);
        $group[] = $form->createElement('radio', 'same_file_name_option', null, get_lang('SameFilenameRename'), FILE_RENAME);
        $group[] = $form->createElement('radio', 'same_file_name_option', null, get_lang('SameFilenameOverwrite'), FILE_OVERWRITE);
        $form->addGroup($group, '', get_lang('SameFilename'));

        $form->addElement('style_submit_button', 'submit', get_lang('CopyCourse'),'class="save"');

        $form->setDefaults(array('copy_option' =>'select_items','same_file_name_option' => FILE_OVERWRITE));
        $form->display();

	}
}

/*	FOOTER	*/
Display::display_footer();