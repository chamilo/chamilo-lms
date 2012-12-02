<?php
/* For licensing terms, see /license.txt */
/**
 * Allows anyone to upload synchronization data
 * @package chamilo.sincro
 * @author Julio Montoya <gugli100@gmail.com>
 * @author Yannick Warnier <yannick.warnier@beeznest.com>
 */
/**
 * Initialization
 */
$language_file = 'admin';
$cidReset = true;
require_once '../main/inc/global.inc.php';
$this_section = SECTION_GLOBAL;

//api_protect_admin_script();
//api_protect_global_admin_script();

if (!api_get_multiple_access_url()) {
	header('Location: ../index.php');
	exit;
}

// Create the form
$form = new FormValidator('add_url');
//
$url_type = 3;

if( $form->validate()) {
	$check = Security::check_token('post');
	if($check) {
		$url_array = $form->getSubmitValues();
		$url = Security::remove_XSS($url_array['url']);
		$description = Security::remove_XSS($url_array['description']);
		$active = 0;
		$url_id = $url_array['id'];
		$url_to_go='index.php';
		if ($url_id!='') {
			//we can't change the status of the url with id=1
			if ($url_id==1) {
				$active=1;
                        }
			//checking url
			if (substr($url,strlen($url)-1, strlen($url))=='/') {
				UrlManager::udpate($url_id, $url, $description, $active);
			} else {
				UrlManager::udpate($url_id, $url.'/', $description, $active);
			}
/*
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
*/
			$url_to_go='index.php';
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
				$url_to_go='index.php';
			} else {
				$url_to_go='index.php';
				$message = get_lang('URLAlreadyAdded');
			}
			// URL Images
			$url .= (substr($url,strlen($url)-1, strlen($url))=='/') ? '' : '/';
			$url_id = UrlManager::get_url_id($url);
/*
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
*/
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


$form->addElement('text', 'url', get_lang('IPAddress'));
$form->addRule('url', get_lang('ThisFieldIsRequired'), 'required');
//$form->addRule('url', '', 'maxlength',254);

$form->addElement('text', 'name', get_lang('InstitutionName'));
//$form->addElement('text', 'ip', get_lang('IPAddress'));
$form->addElement('text', 'latlong', get_lang('LatitudeLongitude'));
$form->addElement('hidden', 'dwn_speed', get_lang('DownloadSpeed'));
$form->addElement('hidden', 'up_speed', get_lang('UploadSpeed'));
$form->addElement('hidden', 'delay', get_lang('ConnectionDelay'));
$form->addElement('text', 'admin_mail', get_lang('AdministratorEmail'));
$form->addElement('text', 'admin_name', get_lang('AdministratorFullname'));
$form->addElement('text', 'admin_phone', get_lang('AdministratorPhone'));
$form->addElement('textarea','description',get_lang('Note'));

//$form->addRule('checkbox', get_lang('ThisFieldIsRequired'), 'required');

//$defaults['url']='http://';
$defaults['url'] = $_SERVER['REMOTE_ADDR'];
$defaults['admin_mail'] = api_get_setting('emailAdministrator');
$defaults['admin_name'] = api_get_setting('administratorSurname').', '.api_get_setting('administratorName');
$defaults['admin_phone'] = api_get_setting('administratorTelephone');
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

$tool_name = get_lang('SpeedTest');
//$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
//$interbreadcrumb[] = array ("url" => 'access_urls.php', "name" => get_lang('MultipleAccessURLs'));
/**
 * View
 */
Display :: display_header($tool_name);

if (isset ($_GET['action'])) {
	switch ($_GET['action']) {
		case 'show_message' :
			Display :: display_normal_message(stripslashes($_GET['message']));
			break;
	}
}

// URL Images
/*
$form->addElement('file','url_image_1','URL Image 1 (PNG)');
$form->addElement('file','url_image_2','URL Image 2 (PNG)');
$form->addElement('file','url_image_3','URL Image 3 (PNG)');
*/
// Submit button
$form->addElement('style_submit_button', 'submit', $submit_name, 'class="add"');
echo '<div class="normal-message">'.get_lang('SpeedTestIntroSection').'</div>';
echo '<div class="form-column" style="float: left;">';
$form->display();
echo '</div>';
echo '<div class="anim-column" style="float: left;">';
echo '<img src="">';
echo '</div>';
