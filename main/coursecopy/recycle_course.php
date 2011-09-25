<?php
/* For licensing terms, see /license.txt */
/**
 * Delete resources from a course.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */
/**
 * Code
 */
// Language files that need to be included
$language_file = array ('exercice', 'admin', 'course_info', 'coursebackup');

// Including the global initialization file
require_once '../inc/global.inc.php';

// Check access rights (only teachers are allowed here)
if (!api_is_allowed_to_edit()) {
	api_not_allowed(true);
}

// Section for the tabs
$this_section = SECTION_COURSES;

// Breadcrumbs
$interbreadcrumb[] = array('url' => '../course_info/maintenance.php', 'name' => get_lang('Maintenance'));

// Displaying the header
$nameTools = get_lang('RecycleCourse');
Display::display_header($nameTools);

// Include additional libraries
require_once 'classes/CourseBuilder.class.php';
require_once 'classes/CourseArchiver.class.php';
require_once 'classes/CourseRecycler.class.php';
require_once 'classes/CourseSelectForm.class.php';

// Display the tool title
api_display_tool_title($nameTools);

/*		MAIN CODE	*/

if ((isset($_POST['action']) && $_POST['action'] == 'course_select_form') || (isset($_POST['recycle_option']) && $_POST['recycle_option'] == 'full_backup')) {
	if (isset($_POST['action']) && $_POST['action'] == 'course_select_form') {
		$course = CourseSelectForm::get_posted_course();
	} else {
		$cb = new CourseBuilder();
		$course = $cb->build();
	}
	$cr = new CourseRecycler($course);
	$cr->recycle();
	Display::display_confirmation_message(get_lang('RecycleFinished'));
} elseif (isset($_POST['recycle_option']) && $_POST['recycle_option'] == 'select_items') {
	$cb = new CourseBuilder();
	$course = $cb->build();
	CourseSelectForm::display_form($course);
} else {
	$cb = new CourseBuilder();
	$course = $cb->build();
	if (!$course->has_resources()) {
		echo get_lang('NoResourcesToRecycle');
	} else {
		Display::display_warning_message(get_lang('RecycleWarning'), false);
?>
	<form method="post" action="recycle_course.php">
	<input type="radio" class="checkbox" id="recycle_option_1" name="recycle_option" value="full_backup" checked="checked"/>
	<label for="recycle_option_1"><?php echo get_lang('FullRecycle'); ?></label>
	<br/>
	<input type="radio" class="checkbox" id="recycle_option_2" name="recycle_option" value="select_items"/>
	<label for="recycle_option_2"><?php echo get_lang('LetMeSelectItems'); ?></label>
	<br/>
	<br/>
	<button class="save" type="submit"><?php echo get_lang('RecycleCourse'); ?></button>
	</form>
<?php
	}
}

// Display the footer
Display::display_footer();
