<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Enums\StateIcon;
use Chamilo\CoreBundle\Framework\Container;
use ChamiloSession as Session;

/**
 * @author Bart Mollet
 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2011
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

api_protect_session_admin_list_users();

$urlId = api_get_current_access_url_id();
$currentUserId = api_get_user_id();

$action = $_REQUEST['action'] ?? '';
$view = $_GET['view'] ?? 'all';
$showDeletedUsers = 'deleted' === $view;

// Login as can be used by different roles
if (isset($_GET['user_id']) && 'login_as' === $action) {
    $check = Security::check_token('get');
    if ($check && api_can_login_as($_GET['user_id'])) {
        $result = UserManager::loginAsUser($_GET['user_id']);

        $oldUserInfo = api_get_user_info($_GET['user_id']);
        if ($result) {
            $userId = $oldUserInfo['id'];
            $message = sprintf(
                get_lang('Attempting to login as %s %s (id %s)'),
                $oldUserInfo['complete_name_with_username'],
                '',
                $userId
            );

            $url = api_get_path(WEB_PATH);
            $goTo = sprintf(get_lang('Login successful. Go to %s'), Display::url($url, $url));

            Display::addFlash(Display::return_message($message, 'normal', false));
            Display::addFlash(Display::return_message($goTo, 'normal', false));

            api_location($url.'?_switch_user='.$oldUserInfo['username']);
            exit;
        } else {
            api_not_allowed(true);
        }
    }
    Security::clear_token();
}

api_protect_admin_script(true);
trimVariables();

$url = api_get_path(WEB_AJAX_PATH).'course.ajax.php?a=get_user_courses';
$urlSession = api_get_path(WEB_AJAX_PATH).'session.ajax.php?a=get_user_sessions';
$extraField = new ExtraField('user');
$variables = $extraField->get_all_extra_field_by_type(ExtraField::FIELD_TYPE_TAG);
$variablesSelect = $extraField->get_all_extra_field_by_type(ExtraField::FIELD_TYPE_SELECT);

if (!empty($variablesSelect)) {
    $variables = array_merge($variables, $variablesSelect);
}
$variablesToShow = [];
if ($variables) {
    foreach ($variables as $variableId) {
        $extraFieldInfo = $extraField->get($variableId);
        $variablesToShow[] = $extraFieldInfo['variable'];
    }
}

Session::write('variables_to_show', $variablesToShow);

$htmlHeadXtra[] = '<script>
function active_user(element) {
    var userId = $(element).attr("id").split("_")[1];
    var isActive = $(element).hasClass("mdi-check-circle");
    var newStatus = isActive ? 0 : 1;
    var confirmMessage = isActive ? "'.get_lang('Are you sure you want to lock this user?').'" : "'.get_lang('Are you sure you want to unlock this user?').'";
    if (confirm(confirmMessage)) {
        $.ajax({
            contentType: "application/x-www-form-urlencoded",
            beforeSend: function() {
                $(element).attr("class", "mdi mdi-loading mdi-spin ch-tool-icon");
            },
            type: "GET",
            url: "'.api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=active_user",
            data: "user_id=" + userId + "&status=" + newStatus,
            success: function(data) {
                if (data == 1) {
                    $(element).attr("class", "mdi mdi-check-circle ch-tool-icon");
                    $(element).attr("title", "'.get_lang('Lock').'");
                } else {
                    $(element).attr("class", "mdi mdi-alert ch-tool-icon");
                    $(element).attr("title", "'.get_lang('Unlock').'");
                }
            }
        });
    }
}

function display_advanced_search_form () {
    if ($("#advanced_search_form").css("display") == "none") {
        $("#advanced_search_form").css("display","block");
        $("#img_plus_and_minus").html(\'&nbsp;'.Display::getMdiIcon('arrow-down-bold', 'ch-tool-icon-button', null, 16).' '.get_lang('Advanced search').'\');
    } else {
        $("#advanced_search_form").css("display","none");
        $("#img_plus_and_minus").html(\'&nbsp;'.Display::getMdiIcon('arrow-right-bold', 'ch-tool-icon-button', null, 16).' '.get_lang('Advanced search').'\');
    }
}

$(function () {
    var select_val = $("#input_select_extra_data").val();
    if ( document.getElementById(\'extra_data_text\')) {
        if (select_val != 0) {
            document.getElementById(\'extra_data_text\').style.display="block";
            if (document.getElementById(\'input_extra_text\'))
                document.getElementById(\'input_extra_text\').value = "";
        } else {
            document.getElementById(\'extra_data_text\').style.display="none";
        }
    }
});

//Load user calendar
function load_calendar(user_id, month, year) {
    var url = "'.api_get_path(WEB_AJAX_PATH).'agenda.ajax.php?a=get_user_agenda&user_id=" +user_id + "&month="+month+"&year="+year;
    $(".modal-body").load(url);
}
</script>';

$this_section = SECTION_PLATFORM_ADMIN;

/**
 * Trim variable values to avoid trailing spaces.
 */
function trimVariables()
{
    $filterVariables = [
        'keyword',
        'keyword_firstname',
        'keyword_lastname',
        'keyword_username',
        'keyword_email',
        'keyword_officialcode',
    ];

    foreach ($filterVariables as $variable) {
        if (isset($_GET[$variable])) {
            $_GET[$variable] = trim($_GET[$variable]);
        }
    }
}

/**
 * Prepares a shared SQL query for retrieving user data from the user table.
 * This function is utilized by both get_user_data() and get_number_of_users()
 * to construct the base SQL query. It supports filtering for active, inactive,
 * and deleted users based on the provided parameters.
 */
function prepare_user_sql_query(bool $getCount, bool $showDeletedUsers = false): string
{
    $sql = '';
    $user_table  = Database::get_main_table(TABLE_MAIN_USER);
    $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);

    $isMultipleUrl = (api_is_platform_admin() || api_is_session_admin()) && api_get_multiple_access_url();
    $urlId = api_get_current_access_url_id();

    if ($getCount) {
        $sql .= "SELECT COUNT(u.id) AS total_number_of_items FROM $user_table u";
    } else {
        $sql .= 'SELECT
                    u.id AS col0,
                    u.official_code AS col2, ';

        if (api_is_western_name_order()) {
            $sql .= 'u.firstname AS col3, u.lastname AS col4, ';
        } else {
            $sql .= 'u.lastname AS col3, u.firstname AS col4, ';
        }

        $sql .= " u.username AS col5,
                  u.email AS col6,
                  u.status AS col7,
                  u.active AS col8,
                  u.created_at AS col9,
                  u.last_login AS col10,
                  u.id AS col11,
                  u.expiration_date AS exp,
                  u.password
                 FROM $user_table u";
    }

    if ($isMultipleUrl) {
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $sql .= " INNER JOIN $access_url_rel_user_table url_rel_user
                  ON (u.id = url_rel_user.user_id)";
    }

    $classId = isset($_REQUEST['class_id']) && !empty($_REQUEST['class_id']) ? (int) $_REQUEST['class_id'] : 0;
    if ($classId) {
        $userGroupTable = Database::get_main_table(TABLE_USERGROUP_REL_USER);
        $sql .= " INNER JOIN $userGroupTable ug ON (ug.user_id = u.id)";
    }

    $keywordList = [
        'keyword_firstname',
        'keyword_lastname',
        'keyword_username',
        'keyword_email',
        'keyword_officialcode',
        'keyword_roles',
        'keyword_active',
        'keyword_inactive',
        'check_easy_passwords',
    ];
    $keywordListValues = [];
    $atLeastOne = false;
    foreach ($keywordList as $keyword) {
        $keywordListValues[$keyword] = null;
        if (isset($_GET[$keyword]) && $_GET[$keyword] !== '') {
            $keywordListValues[$keyword] = Security::remove_XSS($_GET[$keyword]);
            $atLeastOne = true;
        }
    }
    if (!$atLeastOne) {
        $keywordListValues = [];
    }

    $roles = [];
    if (!empty($keywordListValues['keyword_roles'])) {
        $raw = $keywordListValues['keyword_roles'];
        $roles = is_array($raw) ? $raw : array_filter(array_map('trim', explode(',', (string) $raw)));
        $available = array_keys(api_get_roles());
        if (empty($available)) { $available = array_values(api_get_roles()); }
        $roles = array_values(array_unique(array_intersect($roles, $available)));
    }

    $roleToStatus = [
        'ROLE_TEACHER' => COURSEMANAGER,
        'TEACHER'      => COURSEMANAGER,
        'ROLE_STUDENT' => STUDENT,
        'STUDENT'      => STUDENT,
        'ROLE_HR'      => DRH,
        'HR'           => DRH,
        'ROLE_SESSION_MANAGER' => SESSIONADMIN,
        'SESSION_MANAGER'      => SESSIONADMIN,
        'ROLE_STUDENT_BOSS' => STUDENT_BOSS,
        'STUDENT_BOSS'      => STUDENT_BOSS,
        'ROLE_INVITEE'     => INVITEE,
        'INVITEE'          => INVITEE,
    ];
    $mappedStatuses = [];
    foreach ($roles as $r) {
        if (isset($roleToStatus[$r])) {
            $mappedStatuses[] = (int) $roleToStatus[$r];
        }
    }
    $mappedStatuses = array_values(array_unique($mappedStatuses));

    $adminVariants = ['ROLE_PLATFORM_ADMIN','PLATFORM_ADMIN','ROLE_GLOBAL_ADMIN','GLOBAL_ADMIN','ROLE_ADMIN','ADMIN'];
    $needsAdminLeftJoin = (bool) array_intersect($roles, $adminVariants);
    if ($needsAdminLeftJoin) {
        $sql .= " LEFT JOIN $admin_table a ON (a.user_id = u.id) ";
    }

    if (isset($_GET['keyword']) && $_GET['keyword'] !== '') {
        $keywordFiltered = Database::escape_string("%".$_GET['keyword']."%");
        $sql .= " WHERE (
            u.firstname LIKE '$keywordFiltered' OR
            u.lastname LIKE '$keywordFiltered' OR
            concat(u.firstname, ' ', u.lastname) LIKE '$keywordFiltered' OR
            concat(u.lastname, ' ', u.firstname) LIKE '$keywordFiltered' OR
            u.username LIKE '$keywordFiltered' OR
            u.official_code LIKE '$keywordFiltered' OR
            u.email LIKE '$keywordFiltered'
        )";
    } elseif (!empty($keywordListValues)) {
        $sql .= " WHERE (1 = 1 ";

        if (!empty($keywordListValues['keyword_firstname'])) {
            $sql .= " AND u.firstname LIKE '".Database::escape_string("%".$keywordListValues['keyword_firstname']."%")."'";
        }
        if (!empty($keywordListValues['keyword_lastname'])) {
            $sql .= " AND u.lastname LIKE '".Database::escape_string("%".$keywordListValues['keyword_lastname']."%")."'";
        }
        if (!empty($keywordListValues['keyword_username'])) {
            $sql .= " AND u.username LIKE '".Database::escape_string("%".$keywordListValues['keyword_username']."%")."'";
        }
        if (!empty($keywordListValues['keyword_email'])) {
            $sql .= " AND u.email LIKE '".Database::escape_string("%".$keywordListValues['keyword_email']."%")."'";
        }
        if (!empty($keywordListValues['keyword_officialcode'])) {
            $sql .= " AND u.official_code LIKE '".Database::escape_string("%".$keywordListValues['keyword_officialcode']."%")."'";
        }

        if (!empty($roles)) {
            $roleConds = [];

            foreach ($roles as $role) {
                $u = strtoupper($role);
                $variants = [$u];
                if (strpos($u, 'ROLE_') === 0) {
                    $variants[] = substr($u, 5);
                } else {
                    $variants[] = 'ROLE_'.$u;
                }
                $variants = array_values(array_unique($variants));

                $likes = [];
                foreach ($variants as $v) {
                    $esc = Database::escape_string($v);
                    $likes[] = "u.roles LIKE '%\"$esc\"%'";
                }
                $roleConds[] = '('.implode(' OR ', $likes).')';
            }

            if (!empty($mappedStatuses)) {
                $roleConds[] = 'u.status IN ('.implode(',', array_map('intval', $mappedStatuses)).')';
            }

            if ($needsAdminLeftJoin) {
                $roleConds[] = 'a.user_id IS NOT NULL';
            }

            $sql .= ' AND ('.implode(' OR ', $roleConds).')';
        }


        if (isset($keywordListValues['keyword_active']) && !isset($keywordListValues['keyword_inactive'])) {
            $sql .= ' AND u.active = 1';
        } elseif (isset($keywordListValues['keyword_inactive']) && !isset($keywordListValues['keyword_active'])) {
            $sql .= ' AND u.active = 0';
        }

        $sql .= ' )';
    }

    if ($classId) {
        $sql .= " AND ug.usergroup_id = $classId";
    }

    if (api_is_session_admin() && api_get_setting('prevent_session_admins_to_manage_all_users') === 'true') {
        $sql .= ' AND u.creator_id = '.api_get_user_id();
    }

    if ($isMultipleUrl) {
        $sql .= ' AND url_rel_user.access_url_id = '.$urlId;
    }

    $variables = Session::read('variables_to_show', []);
    $extraFields = api_get_setting('profile.user_search_on_extra_fields', true);

    if (!empty($extraFields) && isset($extraFields['extra_fields']) && isset($_GET['keyword'])) {
        $extraFieldList = $extraFields['extra_fields'];
        if (!empty($extraFieldList)) {
            foreach ($extraFieldList as $variable) {
                $_GET['extra_'.$variable] = Security::remove_XSS($_GET['keyword']);
            }
        }
        $variables = array_merge($extraFieldList, $variables);
    }

    if (!empty($variables)) {
        $extraField = new ExtraField('user');
        $extraFieldResult = [];
        $extraFieldHasData = [];

        foreach ($variables as $variable) {
            if (isset($_GET['extra_'.$variable])) {
                $values = is_array($_GET['extra_'.$variable])
                    ? $_GET['extra_'.$variable]
                    : [$_GET['extra_'.$variable]];

                if (empty($values)) {
                    continue;
                }

                $info = $extraField->get_handler_field_info_by_field_variable($variable);
                if (empty($info)) {
                    continue;
                }

                foreach ($values as $value) {
                    if ($value === '' || $value === null) {
                        continue;
                    }
                    if (ExtraField::FIELD_TYPE_TAG === $info['value_type']) {
                        $result = $extraField->getAllUserPerTag($info['id'], $value);
                        $result = empty($result) ? [] : array_column($result, 'user_id');
                    } else {
                        $result = UserManager::get_extra_user_data_by_value($variable, $value, true);
                    }

                    $extraFieldHasData[] = true;
                    if (!empty($result)) {
                        $extraFieldResult = array_merge($extraFieldResult, $result);
                    }
                }
            }
        }

        $condition = isset($_GET['keyword']) ? ' OR ' : ' AND ';
        if (!empty($extraFieldHasData) && !empty($extraFieldResult)) {
            $sql .= " $condition u.id IN ('".implode("','", $extraFieldResult)."')";
        }
    }

    if ($showDeletedUsers) {
        $sql .= (!str_contains($sql, 'WHERE') ? ' WHERE ' : ' AND ').'u.active = '.USER_SOFT_DELETED;
    } else {
        $sql .= (!str_contains($sql, 'WHERE') ? ' WHERE ' : ' AND ').'u.active <> '.USER_SOFT_DELETED;
    }

    $sql .= ' AND u.status <> '.User::ROLE_FALLBACK;

    return $sql;
}

/**
 * Retrieves the total number of users from the database. This function can be
 * configured to either include or exclude users marked as deleted based on the
 * provided parameter.
 */
function get_number_of_users(bool $showDeletedUsers = false): int
{
    $sql = prepare_user_sql_query(true, $showDeletedUsers);
    $res = Database::query($sql);
    $obj = Database::fetch_object($res);

    return $obj->total_number_of_items;
}

/**
 * Fetches a subset of user data from the database based on provided parameters. This function
 * is used to populate a sortable and paginated table of users, allowing for dynamic data retrieval
 * based on user interaction with the table (such as sorting and pagination).
 */
function get_user_data(int $from, int $number_of_items, int $column, string $direction, bool $showDeletedUsers = false): array
{
    $sql = prepare_user_sql_query(false, $showDeletedUsers);
    if (!in_array($direction, ['ASC', 'DESC'])) {
        $direction = 'ASC';
    }

    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from, $number_of_items";

    $res = Database::query($sql);

    $users = [];
    $t = time();
    while ($user = Database::fetch_array($res)) {
        $userPicture = UserManager::getUserPicture(
            $user[0],
            USER_IMAGE_SIZE_SMALL
        );
        $photo = '<img
            src="'.$userPicture.'"
            alt="'.api_get_person_name($user[2], $user[3]).'"
            title="'.api_get_person_name($user[2], $user[3]).'" />';

        if (1 == $user[7] && !empty($user['exp'])) {
            $expiration_time = api_strtotime($user['exp']);
            if ($expiration_time < $t) {
                $user[7] = '-1';
            }
        }

        // forget about the expiration date field
        $users[] = [
            $user[0],
            $photo,
            $user[1],
            $user[2],
            $user[3],
            $user[4],
            $user[5],
            $user[0],
            $user[7],
            api_get_local_time($user[8]),
            api_get_local_time($user[9], null, null, true),
            $user[0],
        ];
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
    return Display::getMdiIcon('email', null, null, null, $email).
        ' '.
        Display::encrypted_mailto_link($email, cut($email, 10), 'small clickable_email_link');
}

/**
 * Returns a mailto-link.
 *
 * @param string $name
 * @param array  $params Deprecated
 * @param array  $row
 *
 * @return string HTML-code with a mailto-link
 */
function user_filter($name, $params, $row)
{
    $name = cut($name, 26);

    return '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$row[0].'">'.$name.'</a>';
}

/**
 * Generates action links for each user in the deleted users list. This function creates HTML
 * links for editing user profiles, restoring deleted users, and permanently deleting users.
 * It is designed to be used in a table that lists users marked as deleted.
 */
function modify_deleted_filter(int $user_id, string $url_params, array $row): string
{

    $result = '';
    $editProfileUrl = Display::getProfileEditionLink($user_id, true);
    $result .= '<a href="'.$editProfileUrl.'">'.
        Display::getMdiIcon('pencil', 'ch-tool-icon', null, 22, get_lang('Edit')).'</a>';

    $restoreUrl = "user_list.php?$url_params&".
        http_build_query([
            'action' => 'restore',
            'user_id' => $user_id,
            'sec_token' => Security::getTokenFromSession(),
            'view' => Security::remove_XSS($_GET['view']),
        ]);
    $result .= Display::url(
        Display::getMdiIcon('restore', 'ch-tool-icon', null, 22, get_lang('Restore')),
        $restoreUrl,
        [
            'title' => get_lang('Restore'),
            'data-title' => addslashes(api_htmlentities(get_lang("Please confirm your choice"))),
            'data-confirm-text' => get_lang('Yes'),
            'data-cancel-text' => get_lang('Cancel'),
            'class' => 'delete-swal',
        ]
    );

    $deleteUrl = "user_list.php?$url_params&".
        http_build_query([
            'action' => 'destroy',
            'user_id' => $user_id,
            'sec_token' => Security::getTokenFromSession(),
            'view' => Security::remove_XSS($_GET['view']),
        ]);
    $result .= Display::url(
        Display::getMdiIcon('delete-forever', 'ch-tool-icon', null, 22, get_lang('Delete permanently')),
        $deleteUrl,
        [
            'title' => get_lang('Delete permanently'),
            'data-title' => addslashes(api_htmlentities(get_lang("Please confirm your choice"))),
            'data-confirm-text' => get_lang('Yes'),
            'data-cancel-text' => get_lang('Cancel'),
            'class' => 'delete-swal',
        ]
    );

    return $result;
}

/**
 * Build the modify-column of the table.
 *
 * @param   int $user_id The user id
 * @param   string $url_params URL params to add to table links
 * @param   array $row Row of elements to alter
 *
 * @throws Exception
 *
 * @return string Some HTML-code with modify-buttons
 */
function modify_filter($user_id, $url_params, $row): string
{
    $_admins_list = Session::read('admin_list', []);
    $repo = Container::getUserRepository();
    $userEntity = $repo->find($user_id);
    $userRoles = $userEntity ? $userEntity->getRoles() : [];

    $isAdminByRole = in_array('ROLE_PLATFORM_ADMIN', $userRoles, true)
        || in_array('ROLE_GLOBAL_ADMIN', $userRoles, true)
        || in_array('ROLE_ADMIN', $userRoles, true);

    $is_admin = $isAdminByRole
        || UserManager::is_admin($user_id)
        || in_array($user_id, $_admins_list, true);

    $repo = Container::getUserRepository();
    $userEntity = $repo->find($user_id);
    $userRoles = $userEntity ? $userEntity->getRoles() : [];

    $currentUserId = api_get_user_id();

    $user_is_anonymous = in_array('ROLE_ANONYMOUS', $userRoles, true);
    $current_user_status_label = $row['7'];
    $result = '';

    if (api_is_platform_admin()) {
        if (!$user_is_anonymous) {
            $result .= '<a href="user_information.php?user_id='.$user_id.'">'.
                Display::getMdiIcon('information', 'ch-tool-icon', null, 22, get_lang('Information')).
                '</a>';
        } else {
            $result .= Display::getMdiIcon('information', 'ch-tool-icon-disabled', null, 22, get_lang('Information'));
        }
    }

    $loginAsRolesForSessionAdmins = ['ROLE_STUDENT'];

    if ('true' === api_get_setting('session.allow_session_admin_login_as_teacher')) {
        $loginAsRolesForSessionAdmins[] = 'ROLE_TEACHER';
    }

    $sessionAdminCanLoginAs = false;
    if (api_is_session_admin()) {
        foreach ($loginAsRolesForSessionAdmins as $role) {
            if (in_array($role, $userRoles, true)) {
                $sessionAdminCanLoginAs = true;
                break;
            }
        }
    }

    if (api_is_platform_admin() || $sessionAdminCanLoginAs) {
        if (!$user_is_anonymous) {
            if (api_global_admin_can_edit_admin($user_id, null, $sessionAdminCanLoginAs)) {
                $result .= '<a href="user_list.php?action=login_as&user_id='.$user_id.'&sec_token='.Security::getTokenFromSession().'">'.
                    Display::getMdiIcon('account-key', 'ch-tool-icon', null, 22, get_lang('Login as')).'</a>';
            } else {
                $result .= Display::getMdiIcon('account-key', 'ch-tool-icon-disabled', null, 22, get_lang('Login as'));
            }
        } else {
            $result .= Display::getMdiIcon('account-key', 'ch-tool-icon-disabled', null, 22, get_lang('Login as'));
        }
    } else {
        $result .= Display::getMdiIcon('account-key', 'ch-tool-icon-disabled', null, 22, get_lang('Login as'));
    }

    if (!in_array('ROLE_STUDENT', $userRoles, true)) {
        $result .= Display::getMdiIcon(
            'chart-box',
            'ch-tool-icon-disabled',
            null,
            22,
            get_lang('Reporting')
        );
    } else {
        $result .= '<a href="../my_space/myStudents.php?student='.$user_id.'">'.
            Display::getMdiIcon(
                'chart-box',
                'ch-tool-icon',
                null,
                22,
                get_lang('Reporting')
            ).'</a>';
    }

    if (api_is_platform_admin(true)) {
        $editProfileUrl = Display::getProfileEditionLink($user_id, true);
        if (!$user_is_anonymous &&
            api_global_admin_can_edit_admin($user_id, null, true)
        ) {
            $result .= '<a href="'.$editProfileUrl.'">'.
                Display::getMdiIcon(
                    'pencil',
                    'ch-tool-icon',
                    null,
                    22,
                    get_lang('Edit')
                ).
                '</a>';
        } else {
            $result .= Display::getMdiIcon(
                    'pencil',
                    'ch-tool-icon-disabled',
                    null,
                    22,
                    get_lang('Edit')
                ).'</a>';
        }
    }

    $allowAssignSkill = api_is_platform_admin(false, true);

    if ($allowAssignSkill) {
        $result .= Display::url(
            Display::getMdiIcon(
                'shield-star',
                'ch-tool-icon',
                null,
                22,
                get_lang('Assign skill')
            ),
            api_get_path(WEB_CODE_PATH).'skills/assign.php?'.http_build_query(['user' => $user_id])
        );
    }

    if ($is_admin) {
        $result .= Display::getMdiIcon(
            'star',
            'ch-tool-icon',
            null,
            22,
            get_lang('Is administrator')
        );
    } else {
        $result .= Display::getMdiIcon(
            'star',
            'ch-tool-icon-disabled',
            null,
            22,
            get_lang('Is not administrator')
        );
    }

    if (api_is_platform_admin()) {
        if ($user_id != $currentUserId &&
            !$user_is_anonymous &&
            api_global_admin_can_edit_admin($user_id)
        ) {
            $anonymizeUrl = "user_list.php?$url_params&"
                .http_build_query(
                    [
                        'action' => 'anonymize',
                        'user_id' => $user_id,
                        'sec_token' => Security::getTokenFromSession(),
                    ]
                );
            $result .= Display::url(
                Display::getMdiIcon(
                    'incognito',
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_SMALL,
                    get_lang('Anonymize')
                ),
                $anonymizeUrl,
                [
                    'data-title' => addslashes(api_htmlentities(get_lang("Please confirm your choice"))),
                    'class' => 'delete-swal',
                    'data-confirm-text' => get_lang('Yes'),
                    'data-cancel-text' => get_lang('Cancel'),
                    'title' => get_lang('Anonymize'),
                ]
            );
        }

        $denyDeleteUsers = api_get_env_variable('DENY_DELETE_USERS', false);
        if (!$denyDeleteUsers) {
            if ($user_id != $currentUserId &&
                !$user_is_anonymous &&
                api_global_admin_can_edit_admin($user_id)
            ) {
                // you cannot lock yourself out otherwise you could disable all the accounts
                // including your own => everybody is locked out and nobody can change it anymore.
                $deleteUrl = "user_list.php?$url_params&"
                    .http_build_query(
                        [
                            'action' => 'delete_user',
                            'user_id' => $user_id,
                            'sec_token' => Security::getTokenFromSession(),
                        ]
                    );
                $result .= Display::url(
                    Display::getMdiIcon(
                        'delete',
                        'ch-tool-icon',
                        null,
                        22,
                        get_lang('Delete')
                    ),
                    $deleteUrl,
                    [
                        'data-title' => addslashes(api_htmlentities(get_lang("Please confirm your choice"))),
                        'title' => get_lang('Delete'),
                        'data-confirm-text' => get_lang('Yes'),
                        'data-cancel-text' => get_lang('Cancel'),
                        'class' => 'delete-swal',
                    ]
                );
            } else {
                $result .= Display::getMdiIcon(
                    'delete',
                    'ch-tool-icon-disabled',
                    null,
                    22,
                    get_lang('Delete')
                );
            }
        }
    }

    $allowDelete = ('true' === api_get_setting('session.allow_delete_user_for_session_admin'));

    if (api_is_session_admin() && $allowDelete) {
        if ($user_id != $currentUserId &&
            !$user_is_anonymous &&
            api_global_admin_can_edit_admin($user_id, null, true)
        ) {
            // you cannot lock yourself out otherwise you could disable all the accounts including your own => everybody is locked out and nobody can change it anymore.
            $deleteUrl = "user_list.php?$url_params&"
                .http_build_query(
                    [
                        'action' => 'delete_user',
                        'user_id' => $user_id,
                        'sec_token' => Security::getTokenFromSession(),
                    ]
                );
            $result .= Display::url(
                Display::getMdiIcon(
                    'delete',
                    'ch-tool-icon',
                    null,
                    ICON_SIZE_SMALL,
                    get_lang('Delete')
                ),
                $deleteUrl,
                [
                    'data-title' => addslashes(api_htmlentities(get_lang("Please confirm your choice"))),
                    'title' => get_lang('Delete'),
                    'data-confirm-text' => get_lang('Yes'),
                    'data-cancel-text' => get_lang('Cancel'),
                    'class' => 'delete-swal',
                ]
            );
        }
    }

    // actions for assigning sessions, courses or users
    if (!api_is_session_admin()) {
        $isSessionManager = in_array('ROLE_SESSION_MANAGER', $userRoles, true);
        $isHR = in_array('ROLE_HR', $userRoles, true);
        $isStudentBoss = in_array('ROLE_STUDENT_BOSS', $userRoles, true);
        $isAdmin = UserManager::is_admin($user_id);

        if ($isSessionManager) {
            $result .= Display::url(
                Display::getMdiIcon(
                    'google-classroom',
                    'ch-tool-icon',
                    null,
                    22,
                    get_lang('Assign sessions')
                ),
                "dashboard_add_sessions_to_user.php?user={$user_id}"
            );
        } else {
            if ($isHR || $isAdmin || $isStudentBoss) {
                $result .= Display::url(
                    Display::getMdiIcon(
                        'account-child',
                        'ch-tool-icon',
                        null,
                        22,
                        get_lang('Assign users')
                    ),
                    "dashboard_add_users_to_user.php?user={$user_id}",
                    ['title' => get_lang('Assign users')]
                );
            }

            if ($isHR || $isAdmin) {
                $result .= Display::url(
                    Display::getMdiIcon(
                        'book-open-page-variant',
                        'ch-tool-icon',
                        null,
                        22,
                        get_lang('Assign courses')
                    ),
                    "dashboard_add_courses_to_user.php?user={$user_id}",
                    ['title' => get_lang('Assign courses')]
                );

                $result .= Display::url(
                    Display::getMdiIcon(
                        'google-classroom',
                        'ch-tool-icon',
                        null,
                        22,
                        get_lang('Assign sessions')
                    ),
                    "dashboard_add_sessions_to_user.php?user={$user_id}",
                    ['title' => get_lang('Assign sessions')]
                );
            }
        }
    }

    return $result;
}

/**
 * Build the active-column of the table to lock or unlock a certain user
 * lock = the user can no longer use this account.
 */
function active_filter(int $active, string $params, array $row): string
{
    $_user = api_get_user_info();

    $action = 'Unlock';
    $image = StateIcon::WARNING;
    if (USER_ACTIVE == $active) {
        $action = 'Lock';
        $image = StateIcon::COMPLETE;
    } elseif (USER_INACTIVE_AUTOMATIC == $active) {
        $action = 'edit';
        $image = StateIcon::EXPIRED;
    } elseif (USER_SOFT_DELETED == $active) {
        $action = 'soft_deleted';
        $image = StateIcon::REJECT;
    }

    $result = '';

    if (in_array($action, ['edit', 'soft_deleted'])) {
        $result = Display::getMdiIcon(
            $image,
            'ch-tool-icon',
            null,
            ICON_SIZE_TINY,
            'edit' === $action ? get_lang('Account expired') : get_lang('The account has been removed temporarily.')
        );
    } elseif ($row['0'] != $_user['user_id']) {
        // you cannot lock yourself out otherwise you could disable all the
        // accounts including your own => everybody is locked out and nobody
        // can change it anymore.
        $result = Display::getMdiIcon(
            $image,
            'ch-tool-icon',
            null,
            ICON_SIZE_TINY,
            get_lang(ucfirst($action)),
            ['onclick' => 'active_user(this);', 'id' => 'img_'.$row['0']]
        );
    }

    return $result;
}

/**
 * Returns a list of user roles excluding the default ROLE_USER.
 *
 * @param int $userId The user ID.
 * @return string HTML string with roles separated by <br>.
 */
function roles_filter($userId): string
{
    static $map = null;
    if ($map === null) {
        $map = api_get_roles();
    }

    $repo = Container::getUserRepository();
    $user = $repo->find($userId);
    if (!$user) {
        return '';
    }

    $codes = array_filter($user->getRoles(), static function ($code) {
        $u = strtoupper($code);
        return $u !== 'ROLE_USER' && $u !== 'USER' && $u !== 'ROLE_ANONYMOUS' && $u !== 'ANONYMOUS';
    });

    $labels = array_map(static function ($code) use ($map) {
        $u = strtoupper($code);
        $bare = preg_replace('/^ROLE_/', '', $u);
        $label = $map[$u] ?? $map[$bare] ?? ucwords(strtolower(str_replace('_', ' ', $bare)));
        return htmlentities($label);
    }, $codes);

    return implode('<br>', $labels);
}


if (isset($_GET['keyword']) || isset($_GET['keyword_firstname'])) {
    $interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
    $interbreadcrumb[] = ['url' => 'user_list.php', 'name' => get_lang('User list')];
    $tool_name = get_lang('Search users');
} else {
    $interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
    $tool_name = get_lang('User list');
}

$message = '';

if (!empty($action)) {
    $check = Security::check_token('get');
    if ($check) {
        switch ($action) {
            case 'add_user_to_my_url':
                $user_id = $_REQUEST['user_id'];
                $result = UrlManager::add_user_to_url($user_id, $urlId);
                if ($result) {
                    $user_info = api_get_user_info($user_id);
                    $message = get_lang('The user has been added').' '.$user_info['complete_name_with_username'];
                    $message = Display::return_message($message, 'confirmation');
                }
                break;
            case 'delete_user':
                $message = UserManager::deleteUserWithVerification((int) $_GET['user_id']);
                Display::addFlash($message);
                header('Location: '.api_get_self());
                exit;
                break;
            case 'delete':
                if (api_is_platform_admin() && !empty($_POST['id'])) {
                    $number_of_selected_users = count($_POST['id']);
                    $number_of_affected_users = 0;
                    if (is_array($_POST['id'])) {
                        foreach ($_POST['id'] as $index => $user_id) {
                            if ($user_id != $currentUserId) {
                                if (UserManager::delete_user($user_id)) {
                                    $number_of_affected_users++;
                                }
                            }
                        }
                    }
                    if ($number_of_selected_users == $number_of_affected_users) {
                        $message = Display::return_message(
                            get_lang('Selected users deleted'),
                            'confirmation'
                        );
                    } else {
                        $message = Display::return_message(
                            get_lang('Some of the selected users have not been deleted. We recommend you confirm which, by using the advanced search.'),
                            'error'
                        );
                    }
                }
                break;
            case 'disable':
                if (api_is_platform_admin()) {
                    $number_of_selected_users = count($_POST['id']);
                    $number_of_affected_users = 0;
                    if (is_array($_POST['id'])) {
                        foreach ($_POST['id'] as $index => $user_id) {
                            if ($user_id != $currentUserId) {
                                if (UserManager::disable($user_id)) {
                                    $number_of_affected_users++;
                                }
                            }
                        }
                    }
                    if ($number_of_selected_users == $number_of_affected_users) {
                        $message = Display::return_message(
                            get_lang('The selected users have all been disabled'),
                            'confirmation'
                        );
                    } else {
                        $message = Display::return_message(
                            get_lang('Some of the selected users have not been disabled. We recommend you confirm which, by using the advanced search.'),
                            'error'
                        );
                    }
                }
                break;
            case 'enable':
                if (api_is_platform_admin()) {
                    $number_of_selected_users = count($_POST['id']);
                    $number_of_affected_users = 0;
                    if (is_array($_POST['id'])) {
                        foreach ($_POST['id'] as $index => $user_id) {
                            if ($user_id != $currentUserId) {
                                if (UserManager::enable($user_id)) {
                                    $number_of_affected_users++;
                                }
                            }
                        }
                    }
                    if ($number_of_selected_users == $number_of_affected_users) {
                        $message = Display::return_message(
                            get_lang('The selected users were all enabled.'),
                            'confirmation'
                        );
                    } else {
                        $message = Display::return_message(
                            get_lang('Some of the selected users have not been enabled. We recommend you confirm which, by using the advanced search.'),
                            'error'
                        );
                    }
                }
                break;
            case 'restore':
                $userIds = [];
                $isMassDeletion = false;
                if (api_is_platform_admin() && !empty($_POST['id']) && is_array($_POST['id'])) {
                    $userIds = $_POST['id'];
                    $isMassDeletion = true;
                } elseif (!empty($_GET['user_id'])) {
                    $userIds[] = $_GET['user_id'];
                }

                $numberOfAffectedUsers = 0;
                foreach ($userIds as $userId) {
                    if ($userId != $currentUserId && UserManager::change_active_state($userId, 1)) {
                        $numberOfAffectedUsers++;
                    }
                }

                if ($isMassDeletion) {
                    if (count($userIds) == $numberOfAffectedUsers) {
                        $message = Display::return_message(
                            get_lang('Selected users restored.'),
                            'confirmation'
                        );
                    } else {
                        $message = Display::return_message(
                            get_lang('Some of the selected users have not been restored. We recommend you confirm which, by using the advanced search.'),
                            'error'
                        );
                    }
                } else {
                    if ($numberOfAffectedUsers > 0) {
                        $message = Display::return_message(
                            get_lang('The user has been restored.'),
                            'confirmation'
                        );
                    } else {
                        $message = Display::return_message(
                            get_lang('The user has not been restored. Please confirm the user ID and try again.'),
                            'error'
                        );
                    }
                }

                Display::addFlash($message);

                if (!$isMassDeletion) {
                    header('Location: '.api_get_self().'?view='.$view);
                    exit;
                }
                break;
            case 'destroy':
                $userIds = [];
                $isMassDeletion = false;
                if (api_is_platform_admin() && !empty($_POST['id']) && is_array($_POST['id'])) {
                    $userIds = $_POST['id'];
                    $isMassDeletion = true;
                } elseif (!empty($_GET['user_id'])) {
                    $userIds[] = $_GET['user_id'];
                }

                $numberOfAffectedUsers = 0;
                foreach ($userIds as $userId) {
                    if ($userId != $currentUserId && UserManager::delete_user($userId, true)) {
                        $numberOfAffectedUsers++;
                    }
                }

                if ($isMassDeletion) {
                    if (count($userIds) == $numberOfAffectedUsers) {
                        $message = Display::return_message(
                            get_lang('Selected users deleted permanently'),
                            'confirmation'
                        );
                    } else {
                        $message = Display::return_message(
                            get_lang('Some of the selected users have not been deleted. We recommend you confirm which, by using the advanced search.'),
                            'error'
                        );
                    }
                } else {
                    if ($numberOfAffectedUsers > 0) {
                        $message = Display::return_message(
                            get_lang('The user has been deleted permanently.'),
                            'confirmation'
                        );
                    } else {
                        $message = Display::return_message(
                            get_lang('The user has not been deleted. Please confirm the user ID and try again.'),
                            'error'
                        );
                    }
                }

                Display::addFlash($message);

                if (!$isMassDeletion) {
                    header('Location: '.api_get_self().'?view='.$view);
                    exit;
                }
                break;
            case 'anonymize':
                $message = UserManager::anonymizeUserWithVerification($_GET['user_id']);
                Display::addFlash($message);
                header('Location: '.api_get_self());
                exit;
                break;
        }
        Security::clear_token();
    }
}

// Create a search-box
$form = new FormValidator('search_simple', 'get', null, null, [], FormValidator::LAYOUT_BOX_SEARCH);
$form->addText(
    'keyword',
    null,
    false,
    [
        'placeholder' => get_lang('Search users'),
    ]
);
$form->addElement('hidden', 'view', $view);
$form->addButtonSearch(get_lang('Search'));

$searchAdvanced = '
<a id="advanced_params" href="javascript://"
    class="btn btn--plain advanced_options" onclick="display_advanced_search_form();">
    <span id="img_plus_and_minus">&nbsp;
    '.Display::getMdiIcon('arrow-right-bold', 'ch-tool-icon-button', null, 16).' '.get_lang('Advanced search').'
    </span>
</a>';
$actionsLeft = '';
$actionsCenter = '';
$actionsRight = '';
if (api_is_platform_admin() && !$showDeletedUsers) {
    $actionsLeft .= '<a href="'.api_get_path(WEB_CODE_PATH).'admin/user_add.php">'.
        Display::getMdiIcon('account-plus', 'ch-tool-icon-gradient', null, 32, get_lang('Add a user')).'</a>';
}

$actionsRight .= $form->returnForm();
$actionsCenter .= $searchAdvanced;

if (isset($_GET['keyword'])) {
    $parameters = ['keyword' => Security::remove_XSS($_GET['keyword'])];
} elseif (isset($_GET['keyword_firstname'])) {
    $parameters['keyword_firstname'] = Security::remove_XSS($_GET['keyword_firstname']);
    $parameters['keyword_lastname'] = Security::remove_XSS($_GET['keyword_lastname']);
    $parameters['keyword_username'] = Security::remove_XSS($_GET['keyword_username']);
    $parameters['keyword_email'] = Security::remove_XSS($_GET['keyword_email']);
    $parameters['keyword_officialcode'] = Security::remove_XSS($_GET['keyword_officialcode']);
    if (isset($_GET['keyword_roles'])) {
        $keywordRoles = is_array($_GET['keyword_roles']) ? $_GET['keyword_roles'] : [$_GET['keyword_roles']];
        $parameters['keyword_roles'] = implode(',', array_map('Security::remove_XSS', $keywordRoles));
    }
    if (isset($_GET['keyword_active'])) {
        $parameters['keyword_active'] = Security::remove_XSS($_GET['keyword_active']);
    }
    if (isset($_GET['keyword_inactive'])) {
        $parameters['keyword_inactive'] = Security::remove_XSS($_GET['keyword_inactive']);
    }
}
// Create a sortable table with user-data
$parameters['sec_token'] = Security::get_token();
$parameters['view'] = $view;

$_admins_list = array_keys(UserManager::get_all_administrators());
Session::write('admin_list', $_admins_list);
// Display Advanced search form.
$form = new FormValidator(
    'advanced_search',
    'get',
    '',
    '',
    [],
    FormValidator::LAYOUT_HORIZONTAL
);
$form->addElement('hidden', 'view', $view);
$form->addElement('header', get_lang('Advanced search'));
$form->addText('keyword_firstname', get_lang('First name'), false);
$form->addText('keyword_lastname', get_lang('Last name'), false);
$form->addText('keyword_username', get_lang('Login'), false);
$form->addText('keyword_email', get_lang('E-mail'), false);
$form->addText('keyword_officialcode', get_lang('Code'), false);

$classId = isset($_REQUEST['class_id']) && !empty($_REQUEST['class_id']) ? (int) $_REQUEST['class_id'] : 0;
$options = [];
if ($classId) {
    $userGroup = new UserGroupModel();
    $groupInfo = $userGroup->get($classId);
    if ($groupInfo) {
        $options = [$classId => $groupInfo['name']];
    }
}
$form->addSelectAjax(
    'class_id',
    get_lang('Social group').' / '.get_lang('Class'),
    $options,
    ['url' => api_get_path(WEB_AJAX_PATH).'usergroup.ajax.php?a=get_class_by_keyword']
);

$form->addSelect(
    'keyword_roles',
    get_lang('Roles'),
    api_get_roles(),
    ['multiple' => true, 'size' => 6]
);

$active_group = [];
$active_group[] = $form->createElement('checkbox', 'keyword_active', '', get_lang('Active'));
$active_group[] = $form->createElement('checkbox', 'keyword_inactive', '', get_lang('Inactive'));
$form->addGroup($active_group, '', get_lang('Account'), null, false);
$form->addElement('checkbox', 'check_easy_passwords', null, get_lang('Check passwords too easy to guess'));
$data = $extraField->addElements($form, 0, [], true, false, $variablesToShow);

$htmlHeadXtra[] = '
    <script>
    $(function () {
        '.$data['jquery_ready_content'].'
    })
    </script>
';

$form->addButtonSearch(get_lang('Search users'));

$defaults = [];
$defaults['keyword_active'] = 1;
$defaults['keyword_inactive'] = 1;
$form->setDefaults($defaults);

$form = '<div id="advanced_search_form" style="display:none;">'.$form->returnForm().'</div>';

$table = new SortableTable(
    'users',
    function() use ($showDeletedUsers) { return get_number_of_users($showDeletedUsers); },
    function($from, $number_of_items, $column, $direction) use ($showDeletedUsers) { return get_user_data($from, $number_of_items, $column, $direction, $showDeletedUsers); },
    (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2,
    20,
    'ASC'
);
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false, 'width="18px"');
$table->set_header(1, get_lang('Photo'), false);
$table->set_header(2, get_lang('Official code'));

if (api_is_western_name_order()) {
    $table->set_header(3, get_lang('First name'));
    $table->set_header(4, get_lang('Last name'));
} else {
    $table->set_header(3, get_lang('Last name'));
    $table->set_header(4, get_lang('First name'));
}
$table->set_header(5, get_lang('Username'));
$table->set_header(6, get_lang('E-mail'));
$table->set_header(7, get_lang('Roles'));
$table->set_header(8, get_lang('active'), true, 'width="15px"');
$table->set_header(9, get_lang('Registration date'), true, 'width="90px"');
$table->set_header(10, get_lang('Latest login'), true, 'width="90px"');
$table->set_header(11, get_lang('Action'), false, 'width="220px"');

$table->set_column_filter(3, 'user_filter');
$table->set_column_filter(4, 'user_filter');
$table->set_column_filter(6, 'email_filter');
$table->set_column_filter(7, 'roles_filter');
$table->set_column_filter(8, 'active_filter');
$actionsList = [];
if ($showDeletedUsers) {
    $table->set_column_filter(11, 'modify_deleted_filter');
    $actionsList['restore'] = get_lang('Restore');
    if (api_is_platform_admin() &&
        !api_get_env_variable('DENY_DELETE_USERS', false)
    ) {
        $actionsList['destroy'] = get_lang('Delete permanently');
    }
} else {
    $table->set_column_filter(11, 'modify_filter');
    if (api_is_platform_admin() &&
        !api_get_env_variable('DENY_DELETE_USERS', false)
    ) {
        $actionsList['delete'] = get_lang('Remove from portal');
    }
    $actionsList['disable'] = get_lang('Disable');
    $actionsList['enable'] = get_lang('Enable');
}
$table->set_form_actions($actionsList);

// Hide email column if login is email, to avoid column with same data
if ('true' === api_get_setting('login_is_email')) {
    $table->setHideColumn(6);
}

$table_result = $table->return_table();
$extra_search_options = '';

// Try to search the user everywhere
if (0 == $table->get_total_number_of_items()) {
    if (api_get_multiple_access_url() && isset($_REQUEST['keyword'])) {
        $keyword = Database::escape_string($_REQUEST['keyword']);
        $conditions = ['username' => $keyword];
        $user_list = UserManager::get_user_list(
            $conditions,
            [],
            false,
            ' OR '
        );
        if (!empty($user_list)) {
            $extra_search_options = Display::page_subheader(get_lang('Users found in other portals'));

            $table = new HTML_Table(['class' => 'table table-hover table-striped data_table']);
            $column = 0;
            $row = 0;
            $headers = [get_lang('User'), 'URL', get_lang('Detail')];
            foreach ($headers as $header) {
                $table->setHeaderContents($row, $column, $header);
                $column++;
            }
            $row++;

            foreach ($user_list as $user) {
                $column = 0;
                $access_info = UrlManager::get_access_url_from_user($user['id']);
                $access_info_to_string = '';
                $add_user = true;
                if (!empty($access_info)) {
                    foreach ($access_info as $url_info) {
                        if ($urlId == $url_info['access_url_id']) {
                            $add_user = false;
                        }
                        $access_info_to_string .= $url_info['url'].'<br />';
                    }
                }
                if ($add_user) {
                    $row_table = [];
                    $row_table[] = api_get_person_name($user['firstname'], $user['lastname']).' ('.$user['username'].') ';
                    $row_table[] = $access_info_to_string;
                    $url = api_get_self().'?action=add_user_to_my_url&user_id='.$user['id'].'&sec_token='.Security::getTokenFromSession();
                    $row_table[] = Display::url(
                        get_lang('Add user to my portal'),
                        $url,
                        ['class' => 'btn']
                    );

                    foreach ($row_table as $cell) {
                        $table->setCellContents($row, $column, $cell);
                        $table->updateCellAttributes(
                            $row,
                            $column,
                            'align="center"'
                        );
                        $column++;
                    }
                    $row++;
                }
            }
            $extra_search_options .= $table->toHtml();
            $table_result = '';
        }
    }
}

$toolbarActions = Display::tabsOnlyLink(
    [
        'all' => [
            'url' => 'user_list.php?view=all',
            'content' => get_lang('All users'),
        ],
        'deleted' => [
            'url' => 'user_list.php?view=deleted',
            'content' => get_lang('Deleted users'),
        ],
    ],
    $view,
    'users-list'
);
$toolbarActions .= Display::toolbarAction('toolbarUser', [$actionsLeft, $actionsCenter.$actionsRight]);

$tpl = new Template($tool_name);
$tpl->assign('actions', $toolbarActions);
$tpl->assign('message', $message);
$tpl->assign('content', $form.$table_result.$extra_search_options);
$tpl->display_one_col_template();
