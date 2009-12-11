<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package dokeos.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
 
$cidReset = true;
require '../inc/global.inc.php';
$language_file = array('registration','messages','userInfo','admin');
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'image.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once '../inc/lib/social.lib.php';

$list_path_friends	= array();
$user_id	= api_get_user_id();
$name_search= Security::remove_XSS($_POST['search_name_q']);
$number_friends = 0;

if (isset($name_search) && $name_search!='undefined') {
	$friends = SocialManager::get_friends($user_id,null,$name_search);
} else {
	$friends = SocialManager::get_friends($user_id);
}
$friend_html = '';
$number_of_images = 8;

$number_friends = count($friends);
if ($number_friends != 0) {
	$number_loop   = ($number_friends/$number_of_images);
	$loop_friends  = ceil($number_loop);
	$j=0;
	$friend_html.= '<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="" >';
	for ($k=0;$k<$loop_friends;$k++) {
		$friend_html.='<tr><td valign="top">';
		if ($j==$number_of_images) {
			$number_of_images=$number_of_images*2;
		}
		while ($j<$number_of_images) {
			if (isset($friends[$j])) {
				$friend = $friends[$j];
				$user_name = api_xml_http_response_encode($friend['firstName'], $friend['lastName']);
				$friends_profile = SocialManager::get_picture_user($friend['friend_user_id'], $friend['image'], 92);
				$friend_html.='<div onMouseover="show_icon_delete(this)" onMouseout="hide_icon_delete(this)" class="image-social-content" id=div_'.$list_friends_id[$j].'>';
				$friend_html.='<span><a href="profile.php?u='.$friend['friend_user_id'].'"><center><img src="'.$friends_profile['file'].'" '.$friends_profile['style'].' id="imgfriend_'.$friend['friend_user_id'].'" title="'.$user_name.'" /></center></a></span>';
				$friend_html.='<img onclick="delete_friend (this)" id=img_'.$friend['friend_user_id'].' src="../img/blank.gif" alt="" title=""  class="image-delete" /> <center class="friend">'.$user_name.'</center></div>';				
			}
			$j++;
		}
		$friend_html.='</td></tr>';
	}
	$friend_html.='<br/></table>';
}
echo $friend_html;
?>