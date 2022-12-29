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

if (api_get_setting('allow_user_course_subscription_by_course_admin') === 'false') {
    if (!api_is_platform_admin()) {
        api_not_allowed(true);
    }
}

// Access restriction
if (!api_is_allowed_to_edit()) {
    api_not_allowed(true);
}

$tool_name = get_lang('SubscribeUserToCourse');
$type = isset($_REQUEST['type']) ? (int) $_REQUEST['type'] : STUDENT;
$keyword = isset($_REQUEST['keyword']) ? Security::remove_XSS($_REQUEST['keyword']) : null;

$courseInfo = api_get_course_info();

if ($type == COURSEMANAGER) {
    $tool_name = get_lang('SubscribeUserToCourseAsTeacher');
}

//extra entries in breadcrumb
$interbreadcrumb[] = [
    'url' => 'user.php?'.api_get_cidreq(),
    'name' => get_lang('ToolUser'),
];
if ($keyword) {
    $interbreadcrumb[] = [
        'url' => 'subscribe_user.php?type='.$type.'&'.api_get_cidreq(),
        'name' => $tool_name,
    ];
    $tool_name = get_lang('SearchResults');
}

$sessionId = api_get_session_id();
$list_register_user = '';
$list_not_register_user = '';

if (isset($_REQUEST['register'])) {
    $userInfo = api_get_user_info($_REQUEST['user_id']);
    if ($userInfo) {
        if ($type === COURSEMANAGER) {
            if (!empty($sessionId)) {
                $message = $userInfo['complete_name_with_username'].' '.get_lang('AddedToCourse');
                SessionManager::set_coach_to_course_session(
                    $_REQUEST['user_id'],
                    $sessionId,
                    $courseInfo['real_id']
                );
                Display::addFlash(Display::return_message($message));
            } else {
                CourseManager::subscribeUser(
                    $_REQUEST['user_id'],
                    $courseInfo['code'],
                    COURSEMANAGER
                );
            }
        } else {
            CourseManager::subscribeUser(
                $_REQUEST['user_id'],
                $courseInfo['code']
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
                        if ($type === COURSEMANAGER) {
                            if (!empty($sessionId)) {
                                $message = $userInfo['complete_name_with_username'].' '.get_lang('AddedToCourse');
                                $result = SessionManager::set_coach_to_course_session(
                                    $user_id,
                                    $sessionId,
                                    $courseInfo['real_id']
                                );
                                if ($result) {
                                    $isSuscribe[] = $message;
                                }
                            } else {
                                CourseManager::subscribeUser($user_id, $courseInfo['code'], COURSEMANAGER);
                            }
                        } else {
                            CourseManager::subscribeUser($user_id, $courseInfo['code']);
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

$htmlHeadXtra[] = '<script>
function display_advanced_search_form () {
    if ($("#advanced_search_form").css("display") == "none") {
        $("#advanced_search_form").css("display", "block");
        $("#img_plus_and_minus").html(\''.Display::returnFontAwesomeIcon('arrow-down').' '.get_lang('AdvancedSearch').'\');
    } else {
        $("#advanced_search_form").css("display", "none");
        $("#img_plus_and_minus").html(\''.Display::returnFontAwesomeIcon('arrow-right').' '.get_lang('AdvancedSearch').'\');
    }
}
</script>';

$searchAdvanced = '<a id="advanced_params" class="btn btn-default advanced_options" onclick="display_advanced_search_form();">'.
    '<span id="img_plus_and_minus">'.
    Display::returnFontAwesomeIcon('arrow-right').' '.get_lang('AdvancedSearch').
    '</span>'.
    '</a>';

// Build table
if (api_get_configuration_value('session_course_users_subscription_limited_to_session_users') && !empty($sessionId)) {
    $table = new SortableTable(
        'subscribe_users',
        'getRestrictedSessionNumberOfUsers',
        'getRestrictedSessionUserList',
        ($is_western_name_order xor $sort_by_first_name) ? 3 : 2
    );
} else {
    $table = new SortableTable(
        'subscribe_users',
        'get_number_of_users',
        'get_user_data',
        ($is_western_name_order xor $sort_by_first_name) ? 3 : 2
    );
}
$parameters['keyword'] = $keyword;
$parameters['type'] = $type;
$table->set_additional_parameters($parameters);
$col = 0;
$table->set_header($col++, '', false);
$table->set_header($col++, get_lang('OfficialCode'));
if (api_is_western_name_order()) {
    $table->set_header($col++, get_lang('FirstName'));
    $table->set_header($col++, get_lang('LastName'));
} else {
    $table->set_header($col++, get_lang('LastName'));
    $table->set_header($col++, get_lang('FirstName'));
}

if (api_get_setting('show_email_addresses') == 'true') {
    $table->set_header($col++, get_lang('Email'));
    $table->set_column_filter($col - 1, 'email_filter');
}
$table->set_header($col++, get_lang('Active'), false);
$table->set_column_filter($col - 1, 'active_filter');
$table->set_header($col++, get_lang('Actions'), false);
$table->set_column_filter($col - 1, 'reg_filter');
$table->set_form_actions(['subscribe' => get_lang('reg')], 'user');

if (!empty($_POST['keyword'])) {
    $keyword_name = Security::remove_XSS($_POST['keyword']);
    echo '<br/>'.get_lang('SearchResultsFor').' <span style="font-style: italic ;"> '.$keyword_name.' </span><br>';
}

Display::display_header($tool_name, 'User');

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
        Display::return_icon('clean_group.gif').' '.get_lang('ClearFilterResults').'</a>';
}

$extraForm = '<a id="advanced_params" class="btn btn-default advanced_options" onclick="display_advanced_search_form();">'.
    '<span id="img_plus_and_minus">'.
    Display::returnFontAwesomeIcon('arrow-right').' '.get_lang('AdvancedSearch').
    '</span>'.
    '</a>';

if (api_get_setting('ProfilingFilterAddingUsers') === 'true') {
    $extraForm .= display_extra_profile_fields_filter();
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
echo Display::toolbarAction('toolbar-subscriber', [$actionsLeft, $extraForm, $form->returnForm()], [4, 4, 4]);

$advancedForm = new FormValidator(
    'advanced_search',
    'get',
    '',
    '',
    [],
    FormValidator::LAYOUT_HORIZONTAL
);

$advancedForm->addElement('html', '<div id="advanced_search_form" style="display:none;">');
$advancedForm->addElement('header', get_lang('AdvancedSearch'));
$advancedForm->addText('keyword_firstname', get_lang('FirstName'), false);
$advancedForm->addText('keyword_lastname', get_lang('LastName'), false);
$advancedForm->addText('keyword_username', get_lang('LoginName'), false);
$advancedForm->addText('keyword_email', get_lang('Email'), false);
$advancedForm->addText('keyword_officialcode', get_lang('OfficialCode'), false);
$advancedForm->addElement('hidden', 'type', $type);
$advancedForm->addElement('hidden', 'cidReq', api_get_course_id());
$advancedForm->addButtonSearch(get_lang('SearchUsers'));
$advancedForm->addElement('html', '</div>');

$advancedForm = $advancedForm->returnForm();
echo $advancedForm;

$option = $type == COURSEMANAGER ? 2 : 1;
echo UserManager::getUserSubscriptionTab($option);

// Display table
$table->display();
Display::display_footer();

/*		SHOW LIST OF USERS  */

function getRestrictedSessionNumberOfUsers(): int
{
    $tblUser = Database::get_main_table(TABLE_MAIN_USER);
    $tblSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    $tblSessionRelCourseRelUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $urlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

    $sessionId = api_get_session_id();
    $courseId = api_get_course_int_id();
    $urlAccessId = api_get_current_access_url_id();

    $sql = "SELECT COUNT(DISTINCT u.id) nbr
        FROM $tblSessionRelUser s
        INNER JOIN $tblUser u ON (u.id = s.user_id)
        INNER JOIN $urlTable url ON (url.user_id = u.id)
        LEFT JOIN $tblSessionRelCourseRelUser scru
            ON (s.session_id = scru.session_id AND s.user_id = scru.user_id AND scru.c_id = $courseId)
        WHERE
            s.session_id = $sessionId
            AND url.access_url_id = $urlAccessId
            AND scru.user_id IS NULL";

    $sql = getSqlFilters($sql);

    $result = Database::fetch_assoc(Database::query($sql));

    return (int) $result['nbr'];
}

function getRestrictedSessionUserList($from, $numberOfItems, $column, $direction): array
{
    $tblUser = Database::get_main_table(TABLE_MAIN_USER);
    $tblSessionRelUser = Database::get_main_table(TABLE_MAIN_SESSION_USER);
    $tblSessionRelCourseRelUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $urlTable = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

    $selectNames = api_is_western_name_order()
        ? "u.firstname AS col2, u.lastname AS col3"
        : "u.lastname AS col2, u.firstname AS col3";

    $selectFields = "u.user_id AS col0, u.official_code AS col1, $selectNames, u.active AS col4, u.user_id AS col5";

    if (api_get_setting('show_email_addresses') === 'true') {
        $selectFields = "u.id AS col0, u.official_code AS col1, $selectNames, u.email AS col4, u.active AS col5, u.user_id AS col6";
    }

    $sessionId = api_get_session_id();
    $courseId = api_get_course_int_id();
    $urlAccessId = api_get_current_access_url_id();

    $sql = "SELECT $selectFields
        FROM $tblSessionRelUser s
        INNER JOIN $tblUser u ON (u.id = s.user_id)
        INNER JOIN $urlTable url ON (url.user_id = u.id)
        LEFT JOIN $tblSessionRelCourseRelUser scru
            ON (s.session_id = scru.session_id AND s.user_id = scru.user_id AND scru.c_id = $courseId)
        WHERE
            s.session_id = $sessionId
            AND url.access_url_id = $urlAccessId
            AND scru.user_id IS NULL";

    $sql = getSqlFilters($sql);

    $sql .= " ORDER BY col$column $direction LIMIT $from, $numberOfItems";

    return Database::store_result(Database::query($sql));
}

function getSqlFilters(string $sql): string
{
    if (isset($_REQUEST['type']) && $_REQUEST['type'] == COURSEMANAGER) {
        $sql .= " AND u.status = ".COURSEMANAGER;
    } else {
        $sql .= " AND u.status <> ".DRH;
    }

    if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
        $keyword = Database::escape_string(trim($_REQUEST['keyword']));
        $sql .= " AND (
            u.firstname LIKE '%".$keyword."%' OR
            u.lastname LIKE '%".$keyword."%' OR
            u.email LIKE '%".$keyword."%' OR
            u.username LIKE '%".$keyword."%' OR
            u.official_code LIKE '%".$keyword."%'
        )";
    }

    return $sql;
}

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

    if (isset($_REQUEST['type']) && $_REQUEST['type'] == COURSEMANAGER) {
        $allowedRoles = implode(',', UserManager::getAllowedRolesAsTeacher());
        if (api_get_session_id() != 0) {
            $sql = "SELECT COUNT(u.id)
                    FROM $user_table u
                    LEFT JOIN $tbl_session_rel_course_user cu
                    ON
                        u.id = cu.user_id AND
                        c_id = '".api_get_course_int_id()."' AND
                        session_id ='".$sessionId."'
                    WHERE
                        cu.user_id IS NULL AND
                        u.status IN ($allowedRoles) AND
                        (u.official_code <> 'ADMIN' OR u.official_code IS NULL) ";

            if (api_is_multiple_url_enabled()) {
                $url_access_id = api_get_current_access_url_id();
                if ($url_access_id != -1) {
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
                                u.status IN ($allowedRoles) AND
                                (u.official_code <> 'ADMIN' OR u.official_code IS NULL)
                            ";
                }
            }
        } else {
            $sql = "SELECT COUNT(u.id)
                    FROM $user_table u
                    LEFT JOIN $course_user_table cu
                    ON u.id = cu.user_id and c_id='".api_get_course_int_id()."'
                    WHERE cu.user_id IS NULL AND u.status IN ($allowedRoles)";

            if (api_is_multiple_url_enabled()) {
                $url_access_id = api_get_current_access_url_id();
                if ($url_access_id != -1) {
                    $tbl_url_rel_user = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);

                    $sql = "SELECT COUNT(u.id)
                        FROM $user_table u
                        LEFT JOIN $course_user_table cu
                        ON u.id = cu.user_id AND c_id='".api_get_course_int_id()."'
                        INNER JOIN  $tbl_url_rel_user as url_rel_user
                        ON (url_rel_user.user_id = u.id)
                        WHERE cu.user_id IS NULL AND u.status IN ($allowedRoles) AND access_url_id= $url_access_id ";
                }
            }
        }
    } else {
        // students
        if ($sessionId != 0) {
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
                if ($url_access_id != -1) {
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
                api_get_setting('ProfilingFilterAddingUsers') === 'true'
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

                if ($url_access_id != -1) {
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
    if (!empty($_GET['keyword_firstname']) || !empty($_GET['keyword_lastname']) || !empty($_GET['keyword_username']) || !empty($_GET['keyword_email']) || !empty($_GET['keyword_officialcode'])) {
        $condition = '';
        $keywords = [
            'firstname' => Security::remove_XSS(Database::escape_string($_GET['keyword_firstname'])),
            'lastname' => Security::remove_XSS(Database::escape_string($_GET['keyword_lastname'])),
            'username' => Security::remove_XSS(Database::escape_string($_GET['keyword_username'])),
            'email' => Security::remove_XSS(Database::escape_string($_GET['keyword_email'])),
            'official_code' => Security::remove_XSS(Database::escape_string($_GET['keyword_officialcode'])),
        ];

        foreach ($keywords as $keyword => $value) {
            if (!empty($value)) {
                if (!empty($condition)) {
                    $condition .= ' AND ';
                }
                $condition .= $keyword." LIKE '%".$value."%'";
            }
        }

        $user_table = Database::get_main_table(TABLE_MAIN_USER);
        $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
        $courseId = api_get_course_int_id();

        $sql = "SELECT COUNT(u.id)
                FROM $user_table u
                LEFT JOIN $course_user_table cu
                ON u.user_id = cu.user_id AND c_id = '".$courseId."' WHERE u.status <> ".DRH."";

        if (!empty($condition)) {
            $sql .= ' AND cu.user_id IS NULL';
            $sql .= ' AND ('.$condition.')';
            $sql .= " AND u.status != ".ANONYMOUS." ";
        }
    } elseif (!empty($_GET['keyword'])) {
        // when there is a keyword then we are searching and we have to change the SQL statement
        $keyword = Database::escape_string(trim($_REQUEST['keyword']));
        $sql .= " AND (
            firstname LIKE '%".$keyword."%' OR
            lastname LIKE '%".$keyword."%' OR
            email LIKE '%".$keyword."%' OR
            username LIKE '%".$keyword."%' OR
            official_code LIKE '%".$keyword."%'
        )";

        // we also want to search for users who have something in their profile fields that matches the keyword
        if (api_get_setting('ProfilingFilterAddingUsers') === 'true') {
            $additional_users = search_additional_profile_fields($keyword);
        }

        // getting all the users of the course (to make sure that we do not display users that are already in the course)
        if (!empty($sessionId)) {
            $a_course_users = CourseManager::get_user_list_from_course_code(
                $courseCode,
                $sessionId
            );
        } else {
            $a_course_users = CourseManager::get_user_list_from_course_code(
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

    if (api_get_setting('show_email_addresses') === 'true') {
        $select_fields = "u.id              AS col0,
                u.official_code        AS col1,
                ".($is_western_name_order
                ? "u.firstname         AS col2,
                u.lastname             AS col3,"
                : "u.lastname          AS col2,
                u.firstname            AS col3,")."
                u.email 	           AS col4,
                u.active               AS col5,
                u.user_id              AS col6";
    } else {
        $select_fields = "u.user_id    AS col0,
                u.official_code        AS col1,
                ".($is_western_name_order
                ? "u.firstname         AS col2,
                u.lastname             AS col3,"
                : "u.lastname          AS col2,
                u.firstname            AS col3,")."
                u.active               AS col4,
                u.user_id              AS col5";
    }
    if (isset($_REQUEST['type']) && $_REQUEST['type'] == COURSEMANAGER) {
        $allowedRoles = implode(',', UserManager::getAllowedRolesAsTeacher());
        // adding a teacher through a session
        if (!empty($sessionId)) {
            $sql = "SELECT $select_fields
                    FROM $user_table u
                    LEFT JOIN $tbl_session_rel_course_user cu
                    ON
                        u.user_id = cu.user_id AND
                        c_id ='".$courseId."' AND
                        session_id ='".$sessionId."'
                    INNER JOIN  $tbl_url_rel_user as url_rel_user
                    ON (url_rel_user.user_id = u.user_id) ";

            // applying the filter of the additional user profile fields
            if (isset($_GET['subscribe_user_filter_value']) &&
                !empty($_GET['subscribe_user_filter_value']) &&
                api_get_setting('ProfilingFilterAddingUsers') == 'true'
            ) {
                $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                $sql .= "
                    LEFT JOIN $table_user_field_values field_values
                        ON field_values.item_id = u.user_id
                    WHERE
                        cu.user_id IS NULL AND
                        u.status IN ($allowedRoles) AND
                        (u.official_code <> 'ADMIN' OR u.official_code IS NULL) AND
                        field_values.field_id = '".intval($field_identification[0])."' AND
                        field_values.value = '".Database::escape_string($field_identification[1])."'";
            } else {
                $sql .= "WHERE cu.user_id IS NULL AND u.status IN ($allowedRoles) AND (u.official_code <> 'ADMIN' OR u.official_code IS NULL) ";
            }
            $sql .= " AND access_url_id = $url_access_id";
        } else {
            // adding a teacher NOT through a session
            $sql = "SELECT $select_fields
                    FROM $user_table u
                    LEFT JOIN $course_user_table cu
                    ON u.user_id = cu.user_id AND c_id = '".$courseId."'";
            // applying the filter of the additional user profile fields
            if (isset($_GET['subscribe_user_filter_value']) &&
                !empty($_GET['subscribe_user_filter_value']) &&
                api_get_setting('ProfilingFilterAddingUsers') == 'true'
            ) {
                $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                $sql .= "
                    LEFT JOIN $table_user_field_values field_values
                        ON field_values.item_id = u.user_id
                    WHERE
                        cu.user_id IS NULL AND u.status IN ($allowedRoles) AND
                        field_values.field_id = '".intval($field_identification[0])."' AND
                        field_values.value = '".Database::escape_string($field_identification[1])."'";
            } else {
                $sql .= "WHERE cu.user_id IS NULL AND u.status IN ($allowedRoles) ";
            }

            // adding a teacher NOT trough a session on a portal with multiple URLs
            if (api_is_multiple_url_enabled()) {
                if ($url_access_id != -1) {
                    $sql = "SELECT $select_fields
                            FROM $user_table u
                            LEFT JOIN $course_user_table cu
                            ON u.user_id = cu.user_id and c_id='".$courseId."'
                            INNER JOIN  $tbl_url_rel_user as url_rel_user
                            ON (url_rel_user.user_id = u.user_id) ";

                    // applying the filter of the additional user profile fields
                    if (isset($_GET['subscribe_user_filter_value']) &&
                        !empty($_GET['subscribe_user_filter_value']) &&
                        api_get_setting('ProfilingFilterAddingUsers') == 'true'
                    ) {
                        $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                        $sql .= "
                            LEFT JOIN $table_user_field_values field_values
                                ON field_values.item_id = u.user_id
                            WHERE
                                cu.user_id IS NULL AND
                                u.status IN ($allowedRoles) AND
                                field_values.field_id = '".intval($field_identification[0])."' AND
                                field_values.value = '".Database::escape_string($field_identification[1])."'";
                    } else {
                        $sql .= "WHERE cu.user_id IS NULL AND u.status IN ($allowedRoles) AND access_url_id= $url_access_id ";
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
                        u.user_id = cu.user_id AND
                        c_id = $courseId AND
                        session_id = $sessionId ";

            if (api_is_multiple_url_enabled()) {
                $sql .= " INNER JOIN $tbl_url_rel_user as url_rel_user ON (url_rel_user.user_id = u.user_id) ";
            }

            // applying the filter of the additional user profile fields
            if (isset($_GET['subscribe_user_filter_value']) &&
                !empty($_GET['subscribe_user_filter_value'])
            ) {
                $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                $sql .= "
                    LEFT JOIN $table_user_field_values field_values
                        ON field_values.item_id = u.user_id
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
                        u.user_id = cu.user_id AND
                        c_id = $courseId ";

            // applying the filter of the additional user profile fields
            if (isset($_GET['subscribe_user_filter_value']) && !empty($_GET['subscribe_user_filter_value'])) {
                $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                $sql .= "
                    LEFT JOIN $table_user_field_values field_values
                        ON field_values.item_id = u.user_id
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
                if ($url_access_id != -1) {
                    $sql = "SELECT $select_fields
                        FROM $user_table u
                        LEFT JOIN $course_user_table cu
                        ON u.user_id = cu.user_id AND c_id='".$courseId."'
                        INNER JOIN  $tbl_url_rel_user as url_rel_user
                        ON (url_rel_user.user_id = u.user_id) ";

                    // applying the filter of the additional user profile fields
                    if (isset($_GET['subscribe_user_filter_value']) &&
                        !empty($_GET['subscribe_user_filter_value']) &&
                        api_get_setting('ProfilingFilterAddingUsers') == 'true'
                    ) {
                        $field_identification = explode('*', $_GET['subscribe_user_filter_value']);
                        $sql .= "
                            LEFT JOIN $table_user_field_values field_values
                                ON field_values.item_id = u.user_id
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
    if (!empty($_GET['keyword_firstname']) || !empty($_GET['keyword_lastname']) || !empty($_GET['keyword_username']) || !empty($_GET['keyword_email']) || !empty($_GET['keyword_officialcode'])) {
        $condition = '';
        $keywords = [
            'firstname' => Security::remove_XSS(Database::escape_string($_GET['keyword_firstname'])),
            'lastname' => Security::remove_XSS(Database::escape_string($_GET['keyword_lastname'])),
            'username' => Security::remove_XSS(Database::escape_string($_GET['keyword_username'])),
            'email' => Security::remove_XSS(Database::escape_string($_GET['keyword_email'])),
            'official_code' => Security::remove_XSS(Database::escape_string($_GET['keyword_officialcode'])),
        ];

        foreach ($keywords as $keyword => $value) {
            if (!empty($value)) {
                if (!empty($condition)) {
                    $condition .= ' AND ';
                }
                $condition .= "u.".$keyword." LIKE '%".$value."%'";
            }
        }

        if (!empty($condition)) {
            $sql .= ' AND ('.$condition.')';
        }
    } elseif (!empty($_REQUEST['keyword'])) {
        $keyword = Database::escape_string(trim($_REQUEST['keyword']));
        $sql .= " AND (
                    firstname LIKE '%".$keyword."%' OR
                    lastname LIKE '%".$keyword."%' OR
                    email LIKE '%".$keyword."%' OR
                    username LIKE '%".$keyword."%' OR
                    official_code LIKE '%".$keyword."%'
                    )
                ";

        if (api_get_setting('ProfilingFilterAddingUsers') === 'true') {
            // we also want to search for users who have something in
            // their profile fields that matches the keyword
            $additional_users = search_additional_profile_fields($keyword);
        }

        // getting all the users of the course (to make sure that we do not
        // display users that are already in the course)
        if (!empty($sessionId)) {
            $a_course_users = CourseManager::get_user_list_from_course_code($course_code, $sessionId);
        } else {
            $a_course_users = CourseManager::get_user_list_from_course_code($course_code, 0);
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
    return Display::encrypted_mailto_link($email, $email);
}
/**
 * Build the reg-column of the table.
 *
 * @param int $user_id The user id
 *
 * @return string Some HTML-code
 */
function reg_filter($user_id)
{
    if (isset($_REQUEST['type']) && $_REQUEST['type'] == COURSEMANAGER) {
        $type = COURSEMANAGER;
    } else {
        $type = STUDENT;
    }
    $user_id = (int) $user_id;

    $result = '<a class="btn btn-small btn-primary" href="'.api_get_self().'?'.api_get_cidreq().'&register=yes&type='.$type.'&user_id='.$user_id.'">'.
        get_lang("reg").'</a>';

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
    if ($active == '1') {
        $action = 'AccountActive';
        $image = 'accept';
    }

    if ($active == '0') {
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
                user.user_id as col0,
                user.official_code as col1,
                user.lastname as col2,
                user.firstname as col3,
                user.email as col4,
                user.active as col5,
                user.user_id as col6
            FROM $table_user user, $table_user_field_values user_values, $tableExtraField e
            WHERE
                user.user_id = user_values.item_id AND
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
    $return = '<option value="">'.get_lang('SelectFilter').'</option>';

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
                        $_GET['subscribe_user_filter_value'] == $field_details[0].'*'.$option_details[1]
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
