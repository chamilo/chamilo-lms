<?php
/* For licensing terms, see /dokeos_license.txt */

$language_file = array('admin');
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';

$this_section = SECTION_SOCIAL;

$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));
Display :: display_header($tool_name, 'Groups');

//show the action menu
SocialManager::show_social_menu();
echo '<div class="actions-title">';
echo get_lang('Groups');
echo '</div>';

$group_id	= intval($_GET['id']);

if ($group_id != 0 ) {
	$group_info = GroupPortalManager::get_group_data($group_id); 
	$picture	= GroupPortalManager::get_picture_group($group_id, $group_info['picture_uri'],160,'medium_');
	$tags		= GroupPortalManager::get_group_tags($group_id,true);
	$users		= GroupPortalManager::get_users_by_group($group_id,true);
	
	//@todo this must be move to default.css for dev use only
	echo '<style> 		
			#group_members { width:250px; height:300px; overflow-x:none; overflow-y: auto;}
			.group_member_item { width:80px; float:left;}
			
	</style>';
	
	
	//Group's title
	echo '<h1>'.$group_info['name'].'</h1>';
	
	//image
	echo '<div id="group_image">';
		echo $img = '<img src="'.$picture['file'].'" />';
	echo '</div>';
	
	//description
	echo '<div id="group_description">';
		echo $group_info['description'];
	echo '</div>';
	
	//Privacy
	echo '<div id="group_privacy">';
		echo get_lang('Privacy').' : ';
		if ($group_info['visibility']== GROUP_PERMISSION_OPEN) {
			echo get_lang('ThisIsAnOpenGroup');
		} elseif ($group_info['visibility']== GROUP_PERMISSION_CLOSED) {
			echo get_lang('ThisIsACloseGroup');
		}
	echo '</div>';
	
	//group tags
	if (!empty($tags)) {
		echo '<div id="group_tags">';
			echo get_lang('Tags').' : '.$tags;
		echo '</div>';
	}
	
	echo get_lang('Members').' : ';
	echo '<div id="group_members">';		
		foreach($users as $user) {			
			echo '<div class="group_member_item">'.$user['picture_uri'].$user['firstname'].$user['lastname'].'</div>';
		}
	echo '</div>';
	
		
	echo '<div id="group_permissions">';


	
	if (is_array($users[api_get_user_id()]) && count($users[api_get_user_id()]) > 0) {
		//im a member

		if ($users[api_get_user_id()]['relation_type']!='') {
			
			$my_group_role = $users[api_get_user_id()]['relation_type'];
			// just a reader
			if ($my_group_role  == GROUP_USER_PERMISSION_READER) {
				echo 'Leave group/';
				echo 'Invite others/';				
			//the main admin
			} elseif ($my_group_role  == GROUP_USER_PERMISSION_ADMIN) {
				echo 'Im the admin/';
				echo 'Edit group/';
				echo 'Invite others';					
			}
		} else {
			//im not a member
			echo 'Join group';
		}
	} else {
		//im not a member
		echo 'Join group';		 		
	}	
	echo '</div>';

	
} else {
	echo '<h1>'.get_lang('Newest').'</h1>';
	echo '<h1>'.get_lang('Popular').'</h1>';
	echo '<h1>'.get_lang('MyGroups').'</h1>';
	
	$results = GroupPortalManager::get_groups_by_user(api_get_user_id(), 0, true);
	$groups = array();
	foreach ($results as $result) {
		$id = $result['id'];
		$url_open  = '<a href="groups.php?id='.$id.'">';
		$url_close = '</a>';
		
		$groups[]= array($url_open.$result['picture_uri'].$url_close, $url_open.$result['name'].$url_close);
	}
	
	Display::display_sortable_grid('search_users', array(), $groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
	
}



	
Display :: display_footer();
?>