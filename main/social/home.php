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
		var_dump($_FILES);
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

echo '<div id="social_wrapper">';

	//this include the social menu div
	SocialManager::show_social_menu(array('messages'));	
	
	echo '<div id="social_main">';
	
		echo '<div id="social_main_sub">';
		
			echo '<div id="social_top">';
			echo get_lang('User Online').'120';
			echo '</div>';
		
			echo '<div id="social_left">';

				//@todo fix this aswell as in main/auth/profile.php
				//User picture size is calculated from SYSTEM path
				$image_syspath = UserManager::get_user_picture_path_by_id(api_get_user_id(), 'system', false, true);
				$image_syspath['dir'].$image_syspath['file'];
				
				$image_size = @getimagesize($image_syspath['dir'].$image_syspath['file']);
				
				//Web path
				$image_path = UserManager::get_user_picture_path_by_id(api_get_user_id(), 'web', false, true);
				$image_dir = $image_path['dir'];
				$image = $image_path['file'];
				$image_file = $image_dir.$image;
				$img_attributes = 'src="'.$image_file.'?rand='.time().'" '
					.'alt="'.api_get_person_name($user_data['firstname'], $user_data['lastname']).'" '
					.'style="float:'.($text_dir == 'rtl' ? 'left' : 'right').'; margin-top:0px;padding:5px;" ';
				if ($image_size[0] > 300) {
					//limit display width to 300px
					$img_attributes .= 'width="300" ';
				}
				
				// get the path,width and height from original picture
				$big_image = $image_dir.'big_'.$image;
				
				$big_image_size = api_getimagesize($big_image);
				$big_image_width = $big_image_size[0];
				$big_image_height = $big_image_size[1];
				$url_big_image = $big_image.'?rnd='.time();
				
				// Style position:absolute has been removed for Opera-compatibility. 
				//echo '<div id="image-message-container" style="float:right;display:inline;position:absolute;padding:3px;width:250px;" >';
				//echo '<div id="image-message-container" style="float:right;display:inline;padding:3px;width:250px;" >';
				
				if ($image == 'unknown.jpg') {
					echo '<img '.$img_attributes.' />';
					if (api_get_setting('profile', 'picture') == 'true') {				
						$form->display();
					}
				} else {
					echo '<input type="image" '.$img_attributes.' onclick="javascript: return show_image(\''.$url_big_image.'\',\''.$big_image_width.'\',\''.$big_image_height.'\');"/>';
				}



			
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