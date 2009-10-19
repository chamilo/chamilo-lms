<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	@package dokeos.admin
==============================================================================
*/

$language_file = 'admin';
$cidReset = true;
require ('../inc/global.inc.php');
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
if (!$_configuration['multiple_access_urls'])
	header('Location: index.php');

require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'security.lib.php');
require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');

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
		}
		Security::clear_token();
		$tok = Security::get_token();
		header('Location: '.$url_to_go.'?action=show_message&message='.urlencode($message).'&sec_token='.$tok);
		exit();
	}
}
else
{
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
$form->addElement('checkbox','active',get_lang('Active'));
$form->addRule('checkbox', get_lang('ThisFieldIsRequired'), 'required');

$defaults['url']='http://';
$form->setDefaults($defaults);

$submit_name = get_lang('Add');
if (isset($_GET['url_id']))
{
	$url_id = Database::escape_string($_GET['url_id']);
	$num_url_id = UrlManager::url_id_exist($url_id);
	if($num_url_id != 1)
	{
		header('Location: access_urls.php');
		exit();
	}
	$url_data = UrlManager::get_url_data_from_id($url_id);
	$form->addElement('hidden','id',$url_data['id']);
	$form->setDefaults($url_data);
	$submit_name = get_lang('Edit');
}

if (!$_configuration['multiple_access_urls'])
	header('Location: index.php');

$tool_name = get_lang('AddUrl');
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$interbreadcrumb[] = array ("url" => 'access_urls.php', "name" => get_lang('MultipleAccessURLs'));
Display :: display_header($tool_name);

if (isset ($_GET['action']))
{
	switch ($_GET['action'])
	{
		case 'show_message' :
			Display :: display_normal_message(stripslashes($_GET['message']));
			break;
	}
}

// Submit button
$form->addElement('submit', 'submit', $submit_name);
$form->display();
?>