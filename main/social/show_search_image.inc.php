<?php
/* For licensing terms, see /dokeos_license.txt */

$cidReset = true;
require '../inc/global.inc.php';
$language_file = array('registration','messages','userInfo','admin');
require_once api_get_path(CONFIGURATION_PATH).'profile.conf.php';
require_once api_get_path(LIBRARY_PATH).'fileManage.lib.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'image.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once '../inc/lib/social.lib.php';
$list_path_friends=array();
$user_id=api_get_user_id();
$name_search=Security::remove_XSS($_POST['search_name_q']);
if (isset($name_search) && $name_search!='undefined') {
	$list_path_friends=SocialManager::get_list_path_web_by_user_id($user_id,null,$name_search);
} else {
	$list_path_friends=SocialManager::get_list_path_web_by_user_id($user_id);
}
$friend_html='';
$number_of_images=8;
$number_friends=0;
$list_friends_id=array();
$list_friends_dir=array();
$list_friends_file=array();

if (count($list_path_friends)!=0) {
	for ($z=0;$z<count($list_path_friends['id_friend']);$z++) {
		$list_friends_id[]  = $list_path_friends['id_friend'][$z]['friend_user_id'];
		$list_friends_dir[] = $list_path_friends['path_friend'][$z]['dir'];
		$list_friends_file[]= $list_path_friends['path_friend'][$z]['file'];
	}
	$number_friends= count($list_friends_dir);
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
			if ($list_friends_file[$j]<>"") {
				$user_info=api_get_user_info($list_friends_id[$j]);
				$user_name=api_xml_http_response_encode(api_get_person_name($user_info['firstName'], $user_info['lastName']));
				$friends_profile = SocialManager::get_picture_user($list_friends_id[$j], $list_friends_file[$j], 92);
				$friend_html.='<div onMouseover="show_icon_delete(this)" onMouseout="hide_icon_delete(this)" class="image-social-content" id=div_'.$list_friends_id[$j].'  >
				<span><center><img src="'.$friends_profile['file'].'" '.$friends_profile['style'].' id="imgfriend_'.$list_friends_id[$j].'" title="'.$user_name.'" onclick=load_thick(\'qualify_contact.inc.php?path_user="'.urlencode($list_friends_dir[$j].$list_friends_file[$j]).'&amp;id_user="'.$list_friends_id[$j].'"\',"") /></center></span>
				<img onclick="delete_friend (this)" id=img_'.$list_friends_id[$j].' src="../img/blank.gif" alt="" title=""  class="image-delete" /> <center class="friend">'.$user_name.'</center></div>';
			}
			$j++;
		}
		$friend_html.='</td></tr>';
	}
	$friend_html.='<br/></table>';
}
echo $friend_html;
?>