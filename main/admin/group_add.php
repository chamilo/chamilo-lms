<?php
/* For licensing terms, see /dokeos_license.txt */
/**
*	@package chamilo.admin
*/

// Language files that should be included
$language_file = array('admin', 'registration','userInfo');

$cidReset = true;

// Including necessary libraries.
require '../inc/global.inc.php';
$libpath = api_get_path(LIBRARY_PATH);
require_once $libpath.'fileUpload.lib.php';

// Section for the tabs
$this_section = SECTION_PLATFORM_ADMIN;

// User permissions
api_protect_admin_script();

$htmlHeadXtra[] = '<script>
textarea = "";
num_characters_permited = 255;
function text_longitud(){
   num_characters = document.forms[0].description.value.length;
  if (num_characters > num_characters_permited){
      document.forms[0].description.value = textarea;
   }else{
      textarea = document.forms[0].description.value;
   }
}
</script>';

// Database table definitions
if (!empty($_GET['message'])) {
	$message = urldecode($_GET['message']);
}

$interbreadcrumb[] = array ('url' => 'index.php', 'name' => get_lang('PlatformAdmin'));
$tool_name = get_lang('AddGroups');

// Create the form
$form = new FormValidator('group_add');
$form->addElement('header', $tool_name);

// name
$form->addElement('text', 'name', get_lang('Name'), array('size'=>60, 'maxlength'=>120));
$form->applyFilter('name', 'html_filter');
$form->applyFilter('name', 'trim');
$form->addRule('name', get_lang('ThisFieldIsRequired'), 'required');

// Description
$form->addElement('textarea', 'description', get_lang('Description'), array('rows'=>3, 'cols'=>58, onKeyDown => "text_longitud()", onKeyUp => "text_longitud()"));
$form->applyFilter('description', 'html_filter');
$form->applyFilter('description', 'trim');

// url
$form->addElement('text', 'url', get_lang('URL'), array('size'=>35));
$form->applyFilter('url', 'html_filter');
$form->applyFilter('url', 'trim');

// Picture
$form->addElement('file', 'picture', get_lang('AddPicture'));
$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);

//Group Parentship
$groups = array();
$groups[0] = get_lang('NoParentship');
$groups = $groups + GroupPortalManager::get_groups_list($group_id);
$group_data['parent_group'] = GroupPortalManager::get_parent_group($group_id);
$form->addElement('select', 'parent_group', get_lang('GroupParentship'), $groups, array());

// Status
$status = array();
$status[GROUP_PERMISSION_OPEN] 		= get_lang('Open');
$status[GROUP_PERMISSION_CLOSED]	= get_lang('Closed');

$form->addElement('select', 'visibility', get_lang('GroupPermissions'), $status);

// Set default values
$defaults['status'] = GROUP_PERMISSION_OPEN;

$form->setDefaults($defaults);

// Submit button
$form->addElement('style_submit_button', 'submit', get_lang('Add'), 'class="add"');

// Validate form
if( $form->validate()) {
	$check = Security::check_token('post');
	if ($check) {
		$values = $form->exportValues();

		$picture_element = & $form->getElement('picture');
		$picture 		= $picture_element->getValue();
		$picture_uri 	= '';
		$name 			= $values['name'];
		$description	= $values['description'];
		$url 			= $values['url'];
		$status 		= intval($values['visibility']);
		$picture 		= $_FILES['picture'];
        $parent_group_id = intval($values['parent_group']);

		$group_id = GroupPortalManager::add($name, $description, $url, $status);
        GroupPortalManager::set_parent_group($group_id,$parent_group_id);

		if (!empty($picture['name'])) {
			$picture_uri = GroupPortalManager::update_group_picture($group_id, $_FILES['picture']['name'], $_FILES['picture']['tmp_name']);
			GroupPortalManager::update($group_id, $name, $description, $url,$status, $picture_uri);
		}

		//@todo send emails

/*		if (!empty($email) && $send_mail) {
			$recipient_name = api_get_person_name($firstname, $lastname, null, PERSON_NAME_EMAIL_ADDRESS);
			$emailsubject = '['.api_get_setting('siteName').'] '.get_lang('YourReg').' '.api_get_setting('siteName');

			$sender_name = api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'), null, PERSON_NAME_EMAIL_ADDRESS);
			$email_admin = api_get_setting('emailAdministrator');

			if ($_configuration['multiple_access_urls']) {
				$access_url_id = api_get_current_access_url_id();
				if ($access_url_id != -1) {
					$url = api_get_access_url($access_url_id);
					$emailbody = get_lang('Dear')." ".stripslashes(api_get_person_name($firstname, $lastname)).",\n\n".get_lang('YouAreReg')." ".api_get_setting('siteName') ." ".get_lang('WithTheFollowingSettings')."\n\n".get_lang('Username')." : ". $username ."\n". get_lang('Pass')." : ".stripslashes($password)."\n\n" .get_lang('Address') ." ". api_get_setting('siteName') ." ". get_lang('Is') ." : ". $url['url'] ."\n\n". get_lang('Problem'). "\n\n". get_lang('Formula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n". get_lang('Manager'). " ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".api_get_setting('emailAdministrator');
				}
			}
			else {
				$emailbody = get_lang('Dear')." ".stripslashes(api_get_person_name($firstname, $lastname)).",\n\n".get_lang('YouAreReg')." ".api_get_setting('siteName') ." ".get_lang('WithTheFollowingSettings')."\n\n".get_lang('Username')." : ". $username ."\n". get_lang('Pass')." : ".stripslashes($password)."\n\n" .get_lang('Address') ." ". api_get_setting('siteName') ." ". get_lang('Is') ." : ". $_configuration['root_web'] ."\n\n". get_lang('Problem'). "\n\n". get_lang('Formula').",\n\n".api_get_person_name(api_get_setting('administratorName'), api_get_setting('administratorSurname'))."\n". get_lang('Manager'). " ".api_get_setting('siteName')."\nT. ".api_get_setting('administratorTelephone')."\n" .get_lang('Email') ." : ".api_get_setting('emailAdministrator');
			}
			@api_mail($recipient_name, $email, $emailsubject, $emailbody, $sender_name, $email_admin);
		}*/

		Security::clear_token();
		$tok = Security::get_token();
		header('Location: group_list.php?action=show_message&message='.urlencode(get_lang('GroupAdded')).'&sec_token='.$tok);
        exit ();
	}
} else {
	if (isset($_POST['submit'])) {
		Security::clear_token();
	}
	$token = Security::get_token();
	$form->addElement('hidden', 'sec_token');
	$form->setConstants(array('sec_token' => $token));
}

// Display form
Display::display_header($tool_name);
//api_display_tool_title($tool_name);
if(!empty($message)){
	Display::display_normal_message(stripslashes($message));
}
$form->display();

// Footer
Display::display_footer();