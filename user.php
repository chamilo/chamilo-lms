<?php
/* For licensing terms, see /license.txt */

/**
 *  Clean URls for the Social Network
 * 
 *  The idea is to access to the user info more easily:
 *  http://campus.chamilo.org/admin instead of http://campus.chamilo.org/main/social/profile.php?1
 *  To use this you should rename the htaccess to .htaccess and check your virtualhost configuration
 * 
 *  More improvements will come in the next version of Chamilo maybe in the 1.8.8
 *   
 */


// name of the language file that needs to be included
$language_file = array('index','registration','messages','userInfo');

$cidReset = true;
require_once 'main/inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

api_block_anonymous_users();

$array_keys = array_keys($_GET);

if (!empty($array_keys)) {
	$username 	= substr($array_keys[0],0,20); // max len of an username	
	$friend_id 	= UserManager::get_user_id_from_username($username);
			
	if ($friend_id != false) {
		SocialManager::display_individual_user($friend_id);
		/*	
		if (api_get_setting('allow_social_tool') =='true') {
			header('Location: main/social/profile.php?u='.$friend_id.'');
			exit;
		} else {
			header('Location: whoisonline.php?id='.$friend_id.'');
			exit;
		}*/
	} else {
		// we cant find your friend
		header('Location: whoisonline.php');
		exit;
	}
} else {
		// we cant find your friend
	header('Location: whoisonline.php');
	exit;
}
?>
