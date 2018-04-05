<?php
/* For licensing terms, see /license.txt */
/**
 * @author Mustapha Alouani
 *
 * @package chamilo.admin
 */
$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';
require '../auth/ldap/authldap.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$action = @$_GET["action"] ?: null;
$login_as_user_id = @$_GET["user_id"] ?: null;

// Login as ...
if ($action == "login_as" && !empty($login_as_user_id)) {
    login_user($login_as_user_id);
}

//if we already have a session id and a user...
/*
if (($_GET['action']=="add_user") && ($_GET['id_session'] == strval(intval($_GET['id_session']))) && $_GET['id_session']>0 ){
    header('Location: ldap_import_students_to_session.php?id_session='.$_GET['id_session'].'&ldap_user='.$_GET['id']);
}
*/

$interbreadcrumb[] = ["url" => 'index.php', "name" => get_lang('PlatformAdmin')];
$tool_name = get_lang('SearchLDAPUsers');
//Display::display_header($tool_name); //cannot display now as we need to redirect
//api_display_tool_title($tool_name);

if (isset($_GET['action'])) {
    $check = Security::check_token('get');
    if ($check) {
        switch ($_GET['action']) {
            case 'show_message':
                Display::addFlash(Display::return_message($_GET['message'], 'normal'));
                Display::display_header($tool_name);
                break;
            case 'delete_user':
                if ($user_id != $_user['user_id'] && UserManager::delete_user($_GET['user_id'])) {
                    Display::addFlash(Display::return_message(get_lang('UserDeleted'), 'normal'));
                } else {
                    Display::addFlash(Display::return_message(get_lang('CannotDeleteUser'), 'error'));
                }
                Display::display_header($tool_name);
                break;
            case 'lock':
                $message = lock_unlock_user('lock', $_GET['user_id']);
                Display::addFlash(Display::return_message($message, 'normal'));
                Display::display_header($tool_name);
                break;
            case 'unlock':
                $message = lock_unlock_user('unlock', $_GET['user_id']);
                Display::addFlash(Display::return_message($message, 'normal'));
                Display::display_header($tool_name);
                break;
            case 'add_user':
                $id = $_GET['id'];
                $UserList = [];
                $userid_match_login = [];
                foreach ($id as $user_id) {
                    $tmp = ldap_add_user($user_id);
                    $UserList[] = $tmp;
                    $userid_match_login[$tmp] = $user_id;
                }
                if (isset($_GET['id_session']) && ($_GET['id_session'] == strval(intval($_GET['id_session']))) && ($_GET['id_session'] > 0)) {
                    ldap_add_user_to_session($UserList, $_GET['id_session']);
                    header('Location: resume_session.php?id_session='.intval($_GET['id_session']));
                } else {
                    if (count($userid_match_login) > 0) {
                        $message = get_lang('LDAPUsersAddedOrUpdated').':<br />';
                        foreach ($userid_match_login as $user_id => $login) {
                            $message .= '- '.$login.'<br />';
                        }
                    } else {
                        $message = get_lang('NoUserAdded');
                    }
                    Display::addFlash(Display::return_message($message, 'normal', false));
                    Display::display_header($tool_name);
                }
                break;
            default:
                Display::display_header($tool_name);
        }
        Security::clear_token();
    } else {
        Display::display_header($tool_name);
    }
} else {
    Display::display_header($tool_name);
}

if (isset($_POST['action'])) {
    $check = Security::check_token('get');
    if ($check) {
        switch ($_POST['action']) {
            case 'delete':
                $number_of_selected_users = count($_POST['id']);
                $number_of_deleted_users = 0;
                foreach ($_POST['id'] as $index => $user_id) {
                    if ($user_id != $_user['user_id']) {
                        if (UserManager::delete_user($user_id)) {
                            $number_of_deleted_users++;
                        }
                    }
                }
                if ($number_of_selected_users == $number_of_deleted_users) {
                    echo Display::return_message(get_lang('SelectedUsersDeleted'), 'normal');
                } else {
                    echo Display::return_message(get_lang('SomeUsersNotDeleted'), 'error');
                }
                break;
            case 'add_user':
                $number_of_selected_users = count($_POST['id']);
                $number_of_added_users = 0;
                $UserList = [];
                foreach ($_POST['id'] as $index => $user_id) {
                    if ($user_id != $_user['user_id']) {
                        $UserList[] = ldap_add_user($user_id);
                    }
                }
                if (isset($_GET['id_session']) && (trim($_GET['id_session']) != "")) {
                    addUserToSession($UserList, $_GET['id_session']);
                }
                if (count($UserList) > 0) {
                    echo Display::return_message(
                        count($UserList)." ".get_lang('LDAPUsersAdded')
                    );
                } else {
                    echo Display::return_message(get_lang('NoUserAdded'));
                }
                break;
        }
        Security::clear_token();
    }
}

$form = new FormValidator('advanced_search', 'get');
$form->addText('keyword_username', get_lang('LoginName'), false);
if (api_is_western_name_order()) {
    $form->addText('keyword_firstname', get_lang('FirstName'), false);
    $form->addText('keyword_lastname', get_lang('LastName'), false);
} else {
    $form->addText('keyword_lastname', get_lang('LastName'), false);
    $form->addText('keyword_firstname', get_lang('FirstName'), false);
}
if (isset($_GET['id_session'])) {
    $form->addElement('hidden', 'id_session', $_GET['id_session']);
}

$type = [];
$type["all"] = get_lang('All');
$type["employee"] = get_lang('Teacher');
$type["student"] = get_lang('Student');

$form->addElement('select', 'keyword_type', get_lang('Status'), $type);
// Structure a rajouer ??
$form->addElement('submit', 'submit', get_lang('Ok'));
//$defaults['keyword_active'] = 1;
//$defaults['keyword_inactive'] = 1;
//$form->setDefaults($defaults);
$form->display();
$parameters['keyword_username'] = @$_GET['keyword_username'] ?: null;
$parameters['keyword_firstname'] = @$_GET['keyword_firstname'] ?: null;
$parameters['keyword_lastname'] = @$_GET['keyword_lastname'] ?: null;
$parameters['keyword_email'] = @$_GET['keyword_email'] ?: null;
if (isset($_GET['id_session'])) {
    $parameters['id_session'] = $_GET['id_session'];
}
// Create a sortable table with user-data

$parameters['sec_token'] = Security::get_token();
$table = new SortableTable(
    'users',
    'ldap_get_number_of_users',
    'ldap_get_user_data',
    (api_is_western_name_order() xor api_sort_by_first_name()) ? 3 : 2
);
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false);
$table->set_header(1, get_lang('LoginName'));
if (api_is_western_name_order()) {
    $table->set_header(2, get_lang('FirstName'));
    $table->set_header(3, get_lang('LastName'));
} else {
    $table->set_header(2, get_lang('LastName'));
    $table->set_header(3, get_lang('FirstName'));
}
$table->set_header(4, get_lang('Email'));
$table->set_header(5, get_lang('Actions'));
//$table->set_column_filter(5, 'email_filter');
//$table->set_column_filter(5, 'active_filter');
$table->set_column_filter(5, 'modify_filter');
$table->set_form_actions(['add_user' => get_lang('AddLDAPUsers')]);
$table->display();

Display::display_footer();
