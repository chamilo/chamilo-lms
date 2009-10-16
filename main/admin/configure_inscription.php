<?php
/* For licensing terms, see /dokeos_license.txt */
/**
==============================================================================
*	This script displays a form for registering new users.
*	@package	 dokeos.auth
==============================================================================
*/
// name of the language file that needs to be included
$language_file = array('registration','admin');
$cidReset = true;
include ("../inc/global.inc.php");

require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once (api_get_path(CONFIGURATION_PATH).'profile.conf.php');
require_once(api_get_path(INCLUDE_PATH).'lib/mail.lib.inc.php');
require_once(api_get_path(INCLUDE_PATH).'lib/legal.lib.php');

//require_once(api_get_path(LIBRARY_PATH).'fileManage.lib.php');
//require_once(api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
//require_once (api_get_path(LIBRARY_PATH).'image.lib.php');

// Load terms & conditions from the current lang
if (get_setting('allow_terms_conditions')=='true') {	
	$get = array_keys($_GET);
	if (isset($get)) {
		if ($get[0]=='legal'){				
			//$language = api_get_setting('platformLanguage');
			$language = api_get_interface_language();
			$language = api_get_language_id($language);
			$term_preview= LegalManager::get_last_condition($language);
			if ($term_preview==false) {
				//look for the default language
				$language = api_get_setting('platformLanguage');				
				$language = api_get_language_id($language);
				$term_preview= LegalManager::get_last_condition($language);
			}					
			$tool_name = get_lang('TermsAndConditions');
			Display :: display_header('');
			echo '<div class="actions-title">';
			echo $tool_name;
			echo '</div>';
			if (!empty($term_preview['content']))
				echo $term_preview['content'];
			else 
				echo get_lang('ComingSoon');
			Display :: display_footer();
			exit;
		}
	}
}

$action=Security::remove_XSS($_GET['action']);
$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$tool_name = get_lang('ConfigureInscription');
if(!empty($action)){
	$interbreadcrumb[]=array('url' => 'configure_inscription.php',"name" => get_lang('ConfigureInscription'));
	switch($action){
		case "edit_top":
			$tool_name=get_lang("EditTopRegister");
			break;
	}
}

$lang = ''; //el for "Edit Language"
if(!empty($_SESSION['user_language_choice'])) {
	$lang=$_SESSION['user_language_choice'];
} elseif(!empty($_SESSION['_user']['language'])) {
	$lang=$_SESSION['_user']['language'];
} else {
	$lang=get_setting('platformLanguage');
}

// ----- Ensuring availability of main files in the corresponding language -----
if ($_configuration['multiple_access_urls']==true) {
	$access_url_id = api_get_current_access_url_id();										 
	if ($access_url_id != -1){						
		$url_info = api_get_access_url($access_url_id);
		// "http://" and the final "/" replaced						
		$url = substr($url_info['url'],7,strlen($url_info['url'])-8);						
		$clean_url = replace_dangerous_char($url);
		$clean_url = str_replace('/','-',$clean_url);
		$clean_url = $clean_url.'/';
		
		$homep = '../../home/'; //homep for Home Path			
		$homep_new = '../../home/'.$clean_url; //homep for Home Path added the url				
		$new_url_dir = api_get_path(SYS_PATH).'home/'.$clean_url;
		//we create the new dir for the new sites
		if (!is_dir($new_url_dir)){		
			umask(0);
			$perm = api_get_setting('permissions_for_new_directories');
			$perm = octdec(!empty($perm)?$perm:'0755');
			mkdir($new_url_dir, $perm);
		}							
	}
} else {			
	$homep_new ='';		
	$homep = '../../home/'; //homep for Home Path
}



$topf 	 = 'register_top'; //topf for Top File
$ext 	 = '.html'; //ext for HTML Extension - when used frequently, variables are
$homef = array($topf);

// If language-specific file does not exist, create it by copying default file
foreach($homef as $my_file) {
	if ($_configuration['multiple_access_urls']==true) {
		if (!file_exists($homep_new.$my_file.'_'.$lang.$ext)) {
			copy($homep.$my_file.$ext,$homep_new.$my_file.'_'.$lang.$ext);
		}		
	} else {	
		if (!file_exists($homep.$my_file.'_'.$lang.$ext)) {
			copy($homep.$my_file.$ext,$homep.$my_file.'_'.$lang.$ext);
		}
	}
}

if(!empty($action)) {
	if($_POST['formSent']) {
		switch($action) {
			case 'edit_top':
				// Filter
				$home_top='';
				if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
					$home_top=WCAG_Rendering::prepareXHTML();
				} else {
					$home_top=trim(stripslashes($_POST['register_top']));
				}
				// Write
				if (file_exists($homep.$topf.'_'.$lang.$ext)) {
					if(is_writable($homep.$topf.'_'.$lang.$ext)) {						
						$fp=fopen($homep.$topf.'_'.$lang.$ext,"w");
						fputs($fp,$home_top);
						fclose($fp);
					} else {
						$errorMsg=get_lang('HomePageFilesNotWritable');
					}
				} else {
					//File does not exist					
					$fp=fopen($homep.$topf.'_'.$lang.$ext,"w");
					fputs($fp,$home_top);
					fclose($fp);
				}
				break;
		}
		if(empty($errorMsg)) {
			header('Location: '.api_get_self());
			exit();
		}
	} else {
		switch($action) {
			case 'edit_top':
				// This request is only the preparation for the update of the home_top
				$home_top = '';
				if(is_file($homep.$topf.'_'.$lang.$ext) && is_readable($homep.$topf.'_'.$lang.$ext)) {
					$home_top=file_get_contents($homep.$topf.'_'.$lang.$ext);
				} elseif(is_file($homep.$topf.$lang.$ext) && is_readable($homep.$topf.$lang.$ext)) {
					$home_top=file_get_contents($homep.$topf.$lang.$ext);
				} else {
					$errorMsg=get_lang('HomePageFilesNotReadable');
				}		
				break;
		}
	}
}

Display :: display_header($tool_name);

echo '<div class="actions-title">';
echo $tool_name;
echo '</div>';

// Forbidden to self-register
if (get_setting('allow_registration') == 'false') {
	api_not_allowed();
}
//api_display_tool_title($tool_name);
if (get_setting('allow_registration')=='approval') {
	Display::display_normal_message(get_lang('YourAccountHasToBeApproved'));
}
//if openid was not found
if (!empty($_GET['openid_msg']) && $_GET['openid_msg'] == 'idnotfound') {
	Display::display_warning_message(get_lang('OpenIDCouldNotBeFoundPleaseRegister'));	
}

$form = new FormValidator('registration');
if (get_setting('allow_terms_conditions')=='true') {
	if (!isset($_SESSION['update_term_and_condition'][1])) {
		$display_all_form=true;
	} else {
		$display_all_form=false;
	}
} else {
	$display_all_form=true;
}
if ($display_all_form===true) {
	
//	LAST NAME and FIRST NAME
$form->addElement('text', 'lastname',  get_lang('LastName'),  array('size' => 40, 'disabled' => 'disabled'));
$form->applyFilter('lastname','trim');
$form->addElement('text', 'firstname', get_lang('FirstName'), array('size' => 40, 'disabled' => 'disabled'));
$form->applyFilter('firstname','trim');
$form->addRule('lastname',  get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('firstname', get_lang('ThisFieldIsRequired'), 'required');
//	EMAIL
$form->addElement('text', 'email', get_lang('Email'), array('size' => 40, 'disabled' => 'disabled'));
if (api_get_setting('registration', 'email') == 'true')
	$form->addRule('email', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('email', get_lang('EmailWrong'), 'email');
if (api_get_setting('openid_authentication')=='true') {
	$form->addElement('text', 'openid', get_lang('OpenIDURL'), array('size' => 40, 'disabled' => 'disabled'));	
}

//	USERNAME
$form->addElement('text', 'username', get_lang('UserName'), array('size' => 20, 'disabled' => 'disabled'));
$form->addRule('username', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('username', get_lang('UsernameWrong'), 'username');
$form->addRule('username', get_lang('UserTaken'), 'username_available');
$form->addRule('username', sprintf(get_lang('UsernameMaxXCharacters'),'20'), 'maxlength',20);
//	PASSWORD
$form->addElement('password', 'pass1', get_lang('Pass'),         array('size' => 40, 'disabled' => 'disabled'));
$form->addElement('password', 'pass2', get_lang('Confirmation'), array('size' => 40, 'disabled' => 'disabled'));
$form->addRule('pass1', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('pass2', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule(array('pass1', 'pass2'), get_lang('PassTwo'), 'compare');
if (CHECK_PASS_EASY_TO_FIND)
	$form->addRule('password1', get_lang('PassTooEasy').': '.api_generate_password(), 'callback', 'api_check_password');

//	PHONE
$form->addElement('text', 'phone', get_lang('Phone'), array('size' => 40, 'disabled' => 'disabled'));
if (api_get_setting('registration', 'phone') == 'true')
	$form->addRule('phone', get_lang('ThisFieldIsRequired'), 'required');

//	LANGUAGE
if (get_setting('registration', 'language') == 'true') {
	$form->addElement('select_language', 'language', get_lang('Language'), '', array('disabled' => 'disabled'));
}
//	STUDENT/TEACHER
if (get_setting('allow_registration_as_teacher') <> 'false') {
	$form->addElement('radio', 'status', get_lang('Status'), get_lang('RegStudent'), STUDENT, array('disabled' => 'disabled'));
	$form->addElement('radio', 'status', null, get_lang('RegAdmin'), COURSEMANAGER, array('disabled' => 'disabled'));
}

//	EXTENDED FIELDS
if (api_get_setting('extended_profile') == 'true' AND api_get_setting('extendedprofile_registration','mycomptetences') == 'true')
{
	$form->add_html_editor('competences', get_lang('MyCompetences'), false, false, array('ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130'));
}
if (api_get_setting('extended_profile') == 'true' AND api_get_setting('extendedprofile_registration','mydiplomas') == 'true')
{
	$form->add_html_editor('diplomas', get_lang('MyDiplomas'), false, false, array('ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130'));
}
if (api_get_setting('extended_profile') == 'true' AND api_get_setting('extendedprofile_registration','myteach') == 'true')
{
	$form->add_html_editor('teach', get_lang('MyTeach'), false, false, array('ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130'));
}
if (api_get_setting('extended_profile') == 'true' AND api_get_setting('extendedprofile_registration','mypersonalopenarea') == 'true')
{
	$form->add_html_editor('openarea', get_lang('MyPersonalOpenArea'), false, false, array('ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130'));
}
if (api_get_setting('extended_profile') == 'true')
{
	if (api_get_setting('extendedprofile_registrationrequired','mycomptetences') == 'true')
	{
		$form->addRule('competences', get_lang('ThisFieldIsRequired'), 'required');
	}
	if (api_get_setting('extendedprofile_registrationrequired','mydiplomas') == 'true')
	{
		$form->addRule('diplomas', get_lang('ThisFieldIsRequired'), 'required');
	}
	if (api_get_setting('extendedprofile_registrationrequired','myteach') == 'true')
	{
		$form->addRule('teach', get_lang('ThisFieldIsRequired'), 'required');
	}
	if (api_get_setting('extendedprofile_registrationrequired','mypersonalopenarea') == 'true')
	{
		$form->addRule('openarea', get_lang('ThisFieldIsRequired'), 'required');
	}
}
// EXTRA FIELDS
$extra = UserManager::get_extra_fields(0,50,5,'ASC');
$extra_data = UserManager::get_extra_user_data(api_get_user_id(),true);
foreach ($extra as $id => $field_details) {
	if ($field_details[6] == 0) {
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
			$form->add_html_editor('extra_'.$field_details[1], $field_details[3], false, false, array('ToolbarSet' => 'Profile', 'Width' => '100%', 'Height' => '130'));
			//$form->addElement('textarea', 'extra_'.$field_details[1], $field_details[3], array('size' => 80));
			$form->applyFilter('extra_'.$field_details[1], 'stripslashes');
			$form->applyFilter('extra_'.$field_details[1], 'trim');
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
			break;
		case USER_FIELD_TYPE_RADIO:
			$group = array();
			foreach ($field_details[9] as $option_id => $option_details) {
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
			foreach ($field_details[9] as $option_id => $option_details) {
				$options[$option_details[1]] = $option_details[2];
			}
			$form->addElement('select','extra_'.$field_details[1],$field_details[3],$options,array('multiple' => 'multiple'));
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);	
			break;
		case USER_FIELD_TYPE_DATE:
			$form->addElement('datepickerdate', 'extra_'.$field_details[1], $field_details[3],array('form_name'=>'registration'));
			$form->_elements[$form->_elementIndex['extra_'.$field_details[1]]]->setLocalOption('minYear',1900);
			$defaults['extra_'.$field_details[1]] = date('Y-m-d 12:00:00');
			$form -> setDefaults($defaults);
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
			$form->applyFilter('theme', 'trim');
			break;
		case USER_FIELD_TYPE_DATETIME:
			$form->addElement('datepicker', 'extra_'.$field_details[1], $field_details[3],array('form_name'=>'registration'));
			$form->_elements[$form->_elementIndex['extra_'.$field_details[1]]]->setLocalOption('minYear',1900);
			$defaults['extra_'.$field_details[1]] = date('Y-m-d 12:00:00');
			$form -> setDefaults($defaults);
			if ($field_details[7] == 0)	$form->freeze('extra_'.$field_details[1]);
			$form->applyFilter('theme', 'trim');
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
			if (key_exists('extra_'.$field_details[1], $extra_data)) {
				// exploding all the selected values (of both select forms)
				$selected_values = explode(';',$extra_data['extra_'.$field_details[1]]);
				$extra_data['extra_'.$field_details[1]]  =array();
				
				// looping through the selected values and assigning the selected values to either the first or second select form
				foreach ($selected_values as $key=>$selected_value) {
					if (key_exists($selected_value,$values[0])) {
						$extra_data['extra_'.$field_details[1]]['extra_'.$field_details[1]] = $selected_value;
					} else {
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

}
//------------ Terms and conditions
if (get_setting('allow_terms_conditions')=='true') {	
	//$language = api_get_setting('platformLanguage');
	$language = api_get_interface_language();
	$language = api_get_language_id($language);
	$term_preview= LegalManager::get_last_condition($language);	
	
	if ($term_preview==false) { 
		//we load from the platform
		$language = api_get_setting('platformLanguage');
		$language = api_get_language_id($language);
		$term_preview= LegalManager::get_last_condition($language);
		//if is false we load from english
		if ($term_preview==false){
			$language = api_get_language_id('english'); //this must work
			$term_preview= LegalManager::get_last_condition($language);	
		}					
	}	
	// Version and language //password
	$form->addElement('hidden', 'legal_accept_type',$term_preview['version'].':'.$term_preview['language_id']);
	$form->addElement('hidden', 'legal_info',$term_preview['legal_id'].':'.$term_preview['language_id']);	
	if (isset($_SESSION['info_current_user'][1]) && isset($_SESSION['info_current_user'][2])) {
		$form->addElement('hidden', 'login',$_SESSION['info_current_user'][1]);
		$form->addElement('hidden', 'password',$_SESSION['info_current_user'][2]);	
	}
	if($term_preview['type'] == 1) {
		$form->addElement('checkbox', 'legal_accept', null, get_lang('IHaveReadAndAgree').'&nbsp;<a href="inscription.php?legal" target="_blank">'.get_lang('TermsAndConditions').'</a>');		
		$form->addRule('extra_legal_accept',  get_lang('ThisFieldIsRequired'), 'required');
	} else {
		if (!empty($term_preview['content'])) {			
			$preview = LegalManager::show_last_condition($term_preview);
			$term_preview  = '<div class="row">
					<div class="label">'.get_lang('TermsAndConditions').'</div>
					<div class="formw">
					'.$preview.'
					<br />				
					</div>
					</div>';		
			$form->addElement('html', $term_preview);
		}		
	}
}

$form->addElement('style_submit_button', 'submit', get_lang('RegisterUser'),array('class' => 'save', 'disabled' => 'disabled'));
$defaults['status'] = STUDENT;
$form->setDefaults($defaults);
if(isset($_SESSION["user_language_choice"]) && $_SESSION["user_language_choice"]!=""){
	$defaults['language'] = $_SESSION["user_language_choice"];
}
else{
	$defaults['language'] = api_get_setting('platformLanguage');
}
if(!empty($_GET['username']))
{
	$defaults['username'] = Security::remove_XSS($_GET['username']);
}
if(!empty($_GET['email']))
{
	$defaults['email'] = Security::remove_XSS($_GET['email']);
}

if(!empty($_GET['phone']))
{
	$defaults['phone'] = Security::remove_XSS($_GET['phone']);
}

if (api_get_setting('openid_authentication')=='true' && !empty($_GET['openid']))
{
	$defaults['openid'] = Security::remove_XSS($_GET['openid']);	
}

switch($action){
	case 'edit_top':
		if($action == 'edit_top') {
			$name= $topf;
			$open = $home_top;
		} else {
			$name = $newsf;
			$open=@file_get_contents($homep.$newsf.'_'.$lang.$ext);

		}

		if(!empty($errorMsg)) {
			Display::display_normal_message($errorMsg); //main API
		}

		$default = array();
		$form = new FormValidator('configure_homepage_'.$action, 'post', api_get_self().'?action='.$action, '', array('style' => 'margin: 0px;'));
		$renderer =& $form->defaultRenderer();
		$renderer->setHeaderTemplate('');
		$renderer->setFormTemplate('<form{attributes}><table border="0" cellpadding="5" cellspacing="0" width="100%">{content}</table></form>');
		$renderer->setElementTemplate('<tr><td>{element}</td></tr>');
		$renderer->setRequiredNoteTemplate('');
		$form->addElement('hidden', 'formSent', '1');
		if (api_get_setting('wcag_anysurfer_public_pages')=='true') {
			//TODO: review these lines
			// Print WCAG-specific HTML editor
			$html = '<tr><td>';
			$html .= WCAG_Rendering::create_xhtml($open);
			$html .= '</td></tr>';
			$form->addElement('html', $html);
		} else {
			$default[$name] = str_replace('{rel_path}', api_get_path(REL_PATH), $open);
			$form->add_html_editor($name, '', true, false, array('ToolbarSet' => 'PortalHomePage', 'Width' => '100%', 'Height' => '400'));
		}
		$form->addElement('style_submit_button', null, get_lang('Save'), 'class="save"');
		$form->setDefaults($default);
		$form->display();
		break;
	default:
		/*******************************/
		echo '&nbsp;&nbsp;<a href="'.api_get_self().'?action=edit_top">'.Display::display_icon('edit.gif', get_lang('Edit')).'</a> <a href="'.api_get_self().'?action=edit_top">'.get_lang('EditNotice').'</a>';
		echo '<div class="note">';
		$home_notice = '';
		if(file_exists($homep.$topf.'_'.$lang.$ext)) {
		$home_notice = @file_get_contents($homep.$topf.'_'.$lang.$ext);
		} else {
			$home_notice = @file_get_contents($homep.$topf.$ext);
		}
		echo $home_notice;
		echo '</div>'; 
		/*******************************/
		$form->display();
		break;
}

/*
==============================================================================
		FOOTER
==============================================================================
*/

Display :: display_footer();
?>