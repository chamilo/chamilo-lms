<?php

/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : (int) $_POST['user_id'];
api_protect_super_admin($user_id, null, true);
$is_platform_admin = api_is_platform_admin() ? 1 : 0;
$userInfo = api_get_user_info($user_id);
$userEntity = api_get_user_entity($user_id);

$htmlHeadXtra[] = '
<script>
var is_platform_id = "'.$is_platform_admin.'";

function enable_expiration_date() {
    document.user_edit.radio_expiration_date[0].checked=false;
    document.user_edit.radio_expiration_date[1].checked=true;
}

function password_switch_radio_button(){
    var input_elements = document.getElementsByTagName("input");
    for (var i = 0; i < input_elements.length; i++) {
        if(input_elements.item(i).name == "reset_password" && input_elements.item(i).value == "2") {
            input_elements.item(i).checked = true;
        }
    }
}

function display_drh_list(){
    var $radios = $("input:radio[name=platform_admin]");
    if (document.getElementById("status_select").value=='.COURSEMANAGER.') {
        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="block";
    } else if (document.getElementById("status_select").value=='.STUDENT.') {
        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="none";
        $radios.filter("[value=0]").attr("checked", true);
    } else {
        if (is_platform_id == 1)
            document.getElementById("id_platform_admin").style.display="none";
        $radios.filter("[value=0]").attr("checked", true);
    }
}

function show_image(image,width,height) {
    width = parseInt(width) + 20;
    height = parseInt(height) + 20;
    window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \' , resizable=0\');
}

function confirmation(name) {
    if (confirm("'.get_lang('AreYouSureToDeleteJS', '').' " + name + " ?")) {
            document.forms["profile"].submit();
    } else {
        return false;
    }
}
</script>';

$htmlHeadXtra[] = api_get_password_checker_js('#username', '#password');
$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');
$tool_name = get_lang('ModifyUserInfo');

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('PlatformAdmin')];
$interbreadcrumb[] = ['url' => 'user_list.php', 'name' => get_lang('UserList')];

$table_user = Database::get_main_table(TABLE_MAIN_USER);
$table_admin = Database::get_main_table(TABLE_MAIN_ADMIN);
$sql = "SELECT u.*, a.user_id AS is_admin FROM $table_user u
        LEFT JOIN $table_admin a ON a.user_id = u.id
        WHERE u.id = '".$user_id."'";
$res = Database::query($sql);
if (Database::num_rows($res) != 1) {
    header('Location: user_list.php');
    exit;
}

$user_data = Database::fetch_array($res, 'ASSOC');
$user_data['platform_admin'] = is_null($user_data['is_admin']) ? 0 : 1;
$user_data['send_mail'] = 0;
$user_data['old_password'] = $user_data['password'];
//Convert the registration date of the user

$user_data['registration_date'] = api_get_local_time($user_data['registration_date']);
unset($user_data['password']);

// Create the form
$form = new FormValidator(
    'user_edit',
    'post',
    api_get_self().'?user_id='.$user_id,
    ''
);
$form->protect();
$form->addElement('header', $tool_name);
$form->addElement('hidden', 'user_id', $user_id);

if (api_is_western_name_order()) {
    // Firstname
    $form->addElement('text', 'firstname', get_lang('FirstName'), ['autocomplete' => 'off']);
    $form->applyFilter('firstname', 'html_filter');
    $form->applyFilter('firstname', 'trim');
    $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
    // Lastname
    $form->addElement('text', 'lastname', get_lang('LastName'), ['autocomplete' => 'off']);
    $form->applyFilter('lastname', 'html_filter');
    $form->applyFilter('lastname', 'trim');
    $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
} else {
    // Lastname
    $form->addElement('text', 'lastname', get_lang('LastName'), ['autocomplete' => 'off']);
    $form->applyFilter('lastname', 'html_filter');
    $form->applyFilter('lastname', 'trim');
    $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
    // Firstname
    $form->addElement('text', 'firstname', get_lang('FirstName'), ['autocomplete' => 'off']);
    $form->applyFilter('firstname', 'html_filter');
    $form->applyFilter('firstname', 'trim');
    $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
}

// Official code
$form->addElement('text', 'official_code', get_lang('OfficialCode'), ['size' => '40']);
$form->applyFilter('official_code', 'html_filter');
$form->applyFilter('official_code', 'trim');

// Email
$form->addElement('text', 'email', get_lang('Email'), ['autocomplete' => 'off']);
$form->addRule('email', get_lang('EmailWrong'), 'email');
if (api_get_setting('registration', 'email') == 'true') {
    $form->addRule('email', get_lang('EmailWrong'), 'required');
}

if (api_get_setting('login_is_email') == 'true') {
    $form->addRule('email', sprintf(get_lang('UsernameMaxXCharacters'), (string) USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
    $form->addRule('email', get_lang('UserTaken'), 'username_available', $user_data['username']);
}

// OpenID
if (api_get_setting('openid_authentication') == 'true') {
    $form->addElement('text', 'openid', get_lang('OpenIDURL'));
}

// Phone
$form->addElement('text', 'phone', get_lang('PhoneNumber'), ['autocomplete' => 'off']);

// Picture
$form->addFile(
    'picture',
    get_lang('AddImage'),
    ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '1 / 1']
);
$allowed_picture_types = api_get_supported_image_extensions(false);

$form->addRule(
    'picture',
    get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
);
if (strlen($user_data['picture_uri']) > 0) {
    $form->addElement('checkbox', 'delete_picture', '', get_lang('DelImage'));
}

// Username
if (api_get_setting('login_is_email') != 'true') {
    $form->addElement(
        'text',
        'username',
        get_lang('LoginName'),
        ['autocomplete' => 'off', 'maxlength' => USERNAME_MAX_LENGTH]
    );
    $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule(
        'username',
        sprintf(get_lang('UsernameMaxXCharacters'), (string) USERNAME_MAX_LENGTH),
        'maxlength',
        USERNAME_MAX_LENGTH
    );
    $form->addRule('username', get_lang('OnlyLettersAndNumbersAllowed'), 'username');
    $form->addRule('username', get_lang('UserTaken'), 'username_available', $user_data['username']);
}

if (isset($extAuthSource) && !empty($extAuthSource) && count($extAuthSource) > 0) {
    $form->addLabel(
        get_lang('ExternalAuthentication'),
        $userInfo['auth_source']
    );
}

// Password
$form->addElement('radio', 'reset_password', get_lang('Password'), get_lang('DontResetPassword'), 0);
$nb_ext_auth_source_added = 0;
if (isset($extAuthSource) && !empty($extAuthSource) && count($extAuthSource) > 0) {
    $auth_sources = [];
    foreach ($extAuthSource as $key => $info) {
        // @todo : make uniform external authentication configuration (ex : cas and external_login ldap)
        // Special case for CAS. CAS is activated from Chamilo > Administration > Configuration > CAS
        // extAuthSource always on for CAS even if not activated
        // same action for file user_add.php
        if (($key == CAS_AUTH_SOURCE && api_get_setting('cas_activate') === 'true') || ($key != CAS_AUTH_SOURCE)) {
            $auth_sources[$key] = $key;
            $nb_ext_auth_source_added++;
        }
    }
    if ($nb_ext_auth_source_added > 0) {
        // @todo check the radio button for external authentification and select the external authentication in the menu
        $group[] = $form->createElement('radio', 'reset_password', null, get_lang('ExternalAuthentication').' ', 3);
        $group[] = $form->createElement('select', 'auth_source', null, $auth_sources);
        $group[] = $form->createElement('static', '', '', '<br />');
        $form->addGroup($group, 'password', null, null, false);
    }
}
$form->addElement('radio', 'reset_password', null, get_lang('AutoGeneratePassword'), 1);
$group = [];
$group[] = $form->createElement('radio', 'reset_password', null, get_lang('EnterPassword'), 2);
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
    '',
    null,
    false
);
$form->addPasswordRule('password', 'password');
$form->addNoSamePasswordRule('password', $userEntity);

// Status
$status = UserManager::getUserStatusList();

$hideSelectProfile = false;
// to hide the status list if it is not enabled with user_status_show_option for admin sessions.
if (api_is_session_admin()) {
    if (true === api_get_configuration_value('user_status_show_options_enabled')) {
        $userStatusConfig = api_get_configuration_value('user_status_show_option');
        if (!empty($userStatusConfig)) {
            $statusConfigHidden = [];
            foreach ($userStatusConfig as $role => $enabled) {
                $constStatus = constant($role);
                if (!$enabled) {
                    $statusConfigHidden[] = $constStatus;
                }
            }
            $hideSelectProfile = in_array($user_data['status'], $statusConfigHidden);
        }
    }
}

if (!$hideSelectProfile) {
    $form->addElement(
        'select',
        'status',
        get_lang('Profile'),
        $status,
        [
            'id' => 'status_select',
            'onchange' => 'javascript: display_drh_list();',
        ]
    );
} else {
    $form->addElement('hidden', 'status', $user_data['status']);
}

$display = isset($user_data['status']) && ($user_data['status'] == STUDENT || (isset($_POST['status']) && $_POST['status'] == STUDENT)) ? 'block' : 'none';

// Platform admin
if (api_is_platform_admin()) {
    $group = [];
    $group[] = $form->createElement('radio', 'platform_admin', null, get_lang('Yes'), 1);
    $group[] = $form->createElement('radio', 'platform_admin', null, get_lang('No'), 0);

    $user_data['status'] == 1 ? $display = 'block' : $display = 'none';

    $form->addElement('html', '<div id="id_platform_admin" style="display:'.$display.'">');
    $form->addGroup($group, 'admin', get_lang('PlatformAdmin'), null, false);
    $form->addElement('html', '</div>');
}

// Language
$form->addSelectLanguage('language', get_lang('Language'));

// Send email
$group = [];
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('Yes'), 1);
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('No'), 0);
$form->addGroup($group, 'mail', get_lang('SendMailToNewUser'), null, false);

// Registration User and Date
$creatorInfo = api_get_user_info($user_data['creator_id']);
$date = sprintf(
    get_lang('CreatedByXYOnZ'),
    'user_information.php?user_id='.$user_data['creator_id'],
    $creatorInfo['username'],
    $user_data['registration_date']
);
$form->addElement('label', get_lang('RegistrationDate'), $date);

$defaultExpiration = 0;
if (!$user_data['platform_admin']) {
    $hideNeverExpiresOpt = api_get_configuration_value('user_hide_never_expire_option');
    $lblExpiration = '';
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
}
$studentBossList = UserManager::getStudentBossList($user_data['user_id']);

$conditions = ['status' => STUDENT_BOSS];
$studentBoss = UserManager::get_user_list($conditions);
$studentBossToSelect = [];

if ($studentBoss) {
    foreach ($studentBoss as $bossId => $userData) {
        $bossInfo = api_get_user_info($userData['user_id']);
        $studentBossToSelect[$bossInfo['user_id']] = $bossInfo['complete_name_with_username'];
    }
}

if (!empty($studentBossList)) {
    $studentBossList = array_column($studentBossList, 'boss_id');
}

$user_data['student_boss'] = array_values($studentBossList);
$form->addElement('advmultiselect', 'student_boss', get_lang('StudentBoss'), $studentBossToSelect);

// EXTRA FIELDS
$extraField = new ExtraField('user');
$returnParams = $extraField->addElements(
    $form,
    $user_data['user_id'],
    [],
    false,
    false,
    [],
    [],
    [],
    false,
    true
);
$jqueryReadyContent = $returnParams['jquery_ready_content'];

$allowEmailTemplate = api_get_configuration_value('mail_template_system');
if ($allowEmailTemplate) {
    $form->addEmailTemplate(['user_edit_content.tpl']);
}

// the $jqueryReadyContent variable collects all functions that will be load in the
$htmlHeadXtra[] = '<script>
$(function () {
    '.$jqueryReadyContent.'
});
</script>';

// Freeze user conditions, admin cannot updated them
$extraConditions = api_get_configuration_value('show_conditions_to_user');

if ($extraConditions && isset($extraConditions['conditions'])) {
    $extraConditions = $extraConditions['conditions'];
    foreach ($extraConditions as $condition) {
        /** @var HTML_QuickForm_group $element */
        $element = $form->getElement('extra_'.$condition['variable']);
        if ($element) {
            $element->freeze();
        }
    }
}

// Submit button
$form->addButtonSave(get_lang('Save'));

// Set default values
$user_data['reset_password'] = 0;
$expiration_date = $user_data['expiration_date'];

if (empty($expiration_date)) {
    $user_data['radio_expiration_date'] = 0;
    $user_data['expiration_date'] = api_get_local_time();
} else {
    $user_data['radio_expiration_date'] = 1;
    $user_data['expiration_date'] = api_get_local_time($expiration_date);
}
$form->setDefaults($user_data);

$error_drh = false;
// Validate form
if ($form->validate()) {
    $user = $form->getSubmitValues(1);
    $reset_password = (int) $user['reset_password'];
    if ($reset_password == 2 && empty($user['password'])) {
        Display::addFlash(Display::return_message(get_lang('PasswordIsTooShort')));
        header('Location: '.api_get_self().'?user_id='.$user_id);
        exit();
    }

    $is_user_subscribed_in_course = CourseManager::is_user_subscribed_in_course($user['user_id']);

    $picture_element = $form->getElement('picture');
    $picture = $picture_element->getValue();

    $picture_uri = $user_data['picture_uri'];
    if (isset($user['delete_picture']) && $user['delete_picture']) {
        $picture_uri = UserManager::deleteUserPicture($user_id);
    } elseif (!empty($picture['name'])) {
        $picture_uri = UserManager::update_user_picture(
            $user_id,
            $_FILES['picture']['name'],
            $_FILES['picture']['tmp_name'],
            $user['picture_crop_result']
        );
    }

    $lastname = $user['lastname'];
    $firstname = $user['firstname'];
    $password = $user['password'];
    $auth_source = isset($user['auth_source']) ? $user['auth_source'] : $userInfo['auth_source'];
    $official_code = $user['official_code'];
    $email = $user['email'];
    $phone = $user['phone'];
    $username = isset($user['username']) ? $user['username'] : $userInfo['username'];
    $status = (int) $user['status'];
    $platform_admin = 0;
    // Only platform admin can change user status to admin.
    if (api_is_platform_admin()) {
        $platform_admin = (int) $user['platform_admin'];
    }

    $send_mail = (int) $user['send_mail'];
    $reset_password = (int) $user['reset_password'];
    $hr_dept_id = isset($user['hr_dept_id']) ? intval($user['hr_dept_id']) : null;
    $language = $user['language'];
    $address = isset($user['address']) ? $user['address'] : null;

    $expiration_date = null;
    if (!$user_data['platform_admin'] && $user['radio_expiration_date'] == '1') {
        $expiration_date = $user['expiration_date'];
    }

    $active = $user_data['platform_admin'] ? 1 : intval($user['active']);

    //If the user is set to admin the status will be overwrite by COURSEMANAGER = 1
    if ($platform_admin == 1) {
        $status = COURSEMANAGER;
    }

    if (api_get_setting('login_is_email') === 'true') {
        $username = $email;
    }

    $template = isset($user['email_template_option']) ? $user['email_template_option'] : [];

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
        $language,
        null,
        $send_mail,
        $reset_password,
        $address,
        $template
    );

    $studentBossListSent = isset($user['student_boss']) ? $user['student_boss'] : [];
    UserManager::subscribeUserToBossList(
        $user_id,
        $studentBossListSent,
        true
    );

    if (api_get_setting('openid_authentication') === 'true' && !empty($user['openid'])) {
        $up = UserManager::update_openid($user_id, $user['openid']);
    }

    $currentUserId = api_get_user_id();
    if ($user_id != $currentUserId) {
        $userObj = api_get_user_entity($user_id);
        if ($platform_admin == 1) {
            UserManager::addUserAsAdmin($userObj);
        } else {
            UserManager::removeUserAdmin($userObj);
        }
    }

    // It updates course relation type as EX-LEARNER if project name (extra field from user_edition_extra_field_to_check) is changed
    if (false !== api_get_configuration_value('user_edition_extra_field_to_check')) {
        $extraToCheck = api_get_configuration_value('user_edition_extra_field_to_check');
        if (isset($user['extra_'.$extraToCheck])) {
            $extraValueToCheck = $user['extra_'.$extraToCheck];
            UserManager::updateCourseRelationTypeExLearner($user_id, $extraValueToCheck);
        }
    }

    $extraFieldValue = new ExtraFieldValue('user');
    $extraFieldValue->saveFieldValues($user);
    $userInfo = api_get_user_info($user_id);
    $message = get_lang('UserUpdated').': '.Display::url(
        $userInfo['complete_name_with_username'],
        api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user_id
    );

    Session::erase('system_timezone');

    Display::addFlash(Display::return_message($message, 'normal', false));
    header('Location: user_list.php');
    exit();
}

$actions = [
    Display::url(
        Display::return_icon(
            'info.png',
            get_lang('Information'),
            [],
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$user_id
    ),
    Display::url(
        Display::return_icon(
            'login_as.png',
            get_lang('LoginAs'),
            [],
            ICON_SIZE_MEDIUM
        ),
        api_get_path(WEB_CODE_PATH).'admin/user_list.php?action=login_as&user_id='.$user_id.'&sec_token='.Security::getTokenFromSession()
    ),
];

$content = Display::toolbarAction('toolbar-user-information', [implode(PHP_EOL, $actions)]);

$bigImage = UserManager::getUserPicture($user_id, USER_IMAGE_SIZE_BIG);
$normalImage = UserManager::getUserPicture($user_id, USER_IMAGE_SIZE_ORIGINAL);
$content .= '<div class="row">';
$content .= '<div class="col-md-10">';
// Display form
$content .= $form->returnForm();
$content .= '</div>';
$content .= '<div class="col-md-2">';
$content .= '<a class="thumbnail expand-image" href="'.$bigImage.'" /><img src="'.$normalImage.'"></a>';
$content .= '</div>';

$tpl = new Template($tool_name);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
