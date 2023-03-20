<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

// Section for the tabs
$this_section = SECTION_PLATFORM_ADMIN;

// User permissions
api_protect_admin_script(true);
api_protect_limit_for_session_admin();

$is_platform_admin = api_is_platform_admin() ? 1 : 0;

$message = null;
$htmlHeadXtra[] = api_get_password_checker_js('#username', '#password');
//$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
//$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');
$htmlHeadXtra[] = '
<script>
$("#status_select").ready(function() {
    if ($(this).attr("value") != '.STUDENT.') {
        $("#id_platform_admin").hide();
    }
});
function enable_expiration_date() { //v2.0
    document.user_add.radio_expiration_date[0].checked=false;
    document.user_add.radio_expiration_date[1].checked=true;
}

function password_switch_radio_button() {
    var input_elements = document.getElementsByTagName("input");
    for (var i = 0; i < input_elements.length; i++) {
        if (input_elements.item(i).name == "password[password_auto]" && input_elements.item(i).value == "0") {
            input_elements.item(i).checked = true;
        }
    }
}

var is_platform_id = "'.$is_platform_admin.'";

function updateStatus(){
    if (document.getElementById("status_select").value=='.STUDENT.') {
        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="none";

    } else if (document.getElementById("status_select").value=='.COURSEMANAGER.') {

        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="block";
    } else {

        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="none";
    }
}
</script>';

if (!empty($_GET['message'])) {
    $message = urldecode($_GET['message']);
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ["url" => 'user_list.php', "name" => get_lang('User list')];
$tool_name = get_lang('Add a user');

// Create the form
$form = new FormValidator('user_add');
$form->addElement('header', '', $tool_name);
if (api_is_western_name_order()) {
    // Firstname
    $form->addElement(
        'text',
        'firstname',
        get_lang('First name'),
        [
            'id' => 'firstname',
        ]
    );
    $form->applyFilter('firstname', 'html_filter');
    $form->applyFilter('firstname', 'trim');
    $form->addRule('firstname', get_lang('Required field'), 'required');
    // Lastname
    $form->addElement(
        'text',
        'lastname',
        get_lang('Last name'),
        [
            'id' => 'lastname',
        ]
    );
    $form->applyFilter('lastname', 'html_filter');
    $form->applyFilter('lastname', 'trim');
    $form->addRule('lastname', get_lang('Required field'), 'required');
} else {
    // Lastname
    $form->addElement(
        'text',
        'lastname',
        get_lang('Last name'),
        [
            'id' => 'lastname',
        ]
    );
    $form->applyFilter('lastname', 'html_filter');
    $form->applyFilter('lastname', 'trim');
    $form->addRule('lastname', get_lang('Required field'), 'required');
    // Firstname
    $form->addElement(
        'text',
        'firstname',
        get_lang('First name'),
        [
            'id' => 'firstname',
        ]
    );
    $form->applyFilter('firstname', 'html_filter');
    $form->applyFilter('firstname', 'trim');
    $form->addRule('firstname', get_lang('Required field'), 'required');
}
// Official code
$form->addElement(
    'text',
    'official_code',
    get_lang('Code'),
    [
        'size' => '40',
        'id' => 'official_code',
    ]
);
$form->applyFilter('official_code', 'html_filter');
$form->applyFilter('official_code', 'trim');
// e-mail
$form->addElement('text', 'email', get_lang('e-mail'), ['size' => '40', 'autocomplete' => 'off', 'id' => 'email']);
$form->addEmailRule('email');
if ('true' == api_get_setting('registration', 'email')) {
    $form->addRule('email', get_lang('Required field'), 'required');
}

if ('true' === api_get_setting('login_is_email')) {
    $form->addRule(
        'email',
        sprintf(get_lang('The login needs to be maximum %s characters long'), (string) User::USERNAME_MAX_LENGTH),
        'maxlength',
        User::USERNAME_MAX_LENGTH
    );
    $form->addRule('email', get_lang('This login is already in use'), 'username_available');
}

// Phone
$form->addElement('text', 'phone', get_lang('Phone number'), ['autocomplete' => 'off', 'id' => 'phone']);
// Picture
$form->addFile(
    'picture',
    get_lang('Add image'),
    ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '1 / 1']
);
$allowed_picture_types = api_get_supported_image_extensions(false);

$form->addRule('picture', get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);

// Username
if ('true' !== api_get_setting('login_is_email')) {
    $form->addElement('text', 'username', get_lang('Login'), ['id' => 'username', 'maxlength' => User::USERNAME_MAX_LENGTH, 'autocomplete' => 'off']);
    $form->addRule('username', get_lang('Required field'), 'required');
    $form->addRule('username', sprintf(get_lang('The login needs to be maximum %s characters long'), (string) User::USERNAME_MAX_LENGTH), 'maxlength', User::USERNAME_MAX_LENGTH);
    $form->addRule('username', get_lang('Only letters and numbers allowed'), 'username');
    $form->addRule('username', get_lang('This login is already in use'), 'username_available');
}

// Password
$group = [];
$auth_sources = 0; //make available wider as we need it in case of form reset (see below)
$nb_ext_auth_source_added = 0;
if (isset($extAuthSource) && count($extAuthSource) > 0) {
    $auth_sources = [];
    foreach ($extAuthSource as $key => $info) {
        // @todo : make uniform external authentification configuration (ex : cas and external_login ldap)
        // Special case for CAS. CAS is activated from Chamilo > Administration > Configuration > CAS
        // extAuthSource always on for CAS even if not activated
        // same action for file user_edit.php
        if ((CAS_AUTH_SOURCE == $key && 'true' === api_get_setting('cas_activate')) || (CAS_AUTH_SOURCE != $key)) {
            $auth_sources[$key] = $key;
            $nb_ext_auth_source_added++;
        }
    }
    if ($nb_ext_auth_source_added > 0) {
        $group[] = $form->createElement('radio', 'password_auto', null, get_lang('External authentification').' ', 2);
        $group[] = $form->createElement('select', 'auth_source', null, $auth_sources);
        $group[] = $form->createElement('static', '', '', '<br />');
    }
}

$group[] = $form->createElement(
    'radio',
    'password_auto',
    get_lang('Password'),
    get_lang('Automatically generate a new password').'<br />',
    1
);
$group[] = $form->createElement(
    'radio',
    'password_auto',
    'id="radio_user_password"',
    get_lang('Enter password'),
    0
);
$group[] = $form->createElement(
    'password',
    'password',
    null,
    [
        'id' => 'password',
        'autocomplete' => 'off',
        'onkeydown' => 'javascript: password_switch_radio_button();',
        //'required' => 'required'
    ]
);

$form->addGroup($group, 'password', get_lang('Password'));
$form->addPasswordRule('password', 'password');
$form->addGroupRule('password', get_lang('Enter password'), 'required', null, 1);

// Status
$status = [];
$status[COURSEMANAGER] = get_lang('Trainer');
$status[STUDENT] = get_lang('Learner');
$status[DRH] = get_lang('Human Resources Manager');
$status[SESSIONADMIN] = get_lang('Sessions administrator');
$status[STUDENT_BOSS] = get_lang('Student\'s superior');
$status[INVITEE] = get_lang('Invitee');

$form->addSelect(
    'status',
    get_lang('Profile'),
    $status,
    [
        'id' => 'status_select',
        'onchange' => 'javascript: updateStatus();',
    ]
);

//drh list (display only if student)
$display = (isset($_POST['status']) && STUDENT == $_POST['status']) || !isset($_POST['status']) ? 'block' : 'none';

if (api_is_platform_admin()) {
    // Platform admin
    $group = [];
    $group[] = $form->createElement('radio', 'platform_admin', 'id="id_platform_admin"', get_lang('Yes'), 1);
    $group[] = $form->createElement('radio', 'platform_admin', 'id="id_platform_admin"', get_lang('No'), 0);
    $form->addElement('html', '<div id="id_platform_admin" style="display:'.$display.';">');
    $form->addGroup($group, 'admin', get_lang('Administration'));
    $form->addElement('html', '</div>');
}

$form->addSelectLanguage('locale', get_lang('Language'));

// Send email
$group = [];
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('Yes'), 1, ['id' => 'send_mail_yes']);
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('No'), 0, ['id' => 'send_mail_no']);
$form->addGroup($group, 'mail', get_lang('Send mail to new user'));
// Expiration Date
$form->addElement('radio', 'radio_expiration_date', get_lang('Expiration date'), get_lang('Never expires'), 0);
$group = [];
$group[] = $form->createElement('radio', 'radio_expiration_date', null, get_lang('Enabled'), 1);
$group[] = $form->createElement(
    'DateTimePicker',
    'expiration_date',
    null,
    [
        'onchange' => 'javascript: enable_expiration_date();',
    ]
);
$form->addGroup($group, 'max_member_group', null, null, false);
// active account or inactive account
$form->addElement('radio', 'active', get_lang('Account'), get_lang('active'), 1);
$form->addElement('radio', 'active', '', get_lang('inactive'), 0);

$extraField = new ExtraField('user');
$returnParams = $extraField->addElements(
    $form,
    null,
    [],
    false,
    false,
    [],
    [],
    [],
    false,
    true
);

$allowEmailTemplate = api_get_configuration_value('mail_template_system');
if ($allowEmailTemplate) {
    $form->addEmailTemplate(
        [
            'subject_registration_platform.tpl',
            'content_registration_platform.tpl',
            'new_user_first_email_confirmation.tpl',
            'new_user_second_email_confirmation.tpl',
        ]
    );
}

$jquery_ready_content = $returnParams['jquery_ready_content'];

// the $jquery_ready_content variable collects all functions that will be load in the $(document).ready javascript function
$htmlHeadXtra[] = '<script>
$(function () {
    '.$jquery_ready_content.'
});
</script>';

// Set default values
$defaults['admin']['platform_admin'] = 0;
$defaults['mail']['send_mail'] = 1;
$defaults['password']['password_auto'] = 1;
$defaults['active'] = 1;
$days = api_get_setting('account_valid_duration');
$defaults['expiration_date'] = api_get_local_time('+'.$days.' day');
$defaults['extra_mail_notify_invitation'] = 1;
$defaults['extra_mail_notify_message'] = 1;
$defaults['extra_mail_notify_group_message'] = 1;
$defaults['radio_expiration_date'] = 0;
$defaults['status'] = STUDENT;
$defaults['locale'] = api_get_language_isocode();
$form->setDefaults($defaults);

// Submit button
$html_results_enabled[] = $form->createElement('button', 'submit', get_lang('Add'), 'plus', 'primary');
$html_results_enabled[] = $form->createElement('button', 'submit_plus', get_lang('Add').'+', 'plus', 'primary');
$form->addGroup($html_results_enabled);

// Validate form
if ($form->validate()) {
    $check = Security::check_token('post');
    if (true) {
        $user = $form->getSubmitValues();
        $lastname = $user['lastname'];
        $firstname = $user['firstname'];
        $official_code = $user['official_code'];
        $email = $user['email'];
        $phone = $user['phone'];
        $username = $user['username'];
        $status = (int) $user['status'];
        $language = $user['locale'];
        $picture = $_FILES['picture'];
        $platform_admin = 0;
        if (isset($user['admin']) && isset($user['admin']['platform_admin'])) {
            $platform_admin = (int) $user['admin']['platform_admin'];
        }
        $send_mail = 0;
        if (isset($user['mail']) && isset($user['mail']['send_mail'])) {
            $send_mail = (int) $user['mail']['send_mail'];
        }

        $hr_dept_id = isset($user['hr_dept_id']) ? (int) $user['hr_dept_id'] : 0;

        if (isset($extAuthSource) && count($extAuthSource) > 0 &&
            '2' == $user['password']['password_auto']
        ) {
            $auth_source = $user['password']['auth_source'];
            $password = 'PLACEHOLDER';
        } else {
            $auth_source = PLATFORM_AUTH_SOURCE;
            $password = '1' === $user['password']['password_auto'] ? api_generate_password() : $user['password']['password'];
        }

        $expiration_date = null;
        if ('1' === $user['radio_expiration_date']) {
            $expiration_date = $user['expiration_date'];
        }

        $active = (int) $user['active'];
        if ('true' === api_get_setting('login_is_email')) {
            $username = $email;
        }

        $extra = [];
        foreach ($user as $key => $value) {
            if ('extra_' === substr($key, 0, 6)) {
                // An extra field
                $extra[substr($key, 6)] = $value;
            }
        }

        $template = isset($user['email_template_option']) ? $user['email_template_option'] : [];

        $user_id = UserManager::create_user(
            $firstname,
            $lastname,
            $status,
            $email,
            $username,
            $password,
            $official_code,
            $language,
            $phone,
            null,
            $auth_source,
            $expiration_date,
            $active,
            $hr_dept_id,
            $extra,
            null,
            $send_mail,
            $platform_admin,
            '',
            false,
            null,
            0,
            $template
        );

        Security::clear_token();
        $tok = Security::get_token();
        if (!empty($user_id)) {
            if (!empty($picture['name'])) {
                $request = Container::getRequest();
                $file = $request->files->get('picture');
                UserManager::update_user_picture(
                    $user_id,
                    $file,
                    $user['picture_crop_result']
                );
                UserManager::update_user(
                    $user_id,
                    $firstname,
                    $lastname,
                    $username,
                    $password,
                    $auth_source,
                    $email,
                    $status,
                    $official_code,
                    $phone,
                    null,
                    $expiration_date,
                    $active,
                    null,
                    $hr_dept_id,
                    null,
                    $language
                );
            }

            $extraFieldValues = new ExtraFieldValue('user');
            $user['item_id'] = $user_id;
            $extraFieldValues->saveFieldValues($user);
            $message = get_lang('The user has been added').': '.
                Display::url(
                    api_get_person_name($firstname, $lastname),
                    api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user_id
                );
        }

        Display::addFlash(Display::return_message($message, 'normal', false));

        if (isset($_POST['submit_plus'])) {
            //we want to add more. Prepare report message and redirect to the same page (to clean the form)
            header('Location: user_add.php?sec_token='.$tok);
            exit;
        } else {
            $tok = Security::get_token();
            header('Location: user_list.php?sec_token='.$tok);
            exit;
        }
    }
} else {
    if (isset($_POST['submit'])) {
        Security::clear_token();
    }
    $token = Security::get_token();
    $form->addElement('hidden', 'sec_token');
    $form->setConstants(['sec_token' => $token]);
}

if (!empty($message)) {
    $message = Display::return_message(stripslashes($message));
}
$content = $form->returnForm();

$tpl = new Template($tool_name);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
