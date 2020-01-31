<?php

/* For licensing terms, see /license.txt */

/**
 * A list containig the accepted course requests.
 *
 * @author José Manuel Abuin Mosquera <chema@cesga.es>, 2010
 * @author Bruno Rubio Gayo <brubio@cesga.es>, 2010
 * Centro de Supercomputacion de Galicia (CESGA)
 * @author Ivan Tcholakov <ivantcholakov@gmail.com> (technical adaptation for Chamilo 1.8.8), 2010
 */
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;
api_protect_admin_script();

// A check whether the course validation feature is enabled.
$course_validation_feature = 'true' == api_get_setting('course_validation');

// Filltering passed to this page parameters.
$delete_course_request = isset($_GET['delete_course_request']) ? (int) ($_GET['delete_course_request']) : '';
$message = isset($_GET['message']) ? trim(Security::remove_XSS(stripslashes(urldecode($_GET['message'])))) : '';
$is_error_message = !empty($_GET['is_error_message']);

if ($course_validation_feature) {
    /*
     * Deletion of a course request.
     */
    if (!empty($delete_course_request)) {
        $course_request_code = CourseRequestManager::get_course_request_code($delete_course_request);
        $result = CourseRequestManager::delete_course_request($delete_course_request);
        if ($result) {
            $message = sprintf(get_lang('The course request %s has been deleted.'), $course_request_code);
            $is_error_message = false;
        } else {
            $message = sprintf(get_lang('The course request %s has not been deleted due to internal error.'), $course_request_code);
            $is_error_message = true;
        }
    } elseif (isset($_POST['action'])) {
        /*
         * Form actions: delete.
         */
        switch ($_POST['action']) {
            // Delete selected courses
            case 'delete_course_requests':
                $course_requests = $_POST['course_request'];
                if (is_array($_POST['course_request']) && !empty($_POST['course_request'])) {
                    $success = true;
                    foreach ($_POST['course_request'] as $index => $course_request_id) {
                        $success &= CourseRequestManager::delete_course_request($course_request_id);
                    }
                    $message = $success ? get_lang('The selected course requests have been deleted.') : get_lang('Some of the selected course requests have not been deleted due to internal error.');
                    $is_error_message = !$success;
                }

                break;
        }
    }
} else {
    $link_to_setting = api_get_path(WEB_CODE_PATH).'admin/settings.php?category=Platform#course_validation';
    $message = sprintf(
        get_lang('The "Course validation" feature is not enabled at the moment. In order to use this feature, please, enable it by using the  %s setting.'),
        sprintf('<strong><a href="%s">%s</a></strong>', $link_to_setting, get_lang('Courses validation'))
    );
    $is_error_message = true;
}

/**
 * Get the number of courses which will be displayed.
 */
function get_number_of_requests()
{
    return CourseRequestManager::count_course_requests(COURSE_REQUEST_ACCEPTED);
}

/**
 * Get course data to display.
 */
function get_request_data($from, $number_of_items, $column, $direction)
{
    $keyword = isset($_GET['keyword']) ? Database::escape_string(trim($_GET['keyword'])) : null;
    $course_request_table = Database::get_main_table(TABLE_MAIN_COURSE_REQUEST);
    $tblCourseCategory = Database::get_main_table(TABLE_MAIN_CATEGORY);

    $from = (int) $from;
    $number_of_items = (int) $number_of_items;
    $column = (int) $column;
    $direction = !in_array(strtolower(trim($direction)), ['asc', 'desc']) ? 'asc' : $direction;

    $sql = "SELECT
                id AS col0,
               code AS col1,
               title AS col2,
               course_category.code AS col3,
               tutor_name AS col4,
               request_date AS col5,
               id  AS col6
           FROM $course_request_table
           LEFT JOIN $tblCourseCategory on course.category_id = course_category.id
           WHERE status = ".COURSE_REQUEST_ACCEPTED;

    if ('' != $keyword) {
        $sql .= " AND (
                title LIKE '%".$keyword."%' OR
                code LIKE '%".$keyword."%' OR
                visual_code LIKE '%".$keyword."%'
            )";
    }
    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";
    $res = Database :: query($sql);

    $course_requests = [];
    while ($course_request = Database :: fetch_row($res)) {
        $course_request[5] = api_get_local_time($course_request[5]);
        $course_requests[] = $course_request;
    }

    return $course_requests;
}

/**
 * Actions in the list: edit, accept, delete.
 */
function modify_filter($id)
{
    $code = CourseRequestManager::get_course_request_code($id);

    return '<a href="course_request_edit.php?id='.$id.'&caller=1">'.
        Display::return_icon('edit.png', get_lang('Edit'), ['style' => 'vertical-align: middle;']).'</a>'.
        '&nbsp;<a href="?delete_course_request='.$id.'">'.
        Display::return_icon(
            'delete.png',
            get_lang('Delete this course request'),
            [
                'style' => 'vertical-align: middle;',
                'onclick' => 'javascript: if (!confirm(\''.addslashes(api_htmlentities(sprintf(get_lang('The course request %s is going to be deleted. Is it OK to proceed?'), $code), ENT_QUOTES)).'\')) return false;',
            ]
        ).
        '</a>';
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'course_list.php', 'name' => get_lang('Course list')];
$tool_name = get_lang('Accepted course requests');

// Display confirmation or error message.
if (!empty($message)) {
    if ($is_error_message) {
        Display::addFlash(Display::return_message($message, 'error', false));
    } else {
        Display::addFlash(Display::return_message($message, 'normal', false));
    }
}

Display :: display_header($tool_name);

if (!$course_validation_feature) {
    Display :: display_footer();
    exit;
}

// Create a simple search-box.
$form = new FormValidator('search_simple', 'get', '', '', 'width=200px', false);
$renderer = $form->defaultRenderer();
$renderer->setCustomElementTemplate('<span>{element}</span> ');
$form->addElement('text', 'keyword', get_lang('Keyword'));
$form->addButtonSearch(get_lang('Search'));

// The action bar.
echo '<div style="float: right; margin-top: 5px; margin-right: 5px;">';
echo ' <a href="course_request_review.php">'.
    Display::return_icon('course_request_pending.png', get_lang('Review incoming course requests')).get_lang('Review incoming course requests').'</a>';
echo ' <a href="course_request_rejected.php">'.
    Display::return_icon('course_request_rejected.gif', get_lang('Rejected course requests')).get_lang('Rejected course requests').'</a>';
echo '</div>';
echo '<div class="actions">';
$form->display();
echo '</div>';

// Create a sortable table with the course data.
$table = new SortableTable('course_requests_accepted', 'get_number_of_requests', 'get_request_data', 5, 20, 'DESC');
$table->set_header(0, '', false);
$table->set_header(1, get_lang('Course code'));
$table->set_header(2, get_lang('Title'));
$table->set_header(3, get_lang('Category'));
$table->set_header(4, get_lang('Trainer'));
$table->set_header(5, get_lang('Request date'));
$table->set_header(6, '', false);
$table->set_column_filter(6, 'modify_filter');
$table->set_form_actions(['delete_course_requests' => get_lang('Delete selected course request(s)')], 'course_request');
$table->display();

/* FOOTER */

Display :: display_footer();
