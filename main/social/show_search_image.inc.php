<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) Julio Montoya Armas 

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/

$cidReset = true;
require '../inc/global.inc.php';
$language_file = array('registration','messages','userInfo','admin');
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
	$friend_html.= '<table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#FFFFFC" >';		
	for ($k=0;$k<$loop_friends;$k++) {
		$friend_html.='<tr><td valign="top">';
		if ($j==$number_of_images) {
			$number_of_images=$number_of_images*2;
		}
		while ($j<$number_of_images) {
			if ($list_friends_file[$j]<>"") {
				$user_info=api_get_user_info($list_friends_id[$j]);
				$user_name=api_convert_encoding($user_info['firstName'].' '.$user_info['lastName'],'UTF-8',$charset) ;
				$friends_profile = UserFriend::get_picture_user($list_friends_id[$j], $list_friends_file[$j], 92);
				$friend_html.='<div onMouseover="show_icon_delete(this)" onMouseout="hide_icon_delete(this)" class="image-social-content" id=div_'.$list_friends_id[$j].'  >
				<span><center><img src="'.$friends_profile['file'].'" '.$friends_profile['style'].' id="imgfriend_'.$list_friends_id[$j].'" title="'.$user_name.'" onclick=load_thick(\'qualify_contact.inc.php?path_user="'.urlencode($list_friends_dir[$j].$list_friends_file[$j]).'&amp;id_user="'.$list_friends_id[$j].'"\',"") /></center></span>
				<img onclick="delete_friend (this)" id=img_'.$list_friends_id[$j].' src="../img/blank.gif" alt="" title=""  class="image-delete" /> <center class="friend">'.$user_name.'</center></div>';
				/*
				 * $friend_html.='&nbsp;<div onMouseover="show_icon_delete(this)" onMouseout="hide_icon_delete(this)" class="image-social-content" id=div_'.$list_friends_id[$j].' style="float:left" >
				 * <img src="'.$list_friends_dir[$j]."/".$list_friends_file[$j].'" width="90" height="110" style="margin-left:3px ;margin-rigth:3px;margin-top:10px;margin-bottom:3px;" id="imgfriend_'.$list_friends_id[$j].'" title="'.$user_name.'" onclick="qualify_friend(this)"/>
				 * <img onclick="delete_friend (this)" id=img_'.$list_friends_id[$j].' src="../img/blank.gif" alt="" title=""  class="image-delete" /></div>&nbsp;';
				 */
			}
			$j++;
		}
		$friend_html.='</td></tr>';
	}
	$friend_html.='<br/></table>';
}
echo $friend_html; 
?>
