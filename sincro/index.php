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
error_log(__LINE__);
if( $form->validate()) {
error_log(__LINE__);
	//$check = Security::check_token('post');
	//if($check) {
error_log(__LINE__);
		$url_array = $form->getSubmitValues();
		$url = Security::remove_XSS($url_array['url']);
		$description = Security::remove_XSS($url_array['description']);
		$active = 0;
		$url_id = $url_array['id'];
		$url_to_go='index.php';
		if ($url_id!='') {
error_log(__LINE__);
			//we can't change the status of the url with id=1
			if ($url_id==1) {
				$active=1;
                        }
			//checking url
			if (substr($url,-1)=='/') {
                            $url.='/';
                        }
			UrlManager::udpate($url_id, $url, $description, $active, $url_array['url_type'], $url_array);
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
error_log(__LINE__);
			$num = UrlManager::url_exist($url);
			if ($num == 0) {
				//checking url
				if (substr($url,-1)=='/') {
                                    $url .= '/';
                                }
error_log(__LINE__);
				UrlManager::add($url, $description, $active, $url_type, $url_array);
				$message = get_lang('URLAdded');
				$url_to_go='index.php';
			} else {
				$url_to_go='index.php';
				$message = get_lang('URLAlreadyAdded');
			}
			// URL Images
			$url .= (substr($url,-1)=='/') ? '' : '/';
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
error_log(__LINE__);
		Security::clear_token();
		$tok = Security::get_token();
		header('Location: '.$url_to_go.'?action=show_message&message='.urlencode($message));//.'&sec_token='.$tok);
		exit();
//	}
} else {
error_log(__LINE__);
/*
	if(isset($_POST['submit'])) {
		Security::clear_token();
	}
	$token = Security::get_token();
	$form->addElement('hidden','sec_token');
	$form->setConstants(array('sec_token' => $token));
*/
}

//$form->addRule('url', get_lang('ThisFieldIsRequired'), 'required');
//$form->addRule('url', '', 'maxlength',254);
$form->addElement('text', 'name', get_lang('InstitutionName'));
$form->addElement('text', 'url', get_lang('IPAddress'));
//$form->addElement('text', 'ip', get_lang('IPAddress'));
$form->addElement('hidden', 'latitude', get_lang('Latitude'), array('id' => 'latitude'));
$form->addElement('hidden', 'longitude', get_lang('Longitude'), array('id' => 'longitude'));
$form->addElement('hidden', 'dwn_speed', get_lang('DownloadSpeed'), array('id' => 'dwn_speed_input'));
$form->addElement('hidden', 'up_speed', get_lang('UploadSpeed'), array('id' => 'up_speed_input'));
$form->addElement('hidden', 'delay', get_lang('ConnectionDelay'), array('id' => 'delay_input'));
$form->addElement('text', 'admin_mail', get_lang('AdministratorEmail'));
$form->addElement('text', 'admin_name', get_lang('AdministratorFullname'));
$form->addElement('text', 'admin_phone', get_lang('AdministratorPhone'));
$form->addElement('textarea','description',get_lang('Note'));

//$form->addRule('checkbox', get_lang('ThisFieldIsRequired'), 'required');

//$defaults['url']='http://';
$defaults['url'] = $_SERVER['REMOTE_ADDR'];
//$defaults['admin_mail'] = api_get_setting('emailAdministrator');
//$defaults['admin_name'] = api_get_setting('administratorSurname').', '.api_get_setting('administratorName');
//$defaults['admin_phone'] = api_get_setting('administratorTelephone');
$form->setDefaults($defaults);

$submit_name = get_lang('SentSincroClientData');
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
//<script type="text/javascript" src="jquery-1.3.1.min.js"></script>
//<script type="text/javascript" src="ui.core.min.js"></script>
$htmlHeadXtra[] = <<<EOB
<link rel="stylesheet" href="css/style.css" type="text/css" />
<script type="text/javascript" src="jquery.query-2.0.1.js"></script>
<script type="text/javascript" src="ui.progressbar.min.js"></script>
<script type="text/javascript" src="config.js"></script>
<script type="text/javascript" src="motor.js"></script>
EOB;

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
$form->addElement('style_submit_button', 'submit', $submit_name, array('class'=>"add hidden", 'id' => 'submit_url_button'));
echo '<div class="normal-message">'.get_lang('SpeedTestIntroSection').'</div>';
echo '<div id="pagesincro">';
echo '<div class="form-column">';
$form->display();
echo '</div>';
echo '<div class="anim-column" style="float: right;">';
include 'body.php';
echo '</div>';
echo '</div>';
Display :: display_footer();
