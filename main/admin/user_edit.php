<?php
/* For licensing terms, see /license.txt */

/**
 * @package chamilo.admin
*/

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : intval($_POST['user_id']);
api_protect_super_admin($user_id, null, true);
$is_platform_admin = api_is_platform_admin() ? 1 : 0;
$userInfo = api_get_user_info($user_id);

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

$gMapsPlugin = GoogleMapsPlugin::create();
$geolocalization = $gMapsPlugin->get('enable_api') === 'true';

if ($geolocalization) {
    $gmapsApiKey = $gMapsPlugin->get('api_key');
    $htmlHeadXtra[] = '<script type="text/javascript" src="//maps.googleapis.com/maps/api/js?sensor=true&key='.$gmapsApiKey.'" ></script>';
}

$htmlHeadXtra[] = api_get_css_asset('cropper/dist/cropper.min.css');
$htmlHeadXtra[] = api_get_asset('cropper/dist/cropper.min.js');

$libpath = api_get_path(LIBRARY_PATH);
$noPHP_SELF = true;
$tool_name = get_lang('ModifyUserInfo');

$interbreadcrumb[] = array('url' => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => "user_list.php", "name" => get_lang('UserList'));

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
$form->addElement('header', $tool_name);
$form->addElement('hidden', 'user_id', $user_id);

if (api_is_western_name_order()) {
    // Firstname
    $form->addElement('text', 'firstname', get_lang('FirstName'));
    $form->applyFilter('firstname', 'html_filter');
    $form->applyFilter('firstname', 'trim');
    $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
    // Lastname
    $form->addElement('text', 'lastname', get_lang('LastName'));
    $form->applyFilter('lastname', 'html_filter');
    $form->applyFilter('lastname', 'trim');
    $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
} else {
    // Lastname
    $form->addElement('text', 'lastname', get_lang('LastName'));
    $form->applyFilter('lastname', 'html_filter');
    $form->applyFilter('lastname', 'trim');
    $form->addRule('lastname', get_lang('ThisFieldIsRequired'), 'required');
    // Firstname
    $form->addElement('text', 'firstname', get_lang('FirstName'));
    $form->applyFilter('firstname', 'html_filter');
    $form->applyFilter('firstname', 'trim');
    $form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
}

// Official code
$form->addElement('text', 'official_code', get_lang('OfficialCode'), array('size' => '40'));
$form->applyFilter('official_code', 'html_filter');
$form->applyFilter('official_code', 'trim');

// Email
$form->addElement('text', 'email', get_lang('Email'));
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
$form->addElement('text', 'phone', get_lang('PhoneNumber'));

// Picture
$form->addFile(
    'picture',
    get_lang('AddImage'),
    array('id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '1 / 1')
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
    $form->addElement('text', 'username', get_lang('LoginName'), array('maxlength' => USERNAME_MAX_LENGTH));
    $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'), (string) USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
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
    $auth_sources = array();
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
$group = array();
$group[] = $form->createElement('radio', 'reset_password', null, get_lang('EnterPassword'), 2);
$group[] = $form->createElement(
    'password',
    'password',
    null,
    array('onkeydown' => 'javascript: password_switch_radio_button();')
);

$form->addGroup($group, 'password', null, null, false);
$form->addPasswordRule('password', 'password');

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

$display = isset($user_data['status']) && ($user_data['status'] == STUDENT || (isset($_POST['status']) && $_POST['status'] == STUDENT)) ? 'block' : 'none';

// Platform admin
if (api_is_platform_admin()) {
    $group = array();
    $group[] = $form->createElement('radio', 'platform_admin', null, get_lang('Yes'), 1);
    $group[] = $form->createElement('radio', 'platform_admin', null, get_lang('No'), 0);

    $user_data['status'] == 1 ? $display = 'block' : $display = 'none';

    $form->addElement('html', '<div id="id_platform_admin" style="display:'.$display.'">');
    $form->addGroup($group, 'admin', get_lang('PlatformAdmin'), null, false);
    $form->addElement('html', '</div>');
}

//Language
$form->addSelectLanguage('language', get_lang('Language'));

// Send email
$group = array();
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('Yes'), 1);
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('No'), 0);
$form->addGroup($group, 'mail', get_lang('SendMailToNewUser'), null, false);

// Registration User and Date
$creatorInfo = api_get_user_info($user_data['creator_id']);
$date = sprintf(get_lang('CreatedByXYOnZ'), 'user_information.php?user_id='.$user_data['creator_id'], $creatorInfo['username'], $user_data['registration_date']);
$form->addElement('label', get_lang('RegistrationDate'), $date);

if (!$user_data['platform_admin']) {
    // Expiration Date
    $form->addElement('radio', 'radio_expiration_date', get_lang('ExpirationDate'), get_lang('NeverExpires'), 0);
    $group = array();
    $group[] = $form->createElement('radio', 'radio_expiration_date', null, get_lang('Enabled'), 1);
    $group[] = $form->createElement('DateTimePicker', 'expiration_date', null, array('onchange' => 'javascript: enable_expiration_date();'));
    $form->addGroup($group, 'max_member_group', null, null, false);

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

if ($studentBossList) {
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
    true
);
$jquery_ready_content = $returnParams['jquery_ready_content'];

// the $jquery_ready_content variable collects all functions that will be load in the $(document).ready javascript function
$htmlHeadXtra[] = '<script>
$(document).ready(function(){
    '.$jquery_ready_content.'
});
</script>';

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
    $reset_password = intval($user['reset_password']);
    if ($reset_password == 2 && empty($user['password'])) {
        Display::addFlash(Display::return_message(get_lang('PasswordIsTooShort')));
        header('Location: '.api_get_self().'?user_id='.$user_id);
        exit();
    }

    $is_user_subscribed_in_course = CourseManager::is_user_subscribed_in_course($user['user_id']);

    if ($user['status'] == DRH && $is_user_subscribed_in_course) {
        $error_drh = true;
    } else {
        $picture_element = $form->getElement('picture');
        $picture = $picture_element->getValue();

        $picture_uri = $user_data['picture_uri'];
        if (isset($user['delete_picture']) && $user['delete_picture']) {
            $picture_uri = UserManager::delete_user_picture($user_id);
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
        $status = intval($user['status']);
        $platform_admin = intval($user['platform_admin']);
        $send_mail = intval($user['send_mail']);
        $reset_password = intval($user['reset_password']);
        $hr_dept_id = isset($user['hr_dept_id']) ? intval($user['hr_dept_id']) : null;
        $language = $user['language'];
        $address = isset($user['address']) ? $user['address'] : null;

        if ($user['radio_expiration_date'] == '1' && !$user_data['platform_admin']) {
            $expiration_date = $user['expiration_date'];
        } else {
            $expiration_date = null;
        }

        $active = $user_data['platform_admin'] ? 1 : intval($user['active']);

        //If the user is set to admin the status will be overwrite by COURSEMANAGER = 1
        if ($platform_admin == 1) {
            $status = COURSEMANAGER;
        }

        if (api_get_setting('login_is_email') == 'true') {
            $username = $email;
        }

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
            $address
        );

        if (isset($user['student_boss'])) {
            UserManager::subscribeUserToBossList($user_id, $user['student_boss']);
        }

        if (api_get_setting('openid_authentication') == 'true' && !empty($user['openid'])) {
            $up = UserManager::update_openid($user_id, $user['openid']);
        }
        $currentUserId = api_get_user_id();

        $userObj = api_get_user_entity($user_id);

        UserManager::add_user_as_admin($userObj);

        if ($user_id != $currentUserId) {
            if ($platform_admin == 1) {
                $userObj = api_get_user_entity($user_id);
                UserManager::add_user_as_admin($userObj);
            } else {
                UserManager::remove_user_admin($user_id);
            }
        }

        $extraFieldValue = new ExtraFieldValue('user');
        $extraFieldValue->saveFieldValues($user);

        $tok = Security::get_token();

        Display::addFlash(Display::return_message(get_lang('UserUpdated')));
        header('Location: user_list.php?sec_token='.$tok);
        exit();
    }
}

if ($error_drh) {
    Display::addFlash(Display::return_message(get_lang('StatusCanNotBeChangedToHumanResourcesManager'), 'error'));
}

$content = null;

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
