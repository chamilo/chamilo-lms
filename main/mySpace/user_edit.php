<?php
/* For licensing terms, see /license.txt */

// including necessary libraries
$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';

// user permissions
api_block_anonymous_users();

if (!api_is_platform_admin()) {
    if (!api_is_drh()) {
        api_not_allowed(true);
    }
} else {
    api_protect_admin_script();
}

$userId = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : '';

$userInfo = api_get_user_info($userId);
if (empty($userInfo)) {
    api_not_allowed(true);
}

$userIsFollowed = UserManager::is_user_followed_by_drh($userId, api_get_user_id());

if (api_drh_can_access_all_session_content()) {
    $students = SessionManager::getAllUsersFromCoursesFromAllSessionFromStatus(
        'drh_all',
        api_get_user_id(),
        false,
        0, //$from,
        null, //$limit,
        null, //$column,
        'desc', //$direction,
        null, //$keyword,
        null, //$active,
        null, //$lastConnectionDate,
        null,
        null,
        STUDENT
    );

    if (empty($students)) {
        api_not_allowed(true);
    }
    $userIdList = [];
    foreach ($students as $student) {
        $userIdList[] = $student['user_id'];
    }

    if (!in_array($userId, $userIdList)) {
        api_not_allowed(true);
    }
} else {
    if (!api_is_platform_admin() && !$userIsFollowed) {
        api_not_allowed(true);
    }
}

$url = api_get_self().'?user_id='.$userId;
$tool_name = get_lang('ModifyUserInfo');
// Create the form
$form = new FormValidator('user_edit', 'post', $url);
// Username
$usernameInput = $form->addElement('text', 'username', get_lang('LoginName'));
$usernameInput->freeze();

// Password
$group = [];
$auth_sources = 0; //make available wider as we need it in case of form reset (see below)
$group[] = &$form->createElement('radio', 'password_auto', get_lang('Password'), get_lang('AutoGeneratePassword').'<br />', 1);
$group[] = &$form->createElement('radio', 'password_auto', 'id="radio_user_password"', null, 0);
$group[] = &$form->createElement('password', 'password', null, ['onkeydown' => 'javascript: password_switch_radio_button(document.user_add,"password[password_auto]");']);
$form->addGroup($group, 'password', get_lang('Password'));

// Send email
$group = [];
$group[] = &$form->createElement('radio', 'send_mail', null, get_lang('Yes'), 1);
$group[] = &$form->createElement('radio', 'send_mail', null, get_lang('No'), 0);
$form->addGroup($group, 'mail', get_lang('SendMailToNewUser'));

// Set default values
$defaults = [];
$defaults['username'] = $userInfo['username'];
$defaults['mail']['send_mail'] = 0;
$defaults['password']['password_auto'] = 1;

$form->setDefaults($defaults);
// Submit button
$select_level = [];
$html_results_enabled[] = $form->addButtonUpdate(get_lang('Update'), 'submit', true);
$form->addGroup($html_results_enabled);
// Validate form
if ($form->validate()) {
    $check = Security::check_token('post');
    if ($check) {
        $user = $form->exportValues();
        $email = $userInfo['email'];
        $username = $userInfo['username'];
        $send_mail = intval($user['mail']['send_mail']);
        $auth_source = PLATFORM_AUTH_SOURCE;
        $resetPassword = $user['password']['password_auto'] == '1' ? 0 : 2;
        $auth_source = $userInfo['auth_source'];
        $password = $user['password']['password_auto'] == '1' ? api_generate_password() : $user['password']['password'];

        UserManager::update_user(
            $userId,
            $userInfo['firstname'],
            $userInfo['lastname'],
            $userInfo['username'],
            $password,
            $auth_source,
            $userInfo['email'],
            $userInfo['status'],
            $userInfo['official_code'],
            $userInfo['phone'],
            $userInfo['picture_uri'],
            $userInfo['expiration_date'],
            $userInfo['active'],
            $userInfo['creator_id'],
            $userInfo['hr_dept_id'],
            null, //$extra =
            $userInfo['language'],
            null, //$encrypt_method
            false,
            $resetPassword
        );

        if (!empty($email) && $send_mail) {
            $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
            $portal_url = api_get_path(WEB_PATH);
            if (api_is_multiple_url_enabled()) {
                $access_url_id = api_get_current_access_url_id();
                if ($access_url_id != -1) {
                    $url = api_get_access_url($access_url_id);
                    $portal_url = $url['url'];
                }
            }

            $emailbody = get_lang('Dear')." ".stripslashes(api_get_person_name($userInfo['firstname'], $userInfo['lastname'])).",\n\n".
                get_lang('YouAreReg')." ".api_get_setting('siteName')." ".get_lang('WithTheFollowingSettings')."\n\n".
                get_lang('Username')." : ".$username."\n".get_lang('Pass')." : ".stripslashes($password)."\n\n".
                get_lang('Address')." ".api_get_setting('siteName')." ".
                get_lang('Is')." : ".$portal_url."\n\n".
                get_lang('Problem')."\n\n".
                get_lang('SignatureFormula').",\n\n".
                api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n".
                get_lang('Manager')." ".
                api_get_setting('siteName')."\nT. ".
                api_get_setting('administratorTelephone')."\n".
                get_lang('Email')." : ".api_get_setting('emailAdministrator');
            $emailbody = nl2br($emailbody);

            MessageManager::send_message_simple($userInfo['user_id'], $emailsubject, $emailbody);
        }

        Security::clear_token();
        $tok = Security::get_token();
        header('Location: '.$url.'&message=1');
        exit();
    }
} else {
    if (isset($_POST['submit'])) {
        Security::clear_token();
    }
    $token = Security::get_token();
    $form->addElement('hidden', 'sec_token');
    $form->setConstants(['sec_token' => $token]);
}

$interbreadcrumb[] = [
    'url' => api_get_path(WEB_CODE_PATH)."mySpace/student.php",
    "name" => get_lang('UserList'),
];

if (isset($_REQUEST['message'])) {
    Display::addFlash(Display::return_message(get_lang('Updated'), 'normal'));
}

Display::display_header($tool_name);
// Display form
$form->display();

Display::display_footer();
