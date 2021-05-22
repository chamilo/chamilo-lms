<?php

/* For licensing terms, see /license.txt*/

use Chamilo\CoreBundle\Entity\ExtraField;
use ExtraField as ExtraFieldModel;

/**
 * This script allows teachers to subscribe existing users
 * to their course.
 */
require_once __DIR__.'/../inc/global.inc.php';
$current_course_tool = TOOL_USER;

// the section (for the tabs)
$this_section = SECTION_COURSES;

// notice for unauthorized people.
api_protect_course_script(true);

if ('false' === api_get_setting('allow_user_course_subscription_by_course_admin')) {
    if (!api_is_platform_admin()) {
        api_not_allowed(true);
    }
}

// Access restriction
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$tool_name = get_lang('Enroll users to course');
$type = isset($_REQUEST['type']) ? (int) $_REQUEST['type'] : STUDENT;
$keyword = isset($_REQUEST['keyword']) ? Security::remove_XSS($_REQUEST['keyword']) : null;

$courseInfo = api_get_course_info();

if (COURSEMANAGER == $type) {
    $tool_name = get_lang('Enroll users to courseAsTeacher');
}

//extra entries in breadcrumb
$interbreadcrumb[] = [
    'url' => 'user.php?'.api_get_cidreq(),
    'name' => get_lang('Users'),
];
if ($keyword) {
    $interbreadcrumb[] = [
        'url' => 'subscribe_user.php?type='.$type.'&'.api_get_cidreq(),
        'name' => $tool_name,
    ];
    $tool_name = get_lang('Search results');
}

$sessionId = api_get_session_id();
$list_Registerister_user = '';
$list_not_Registerister_user = '';

if (isset($_REQUEST['Registerister'])) {
    $userInfo = api_get_user_info($_REQUEST['user_id']);
    if ($userInfo) {
        if (COURSEMANAGER === $type) {
            if (!empty($sessionId)) {
                $message = $userInfo['complete_name_with_username'].' '.get_lang('has been Registeristered to your course');
                SessionManager::set_coach_to_course_session(
                    $_REQUEST['user_id'],
                    $sessionId,
                    $courseInfo['real_id']
                );
                Display::addFlash(Display::return_message($message));
            } else {
                CourseManager::subscribeUser(
                    $_REQUEST['user_id'],
                    $courseInfo['real_id'],
                    COURSEMANAGER
                );
            }
        } else {
            CourseManager::subscribeUser(
                $_REQUEST['user_id'],
                $courseInfo['real_id']
            );
        }
    }
    header('Location:'.api_get_path(WEB_CODE_PATH).'user/user.php?'.api_get_cidreq().'&type='.$type);
    exit;
}

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'subscribe':
            if (is_array($_POST['user'])) {
                $isSuscribe = [];
                foreach ($_POST['user'] as $index => $user_id) {
                    $userInfo = api_get_user_info($user_id);
                    if ($userInfo) {
                        if (COURSEMANAGER === $type) {
                            if (!empty($sessionId)) {
                                $message = $userInfo['complete_name_with_username'].' '.get_lang('has been Registeristered to your course');
                                $result = SessionManager::set_coach_to_course_session(
                                    $user_id,
                                    $sessionId,
                                    $courseInfo['real_id']
                                );
                                if ($result) {
                                    $isSuscribe[] = $message;
                                }
                            } else {
                                CourseManager::subscribeUser($user_id, $courseInfo['real_id'], COURSEMANAGER);
                            }
                        } else {
                            CourseManager::subscribeUser($user_id, $courseInfo['real_id']);
                        }
                    }
                }

                if (!empty($isSuscribe)) {
                    foreach ($isSuscribe as $info) {
                        Display::addFlash(Display::return_message($info));
                    }
                }
            }

            header('Location:'.api_get_path(WEB_CODE_PATH).'user/user.php?'.api_get_cidreq().'&type='.$type);
            exit;
        break;
    }
}

$is_western_name_order = api_is_western_name_order();
$sort_by_first_name = api_sort_by_first_name();

// Build table
$table = new SortableTable(
    'subscribe_users',
    'get_number_of_users',
    'get_user_data',
    ($is_western_name_order xor $sort_by_first_name) ? 3 : 2
);
$parameters['keyword'] = $keyword;
$parameters['type'] = $type;
$table->set_additional_parameters($parameters);
$col = 0;
$table->set_header($col++, '', false);
$table->set_header($col++, get_lang('Code'));
if (api_is_western_name_order()) {
    $table->set_header($col++, get_lang('First name'));
    $table->set_header($col++, get_lang('Last name'));
} else {
    $table->set_header($col++, get_lang('Last name'));
    $table->set_header($col++, get_lang('First name'));
}

if ('true' == api_get_setting('show_email_addresses')) {
    $table->set_header($col++, get_lang('e-mail'));
    $table->set_column_filter($col - 1, 'email_filter');
}
$table->set_header($col++, get_lang('active'), false);
$table->set_column_filter($col - 1, 'active_filter');
$table->set_header($col++, get_lang('Detail'), false);
$table->set_column_filter($col - 1, 'Register_filter');
$table->set_form_actions(['subscribe' => get_lang('Register')], 'user');

if (!empty($_POST['keyword'])) {
    $keyword_name = Security::remove_XSS($_POST['keyword']);
    echo '<br/>'.get_lang('Search resultsFor').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

Display :: display_header($tool_name, 'User');

// Build search-form
switch ($type) {
    case STUDENT:
        $url = api_get_path(WEB_CODE_PATH).'user/user.php?'.api_get_cidreq().'';
        break;
    case COURSEMANAGER:
        $url = api_get_path(WEB_CODE_PATH).'user/user.php?'.api_get_cidreq().'&type='.COURSEMANAGER;
        break;
}

$actionsLeft = Display::url(
    Display::return_icon('back.png', get_lang('Back'), '', ICON_SIZE_MEDIUM),
    $url
);

if (isset($_GET['subscribe_user_filter_value']) && !empty($_GET['subscribe_user_filter_value'])) {
    $actionsLeft .= '<a href="subscribe_user.php?type='.$type.'">'.
        Display::return_icon('clean_group.gif').' '.get_lang('Clear filter results').'</a>';
}
$extraForm = '';
if ('true' === api_get_setting('ProfilingFilterAddingUsers')) {
    $extraForm = display_extra_profile_fields_filter();
}

// Build search-form
$form = new FormValidator(
    'search_user',
    'get',
    api_get_self().'?'.api_get_cidreq(),
    '',
    null,
    FormValidator::LAYOUT_INLINE
);
$form->addText('keyword', '', false);
$form->addElement('hidden', 'type', $type);
$form->addElement('hidden', 'cidReq', api_get_course_id());
$form->addButtonSearch(get_lang('Search'));
echo Display::toolbarAction('toolbar-subscriber', [$actionsLeft, $extraForm, $form->returnForm()]);

$option = COURSEMANAGER == $type ? 2 : 1;
echo UserManager::getUserSubscriptionTab($option);

// Display table
$table->display();
Display::display_footer();

/*		SHOW LIST OF USERS  */

/**
 ** Get the users to display on the current page.
 */
function get_number_of_users()
{
    // Database table definition
    $user_table = Database::get_main_table(TABLE_MAIN_USER);
    $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tbl_session_rel_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $table_user_field_values = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);

    $courseCode = api_get_course_id();
    $sessionId = api_get_session_id();

    if (isset($_REQUEST['type']) && 'teacher' === $_REQUEST['type']) {
        if (0 != api_get_session_id()) {
            $sql = "SELECT COUNT(u.id)
                    FROM $user_table u
                    LEFT JOIN $tbl_session_rel_course_user cu
                    ON
                        u.id = cu.user_id AND
                        c_id = '".api_get_course_int_id()."' AND
                        session_id ='".$sessionId."'
                    WHERE
                        cu.user_id IS NULL AND
                        u.status = 1 AND
                        (u.official_code <> 'ADMIN' OR u.official_code IS NULL) ";

            if (api_is_multiple_url_enabled()) {
                $url_access_id = api_get_current_access_url_id();
                if (-1 != $url_access_id) {
                    $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
                    $sql = "SELECT COUNT(u.id)
                            FROM $user_table u
                            LEFT JOIN $tbl_session_rel_course_user cu
                            ON
                                u.id = cu.user_id AND cu.c_id = '".api_get_course_int_id()."' AND
                                session_id ='".$sessionId."'
                            INNER JOIN  $tbl_url_rel_user as url_rel_user
                            ON (url_rel_user.user_id = u.id)
                            WHERE
                                cu.user_id IS NULL AND
                                access_url_id= $url_access_id AND
                                u.status = 1 AND
                                (u.official_code <> 'ADMIN' OR u.official_code IS NULL)
                            ";
                }
            }
        } else {
            $sql = "SELECT COUNT(u.id)
                    FROM $user_table u
                    LEFT JOIN $course_user_table cu
                    ON u.id = cu.user_id and c_id='".api_get_course_int_id()."'
                    WHERE cu.user_id IS NULL AND u.status<>".DRH." ";

            if (api_is_multiple_url_enabled()) {
                $url_access_id = api_get_current_access_url_id();
                if (-1 != $url_access_id) {
                    $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

                    $sql = "SELECT COUNT(u.id)
                        FROM $user_table u
                        LEFT JOIN $course_user_table cu
                        ON u.id = cu.user_id AND c_id='".api_get_course_int_id()."'
                        INNER JOIN  $tbl_url_rel_user as url_rel_user
                        ON (url_rel_user.user_id = u.id)
                        WHERE cu.user_id IS NULL AND u.status<>".DRH." AND access_url_id= $url_access_id ";
                }
            }
        }
    } else {
        // students
        if (0 != $sessionId) {
            $sql = "SELECT COUNT(u.id)
                    FROM $user_table u
                    LEFT JOIN $tbl_session_rel_course_user cu
                    ON
                        u.id = cu.user_id AND
                        c_id='".api_get_course_int_id()."' AND
                        session_id ='".$sessionId."'
                    WHERE
                        cu.user_id IS NULL AND
                        u.status<>".DRH." AND
                        (u.official_code <> 'ADMIN' OR u.official_code IS NULL) ";

            if (api_is_multiple_url_enabled()) {
                $url_access_id = api_get_current_access_url_id();
                if (-1 != $url_access_id) {
                    $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
                    $sql = "SELECT COUNT(u.id)
                            FROM $user_table u
                            LEFT JOIN $tbl_session_rel_course_user cu
                            ON
                                u.id = cu.user_id AND
                                c_id='".api_get_course_int_id()."' AND
                                session_id ='".$sessionId."'
                            INNER JOIN $tbl_url_rel_user as url_rel_user
                            ON (url_rel_user.user_id = u.id)
                            WHERE
                                cu.user_id IS NULL AND
                                u.status<>".DRH." AND
                                access_url_id= $url_access_id AND
                                (u.official_code <> 'ADMIN' OR u.official_code IS NULL) ";
                }
            }
        } else {
            $sql = "SELECT COUNT(u.id)
                    FROM $user_table u
                    LEFT JOIN $course_user_table cu
                    ON u.id = cu.user_id AND c_id='".api_get_course_int_id()."'";

            // we change the SQL when we have a filter
            if (isset($_GET['subscribe_user_filter_value']) &&
                !empty($_GET['subscribe_user_filter_value']) &&
                'true' === api_get_setting('ProfilingFilterAddingUsers')
            ) {
                $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                $sql .= "
                    LEFT JOIN $table_user_field_values field_values
                    ON field_values.item_id = u.id
                    WHERE
                        cu.user_id IS NULL AND
                        u.status <> ".DRH." AND
                        field_values.field_id = '".intval($field_identification[0])."' AND
                        field_values.value = '".Database::escape_string($field_identification[1])."'
                    ";
            } else {
                $sql .= "WHERE cu.user_id IS NULL AND u.status <> ".DRH." ";
            }

            if (api_is_multiple_url_enabled()) {
                $url_access_id = api_get_current_access_url_id();

                if (-1 != $url_access_id) {
                    $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
                    $sql = "SELECT COUNT(u.id)
                            FROM $user_table u
                            LEFT JOIN $course_user_table cu
                            ON u.id = cu.user_id AND c_id='".api_get_course_int_id()."'
                            INNER JOIN $tbl_url_rel_user as url_rel_user
                            ON (url_rel_user.user_id = u.id)
                            WHERE cu.user_id IS NULL AND access_url_id= $url_access_id AND u.status <> ".DRH." ";
                }
            }
        }
    }

    // when there is a keyword then we are searching and we have to change the SQL statement
    if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
        $keyword = Database::escape_string(trim($_REQUEST['keyword']));
        $sql .= " AND (
            firstname LIKE '%".$keyword."%' OR
            lastname LIKE '%".$keyword."%' OR
            email LIKE '%".$keyword."%' OR
            username LIKE '%".$keyword."%' OR
            official_code LIKE '%".$keyword."%'
        )";

        // we also want to search for users who have something in their profile fields that matches the keyword
        if ('true' === api_get_setting('ProfilingFilterAddingUsers')) {
            $additional_users = search_additional_profile_fields($keyword);
        }

        // getting all the users of the course (to make sure that we do not display users that are already in the course)
        if (!empty($sessionId)) {
            $a_course_users = CourseManager:: get_user_list_from_course_code(
                $courseCode,
                $sessionId
            );
        } else {
            $a_course_users = CourseManager:: get_user_list_from_course_code(
                $courseCode,
                0
            );
        }
        foreach ($a_course_users as $user_id => $course_user) {
            $users_of_course[] = $course_user['user_id'];
        }
    }
    $sql .= " AND u.status <> ".ANONYMOUS." ";
    $res = Database::query($sql);
    $count_user = 0;

    if ($res) {
        $row = Database::fetch_row($res);
        $count_user = $row[0];
    }

    return $count_user;
}
/**
 * Get the users to display on the current page.
 */
function get_user_data($from, $number_of_items, $column, $direction)
{
    $url_access_id = api_get_current_access_url_id();
    $course_code = api_get_course_id();
    $sessionId = api_get_session_id();
    $courseId = api_get_course_int_id();

    // Database table definitions
    $user_table = Database::get_main_table(TABLE_MAIN_USER);
    $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tbl_session_rel_course_user = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $table_user_field_values = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
    $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

    // adding teachers
    $is_western_name_order = api_is_western_name_order();

    if ('true' === api_get_setting('show_email_addresses')) {
        $select_fields = "u.id              AS col0,
                u.official_code        AS col1,
                ".($is_western_name_order
                ? "u.firstname         AS col2,
                u.lastname             AS col3,"
                : "u.lastname          AS col2,
                u.firstname            AS col3,")."
                u.email 	           AS col4,
                u.active               AS col5,
                u.id              AS col6";
    } else {
        $select_fields = "u.id    AS col0,
                u.official_code        AS col1,
                ".($is_western_name_order
                ? "u.firstname         AS col2,
                u.lastname             AS col3,"
                : "u.lastname          AS col2,
                u.firstname            AS col3,")."
                u.active               AS col4,
                u.id              AS col5";
    }
    if (isset($_REQUEST['type']) && COURSEMANAGER == $_REQUEST['type']) {
        // adding a teacher through a session
        if (!empty($sessionId)) {
            $sql = "SELECT $select_fields
                    FROM $user_table u
                    LEFT JOIN $tbl_session_rel_course_user cu
                    ON
                        u.id = cu.user_id AND
                        c_id ='".$courseId."' AND
                        session_id ='".$sessionId."'
                    INNER JOIN  $tbl_url_rel_user as url_rel_user
                    ON (url_rel_user.user_id = u.id) ";

            // applying the filter of the additional user profile fields
            if (isset($_GET['subscribe_user_filter_value']) &&
                !empty($_GET['subscribe_user_filter_value']) &&
                'true' == api_get_setting('ProfilingFilterAddingUsers')
            ) {
                $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                $sql .= "
                    LEFT JOIN $table_user_field_values field_values
                        ON field_values.item_id = u.id
                    WHERE
                        cu.user_id IS NULL AND
                        u.status = 1 AND
                        (u.official_code <> 'ADMIN' OR u.official_code IS NULL) AND
                        field_values.field_id = '".intval($field_identification[0])."' AND
                        field_values.value = '".Database::escape_string($field_identification[1])."'";
            } else {
                $sql .= "WHERE cu.user_id IS NULL AND u.status=1 AND (u.official_code <> 'ADMIN' OR u.official_code IS NULL) ";
            }
            $sql .= " AND access_url_id = $url_access_id";
        } else {
            // adding a teacher NOT through a session
            $sql = "SELECT $select_fields
                    FROM $user_table u
                    LEFT JOIN $course_user_table cu
                    ON u.id = cu.user_id AND c_id = '".$courseId."'";
            // applying the filter of the additional user profile fields
            if (isset($_GET['subscribe_user_filter_value']) &&
                !empty($_GET['subscribe_user_filter_value']) &&
                'true' == api_get_setting('ProfilingFilterAddingUsers')
            ) {
                $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                $sql .= "
                    LEFT JOIN $table_user_field_values field_values
                        ON field_values.item_id = u.id
                    WHERE
                        cu.user_id IS NULL AND u.status<>".DRH." AND
                        field_values.field_id = '".intval($field_identification[0])."' AND
                        field_values.value = '".Database::escape_string($field_identification[1])."'";
            } else {
                $sql .= "WHERE cu.user_id IS NULL AND u.status <> ".DRH." ";
            }

            // adding a teacher NOT trough a session on a portal with multiple URLs
            if (api_is_multiple_url_enabled()) {
                if (-1 != $url_access_id) {
                    $sql = "SELECT $select_fields
                            FROM $user_table u
                            LEFT JOIN $course_user_table cu
                            ON u.id = cu.user_id and c_id='".$courseId."'
                            INNER JOIN  $tbl_url_rel_user as url_rel_user
                            ON (url_rel_user.user_id = u.id) ";

                    // applying the filter of the additional user profile fields
                    if (isset($_GET['subscribe_user_filter_value']) &&
                        !empty($_GET['subscribe_user_filter_value']) &&
                        'true' == api_get_setting('ProfilingFilterAddingUsers')
                    ) {
                        $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                        $sql .= "
                            LEFT JOIN $table_user_field_values field_values
                                ON field_values.item_id = u.id
                            WHERE
                                cu.user_id IS NULL AND
                                u.status<>".DRH." AND
                                field_values.field_id = '".intval($field_identification[0])."' AND
                                field_values.value = '".Database::escape_string($field_identification[1])."'";
                    } else {
                        $sql .= "WHERE cu.user_id IS NULL AND u.status <> ".DRH." AND access_url_id= $url_access_id ";
                    }
                }
            }
        }
    } else {
        // adding a student
        if (!empty($sessionId)) {
            $sql = "SELECT $select_fields
                    FROM $user_table u
                    LEFT JOIN $tbl_session_rel_course_user cu
                    ON
                        u.id = cu.user_id AND
                        c_id = $courseId AND
                        session_id = $sessionId ";

            if (api_is_multiple_url_enabled()) {
                $sql .= " INNER JOIN $tbl_url_rel_user as url_rel_user ON (url_rel_user.user_id = u.id) ";
            }

            // applying the filter of the additional user profile fields
            if (isset($_GET['subscribe_user_filter_value']) &&
                !empty($_GET['subscribe_user_filter_value'])
            ) {
                $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                $sql .= "
                    LEFT JOIN $table_user_field_values field_values
                        ON field_values.item_id = u.id
                    WHERE
                        cu.user_id IS NULL AND
                        u.status<>".DRH." AND
                        (u.official_code <> 'ADMIN' OR u.official_code IS NULL) AND
                        field_values.field_id = '".intval($field_identification[0])."' AND
                        field_values.value = '".Database::escape_string($field_identification[1])."'";
            } else {
                $sql .= "WHERE
                            cu.user_id IS NULL AND
                            u.status <> ".DRH." AND
                            (u.official_code <> 'ADMIN' OR u.official_code IS NULL) ";
            }
            if (api_is_multiple_url_enabled()) {
                $sql .= "AND access_url_id = $url_access_id";
            }
        } else {
            $sql = "SELECT $select_fields
                    FROM $user_table u
                    LEFT JOIN $course_user_table cu
                    ON
                        u.id = cu.user_id AND
                        c_id = $courseId ";

            // applying the filter of the additional user profile fields
            if (isset($_GET['subscribe_user_filter_value']) && !empty($_GET['subscribe_user_filter_value'])) {
                $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                $sql .= "
                    LEFT JOIN $table_user_field_values field_values
                        ON field_values.item_id = u.id
                    WHERE
                        cu.user_id IS NULL AND
                        u.status <> ".DRH." AND
                        field_values.field_id = '".intval($field_identification[0])."' AND
                        field_values.value = '".Database::escape_string($field_identification[1])."'";
            } else {
                $sql .= "WHERE cu.user_id IS NULL AND u.status <> ".DRH." ";
            }

            //showing only the courses of the current Chamilo access_url_id
            if (api_is_multiple_url_enabled()) {
                if (-1 != $url_access_id) {
                    $sql = "SELECT $select_fields
                        FROM $user_table u
                        LEFT JOIN $course_user_table cu
                        ON u.id = cu.user_id AND c_id='".$courseId."'
                        INNER JOIN  $tbl_url_rel_user as url_rel_user
                        ON (url_rel_user.user_id = u.id) ";

                    // applying the filter of the additional user profile fields
                    if (isset($_GET['subscribe_user_filter_value']) &&
                        !empty($_GET['subscribe_user_filter_value']) &&
                        'true' == api_get_setting('ProfilingFilterAddingUsers')
                    ) {
                        $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                        $sql .= "
                            LEFT JOIN $table_user_field_values field_values
                                ON field_values.item_id = u.id
                            WHERE
                                cu.user_id IS NULL AND
                                u.status<>".DRH." AND
                                field_values.field_id = '".intval($field_identification[0])."' AND
                                field_values.value = '".Database::escape_string($field_identification[1])."' AND
                                access_url_id = $url_access_id
                            ";
                    } else {
                        $sql .= "WHERE cu.user_id IS NULL AND u.status<>".DRH." AND access_url_id = $url_access_id ";
                    }
                }
            }
        }
    }

    // adding additional WHERE statements to the SQL for the search functionality
    if (isset($_REQUEST['keyword'])) {
        $keyword = Database::escape_string(trim($_REQUEST['keyword']));
        $sql .= " AND (
                    firstname LIKE '%".$keyword."%' OR
                    lastname LIKE '%".$keyword."%' OR
                    email LIKE '%".$keyword."%' OR
                    username LIKE '%".$keyword."%' OR
                    official_code LIKE '%".$keyword."%'
                    )
                ";

        if ('true' === api_get_setting('ProfilingFilterAddingUsers')) {
            // we also want to search for users who have something in
            // their profile fields that matches the keyword
            $additional_users = search_additional_profile_fields($keyword);
        }

        // getting all the users of the course (to make sure that we do not
        // display users that are already in the course)
        if (!empty($sessionId)) {
            $a_course_users = CourseManager :: get_user_list_from_course_code($course_code, $sessionId);
        } else {
            $a_course_users = CourseManager :: get_user_list_from_course_code($course_code, 0);
        }
        foreach ($a_course_users as $user_id => $course_user) {
            $users_of_course[] = $course_user['user_id'];
        }
    }

    $sql .= " AND u.status != ".ANONYMOUS." ";
    $column = (int) $column;
    $direction = !in_array(strtolower(trim($direction)), ['asc', 'desc']) ? 'asc' : $direction;
    // Sorting and pagination (used by the sortable table)
    $sql .= " ORDER BY col$column $direction ";
    $from = (int) $from;
    $number_of_items = (int) $number_of_items;
    $sql .= " LIMIT $from, $number_of_items";

    $res = Database::query($sql);
    $users = [];
    while ($user = Database::fetch_row($res)) {
        $users[] = $user;
    }

    return $users;
}
/**
 * Returns a mailto-link.
 *
 * @param string $email An email-address
 *
 * @return string HTML-code with a mailto-link
 */
function email_filter($email)
{
    return Display :: encrypted_mailto_link($email, $email);
}
/**
 * Build the Register-column of the table.
 *
 * @param int $user_id The user id
 *
 * @return string Some HTML-code
 */
function Register_filter($user_id)
{
    if (isset($_REQUEST['type']) && COURSEMANAGER == $_REQUEST['type']) {
        $type = COURSEMANAGER;
    } else {
        $type = STUDENT;
    }
    $user_id = (int) $user_id;

    $result = '<a class="btn btn-small btn-primary" href="'.api_get_self().'?'.api_get_cidreq().'&Registerister=yes&type='.$type.'&user_id='.$user_id.'">'.
        get_lang("Register").'</a>';

    return $result;
}

/**
 * Build the active-column of the table to lock or unlock a certain user
 * lock = the user can no longer use this account.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @param int    $active     the current state of the account
 * @param string $url_params
 *
 * @return string Some HTML-code with the lock/unlock button
 */
function active_filter($active, $url_params, $row)
{
    $_user = api_get_user_info();
    if ('1' == $active) {
        $action = 'Accountactive';
        $image = 'accept';
    }

    if ('0' == $active) {
        $action = 'AccountInactive';
        $image = 'error';
    }
    $result = '';
    if ($row['0'] != $_user['user_id']) {
        // you cannot lock yourself out otherwise you could disable all the accounts
        // including your own => everybody is locked out and nobody can change it anymore.
        $result = Display::return_icon(
            $image.'.png',
            get_lang(ucfirst($action)),
            [],
            ICON_SIZE_TINY
        );
    }

    return $result;
}

/**
 * Search the additional user profile fields defined by the platform administrator in
 * platform administration > profiling for a given keyword.
 * We not only search in the predefined options but also in the input fields wherer
 * the user can enter some text.
 *
 * For this we get the additional profile field options that match the (search) keyword,
 * then we find all the users who have entered the (search)keyword in a input field of the
 * additional profile fields or have chosen one of the matching predefined options
 *
 * @param string $keyword a keyword we are looking for in the additional profile fields
 *
 * @return array $additional_users an array with the users who have an additional profile field that matches the keyword
 */
function search_additional_profile_fields($keyword)
{
    // database table definitions
    $table_user_field_options = Database::get_main_table(TABLE_EXTRA_FIELD_OPTIONS);
    $table_user_field_values = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
    $tableExtraField = Database::get_main_table(TABLE_EXTRA_FIELD);
    $table_user = Database::get_main_table(TABLE_MAIN_USER);

    $keyword = Database::escape_string($keyword);
    // getting the field option text that match this keyword (for radio buttons and checkboxes)
    $sql = "SELECT * FROM $table_user_field_options
            WHERE display_text LIKE '%".$keyword."%'";
    $result_profiling = Database::query($sql);
    while ($profiling_field_options = Database::fetch_array($result_profiling)) {
        $profiling_field_options_exact_values[] = $profiling_field_options;
    }
    $profiling_field_options_exact_values_sql = '';
    foreach ($profiling_field_options_exact_values as $profilingkey => $profilingvalue) {
        $profiling_field_options_exact_values_sql .= " OR (field_id = '".$profilingvalue['field_id']."' AND value='".$profilingvalue['option_value']."') ";
    }

    $extraFieldType = ExtraField::USER_FIELD_TYPE;

    // getting all the user ids of the users who have chosen on of the predefined fields that contain the keyword
    // or all the users who have entered the keyword in a free-form field
    $sql = "SELECT
                user.id as col0,
                user.official_code as col1,
                user.lastname as col2,
                user.firstname as col3,
                user.email as col4,
                user.active as col5,
                user.id as col6
            FROM $table_user user, $table_user_field_values user_values, $tableExtraField e
            WHERE
                user.id = user_values.item_id AND
                user_values.field_id = e.id AND
                e.extra_field_type = $extraFieldType AND
                (value LIKE '%".$keyword."%'".$profiling_field_options_exact_values_sql.")";
    $result = Database::query($sql);
    $additional_users = [];
    while ($profiled_users = Database::fetch_array($result)) {
        $additional_users[$profiled_users['col0']] = $profiled_users;
    }

    return $additional_users;
}

/**
 * This function displays a dropdown list with all the additional user
 * profile fields defined by the platform administrator in
 * platform administration > profiling.
 * Only the fields that have predefined fields are usefull for such a filter.
 */
function display_extra_profile_fields_filter()
{
    // getting all the additional user profile fields
    $extra = UserManager::get_extra_fields(0, 50, 5, 'ASC');
    $return = '<option value="">'.get_lang('Select filter').'</option>';

    // looping through the additional user profile fields
    foreach ($extra as $id => $field_details) {
        // $field_details[2] contains the type of the additional user profile field
        switch ($field_details[2]) {
            // text fields cannot be used as a filter
            case ExtraFieldModel::FIELD_TYPE_TEXT:
                break;
            // text area fields cannot be used as a filter
            case ExtraFieldModel::FIELD_TYPE_TEXTAREA:
                break;
            case ExtraFieldModel::FIELD_TYPE_RADIO:
            case ExtraFieldModel::FIELD_TYPE_SELECT:
            case ExtraFieldModel::FIELD_TYPE_SELECT_MULTIPLE:
                $return .= '<optgroup label="'.$field_details[3].'">';
                foreach ($field_details[9] as $option_id => $option_details) {
                    if (isset($_GET['subscribe_user_filter_value']) &&
                        $field_details[0].'*'.$option_details[1] == $_GET['subscribe_user_filter_value']
                    ) {
                        $selected = 'selected="selected"';
                    } else {
                        $selected = false;
                    }
                    $return .= '<option value="'.$field_details[0].'*'.$option_details[1].'" '.$selected.'>'.$option_details[2].'</option>';
                }
                $return .= '</optgroup>';
                break;
        }
    }

    $html = '<form class="form-inline" id="subscribe_user_filter" name="subscribe_user_filter" method="get" action="'.api_get_self().'?'.api_get_cidreq().'">';
    $html .= '<input type="hidden" name="type" id="type" value="'.Security::remove_XSS($_REQUEST['type']).'" />';
    $html .= '<select name="subscribe_user_filter_value" id="subscribe_user_filter_value">'.$return.'</select>';
    $html .= '<button type="submit" name="submit_filter" id="submit_filter" value="" class="search">'.get_lang('Filter').'</button>';
    $html .= '</form>';

    return $html;
}
