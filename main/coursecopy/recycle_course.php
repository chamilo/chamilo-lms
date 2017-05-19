<?php
/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;
use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRecycler;

/**
 * Delete resources from a course.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
 * @package chamilo.backup
 */

require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_COURSE_MAINTENANCE;
api_protect_course_script(true);

// Check access rights (only teachers are allowed here)
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

// Section for the tabs
$this_section = SECTION_COURSES;

// Breadcrumbs
$interbreadcrumb[] = array(
    'url' => api_get_path(WEB_CODE_PATH).'course_info/maintenance.php',
    'name' => get_lang('Maintenance')
);

// Displaying the header
$nameTools = get_lang('RecycleCourse');
Display::display_header($nameTools);

// Display the tool title
echo Display::page_header($nameTools);

if (Security::check_token('post') && (
        isset($_POST['action']) &&
        $_POST['action'] == 'course_select_form' ||
        (
            isset($_POST['recycle_option']) &&
            $_POST['recycle_option'] == 'full_backup'
        )
    )
) {
    // Clear token
    Security::clear_token();

    if (isset($_POST['action']) && $_POST['action'] == 'course_select_form') {
        $course = CourseSelectForm::get_posted_course();
    } else {
        $cb = new CourseBuilder();
        $course = $cb->build();
    }
    $recycle_type = '';
    if (isset($_POST['recycle_option']) && $_POST['recycle_option'] == 'full_backup') {
        $recycle_type = 'full_backup';
    } else if (isset($_POST['action']) && $_POST['action'] == 'course_select_form') {
        $recycle_type = 'select_items';
    }
    $cr = new CourseRecycler($course);
    $cr->recycle($recycle_type);
    echo Display::return_message(get_lang('RecycleFinished'), 'confirm');
} elseif (Security::check_token('post') && (
        isset($_POST['recycle_option']) &&
        $_POST['recycle_option'] == 'select_items'
    )
) {
    // Clear token
    Security::clear_token();

    $cb = new CourseBuilder();
    $course = $cb->build();
    // Add token to Course select form
    $hiddenFields['sec_token'] = Security::get_token();
    CourseSelectForm::display_form($course, $hiddenFields);
} else {
    $cb = new CourseBuilder();
    $course = $cb->build();
    if (!$course->has_resources()) {
        echo get_lang('NoResourcesToRecycle');
    } else {
        echo Display::return_message(get_lang('RecycleWarning'), 'warning', false);
        $form = new FormValidator('recycle_course', 'post', api_get_self().'?'.api_get_cidreq());
        $form->addElement('header', get_lang('SelectOptionForBackup'));
        $form->addElement('radio', 'recycle_option', null, get_lang('FullRecycle'), 'full_backup');
        $form->addElement('radio', 'recycle_option', null, get_lang('LetMeSelectItems'), 'select_items');
        $form->addButtonSave(get_lang('RecycleCourse'));
        $form->setDefaults(array('recycle_option' => 'select_items'));
        // Add Security token
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(array('sec_token' => $token));
        $form->display();
    }
}

// Display the footer
Display::display_footer();
