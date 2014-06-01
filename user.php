<?php
/* For licensing terms, see /license.txt */
/**
 *  Clean URls for the Social Network
 *
 *  The idea is to access to the user info more easily:
 *  http://campus.chamilo.org/admin instead of 
 *  http://campus.chamilo.org/main/social/profile.php?1
 *  To use this you should rename the htaccess to .htaccess and check your 
 *  virtualhost configuration
 *
 *  More improvements will come in next versions of Chamilo maybe in the 1.8.8
 *  @package chamilo.main
 */
/**
 * Variables definitions and inclusions
 */
// name of the language file that needs to be included
$language_file = array('index','registration','messages','userInfo');

$cidReset = true;
require_once 'main/inc/global.inc.php';

/**
 * Access permissions check
 */
api_block_anonymous_users();

/**
 * Treat URL arguments
 */
$array_keys = array_keys($_GET);

if (!empty($array_keys)) {
	$username 	= substr($array_keys[0],0,20); // max len of an username
	$friend_id 	= UserManager::get_user_id_from_username($username);

	if ($friend_id) {
		SocialManager::display_individual_user($friend_id);
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