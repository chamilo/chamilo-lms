<?php
/* For licensing terms, see /license.txt */
/**
* This file displays the user's profile,
* optionally it allows users to modify their profile as well.
*
* See inc/conf/profile.conf.php to modify settings
*
* @package chamilo.auth
*/
/**
 * Code
 */

// Language files that should be included.
$language_file = array('registration', 'messages', 'userInfo');
$cidReset = true;
require_once '../inc/global.inc.php';

if (api_get_setting('allow_social_tool') == 'true') {
	$this_section = SECTION_SOCIAL;
} else {
	$this_section = SECTION_MYPROFILE;
}

$_SESSION['this_section'] = $this_section;

if (!(isset($_user['user_id']) && $_user['user_id']) || api_is_anonymous($_user['user_id'], true)) {
    api_not_allowed(true);
}

$htmlHeadXtra[] = '<script src="../inc/lib/javascript/tag/jquery.fcbkcomplete.js" type="text/javascript" language="javascript"></script>';
$htmlHeadXtra[] = '<link href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/tag/style.css" rel="stylesheet" type="text/css" />';

$htmlHeadXtra[] = '<script>
function confirmation(name) {
	if (confirm("'.get_lang('AreYouSureToDelete', '').' " + name + " ?")) {
			document.forms["profile"].submit();
	} else {
		return false;
	}
}
function show_image(image,width,height) {
	width = parseInt(width) + 20;
	height = parseInt(height) + 20;
	window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');

}
function generate_open_id_form() {
	$.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		/*$("#div_api_key").html("Loading...");*/ },
		type: "POST",
		url: "'.api_get_path(WEB_AJAX_PATH).'user_manager.ajax.php?a=generate_api_key",
		data: "num_key_id="+"",
		success: function(datos) {
		 $("#div_api_key").html(datos);
		}
	});
}  
           
function hide_icon_edit(element_html)  {
    ident="#edit_image";
    $(ident).hide();
}      
function show_icon_edit(element_html) {    
    ident="#edit_image";
    $(ident).show();
}       
</script>';

//$interbreadcrumb[] = array('url' => '../auth/profile.php', 'name' => get_lang('ModifyProfile'));
if (!empty ($_GET['coursePath'])) {
	$course_url = api_get_path(WEB_COURSE_PATH).htmlentities(strip_tags($_GET['coursePath'])).'/index.php';
	$interbreadcrumb[] = array('url' => $course_url, 'name' => Security::remove_XSS($_GET['courseCode']));
}

$warning_msg = '';
if (!empty($_GET['fe'])) {
	$warning_msg .= get_lang('UplUnableToSaveFileFilteredExtension');
	$_GET['fe'] = null;
}

$jquery_ready_content = '';
if (api_get_setting('allow_message_tool') == 'true') {
	$jquery_ready_content = <<<EOF
			$(".message-content .message-delete").click(function(){
				$(this).parents(".message-content").animate({ opacity: "hide" }, "slow");
				$(".message-view").animate({ opacity: "show" }, "slow");
			});
EOF;
}

//	Configuration file
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';

// Libraries
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';

$tool_name = is_profile_editable() ? get_lang('ModifProfile') : get_lang('ViewProfile');
$table_user = Database :: get_main_table(TABLE_MAIN_USER);

/*	Form	*/

/*
 * Get initial values for all fields.
 */
$user_data = UserManager::get_user_info_by_id(api_get_user_id());
$array_list_key = UserManager::get_api_keys(api_get_user_id());
$id_temp_key = UserManager::get_api_key_id(api_get_user_id(), 'dokeos');
$value_array = $array_list_key[$id_temp_key];
$user_data['api_key_generate'] = $value_array;

if ($user_data !== false) {
	if (is_null($user_data['language'])) {
		$user_data['language'] = api_get_setting('platformLanguage');
	}
}

/*
 * Initialize the form.
 */
$form = new FormValidator('profile', 'post', api_get_self()."?".str_replace('&fe=1', '', $_SERVER['QUERY_STRING']), null, array('style' => 'width: 70%; float: '.($text_dir == 'rtl' ? 'right;' : 'left;')));

/* Make sure this is the first submit on the form, even though it is hidden!
 * Otherwise, if a user has productions and presses ENTER to submit, he will
 * attempt to delete the first production in the list. */
//if (is_profile_editable()) {
//	$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"', array('style' => 'visibility:hidden;'));
//}

//	SUBMIT (visible)
/*if (is_profile_editable()) {
	$form->addElement('style_submit_button', 'apply_change', get_lang('SaveSettings'), 'class="save"');
} else {
	$form->freeze();
}*/

//THEME
if (is_profile_editable() && api_get_setting('user_selected_theme') == 'true') {
        $form->addElement('select_theme', 'theme', get_lang('Theme'));
	if (api_get_setting('profile', 'theme') !== 'true')
		$form->freeze('theme');
	$form->applyFilter('theme', 'trim');
}

if (api_is_western_name_order()) {
	//	FIRST NAME and LAST NAME
	$form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
	$form->addElement('text', 'lastname',  get_lang('LastName'),  array('size' => 40));
} else {
	//	LAST NAME and FIRST NAME
	$form->addElement('text', 'lastname',  get_lang('LastName'),  array('size' => 40));
	$form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
}
if (api_get_setting('profile', 'name') !== 'true') {
	$form->freeze(array('lastname', 'firstname'));
}
$form->applyFilter(array('lastname', 'firstname'), 'stripslashes');
$form->applyFilter(array('lastname', 'firstname'), 'trim');
$form->addRule('lastname' , get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');

//	USERNAME
$form->addElement('text', 'username', get_lang('UserName'), array('maxlength' => USERNAME_MAX_LENGTH, 'size' => USERNAME_MAX_LENGTH));
if (api_get_setting('profile', 'login') !== 'true') {
	$form->freeze('username');
}
$form->applyFilter('username', 'stripslashes');
$form->applyFilter('username', 'trim');
$form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('username', get_lang('UsernameWrong'), 'username');
$form->addRule('username', get_lang('UserTaken'), 'username_available', $user_data['username']);

//	OFFICIAL CODE
if (CONFVAL_ASK_FOR_OFFICIAL_CODE) {
	$form->addElement('text', 'official_code', get_lang('OfficialCode'), array('size' => 40));
	if (api_get_setting('profile', 'officialcode') !== 'true') {
		$form->freeze('official_code');
	}
	$form->applyFilter('official_code', 'stripslashes');
	$form->applyFilter('official_code', 'trim');
	if (api_get_setting('registration', 'officialcode') == 'true' && api_get_setting('profile', 'officialcode') == 'true') {
		$form->addRule('official_code', get_lang('ThisFieldIsRequired'), 'required');
	}
}

//	EMAIL
$form->addElement('email', 'email', get_lang('Email'), array('size' => 40));
if (api_get_setting('profile', 'email') !== 'true') {
	$form->freeze('email');
}

if (api_get_setting('registration', 'email') == 'true' &&  api_get_setting('profile', 'email') == 'true') {        
    $form->applyFilter('email', 'stripslashes');
    $form->applyFilter('email', 'trim');
    $form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
    $form->addRule('email', get_lang('EmailWrong'), 'email');    
}

// OPENID URL
if (is_profile_editable() && api_get_setting('openid_authentication') == 'true') {
	$form->addElement('text', 'openid', get_lang('OpenIDURL'), array('size' => 40));
	if (api_get_setting('profile', 'openid') !== 'true') {
		$form->freeze('openid');
	}
	$form->applyFilter('openid', 'trim');
	//if (api_get_setting('registration', 'openid') == 'true') {
	//	$form->addRule('openid', get_lang('ThisFieldIsRequired'), 'required');
	//}
}

//	PHONE
$form->addElement('text', 'phone', get_lang('phone'), array('size' => 20));
if (api_get_setting('profile', 'phone') !== 'true') {
	$form->freeze('phone');
}
$form->applyFilter('phone', 'stripslashes');
$form->applyFilter('phone', 'trim');
/*if (api_get_setting('registration', 'phone') == 'true') {
	$form->addRule('phone', get_lang('ThisFieldIsRequired'), 'required');
}
$form->addRule('phone', get_lang('EmailWrong'), 'email');*/

//	PICTURE
if (is_profile_editable() && api_get_setting('profile', 'picture') == 'true') {
	$form->addElement('file', 'picture', ($user_data['picture_uri'] != '' ? get_lang('UpdateImage') : get_lang('AddImage')));
	$form->add_progress_bar();
	if (!empty($user_data['picture_uri'])) {
		$form->addElement('checkbox', 'remove_picture', null, get_lang('DelImage'));
	}
	$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
	$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
}

//	LANGUAGE
$form->addElement('select_language', 'language', get_lang('Language'));
if (api_get_setting('profile', 'language') !== 'true') {
	$form->freeze('language');
}

//	EXTENDED PROFILE  this make the page very slow!
if (api_get_setting('extended_profile') == 'true') {
	if (!isset($_GET['type']) || (isset($_GET['type']) && $_GET['type'] == 'extended')) {
		$width_extended_profile = 500;
		//$form->addElement('html', '<a href="javascript: void(0);" onclick="javascript: show_extend();"> show_extend_profile</a>');
		//$form->addElement('static', null, '<em>'.get_lang('OptionalTextFields').'</em>');
		//	MY COMPETENCES
		$form->add_html_editor('competences', get_lang('MyCompetences'), false, false, array('ToolbarSet' => 'Profile', 'Width' => $width_extended_profile, 'Height' => '130'));
		//	MY DIPLOMAS
		$form->add_html_editor('diplomas', get_lang('MyDiplomas'), false, false, array('ToolbarSet' => 'Profile', 'Width' => $width_extended_profile, 'Height' => '130'));
		//	WHAT I AM ABLE TO TEACH
		$form->add_html_editor('teach', get_lang('MyTeach'), false, false, array('ToolbarSet' => 'Profile', 'Width' => $width_extended_profile, 'Height' => '130'));

		//	MY PRODUCTIONS
		$form->addElement('file', 'production', get_lang('MyProductions'));
		if ($production_list = UserManager::build_production_list(api_get_user_id(), '', true)) {
			$form->addElement('static', 'productions_list', null, $production_list);
		}
		//	MY PERSONAL OPEN AREA
		$form->add_html_editor('openarea', get_lang('MyPersonalOpenArea'), false, false, array('ToolbarSet' => 'Profile', 'Width' => $width_extended_profile, 'Height' => '350'));
		$form->applyFilter(array('competences', 'diplomas', 'teach', 'openarea'), 'stripslashes');
		$form->applyFilter(array('competences', 'diplomas', 'teach'), 'trim'); // openarea is untrimmed for maximum openness
	}
}

//	PASSWORD, if auth_source is platform
if (is_platform_authentication() && is_profile_editable() && api_get_setting('profile', 'password') == 'true') {
	$form->addElement('password', 'password0', array(get_lang('Pass'), get_lang('Enter2passToChange')), array('size' => 40));	
	$form->addElement('password', 'password1', get_lang('NewPass'), array('size' => 40));
	$form->addElement('password', 'password2', get_lang('Confirmation'), array('size' => 40));
	//	user must enter identical password twice so we can prevent some user errors
	$form->addRule(array('password1', 'password2'), get_lang('PassTwo'), 'compare');
	if (CHECK_PASS_EASY_TO_FIND) {
		$form->addRule('password1', get_lang('CurrentPasswordEmptyOrIncorrect'), 'callback', 'api_check_password');
	}
}

// EXTRA FIELDS
$extra_data = UserManager::get_extra_user_data(api_get_user_id(), true);
$return_params = UserManager::set_extra_fields_in_form($form, $extra_data, 'profile', false, api_get_user_id());
$jquery_ready_content = $return_params['jquery_ready_content'];

// the $jquery_ready_content variable collects all functions that will be load in the $(document).ready javascript function
$htmlHeadXtra[] ='<script>
$(document).ready(function(){
	'.$jquery_ready_content.'
});
</script>';

if (api_get_setting('profile', 'apikeys') == 'true') {
	$form->addElement('html', '<div id="div_api_key">');
	$form->addElement('text', 'api_key_generate', get_lang('MyApiKey'), array('size' => 40, 'id' => 'id_api_key_generate'));
	$form->addElement('html', '</div>');
	$form->addElement('button', 'generate_api_key', get_lang('GenerateApiKey'), array('id' => 'id_generate_api_key', 'onclick' => 'generate_open_id_form()')); //generate_open_id_form()
}
//	SUBMIT
if (is_profile_editable()) {
	$form->addElement('style_submit_button', 'apply_change', get_lang('SaveSettings'), 'class="save"');
} else {
	$form->freeze();
}

$user_data = array_merge($user_data, $extra_data);
$form->setDefaults($user_data);

/*		FUNCTIONS   */


/**
 * Is user auth_source is platform ?
 * 
 * @return  boolean if auth_source is platform
 */
function is_platform_authentication() {
    $tab_user_info = api_get_user_info();
    return $tab_user_info['auth_source'] == PLATFORM_AUTH_SOURCE;
}

/**
 * Can a user edit his/her profile?
 *
 * @return	boolean	Editability of the profile
 */
function is_profile_editable() {
	return $GLOBALS['profileIsEditable'];
}

/*
	PRODUCTIONS FUNCTIONS
*/

/**
 * Upload a submitted user production.
 *
 * @param	$user_id	User id
 * @return	The filename of the new production or FALSE if the upload has failed
 */
function upload_user_production($user_id) {
	$image_path = UserManager::get_user_picture_path_by_id($user_id, 'system', true);

	$production_repository = $image_path['dir'].$user_id.'/';

	if (!file_exists($production_repository)) {
		@mkdir($production_repository, api_get_permissions_for_new_directories(), true);
	}
	$filename = replace_dangerous_char($_FILES['production']['name']);
	$filename = disable_dangerous_file($filename);

	if (filter_extension($filename)) {
		if (@move_uploaded_file($_FILES['production']['tmp_name'], $production_repository.$filename)) {
			return $filename;
		}
	}
	return false; // this should be returned if anything went wrong with the upload
}

/**
 * Check current user's current password
 * @param	char	password
 * @return	bool true o false
 * @uses Gets user ID from global variable
 */
function check_user_password($password){
	global $_user;
	$user_id = api_get_user_id();
	if ($user_id != strval(intval($user_id)) || empty($password)) { return false; }
	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$password = api_get_encrypted_password($password);
	$sql_password = "SELECT * FROM $table_user WHERE user_id='".$user_id."' AND password='".$password."'";
	$result = Database::query($sql_password);
	return Database::num_rows($result) != 0;
}
/**
 * Check current user's current password
 * @param	char	email
 * @return	bool true o false
 * @uses Gets user ID from global variable
 */
function check_user_email($email) {
	$user_id = api_get_user_id();
	if ($user_id != strval(intval($user_id)) || empty($email)) { return false; }
	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
    $email = Database::escape_string($email);
	$sql_password = "SELECT * FROM $table_user WHERE user_id='".$user_id."' AND email='".$email."'";
	$result = Database::query($sql_password);
	return Database::num_rows($result) != 0;
}

/*		MAIN CODE */
$filtered_extension         = false;
$update_success             = false;
$upload_picture_success     = false;
$upload_production_success  = false;
$msg_fail_changue_email     = false;
$msg_is_not_password        = false;

if (is_platform_authentication()) {
    if (!empty($_SESSION['change_email'])) {
    	$msg_fail_changue_email= ($_SESSION['change_email'] == 'success');
    	unset($_SESSION['change_email']);
    } elseif (!empty($_SESSION['is_not_password'])) {
    	$msg_is_not_password = ($_SESSION['is_not_password'] == 'success');
    	unset($_SESSION['is_not_password']);
    } elseif (!empty($_SESSION['profile_update'])) {
    	$update_success = ($_SESSION['profile_update'] == 'success');
    	unset($_SESSION['profile_update']);
    } elseif (!empty($_SESSION['image_uploaded'])) {
    	$upload_picture_success = ($_SESSION['image_uploaded'] == 'success');
    	unset($_SESSION['image_uploaded']);
    } elseif (!empty($_SESSION['production_uploaded'])) {
    	$upload_production_success = ($_SESSION['production_uploaded'] == 'success');
    	unset($_SESSION['production_uploaded']);
    }
}

if ($form->validate()) {

	$wrong_current_password = false;
//	$user_data = $form->exportValues();
	$user_data = $form->getSubmitValues();
	
	// set password if a new one was provided
	if (!empty($user_data['password0'])) {
		if (check_user_password($user_data['password0'])) {
			if (!empty($user_data['password1'])) {
				$password = $user_data['password1'];
			}
		} else {
			$wrong_current_password = true;
			$_SESSION['is_not_password'] = 'success';
		}
	}
	if (empty($user_data['password0']) && !empty($user_data['password1'])) {
		$wrong_current_password = true;
		$_SESSION['is_not_password'] = 'success';
	}

    $allow_users_to_change_email_with_no_password = true;
    if (is_platform_authentication() && api_get_setting('allow_users_to_change_email_with_no_password') == 'false') {
        $allow_users_to_change_email_with_no_password = false;
    }
    
    
    $changeemail = '';
    
    //If user is sending the email to be changed (input is available and is not freeze )
    if (api_get_setting('registration', 'email') == 'true' &&  api_get_setting('profile', 'email') == 'true') {        

        if ($allow_users_to_change_email_with_no_password) {            
            if (!check_user_email($user_data['email'])) {
                $changeemail = $user_data['email'];
                //$_SESSION['change_email'] = 'success';
            }        
        } else {
            //Normal behaviour
            if (!check_user_email($user_data['email']) && !empty($user_data['password0']) && !$wrong_current_password) {
                $changeemail = $user_data['email'];
            }

            if (!check_user_email($user_data['email']) && empty($user_data['password0'])){
                $_SESSION['change_email'] = 'success';
            }        
        }
    }


	// Upload picture if a new one is provided
	if ($_FILES['picture']['size']) {	 
		if ($new_picture = UserManager::update_user_picture(api_get_user_id(), $_FILES['picture']['name'], $_FILES['picture']['tmp_name'])) {
			$user_data['picture_uri'] = $new_picture;
			$_SESSION['image_uploaded'] = 'success';			
		}
	} elseif (!empty($user_data['remove_picture'])) {
	    // remove existing picture if asked
		UserManager::delete_user_picture(api_get_user_id());
		$user_data['picture_uri'] = '';
	}
	
	//Remove production	
	if (is_array($user_data['remove_production'])) {
		foreach (array_keys($user_data['remove_production']) as $production) {
			UserManager::remove_user_production(api_get_user_id(), urldecode($production));
		}
		if ($production_list = UserManager::build_production_list(api_get_user_id(), true, true)) {			
			$form->insertElementBefore($form->createElement('static', null, null, $production_list), 'productions_list');
		}
		$form->removeElement('productions_list');
		$file_deleted = true;
	}	

	// upload production if a new one is provided
	if ($_FILES['production']['size']) {
		$res = upload_user_production(api_get_user_id());
		if (!$res) {
			//it's a bit excessive to assume the extension is the reason why upload_user_production() returned false, but it's true in most cases
			$filtered_extension = true;
		} else {
			$_SESSION['production_uploaded'] = 'success';
		}
	}

	// remove values that shouldn't go in the database
	unset($user_data['password0'],$user_data['password1'], $user_data['password2'], $user_data['MAX_FILE_SIZE'],
	$user_data['remove_picture'], $user_data['apply_change'],$user_data['email'] );

	// Following RFC2396 (http://www.faqs.org/rfcs/rfc2396.html), a URI uses ':' as a reserved character
	// we can thus ensure the URL doesn't contain any scheme name by searching for ':' in the string
	$my_user_openid = isset($user_data['openid']) ? $user_data['openid'] : '';
	if (!preg_match('/^[^:]*:\/\/.*$/', $my_user_openid)) {
		//ensure there is at least a http:// scheme in the URI provided
		$user_data['openid'] = 'http://'.$my_user_openid;
	}
	$extras = array();
	
	//Checking the user language
	$languages = api_get_languages();   
    if (!in_array($user_data['language'], $languages['folder'])) {
        $user_data['language'] = api_get_setting('platformLanguage');
    }
    
	//Only update values that are request by the "profile" setting
	$profile_list = api_get_setting('profile');
	//Adding missing variables		
	
	$available_values_to_modify = array();
	foreach($profile_list as $key => $status) {	    
	    if ($status == 'true') {
            switch($key) {
                case 'login':
                    $available_values_to_modify[] = 'username';
                    break;
                case 'name':
                    $available_values_to_modify[] = 'firstname';
                    $available_values_to_modify[] = 'lastname'; 
                    break;
                case 'picture':
                    $available_values_to_modify[] = 'picture_uri';
                    break;
                default:
                    $available_values_to_modify[] = $key;
                    break;
            }            
	    }
	}
    
	//Fixing missing variables    
    $available_values_to_modify = array_merge($available_values_to_modify, array('competences', 'diplomas', 'openarea', 'teach', 'openid'));
    
	// build SQL query
	$sql = "UPDATE $table_user SET";
	unset($user_data['api_key_generate']);
	foreach ($user_data as $key => $value) {
		if (substr($key, 0, 6) == 'extra_') { //an extra field
			$new_key = substr($key, 6);
			// format array date to 'Y-m-d' or date time  to 'Y-m-d H:i:s'
			if (is_array($value) && isset($value['Y']) && isset($value['F']) && isset($value['d'])) {
				if (isset($value['H']) && isset($value['i'])) {
					// extra field date time
					$time = mktime($value['H'],$value['i'],0,$value['F'],$value['d'],$value['Y']);
					$extras[$new_key] = date('Y-m-d H:i:s',$time);
				} else {
					// extra field date
					$time = mktime(0,0,0,$value['F'],$value['d'],$value['Y']);
					$extras[$new_key] = date('Y-m-d',$time);
				}
			} else {
				$extras[$new_key] = $value;
			}
		} else {
		    if (in_array($key, $available_values_to_modify)) {	
                $sql .= " $key = '".Database::escape_string($value)."',";
		    }		    
		}
	}

	//change email
	if ($allow_users_to_change_email_with_no_password) {	    
        if (!empty($changeemail) && in_array('email', $available_values_to_modify)) {
            $sql .= " email = '".Database::escape_string($changeemail)."',";
        }
        if (isset($password) && in_array('password', $available_values_to_modify)) {
            $password = api_get_encrypted_password($password);
            $sql .= " password = '".Database::escape_string($password)."'";
        } else {            
            // remove trailing , from the query we have so far
            $sql = rtrim($sql, ',');
        }	    
    } else {
        //normal behaviour
        if (!empty($changeemail) && !isset($password) && in_array('email', $available_values_to_modify)) {
            $sql .= " email = '".Database::escape_string($changeemail)."'";
        } elseif (isset($password) && isset($changeemail) && in_array('email', $available_values_to_modify) && in_array('password', $available_values_to_modify)) {            
            if (!empty($changeemail)) {
                $sql .= " email = '".Database::escape_string($changeemail)."',";
            }
            $password = api_get_encrypted_password($password);
            $sql .= " password = '".Database::escape_string($password)."'";
        } elseif (isset($password) && in_array('password', $available_values_to_modify)) {
            $password = api_get_encrypted_password($password);
            $sql .= " password = '".Database::escape_string($password)."'";
        } else {
            // remove trailing , from the query we have so far
            $sql = rtrim($sql, ',');
        }        
    }
	$sql .= " WHERE user_id  = '".api_get_user_id()."'";
	Database::query($sql);
    

	// User tag process
	//1. Deleting all user tags
	$list_extra_field_type_tag = UserManager::get_all_extra_field_by_type(UserManager::USER_FIELD_TYPE_TAG);
    
	if (is_array($list_extra_field_type_tag) && count($list_extra_field_type_tag)>0) {
		foreach ($list_extra_field_type_tag as $id) {
			UserManager::delete_user_tags(api_get_user_id(), $id);
		}
	}

	//2. Update the extra fields and user tags if available
    
	if (is_array($extras) && count($extras)> 0) {        
		foreach ($extras as $key => $value) {
			//3. Tags are process in the UserManager::update_extra_field_value by the UserManager::process_tags function
			UserManager::update_extra_field_value(api_get_user_id(), $key, $value);
		}        
	}
    
    // re-init the system to take new settings into account
    $_SESSION['_user']['uidReset'] = true;
    $_SESSION['noredirection'] = true;
    $_SESSION['profile_update'] = 'success';
    $url = api_get_self()."?{$_SERVER['QUERY_STRING']}".($filtered_extension && strpos($_SERVER['QUERY_STRING'], '&fe=1') === false ? '&fe=1' : '');    
    header("Location: ".$url);
    exit;
}


/*  		MAIN DISPLAY SECTION  */
// the header
Display::display_header(get_lang('ModifyProfile'));

if (api_get_setting('allow_social_tool') != 'true') {

	if (api_get_setting('extended_profile') == 'true') {
		echo '<div class="actions">';

		if (api_get_setting('allow_social_tool') == 'true' && api_get_setting('allow_message_tool') == 'true') {
			echo '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php">'.Display::return_icon('shared_profile.png', get_lang('ViewSharedProfile')).'</a>';
		}
		if (api_get_setting('allow_message_tool') == 'true') {
			echo '<a href="'.api_get_path(WEB_PATH).'main/messages/inbox.php">'.Display::return_icon('inbox.png', get_lang('Messages')).'</a>';
		}
		$show = isset($_GET['show']) ? '&amp;show='.Security::remove_XSS($_GET['show']) : '';

		if (isset($_GET['type']) && $_GET['type'] == 'extended') {
			echo '<a href="profile.php?type=reduced'.$show.'">'.Display::return_icon('edit.png', get_lang('EditNormalProfile'),'',16).'</a>';
		} else {
			echo '<a href="profile.php?type=extended'.$show.'">'.Display::return_icon('edit.png', get_lang('EditExtendProfile'),'',16).'</a>';
		}
		echo '</div>';
	}
}

if (!empty($file_deleted)) {
	Display :: display_confirmation_message(get_lang('FileDeleted'), false);
} elseif (!empty($update_success)) {
	$message = get_lang('ProfileReg');

	if ($upload_picture_success) {
		$message .= '<br /> '.get_lang('PictureUploaded');
	}

	if ($upload_production_success) {
		$message.='<br />'.get_lang('ProductionUploaded');
	}
	Display :: display_confirmation_message($message, false);
}


if (!empty($msg_fail_changue_email)){
	$errormail=get_lang('ToChangeYourEmailMustTypeYourPassword');
	Display :: display_error_message($errormail, false);
}

if (!empty($msg_is_not_password)){
	$warning_msg = get_lang('CurrentPasswordEmptyOrIncorrect');
	Display :: display_warning_message($warning_msg, false);
}

//User picture size is calculated from SYSTEM path
$image_syspath = UserManager::get_user_picture_path_by_id(api_get_user_id(), 'system', false, true);
$image_syspath['dir'].$image_syspath['file'];

//$image_size = api_getimagesize($image_syspath['dir'].$image_syspath['file']);

//Web path
$image_path = UserManager::get_user_picture_path_by_id(api_get_user_id(), 'web', false, true);
$image_dir = $image_path['dir'];
$image = $image_path['file'];
$image_file = $image_dir.$image;
$img_attributes = 'src="'.$image_file.'?rand='.time().'" '
	.'alt="'.api_get_person_name($user_data['firstname'], $user_data['lastname']).'" '
	.'style="float:'.($text_dir == 'rtl' ? 'left' : 'right').'; margin-top:0px;padding:5px;" ';
if ($image_size['width'] > 300) {
	//limit display width to 300px
	$img_attributes .= 'width="300" ';
}

// get the path,width and height from original picture
$big_image = $image_dir.'big_'.$image;

$big_image_size     = api_getimagesize($big_image);
$big_image_width    = $big_image_size['width'];
$big_image_height   = $big_image_size['height'];
$url_big_image      = $big_image.'?rnd='.time();

$show_delete_account_button = api_get_setting('platform_unsubscribe_allowed') == 'true' ? true : false;

if (api_get_setting('allow_social_tool') == 'true') {    
	echo '<div class="row-fluid">';
		echo '<div class="span3">';
		echo SocialManager::show_social_menu('home', null, api_get_user_id(), false, $show_delete_account_button);
		echo '</div>';
		echo '<div class="span9">';
        $form->display();			
	echo '</div>';
} else {
	// Style position:absolute has been removed for Opera-compatibility.
	//echo '<div id="image-message-container" style="float:right;display:inline;position:absolute;padding:3px;width:250px;" >';
	echo '<div id="image-message-container" style="float:right;display:inline;padding:3px;width:230px;" >';

	if ($image == 'unknown.jpg') {
		echo '<img '.$img_attributes.' />';
	} else {
		echo '<input type="image" '.$img_attributes.' onclick="javascript: return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');"/>';
	}
	echo '</div>';
	$form->display();
}
Display :: display_footer();