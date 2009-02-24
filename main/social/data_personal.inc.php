<?php
$language_file = array('registration','messages','userInfo','admin');
require_once ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');

// @todo here we must show the user information as read only 
//User picture size is calculated from SYSTEM path
$user_info= UserManager::get_user_info_by_id(api_get_user_id());
$img_array= UserManager::get_user_picture_path_by_id(api_get_user_id(),'web',true,true);

echo '<div id="actions">';
	echo '<a href="../auth/profile.php?show=1"">'.Display::return_icon('edit.gif').'&nbsp;'.utf8_encode(get_lang('EditInformation')).'</a>';
echo '</div">';

echo '<div id="profile_container" style="width:500px;">';
	echo '<div id="picture" style="width:200px;float:right;position:relative;">'; 
		echo '<img src='.$img_array['dir'].$img_array['file'].' />';
	echo '</div>';	
	echo '<div class="social-profile-info">';
		echo '<dl>';
		echo '<dt>'.get_lang('Username').'</dt>		<dd>'. $user_info['username'].'	</dd>';
		echo '<dt>'.get_lang('Firstname').'</dt>	<dd>'. $user_info['firstname'].'</dd>';
		echo '<dt>'.get_lang('Lastname').'</dt>		<dd>'. $user_info['lastname'].'</dd>';
		echo '<dt>'.get_lang('OfficialCode').'</dt>	<dd>'. $user_info['official_code'].'</dd>';
		echo '<dt>'.get_lang('Email').'</dt>		<dd>'. $user_info['email'].'</dd>';
		echo '<dt>'.get_lang('Phone').'</dt>		<dd>'. $user_info['phone'].'</dd>';
		echo '</dl>';
	echo '</div>';
echo '</div>';
?>
