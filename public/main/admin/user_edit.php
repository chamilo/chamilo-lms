<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Entity\CourseRelUser;
use Chamilo\CoreBundle\Entity\User;
use Chamilo\CoreBundle\Entity\UserAuthSource;
use Chamilo\CoreBundle\Enums\ActionIcon;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Helpers\AuthenticationConfigHelper;
use Chamilo\CoreBundle\Repository\CourseRelUserRepository;
use ChamiloSession as Session;

$cidReset = true;
require_once __DIR__.'/../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);

$user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : (int) $_POST['user_id'];
api_protect_super_admin($user_id, null, true);
$is_platform_admin = api_is_platform_admin() ? 1 : 0;
$userInfo = api_get_user_info($user_id);
$userObj = api_get_user_entity($user_id);
$illustrationRepo = Container::getIllustrationRepository();

/** @var AuthenticationConfigHelper $authenticationConfigHelper */
$authenticationConfigHelper = Container::$container->get(AuthenticationConfigHelper::class);

$accessUrl = Container::getAccessUrlUtil()->getCurrent();

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
    if (confirm("'.get_lang('Are you sure to delete').' " + name + " ?")) {
            document.forms["profile"].submit();
    } else {
        return false;
    }
}
</script>';

$advancedPanelOpen = !empty($_POST);
$tool_name = get_lang('Edit user information');

$interbreadcrumb[] = ['url' => 'index.php', 'name' => get_lang('Administration')];
$interbreadcrumb[] = ['url' => 'user_list.php', 'name' => get_lang('User list')];

$table_user = Database::get_main_table(TABLE_MAIN_USER);
$table_admin = Database::get_main_table(TABLE_MAIN_ADMIN);
$sql = "SELECT u.*, a.user_id AS is_admin FROM $table_user u
        LEFT JOIN $table_admin a ON a.user_id = u.id
        WHERE u.id = '".$user_id."'";
$res = Database::query($sql);
if (1 != Database::num_rows($res)) {
    header('Location: user_list.php');
    exit;
}

$user_data = Database::fetch_assoc($res);

$user_data['platform_admin'] = is_null($user_data['is_admin']) ? 0 : 1;
$user_data['send_mail'] = 0;
$user_data['old_password'] = $user_data['password'];
//Convert the registration date of the user

$user_data['created_at'] = api_get_local_time($user_data['created_at']);
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
    $form->addElement('text', 'firstname', get_lang('First name'));
    $form->applyFilter('firstname', 'html_filter');
    $form->applyFilter('firstname', 'trim');
    $form->addRule('firstname', get_lang('Required field'), 'required');
    // Lastname
    $form->addElement('text', 'lastname', get_lang('Last name'));
    $form->applyFilter('lastname', 'html_filter');
    $form->applyFilter('lastname', 'trim');
    $form->addRule('lastname', get_lang('Required field'), 'required');
} else {
    // Lastname
    $form->addElement('text', 'lastname', get_lang('Last name'));
    $form->applyFilter('lastname', 'html_filter');
    $form->applyFilter('lastname', 'trim');
    $form->addRule('lastname', get_lang('Required field'), 'required');
    // Firstname
    $form->addElement('text', 'firstname', get_lang('First name'));
    $form->applyFilter('firstname', 'html_filter');
    $form->applyFilter('firstname', 'trim');
    $form->addRule('firstname', get_lang('Required field'), 'required');
}

// Official code
$form->addElement('text', 'official_code', get_lang('Code'), ['size' => '40']);
$form->applyFilter('official_code', 'html_filter');
$form->applyFilter('official_code', 'trim');

// e-mail
$form->addElement('text', 'email', get_lang('E-mail'));
$form->addEmailRule('email');
if ('true' == api_get_setting('registration', 'email')) {
    $form->addRule('email', get_lang('Required field'), 'required');
}

if ('true' == api_get_setting('login_is_email')) {
    $form->addRule(
        'email',
        sprintf(get_lang('The login needs to be maximum %s characters long'), (string) User::USERNAME_MAX_LENGTH),
        'maxlength',
        User::USERNAME_MAX_LENGTH
    );
    $form->addRule('email', get_lang('This login is already in use'), 'username_available', $user_data['username']);
}

// Phone
$form->addElement('text', 'phone', get_lang('Phone number'));

// Picture
$form->addFile(
    'picture',
    get_lang('Add image'),
    ['id' => 'picture', 'class' => 'picture-form', 'crop_image' => true, 'crop_ratio' => '1 / 1']
);
$allowed_picture_types = api_get_supported_image_extensions(false);

$form->addRule(
    'picture',
    get_lang('Only PNG, JPG or GIF images allowed').' ('.implode(',', $allowed_picture_types).')',
    'filetype',
    $allowed_picture_types
);

$hasPicture = $illustrationRepo->hasIllustration($userObj);

if ($hasPicture) {
    $picture = $illustrationRepo->getIllustrationUrl($userObj);
    $form->addElement('html', '<img src="'.$picture.'" class="w-32 h-32" />');
    $form->addElement('checkbox', 'delete_picture', '', get_lang('Remove picture'));
}

// Username
if ('true' !== api_get_setting('login_is_email')) {
    $form->addElement('text', 'username', get_lang('Username'), ['maxlength' => User::USERNAME_MAX_LENGTH]);
    $form->addRule('username', get_lang('Required field'), 'required');
    $form->addRule(
        'username',
        sprintf(get_lang('The login needs to be maximum %s characters long'), (string) User::USERNAME_MAX_LENGTH),
        'maxlength',
        User::USERNAME_MAX_LENGTH
    );
    $form->addRule('username', get_lang('Only letters and numbers allowed'), 'username');
    $form->addRule('username', get_lang('This login is already in use'), 'username_available', $user_data['username']);
}

$extAuthSource = $authenticationConfigHelper->getAuthSourceAuthentications($accessUrl);

if (!empty($extAuthSource) && count($extAuthSource) > 0) {
    foreach ($userInfo['auth_sources'] as $userAuthSource) {
        $form->addLabel(
            get_lang('External authentification'),
            $userAuthSource
        );
    }
}

// Password
$form->addElement('radio', 'reset_password', get_lang('Password'), get_lang('Don\'t reset password'), 0);
$nb_ext_auth_source_added = 0;
if (!empty($extAuthSource) && count($extAuthSource) > 0) {
    $auth_sources = [];
    foreach ($extAuthSource as $key) {
        // @todo : make uniform external authentication configuration (ex : cas and external_login ldap)
        // Special case for CAS. CAS is activated from Chamilo > Administration > Configuration > CAS
        // extAuthSource always on for CAS even if not activated
        // same action for file user_add.php
        if ((UserAuthSource::CAS == $key && 'true' === api_get_setting('cas_activate')) || (UserAuthSource::CAS != $key)) {
            $auth_sources[$key] = $key;
            $nb_ext_auth_source_added++;
        }
    }
    if ($nb_ext_auth_source_added > 0) {
        // @todo check the radio button for external authentification and select the external authentication in the menu
        $group[] = $form->createElement('radio', 'reset_password', null, get_lang('External authentification').' ', 3);
        $group[] = $form->createElement('select', 'auth_source', null, $auth_sources, ['multiple' => 'multiple']);
        $group[] = $form->createElement('static', '', '', '<br />', []);
        $form->addGroup($group, 'password', null, null, false);
    }
}
$form->addElement('radio', 'reset_password', null, get_lang('Automatically generate a new password'), 1);
$group = [];
$group[] = $form->createElement('radio', 'reset_password', null, get_lang('Enter password'), 2);
$group[] = $form->createElement(
    'password',
    'password',
    null,
    [
        'id' => 'password',
        'onkeydown' => 'javascript: password_switch_radio_button();',
        'show_hide' => true,
    ]
);

$form->addGroup($group, 'password', null, null, false);
$form->addPasswordRule('password', 'password');

$form->addElement(
    'select',
    'roles',
    get_lang('Roles'),
    api_get_roles(),
    [
        'multiple' => 'multiple',
        'size' => 8,
    ]
);
$form->addRule('roles', get_lang('Required field'), 'required');

$display = 'none';
if (isset($_POST['roles']) && is_array($_POST['roles'])) {
    $norm = array_map('api_normalize_role_code', $_POST['roles']);
    $display = (in_array('ROLE_TEACHER', $norm, true) || in_array('ROLE_SESSION_MANAGER', $norm, true)) ? 'block' : 'none';
}

// Platform admin
if (api_is_platform_admin()) {
    $group = [];
    $group[] = $form->createElement('radio', 'platform_admin', null, get_lang('Yes'), 1);
    $group[] = $form->createElement('radio', 'platform_admin', null, get_lang('No'), 0);

    1 == $user_data['status'] ? $display = 'block' : $display = 'none';

    $form->addElement('html', '<div id="id_platform_admin" style="display:'.$display.'">');
    $form->addGroup($group, 'admin', get_lang('Administration'), null, false);
    $form->addElement('html', '</div>');
}

//Language
$form->addSelectLanguage('locale', get_lang('Language'));

// Send email
$group = [];
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('Yes'), 1);
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('No'), 0);
$form->addGroup($group, 'mail', get_lang('Send mail to new user'), null, false);

// Registration User and Date
$creatorInfo = api_get_user_info($user_data['creator_id']);
if (!empty($creatorInfo)) {
    $date = sprintf(
        get_lang('Create by <a href="%s">%s</a> on %s'),
        'user_information.php?user_id='.$user_data['creator_id'],
        $creatorInfo['username'],
        $user_data['created_at']
    );
    $form->addElement('label', get_lang('Registration date'), $date);
}

$isUserEditingOwnAccount = ($user_data['id'] === api_get_user_id());
$hideFields = $isUserEditingOwnAccount || USER_SOFT_DELETED == $user_data['active'];
if (!$hideFields) {
    // Expiration Date
    $form->addElement('radio', 'radio_expiration_date', get_lang('Expiration date'), get_lang('Never expires'), 0);
    $group = [];
    $group[] = $form->createElement('radio', 'radio_expiration_date', null, get_lang('Enabled'), 1);
    $group[] = $form->createElement(
        'DateTimePicker',
        'expiration_date',
        null,
        ['onchange' => 'javascript: enable_expiration_date();']
    );
    $form->addGroup($group, 'max_member_group', null, null, false);

    // active account or inactive account
    $form->addElement('radio', 'active', get_lang('Account'), get_lang('active'), 1);
    $form->addElement('radio', 'active', '', get_lang('inactive'), 0);
} else {
    $form->addElement('hidden', 'active', $user_data['active']);
    $form->addElement('hidden', 'expiration_date', $user_data['expiration_date']);
}
$studentBossList = UserManager::getStudentBossList($user_id);

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

$user_data['student_boss'] = $studentBossList;
$form->addMultiSelect('student_boss', get_lang('Superior (n+1)'), $studentBossToSelect);

$form->addElement('html', Display::advancedPanelStart(
    'advanced_params',
    get_lang('Advanced settings'),
    $advancedPanelOpen
));

// EXTRA FIELDS
$extraField = new ExtraField('user');
$returnParams = $extraField->addElements(
    $form,
    $user_id,
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

$form->addEmailTemplate(['user_edit_content.tpl']);
$form->addElement('html', Display::advancedPanelEnd());

// the $jqueryReadyContent variable collects all functions that will be load in the
$htmlHeadXtra[] = '<script>
$(function () {
    '.$jqueryReadyContent.'
});
</script>';

// Freeze user conditions, admin cannot updated them
$extraConditions = api_get_setting('profile.show_conditions_to_user', true);
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
$user_data['auth_source'] = $userInfo['auth_sources'];

if (!$hideFields) {
    $expiration_date = $user_data['expiration_date'];
    if (empty($expiration_date)) {
        $user_data['radio_expiration_date'] = 0;
        $user_data['expiration_date'] = api_get_local_time();
    } else {
        $user_data['radio_expiration_date'] = 1;
        $user_data['expiration_date'] = api_get_local_time($expiration_date);
    }
}

$roleOptions = api_get_roles();
$optionKeyByCanon = [];
foreach ($roleOptions as $optKey => $label) {
    $optionKeyByCanon[api_normalize_role_code((string) $optKey)] = $optKey;
}

$userCanonRoles = array_map('api_normalize_role_code', (array) $userObj->getRoles());
$selectedOptionKeys = [];
foreach ($userCanonRoles as $canon) {
    if (isset($optionKeyByCanon[$canon])) {
        $selectedOptionKeys[] = $optionKeyByCanon[$canon];
    }
}

$user_data['roles'] = array_values(array_unique($selectedOptionKeys));
$form->setDefaults($user_data);

$error_drh = false;
// Validate form
if ($form->validate()) {
    $user = $form->getSubmitValues(1);
    $reset_password = (int) $user['reset_password'];
    if (2 == $reset_password && empty($user['password'])) {
        Display::addFlash(Display::return_message(get_lang('The password is too short')));
        header('Location: '.api_get_self().'?user_id='.$user_id);
        exit();
    }

    $roles = $user['roles'] ?? [];
    $roles = array_values(array_unique(array_map('api_normalize_role_code', $roles)));
    $newStatus = api_status_from_roles($roles);
    if ($newStatus === DRH && CourseManager::is_user_subscribed_in_course((int) $user_id)) {
        $error_drh = true;
    } else {
        $picture_element = $form->getElement('picture');
        $picture = $picture_element->getValue();
        $picture_uri = $user_data['picture_uri'];
        if (isset($user['delete_picture']) && $user['delete_picture']) {
            $picture_uri = UserManager::deleteUserPicture($user_id);
        }
        if (!empty($picture['name'])) {
            $picture_uri = UserManager::deleteUserPicture($user_id);
            $request = Container::getRequest();
            $file = $request->files->get('picture');
            $picture_uri = UserManager::update_user_picture(
                $user_id,
                $file,
                $user['picture_crop_result']
            );
        }

        $lastname = $user['lastname'];
        $firstname = $user['firstname'];
        $password = $user['password'];
        $auth_source = $user['auth_source'] ?? ($userInfo['auth_source'] ?? []);
        $official_code = $user['official_code'];
        $email = $user['email'];
        $phone = $user['phone'];
        $username = $user['username'] ?? $userInfo['username'];
        $platform_admin = 0;
        // Only platform admin can change user status to admin.
        if (api_is_platform_admin()) {
            $platform_admin = (int) $user['platform_admin'];
        }
        $send_mail = (int) $user['send_mail'];
        $reset_password = (int) $user['reset_password'];
        $hr_dept_id = isset($user['hr_dept_id']) ? intval($user['hr_dept_id']) : null;
        $language = $user['locale'];
        $address = $user['address'] ?? null;
        $expiration_date = !empty($user['expiration_date']) ? $user['expiration_date'] : null;
        if (isset($user['radio_expiration_date']) && 0 === (int) $user['radio_expiration_date']) {
            $expiration_date = null;
        }
        $active = isset($user['active']) ? (int) $user['active'] : USER_SOFT_DELETED;

        if ('true' === api_get_setting('login_is_email')) {
            $username = $email;
        }

        $template = $user['email_template_option'] ?? [];
        if ((int) ($user['platform_admin'] ?? 0) === 1) {
            $newStatus = COURSEMANAGER;
        }

        $incompatible = false;
        $conflicts = [];
        $oldStatus = (int) $userObj->getStatus();
        $em = Database::getManager();

        if ($oldStatus !== $newStatus) {
            $isNowStudent = ($newStatus === STUDENT);
            if ($isNowStudent) {

                /** @var CourseRelUserRepository $cruRepo */
                $cruRepo = $em->getRepository(CourseRelUser::class);
                $courseTeacherCount = $cruRepo->countTaughtCoursesForUser($userObj);
                $coachSessions = $userObj->getSessionsAsGeneralCoach();
                $adminSessions = $userObj->getSessionsAsAdmin();

                if ($courseTeacherCount > 0) {
                    $conflicts[] = get_lang('User is teacher in some courses');
                }

                if (!empty($coachSessions)) {
                    $conflicts[] = get_lang('User is general coach in some sessions');
                }

                if (!empty($adminSessions)) {
                    $conflicts[] = get_lang('User is session admin in some sessions');
                }

                if (!empty($conflicts)) {
                    $incompatible = true;
                }
            }
        }
        if ($incompatible) {
            $conflictMessage = Display::return_message(
                get_lang('Role change denied due to incompatible current assignments:').'<br>- '.implode('<br>- ', $conflicts),
                'error',
                false
            );

            $content = $conflictMessage;
            $content .= $form->returnForm();

            $tpl = new Template($tool_name);
            $tpl->assign('content', $content);
            $tpl->display_one_col_template();
            exit;
        }

        UserManager::update_user(
            $user_id,
            $firstname,
            $lastname,
            $username,
            $password,
            $auth_source,
            $email,
            $newStatus,
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

        $studentBossListSent = $user['student_boss'] ?? [];
        UserManager::subscribeUserToBossList(
            $user_id,
            $studentBossListSent,
            true
        );

        $currentUserId = api_get_user_id();

        if ($user_id != $currentUserId) {
            if (1 == $platform_admin) {
                UserManager::addUserAsAdmin($userObj);
            } else {
                UserManager::removeUserAdmin($userObj);
            }
        }

        $repo = Container::getUserRepository();
        /* @var User $userEntity */
        $userEntity = $repo->find($user_id);
        if ($userEntity) {
            $userEntity->setRoles($roles);
            $repo->updateUser($userEntity);
        }

        if (api_has_admin_role($roles)) {
            UserManager::addUserAsAdmin($userEntity);
        } else {
            UserManager::removeUserAdmin($userEntity);
        }

        $extraFieldValue = new ExtraFieldValue('user');
        $extraFieldValue->saveFieldValues($user);
        $userInfo = api_get_user_info($user_id);
        $message = get_lang('User updated').': '.Display::url(
                $userInfo['complete_name_with_username'],
                api_get_path(WEB_CODE_PATH).'admin/user_edit.php?user_id='.$user_id
            );

        Session::erase('system_timezone');

        Display::addFlash(Display::return_message($message, 'normal', false));
        header('Location: user_list.php');
        exit();
    }
}

if ($error_drh) {
    Display::addFlash(
        Display::return_message(
            get_lang('The status of this user cannot be changed to human resources manager.'),
            'error'
        )
    );
}

$actions = [
    Display::url(
        Display::getMdiIcon(ActionIcon::INFORMATION, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Information')),
        api_get_path(WEB_CODE_PATH).'admin/user_information.php?user_id='.$user_id
    ),
    Display::url(
        Display::getMdiIcon(ActionIcon::LOGIN_AS, 'ch-tool-icon', null, ICON_SIZE_MEDIUM, get_lang('Login as')),
        api_get_path(WEB_CODE_PATH).
        'admin/user_list.php?action=login_as&user_id='.$user_id.'&sec_token='.Security::getTokenFromSession()
    ),
];

$content = Display::toolbarAction('toolbar-user-information', [implode(PHP_EOL, $actions)]);

$bigImage = UserManager::getUserPicture($user_id, USER_IMAGE_SIZE_ORIGINAL);
$normalImage = UserManager::getUserPicture($user_id, USER_IMAGE_SIZE_BIG);

$content .= $form->returnForm();

$tpl = new Template($tool_name);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
