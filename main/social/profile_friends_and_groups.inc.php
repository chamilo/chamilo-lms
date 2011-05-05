<?php
/* For licensing terms, see /license.txt */
$language_file = array('registration','messages','userInfo','admin');
$cidReset=true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'message.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$views = array('friends','mygroups');
$user_id = intval($_GET['user_id']);

if (isset($_GET['view']) && in_array($_GET['view'], $views)) {
	// show all friends by user_id
	if ($_GET['view']=='friends') {
		echo '<div style="margin-top:20px;">';
			$list_path_friends= $list_path_normal_friends = $list_path_parents = array();
			//SOCIALGOODFRIEND , USER_RELATION_TYPE_FRIEND, SOCIALPARENT
			$friends = SocialManager::get_friends($user_id, USER_RELATION_TYPE_FRIEND);
			$number_friends	= count($friends);
			$friend_html	= '';
			$friend_html	.= '<div><h3>'.get_lang('SocialFriend').'</h3></div>';
			$friend_html	.= '<div id="friend-container" class="social-friend-container">';
			$friend_html	.= '<div id="friend-header" >';
						
			if ($number_friends == 1) {
				$friend_html.= '<div style="float:left;width:80%">'.$number_friends.' '.get_lang('Friend').'</div>';
			} else {
				$friend_html.= '<div style="float:left;width:80%">'.$number_friends.' '.get_lang('Friends').'</div>';
			}
																
				$friend_html.= '</div>'; // close div friend-header									
			
			for ($k=0;$k<$number_friends;$k++) {
				if (isset($friends[$k])) {
					$friend = $friends[$k];				
					$name_user	= api_get_person_name($friend['firstName'], $friend['lastName']);
					$friend_html.='<div id=div_'.$friend['friend_user_id'].' class="image_friend_network" ><span><center>';
					// the height = 92 must be the sqme in the image_friend_network span style in default.css
					$friends_profile = SocialManager::get_picture_user($friend['friend_user_id'], $friend['image'], 92, USER_IMAGE_SIZE_MEDIUM , 'width="85" height="90" ');
					
					$friend_html.='<a href="profile.php?u='.$friend['friend_user_id'].'&amp;'.$link_shared.'">';
					$friend_html.='<img src="'.$friends_profile['file'].'" '.$friends_profile['style'].' id="imgfriend_'.$friend['friend_user_id'].'" title="'.$name_user.'" />';
					$friend_html.= '</center></span>';
					$friend_html.= '<center class="friend">'.$name_user.'</a></center>';
					$friend_html.= '</div>';
				}
			}
			echo $friend_html;
		echo '</div>';		
	} else {
		// show all groups by user_id	
		// MY GROUPS		    
	    $results = GroupPortalManager::get_groups_by_user($user_id, 0);	
		$grid_my_groups = array();
		if (is_array($results) && count($results) > 0) {
			$i = 1;
			foreach ($results as $result) {				
				$id = $result['id'];
				$url_open  = '<a href="groups.php?id='.$id.'">';
				$url_close = '</a>';
				$icon = '';					
				$name = cut($result['name'],20,true);				
				if ($result['relation_type'] == GROUP_USER_PERMISSION_ADMIN) {		 	
					$icon = Display::return_icon('social_group_admin.png', get_lang('Admin'), array('style'=>'vertical-align:middle;width:16px;height:16px;'));
				} elseif ($result['relation_type'] == GROUP_USER_PERMISSION_MODERATOR) {			
					$icon = Display::return_icon('social_group_moderator.png', get_lang('Moderator'), array('style'=>'vertical-align:middle;width:16px;height:16px;'));
				}
				$count_users_group = count(GroupPortalManager::get_all_users_by_group($id));
				if ($count_users_group == 1 ) {
					$count_users_group = $count_users_group.' '.get_lang('Member');	
				} else {
					$count_users_group = $count_users_group.' '.get_lang('Members');
				}										
				$picture = GroupPortalManager::get_picture_group($result['id'], $result['picture_uri'],80);
				$item_name = '<div class="box_shared_profile_group_title">'.$url_open.api_xml_http_response_encode($name). $icon.$url_close.'</div>';
				$item_description = '';
				if (!empty($result['description'])) { 				
					$item_description = '<div class="box_shared_profile_group_description"><span class="social-groups-text2">'.api_xml_http_response_encode(get_lang('Description')).'</span><p class="social-groups-text4">'.cut(api_xml_http_response_encode($result['description']),120,true).'</p></div>';
				} 
				  															
				$result['picture_uri'] = '<div class="box_shared_profile_group_image"><img class="social-groups-image" src="'.$picture['file'].'" hspace="4" height="50" border="2" align="left" width="50" /></div>';
				$item_actions = '';
				if (api_get_user_id() == $user_id) {
					$item_actions = '<div class="box_shared_profile_group_actions"><a href="groups.php?id='.$id.'">'.get_lang('SeeMore').$url_close.'</div>';	
				}							
				$grid_my_groups[]= array($item_name,$url_open.$result['picture_uri'].$url_close, $item_description.$item_actions);
				$i++;					
			}
		}			    
	    if (count($grid_my_groups) > 0) {	
			echo '<div style="margin-top:20px">';
				echo '<div><h3>'.get_lang('MyGroups').'</h3></div>';
				$count_groups = 0;
				if (count($results) == 1 ) {
					$count_groups = count($results).' '.get_lang('Group');	
				} else {
					$count_groups = count($results).' '.get_lang('Groups');
				}
				echo '<div>'.$count_groups.'</div>';
									    			
    			Display::display_sortable_grid('shared_profile_mygroups', array(), $grid_my_groups, array('hide_navigation'=>true, 'per_page' => 2), $query_vars, false, array(true, true, true,false));	    			
			echo '</div>';	
		}		
	}	
}