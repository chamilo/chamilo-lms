<?php //$id: $
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package dokeos.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
 
$language_file = array('registration','messages','userInfo','admin','forum','blog');
$cidReset = true;
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'array.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$user_id = api_get_user_id();
$show_full_profile = true;
//social tab
$this_section = SECTION_SOCIAL;

api_block_anonymous_users();
$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery


Display :: display_header(null);

echo '<div id="social_wrapper">';

	//this include the social menu div
	SocialManager::show_social_menu(array('messages'));	
	
	echo '<div id="social_main">';
	
		echo '<div id="social_main_sub">';
		
			echo '<div id="social_top">';
			echo get_lang('User Online').'120';
			echo '</div>';
		
			echo '<div id="social_left">';
			echo 'myinfo';
			
				echo '<div id="social_center">';
				echo '</div>';	
				echo '<div id="social_center">';
				echo '</div>';
				echo '<div id="social_center">';
				echo '</div>';
				
			echo '</div>';
			
			echo '<div id="social_right">';
					
				$results = GroupPortalManager::get_groups_by_age(1);
				
				$groups = array();
				foreach ($results as $result) {
					
					$id = $result['id'];
					$url_open  = '<a href="groups.php?id='.$id.'">';
					$url_close = '</a>';		
					$groups[]= array($url_open.$result['picture_uri'].$url_close, $url_open.$result['name'].$url_close, cut($result['description'],180,true));
				}
				if (count($groups) > 0) {		
					echo '<h3>'.get_lang('Popular').'</h3>';	
					Display::display_sortable_grid('home_group', array(), $groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));		
				}
				
				
				$results = GroupPortalManager::get_groups_by_popularity(1);
				$groups = array();
				foreach ($results as $result) {
					$id = $result['id'];
					$url_open  = '<a href="groups.php?id='.$id.'">';
					$url_close = '</a>';		
					
					if ($result['count'] == 1 ) {
						$result['count'] = $result['count'].' '.get_lang('Member');	
					} else {
						$result['count'] = $result['count'].' '.get_lang('Members');
					}
					
					$groups[]= array($url_open.$result['picture_uri'].$url_close, $url_open.$result['name'].$url_close,$result['count'],cut($result['description'],120,true));
				}
				if (count($groups) > 0) {
					echo '<h3>'.get_lang('Popular').'</h3>';
					Display::display_sortable_grid('home_group', array(), $groups, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,true,true));
				}
			
			echo '</div>';
			
		echo '</div>';
		
	echo '</div>';
	
echo '</div>';

Display :: display_footer();