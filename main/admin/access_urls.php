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
require_once (api_get_path(LIBRARY_PATH).'urlmanager.lib.php');

$my_user_url_list = api_get_access_url_from_user(api_get_user_id());
$current_access_url_id = api_get_current_access_url_id();
$url_list = UrlManager::get_url_data();

// Actions
if (isset ($_GET['action'])) {	
	if ($_GET['action'] == 'show_message')
		Display :: display_normal_message(Security::remove_XSS(stripslashes($_GET['message'])));	
		
	$check = Security::check_token('get');		
	if ($check) {
		$url_id=Database::escape_string($_GET['url_id']);
	
		switch ($_GET['action']) {		
			case 'delete_url' :		
				$result = UrlManager::delete($url_id);					
				if ($result) {
					Display :: display_normal_message(get_lang('URLDeleted'));
				} else {
					Display :: display_error_message(get_lang('CannotDeleteURL'));
				}
				break;
			case 'lock' :
				UrlManager::set_url_status('lock',$url_id);				
				Display :: display_normal_message(get_lang('URLInactive'));
				break;
			case 'unlock';
				UrlManager::set_url_status('unlock',$url_id);
				Display :: display_normal_message(get_lang('URLActive'));
				break;			
			case 'register';			
				// we are going to register the admin
				if(api_is_platform_admin()) {					
					if($current_access_url_id!=-1) {
						$url_str = '';
						foreach($url_list as $my_url) {	
							if (!in_array($my_url['id'],$my_user_url_list)){								
								UrlManager::add_user_to_url(api_get_user_id(),$my_url['id']);	
									$url_str.=$my_url['url'].' '; 
							}
						}						
						Display :: display_normal_message(get_lang('AdminUserRegisteredToThisURL').': '.$url_str.'<br />',false);
					}
				}						
				break;	
			}
			
		}
		Security::clear_token();
}

$parameters['sec_token'] = Security::get_token();

// checking if the admin is registered in all sites

$url_string='';
$my_user_url_list = api_get_access_url_from_user(api_get_user_id());
foreach($url_list as $my_url) {	
	if (!in_array($my_url['id'],$my_user_url_list)){
		$url_string.=$my_url['url'].' ';		
	}	
}
if(!empty($url_string)) {
	Display :: display_warning_message(get_lang('AdminShouldBeRegisterInSite').':<br />'.$url_string,false);
}

// checking the current installation
if ($current_access_url_id==-1) {	
	Display :: display_warning_message(get_lang('URLNotConfiguredPleaseChangedTo').': '.api_get_path(WEB_PATH));	
} elseif(api_is_platform_admin()) {			
	$quant= UrlManager::relation_url_user_exist(api_get_user_id(),$current_access_url_id);
	if ($quant==0) {
		Display :: display_warning_message('<a href="'.api_get_self().'?action=register&sec_token='.$parameters['sec_token'].'">'.get_lang('ClickToRegisterAdmin').'</a>',false);	
	} 
}

// action menu
echo '<div class="actions" style="height:22px;">';
echo '<div style="float:right;">
		<a href="'.api_get_path(WEB_CODE_PATH).'admin/access_url_edit.php">'.Display::return_icon('view_more_stats.gif',get_lang('AddUrl'),'').get_lang('AddUrl').'</a>&nbsp;&nbsp;
		<a href="'.api_get_path(WEB_CODE_PATH).'admin/access_url_edit_users_to_url.php">'.Display::return_icon('members.gif',get_lang('AddUserToURL'),'').get_lang('ManageUsers').'</a>															
	    <a href="'.api_get_path(WEB_CODE_PATH).'admin/access_url_edit_courses_to_url.php">'.Display::return_icon('courses.gif',get_lang('AddUsersToCourse'),'').get_lang('ManageCourses').'</a>
	    <a href="'.api_get_path(WEB_CODE_PATH).'admin/access_url_edit_sessions_to_url.php">'.Display::return_icon('courses.gif',get_lang('AddUsersToCourse'),'').get_lang('ManageSessions').'</a>
	  </div><br />';		  
echo '</div>';		  

$table = new SortableTable('urls', 'url_count_mask', 'get_url_data_mask',2); 
$table->set_additional_parameters($parameters);
$table->set_header(0, '', false);

$table->set_header(1, get_lang('URL'));
$table->set_header(2, get_lang('Description'));
$table->set_header(3, get_lang('Active'));
//$table->set_header(4, get_lang('Status'));
$table->set_header(4, get_lang('Modify'));

$table->set_column_filter(3, 'active_filter');
//$table->set_column_filter(4, 'status_filter');
$table->set_column_filter(4, 'modify_filter');

//$table->set_form_actions(array ('delete' => get_lang('DeleteFromPlatform')));
$table->display(); 
/*
function status_filter($active, $url_params, $row) {	
	$url_id =UrlManager::get_url_id($row[1]);	
	if ($row[0] == $url_id ) { 	
		$action='lock';
		$image='right';
	} else {
		$image='wrong';
	}
	// you cannot lock the default	
	$result = Display::return_icon($image.'.gif', get_lang(ucfirst($action)));		

	return $result;
}
*/
function modify_filter($active, $url_params, $row) {
	global $charset;	
	$url_id = $row['0'];
	$result .= '<a href="access_url_edit.php?url_id='.$url_id.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>&nbsp;';
	if ($url_id != '1') {	
		$result .= '<a href="access_urls.php?action=delete_url&amp;url_id='.$url_id.'&amp;sec_token='.$_SESSION['sec_token'].'" onclick="javascript:if(!confirm('."'".addslashes(htmlentities(get_lang("ConfirmYourChoice"),ENT_QUOTES,$charset))."'".')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>';
	}
	return $result;
}

function active_filter($active, $url_params, $row) {	
	$active = $row['3'];
	if ($active=='1') {
		$action='lock';
		$image='right';
	}
	if ($active=='0') {
		$action='unlock';
		$image='wrong';
	}
	// you cannot lock the default
	if ($row['0']=='1') { 
		$result = Display::return_icon($image.'.gif', get_lang(ucfirst($action)));
	} else {
		$result = '<a href="access_urls.php?action='.$action.'&amp;url_id='.$row['0'].'&amp;sec_token='.$_SESSION['sec_token'].'">'.Display::return_icon($image.'.gif', get_lang(ucfirst($action))).'</a>';		
	}
	return $result;
}

// this 2 "mask" function are here just because the SortableTable
function get_url_data_mask($id, $url_params=null, $row=null) {
	return UrlManager::get_url_data();
}
function url_count_mask() {
	return UrlManager::url_count();				
}

/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?>