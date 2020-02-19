<?php

/* For licensing terms, see /license.txt */

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;
use Chamilo\CourseBundle\Component\CourseCopy\CourseRecycler;
use Chamilo\CourseBundle\Component\CourseCopy\CourseSelectForm;

/**
 * Delete resources from a course.
 *
 * @author Bart Mollet <bart.mollet@hogent.be>
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
$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH).'course_info/maintenance.php',
    'name' => get_lang('Backup'),
];

// Displaying the header
$nameTools = get_lang('Empty this course');
Display::display_header($nameTools);

// Display the tool title
echo Display::page_header($nameTools);
$action = isset($_POST['action']) ? $_POST['action'] : '';

if (Security::check_token('post') && (
        'course_select_form' === $action ||
        (
            isset($_POST['recycle_option']) &&
            'full_backup' == $_POST['recycle_option']
        )
    )
) {
    // Clear token
    Security::clear_token();
    if (isset($_POST['action']) && 'course_select_form' === $_POST['action']) {
        $course = CourseSelectForm::get_posted_course();
    } else {
        $cb = new CourseBuilder();
        $course = $cb->build();
    }
    $recycle_type = '';
    if (isset($_POST['recycle_option']) && 'full_backup' === $_POST['recycle_option']) {
        $recycle_type = 'full_backup';
    } elseif (isset($_POST['action']) && 'course_select_form' === $_POST['action']) {
        $recycle_type = 'select_items';
    }
    $cr = new CourseRecycler($course);
    $cr->recycle($recycle_type);
    echo Display::return_message(get_lang('Recycle is finished'), 'confirm');
} elseif (Security::check_token('post') && (
        isset($_POST['recycle_option']) &&
        'select_items' === $_POST['recycle_option']
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
        echo get_lang('No resource to recycle');
    } else {
        echo Display::return_message(get_lang('Warning: using this tool, you will delete learning objects in your course. There is no UNDO possible. We advise you to create a <a href="create_backup.php">backup</a> before.'), 'warning', false);
        $form = new FormValidator('recycle_course', 'post', api_get_self().'?'.api_get_cidreq());
        $form->addElement('header', get_lang('Please select a backup option'));
        $form->addElement('radio', 'recycle_option', null, get_lang('Delete everything'), 'full_backup');
        $form->addElement('radio', 'recycle_option', null, get_lang('Let me select learning objects'), 'select_items');
        $form->addButtonSave(get_lang('Empty this course'));
        $form->setDefaults(['recycle_option' => 'select_items']);
        // Add Security token
        $token = Security::get_token();
        $form->addElement('hidden', 'sec_token');
        $form->setConstants(['sec_token' => $token]);
        $form->display();
    }
}

// Display the footer
Display::display_footer();
