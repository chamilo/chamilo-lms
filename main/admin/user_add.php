<?php

/* For licensing terms, see /license.txt */

$cidReset = true;
// Including necessary libraries.
require_once __DIR__.'/../inc/global.inc.php';

// Section for the tabs
$this_section = SECTION_PLATFORM_ADMIN;

// User permissions
api_protect_admin_script(true);
api_protect_limit_for_session_admin();

$is_platform_admin = api_is_platform_admin() ? 1 : 0;
$setExpirationDateByRole = (false !== api_get_configuration_value('user_number_of_days_for_default_expiration_date_per_role'));
$message = null;
$htmlHeadXtra[] = api_get_password_checker_js('#username', '#password');
$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');
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
var setExpirationDateByRole = "'.$setExpirationDateByRole.'";
function updateStatus() {
    var status = document.getElementById("status_select").value;
    if (status == '.STUDENT.') {
        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="none";

    } else if (status == '.COURSEMANAGER.') {
        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="block";
    } else {
        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="none";
    }

    if (setExpirationDateByRole) {
        setExpirationDatePicker(status);
    }
}

function setExpirationDatePicker(status) {
    $.getJSON("../inc/ajax/user_manager.ajax.php?a=set_expiration_date&status="+status, function(json) {
        if (json.formatted) {
            $("#expiration_date_alt_text").text(json.formatted);
        }
        if (json.date) {
            $("#expiration_date").val(json.date);
            $("#expiration_date_alt").val(json.date);
        }
    });
}

</script>';

if (!empty($_GET['message'])) {
    $message = urldecode($_GET['message']);
}

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ["url" => 'user_list.php', "name" => get_lang('UserList')];
$tool_name = get_lang('AddUsers');

// Create the form
$form = new FormValidator('user_add');
$form->addElement('header', '', $tool_name);
if (api_is_western_name_order()) {
    // Firstname
    $form->addElement(
        'text',
        'firstname',
        get_lang('FirstName'),
        [
            'id' => 'firstname',
        ]
    );
    $form->applyFilter('firstname', 'html_filter');
    $form->applyFilter('firstname', 'trim');
    $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
    // Lastname
    $form->addElement(
        'text',
        'lastname',
        get_lang('LastName'),
        [
            'id' => 'lastname',
        ]
    );
    $form->applyFilter('lastname', 'html_filter');
    $form->applyFilter('lastname', 'trim');
    $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
} else {
    // Lastname
    $form->addElement(
        'text',
        'lastname',
        get_lang('LastName'),
        [
            'id' => 'lastname',
        ]
    );
    $form->applyFilter('lastname', 'html_filter');
    $form->applyFilter('lastname', 'trim');
    $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
    // Firstname
    $form->addElement(
        'text',
        'firstname',
        get_lang('FirstName'),
        [
            'id' => 'firstname',
        ]
    );
    $form->applyFilter('firstname', 'html_filter');
    $form->applyFilter('firstname', 'trim');
    $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
}
// Official code
$form->addElement(
    'text',
    'official_code',
    get_lang('OfficialCode'),
    [
        'size' => '40',
        'id' => 'official_code',
    ]
);
$form->applyFilter('official_code', 'html_filter');
$form->applyFilter('official_code', 'trim');
// Email
$form->addElement('text', 'email', get_lang('Email'), ['size' => '40', 'autocomplete' => 'off', 'id' => 'email']);
$form->addRule('email', get_lang('EmailWrong'), 'email');
if (api_get_setting('registration', 'email') == 'true') {
    $form->addRule('email', get_lang('EmailWrong'), 'required');
}

if (api_get_setting('login_is_email') == 'true') {
    $form->addRule('email', sprintf(get_lang('UsernameMaxXCharacters'), (string) USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
    $form->addRule('email', get_lang('UserTaken'), 'username_available');
}

// Phone
$form->addText('phone', get_lang('PhoneNumber'), false, ['autocomplete' => 'off', 'id' => 'phone']);
// Picture
$form->addFile(
    'picture',
    get_lang('AddImage'),
    ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '1 / 1']
);
$allowed_picture_types = api_get_supported_image_extensions(false);

$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);

// Username
if (api_get_setting('login_is_email') != 'true') {
    $form->addElement('text', 'username', get_lang('LoginName'), ['id' => 'username', 'maxlength' => USERNAME_MAX_LENGTH, 'autocomplete' => 'off']);
    $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'), (string) USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
    $form->addRule('username', get_lang('OnlyLettersAndNumbersAllowed'), 'username');
    $form->addRule('username', get_lang('UserTaken'), 'username_available');
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
        if (($key == CAS_AUTH_SOURCE && api_get_setting('cas_activate') === 'true') || ($key != CAS_AUTH_SOURCE)) {
            $auth_sources[$key] = $key;
            $nb_ext_auth_source_added++;
        }
    }
    if ($nb_ext_auth_source_added > 0) {
        $group[] = $form->createElement('radio', 'password_auto', null, get_lang('ExternalAuthentication').' ', 2);
        $group[] = $form->createElement('select', 'auth_source', null, $auth_sources);
        $group[] = $form->createElement('static', '', '', '<br />');
    }
}

$group[] = $form->createElement(
    'radio',
    'password_auto',
    get_lang('Password'),
    get_lang('AutoGeneratePassword').'<br />',
    1
);
$group[] = $form->createElement(
    'radio',
    'password_auto',
    'id="radio_user_password"',
    get_lang('EnterPassword'),
    0
);
$group[] = $form->createElement(
    'password',
    'password',
    null,
    [
        'id' => 'password',
        'autocomplete' => 'new-password',
        'onkeydown' => 'javascript: password_switch_radio_button();',
        'show_hide' => true,
        //'required' => 'required'
    ]
);

$form->addGroup(
    $group,
    'password',
    get_lang('Password')
);
$form->addPasswordRule('password', 'password');
$form->addGroupRule('password', get_lang('EnterPassword'), 'required', null, 1);

// Status
$status = UserManager::getUserStatusList();

$form->addElement(
    'select',
    'status',
    get_lang('Profile'),
    $status,
    [
        'id' => 'status_select',
        'onchange' => 'javascript: updateStatus();',
    ]
);

//drh list (display only if student)
$display = (isset($_POST['status']) && $_POST['status'] == STUDENT) || !isset($_POST['status']) ? 'block' : 'none';

if (api_is_platform_admin()) {
    // Platform admin
    $group = [];
    $group[] = $form->createElement('radio', 'platform_admin', 'id="id_platform_admin"', get_lang('Yes'), 1);
    $group[] = $form->createElement('radio', 'platform_admin', 'id="id_platform_admin"', get_lang('No'), 0);
    $form->addElement('html', '<div id="id_platform_admin" style="display:'.$display.';">');
    $form->addGroup($group, 'admin', get_lang('PlatformAdmin'));
    $form->addElement('html', '</div>');
}

$form->addSelectLanguage('language', get_lang('Language'), null);

// Send email
$group = [];
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('Yes'), 1, ['id' => 'send_mail_yes']);
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('No'), 0, ['id' => 'send_mail_no']);
$form->addGroup($group, 'mail', get_lang('SendMailToNewUser'));
// Expiration Date
$hideNeverExpiresOpt = api_get_configuration_value('user_hide_never_expire_option');
$lblExpiration = '';
$defaultExpiration = 0;
if ($hideNeverExpiresOpt) {
    $lblExpiration = get_lang('ExpirationDate');
    $defaultExpiration = 1;
    $group = [];
    $group[] = $form->createElement('radio', 'radio_expiration_date', get_lang('ExpirationDate'), get_lang('Enabled'), 1);
    $group[] = $form->createElement(
        'DateTimePicker',
        'expiration_date',
        null
    );
} else {
    $form->addElement('radio', 'radio_expiration_date', get_lang('ExpirationDate'), get_lang('NeverExpires'), 0);
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
}
$form->addGroup($group, 'max_member_group', $lblExpiration, null, false);

// Active account or inactive account
$form->addElement('radio', 'active', get_lang('ActiveAccount'), get_lang('Active'), 1);
$form->addElement('radio', 'active', '', get_lang('Inactive'), 0);

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

$expirationDateDefault = UserManager::getExpirationDateByRole(STUDENT);
if (!empty($expirationDateDefault)) {
    $defaults['expiration_date'] = $expirationDateDefault['date'];
} else {
    $defaults['expiration_date'] = api_get_local_time('+'.$days.' day');
}

$defaults['extra_mail_notify_invitation'] = 1;
$defaults['extra_mail_notify_message'] = 1;
$defaults['extra_mail_notify_group_message'] = 1;
$defaults['radio_expiration_date'] = $defaultExpiration;
$defaults['status'] = STUDENT;
$form->setDefaults($defaults);

// Submit button
$html_results_enabled[] = $form->createElement('button', 'submit', get_lang('Add'), 'plus', 'primary');
$html_results_enabled[] = $form->createElement('button', 'submit_plus', get_lang('Add').'+', 'plus', 'primary');
$form->addGroup($html_results_enabled);

// Validate form
if ($form->validate()) {
    $check = Security::check_token('post');
    if ($check) {
        $user = $form->exportValues();

        $lastname = $user['lastname'];
        $firstname = $user['firstname'];
        $official_code = $user['official_code'];
        $email = $user['email'];
        $phone = $user['phone'];
        $username = $user['username'];
        $status = (int) $user['status'];
        $language = $user['language'];
        $picture = $_FILES['picture'];
        $platform_admin = (int) $user['admin']['platform_admin'];
        $send_mail = (int) $user['mail']['send_mail'];
        $hr_dept_id = isset($user['hr_dept_id']) ? (int) $user['hr_dept_id'] : 0;

        if (isset($extAuthSource) && count($extAuthSource) > 0 &&
            $user['password']['password_auto'] == '2'
        ) {
            $auth_source = $user['password']['auth_source'];
            $password = 'PLACEHOLDER';
        } else {
            $auth_source = PLATFORM_AUTH_SOURCE;
            $password = $user['password']['password_auto'] == '1' ? api_generate_password() : $user['password']['password'];
        }

        if ($user['radio_expiration_date'] == '1') {
            $expiration_date = $user['expiration_date'];
        } else {
            $expiration_date = null;
        }

        $active = (int) $user['active'];
        if (api_get_setting('login_is_email') == 'true') {
            $username = $email;
        }

        $extra = [];
        foreach ($user as $key => $value) {
            if (substr($key, 0, 6) == 'extra_') {
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
                $picture_uri = UserManager::update_user_picture(
                    $user_id,
                    $_FILES['picture']['name'],
                    $_FILES['picture']['tmp_name'],
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
                    $picture_uri,
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
            $message = get_lang('UserAdded').': '.
                Display::url(
                    api_get_person_name($firstname, $lastname),
                    api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user_id
                );
        }

        Display::addFlash(Display::return_message($message, 'normal', false));

        if (isset($_POST['submit_plus'])
            || (api_is_session_admin() && api_get_configuration_value('limit_session_admin_list_users'))
        ) {
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
