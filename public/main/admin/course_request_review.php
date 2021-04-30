<?php

/* For licensing terms, see /license.txt */

/**
 * A list containing the pending course requests.
 *
 * @author José Manuel Abuin Mosquera <chema@cesga.es>, 2010
 * Centro de Supercomputacion de Galicia (CESGA)
 * @author Ivan Tcholakov <ivantcholakov@gmail.com> (technical adaptation for Chamilo 1.8.8), 2010
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

// The delete action should be deactivated in this page.
// Better reject the target request, after that you can delete it.
// see DELETE_ACTION_ENABLED constant in main_api.lib.php

// A check whether the course validation feature is enabled.
$course_validation_feature = 'true' == api_get_setting('course_validation');

// Filltering passed to this page parameters.
$accept_course_request = isset($_GET['accept_course_request']) ? (int) ($_GET['accept_course_request']) : '';
$reject_course_request = isset($_GET['reject_course_request']) ? (int) ($_GET['reject_course_request']) : '';
$request_info = isset($_GET['request_info']) ? (int) ($_GET['request_info']) : '';
$delete_course_request = isset($_GET['delete_course_request']) ? (int) ($_GET['delete_course_request']) : '';
$message = isset($_GET['message']) ? trim(Security::remove_XSS(stripslashes(urldecode($_GET['message'])))) : '';
$is_error_message = isset($_GET['is_error_message']) ? !empty($_GET['is_error_message']) : '';
$keyword = isset($_GET['keyword']) ? Database::escape_string(trim($_GET['keyword'])) : '';

if ($course_validation_feature) {
    /*
     * Course acceptance and creation.
     */
    if (!empty($accept_course_request)) {
        $course_request_code = CourseRequestManager::get_course_request_code($accept_course_request);
        $course_id = CourseRequestManager::accept_course_request($accept_course_request);
        if ($course_id) {
            $course_code = CourseManager::get_course_code_from_course_id($course_id);
            $message = sprintf(get_lang('The course request %s has been accepted. A new course %s has been created.'), $course_request_code, $course_code);
            $is_error_message = false;
        } else {
            $message = sprintf(get_lang('The course request %s has not been accepted due to internal error.'), $course_request_code);
            $is_error_message = true;
        }
    } elseif (!empty($reject_course_request)) {
        /**
         * Course rejection.
         */
        $course_request_code = CourseRequestManager::get_course_request_code($reject_course_request);
        $result = CourseRequestManager::reject_course_request($reject_course_request);
        if ($result) {
            $message = sprintf(get_lang('The course request %s has been rejected.'), $course_request_code);
            $is_error_message = false;
        } else {
            $message = sprintf(get_lang('The course request %s has not been rejected due to internal error.'), $course_request_code);
            $is_error_message = true;
        }
    } elseif (!empty($request_info)) {
        /**
         * Sending to the teacher a request for additional information about the proposed course.
         */
        $course_request_code = CourseRequestManager::get_course_request_code($request_info);
        $result = CourseRequestManager::ask_for_additional_info($request_info);
        if ($result) {
            $message = sprintf(get_lang('Additional information about the course request %s has been asked.'), $course_request_code);
            $is_error_message = false;
        } else {
            $message = sprintf(get_lang('Additional information about the course request %s has not been asked due to internal error.'), $course_request_code);
            $is_error_message = true;
        }
    } elseif (!empty($delete_course_request)) {
        /**
         * Deletion of a course request.
         */
        $course_request_code = CourseRequestManager::get_course_request_code($delete_course_request);
        $result = CourseRequestManager::delete_course_request($delete_course_request);
        if ($result) {
            $message = sprintf(get_lang('The course request %s has been deleted.'), $course_request_code);
            $is_error_message = false;
        } else {
            $message = sprintf(get_lang('The course request %s has not been deleted due to internal error.'), $course_request_code);
            $is_error_message = true;
        }
    } elseif (DELETE_ACTION_ENABLED && isset($_POST['action'])) {
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
    return CourseRequestManager::count_course_requests(COURSE_REQUEST_PENDING);
}

/**
 * Get course data to display.
 */
function get_request_data($from, $number_of_items, $column, $direction)
{
    global $keyword;
    $course_request_table = Database::get_main_table(TABLE_MAIN_COURSE_REQUEST);

    $from = (int) $from;
    $number_of_items = (int) $number_of_items;
    $column = (int) $column;
    $direction = !in_array(strtolower(trim($direction)), ['asc', 'desc']) ? 'asc' : $direction;
    if (DELETE_ACTION_ENABLED) {
        $sql = "SELECT id AS col0,
                   code AS col1,
                   title AS col2,
                   category_code AS col3,
                   tutor_name AS col4,
                   request_date AS col5,
                   id  AS col6
               FROM $course_request_table
               WHERE status = ".COURSE_REQUEST_PENDING;
    } else {
        $sql = "SELECT
                   code AS col0,
                   title AS col1,
                   category_code AS col2,
                   tutor_name AS col3,
                   request_date AS col4,
                   id  AS col5
               FROM $course_request_table
               WHERE status = ".COURSE_REQUEST_PENDING;
    }

    if ('' != $keyword) {
        $sql .= " AND (title LIKE '%".$keyword."%' OR code LIKE '%".$keyword."%' OR visual_code LIKE '%".$keyword."%')";
    }
    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";
    $res = Database::query($sql);

    $course_requests = [];
    while ($course_request = Database::fetch_row($res)) {
        if (DELETE_ACTION_ENABLED) {
            $course_request[5] = api_get_local_time($course_request[5]);
        } else {
            $course_request[4] = api_get_local_time($course_request[4]);
        }
        $course_requests[] = $course_request;
    }

    return $course_requests;
}

/**
 * Enlace a la ficha del profesor.
 */
function email_filter($teacher)
{
    $teacher = Database::escape_string($teacher);
    $sql = 'SELECT user_id FROM '.Database::get_main_table(TABLE_MAIN_COURSE_REQUEST)." 
            WHERE tutor_name LIKE '".$teacher."'";
    $res = Database::query($sql);
    $info = Database::fetch_array($res);

    return '<a href="./user_information.php?user_id='.$info[0].'">'.$teacher.'</a>';
}

/**
 * Actions in the list: edit, accept, reject, request additional information.
 */
function modify_filter($id)
{
    $code = CourseRequestManager::get_course_request_code($id);
    $result = '<a href="course_request_edit.php?id='.$id.'&caller=0">'.
        Display::return_icon('edit.png', get_lang('Edit'), ['style' => 'vertical-align: middle;']).'</a>'.
        '&nbsp;<a href="?accept_course_request='.$id.'">'.
        Display::return_icon('accept.png', get_lang('Accept this course'), ['style' => 'vertical-align: middle;', 'onclick' => 'javascript: if (!confirm(\''.addslashes(api_htmlentities(sprintf(get_lang('A new course %s is going to be created. Is it OK to proceed?'), $code), ENT_QUOTES)).'\')) return false;'], 16).'</a>'.
        '&nbsp;<a href="?reject_course_request='.$id.'">'.
        Display::return_icon('error.png', get_lang('Reject this course request'), ['style' => 'vertical-align: middle;', 'onclick' => 'javascript: if (!confirm(\''.addslashes(api_htmlentities(sprintf(get_lang('The course request %s is going to be rejected. Is it OK to proceed?'), $code), ENT_QUOTES)).'\')) return false;'], 16).'</a>';
    if (!CourseRequestManager::additional_info_asked($id)) {
        $result .= '&nbsp;<a href="?request_info='.$id.'">'.
            Display::return_icon('request_info.gif', get_lang('Ask for additional information'), ['style' => 'vertical-align: middle;', 'onclick' => 'javascript: if (!confirm(\''.addslashes(api_htmlentities(sprintf(get_lang('Additional information about %s course request is going to be asked through an e-mail message. Is it OK to proceed?'), $code), ENT_QUOTES)).'\')) return false;']).'</a>';
    }
    if (DELETE_ACTION_ENABLED) {
        $message = addslashes(api_htmlentities(sprintf(get_lang('The course request %s is going to be deleted. Is it OK to proceed?'), $code), ENT_QUOTES));
        $result .= '&nbsp;<a href="?delete_course_request='.$id.'">';
        $result .= Display::return_icon(
            'delete.png',
            get_lang('Delete this course request'),
            [
                'style' => 'vertical-align: middle;',
                'onclick' => 'javascript: if (!confirm(\''.$message.'\')) return false;',
            ]
        );
        $result .= '</a>';
    }

    return $result;
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'course_list.php', 'name' => get_lang('Course list')];

$tool_name = get_lang('Review incoming course requests');

// Display confirmation or error message.
if (!empty($message)) {
    if ($is_error_message) {
        Display::addFlash(Display::return_message($message, 'error', false));
    } else {
        Display::addFlash(Display::return_message($message, 'normal', false));
    }
}

Display::display_header($tool_name);

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
echo ' <a href="course_request_accepted.php">';
echo Display::return_icon('course_request_accepted.gif', get_lang('Accepted course requests')).
    get_lang('Accepted course requests');
echo '</a>';
echo ' <a href="course_request_rejected.php">';
echo Display::return_icon('course_request_rejected.gif', get_lang('Rejected course requests')).
    get_lang('Rejected course requests');
echo '</a>';
echo '</div>';
echo '<div class="actions">';
$form->display();
echo '</div>';

// Create a sortable table with the course data.
$offet = DELETE_ACTION_ENABLED ? 1 : 0;
$table = new SortableTable(
    'course_requests_review',
    'get_number_of_requests',
    'get_request_data',
    4 + $offet,
    20,
    'DESC'
);
//$table->set_additional_parameters($parameters);
if (DELETE_ACTION_ENABLED) {
    $table->set_header(0, '', false);
}
$table->set_header(0 + $offet, get_lang('Course code'));
$table->set_header(1 + $offet, get_lang('Title'));
$table->set_header(2 + $offet, get_lang('Category'));
$table->set_header(3 + $offet, get_lang('Trainer'));
$table->set_header(4 + $offet, get_lang('Request date'));
$table->set_header(5 + $offet, '', false);
$table->set_column_filter(3 + $offet, 'email_filter');
$table->set_column_filter(5 + $offet, 'modify_filter');
if (DELETE_ACTION_ENABLED) {
    $table->set_form_actions(['delete_course_requests' => get_lang('Delete selected course request(s)')], 'course_request');
}
$table->display();

Display::display_footer();
