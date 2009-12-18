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
	
		echo '<div id="social_center">';
		echo 'myinfo';
		echo '</div>';
		
		echo '<div id="social_right">';
		echo 'group info';
		echo '</div>';
		
	echo '</div>';
	
echo '</div>';

Display :: display_footer();