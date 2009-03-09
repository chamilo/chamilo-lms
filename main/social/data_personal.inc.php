<?php
$language_file = array('registration','messages','userInfo','admin');
require_once ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');

// @todo here we must show the user information as read only 
//User picture size is calculated from SYSTEM path
$user_info= UserManager::get_user_info_by_id(api_get_user_id());
$img_array= UserManager::get_user_picture_path_by_id(api_get_user_id(),'web',true,true);

echo '<div id="actions">';
	echo '<a href="../auth/profile.php?show=1"">'.Display::return_icon('edit.gif').'&nbsp;'.mb_convert_encoding(get_lang('EditInformation'),'UTF-8',$charset).'</a>';
echo '</div">';

echo '<div id="profile_container" style="width:500px;">';
	echo '<div id="picture" style="width:200px;float:right;position:relative;">'; 
		echo '<img src='.$img_array['dir'].$img_array['file'].' />';
	echo '</div>';	
	echo '<div class="social-profile-info">';
		echo '<dl>';
		echo '<dt>'.mb_convert_encoding(get_lang('UserName'),'UTF-8',$charset).'</dt>
		<dd>'. mb_convert_encoding($user_info['username'],'UTF-8',$charset).'	</dd>';
		echo '<dt>'.mb_convert_encoding(get_lang('FirstName'),'UTF-8',$charset).'</dt>
		<dd>'. mb_convert_encoding($user_info['firstname'],'UTF-8',$charset).'</dd>';
		echo '<dt>'.mb_convert_encoding(get_lang('LastName'),'UTF-8',$charset).'</dt>
		<dd>'. mb_convert_encoding($user_info['lastname'],'UTF-8',$charset).'</dd>';
		echo '<dt>'.mb_convert_encoding(get_lang('OfficialCode'),'UTF-8',$charset).'</dt>	
		<dd>'. mb_convert_encoding($user_info['official_code'],'UTF-8',$charset).'</dd>';
		echo '<dt>'.mb_convert_encoding(get_lang('Email'),'UTF-8',$charset).'</dt>
		<dd>'. mb_convert_encoding($user_info['email'],'UTF-8',$charset).'</dd>';
		echo '<dt>'.mb_convert_encoding(get_lang('Phone'),'UTF-8',$charset).'</dt>
		<dd>'. mb_convert_encoding($user_info['phone'],'UTF-8',$charset).'</dd>';
		echo '</dl>';
	echo '</div>';
echo '</div>';
?>