<?php
/* For licensing terms, see /license.txt */

/**
 * A page for detailed preview or edition of a given course request.
 * @package chamilo.admin
 * @author Ivan Tcholakov <ivantcholakov@gmail.com>, 2010
 */

// Initialization section.

// Language files that need to be included.
$language_file = array('admin', 'create_course');

$cidReset = true;
require '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

require_once api_get_path(LIBRARY_PATH).'add_course.lib.inc.php';
require_once api_get_path(CONFIGURATION_PATH).'course_info.conf.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'course_request.lib.php';
require_once api_get_path(LIBRARY_PATH).'mail.lib.inc.php';
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

// Including a configuration file.
require_once api_get_path(CONFIGURATION_PATH).'add_course.conf.php';

// Including additional libraries.
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

// A check whether the course validation feature is enabled.
$course_validation_feature = api_get_setting('course_validation') == 'true';

// Filltering passed to this page parameters.
$id = intval($_GET['id']);
$caller = intval($_GET['caller']);

if ($course_validation_feature) {

    // Retrieve request's data from the corresponding database record.
    $course_request_info = CourseRequestManager::get_course_request_info($id);
    if (!is_array($course_request_info)) {
        // Prepare an error message notifying that the course request has not been found or does not exist.
        $message = get_lang('CourseRequestHasNotBeenFound');
        $is_error_message = true;
    }

} else {

    // Prepare an error message notifying that the course validation feature has not been enabled.
    $link_to_setting = api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Platform#course_validation';
    $message = sprintf(get_lang('PleaseActivateCourseValidationFeature'), sprintf('<strong><a href="%s">%s</a></strong>', $link_to_setting, get_lang('EnableCourseValidation')));
    $is_error_message = true;

}

// Functions.

// Converts the given numerical id to the name of the page that opened this editor.
function get_caller_name($caller_id) {
    switch ($caller_id) {
        case 1:
            return 'course_request_accepted.php';
        case 2:
            return 'course_request_rejected.php';
    }
    return 'course_request_review.php';
}

// The header.
$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$tool_name = get_lang('CourseRequestEdit');
Display :: display_header($tool_name);

// Display confirmation or error message.
if (!empty($message)) {
    if ($is_error_message) {
        Display::display_error_message($message, false);
    } else {
        Display::display_normal_message($message, false);
    }
}

if (!$course_validation_feature) {
    // Disabled course validation feature - show nothing after the error message.
    Display :: display_footer();
    exit;
}

// The action bar.
echo '<div class="actions">';
echo '<a href="course_list.php">'.Display::return_icon('courses.gif', get_lang('CourseList')).get_lang('CourseList').'</a>';
echo '<a href="course_request_review.php">'.Display::return_icon('course_request_pending.png', get_lang('ReviewCourseRequests')).get_lang('ReviewCourseRequests').'</a>';
echo '<a href="course_request_accepted.php">'.Display::return_icon('course_request_accepted.gif', get_lang('AcceptedCourseRequests')).get_lang('AcceptedCourseRequests').'</a>';
echo '<a href="course_request_rejected.php">'.Display::return_icon('course_request_rejected.gif', get_lang('RejectedCourseRequests')).get_lang('RejectedCourseRequests').'</a>';
echo '</div>';

if (!is_array($course_request_info)) {
    // Not accessible database record - show the error message and the action bar.
    Display :: display_footer();
    exit;
}

// Build the form.
$form = new FormValidator('add_course');

// Form title.
$form->addElement('header', '', $tool_name);

// Title
$form->addElement('text', 'title', get_lang('CourseName'), array('size' => '60', 'id' => 'title'));
$form->applyFilter('title', 'html_filter');
$form->addElement('static', null, null, get_lang('Ex'));

$categories_select = $form->addElement('select', 'category_code', get_lang('Fac'), array());
$form->applyFilter('category_code', 'html_filter');
CourseManager::select_and_sort_categories($categories_select);
$form->addElement('static', null, null, get_lang('TargetFac'));

// Other form's elements...

//...

// Set the default values based on the corresponding database record.

//...

// Validate the form and perform the ordered actions.

//...

// Display the form.
$form->display();

// The footer.
Display :: display_footer();
