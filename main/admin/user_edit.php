<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.admin
 */
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\UserBundle\Entity\User;
use Chamilo\UserBundle\Form\UserType;

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : intval($_POST['user_id']);

api_protect_super_admin($user_id, null, true);
$is_platform_admin = api_is_platform_admin() ? 1 : 0;
$tool_name = get_lang('ModifyUserInfo');

$interbreadcrumb[] = array('url' => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => "user_list.php", "name" => get_lang('UserList'));

//$user = Container::getEntityManager()->getRepository('ChamiloUserBundle:User')->find($user_id);
$user_data = api_get_user_info($user_id, false, true);

$user_data['platform_admin'] = api_is_platform_admin_by_id($user_id);
$user_data['send_mail'] = 0;
$user_data['old_password'] = $user_data['password'];
//Convert the registration date of the user
//@todo remove the date_default_timezone_get() see UserManager::create_user function
$user_data['registration_date'] = api_get_local_time(
    $user_data['registration_date'],
    null,
    date_default_timezone_get()
);
unset($user_data['password']);
$extra_data = UserManager :: get_extra_user_data($user_id, true);
$user_data = array_merge($user_data, $extra_data);

// Create the form
$form = new FormValidator('user_edit', 'post', api_get_self().'?user_id='.$user_id);
$form->addElement('header', '', $tool_name);
$form->addElement('hidden', 'user_id', $user_id);

if (api_is_western_name_order()) {
    // First name
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
$form->addElement('text', 'email', get_lang('Email'), array('size' => '40'));
$form->addRule('email', get_lang('EmailWrong'), 'email');
/*if (api_get_setting('registration', 'email') == 'true') {
    $form->addRule('email', get_lang('EmailWrong'), 'required');
}*/

if (api_get_setting('profile.login_is_email') == 'true') {
    $form->addRule(
        'email',
        sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH),
        'maxlength',
        USERNAME_MAX_LENGTH
    );
    $form->addRule('email', get_lang('UserTaken'), 'username_available', $user_data['username']);
}

// OpenID
/*if (api_get_setting('openid_authentication') == 'true') {
    $form->addElement('text', 'openid', get_lang('OpenIDURL'), array('size' => '40'));
}*/

// Phone
$form->addElement('text', 'phone', get_lang('PhoneNumber'));

// Picture
$form->addElement('file', 'picture', get_lang('AddPicture'));
$allowed_picture_types = array('jpg', 'jpeg', 'png', 'gif');
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

if (api_get_setting('profile.login_is_email') != 'true') {
    $form->addElement('text', 'username', get_lang('LoginName'), array('maxlength' => USERNAME_MAX_LENGTH));
    $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule(
        'username',
        sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH),
        'maxlength',
        USERNAME_MAX_LENGTH
    );
    $form->addRule('username', get_lang('OnlyLettersAndNumbersAllowed'), 'username');
    $form->addRule('username', get_lang('UserTaken'), 'username_available', $user_data['username']);
}

// Password
$form->addElement('radio', 'reset_password', get_lang('Password'), get_lang('DontResetPassword'), 0);
$nb_ext_auth_source_added = 0;
if (isset($extAuthSource) && !empty($extAuthSource) && count($extAuthSource) > 0) {
    $auth_sources = array();
    foreach ($extAuthSource as $key => $info) {
        // @todo : make uniform external authentification configuration (ex : cas and external_login ldap)
        // Special case for CAS. CAS is activated from Chamilo > Administration > Configuration > CAS
        // extAuthSource always on for CAS even if not activated
        // same action for file user_add.php
        if (($key == CAS_AUTH_SOURCE && api_get_setting('cas_activate') === 'true') || ($key != CAS_AUTH_SOURCE)) {
            $auth_sources[$key] = $key;
            $nb_ext_auth_source_added++;
        }
    }
    if ($nb_ext_auth_source_added > 0) {
        // @todo check the radio button for external authentification and select the external authentification in the menu
        $group[] = $form->createElement('radio', 'reset_password', null, get_lang('ExternalAuthentication').' ', 3);
        $group[] = $form->createElement('select', 'auth_source', null, $auth_sources);
        $group[] = $form->createElement('static', '', '', '<br />');
        $form->addGroup($group, 'password', null, '', false);
    }
}

$form->addElement('radio', 'reset_password', null, get_lang('AutoGeneratePassword'), 1);
// before giving the form to reset the password, check the corresponding param
if (api_is_global_platform_admin() or api_get_setting('admins_can_set_users_pass')==='true') {
    $group = array();
    $group[] =$form->createElement('radio', 'reset_password', null, null, 2);
    $group[] =$form->createElement('password', 'password', null, array('onkeydown' => 'javascript: password_switch_radio_button();'));
    $form->addGroup($group, 'password', null, '', false);
}

// Status.
$status = api_get_user_roles();
unset($status[ANONYMOUS]);

$form->addElement(
    'select',
    'status',
    get_lang('Profile'),
    $status,
    array(
        'id' => 'status_select',
        'class' => 'chzn-select',
        'multiple' => 'multiple'
    )
);

$display = isset($user_data['status']) && ($user_data['status'] == STUDENT || (isset($_POST['status']) && $_POST['status'] == STUDENT)) ? 'block' : 'none';

//Language
$form->addElement('select_language', 'language', get_lang('Language'));

// Send email
$group = array();
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('Yes'), 1);
$group[] = $form->createElement('radio', 'send_mail', null, get_lang('No'), 0);
$form->addGroup($group, 'mail', get_lang('SendMailToNewUser'), '&nbsp;', false);

// Registration Date
$creatorInfo = api_get_user_info($user_data['creator_id']);
$date = sprintf(
    get_lang('CreatedByXYOnZ'),
    'user_information.php?user_id='.$user_data['creator_id'],
    $creatorInfo['username'],
    $user_data['registration_date']
);
$form->addElement(
    'label',
    get_lang('RegistrationDate'),
    $date
);

// Expiration Date
if (!$user_data['platform_admin']) {
	// Expiration Date
	$form->addElement('radio', 'radio_expiration_date', get_lang('ExpirationDate'), get_lang('NeverExpires'), 0);
	$group = array ();
	$group[] = $form->createElement('radio', 'radio_expiration_date', null, get_lang('On'), 1);
	$group[] = $form->createElement('date_time_picker', 'expiration_date', array('onchange' => 'javascript: enable_expiration_date();'));
	$form->addGroup($group, 'max_member_group', null, '', false);

	// Active account or inactive account
	$form->addElement('radio', 'active', get_lang('ActiveAccount'), get_lang('Active'), 1);
	$form->addElement('radio', 'active', '', get_lang('Inactive'), 0);
}
// Active account or inactive account

// EXTRA FIELDS
$extraField = new ExtraField('user');
$return_params = $extraField->set_extra_fields_in_form($form, $extra_data, 'user_edit', true, $user_id);
$jquery_ready_content = $return_params['jquery_ready_content'];

// the $jquery_ready_content variable collects all functions that will be load in the $(document).ready javascript function
$htmlHeadXtra[] = '<script>
$(document).ready(function(){
	'.$jquery_ready_content.'
});
</script>';


// Submit button
$form->addElement('style_submit_button', 'submit', get_lang('ModifyInformation'), 'class="save"');

// Set default values
$user_data['reset_password'] = 0;
$expiration_date = $user_data['expiration_date'];

if ($expiration_date == '0000-00-00 00:00:00') {
    $user_data['radio_expiration_date'] = 0;
} else {
    $user_data['radio_expiration_date'] = 1;
}

$user = Database::getManager()->getRepository('ChamiloUserBundle:User')->find($user_data['user_id']);
$roles = $user->getGroups();

$roleToArray  = array();
if (!empty($roles)) {
    foreach($roles as $role) {
        $roleId = $role->getId();
        $roleToArray[] = $roleId;
    }
}

$user_data['status'] = $roleToArray;
$form->setDefaults($user_data);

$error_drh = false;
// Validate form
if ($form->validate()) {

    $user = $form->getSubmitValues();
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
                $_FILES['picture']['tmp_name']
            );
        }

        $lastname = $user['lastname'];
        $firstname = $user['firstname'];
        $password = $user['password'];
        $auth_source = null;
        $official_code = $user['official_code'];
        $email = $user['email'];
        $phone = $user['phone'];
        $username = $user['username'];
        $status = $user['status'];
        $send_mail = intval($user['send_mail']);
        $reset_password = intval($user['reset_password']);
        $hr_dept_id = isset($user['hr_dept_id']) ? intval($user['hr_dept_id']) : null;
        $language = $user['language'];

        if (isset($user['radio_expiration_date']) && $user['radio_expiration_date'] == '1') {
            $expiration_date = new \DateTime($user['expiration_date']);
        } else {
            $expiration_date = null;
        }

        $active = isset($user['active']) ? intval($user['active']) : 0;

        if (api_get_setting('profile.login_is_email') == 'true') {
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
            $reset_password
        );

        /*if (api_get_setting('openid_authentication') == 'true' && !empty
            ($user['openid'])) {
            $up = UserManager::update_openid($user_id, $user['openid']);
        }*/

        // Using the extra field value obj
        $extraFieldValues = new ExtraFieldValue('user');
        $extraFieldValues->save_field_values($user);

        $tok = Security::get_token();
/*
        header(
            'Location: user_list.php?action=show_message&message='.urlencode(get_lang('UserUpdated')).'&sec_token='.$tok
        );
        exit();*/
    }
}

$message = null;
if ($error_drh) {
    $err_msg = get_lang('StatusCanNotBeChangedToHumanResourcesManager');
    $message = Display::return_message($err_msg, 'error');
}

// USER PICTURE
$image_path = UserManager::get_user_picture_path_by_id($user_id, 'web');
$image_dir = $image_path['dir'];
$image = $image_path['file'];
$image_file = ($image != '' ? $image_dir.$image : api_get_path(WEB_IMG_PATH).'unknown.jpg');
$image_size = api_getimagesize($image_file);

// get the path,width and height from original picture
$big_image = $image_dir.'big_'.$image;
$big_image_size = api_getimagesize($big_image);
$big_image_width = $big_image_size['width'];
$big_image_height = $big_image_size['height'];
$url_big_image = $big_image.'?rnd='.time();

// Display form
$content = $form->return_form();

$em = Container::getEntityManager();
$request = Container::getRequest();

$user = new User();
if (!empty($user_id)) {
    $user = $em->getRepository('ChamiloUserBundle:User')->find($user_id);
}

$builder = Container::getFormFactory()->createBuilder(
    new UserType(Container::getSecurity()),
    $user
);

$form = $builder->getForm();
$form->handleRequest($request);

if ($form->isValid()) {
    $em->flush();
    Container::addFlash(get_lang('Updated'));
    $url = Container::getRouter()->generate(
        'main',
        array('name' => 'admin/user_list.php')
    );
    header('Location: '.$url);
    exit;
}
$urlAction = api_get_self().'?user_id='.$user_id;

echo Container::getTemplate()->render(
    'ChamiloCoreBundle:User:create.html.twig',
    array(
        'form' => $form->createView(),
        'url' => $urlAction
    )
);
