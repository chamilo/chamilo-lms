<?php
/* For licensing terms, see /license.txt */
/**
*	@package chamilo.admin
*	@author Julio Montoya <gugli100@gmail.com>
*/

$language_file = 'admin';
$cidReset = true;
require_once '../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

//api_protect_admin_script();
api_protect_global_admin_script();
if (!$_configuration['multiple_access_urls']) {
	header('Location: index.php');
	exit;
}

require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
require_once api_get_path(LIBRARY_PATH).'security.lib.php';
require_once api_get_path(LIBRARY_PATH).'urlmanager.lib.php';

// Create the form
$form = new FormValidator('add_url');

if( $form->validate()) {
	$check = Security::check_token('post');
	if($check) {
		$url_array = $form->getSubmitValues();
		$url = Security::remove_XSS($url_array['url']);
		$description = Security::remove_XSS($url_array['description']);
		$active = intval($url_array['active']);
		$url_id = $url_array['id'];
		$url_to_go='access_urls.php';
		if ($url_id!='') {
			//we can't change the status of the url with id=1
			if ($url_id==1)
				$active=1;
			//checking url
			if (substr($url,strlen($url)-1, strlen($url))=='/') {
				UrlManager::udpate($url_id, $url, $description, $active);
			} else {
				UrlManager::udpate($url_id, $url.'/', $description, $active);
			}
			// URL Images
			$url_images_dir = api_get_path(SYS_PATH).'custompages/url-images/';
			$image_fields = array("url_image_1", "url_image_2", "url_image_3");
			foreach ($image_fields as $image_field) {
				if ($_FILES[$image_field]['error'] == 0) {
					// Hardcoded: only PNG files allowed
					if (end(explode('.', $_FILES[$image_field]['name'])) == 'png') {
						move_uploaded_file($_FILES[$image_field]['tmp_name'], $url_images_dir.$url_id.'_'.$image_field.'.png');
					}
					// else fail silently
				}
				// else fail silently
			}
			$url_to_go='access_urls.php';
			$message=get_lang('URLEdited');
		} else {
			$num = UrlManager::url_exist($url);
			if ($num == 0) {
				//checking url
				if (substr($url,strlen($url)-1, strlen($url))=='/') {
					UrlManager::add($url, $description, $active);
				} else {
					//create
					UrlManager::add($url.'/', $description, $active);
				}
				$message = get_lang('URLAdded');
				$url_to_go='access_urls.php';
			} else {
				$url_to_go='access_url_edit.php';
				$message = get_lang('URLAlreadyAdded');
			}
			// URL Images
			$url .= (substr($url,strlen($url)-1, strlen($url))=='/') ? '' : '/';
			$url_id = UrlManager::get_url_id($url);
			$url_images_dir = api_get_path(SYS_PATH).'custompages/url-images/';
			$image_fields = array("url_image_1", "url_image_2", "url_image_3");
			foreach ($image_fields as $image_field) {
				if ($_FILES[$image_field]['error'] == 0) {
					// Hardcoded: only PNG files allowed
					if (end(explode('.', $_FILES[$image_field]['name'])) == 'png') {
						move_uploaded_file($_FILES[$image_field]['tmp_name'], $url_images_dir.$url_id.'_'.$image_field.'.png');
					}
					// else fail silently
				}
				// else fail silently
			}
		}
		Security::clear_token();
		$tok = Security::get_token();
		header('Location: '.$url_to_go.'?action=show_message&message='.urlencode($message).'&sec_token='.$tok);
		exit();
	}
} else {
	if(isset($_POST['submit'])) {
		Security::clear_token();
	}
	$token = Security::get_token();
	$form->addElement('hidden','sec_token');
	$form->setConstants(array('sec_token' => $token));
}


$form->addElement('text','url',get_lang('URL'),array('size'=>'30'));
$form->addRule('url', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('url', '', 'maxlength',254);
$form->addElement('textarea','description',get_lang('Description'));

//the first url with id = 1 will be always active
if ($_GET['url_id'] != 1) {
	$form->addElement('checkbox','active',get_lang('Active'));
}

//$form->addRule('checkbox', get_lang('ThisFieldIsRequired'), 'required');

$defaults['url']='http://';
$form->setDefaults($defaults);

$submit_name = get_lang('AddUrl');
if (isset($_GET['url_id'])) {
	$url_id = Database::escape_string($_GET['url_id']);
	$num_url_id = UrlManager::url_id_exist($url_id);
	if($num_url_id != 1) {
		header('Location: access_urls.php');
		exit();
	}
	$url_data = UrlManager::get_url_data_from_id($url_id);
	$form->addElement('hidden','id',$url_data['id']);
	$form->setDefaults($url_data);
	$submit_name = get_lang('AddUrl');
}

if (!$_configuration['multiple_access_urls'])
	header('Location: index.php');

$tool_name = get_lang('AddUrl');
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => 'access_urls.php', "name" => get_lang('MultipleAccessURLs'));
Display :: display_header($tool_name);

if (isset ($_GET['action'])) {
	switch ($_GET['action']) {
		case 'show_message' :
			Display :: display_normal_message(stripslashes($_GET['message']));
			break;
	}
}

// URL Images
$form->addElement('file','url_image_1','URL Image 1 (PNG)');
$form->addElement('file','url_image_2','URL Image 2 (PNG)');
$form->addElement('file','url_image_3','URL Image 3 (PNG)');

// Submit button
$form->addElement('style_submit_button', 'submit', $submit_name, 'class="add"');
$form->display();
