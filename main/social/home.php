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
$user_info = api_get_user_info(api_get_user_id());
$user_online_list = WhoIsOnline(api_get_setting('time_limit_whosonline'));
$user_online_count = count($user_online_list); 
	

echo '<div id="social_wrapper">';

	//this include the social menu div
	SocialManager::show_social_menu();	
	
	echo '<div id="social_main">';
	
		echo '<div id="social_main_sub">';
		
			echo '<div id="social_top">';
			echo get_lang('FriendsOnline').' '.$user_online_count;
			echo '</div>';
		
			echo '<div id="social_left">';
				
				$user_image_array = UserManager::get_picture_user(api_get_user_id(), $user_info['picture_uri'], 200, USER_IMAGE_SIZE_MEDIUM);				
				
				echo '<div class="user_info">';
				
				if ($user_image_array['file'] != 'unknown.jpg') {
	    	  		echo '<img src='.$user_image_array['dir'].$user_image_array['file'].' /> <br /><br />';
				} else {
					echo '<img src='.$user_image_array['dir'].$user_image_array['file'].' /><br /><br />';
				}
				
					echo get_lang('Name').': '.api_get_person_name($user_info['firstName'], $user_info['lastName']);
				echo '</div>';
				
				
				
				
				echo '<div class="user_online">';
				SocialManager::display_user_list($user_online_list);
				echo '</div>';	
			
				echo '<div id="social_center">';
				echo '</div>';	
				echo '<div id="social_center">';
				echo '</div>';
				echo '<div id="social_center">';
				echo '</div>';
				
			echo '</div>';
			
			echo '<div id="social_right">';
				
				echo UserManager::get_search_form($query);
				
				$results = GroupPortalManager::get_groups_by_age(1);
				
				
				$groups = array();
				foreach ($results as $result) {
					
					$id = $result['id'];
					$url_open  = '<a href="groups.php?id='.$id.'">';
					$url_close = '</a>';
					$result['picture_uri'] = '<img class="imageGroups" src="'.$result['picture_uri'].'" hspace="4" height="50" border="2" align="left" width="50" />';
					$groups[]= array($url_open.$result['picture_uri'].$url_close, $url_open.$result['name'].$url_close, cut($result['description'],180,true));
				}
				if (count($groups) > 0) {		
					echo '<h3>'.get_lang('Newest').'</h3>';	
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
					$result['picture_uri'] = '<img class="imageGroups" src="'.$result['picture_uri'].'" hspace="4" height="50" border="2" align="left" width="50" />';
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