<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package dokeos.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
 
// name of the language file that needs to be included
$language_file = array('registration','admin','userInfo');
$cidReset = true;
require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

api_block_anonymous_users();

$this_section = SECTION_SOCIAL;
$tool_name = get_lang('Search');
$interbreadcrumb[]= array ('url' =>'profile.php','name' => get_lang('Social'));

Display :: display_header($tool_name);

echo '<div class="actions-title">';
echo get_lang('Search');
echo '</div>';

echo '<div id="social_wrapper">';

	//this include the social menu div
	SocialManager::show_social_menu(array('messages'));	
	
	echo '<div id="social_main">';
	

$query = $_GET['q'];
echo UserManager::get_search_form($query);
	
//I'm searching something
if ($query != '') {
	if (isset($query) && $query!='') {		
		//get users from tags
		$users = UserManager::get_all_user_tags($query, 0, 0, 5);	
		$groups = GroupPortalManager::get_all_group_tags($query);
		
		if (empty($users) && empty($groups)) {
			echo get_lang('SorryNoResults');	
		}
				
		$results = array();
		if (is_array($users) && count($users)> 0) {
			echo '<h2>'.get_lang('Users').'</h2>';			
			foreach($users as $user) {
				$picture = UserManager::get_picture_user($user['user_id'], $user['picture_uri'],80);
				$url_open = '<a href="'.api_get_path(WEB_PATH).'main/social/profile.php?u='.$user['user_id'].'">';
				$url_close ='</a>';
				$img = $url_open.'<img src="'.$picture['file'].'" />'.$url_close;
				$user['firstname'] = $url_open.$user['firstname'].$url_close;
				$user['lastname'] = $url_open.$user['lastname'].$url_close;
				
				$results[] = array($img, $user['firstname'],$user['lastname'],$user['tag']);			
			}		
		}		
		Display::display_sortable_grid('search_user', array(), $results, array('hide_navigation'=>true, 'per_page' => 5), $query_vars, false ,true);
		
		//get users from tags
		
		
				
		$results = array();
		if (is_array($groups) && count($groups)>0) {
			echo '<h2>'.get_lang('Groups').'</h2>';
			foreach($groups as $group) {
				$picture = GroupPortalManager::get_picture_group($group['id'], $group['picture_uri'],80);
				$img = '<img src="'.$picture['file'].'" />';
				$tags = GroupPortalManager::get_group_tags($group['id']);
				$group['name'] = '<a href="'.api_get_path(WEB_PATH).'main/social/groups.php?id='.$group['id'].'">'.$group['name'].'</a>';
				$img = '<a href="'.api_get_path(WEB_PATH).'main/social/groups.php?id='.$group['id'].'">'.$img.'</a>';
				$results[] = array($img, $group['name'],$group['description'],$tags);			
			}		
		}		
		Display::display_sortable_grid('search_group', array(), $results, array('hide_navigation'=>true, 'per_page' => 5), $query_vars,  false, array(true,true,true,true,true));
					    
	}		
} else {
	//we should show something
}
	echo '</div>';	
echo '</div>';

Display :: display_footer();
?>