<?php
/* For licensing terms, see /license.txt */

/**
 * A list containig the accepted course requests
 * @package chamilo.admin
 * @author José Manuel Abuin Mosquera <chema@cesga.es>, 2010
 * @author Bruno Rubio Gayo <brubio@cesga.es>, 2010
 * Centro de Supercomputacion de Galicia (CESGA)
 *
 * @author Ivan Tcholakov <ivantcholakov@gmail.com> (technical adaptation for Chamilo 1.8.8), 2010
 */

/* INIT SECTION */

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
require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'sortabletable.class.php';

// Including a configuration file.
require api_get_path(CONFIGURATION_PATH).'add_course.conf.php';

// Include additional libraries
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';

// A check whether the course validation feature is enabled.
$course_validation_feature = api_get_setting('course_validation') == 'true';

// Filltering passed to this page parameters.
$delete_course_request = intval($_GET['delete_course_request']);
$message = trim(Security::remove_XSS(stripslashes(urldecode($_GET['message']))));
$is_error_message = !empty($_GET['is_error_message']);
$keyword = Database::escape_string(trim($_GET['keyword']));

if ($course_validation_feature) {

    /**
     * Deletion of a course request.
     */
    if (!empty($delete_course_request)) {
        $course_request_code = CourseRequestManager::get_course_request_code($delete_course_request);
        $result = CourseRequestManager::delete_course_request($delete_course_request);
        if ($result) {
            $message = sprintf(get_lang('CourseRequestDeleted'), $course_request_code);
            $is_error_message = false;
        } else {
            $message = sprintf(get_lang('CourseRequestDeletionFailed'), $course_request_code);
            $is_error_message = true;
        }
    }

    /**
     * Form actions: delete.
     */
    elseif (isset($_POST['action'])) {
        switch ($_POST['action']) {
            // Delete selected courses
            case 'delete_course_requests' :
                $course_requests = $_POST['course_request'];
                if (is_array($_POST['course_request']) && !empty($_POST['course_request'])) {
                    $success = true;
                    foreach ($_POST['course_request'] as $index => $course_request_id) {
                        $success &= CourseRequestManager::delete_course_request($course_request_id);
                    }
                    $message = $success ? get_lang('SelectedCourseRequestsDeleted') : get_lang('SomeCourseRequestsNotDeleted');
                    $is_error_message = !$success;
                }
                break;
        }
    }

} else {

   $link_to_setting = api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Platform#course_validation';
   $message = sprintf(get_lang('PleaseActivateCourseValidationFeature'), sprintf('<strong><a href="%s">%s</a></strong>', $link_to_setting, get_lang('EnableCourseValidation')));
   $is_error_message = true;

}

/**
 * Get the number of courses which will be displayed.
 */
function get_number_of_requests() {
    return CourseRequestManager::count_course_requests(COURSE_REQUEST_ACCEPTED);
}

/**
 * Get course data to display
 */
function get_request_data($from, $number_of_items, $column, $direction) {
    global $keyword;

    $course_request_table = Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST);
    $users_table = Database :: get_main_table(TABLE_MAIN_USER);
    $course_users_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

    $sql = "SELECT id AS col0,
                   code AS col1,
                   title AS col2,
                   category_code AS col3,
                   tutor_name AS col4,
                   request_date AS col5,
                   id  AS col6
                   FROM $course_request_table WHERE status = ".COURSE_REQUEST_ACCEPTED;

    if ($keyword != '') {
        $sql .= " AND (title LIKE '%".$keyword."%' OR code LIKE '%".$keyword."%' OR visual_code LIKE '%".$keyword."%')";
    }
    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";
    $res = Database :: query($sql);

    $course_requests = array();
    while ($course_request = Database :: fetch_row($res)) {
        $course_request[5] = api_get_local_time($course_request[5]);
        $course_requests[] = $course_request;
    }

    return $course_requests;
}

/**
 * Actions in the list: edit, accept, delete.
 */
function modify_filter($id) {
    $code = CourseRequestManager::get_course_request_code($id);
    $result = '<a href="course_request_edit.php?id='.$id.'&caller=1">'.Display::return_icon('edit.gif', get_lang('Edit'), array('style' => 'vertical-align: middle;')).'</a>'.
        '&nbsp;<a href="?delete_course_request='.$id.'">'.Display::return_icon('delete.gif', get_lang('DeleteThisCourseRequest'), array('style' => 'vertical-align: middle;', 'onclick' => 'javascript: if (!confirm(\''.addslashes(api_htmlentities(sprintf(get_lang('ACourseRequestWillBeDeleted'), $code), ENT_QUOTES)).'\')) return false;')).'</a>';
    return $result;
}

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$tool_name = get_lang('AcceptedCourseRequests');
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
    Display :: display_footer();
    exit;
}

// Create a simple search-box.
$form = new FormValidator('search_simple', 'get', '', '', 'width=200px', false);
$renderer = $form->defaultRenderer();
$renderer->setElementTemplate('<span>{element}</span> ');
$form->addElement('text', 'keyword', get_lang('keyword'));
$form->addElement('style_submit_button', 'submit', get_lang('Search'), 'class="search"');

// The action bar.
echo '<div style="float: right; margin-top: 5px; margin-right: 5px;">';
echo '<a href="course_list.php">'.Display::return_icon('courses.gif', get_lang('CourseList')).get_lang('CourseList').'</a>';
echo ' <a href="course_request_review.php">'.Display::return_icon('course_request_pending.png', get_lang('ReviewCourseRequests')).get_lang('ReviewCourseRequests').'</a>';
echo ' <a href="course_request_rejected.php">'.Display::return_icon('course_request_rejected.gif', get_lang('RejectedCourseRequests')).get_lang('RejectedCourseRequests').'</a>';
echo '</div>';
echo '<div class="actions">';
$form->display();
echo '</div>';

// Create a sortable table with the course data.
$table = new SortableTable('course_requests_accepted', 'get_number_of_requests', 'get_request_data', 5, 20, 'DESC');
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false);
$table->set_header(1, get_lang('Code'));
$table->set_header(2, get_lang('Title'));
$table->set_header(3, get_lang('Category'));
$table->set_header(4, get_lang('Teacher'));
$table->set_header(5, get_lang('CourseRequestDate'));
$table->set_header(6, '', false);
$table->set_column_filter(6, 'modify_filter');
$table->set_form_actions(array('delete_course_requests' => get_lang('DeleteCourseRequests')), 'course_request');
$table->display();

/* FOOTER */

Display :: display_footer();
