<?php // $Id: profile.php 20951 2009-05-23 19:07:59Z ivantcholakov $
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
* This file displays the user's profile,
* optionally it allows users to modify their profile as well.
*
* See inc/conf/profile.conf.php to modify settings
*
* @package dokeos.auth
==============================================================================
*/
/**
 * Init section
 */
// name of the language file that needs to be included
$language_file = array('registration','messages','userInfo');
$cidReset = true;
require ('../inc/global.inc.php');
if (!isset($_GET['show'])) {
	 if (api_get_setting('allow_social_tool')=='true' || api_get_setting('allow_message_tool')=='true') {
		header('Location:../social/index.php');
		exit;
	}
}
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
$this_section = SECTION_MYPROFILE;
$_SESSION['this_section']=$this_section;
api_block_anonymous_users();

$htmlHeadXtra[] = '<script src="../inc/lib/javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script type="text/javascript">
function confirmation(name) {
	if (confirm("'.get_lang('AreYouSureToDelete').' " + name + " ?"))
		{return true;}
	else
		{return false;}
}
function show_image(image,width,height) {
	width = parseInt(width) + 20;
	height = parseInt(height) + 20;			
	window_x = window.open(image,\'windowX\',\'width=\'+ width + \', height=\'+ height + \'\');
		
}			
</script>';

if (api_get_setting('allow_message_tool')=='true') {
	$htmlHeadXtra[] ='<script type="text/javascript">
	$(document).ready(function(){
		$(".message-content .message-delete").click(function(){
			$(this).parents(".message-content").animate({ opacity: "hide" }, "slow");
			$(".message-view").animate({ opacity: "show" }, "slow");
		});				
		
	});
	</script>';	
}
$htmlHeadXtra[] ='<script type="text/javascript">
function generate_open_id_form() {
	$.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		/*$("#div_api_key").html("Loading...");*/ },
		type: "POST",
		url: "../auth/generate_api_key.inc.php",
		data: "num_key_id="+"",
		success: function(datos) {
		 $("#div_api_key").html(datos);
		}
	});
}
</script>';
$interbreadcrumb[]= array (
	'url' => '../auth/profile.php',
	'name' => get_lang('ModifyProfile')
);
if (!empty ($_GET['coursePath'])) {
	$course_url = api_get_path(WEB_COURSE_PATH).htmlentities(strip_tags($_GET['coursePath'])).'/index.php';
	$interbreadcrumb[] = array ('url' => $course_url, 'name' => Security::remove_XSS($_GET['courseCode']));
}
$warning_msg = '';
if(!empty($_GET['fe'])) {
	$warning_msg .= get_lang('UplUnableToSaveFileFilteredExtension');
	$_GET['fe'] = null;
}
if(!empty($_GET['cp'])) {
	$warning_msg .= get_lang('CurrentPasswordEmptyOrIncorrect');
	$_GET['cp'] = null;
}

/*
-----------------------------------------------------------
	Configuration file
-----------------------------------------------------------
*/
require_once (api_get_path(CONFIGURATION_PATH).'profile.conf.php');

/*
-----------------------------------------------------------
	Libraries
-----------------------------------------------------------
*/
require_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
require_once (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
require_once (api_get_path(LIBRARY_PATH).'image.lib.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once (api_get_path(LIBRARY_PATH).'social.lib.php');

if (is_profile_editable())
	$tool_name = get_lang('ModifProfile');
else
	$tool_name = get_lang('ViewProfile');

$table_user = Database :: get_main_table(TABLE_MAIN_USER);

/*
-----------------------------------------------------------
	Form
-----------------------------------------------------------
*/
/*
 * Get initial values for all fields.
 */

$user_data = UserManager::get_user_info_by_id(api_get_user_id());
$array_list_key=UserManager::get_api_keys(api_get_user_id());
$id_temp_key=UserManager::get_api_key_id(api_get_user_id(),'dokeos');
$value_array=$array_list_key[$id_temp_key];
$user_data['api_key_generate']=$value_array;

if ($user_data !== false) {
	if (is_null($user_data['language']))
		$user_data['language'] = api_get_setting('platformLanguage');
}

$fck_attribute['Width'] = "100%";
$fck_attribute['Height'] = "130";
$fck_attribute['ToolbarSet'] = "Profil";
// hiding the toolbar of fckeditor
$fck_attribute['Config']['ToolbarStartExpanded']='false';

/*
 * Initialize the form.
 */
$form = new FormValidator('profile', 'post', api_get_self()."?".str_replace('&fe=1','',$_SERVER['QUERY_STRING']), null, array('style' => 'width: 75%; float: '.($text_dir=='rtl'?'right;':'left;')));

/* Make sure this is the first submit on the form, even though it is hidden!
 * Otherwise, if a user has productions and presses ENTER to submit, he will
 * attempt to delete the first production in the list. */
//if (is_profile_editable())
//	$form->addElement('style_submit_button', null, get_lang('SaveSettings'), 'class="save"', array('style' => 'visibility:hidden;'));

//	SUBMIT (visible)
/*if (is_profile_editable())
{
	$form->addElement('style_submit_button', 'apply_change', get_lang('SaveSettings'), 'class="save"');
}
else
{
	$form->freeze();
}*/

//THEME
if (is_profile_editable() && api_get_setting('user_selected_theme') == 'true') {
        $form->addElement('select_theme', 'theme', get_lang('Theme'));
	if (api_get_setting('profile', 'theme') !== 'true')
		$form->freeze('theme');
	$form->applyFilter('theme', 'trim');
}

//	LAST NAME and FIRST NAME
$form->addElement('text', 'lastname',  get_lang('LastName'),  array('size' => 40));
$form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40));
if (api_get_setting('profile', 'name') !== 'true')
	$form->freeze(array('lastname', 'firstname'));
$form->applyFilter(array('lastname', 'firstname'), 'stripslashes');
$form->applyFilter(array('lastname', 'firstname'), 'trim');
$form->addRule('lastname' , get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');


//	USERNAME
$form->addElement('text', 'username', get_lang('UserName'), array('size' => 40));
if (api_get_setting('profile', 'login') !== 'true')
	$form->freeze('username');
$form->applyFilter('username', 'stripslashes');
$form->applyFilter('username', 'trim');
$form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('username', get_lang('UsernameWrong'), 'username');
$form->addRule('username', get_lang('UserTaken'), 'username_available', $user_data['username']);

//	OFFICIAL CODE
if (CONFVAL_ASK_FOR_OFFICIAL_CODE) {
	$form->addElement('text', 'official_code', get_lang('OfficialCode'), array('size' => 40));
	if (api_get_setting('profile', 'officialcode') !== 'true')
		$form->freeze('official_code');
	$form->applyFilter('official_code', 'stripslashes');
	$form->applyFilter('official_code', 'trim');
	if (api_get_setting('registration', 'officialcode') == 'true' && api_get_setting('profile', 'officialcode') == 'true')
		$form->addRule('official_code', get_lang('ThisFieldIsRequired'), 'required');
}

//	EMAIL
$form->addElement('text', 'email', get_lang('Email'), array('size' => 40));
if (api_get_setting('profile', 'email') !== 'true')
	$form->freeze('email');
$form->applyFilter('email', 'stripslashes');
$form->applyFilter('email', 'trim');
if (api_get_setting('registration', 'email') == 'true')
	$form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('email', get_lang('EmailWrong'), 'email');

// OPENID URL
if(is_profile_editable() && api_get_setting('openid_authentication')=='true') {
	$form->addElement('text', 'openid', get_lang('OpenIDURL'), array('size' => 40));
	if (api_get_setting('profile', 'openid') !== 'true')
		$form->freeze('openid');
	$form->applyFilter('openid', 'trim');
	//if (api_get_setting('registration', 'openid') == 'true')
	//	$form->addRule('openid', get_lang('ThisFieldIsRequired'), 'required');
}

//	PHONE
$form->addElement('text', 'phone', get_lang('phone'), array('size' => 20));
if (api_get_setting('profile', 'phone') !== 'true')
	$form->freeze('phone');
$form->applyFilter('phone', 'stripslashes');
$form->applyFilter('phone', 'trim');
/*if (api_get_setting('registration', 'phone') == 'true')
	$form->addRule('phone', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('phone', get_lang('EmailWrong'), 'email');*/

//	PICTURE
if (is_profile_editable() && api_get_setting('profile', 'picture') == 'true') {
	$form->addElement('file', 'picture', ($user_data['picture_uri'] != '' ? get_lang('UpdateImage') : get_lang('AddImage')));
	$form->add_progress_bar();
	if( strlen($user_data['picture_uri']) > 0) {
		$form->addElement('checkbox', 'remove_picture', null, get_lang('DelImage'));
	}
	$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
	$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
}

//	LANGUAGE
$form->addElement('select_language', 'language', get_lang('Language'));
if (api_get_setting('profile', 'language') !== 'true')
	$form->freeze('language');


//	EXTENDED PROFILE  this make the page very slow!
if (api_get_setting('extended_profile') == 'true') {
	if ($_GET['type']=='extended') {
		//$form->addElement('html', '<a href="#" onclick="javascript:show_extend();"> show_extend_profile</a>');			
		$form->addElement('static', null, '<em>'.get_lang('OptionalTextFields').'</em>');	
		//	MY COMPETENCES
		$form->add_html_editor('competences', get_lang('MyCompetences'), false);
		//	MY DIPLOMAS
		$form->add_html_editor('diplomas', get_lang('MyDiplomas'), false);
		//	WHAT I AM ABLE TO TEACH
		$form->add_html_editor('teach', get_lang('MyTeach'), false);
	
		//	MY PRODUCTIONS
		$form->addElement('file', 'production', get_lang('MyProductions'));
		if ($production_list = UserManager::build_production_list($_user['user_id'],'',true)) {
				$form->addElement('static', 'productions_list', null, $production_list);
		}
		//	MY PERSONAL OPEN AREA
		$form->add_html_editor('openarea', get_lang('MyPersonalOpenArea'), false);
		$form->applyFilter(array('competences', 'diplomas', 'teach', 'openarea'), 'stripslashes');
		$form->applyFilter(array('competences', 'diplomas', 'teach'), 'trim'); // openarea is untrimmed for maximum openness
	}
}

//	PASSWORD
if (is_profile_editable() && api_get_setting('profile', 'password') == 'true') {
	
	$form->addElement('password', 'password0', get_lang('Pass'), array('size' => 40));
	$form->addElement('static', null, null, '<em>'.get_lang('Enter2passToChange').'</em>');
	$form->addElement('password', 'password1', get_lang('NewPass'),         array('size' => 40));
	$form->addElement('password', 'password2', get_lang('Confirmation'), array('size' => 40));
	//	user must enter identical password twice so we can prevent some user errors
	$form->addRule(array('password1', 'password2'), get_lang('PassTwo'), 'compare');
	if (CHECK_PASS_EASY_TO_FIND)
		$form->addRule('password1', get_lang('PassTooEasy').': '.api_generate_password(), 'callback', 'api_check_password');
		
}

// EXTRA FIELDS
$extra = UserManager::get_extra_fields(0,50,5,'ASC');
$extra_data = UserManager::get_extra_user_data(api_get_user_id(),true);
foreach($extra as $id => $field_details) {
	if($field_details[6] == 0) {
		continue;
	}
	switch($field_details[2]) {
		case USER_FIELD_TYPE_TEXT:
			$form->addElement('text', 'extra_'.$field_details[1], $field_details[3], array('size' => 40));
			$form->applyFilter('extra_'.$field_details[1], 'stripslashes');
			$form->applyFilter('extra_'.$field_details[1], 'trim');
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
			break;
		case USER_FIELD_TYPE_TEXTAREA:
			$form->add_html_editor('extra_'.$field_details[1], $field_details[3], false);
			//$form->addElement('textarea', 'extra_'.$field_details[1], $field_details[3], array('size' => 80));
			$form->applyFilter('extra_'.$field_details[1], 'stripslashes');
			$form->applyFilter('extra_'.$field_details[1], 'trim');
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
			break;
		case USER_FIELD_TYPE_RADIO:
			$group = array();
			foreach($field_details[9] as $option_id => $option_details) {
				$options[$option_details[1]] = $option_details[2];
				$group[] =& HTML_QuickForm::createElement('radio', 'extra_'.$field_details[1], $option_details[1],$option_details[2].'<br />',$option_details[1]);
			}
			$form->addGroup($group, 'extra_'.$field_details[1], $field_details[3], '');
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);	
			break;
		case USER_FIELD_TYPE_SELECT:
			$options = array();
			foreach($field_details[9] as $option_id => $option_details) {
				$options[$option_details[1]] = $option_details[2];
			}
			$form->addElement('select','extra_'.$field_details[1],$field_details[3],$options,'');	
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);			
			break;
		case USER_FIELD_TYPE_SELECT_MULTIPLE:
			$options = array();
			foreach($field_details[9] as $option_id => $option_details) {
				$options[$option_details[1]] = $option_details[2];
			}
			$form->addElement('select','extra_'.$field_details[1],$field_details[3],$options,array('multiple' => 'multiple'));
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);	
			break;
		case USER_FIELD_TYPE_DATE:
			$form->addElement('datepickerdate', 'extra_'.$field_details[1],$field_details[3], array('form_name'=>'profile'));
			$form->_elements[$form->_elementIndex['extra_'.$field_details[1]]]->setLocalOption('minYear',1900);						
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
			$form->applyFilter('theme', 'trim');
			$defaults['extra_'.$field_details[1]] = date('Y-m-d 12:00:00');
			$form -> setDefaults($defaults);
			break;
		case USER_FIELD_TYPE_DATETIME:
			$form->addElement('datepicker', 'extra_'.$field_details[1],$field_details[3], array('form_name'=>'profile'));
			$form->_elements[$form->_elementIndex['extra_'.$field_details[1]]]->setLocalOption('minYear',1900);	
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
			$form->applyFilter('theme', 'trim');
			$defaults['extra_'.$field_details[1]] = date('Y-m-d 12:00:00');
			$form -> setDefaults($defaults);
			break;
		case USER_FIELD_TYPE_DOUBLE_SELECT:
			foreach ($field_details[9] as $key=>$element) {
				if ($element[2][0] == '*') {
					$values['*'][$element[0]] = str_replace('*','',$element[2]);
				} else {
					$values[0][$element[0]] = $element[2];
				}
			}
			
			$group='';
			$group[] =& HTML_QuickForm::createElement('select', 'extra_'.$field_details[1],'',$values[0],'');
			$group[] =& HTML_QuickForm::createElement('select', 'extra_'.$field_details[1].'*','',$values['*'],'');
			$form->addGroup($group, 'extra_'.$field_details[1], $field_details[3], '&nbsp;');
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);

			// recoding the selected values for double : if the user has selected certain values, we have to assign them to the correct select form
			if (key_exists('extra_'.$field_details[1], $extra_data))
			{
				// exploding all the selected values (of both select forms)
				$selected_values = explode(';',$extra_data['extra_'.$field_details[1]]);
				$extra_data['extra_'.$field_details[1]]  =array();
				
				// looping through the selected values and assigning the selected values to either the first or second select form
				foreach ($selected_values as $key=>$selected_value)
				{
					if (key_exists($selected_value,$values[0]))
					{
						$extra_data['extra_'.$field_details[1]]['extra_'.$field_details[1]] = $selected_value;
					}
					else 
					{
						$extra_data['extra_'.$field_details[1]]['extra_'.$field_details[1].'*'] = $selected_value;
					}
				}
			}
			break;
		case USER_FIELD_TYPE_DIVIDER:
			$form->addElement('static',$field_details[1], '<br /><strong>'.$field_details[3].'</strong>');
			break;
	}
}
if (api_get_setting('profile', 'apikeys') == 'true') {
	$form->addElement('html','<div id="div_api_key">');
	$form->addElement('text', 'api_key_generate', get_lang('MyApiKey'), array('size' => 40,'id' => 'id_api_key_generate'));
	$form->addElement('html','</div>');
	$form->addElement('button', 'generate_api_key', get_lang('GenerateApiKey'),array('id' => 'id_generate_api_key','onclick' => 'generate_open_id_form()'));//generate_open_id_form()
}
//	SUBMIT
if (is_profile_editable()) {
	$form->addElement('style_submit_button', 'apply_change', get_lang('SaveSettings'), 'class="save"');
} else {
	$form->freeze();
}

$user_data = array_merge($user_data,$extra_data);
$form->setDefaults($user_data);

/*
==============================================================================
		FUNCTIONS
==============================================================================
*/

/*
-----------------------------------------------------------
	LOGIC FUNCTIONS
-----------------------------------------------------------
*/

/**
 * Can a user edit his/her profile?
 *
 * @return	boolean	Editability of the profile
 */
function is_profile_editable()
{
	return $GLOBALS['profileIsEditable'];
}

/*
-----------------------------------------------------------
	USER IMAGE FUNCTIONS
-----------------------------------------------------------
*/

/**
 * Upload a submitted user image.
 *
 * @param	$user_id User id
 * @return	The filename of the new picture or FALSE if the upload has failed
 */
function upload_user_image($user_id)
{
	/* Originally added by Miguel (miguel@cesga.es) - 2003-11-04
	 * Code Refactoring by Hugues Peeters (hugues.peeters@claroline.net) - 2003-11-24
	 * Moved inside a function and refactored by Thomas Corthals - 2005-11-04
	 */

	$image_path = UserManager::get_user_picture_path_by_id($user_id,'system',true);
	$image_repository = $image_path['dir'];
	$existing_image = $image_path['file'];
  	$file_extension = explode('.', $_FILES['picture']['name']);
	$file_extension = strtolower($file_extension[count($file_extension) - 1]);

	if (!file_exists($image_repository)) {
		mkpath($image_repository);
	} 
	
	if ($existing_image != '') {
		if (KEEP_THE_NAME_WHEN_CHANGE_IMAGE) {
			$picture_filename = $existing_image;
			$old_picture_filename = 'saved_'.date('Y_m_d_H_i_s').'_'.uniqid('').'_'.$existing_image;
		} else {
			$old_picture_filename = $existing_image;
			$picture_filename = (PREFIX_IMAGE_FILENAME_WITH_UID ? 'u'.$user_id.'_' : '').uniqid('').'.'.$file_extension;
		}

		if (KEEP_THE_OLD_IMAGE_AFTER_CHANGE) {
			@rename($image_repository.$existing_image, $image_repository.$old_picture_filename);
		} else {
			@unlink($image_repository.$existing_image);
		}
	} else {
		$picture_filename = (PREFIX_IMAGE_FILENAME_WITH_UID ? $user_id.'_' : '').uniqid('').'.'.$file_extension;
	}
	
	// get the picture and resize only if the picture is bigger width
	$picture_infos=getimagesize($_FILES['picture']['tmp_name']);	
	$type=$picture_infos[2];
	$small_temp = UserManager::resize_picture($_FILES['picture']['tmp_name'], 22); //small picture
	$medium_temp = UserManager::resize_picture($_FILES['picture']['tmp_name'], 85); //medium picture
	$temp = UserManager::resize_picture($_FILES['picture']['tmp_name'], 200); // normal picture
	$big_temp = new image($_FILES['picture']['tmp_name']); // original picture
	
    switch (!empty($type)) {
	    case 2 :
	    	$small_temp->send_image('JPG',$image_repository.'small_'.$picture_filename); 
	    	$medium_temp->send_image('JPG',$image_repository.'medium_'.$picture_filename);
	    	$temp->send_image('JPG',$image_repository.$picture_filename);
	    	$big_temp->send_image('JPG',$image_repository.'big_'.$picture_filename);	    		 
	    	break;
	    case 3 :
	    	$small_temp->send_image('PNG',$image_repository.'small_'.$picture_filename);
	    	$medium_temp->send_image('PNG',$image_repository.'medium_'.$picture_filename);
	    	$temp->send_image('PNG',$image_repository.$picture_filename);
	    	$big_temp->send_image('PNG',$image_repository.'big_'.$picture_filename);
	    	break;
	    case 1 :
	    	$small_temp->send_image('GIF',$image_repository.'small_'.$picture_filename);
	    	$medium_temp->send_image('GIF',$image_repository.'medium_'.$picture_filename);
	    	$temp->send_image('GIF',$image_repository.$picture_filename);
	    	$big_temp->send_image('GIF',$image_repository.'big_'.$picture_filename);	    		 
	    	break;
    }
    return $picture_filename;    
}

/**
 * Remove an existing user image.
 *
 * @param	$user_id	User id
 */
function remove_user_image($user_id)
{
	$image_path = UserManager::get_user_picture_path_by_id($user_id,'system');
	$image_repository = $image_path['dir'];
	$image = $image_path['file'];

	if ($image != '')
	{
		if (KEEP_THE_OLD_IMAGE_AFTER_CHANGE) 
		{
			@rename($image_repository.$image, $image_repository.'deleted_'.date('Y_m_d_H_i_s').'_'.$image);
		}
		else
		{
			@unlink($image_repository.$image);
		}
	}
}

/*
-----------------------------------------------------------
	PRODUCTIONS FUNCTIONS
-----------------------------------------------------------
*/

/**
 * Upload a submitted user production.
 *
 * @param	$user_id	User id
 * @return	The filename of the new production or FALSE if the upload has failed
 */
function upload_user_production($user_id)
{
	$image_path = UserManager::get_user_picture_path_by_id($user_id,'system',true);
	
	$production_repository = $image_path['dir'].$user_id.'/';

	if (!file_exists($production_repository)) 
	{
		mkpath($production_repository);
	}

	$filename = replace_dangerous_char($_FILES['production']['name']);
	$filename = disable_dangerous_file($filename);

	if(filter_extension($filename))
	{
		if (@move_uploaded_file($_FILES['production']['tmp_name'], $production_repository.$filename))
		{
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
	$user_id = $_user['user_id'];
	if ( $user_id != strval(intval($user_id)) || empty($password) ) { return false; }
	$table_user = Database :: get_main_table(TABLE_MAIN_USER);
	$password = api_get_encrypted_password($password);
	$sql_password="SELECT * FROM $table_user WHERE user_id='".$user_id."' AND password='".$password."'";
	$result=api_sql_query($sql_password, __FILE__, __LINE__);
	if (Database::num_rows($result)==0) {
		return false;
	} else {
		return true;
	}
}
/*
==============================================================================
		MAIN CODE
==============================================================================
*/
$filtered_extension = false;
$update_success = false;
$upload_picture_success = false;
$upload_production_success = false;

if (!empty($_SESSION['profile_update']))
{
	$update_success = ($_SESSION['profile_update'] == 'success');
	unset($_SESSION['profile_update']);
}

if (!empty($_SESSION['image_uploaded']))
{
	$upload_picture_success = ($_SESSION['image_uploaded'] == 'success');
	unset($_SESSION['image_uploaded']);
}

if (!empty($_SESSION['production_uploaded']))
{
	$upload_production_success = ($_SESSION['production_uploaded'] == 'success');
	unset($_SESSION['production_uploaded']);
} elseif (isset($_POST['remove_production'])) {
	foreach (array_keys($_POST['remove_production']) as $production)
	{
		UserManager::remove_user_production($_user['user_id'], urldecode($production));
	}

	if ($production_list = UserManager::build_production_list($_user['user_id'], true,true))
		$form->insertElementBefore($form->createElement('static', null, null, $production_list), 'productions_list');

	$form->removeElement('productions_list');

	$file_deleted = true;
} elseif ($form->validate()) {
	$wrong_current_password = false;
	$user_data = $form->exportValues();
	//
	// set password if a new one was provided
	if(!empty($user_data['password0'])){
		$val=check_user_password($user_data['password0']);
		if ($val==true){
			if (!empty($user_data['password1']))
			$password = $user_data['password1'];
		} else {
			$wrong_current_password = true;
		}
	}
	if (empty($user_data['password0']) && !empty($user_data['password1'])) {
			$wrong_current_password = true;		
	}

	// upload picture if a new one is provided
	if ($_FILES['picture']['size'])
	{
		if ($new_picture = upload_user_image($_user['user_id'])) 
		{
			$user_data['picture_uri'] = $new_picture;
			$_SESSION['image_uploaded'] = 'success';			
		}
	}
	// remove existing picture if asked
	elseif (!empty($user_data['remove_picture']))
	{
		remove_user_image($_user['user_id']);
		$user_data['picture_uri'] = '';
	}

	// upload production if a new one is provided
	if ($_FILES['production']['size'])
	{
		$res = upload_user_production($_user['user_id']);
		if(!$res)
		{
			//it's a bit excessive to assume the extension is the reason why upload_user_production() returned false, but it's true in most cases
			$filtered_extension = true;
		}
		else
		{
			$_SESSION['production_uploaded'] = 'success';	
		}
	}

	
	// remove values that shouldn't go in the database
	unset($user_data['password0'],$user_data['password1'], $user_data['password2'], $user_data['MAX_FILE_SIZE'],
		$user_data['remove_picture'], $user_data['apply_change']);

	// Following RFC2396 (http://www.faqs.org/rfcs/rfc2396.html), a URI uses ':' as a reserved character
	// we can thus ensure the URL doesn't contain any scheme name by searching for ':' in the string
	$my_user_openid=isset($user_data['openid']) ? $user_data['openid'] : '';
	if(!preg_match('/^[^:]*:\/\/.*$/',$my_user_openid))
	{	//ensure there is at least a http:// scheme in the URI provided
		$user_data['openid'] = 'http://'.$my_user_openid;
	}
	$extras = array();
	// build SQL query
	$sql = "UPDATE $table_user SET";
	unset($user_data['api_key_generate']);
	foreach($user_data as $key => $value)
	{
		if(substr($key,0,6)=='extra_') //an extra field
		{
			$extras[substr($key,6)] = $value;
		}
		else
		{
			$sql .= " $key = '".Database::escape_string($value)."',";
		}
	}

	if (isset($password))
	{
		$password = api_get_encrypted_password($password);
		$sql .= " password = '".Database::escape_string($password)."'";  		
	}
	else // remove trailing , from the query we have so far
	{
		$sql = rtrim($sql, ',');
	}

	$sql .= " WHERE user_id  = '".$_user['user_id']."'";		
	api_sql_query($sql, __FILE__, __LINE__);
	//update the extra fields
	foreach($extras as $key=>$value)
	{
		$myres = UserManager::update_extra_field_value($_user['user_id'],$key,$value);
	}
	
	// re-init the system to take new settings into account
	$uidReset = true;
	include (api_get_path(INCLUDE_PATH).'local.inc.php');
	$_SESSION['profile_update'] = 'success';
	header("Location: ".api_get_self()."?{$_SERVER['QUERY_STRING']}".($filtered_extension && strstr($_SERVER['QUERY_STRING'],'&fe=1')===false?'&fe=1':'').($wrong_current_password && strstr($_SERVER['QUERY_STRING'],'&cp=1')===false?'&cp=1':''));
	exit;
}

if (isset($_GET['show'])) {
	
	if ((api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true') ||(api_get_setting('allow_social_tool')=='true')) {
		$interbreadcrumb[]= array (
		'url' => '#',
		'name' => get_lang('SocialNetwork')
		);
	} elseif ((api_get_setting('allow_social_tool')=='false' && api_get_setting('allow_message_tool')=='true')) {
		$interbreadcrumb[]= array (
		'url' => '#',
		'name' => get_lang('MessageTool')
		);	
	}
}

/*
==============================================================================
		MAIN DISPLAY SECTION
==============================================================================
*/
Display :: display_header('');

if (api_get_setting('extended_profile') == 'true') {	
	echo '<div class="actions">';
	if (isset($_GET['show'])) {
		$show='&amp;show='.Security::remove_XSS($_GET['show']);
	} else {
		$show='';
	}
	if (isset($_GET['type']) && $_GET['type']=='extended') {
		echo '<a href="profile.php?type=reduced'.$show.'">'.Display::return_icon('edit.gif',get_lang('EditNormalProfile')).'&nbsp;'.get_lang('EditNormalProfile').'</a>';			
	} else {
		echo '<a href="profile.php?type=extended'.$show.'">'.Display::return_icon('edit.gif',get_lang('EditExtendProfile')).'&nbsp;'.get_lang('EditExtendProfile').'</a>';
	}
	if (api_get_setting('allow_social_tool')=='true' && api_get_setting('allow_message_tool')=='true') { 
		echo '<a href="../social/profile.php">'.Display::return_icon('shared_profile.png',get_lang('ViewSharedProfile')).'&nbsp;'.get_lang('ViewSharedProfile').'</a>';
	}
	echo '</div>';
} 

if (!empty($file_deleted)) {
	Display :: display_confirmation_message(get_lang('FileDeleted'),false);
} elseif (!empty($update_success)) {
	$message=get_lang('ProfileReg');	

	if ($upload_picture_success == true) {
		$message.='<br /> '.get_lang('PictureUploaded');
	}
	
	if ($upload_production_success == true) {
		$message.='<br />'.get_lang('ProductionUploaded');
	}
		
	Display :: display_confirmation_message($message,false);	
}

if(!empty($warning_msg))
{
	Display :: display_warning_message($warning_msg,false);
}

//User picture size is calculated from SYSTEM path
$image_syspath = UserManager::get_user_picture_path_by_id(api_get_user_id(),'system',false,true);
$image_syspath['dir'].$image_syspath['file'];

$image_size = @getimagesize($image_syspath['dir'].$image_syspath['file']);

//Web path
$image_path = UserManager::get_user_picture_path_by_id(api_get_user_id(),'web',false,true);
$image_dir = $image_path['dir'];
$image = $image_path['file'];
$image_file = $image_dir.$image;
$img_attributes = 'src="'.$image_file.'?rand='.time().'" '
	.'alt="'.$user_data['lastname'].' '.$user_data['firstname'].'" '
	.'style="float:'.($text_dir == 'rtl' ? 'left' : 'right').'; margin-top:0px;padding:5px;" ';
if ($image_size[0] > 300) {
	//limit display width to 300px
	$img_attributes .= 'width="300" ';	
}

// get the path,width and height from original picture
$big_image = $image_dir.'big_'.$image;

$big_image_size = @getimagesize(api_url_to_local_path($big_image));
$big_image_width= $big_image_size[0];
$big_image_height= $big_image_size[1];
$url_big_image = $big_image.'?rnd='.time();

echo '<div id="image-message-container" style="float:right;padding:5px;width:250px;" >';
if ($image=='unknown.jpg') {
	echo '<img '.$img_attributes.' />';
} else {
	echo '<input type="image" '.$img_attributes.' onclick="return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');"/>';
}
if (api_get_setting('allow_message_tool')=='true') {	
	include (api_get_path(LIBRARY_PATH).'message.lib.php');
	$number_of_new_messages = MessageManager::get_new_messages();
	$number_of_outbox_message=MessageManager::get_number_of_messages_sent();
	$cant_out_box=' ('.$number_of_outbox_message.')';
	$cant_msg = ' ('.$number_of_new_messages.')';
	$number_of_new_messages_of_friend=UserFriend::get_message_number_invitation_by_user_id(api_get_user_id());
	//echo '<div class="message-view" style="display:none;">'.get_lang('ViewMessages').'</div>';
	echo '<div class="message-content">
			<h2 class="message-title">'.get_lang('Messages').'</h2>
			<p>
				<a href="../social/index.php#remote-tab-2" class="message-body">'.get_lang('Inbox').$cant_msg.' </a><br />
				<a href="../social/index.php#remote-tab-3" class="message-body">'.get_lang('Outbox').$cant_out_box.'</a><br />
			</p>';		
	
	/* if (api_get_setting('allow_social_tool')=='true') {		 
		 if ($number_of_new_messages_of_friend>0) {
			echo '<div class="message-content-internal">';		
			echo '<a href="../social/index.php#remote-tab-4" style="color:#000000">'. Display::return_icon('info3.gif',get_lang('NewMessage'),'align="absmiddle"').'&nbsp;'.get_lang('Invitation ').'('.$number_of_new_messages_of_friend.')'.'</a>';
			echo '</div><br/>';		    
		 }			
	 }*/
	echo '<img src="../img/delete.gif" alt="'.get_lang('Close').'" title="'.get_lang('Close').'"  class="message-delete" />';
	if ($number_of_new_messages_of_friend>0) {
		echo '<br/>';
	}
	echo '</div>';
}
echo '</div>';
$form->display();
Display :: display_footer();
?>
