<?php
/* For licensing terms, see /license.txt */

/**
*	@package chamilo.admin
*/

$cidReset = true;
// Including necessary libraries.
require_once __DIR__.'/../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);

// Section for the tabs
$this_section = SECTION_PLATFORM_ADMIN;

// User permissions
api_protect_admin_script(true);
api_protect_limit_for_session_admin();

$is_platform_admin = api_is_platform_admin() ? 1 : 0;

$message = null;
$htmlHeadXtra[] = api_get_password_checker_js('#username', '#password');

$checkPass = api_get_setting('allow_strength_pass_checker');
if ($checkPass == 'true') {
    $htmlHeadXtra[] = '
    <script>
    $(document).ready(function() {
        $("#password").keypress(function() {
            $("#password").each(function(index, value) {
                var value = $(this).attr("value");
                if (value == 0) {
                    $("#password_progress").show();
                    $(".password-verdict").show();
                    $(".error-list").show();
                } else {
                    $("#password_progress").hide();
                    $(".password-verdict").hide();
                    $(".error-list").hide();
                }
            });
        });
    });
    </script>';
}
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

function display_drh_list(){
	if (document.getElementById("status_select").value=='.STUDENT.') {
		document.getElementById("drh_list").style.display="block";
        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="none";

	} else if (document.getElementById("status_select").value=='.COURSEMANAGER.') {
		document.getElementById("drh_list").style.display="none";

        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="block";
	} else {
		document.getElementById("drh_list").style.display="none";

        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="none";
	}
}
</script>';

if (!empty($_GET['message'])) {
    $message = urldecode($_GET['message']);
}

$interbreadcrumb[] = array('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array("url" => 'user_list.php', "name" => get_lang('UserList'));
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
        array(
            'id' => 'firstname'
        )
    );
    $form->applyFilter('firstname', 'html_filter');
    $form->applyFilter('firstname', 'trim');
    $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
    // Lastname
    $form->addElement(
        'text',
        'lastname',
        get_lang('LastName'),
        array(
            'id' => 'lastname'
        )
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
        array(
            'id' => 'lastname'
        )
    );
    $form->applyFilter('lastname', 'html_filter');
    $form->applyFilter('lastname', 'trim');
    $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
    // Firstname
    $form->addElement(
        'text',
        'firstname',
        get_lang('FirstName'),
        array(
            'id' => 'firstname'
        )
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
    array(
        'size' => '40',
        'id' => 'official_code'
    )
);
$form->applyFilter('official_code', 'html_filter');
$form->applyFilter('official_code', 'trim');
// Email
$form->addElement('text', 'email', get_lang('Email'), array('size' => '40', 'autocomplete' => 'off', 'id' => 'email'));
$form->addRule('email', get_lang('EmailWrong'), 'email');
if (api_get_setting('registration', 'email') == 'true') {
    $form->addRule('email', get_lang('EmailWrong'), 'required');
}

if (api_get_setting('login_is_email') == 'true') {
    $form->addRule('email', sprintf(get_lang('UsernameMaxXCharacters'), (string) USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
    $form->addRule('email', get_lang('UserTaken'), 'username_available');
}

// Phone
$form->addElement('text', 'phone', get_lang('PhoneNumber'), ['autocomplete' => 'off', 'id' => 'phone']);
// Picture
$form->addFile(
    'picture',
    get_lang('AddImage'),
    array('id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '1 / 1')
);
$allowed_picture_types = api_get_supported_image_extensions(false);

$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);

// Username
if (api_get_setting('login_is_email') != 'true') {
    $form->addElement('text', 'username', get_lang('LoginName'), array('id'=> 'username', 'maxlength' => USERNAME_MAX_LENGTH, 'autocomplete' => 'off'));
    $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'), (string) USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
    $form->addRule('username', get_lang('OnlyLettersAndNumbersAllowed'), 'username');
    $form->addRule('username', get_lang('UserTaken'), 'username_available');
}

// Password
$group = array();
$auth_sources = 0; //make available wider as we need it in case of form reset (see below)
$nb_ext_auth_source_added = 0;
if (isset($extAuthSource) && count($extAuthSource) > 0) {
    $auth_sources = array();
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
    array(
        'id' => 'password',
        'autocomplete' => 'off',
        'onkeydown' => 'javascript: password_switch_radio_button();',
        //'required' => 'required'
    )
);

$form->addGroup($group, 'password', get_lang('Password'));
$form->addPasswordRule('password', 'password');
$form->addGroupRule('password', get_lang('EnterPassword'), 'required', null, 1);

if ($checkPass) {
    $passwordStrengthLabels = '
        <div id="password-verdict"></div>
        <div id="password-errors"></div>
        <div id="password_progress" style="display:none"></div>
    ';
    $form->addElement('label', null, $passwordStrengthLabels);
}

// Status
$status = array();
$status[COURSEMANAGER] = get_lang('Teacher');
$status[STUDENT] = get_lang('Learner');
$status[DRH] = get_lang('Drh');
$status[SESSIONADMIN] = get_lang('SessionsAdmin');
$status[STUDENT_BOSS] = get_lang('RoleStudentBoss');
$status[INVITEE] = get_lang('Invitee');

$form->addElement(
    'select',
    'status',
    get_lang('Profile'),
    $status,
    array(
        'id' => 'status_select',
        'onchange' => 'javascript: display_drh_list();'
    )
);

//drh list (display only if student)
$display = isset($_POST['status']) && $_POST['status'] == STUDENT || !isset($_POST['status']) ? 'block' : 'none';

//@todo remove the drh list here. This code is unused
$form->addElement('html', '<div id="drh_list" style="display:'.$display.';">');

if (isset($drh_list) && is_array($drh_list)) {
    foreach ($drh_list as $drh) {
        $drh_select->addOption(
            api_get_person_name($drh['firstname'], $drh['lastname']),
            $drh['user_id']
        );
    }
}
$form->addElement('html', '</div>');

if (api_is_platform_admin()) {
    // Platform admin
    $group = array();
    $group[] = $form->createElement('radio', 'platform_admin', 'id="id_platform_admin"', get_lang('Yes'), 1);
    $group[] = $form->createElement('radio', 'platform_admin', 'id="id_platform_admin"', get_lang('No'), 0);
    $form->addElement('html', '<div id="id_platform_admin" style="display:'.$display.';">');
    $form->addGroup($group, 'admin', get_lang('PlatformAdmin'));
    $form->addElement('html', '</div>');
}

$form->addSelectLanguage('language', get_lang('Language'), null);

// Send email
$group = array();
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('Yes'), 1);
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('No'), 0);
$form->addGroup($group, 'mail', get_lang('SendMailToNewUser'));
// Expiration Date
$form->addElement('radio', 'radio_expiration_date', get_lang('ExpirationDate'), get_lang('NeverExpires'), 0);
$group = array();
$group[] = $form->createElement('radio', 'radio_expiration_date', null, get_lang('Enabled'), 1);
$group[] = $form->createElement(
    'DateTimePicker',
    'expiration_date',
    null,
    array(
        'onchange' => 'javascript: enable_expiration_date();'
    )
);
$form->addGroup($group, 'max_member_group', null, null, false);
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
    true
);
$jquery_ready_content = $returnParams['jquery_ready_content'];

// the $jquery_ready_content variable collects all functions that will be load in the $(document).ready javascript function
$htmlHeadXtra[] = '<script>
$(document).ready(function(){
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
        $status = intval($user['status']);
        $language = $user['language'];
        $picture = $_FILES['picture'];
        $platform_admin = intval($user['admin']['platform_admin']);
        $send_mail = intval($user['mail']['send_mail']);
        $hr_dept_id = isset($user['hr_dept_id']) ? intval($user['hr_dept_id']) : 0;

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

		$active = intval($user['active']);
        if (api_get_setting('login_is_email') == 'true') {
            $username = $email;
        }

        $extra = array();
        foreach ($user as $key => $value) {
            if (substr($key, 0, 6) == 'extra_') { //an extra field
                $extra[substr($key, 6)] = $value;
            }
        }

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
            $platform_admin
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
    $form->setConstants(array('sec_token' => $token));
}

if (!empty($message)) {
	$message = Display::return_message(stripslashes($message));
}
$content = $form->returnForm();

$tpl = new Template($tool_name);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();


