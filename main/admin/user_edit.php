<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/

// Language files that should be included
$language_file = array('admin', 'registration');

$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

$htmlHeadXtra[] = '
<script type="text/javascript">
<!--
function enable_expiration_date() { //v2.0
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
	if(document.getElementById("status_select").value=='.COURSEMANAGER.') {
		document.getElementById("id_platform_admin").style.display="block";
	} else if (document.getElementById("status_select").value=='.STUDENT.') {
		document.getElementById("id_platform_admin").style.display="none";
	} else {
		document.getElementById("id_platform_admin").style.display="none";
	}
}

function show_image(image,width,height) {
	width = parseInt(width) + 20;
	height = parseInt(height) + 20;
	window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \' , resizable=0\');
}
//-->
</script>';

$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'fileManage.lib.php';
require_once $libpath.'fileUpload.lib.php';
require_once $libpath.'mail.lib.inc.php';

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : intval($_POST['user_id']);
$noPHP_SELF = true;
$tool_name = get_lang('ModifyUserInfo');

$interbreadcrumb[] = array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array('url' => "user_list.php","name" => get_lang('UserList'));

$table_user = Database::get_main_table(TABLE_MAIN_USER);
$table_admin = Database::get_main_table(TABLE_MAIN_ADMIN);
$sql = "SELECT u.*, a.user_id AS is_admin FROM $table_user u LEFT JOIN $table_admin a ON a.user_id = u.user_id WHERE u.user_id = '".$user_id."'";
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

//@todo remove the date_default_timezone_get() see UserManager::create_user function
$user_data['registration_date'] = api_get_local_time($user_data['registration_date'], null, date_default_timezone_get());
unset($user_data['password']);

$user_data = array_merge($user_data, Usermanager :: get_extra_user_data($user_id, true));

// Create the form
$form = new FormValidator('user_edit', 'post', '', '', array('style' => 'width: 60%; float: '.($text_dir == 'rtl' ? 'right;' : 'left;')));
$form->addElement('header', '', $tool_name);
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
$form->addElement('text', 'email', get_lang('Email'), array('size' => '40'));
$form->addRule('email', get_lang('EmailWrong'), 'email');
$form->addRule('email', get_lang('EmailWrong'), 'required');

// OpenID
if (api_get_setting('openid_authentication') == 'true') {
	$form->addElement('text', 'openid', get_lang('OpenIDURL'), array('size' => '40'));
}

// Phone
$form->addElement('text', 'phone', get_lang('PhoneNumber'));

// Picture
$form->addElement('file', 'picture', get_lang('AddPicture'));
$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
if (strlen($user_data['picture_uri']) > 0) {
	$form->addElement('checkbox', 'delete_picture', '', get_lang('DelImage'));
}

// Username
$form->addElement('text', 'username', get_lang('LoginName'), array('maxlength' => USERNAME_MAX_LENGTH));
$form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
$form->addRule('username', get_lang('OnlyLettersAndNumbersAllowed'), 'username');
$form->addRule('username', get_lang('UserTaken'), 'username_available', $user_data['username']);

// Password
$form->addElement('radio', 'reset_password', get_lang('Password'), get_lang('DontResetPassword'), 0);
if (count($extAuthSource) > 0)
{
	$group[] =& HTML_QuickForm::createElement('radio', 'reset_password', null, get_lang('ExternalAuthentication').' ', 3);
	$auth_sources = array();
	foreach($extAuthSource as $key => $info) {
		$auth_sources[$key] = $key;
	}
	$group[] =& HTML_QuickForm::createElement('select', 'auth_source', null, $auth_sources);
	$group[] =& HTML_QuickForm::createElement('static', '', '', '<br />');
	$form->addGroup($group, 'password', null, '', false);
}
$form->addElement('radio', 'reset_password', null, get_lang('AutoGeneratePassword'), 1);
$group = array();
$group[] =& HTML_QuickForm::createElement('radio', 'reset_password', null, null, 2);
$group[] =& HTML_QuickForm::createElement('password', 'password', null, array('onkeydown' => 'javascript: password_switch_radio_button();'));
$form->addGroup($group, 'password', null, '', false);

// Status
$status = array();
$status[COURSEMANAGER] 	= get_lang('Teacher');
$status[STUDENT] 		= get_lang('Learner');
$status[DRH] 			= get_lang('Drh');
$status[SESSIONADMIN] 	= get_lang('SessionsAdmin');

$form->addElement('select', 'status', get_lang('Profile'), $status, array('id' => 'status_select', 'onchange' => 'javascript: display_drh_list();','class'=>'chzn-select'));

//Language
$form->addElement('select_language', 'language', get_lang('Language'));

$display = $user_data['status'] == STUDENT || $_POST['status'] == STUDENT ? 'block' : 'none';

/*
$form->addElement('html', '<div id="drh_list" style="display:'.$display.';">');
$drh_select = $form->addElement('select', 'hr_dept_id', get_lang('Drh'), array(), 'id="drh_select"');
$drh_list = UserManager :: get_user_list(array('status' => DRH), api_sort_by_first_name() ? array('firstname', 'lastname') : array('lastname', 'firstname'));

if (count($drh_list) == 0) {
	$drh_select->addOption('- '.get_lang('ThereIsNotStillAResponsible', '').' -', 0);
} else {
	$drh_select->addOption('- '.get_lang('SelectAResponsible').' -', 0);
}

foreach($drh_list as $drh) {
	$drh_select->addOption(api_get_person_name($drh['firstname'], $drh['lastname']), $drh['user_id']);
}
$form->addElement('html', '</div>');
*/

// Platform admin
// Only when changing another user!
//if ($user_id != $_user['user_id']) {
	$group = array();
	$group[] =& HTML_QuickForm::createElement('radio', 'platform_admin', null, get_lang('Yes'), 1);
	$group[] =& HTML_QuickForm::createElement('radio', 'platform_admin', null, get_lang('No'), 0);

	$user_data['status'] == 1 ? $display = 'block':$display = 'none';

	$form->addElement('html', '<div id="id_platform_admin" style="display:'.$display.'">');
	$form->addGroup($group, 'admin', get_lang('PlatformAdmin'), null, false);
	$form->addElement('html', '</div>');
//}

// Send email
$group = array();
$group[] =& HTML_QuickForm::createElement('radio', 'send_mail', null, get_lang('Yes'), 1);
$group[] =& HTML_QuickForm::createElement('radio', 'send_mail', null, get_lang('No'), 0);
$form->addGroup($group, 'mail', get_lang('SendMailToNewUser'), '&nbsp;', false);

// Registration Date
$form->addElement('static', 'registration_date', get_lang('RegistrationDate'), $user_data['registration_date']);

if (!$user_data['platform_admin']) {
	// Expiration Date
	$form->addElement('radio', 'radio_expiration_date', get_lang('ExpirationDate'), get_lang('NeverExpires'), 0);
	$group = array ();
	$group[] = & $form->createElement('radio', 'radio_expiration_date', null, get_lang('On'), 1);
	$group[] = & $form->createElement('datepicker', 'expiration_date', null, array('form_name' => $form->getAttribute('name'), 'onchange' => 'javascript: enable_expiration_date();'));
	$form->addGroup($group, 'max_member_group', null, '', false);

	// Active account or inactive account
	$form->addElement('radio', 'active', get_lang('ActiveAccount'), get_lang('Active'), 1);
	$form->addElement('radio', 'active', '', get_lang('Inactive'), 0);
}


// EXTRA FIELDS

$extra = UserManager::get_extra_fields(0, 50, 5, 'ASC', true);
foreach ($extra as $id => $field_details) {
	/*if ($field_details[6] == 0) {
		continue;
	}*/
	switch ($field_details[2]) {
		case USER_FIELD_TYPE_TEXT:
			$form->addElement('text', 'extra_'.$field_details[1], $field_details[3], array('size' => 40));
			$form->applyFilter('extra_'.$field_details[1], 'stripslashes');
			$form->applyFilter('extra_'.$field_details[1], 'trim');
			break;
		case USER_FIELD_TYPE_TEXTAREA:
			$form->add_html_editor('extra_'.$field_details[1], $field_details[3], false, false, array('ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130'));
			//$form->addElement('textarea', 'extra_'.$field_details[1], $field_details[3], array('size' => 80));
			$form->applyFilter('extra_'.$field_details[1], 'stripslashes');
			$form->applyFilter('extra_'.$field_details[1], 'trim');
			break;
		case USER_FIELD_TYPE_RADIO:
			$group = array();
			foreach ($field_details[9] as $option_id => $option_details) {
				$options[$option_details[1]] = $option_details[2];
				$group[] =& HTML_QuickForm::createElement('radio', 'extra_'.$field_details[1], $option_details[1], $option_details[2].'<br />', $option_details[1]);
			}
			$form->addGroup($group, 'extra_'.$field_details[1], $field_details[3], '');
			break;
		case USER_FIELD_TYPE_SELECT:
            $get_lang_variables = false;
            if (in_array($field_details[1], array('mail_notify_message','mail_notify_invitation', 'mail_notify_group_message'))) {
                $get_lang_variables = true;
            }
            $options = array();
            foreach($field_details[9] as $option_id => $option_details) {
                if ($get_lang_variables) {
                    $option_details[2] = get_lang($option_details[2]);
                }
                $options[$option_details[1]] = $option_details[2];
            }
            if ($get_lang_variables) {
                $field_details[3] = get_lang($field_details[3]);
            }
            $form->addElement('select', 'extra_'.$field_details[1], $field_details[3], $options, array('class'=>'chzn-select', 'id'=>'extra_'.$field_details[1]));		
			break;
		case USER_FIELD_TYPE_SELECT_MULTIPLE:
			$options = array();
			foreach ($field_details[9] as $option_id => $option_details) {
				$options[$option_details[1]] = $option_details[2];
			}
			$form->addElement('select', 'extra_'.$field_details[1], $field_details[3], $options, array('multiple' => 'multiple'));
			break;
		case USER_FIELD_TYPE_DATE:
			$form->addElement('datepickerdate', 'extra_'.$field_details[1], $field_details[3], array('form_name' => 'user_edit'));
			$form->_elements[$form->_elementIndex['extra_'.$field_details[1]]]->setLocalOption('minYear', 1900);
			$defaults['extra_'.$field_details[1]] = date('Y-m-d 12:00:00');
			$form -> setDefaults($defaults);
			$form->applyFilter('theme', 'trim');
			break;
		case USER_FIELD_TYPE_DATETIME:
			$form->addElement('datepicker', 'extra_'.$field_details[1], $field_details[3], array('form_name' => 'user_edit'));
			$form->_elements[$form->_elementIndex['extra_'.$field_details[1]]]->setLocalOption('minYear', 1900);
			$defaults['extra_'.$field_details[1]] = date('Y-m-d 12:00:00');
			$form -> setDefaults($defaults);
			$form->applyFilter('theme', 'trim');
			break;
	}
}

// Submit button
$form->addElement('style_submit_button', 'submit', get_lang('ModifyInformation'), 'class="save"');

// Set default values
$user_data['reset_password'] = 0;
$expiration_date = $user_data['expiration_date'];
if ($expiration_date == '0000-00-00 00:00:00') {
	$user_data['radio_expiration_date'] = 0;
	$user_data['expiration_date'] = array();
	$user_data['expiration_date']['d'] = date('d');
	$user_data['expiration_date']['F'] = date('m');
	$user_data['expiration_date']['Y'] = date('Y');
} else {
	$user_data['radio_expiration_date'] = 1;
	$user_data['expiration_date'] = array();
	$user_data['expiration_date']['d'] = substr($expiration_date, 8, 2);
	$user_data['expiration_date']['F'] = substr($expiration_date, 5, 2);
	$user_data['expiration_date']['Y'] = substr($expiration_date, 0, 4);
}
$form->setDefaults($user_data);

$error_drh = false;
// Validate form
if ( $form->validate()) {

	$user = $form->exportValues();
	$is_user_subscribed_in_course = CourseManager::is_user_subscribed_in_course($user['user_id']);

	if ($user['status'] == DRH && $is_user_subscribed_in_course) {
		$error_drh = true;
	} else {
		$picture_element = & $form->getElement('picture');
		$picture = $picture_element->getValue();

		$picture_uri = $user_data['picture_uri'];
		if ($user['delete_picture']) {
			$picture_uri = UserManager::delete_user_picture($user_id);
			}
		elseif (!empty($picture['name'])) {
			$picture_uri = UserManager::update_user_picture($user_id, $_FILES['picture']['name'], $_FILES['picture']['tmp_name']);
		}

		$lastname = $user['lastname'];
		$firstname = $user['firstname'];
		$official_code = $user['official_code'];
		$email = $user['email'];
		$phone = $user['phone'];
		$username = $user['username'];
		$status = intval($user['status']);
		$platform_admin = intval($user['platform_admin']);
		$send_mail = intval($user['send_mail']);
		$reset_password = intval($user['reset_password']);
		$hr_dept_id = intval($user['hr_dept_id']);
		$language = $user['language'];
		if ($user['radio_expiration_date'] == '1' && !$user_data['platform_admin']) {
			$expiration_date=$user['expiration_date'];
		} else {
			$expiration_date='0000-00-00 00:00:00';
		}
		$active = $user_data['platform_admin'] ? 1 : intval($user['active']);

		if ($reset_password == 0) {
			$password = null;
			$auth_source = $user_data['auth_source'];
		}
		elseif($reset_password == 1) {
			$password = api_generate_password();
			$auth_source = PLATFORM_AUTH_SOURCE;
		}
		elseif($reset_password == 2) {
			$password = $user['password'];
			$auth_source = PLATFORM_AUTH_SOURCE;
		}
		elseif($reset_password == 3) {
			$password = $user['password'];
			$auth_source = $user['auth_source'];
		}
		UserManager::update_user($user_id, $firstname, $lastname, $username, $password, $auth_source, $email, $status, $official_code, $phone, $picture_uri, $expiration_date, $active, null, $hr_dept_id, null, $language);
		if (api_get_setting('openid_authentication') == 'true' && !empty($user['openid'])) {
			$up = UserManager::update_openid($user_id,$user['openid']);
		}
		if ($user_id != $_SESSION['_uid']) {
			if ($platform_admin == 1) {
				$sql = "INSERT IGNORE INTO $table_admin SET user_id = '".$user_id."'";
				Database::query($sql);
			} else {
				$sql = "DELETE FROM $table_admin WHERE user_id = '".$user_id."'";
				Database::query($sql);
			}
		}

		$extras = array();
		foreach($user as $key => $value) {
			if(substr($key, 0, 6) == 'extra_') { //an extra field
				$myres = UserManager::update_extra_field_value($user_id, substr($key, 6), $value);
			}
		}

		if (!empty ($email) && $send_mail) {
			$recipient_name = api_get_person_name($firstname, $lastname, null, PERSON_NAME_EMAIL_ADDRESS);
			$emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');
			$sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
			$email_admin = api_get_setting('emailAdministrator');

			if ($_configuration['multiple_access_urls']) {
				$access_url_id = api_get_current_access_url_id();
				if ($access_url_id != -1) {
					$url = api_get_access_url($access_url_id);
					$emailbody = get_lang('Dear')." ".stripslashes(api_get_person_name($firstname, $lastname)).",\n\n".get_lang('YouAreReg')." ". api_get_setting('siteName') ." ".get_lang('WithTheFollowingSettings')."\n\n".get_lang('Username')." : ". $username . (($reset_password > 0) ? "\n". get_lang('Pass')." : ".stripslashes($password) : "") . "\n\n" .get_lang('Address') ." ". api_get_setting('siteName') ." ". get_lang('Is') ." : ". $url['url'] ."\n\n". get_lang('Problem'). "\n\n". get_lang('Formula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n". get_lang('Manager'). " ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".api_get_setting('emailAdministrator');
				}
			}
			else {
				$emailbody=get_lang('Dear')." ".stripslashes(api_get_person_name($firstname, $lastname)).",\n\n".get_lang('YouAreReg')." ". api_get_setting('siteName') ." ".get_lang('WithTheFollowingSettings')."\n\n".get_lang('Username')." : ". $username . (($reset_password > 0) ? "\n". get_lang('Pass')." : ".stripslashes($password) : "") . "\n\n" .get_lang('Address') ." ". api_get_setting('siteName') ." ". get_lang('Is') ." : ". $_configuration['root_web'] ."\n\n". get_lang('Problem'). "\n\n". get_lang('Formula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n". get_lang('Manager'). " ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".api_get_setting('emailAdministrator');
			}

			@api_mail($recipient_name, $email, $emailsubject, $emailbody, $sender_name, $email_admin);
		}
		$tok = Security::get_token();
		header('Location: user_list.php?action=show_message&message='.urlencode(get_lang('UserUpdated')).'&sec_token='.$tok);
		exit();
	}
}

if ($error_drh) {
	$err_msg = get_lang('StatusCanNotBeChangedToHumanResourcesManager');
	$message = Display::return_message($err_msg, 'error');
}

// USER PICTURE
$image_path = UserManager::get_user_picture_path_by_id($user_id,'web');
$image_dir = $image_path['dir'];
$image = $image_path['file'];
$image_file = ($image != '' ? $image_dir.$image : api_get_path(WEB_CODE_PATH).'img/unknown.jpg');
$image_size = api_getimagesize($image_file);

$img_attributes = 'src="'.$image_file.'?rand='.time().'" '
	.'alt="'.api_get_person_name($user_data['firstname'], $user_data['lastname']).'" '
	.'style="float:'.($text_dir == 'rtl' ? 'left' : 'right').'; padding:5px;" ';

if ($image_size['width'] > 300) { //limit display width to 300px
	$img_attributes .= 'width="300" ';
}

// get the path,width and height from original picture
$big_image = $image_dir.'big_'.$image;
$big_image_size = api_getimagesize($big_image);
$big_image_width = $big_image_size['width'];
$big_image_height = $big_image_size['height'];
$url_big_image = $big_image.'?rnd='.time();

if ($image == '') {
	$content .= '<img '.$img_attributes.' />';
} else {
	$content .= '<input type="image" '.$img_attributes.' onclick="javascript: return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');"/>';
}

// Display form
$content .= $form->return_form();

$tpl = new Template($tool_name);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();