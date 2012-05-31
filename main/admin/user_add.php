<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*/

// Language files that should be included
$language_file = array('admin', 'registration');
$cidReset = true;
// Including necessary libraries.
require_once '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'fileManage.lib.php';
require_once $libpath.'fileUpload.lib.php';
require_once $libpath.'mail.lib.inc.php';

// Section for the tabs
$this_section = SECTION_PLATFORM_ADMIN;

// User permissions
api_protect_admin_script();

// Database table definitions
$table_admin 	= Database::get_main_table(TABLE_MAIN_ADMIN);

$htmlHeadXtra[] = '
<script type="text/javascript">
<!--
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

function display_drh_list(){
	if(document.getElementById("status_select").value=='.STUDENT.')
	{
		document.getElementById("drh_list").style.display="block";
		document.getElementById("id_platform_admin").style.display="none";
	}
	else if (document.getElementById("status_select").value=='.COURSEMANAGER.')
	{
		document.getElementById("drh_list").style.display="none";
		document.getElementById("id_platform_admin").style.display="block";
	}
	else
	{
		document.getElementById("drh_list").style.display="none";
		document.getElementById("id_platform_admin").style.display="none";
	}
}

//-->
</script>';

if (!empty($_GET['message'])) {
	$message = urldecode($_GET['message']);
}

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => 'user_list.php', "name" => get_lang('UserList'));
$tool_name = get_lang('AddUsers');

// Create the form
$form = new FormValidator('user_add');
$form->addElement('header', '', $tool_name);
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

if (api_get_setting('login_is_email') == 'true') {
    $form->addRule('email', sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
    $form->addRule('email', get_lang('UserTaken'), 'username_available', $user_data['username']);
}

// Phone
$form->addElement('text', 'phone', get_lang('PhoneNumber'));
// Picture
$form->addElement('file', 'picture', get_lang('AddPicture'));
$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');

$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);

// Username
if (api_get_setting('login_is_email') != 'true') {
    $form->addElement('text', 'username', get_lang('LoginName'), array('maxlength' => USERNAME_MAX_LENGTH));
    $form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'), (string)USERNAME_MAX_LENGTH), 'maxlength', USERNAME_MAX_LENGTH);
    $form->addRule('username', get_lang('OnlyLettersAndNumbersAllowed'), 'username');
    $form->addRule('username', get_lang('UserTaken'), 'username_available', $user_data['username']);
}

// Password
$group = array();
$auth_sources = 0; //make available wider as we need it in case of form reset (see below)
$nb_ext_auth_source_added = 0;
if (count($extAuthSource) > 0) {
	$auth_sources = array();
	foreach($extAuthSource as $key => $info) {
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
    	$group[] =& HTML_QuickForm::createElement('radio', 'password_auto', null, get_lang('ExternalAuthentication').' ', 2);
    	$group[] =& HTML_QuickForm::createElement('select', 'auth_source', null, $auth_sources);
    	$group[] =& HTML_QuickForm::createElement('static', '', '', '<br />');
    }
}
$group[] =& HTML_QuickForm::createElement('radio', 'password_auto', get_lang('Password'), get_lang('AutoGeneratePassword').'<br />', 1);
$group[] =& HTML_QuickForm::createElement('radio', 'password_auto', 'id="radio_user_password"', null, 0);
$group[] =& HTML_QuickForm::createElement('password', 'password', null, array('onkeydown' => 'javascript: password_switch_radio_button();'));
$form->addGroup($group, 'password', get_lang('Password'), '');

// Status
$status = array();
$status[COURSEMANAGER] = get_lang('Teacher');
$status[STUDENT] = get_lang('Learner');
$status[DRH] = get_lang('Drh');
$status[SESSIONADMIN] = get_lang('SessionsAdmin');

$form->addElement('select', 'status', get_lang('Profile'), $status, array('id' => 'status_select', 'class'=>'chzn-select', 'onchange' => 'javascript: display_drh_list();'));
$form->addElement('select_language', 'language', get_lang('Language'), null);
//drh list (display only if student)
$display = ($_POST['status'] == STUDENT || !isset($_POST['status'])) ? 'block' : 'none';


$form->addElement('html', '<div id="drh_list" style="display:'.$display.';">');
/*$drh_select = $form->addElement('select', 'hr_dept_id', get_lang('Drh'), array(), 'id="drh_select"');
$drh_list = UserManager :: get_user_list(array('status' => DRH), api_sort_by_first_name() ? array('firstname', 'lastname') : array('lastname', 'firstname'));
if (count($drh_list) == 0) {
	$drh_select->addOption('- '.get_lang('ThereIsNotStillAResponsible', '').' -', 0);
} else {
	$drh_select->addOption('- '.get_lang('SelectAResponsible').' -', 0);
}*/

if (is_array($drh_list)) {
	foreach ($drh_list as $drh) {
		$drh_select->addOption(api_get_person_name($drh['firstname'], $drh['lastname']), $drh['user_id']);
	}
}
$form->addElement('html', '</div>');



// Platform admin
$group = array();
$group[] =& HTML_QuickForm::createElement('radio', 'platform_admin', 'id="id_platform_admin"', get_lang('Yes'), 1);
$group[] =& HTML_QuickForm::createElement('radio', 'platform_admin', 'id="id_platform_admin"', get_lang('No'), 0);
$display = ($_POST['status'] == STUDENT || !isset($_POST['status'])) ? 'none' : 'block';
$form->addElement('html', '<div id="id_platform_admin" style="display:'.$display.';">');
$form->addGroup($group, 'admin', get_lang('PlatformAdmin'), '&nbsp;');
$form->addElement('html', '</div>');
// Send email
$group = array();
$group[] =& HTML_QuickForm::createElement('radio', 'send_mail', null, get_lang('Yes'), 1);
$group[] =& HTML_QuickForm::createElement('radio', 'send_mail', null, get_lang('No'), 0);
$form->addGroup($group, 'mail', get_lang('SendMailToNewUser'), '&nbsp;');
// Expiration Date
$form->addElement('radio', 'radio_expiration_date', get_lang('ExpirationDate'), get_lang('NeverExpires'), 0);
$group = array ();
$group[] = & $form->createElement('radio', 'radio_expiration_date', null, get_lang('On'), 1);
$group[] = & $form->createElement('datepicker', 'expiration_date', null, array('form_name' => $form->getAttribute('name'), 'onchange' => 'javascript: enable_expiration_date();'));
$form->addGroup($group, 'max_member_group', null, '', false);
// Active account or inactive account
$form->addElement('radio', 'active', get_lang('ActiveAccount'), get_lang('Active'), 1);
$form->addElement('radio', 'active', '', get_lang('Inactive'), 0);

$extra_data = UserManager::get_extra_user_data(0, true);
UserManager::set_extra_fields_in_form($form, $extra_data, 'user_add');

// Set default values
$defaults['admin']['platform_admin'] = 0;
$defaults['mail']['send_mail'] = 1;
$defaults['password']['password_auto'] = 1;
$defaults['active'] = 1;
$defaults['expiration_date'] = array();
$days = api_get_setting('account_valid_duration');
$time = strtotime('+'.$days.' day');
$defaults['expiration_date']['d'] = date('d', $time);
$defaults['expiration_date']['F'] = date('m', $time);
$defaults['expiration_date']['Y'] = date('Y', $time);
$defaults['radio_expiration_date'] = 0;
$defaults['status'] = STUDENT;
$defaults = array_merge($defaults, $extra_data);
$form->setDefaults($defaults);

// Submit button
$html_results_enabled[] = FormValidator :: createElement ('style_submit_button', 'submit_plus', get_lang('Add').'+', 'class="add"');
$html_results_enabled[] = FormValidator :: createElement ('style_submit_button', 'submit', get_lang('Add'), 'class="add"');
$form->addGroup($html_results_enabled);

// Validate form
if( $form->validate()) {
	$check = Security::check_token('post');
	if ($check) {
		$user = $form->exportValues();			
		$lastname       = $user['lastname'];
		$firstname      = $user['firstname'];
		$official_code  = $user['official_code'];
		$email          = $user['email'];
		$phone          = $user['phone'];
		$username       = $user['username'];
		$status         = intval($user['status']);
		$language       = $user['language'];
		$picture        = $_FILES['picture'];
		$platform_admin = intval($user['admin']['platform_admin']);
		$send_mail      = intval($user['mail']['send_mail']);
		$hr_dept_id     = intval($user['hr_dept_id']);
        
		if (count($extAuthSource) > 0 && $user['password']['password_auto'] == '2') {
			$auth_source = $user['password']['auth_source'];
			$password = 'PLACEHOLDER';
		} else {
			$auth_source = PLATFORM_AUTH_SOURCE;
			$password = $user['password']['password_auto'] == '1' ? api_generate_password() : $user['password']['password'];
		}
        
		if ($user['radio_expiration_date'] == '1') {
			$expiration_date = $user['expiration_date'];
		} else {
			$expiration_date = '0000-00-00 00:00:00';
		}
        
		$active = intval($user['active']);
    
        if (api_get_setting('login_is_email') == 'true') {
            $username = $email;
        }

		$user_id = UserManager::create_user($firstname, $lastname, $status, $email, $username, $password, $official_code, $language, $phone, null, $auth_source, $expiration_date, $active, $hr_dept_id, null, null, $send_mail);
        
		Security::clear_token();
		$tok = Security::get_token();
		if ($user_id === false) {
			//If any error ocurred during user creation, print it (api_failureList 
			// stores values as separate words, so rework it
			$message = '';
			$message_bits = explode(' ',api_get_last_failure());
			foreach ($message_bits as $bit) {
				$message .= ucfirst($bit);
			}
		} else {
 			if (!empty($picture['name'])) {
				$picture_uri = UserManager::update_user_picture($user_id, $_FILES['picture']['name'], $_FILES['picture']['tmp_name']);
				UserManager::update_user($user_id, $firstname, $lastname, $username, $password, $auth_source, $email, $status, $official_code, $phone, $picture_uri, $expiration_date, $active, null, $hr_dept_id, null, $language);
			}
			$extras = array();
			foreach ($user as $key => $value) {
				if (substr($key, 0, 6) == 'extra_') { //an extra field
					UserManager::update_extra_field_value($user_id, substr($key, 6), $value);
				}
			}
			if ($platform_admin) {
				$sql = "INSERT INTO $table_admin SET user_id = '".$user_id."'";
				Database::query($sql);
			}
			$message = get_lang('UserAdded');
		}
		if (isset($user['submit_plus'])) {
			//we want to add more. Prepare report message and redirect to the same page (to clean the form)
			header('Location: user_add.php?message='.urlencode($message).'&sec_token='.$tok);
			exit ();
		} else {
			$tok = Security::get_token();
			header('Location: user_list.php?action=show_message&message='.urlencode($message).'&sec_token='.$tok);
			exit ();
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

if(!empty($message)){
	$message = Display::return_message(stripslashes($message));
}
$content .= $form->return_form();

$tpl = new Template($tool_name);
$tpl->assign('actions', $actions);
$tpl->assign('message', $message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();
