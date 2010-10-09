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

// Filltering passed to this page parameters.
$delete_course_request = intval($_GET['delete_course_request']);
$message = trim(Security::remove_XSS(stripslashes(urldecode($_GET['message']))));
$is_error_message = !empty($_GET['is_error_message']);

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
 * Get the number of courses which will be displayed.
 */
function get_number_of_courses() {
    return CourseRequestManager::count_course_requests(COURSE_REQUEST_ACCEPTED);
}

/**
 * Get course data to display
 */
function get_course_data($from, $number_of_items, $column, $direction) {
    $course_table = Database :: get_main_table(TABLE_MAIN_COURSE_REQUEST);
    $users_table = Database :: get_main_table(TABLE_MAIN_USER);
    $course_users_table = Database :: get_main_table(TABLE_MAIN_COURSE_USER);

    $sql = "SELECT code AS col0,

                   title AS col1,
                   category_code AS col2,
                   tutor_name AS col3,
                   request_date AS col4,
                   id AS col5
                   FROM $course_table WHERE status = ".COURSE_REQUEST_ACCEPTED;

    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";
    $res = Database :: query($sql);
    $courses = array();

    while ($course = Database :: fetch_row($res)) {
        $courses[] = $course;
    }

    return $courses;
}

/**
 * Actions in the list: edit, accept, delete.
 */
function modify_filter($id) {
    $code = CourseRequestManager::get_course_request_code($id);
    $result = '<a href="editar_curso.php?id='.$id.'">'.Display::return_icon('edit.gif', get_lang('Edit'), array('style' => 'vertical-align: middle;')).'</a>'.
        '&nbsp;<a href="?delete_course_request='.$id.'">'.Display::return_icon('delete.gif', get_lang('DeleteThisCourseRequest'), array('style' => 'vertical-align: middle;', 'onclick' => 'javascript: if (!confirm(\''.addslashes(api_htmlentities(sprintf(get_lang('ACourseRequestWillBeDeleted'), $code), ENT_QUOTES)).'\')) return false;')).'</a>';
    return $result;
}

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$tool_name = get_lang('AcceptedCourseRequests');
Display :: display_header($tool_name);

// Display confirmation or error message.
if (!empty($message)) {
    if ($is_error_message) {
        Display::display_error_message($message);
    } else {
        Display::display_normal_message($message);
    }
}

// The action bar.
echo '<div class="actions">';
echo '<a href="course_list.php">'.Display::return_icon('courses.gif', get_lang('CourseList')).get_lang('CourseList').'</a>';
echo '<a href="course_request_review.php">'.Display::return_icon('course_request_pending.png', get_lang('ReviewCourseRequests')).get_lang('ReviewCourseRequests').'</a>';
echo '<a href="course_request_rejected.php">'.Display::return_icon('course_request_rejected.gif', get_lang('RejectedCourseRequests')).get_lang('RejectedCourseRequests').'</a>';
echo '</div>';

// Create a sortable table with the course data
$table = new SortableTable('courses', 'get_number_of_courses', 'get_course_data', 2);
$table->set_additional_parameters($parameters);
//$table->set_header(0, '', false);
$table->set_header(0, get_lang('Code'));
$table->set_header(1, get_lang('Title'));
$table->set_header(2, get_lang('Category'));
//$table->set_header(3, get_lang('Teacher'), false);
//$table->set_header(4, get_lang('CourseRequestDate'), false);
$table->set_header(3, get_lang('Teacher'));
$table->set_header(4, get_lang('CourseRequestDate'));
$table->set_header(5, '', false);
$table->set_column_filter(5, 'modify_filter');
//$table->set_form_actions(array('delete_courses' => get_lang('DeleteCourse')), 'course');
$table->display();

/* FOOTER */

Display :: display_footer();
