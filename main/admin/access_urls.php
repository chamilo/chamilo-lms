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
/**
==============================================================================
*	@package dokeos.admin
============================================================================== 
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/

// name of the language file that needs to be included 
$language_file = 'admin';
$cidReset = true;
require ('../inc/global.inc.php');
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
if (!$_configuration['multiple_access_urls'])
	header('Location: index.php');
	
$interbreadcrumb[] = array ("url" => 'index.php', "name" => get_lang('PlatformAdmin'));
$tool_name = get_lang('MultipleAccessURLs');
Display :: display_header($tool_name);

require_once (api_get_path(LIBRARY_PATH).'sortabletable.class.php');
require_once (api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php');
require_once (api_get_path(LIBRARY_PATH).'security.lib.php');

// Actions
if (isset ($_GET['action']))
{
	$check = Security::check_token('get');
	if ($check) 
	{	
		$url_id=Database::escape_string($_GET['url_id']);
	
		switch ($_GET['action'])
		{
				case 'show_message' :
					Display :: display_normal_message(stripslashes($_GET['message']));
					break;
				case 'delete_url' :
					$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);					
					$sql= "DELETE FROM $access_url_table WHERE id = '$url_id'";
					$result = api_sql_query($sql,  __FILE__, __LINE__);
					if ($result)
					{
						Display :: display_normal_message(get_lang('URLDeleted'));
					}
					else
					{
						Display :: display_error_message(get_lang('CannotDeleteURL'));
					}
					break;
				case 'lock' :
					$message=lock_unlock_user('lock',$url_id);
					Display :: display_normal_message($message);
					break;
				case 'unlock';
					$message=lock_unlock_user('unlock',$url_id);
					Display :: display_normal_message($message);
					break;	
			}
		}
		Security::clear_token();
}

echo '<div align="right">
		<a href="'.api_get_path(WEB_CODE_PATH).'admin/access_url_edit.php">'.get_lang('AddUrl').'</a>									
	  </div><br />';		  
		  
$table = new SortableTable('urls', 'get_number_of_urls', 'get_url_data',2);
$parameters['sec_token'] = Security::get_token();	
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false);

$table->set_header(1, get_lang('URL'));
$table->set_header(2, get_lang('Description'));
$table->set_header(3, get_lang('Active'));
$table->set_header(4, get_lang('Modify'));

$table->set_column_filter(3, 'active_filter');
$table->set_column_filter(4, 'modify_filter');
//$table->set_form_actions(array ('delete' => get_lang('DeleteFromPlatform')));
$table->display();

function get_number_of_urls()
{
	$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);	
	$sql = "SELECT count(id) as count_result FROM $access_url_table";
	$res = api_sql_query($sql, __FILE__, __LINE__);
	$url = Database::fetch_row($res);
	$result = $url['0'];	
	return $result;	
}

function get_url_data()
{
	$access_url_table= Database :: get_main_table(TABLE_MAIN_ACCESS_URL);	
	$sql = "SELECT id AS col0,  url AS col1, description AS col2, active AS col3 FROM $access_url_table";
	$res = api_sql_query($sql, __FILE__, __LINE__);
	$urls = array ();
	while ($url = Database::fetch_row($res))
	{
		$urls[] = $url;
	}
	return $urls;
}

function modify_filter($active, $url_params, $row)
{
	global $charset;	
	$url_id = $row['0'];
	if ($url_id != '1')
	{
		$result .= '<a href="access_url_edit.php?url_id='.$url_id.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>&nbsp;';
		$result .= '<a href="access_urls.php?action=delete_url&amp;url_id='.$url_id.'&amp;sec_token='.$_SESSION['sec_token'].'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
	}
	return $result;
}

function active_filter($active, $url_params, $row)
{	
	$active = $row['3'];
	if ($active=='1')
	{
		$action='lock';
		$image='right';
	}
	if ($active=='0')
	{
		$action='unlock';
		$image='wrong';
	}
	if ($row['0']=='1') // you cannot lock the default
	{ 
		$result = Display::return_icon($image.'.gif', get_lang(ucfirst($action)));
	}
	else
	{
		$result = '<a href="access_urls.php?action='.$action.'&amp;url_id='.$row['0'].'&amp;sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon($image.'.gif', get_lang(ucfirst($action))).'</a>';		
	}
	return $result;
}

function lock_unlock_user($status,$url_id)
{
	$url_table = Database :: get_main_table(TABLE_MAIN_ACCESS_URL);
	if ($status=='lock')
	{
		$status_db='0';
		$return_message=get_lang('URLInactivate');
	}
	if ($status=='unlock')
	{
		$status_db='1';
		$return_message=get_lang('URLActivate');
	}

	if(($status_db=='1' OR $status_db=='0') AND is_numeric($url_id))
	{
		$sql="UPDATE $url_table SET active='".Database::escape_string($status_db)."' WHERE id='".Database::escape_string($url_id)."'";
		$result = api_sql_query($sql, __FILE__, __LINE__);
	}

	if ($result)
	{
		return $return_message;
	}
}



/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>