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
$tool_name = get_lang('Edit user information');

// Create the form
$form = new FormValidator('user_edit', 'post', $url);

// Username
$usernameInput = $form->addElement('text', 'username', get_lang('Login'));
$usernameInput->freeze();

// Password
$group = [];
$auth_sources = 0; // make available wider as we need it in case of form reset (see below)
$group[] = &$form->createElement(
    'radio',
    'password_auto',
    get_lang('Password'),
    get_lang('Automatically generate a new password').'<br />',
    1
);
$group[] = &$form->createElement(
    'radio',
    'password_auto',
    'id="radio_user_password"',
    null,
    0
);
$group[] = &$form->createElement(
    'password',
    'password',
    null,
    [
        'onkeydown' => 'javascript: password_switch_radio_button(document.user_add,"password[password_auto]");',
    ]
);
$form->addGroup($group, 'password', get_lang('Password'));

// Send email
$group = [];
$group[] = &$form->createElement('radio', 'send_mail', null, get_lang('Yes'), 1);
$group[] = &$form->createElement('radio', 'send_mail', null, get_lang('No'), 0);
$form->addGroup($group, 'mail', get_lang('Send mail to new user'));

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
        $send_mail = (int) $user['mail']['send_mail'];
        $resetPassword = '1' == $user['password']['password_auto'] ? 0 : 2;
        $auth_sources = $userInfo['auth_sources'];
        $password = '1' == $user['password']['password_auto']
            ? api_generate_password()
            : $user['password']['password'];

        UserManager::update_user(
            $userId,
            $userInfo['firstname'],
            $userInfo['lastname'],
            $userInfo['username'],
            $password,
            $auth_sources,
            $userInfo['email'],
            $userInfo['status'],
            $userInfo['official_code'],
            $userInfo['phone'],
            $userInfo['picture_uri'],
            $userInfo['expiration_date'],
            $userInfo['active'],
            $userInfo['creator_id'],
            $userInfo['hr_dept_id'],
            null, // $extra
            $userInfo['language'],
            null, // $encrypt_method
            false,
            $resetPassword
        );

        if (!empty($email) && $send_mail) {
            $emailsubject = '['.api_get_setting('siteName').'] '.get_lang('Your registration on').' '.api_get_setting('siteName');
            $portal_url = api_get_path(WEB_PATH);
            if (api_is_multiple_url_enabled()) {
                $access_url_id = api_get_current_access_url_id();
                if (-1 != $access_url_id) {
                    $urlAccess = api_get_access_url($access_url_id);
                    $portal_url = $urlAccess['url'];
                }
            }

            $emailbody = get_lang('Dear')." ".
                stripslashes(api_get_person_name($userInfo['firstname'], $userInfo['lastname'])).",\n\n".
                get_lang('You are registered to')." ".api_get_setting('siteName')." ".
                get_lang('with the following settings:')."\n\n".
                get_lang('Username')." : ".$username."\n".
                get_lang('Pass')." : ".stripslashes($password)."\n\n".
                get_lang('The address of')." ".api_get_setting('siteName')." ".
                get_lang('is')." : ".$portal_url."\n\n".
                get_lang('In case of trouble, contact us.')."\n\n".
                get_lang('Sincerely').",\n\n".
                api_get_person_name(
                    api_get_setting('administratorName'),
                    api_get_setting('administratorSurname')
                )."\n".
                get_lang('Administrator')." ".
                api_get_setting('siteName')."\nT. ".
                api_get_setting('administratorTelephone')."\n".
                get_lang('E-mail')." : ".api_get_setting('emailAdministrator');

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
    'url' => api_get_path(WEB_CODE_PATH).'my_space/student.php',
    'name' => get_lang('User list'),
];

if (isset($_REQUEST['message'])) {
    Display::addFlash(
        Display::return_message(get_lang('Update successful'), 'normal')
    );
}

Display::display_header($tool_name);

/**
 * Small layout tweaks so the page looks better:
 * - Center form in a card.
 * - Limit password field width so it is not full screen.
 */
echo '<style>
/* Main wrapper must always take full width */
.user-edit-wrapper {
    width: 100%;
    padding: 1.5rem 1.25rem 2.5rem;
}

/* Inner card centered with a max width */
.user-edit-card {
    max-width: 100%;
    margin: 0 auto;
    background-color: #ffffff;
    border-radius: 0.75rem;
    border: 1px solid #e5e7eb; /* light gray */
    box-shadow: 0 10px 15px rgba(15, 23, 42, 0.08);
    padding: 1.75rem 1.75rem 2rem;
}

.user-edit-card .field {
  margin-top: 1rem;
}

/* Reduce visual weight of labels a bit */
#user_edit .form-group label.control-label {
    font-weight: 600;
}

/* Limit password input width so it does not span the whole page */
#user_edit input[type="password"] {
    max-width: 320px;
}

/* Limit radios + password group to a readable width */
#user_edit .form-group .form-inline,
#user_edit .form-group .form-control {
    /* This keeps elements from stretching on very wide screens */
    max-width: 100%;
}

/* Make the radio + password inline group breathe a little */
#user_edit .form-group .form-control[type="password"] {
    display: inline-block;
    margin-left: 0.5rem;
}

/* Align submit button to the left with some top margin */
#user_edit .form-actions,
#user_edit .btn-primary {
    margin-top: 1rem;
}
</style>';

// Optional page subtitle
echo '<div class="user-edit-wrapper">';
echo '  <div class="user-edit-card">';
echo        Display::page_subheader(get_lang('Reset or update user password'));
// Display form inside styled card
$form->display();
echo '  </div>';
echo '</div>';

Display::display_footer();
