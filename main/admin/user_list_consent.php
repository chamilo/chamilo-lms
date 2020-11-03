<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

/**
 * @author Bart Mollet
 * @author Julio Montoya <gugli100@gmail.com> BeezNest 2011
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$urlId = api_get_current_access_url_id();
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

api_protect_admin_script();

$this_section = SECTION_PLATFORM_ADMIN;

$extraFields = UserManager::createDataPrivacyExtraFields();
Session::write('data_privacy_extra_fields', $extraFields);

/**
 * Prepares the shared SQL query for the user table.
 * See get_user_data() and get_number_of_users().
 *
 * @param bool $getCount Whether to count, or get data
 *
 * @return string SQL query
 */
function prepare_user_sql_query($getCount)
{
    $sql = '';
    $user_table = Database::get_main_table(TABLE_MAIN_USER);
    $admin_table = Database::get_main_table(TABLE_MAIN_ADMIN);

    if ($getCount) {
        $sql .= "SELECT COUNT(u.id) AS total_number_of_items FROM $user_table u";
    } else {
        $sql .= 'SELECT u.id AS col0, u.official_code AS col2, ';
        if (api_is_western_name_order()) {
            $sql .= 'u.firstname AS col3, u.lastname AS col4, ';
        } else {
            $sql .= 'u.lastname AS col3, u.firstname AS col4, ';
        }

        $sql .= " u.username AS col5,
                    u.email AS col6,
                    u.status AS col7,
                    u.active AS col8,
                    u.id AS col9,
                    u.registration_date AS col10,
                    u.expiration_date AS exp,
                    u.password,
                    v.field_id,
                    v.updated_at
                FROM $user_table u";
    }

    // adding the filter to see the user's only of the current access_url
    if ((api_is_platform_admin() || api_is_session_admin()) && api_get_multiple_access_url()) {
        $access_url_rel_user_table = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $sql .= " INNER JOIN $access_url_rel_user_table url_rel_user
                  ON (u.id=url_rel_user.user_id)";
    }

    $extraFields = Session::read('data_privacy_extra_fields');
    $extraFieldId = $extraFields['delete_legal'];
    $extraFieldIdDeleteAccount = $extraFields['delete_account_extra_field'];

    $extraFieldValue = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
    $sql .= " INNER JOIN $extraFieldValue v
              ON (
                    u.id = v.item_id AND
                    (field_id = $extraFieldId OR field_id = $extraFieldIdDeleteAccount) AND
                    v.value = 1
              ) ";

    $keywordList = [
        'keyword_firstname',
        'keyword_lastname',
        'keyword_username',
        'keyword_email',
        'keyword_officialcode',
        'keyword_status',
        'keyword_active',
        'keyword_inactive',
        'check_easy_passwords',
    ];

    $keywordListValues = [];
    $atLeastOne = false;
    foreach ($keywordList as $keyword) {
        $keywordListValues[$keyword] = null;
        if (isset($_GET[$keyword]) && !empty($_GET[$keyword])) {
            $keywordListValues[$keyword] = $_GET[$keyword];
            $atLeastOne = true;
        }
    }

    if (false == $atLeastOne) {
        $keywordListValues = [];
    }

    if (isset($_GET['keyword']) && !empty($_GET['keyword'])) {
        $keywordFiltered = Database::escape_string("%".$_GET['keyword']."%");
        $sql .= " WHERE (
                    u.firstname LIKE '$keywordFiltered' OR
                    u.lastname LIKE '$keywordFiltered' OR
                    concat(u.firstname, ' ', u.lastname) LIKE '$keywordFiltered' OR
                    concat(u.lastname,' ',u.firstname) LIKE '$keywordFiltered' OR
                    u.username LIKE '$keywordFiltered' OR
                    u.official_code LIKE '$keywordFiltered' OR
                    u.email LIKE '$keywordFiltered'
                )
        ";
    } elseif (isset($keywordListValues) && !empty($keywordListValues)) {
        $query_admin_table = '';
        $keyword_admin = '';

        if (isset($keywordListValues['keyword_status']) &&
            $keywordListValues['keyword_status'] == PLATFORM_ADMIN
        ) {
            $query_admin_table = " , $admin_table a ";
            $keyword_admin = ' AND a.user_id = u.id ';
            $keywordListValues['keyword_status'] = '%';
        }

        $keyword_extra_value = '';

        $sql .= " $query_admin_table
            WHERE (
                u.firstname LIKE '".Database::escape_string("%".$keywordListValues['keyword_firstname']."%")."' AND
                u.lastname LIKE '".Database::escape_string("%".$keywordListValues['keyword_lastname']."%")."' AND
                u.username LIKE '".Database::escape_string("%".$keywordListValues['keyword_username']."%")."' AND
                u.email LIKE '".Database::escape_string("%".$keywordListValues['keyword_email']."%")."' AND
                u.status LIKE '".Database::escape_string($keywordListValues['keyword_status'])."' ";
        if (!empty($keywordListValues['keyword_officialcode'])) {
            $sql .= " AND u.official_code LIKE '".Database::escape_string("%".$keywordListValues['keyword_officialcode']."%")."' ";
        }

        $sql .= "
            $keyword_admin
            $keyword_extra_value
        ";

        if (isset($keywordListValues['keyword_active']) &&
            !isset($keywordListValues['keyword_inactive'])
        ) {
            $sql .= " AND u.active = 1";
        } elseif (isset($keywordListValues['keyword_inactive']) &&
            !isset($keywordListValues['keyword_active'])
        ) {
            $sql .= " AND u.active = 0";
        }
        $sql .= " ) ";
    }

    $preventSessionAdminsToManageAllUsers = api_get_setting('prevent_session_admins_to_manage_all_users');
    if (api_is_session_admin() && $preventSessionAdminsToManageAllUsers === 'true') {
        $sql .= " AND u.creator_id = ".api_get_user_id();
    }

    // adding the filter to see the user's only of the current access_url
    if ((api_is_platform_admin() || api_is_session_admin()) &&
        api_get_multiple_access_url()
    ) {
        $sql .= " AND url_rel_user.access_url_id = ".api_get_current_access_url_id();
    }

    return $sql;
}

/**
 * Get the total number of users on the platform.
 *
 * @see SortableTable#get_total_number_of_items()
 */
function get_number_of_users()
{
    $sql = prepare_user_sql_query(true);
    $res = Database::query($sql);
    $obj = Database::fetch_object($res);

    return $obj->total_number_of_items;
}

/**
 * Get the users to display on the current page (fill the sortable-table).
 *
 * @param   int     offset of first user to recover
 * @param   int     Number of users to get
 * @param   int     Column to sort on
 * @param   string  Order (ASC,DESC)
 *
 * @return array Users list
 *
 * @see SortableTable#get_table_data($from)
 */
function get_user_data($from, $number_of_items, $column, $direction)
{
    $sql = prepare_user_sql_query(false);
    if (!in_array($direction, ['ASC', 'DESC'])) {
        $direction = 'ASC';
    }
    $column = (int) $column;
    $from = (int) $from;
    $number_of_items = (int) $number_of_items;
    $sql .= " ORDER BY col$column $direction ";
    $sql .= " LIMIT $from,$number_of_items";

    $res = Database::query($sql);

    $users = [];
    $t = time();
    while ($user = Database::fetch_row($res)) {
        $userPicture = UserManager::getUserPicture(
            $user[0],
            USER_IMAGE_SIZE_SMALL
        );
        $photo = '<img
            src="'.$userPicture.'" width="22" height="22"
            alt="'.api_get_person_name($user[2], $user[3]).'"
            title="'.api_get_person_name($user[2], $user[3]).'" />';

        if (1 == $user[7] && !empty($user[10])) {
            // check expiration date
            $expiration_time = convert_sql_date($user[10]);
            // if expiration date is passed, store a special value for active field
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
            $user[6],
            $user[7],
            api_get_local_time($user[9]),
            $user[12],
            Display::dateToStringAgoAndLongDate($user[13]),
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
    return Display::encrypted_mailto_link($email, $email);
}

/**
 * Returns a mailto-link.
 *
 * @param string $name   An email-address
 * @param array  $params Deprecated
 * @param array  $row
 *
 * @return string HTML-code with a mailto-link
 */
function user_filter($name, $params, $row)
{
    return '<a href="'.api_get_path(WEB_PATH).'whoisonline.php?origin=user_list&id='.$row[0].'">'.$name.'</a>';
}

function requestTypeFilter($fieldId, $url_params, $row)
{
    $extraFields = Session::read('data_privacy_extra_fields');
    $extraFieldId = $extraFields['delete_legal'];

    if ($fieldId == $extraFieldId) {
        return get_lang('DeleteLegal');
    } else {
        return get_lang('DeleteAccount');
    }
}

/**
 * Build the modify-column of the table.
 *
 * @param   int     The user id
 * @param   string  URL params to add to table links
 * @param   array   Row of elements to alter
 *
 * @throws Exception
 *
 * @return string Some HTML-code with modify-buttons
 */
function modify_filter($user_id, $url_params, $row)
{
    $_admins_list = Session::read('admin_list', []);
    $is_admin = in_array($user_id, $_admins_list);
    $token = Security::getTokenFromSession();
    $result = '';
    $result .= '<a href="user_information.php?user_id='.$user_id.'">'.
        Display::return_icon('info2.png', get_lang('Info')).'</a>&nbsp;&nbsp;';

    $result .= Display::url(
        Display::return_icon('message_new.png', get_lang('SendMessage')),
        api_get_path(WEB_CODE_PATH).'messages/new_message.php?send_to_user='.$user_id
    );
    $result .= '&nbsp;&nbsp;';
    $extraFields = Session::read('data_privacy_extra_fields');
    $extraFieldId = $extraFields['delete_legal'];

    if ($row[10] == $extraFieldId) {
        $result .= Display::url(
            Display::return_icon('delete_terms.png', get_lang('RemoveTerms')),
            api_get_self().'?user_id='.$user_id.'&action=delete_terms&sec_token='.$token
        );
        $result .= '&nbsp;&nbsp;';
    }

    if ($user_id != api_get_user_id()) {
        $result .= ' <a href="'.api_get_self().'?action=anonymize&user_id='.$user_id.'&'.$url_params.'&sec_token='.$token.'"  onclick="javascript:if(!confirm('."'".addslashes(
                api_htmlentities(get_lang('ConfirmYourChoice'))
            )."'".')) return false;">'.
            Display::return_icon(
                'anonymous.png',
                get_lang('Anonymize'),
                [],
                ICON_SIZE_SMALL
            ).
            '</a>';

        $result .= ' <a href="'.api_get_self().'?action=delete_user&user_id='.$user_id.'&'.$url_params.'&sec_token='.$token.'"  onclick="javascript:if(!confirm('."'".addslashes(
            api_htmlentities(get_lang('ConfirmYourChoice'))
        )."'".')) return false;">'.
        Display::return_icon(
            'delete.png',
            get_lang('Delete'),
            [],
            ICON_SIZE_SMALL
        ).
        '</a>';
    }

    $editProfileUrl = Display::getProfileEditionLink($user_id, true);

    $result .= '<a href="'.$editProfileUrl.'">'.
        Display::return_icon(
            'edit.png',
            get_lang('Edit'),
            [],
            ICON_SIZE_SMALL
        ).
        '</a>&nbsp;';

    if ($is_admin) {
        $result .= Display::return_icon(
            'admin_star.png',
            get_lang('IsAdministrator'),
            ['width' => ICON_SIZE_SMALL, 'heigth' => ICON_SIZE_SMALL]
        );
    } else {
        $result .= Display::return_icon(
            'admin_star_na.png',
            get_lang('IsNotAdministrator')
        );
    }

    return $result;
}

/**
 * Build the active-column of the table to lock or unlock a certain user
 * lock = the user can no longer use this account.
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University
 *
 * @param int    $active the current state of the account
 * @param string $params
 * @param array  $row
 *
 * @return string Some HTML-code with the lock/unlock button
 */
function active_filter($active, $params, $row)
{
    $_user = api_get_user_info();

    if ($active == '1') {
        $action = 'Lock';
        $image = 'accept';
    } elseif ($active == '-1') {
        $action = 'edit';
        $image = 'warning';
    } elseif ($active == '0') {
        $action = 'Unlock';
        $image = 'error';
    }

    $result = '';

    if ($action === 'edit') {
        $result = Display::return_icon(
            $image.'.png',
            get_lang('AccountExpired'),
            [],
            16
        );
    } elseif ($row['0'] != $_user['user_id']) {
        // you cannot lock yourself out otherwise you could disable all the
        // accounts including your own => everybody is locked out and nobody
        // can change it anymore.
        $result = Display::return_icon(
            $image.'.png',
            get_lang(ucfirst($action)),
            ['onclick' => 'active_user(this);', 'id' => 'img_'.$row['0']],
            16
        );
    }

    return $result;
}

/**
 * Instead of displaying the integer of the status, we give a translation for the status.
 *
 * @param int $status
 *
 * @return string translation
 *
 * @version march 2008
 *
 * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University, Belgium
 */
function status_filter($status)
{
    $statusname = api_get_status_langvars();

    return $statusname[$status];
}

if (isset($_GET['keyword']) || isset($_GET['keyword_firstname'])) {
    $interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
    $interbreadcrumb[] = ['url' => 'user_list_consent.php', 'name' => get_lang('UserList')];
    $tool_name = get_lang('SearchUsers');
} else {
    $interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
    $tool_name = get_lang('UserList');
}

$message = '';

if (!empty($action)) {
    $check = Security::check_token('get');
    if ($check) {
        switch ($action) {
            case 'delete_terms':
                UserManager::cleanUserRequestsOfRemoval($_GET['user_id']);

                Display::addFlash(Display::return_message(get_lang('Deleted')));
                header('Location: '.api_get_self());
                exit;

                break;
            case 'delete_user':
                $message = UserManager::deleteUserWithVerification($_GET['user_id']);
                Display::addFlash($message);
                header('Location: '.api_get_self());
                exit;
                break;
            case 'delete':
                if (api_is_platform_admin()) {
                    $number_of_selected_users = count($_POST['id']);
                    $number_of_affected_users = 0;
                    if (is_array($_POST['id'])) {
                        foreach ($_POST['id'] as $index => $user_id) {
                            if ($user_id != $_user['user_id']) {
                                if (UserManager::delete_user($user_id)) {
                                    $number_of_affected_users++;
                                }
                            }
                        }
                    }
                    if ($number_of_selected_users == $number_of_affected_users) {
                        $message = Display::return_message(
                            get_lang('SelectedUsersDeleted'),
                            'confirmation'
                        );
                    } else {
                        $message = Display::return_message(
                            get_lang('SomeUsersNotDeleted'),
                            'error'
                        );
                    }
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
$form = new FormValidator('search_simple', 'get', null, null, null, 'inline');
$form->addText(
    'keyword',
    get_lang('Search'),
    false,
    [
        'aria-label' => get_lang('SearchUsers'),
    ]
);
$form->addButtonSearch(get_lang('Search'));

$actionsLeft = '';
$actionsCenter = '';
$actionsRight = '';
$actionsLeft .= $form->returnForm();

if (isset($_GET['keyword'])) {
    $parameters = ['keyword' => Security::remove_XSS($_GET['keyword'])];
} elseif (isset($_GET['keyword_firstname'])) {
    $parameters['keyword_firstname'] = Security::remove_XSS($_GET['keyword_firstname']);
    $parameters['keyword_lastname'] = Security::remove_XSS($_GET['keyword_lastname']);
    $parameters['keyword_username'] = Security::remove_XSS($_GET['keyword_username']);
    $parameters['keyword_email'] = Security::remove_XSS($_GET['keyword_email']);
    $parameters['keyword_officialcode'] = Security::remove_XSS($_GET['keyword_officialcode']);
    $parameters['keyword_status'] = Security::remove_XSS($_GET['keyword_status']);
    $parameters['keyword_active'] = Security::remove_XSS($_GET['keyword_active']);
    $parameters['keyword_inactive'] = Security::remove_XSS($_GET['keyword_inactive']);
}
// Create a sortable table with user-data
$parameters['sec_token'] = Security::get_token();

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

$form->addElement('html', '<div id="advanced_search_form" style="display:none;">');
$form->addElement('header', get_lang('AdvancedSearch'));
$form->addText('keyword_firstname', get_lang('FirstName'), false);
$form->addText('keyword_lastname', get_lang('LastName'), false);
$form->addText('keyword_username', get_lang('LoginName'), false);
$form->addText('keyword_email', get_lang('Email'), false);
$form->addText('keyword_officialcode', get_lang('OfficialCode'), false);

$status_options = [];
$status_options['%'] = get_lang('All');
$status_options[STUDENT] = get_lang('Student');
$status_options[COURSEMANAGER] = get_lang('Teacher');
$status_options[DRH] = get_lang('Drh');
$status_options[SESSIONADMIN] = get_lang('SessionsAdmin');
$status_options[PLATFORM_ADMIN] = get_lang('Administrator');

$form->addElement(
    'select',
    'keyword_status',
    get_lang('Profile'),
    $status_options
);
$form->addButtonSearch(get_lang('SearchUsers'));

$defaults = [];
$defaults['keyword_active'] = 1;
$defaults['keyword_inactive'] = 1;
$form->setDefaults($defaults);
$form->addElement('html', '</div>');

$form = $form->returnForm();

$table = new SortableTable(
    'users',
    'get_number_of_users',
    'get_user_data',
    (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2
);
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false, 'width="18px"');
$table->set_header(1, get_lang('Photo'), false);
$table->set_header(2, get_lang('OfficialCode'));

if (api_is_western_name_order()) {
    $table->set_header(3, get_lang('FirstName'));
    $table->set_header(4, get_lang('LastName'));
} else {
    $table->set_header(3, get_lang('LastName'));
    $table->set_header(4, get_lang('FirstName'));
}
$table->set_header(5, get_lang('LoginName'));
$table->set_header(6, get_lang('Email'));
$table->set_header(7, get_lang('Profile'));
$table->set_header(8, get_lang('Active'));
$table->set_header(9, get_lang('RegistrationDate'));
$table->set_header(10, get_lang('RequestType'));
$table->set_header(11, get_lang('RequestDate'));
$table->set_header(12, get_lang('Action'), false);

$table->set_column_filter(3, 'user_filter');
$table->set_column_filter(4, 'user_filter');
$table->set_column_filter(6, 'email_filter');
$table->set_column_filter(7, 'status_filter');
$table->set_column_filter(8, 'active_filter');
$table->set_column_filter(12, 'modify_filter');
$table->set_column_filter(10, 'requestTypeFilter');

// Only show empty actions bar if delete users has been blocked
$actionsList = [];
if (api_is_platform_admin() &&
    !api_get_configuration_value('deny_delete_users')
) {
    $actionsList['delete'] = get_lang('DeleteFromPlatform');
}

$table->set_form_actions($actionsList);

$table_result = $table->return_table();
$extra_search_options = '';
$toolbarActions = Display::toolbarAction(
    'toolbarUser',
    [$actionsLeft, $actionsCenter, $actionsRight],
    [4, 4, 4]
);

$noticeMessage = sprintf(
    get_lang('InformationRightToBeForgottenLinkX'),
    '<a href="https://gdpr-info.eu/art-17-gdpr/">https://gdpr-info.eu/art-17-gdpr/</a>'
);
$notice = Display::return_message($noticeMessage, 'normal', false);

$tpl = new Template($tool_name);
$tpl->assign('actions', $toolbarActions);
$tpl->assign('message', $message);
$tpl->assign('content', $form.$table_result.$extra_search_options.$notice);
$tpl->display_one_col_template();
