<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos SPRL
	Copyright (c) 2008 Julio Montoya

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
// name of the language file that needs to be included 
$language_file = 'admin';
$cidReset = true;
require ('../inc/global.inc.php');
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'security.lib.php');
$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
	
// Create the form
$form = new FormValidator('add_url');

$form->addElement('text','url',get_lang('URL'),array('size'=>'30'));
$form->addElement('static', null, null, get_lang('Example'));

$form->addRule('url', get_lang('ThisFieldIsRequired'), 'required');
$form->addRule('url', '', 'maxlength',254);

$form->addElement('textarea','description',get_lang('Description'));
$form->addElement('checkbox','active',get_lang('Active'));
$form->addRule('checkbox', get_lang('ThisFieldIsRequired'), 'required');

$defaults['url']='http://';
$form->setDefaults($defaults);

if( $form->validate())
{		
	$check = Security::check_token('post');	
	if($check)
	{
		$url_array = $form->getSubmitValues();
		$url = Security::remove_XSS($url_array['url']);
		$description = Security::remove_XSS($url_array['description']);
		$active = intval($url_array['active']);
		$tms = time();
		$url_id = $url_array['id'];
		$url_to_go='access_urls.php';
			
		if ($url_id!='')
		{			
			$sql = "UPDATE $access_url_table
	                SET url = '".Database::escape_string($url)."',
	                description = '".Database::escape_string($description)."',
	                active = '".Database::escape_string($active)."',
	                created_by = '".Database::escape_string(api_get_user_id())."',
	                tms = FROM_UNIXTIME(".$tms.") WHERE id = '$url_id'";
			api_sql_query($sql, __FILE__, __LINE__);
			$url_to_go='access_urls.php';
			$message=get_lang('URLEdited');
		}
		else
		{			
			$sql = "SELECT id FROM $access_url_table WHERE url = '$url' ";	
			$res = api_sql_query($sql,__FILE__,__LINE__); 
			$result = Database::fetch_array($res);
						
			if (empty($result))
			{
				//checking url
				if (substr($url,strlen($url)-1, strlen($url))=='/')
				{				
					//create		
					$sql = "INSERT INTO $access_url_table
				                SET url = '".Database::escape_string($url)."',
				                description = '".Database::escape_string($description)."',
				                active = '".Database::escape_string($active)."',
				                created_by = '".Database::escape_string(api_get_user_id())."',
				                tms = FROM_UNIXTIME(".$tms.")";
					$result = api_sql_query($sql, __FILE__, __LINE__);				
					$message = get_lang('URLAdded');
				}
				else
				{
					$message = get_lang('URLMustHaveFinalSlash');
				}
				$url_to_go='access_url_edit.php';		
			}
			else
			{
				$url_to_go='access_url_edit.php';
				$message = get_lang('URLAlreadyAdded');			
			}
			Security::clear_token();
			$tok = Security::get_token();
			header('Location: '.$url_to_go.'?action=show_message&message='.urlencode($message).'&sec_token='.$tok);
			exit();				
		}
		
	}
}
else
{
	if(isset($_POST['submit']))
	{
		Security::clear_token();
	}
	$token = Security::get_token();
	$form->addElement('hidden','sec_token');
	$form->setConstants(array('sec_token' => $token));
}

$submit_name = get_lang('Add');
if (isset($_GET['url_id']))
{
	$url_id = Database::escape_string($_GET['url_id']);
	$sql = "SELECT id, url, description, active FROM $access_url_table WHERE id = '".$url_id."'";
	$res = api_sql_query($sql,__FILE__,__LINE__);
	if(mysql_num_rows($res) != 1)
	{
		header('Location: access_urls.php');
		exit;
	}
	$url_data = Database::fetch_array($res,'ASSOC');
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