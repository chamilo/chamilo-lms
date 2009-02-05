<?php
$cidReset = true;
require '../inc/global.inc.php';
$language_file = array('registration','messages');
require_once (api_get_path(CONFIGURATION_PATH).'profile.conf.php');
include_once (api_get_path(LIBRARY_PATH).'fileManage.lib.php');
include_once (api_get_path(LIBRARY_PATH).'fileUpload.lib.php');
include_once (api_get_path(LIBRARY_PATH).'image.lib.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once '../inc/lib/social.lib.php';
$list_path_friends=array();
$user_id=api_get_user_id();
$name_search=$_POST['search_name_q'];
if (isset($name_search) && $name_search!='undefined') {
	$list_path_friends=UserFriend::get_list_path_web_by_user_id($user_id,null,$name_search);		
} else {
	$list_path_friends=UserFriend::get_list_path_web_by_user_id($user_id);		
}
$friend_html='';
$number_of_images=5;
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
	$friend_html.= '<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFC" >';		
	for ($k=0;$k<$loop_friends;$k++) {
		$friend_html.='<tr><td valign="top">';
		if ($j==$number_of_images) {
			$number_of_images=$number_of_images*2;
		}
		while ($j<$number_of_images) {
			if ($list_friends_file[$j]<>"") {
				$user_info=api_get_user_info($list_friends_id[$j]);
				$name_user=$user_info['firstName'].' '.$user_info['lastName'];
				$friend_html.='&nbsp;<div onMouseover="show_icon_delete(this)" onMouseout="hide_icon_delete(this)" class="image-social-content" id=div_'.$list_friends_id[$j].' style="float:left" ><img src="'.$list_friends_dir[$j]."/".$list_friends_file[$j].'" width="90" height="110" style="margin-left:3px ;margin-rigth:3px;margin-top:10px;margin-bottom:3px;" id="imgfriend_'.$list_friends_id[$j].'" title="'.$name_user.'" onclick="qualify_friend(this)"/><img onclick="delete_friend (this)" id=img_'.$list_friends_id[$j].' src="" alt="" title=""  class="image-delete" /></div>&nbsp;';	
			}
			$j++;
		}
		$friend_html.='</td></tr>';
	}
	$friend_html.='<br/></table>';
}
echo $friend_html; 
?>
