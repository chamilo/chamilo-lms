<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2009 Dokeos SPRL
	Copyright (c) 2009 Julio Montoya Armas <gugli100@gmail.com>
	Copyright (c) Facultad de Matematicas, UADY (México)
	Copyright (c) Evie, Free University of Brussels (Belgium)	
	Copyright (c) 2009 Isaac Flores Paz <isaac.flores@dokeos.com>	

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
$language_file = array('registration','messages','userInfo','admin');
$cidReset=true;
include_once ('../inc/global.inc.php');
require_once '../messages/message.class.php';
include_once(api_get_path(LIBRARY_PATH).'/usermanager.lib.php');
include_once(api_get_path(LIBRARY_PATH).'/message.lib.php');
include_once(api_get_path(LIBRARY_PATH).'/social.lib.php');
if (api_is_anonymous()) {
	api_not_allowed();
}

if (api_get_setting('allow_message_tool')<>'true' && api_get_setting('allow_social_tool')<>'true'){
	api_not_allowed();
}

if ( isset($_REQUEST['user_friend']) ) {
	$info_user_friend=array();
	$info_path_friend=array();
 	$userfriend_id=Security::remove_XSS($_REQUEST['user_friend']);
 	// panel=1  send message
 	// panel=2  send invitation
 	$panel=Security::remove_XSS($_REQUEST['view_panel']);
 	$info_user_friend=api_get_user_info($userfriend_id);
 	$info_path_friend=UserManager::get_user_picture_path_by_id($userfriend_id,'web',false,true);
}
?>
<table width="600" border="0" height="220">
    <tr height="180">
        <td>    
        <div class="message-content-body-left">
			<img class="message-image-info" src="<?php echo $info_path_friend['dir'].$info_path_friend['file']; ?>"/>
			<?php 
			if ($panel != 1) {
				echo '<br /><center>'.mb_convert_encoding($info_user_friend['firstName'].' '.$info_user_friend['lastName'],'UTF-8',$charset).'</center>'; 					
			}
			?>
		</div>
<div class="message-conten-body-right">
<div id="id_content_panel_init">
			<dl>
<?php 
		/*if ($panel == 1) {
		<dd><a href="javascript:void(0)" onclick="change_panel('2','<?php echo $userfriend_id; ?>')"><?php echo mb_convert_encoding(get_lang('SendInviteMessage'),'UTF-8',$charset);?></a></dd>' .
		}
		<input type="button" value="<?php echo mb_convert_encoding(get_lang('NewMessage'),'UTF-8',$charset); ?>" onclick="hide_display_message()" />&nbsp;&nbsp;&nbsp;
		**/		
		if (api_get_setting('allow_message_tool')=='true') {
			if ($panel == 1) {
		   		 $user_info=api_get_user_info($userfriend_id);
		  		 echo mb_convert_encoding(get_lang('To'),'UTF-8',$charset); ?> :&nbsp;&nbsp;&nbsp;&nbsp;<?php echo mb_convert_encoding($user_info['firstName'].' '.$user_info['lastName'],'UTF-8',$charset); ?>
		  		 <br/>
		 		 <br/><?php echo mb_convert_encoding(get_lang('Subject'),'UTF-8',$charset); ?> :<br/><input id="txt_subject_id" type="text" style="width:300px;"><br/>
		   		 <br/><?php echo mb_convert_encoding(get_lang('Message'),'UTF-8',$charset); ?> :<br/><textarea id="txt_area_invite" rows="4" cols="41"></textarea>
		   		 <br /><br />		    	  
		   		 <input type="button" value="<?php echo get_lang('SendMessage'); ?>" onclick="action_database_panel('5','<?php echo $userfriend_id;?>')" />
<?php
			} else {				
				echo mb_convert_encoding(get_lang('AddPersonalMessage'),'UTF-8',$charset);  ?> :<br/><br/>
				<textarea id="txt_area_invite" rows="5" cols="41"></textarea><br /><br />
						    
		    	<input type="button" value="<?php echo mb_convert_encoding(get_lang('SocialAddToFriends'),'UTF-8',$charset); ?>" onclick="action_database_panel('4','<?php echo $userfriend_id;?>')" />
<?php					
				}
			}
			if (!isset($_REQUEST['view'])) {
				//<dd><a href="main/social/index.php#remote-tab-5"> echo get_lang('SocialSeeContacts'); </a></dd>
			}
?>
			</dl>			
</div>
        </td>
    </tr>
        </div>
    <tr height="22">
        <td>
			<div id="display_response_id" style="position:relative"></div>
			<div class="message-bottom-title">&nbsp;</div>
		</td>
	</tr>
</table>