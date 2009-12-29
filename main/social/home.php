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

//fast upload image
if (api_get_setting('profile', 'picture') == 'true') {
	require_once api_get_path(LIBRARY_PATH).'formvalidator/FormValidator.class.php';
	$form = new FormValidator('profile', 'post', 'home.php', null, array());
	
	//	PICTURE	
	$form->addElement('file', 'picture', get_lang('AddImage'));
	$form->add_progress_bar();
	if (!empty($user_data['picture_uri'])) {
		$form->addElement('checkbox', 'remove_picture', null, get_lang('DelImage'));
	}
	$allowed_picture_types = array ('jpg', 'jpeg', 'png', 'gif');
	$form->addRule('picture', get_lang('OnlyImagesAllowed').' ('.implode(',', $allowed_picture_types).')', 'filetype', $allowed_picture_types);
	$form->addElement('style_submit_button', 'apply_change', get_lang('SaveSettings'), 'class="save"');
						
	if ($form->validate()) {
		$user_data = $form->getSubmitValues();
		// upload picture if a new one is provided
		if ($_FILES['picture']['size']) {			
			if ($new_picture = UserManager::update_user_picture(api_get_user_id(), $_FILES['picture']['name'], $_FILES['picture']['tmp_name'])) {
				$table_user = Database :: get_main_table(TABLE_MAIN_USER);
				$sql = "UPDATE $table_user SET picture_uri = '$new_picture' WHERE user_id =  ".api_get_user_id();
				$result = Database::query($sql, __FILE__, __LINE__);
			}
		}
	}
}

Display :: display_header(null);
$user_info = UserManager :: get_user_info_by_id(api_get_user_id());
//$user_info = api_get_user_info(api_get_user_id());
$user_online_list = WhoIsOnline(api_get_setting('time_limit_whosonline'));
$user_online_count = count($user_online_list); 

echo '<div class="actions-title-groups">';
echo '<table width="100%"><tr><td width="150px" bgcolor="#32578b"><center><span class="menuTex1">'.strtoupper(get_lang('Menu')).'</span></center></td>
		<td width="15px">&nbsp;</td><td bgcolor="#32578b">'.Display::return_icon('whoisonline.png','',array('hspace'=>'6')).'<a href="#" ><span class="menuTex1">'.get_lang('FriendsOnline').' '.$user_online_count.'</span></a></td>
		</tr></table>';
/*
echo '<div class="menuTitle" align="center"><span class="menuTex1">'.get_lang('Menu').'</span></div>';
echo '<div class="TitleRigth">'.Display::return_icon('whoisonline.png','',array('hspace'=>'6')).'<a href="#" ><span class="menuTex1">'.$who_is_on_line.'</span></a></div>';
*/
echo '</div>';
	

echo '<div id="socialContent">';

	echo '<div id="socialContentLeft">';	
	//this include the social menu div
	SocialManager::show_social_menu();	
	echo '</div>';
	echo '<div class="socialContentRight">';
		echo '<div id="boxmyGroups">';
			echo '<div id="boxmyGroupsLeft">';
						
			$user_image_array = UserManager::get_picture_user(api_get_user_id(), $user_info['picture_uri'], 400, USER_IMAGE_SIZE_BIG);				
			// information current user			
			echo	'<div class="boxMygroupsContent">
                    	<div>'.Display::return_icon('boxmygroups.jpg').'</div>
                        <div class="myGroupsContent">
                        	<div><img hspace="6" height="90" align="left" width="80" src="'.$user_image_array['dir'].$user_image_array['file'].'"/><p class="groupTex3"><strong>'.get_lang('Information').'</strong></p></div>                        	
                            <div><p><strong>'.get_lang('Username').'</strong><br /><span class="groupText4">'.$user_info['username'].'</span></p></div>
                            <div><p><strong>'.get_lang('Name').'</strong><br /><span class="groupText4">'.api_get_person_name($user_info['firstname'], $user_info['lastname']).'</span></p></div>
                            <div><p><strong>'.get_lang('Email').'</strong><br /><span class="groupText4">'.($user_info['email']?$user_info['email']:'').'</span></p></div>
                            <div><p><strong>'.get_lang('Phone').'</strong><br /><span class="groupText4">'.($user_info['phone']?$user_info['phone']:'').'</span></p></div>
                            <div class="box_description_group_actions" ><a href="profile.php">'.get_lang('SeeMore').$url_close.'</a></div>	            
                        </div>                        
					</div>';
 
			echo '<div class="boxMygroupsContent">
                    	<div>'.Display::return_icon('boxmygroups.jpg').'</div>
                        <div class="myGroupsContent">
                          <div><p class="groupTex3"><strong>'.get_lang('UsersOnline').'</strong> </p></div>
                          <div>';             
            echo '<center>'.SocialManager::display_user_list($user_online_list).'</center>';                                
            echo '</div>
                      </div>
                   </div>';
			
			echo '</div>';

			echo '<div id="boxmyGroupsRigth">';
			echo '<br /><br />';
			echo UserManager::get_search_form($query);
			echo '<br />';			
			$results = GroupPortalManager::get_groups_by_age(1,false);							
			$groups_newest = array();
			foreach ($results as $result) {
				$id = $result['id'];
				$url_open  = '<a href="groups.php?id='.$id.'">';
				$url_close = '</a>';
				$count_users_group = count(GroupPortalManager::get_all_users_by_group($id));
				if ($count_users_group == 1 ) {
					$count_users_group = $count_users_group.' '.get_lang('Member');	
				} else {
					$count_users_group = $count_users_group.' '.get_lang('Members');
				}
				$result['name'] = $url_open.ucwords(cut($result['name'],40,true)).'('.$count_users_group.') '.$url_close.'<span>'.get_lang('DescriptionGroup').'</span>';
				$picture = GroupPortalManager::get_picture_group($id, $result['picture_uri'],80);							
				$result['picture_uri'] = '<img class="imageGroups" src="'.$picture['file'].'" hspace="10" height="44" border="2" align="left" width="44" />';
				$actions = '<div class="box_description_group_actions" ><a href="groups.php?view=newest">'.get_lang('SeeMore').$url_close.'</div>';								
				$groups_newest[]= array($url_open.$result['picture_uri'].$url_close, $result['name'], cut($result['description'],120,true).$actions);
			}

			$results = GroupPortalManager::get_groups_by_popularity(1,false);
			$groups_pop = array();
			foreach ($results as $result) {
				$id = $result['id'];
				$url_open  = '<a href="groups.php?id='.$id.'">';
				$url_close = '</a>';		
				
				if ($result['count'] == 1 ) {
					$result['count'] = $result['count'].' '.get_lang('Member');	
				} else {
					$result['count'] = $result['count'].' '.get_lang('Members');
				}
				$result['name'] = $url_open.ucwords(cut($result['name'],40,true)).'('.$result['count'].') '.$url_close.'<span>'.get_lang('DescriptionGroup').'</span>';
				$picture = GroupPortalManager::get_picture_group($id, $result['picture_uri'],80);							
				$result['picture_uri'] = '<img class="imageGroups" src="'.$picture['file'].'" hspace="10" height="44" border="2" align="left" width="44" />';
				$actions = '<div class="box_description_group_actions" ><a href="groups.php?view=pop">'.get_lang('SeeMore').$url_close.'</div>';								
				$groups_pop[]= array($url_open.$result['picture_uri'].$url_close, $result['name'], cut($result['description'],120,true).$actions);
			}
			
			if (count($groups_newest) > 0) {		
				echo '<div class="home_group_title">'.strtoupper(get_lang('Newest')).'</div>';	
				Display::display_sortable_grid('home_group', array(), $groups_newest, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
				echo '<br /><br /><br />';		
			}
			
			if (count($groups_pop) > 0) {
				echo '<div class="home_group_title">'.strtoupper(get_lang('Popular')).'</div>';
				Display::display_sortable_grid('home_group', array(), $groups_pop, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,true,true));
			}
	                               	 
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</div>';	
	
Display :: display_footer();