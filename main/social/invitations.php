<?php
/* For licensing terms, see /license.txt */
/**
 * @package chamilo.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
/**
 * Initialization
 */
$language_file = array('messages','userInfo');
$cidReset=true;
require_once '../inc/global.inc.php';

api_block_anonymous_users();
if (api_get_setting('allow_social_tool') !='true') {
    api_not_allowed();
}

require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$this_section = SECTION_SOCIAL;

$interbreadcrumb[]= array ('url' =>'profile.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Invitations'));

$htmlHeadXtra[] = '
<script type="text/javascript">
		
function denied_friend (element_input) {
	name_button=$(element_input).attr("id");
	name_div_id="id_"+name_button.substring(13);
	user_id=name_div_id.split("_");
	friend_user_id=user_id[1];	
	 $.ajax({
		contentType: "application/x-www-form-urlencoded",
		beforeSend: function(objeto) {
		$("#id_response").html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
		type: "POST",
		url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=deny_friend",
		data: "denied_friend_id="+friend_user_id,
		success: function(datos) {
		 $("div#"+name_div_id).hide("slow");
		 $("#id_response").html(datos);
		}
	});
}
function register_friend(element_input) {
 if(confirm("'.get_lang('AddToFriends').'")) {
		name_button=$(element_input).attr("id");
		name_div_id="id_"+name_button.substring(13);
		user_id=name_div_id.split("_");
		user_friend_id=user_id[1];
		 $.ajax({
			contentType: "application/x-www-form-urlencoded",
			beforeSend: function(objeto) {
			$("div#dpending_"+user_friend_id).html("<img src=\'../inc/lib/javascript/indicator.gif\' />"); },
			type: "POST",
			url: "'.api_get_path(WEB_AJAX_PATH).'social.ajax.php?a=add_friend",
			data: "friend_id="+user_friend_id+"&is_my_friend="+"friend",
			success: function(datos) {  $("div#"+name_div_id).hide("slow");
				$("form").submit()
			}
		});
 }
}

</script>';

api_block_anonymous_users();
//Display :: display_header($tool_name, 'Groups');

// easy links
if (is_array($_GET) && count($_GET)>0) {
	foreach($_GET as $key => $value) { 
		switch ($key) {
			case 'accept':				
				$user_role = GroupPortalManager::get_user_group_role(api_get_user_id(), $value);							
				if (in_array($user_role , array(GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER,GROUP_USER_PERMISSION_PENDING_INVITATION))) {				
					GroupPortalManager::update_user_role(api_get_user_id(), $value, GROUP_USER_PERMISSION_READER);
					$show_message = get_lang('UserIsSubscribedToThisGroup');
				} elseif (in_array($user_role , array(GROUP_USER_PERMISSION_READER, GROUP_USER_PERMISSION_ADMIN, GROUP_USER_PERMISSION_MODERATOR))) {
					$show_message = get_lang('UserIsAlreadySubscribedToThisGroup');
				} else {
					$show_message = get_lang('UserIsNotSubscribedToThisGroup');
				}			
			break 2;			
			case 'deny':
				// delete invitation
				GroupPortalManager::delete_user_rel_group(api_get_user_id(), $value); 
				$show_message = get_lang('GroupInvitationWasDeny');
			break 2;
		}		
	}
}
$social_left_content = SocialManager::show_social_menu('invitations');

$social_right_content =  '<div id="id_response" align="center"></div>';

$list_get_invitation = array();
$user_id = api_get_user_id();

$list_get_invitation		= SocialManager::get_list_invitation_of_friends_by_user_id($user_id);
$list_get_invitation_sent	= SocialManager::get_list_invitation_sent_by_user_id($user_id);
$pending_invitations 		= GroupPortalManager::get_groups_by_user($user_id, GROUP_USER_PERMISSION_PENDING_INVITATION);
$number_loop = count($list_get_invitation);

$total_invitations = $number_loop + count($list_get_invitation_sent) + count($pending_invitations);

if ($total_invitations == 0 && count($_GET) <= 0) {
    $social_right_content .= '<a href="search.php">'.get_lang('TryAndFindSomeFriends').'</a><br /><br />';
} 		
		
		if ($number_loop != 0) {
			$social_right_content .= '<h2>'.get_lang('InvitationReceived').'</h2>';	
			
			foreach ($list_get_invitation as $invitation) {				
				$sender_user_id = $invitation['user_sender_id'];				
				$social_right_content .= '<div id="id_'.$sender_user_id.'" class="invitation_confirm">';

				   		$picture = UserManager::get_user_picture_path_by_id($sender_user_id,'web',false,true);
				   		$friends_profile = SocialManager::get_picture_user($sender_user_id, $picture['file'], 92);
				        $user_info	= api_get_user_info($sender_user_id);
				        $title 		= Security::remove_XSS($invitation['title'], STUDENT, true);	        
				        $content 	= Security::remove_XSS($invitation['content'], STUDENT, true);				        
				        $date		= api_convert_and_format_date($invitation['send_date'], DATE_TIME_FORMAT_LONG);  				                        
				       	
					$social_right_content .= '<table cellspacing="0" border="0">
					<tbody>
						<tr>
							<td class="invitation_image">
								<a href="profile.php?u=<?php echo $sender_user_id; ?>">
								<img src="'.$friends_profile['file'].'" '.$friends_profile['style'].' /></a>
							</td>
							<td class="info">
									<a class="profile_link" href="profile.php?u='.$sender_user_id.'">'.api_get_person_name($user_info['firstName'], $user_info['lastName']).'</a>
									<div>
									'.$title.' : '.$content.'
									</div>
									<div>
									'.get_lang('DateSend').' : '.$date.'
									</div> 
									<div class="buttons">
				   						<button class="save" name="btn_accepted" type="submit" id="btn_accepted_'.$sender_user_id.'" value="'.get_lang('Accept').' "onclick="javascript:register_friend(this)">
				   						'.get_lang('Accept').'</button>
					     				<button class="cancel" name="btn_denied" type="submit" id="btn_deniedst_'.$sender_user_id.' " value="'.get_lang('Deny').' " onclick="javascript:denied_friend(this)" >
					     				'.get_lang('Deny').'</button>
									</div>					
							</td>
						</tr>
					</tbody>
					</table>
				</div>';				
			}
		}		
		
		if (count($list_get_invitation_sent) > 0 ) {	
			$social_right_content .= '<h2>'.get_lang('InvitationSent').'</h2>';
			foreach ($list_get_invitation_sent as $invitation) {
				$sender_user_id = $invitation['user_receiver_id'];
                
				$social_right_content .= '<div id="id_'.$sender_user_id.'" class="invitation_confirm">';
				   	
                $picture = UserManager::get_user_picture_path_by_id($sender_user_id,'web',false,true);
                $friends_profile = SocialManager::get_picture_user($sender_user_id, $picture['file'], 92);
                $user_info	= api_get_user_info($sender_user_id);	  

                $title		= Security::remove_XSS($invitation['title'], STUDENT, true);
                $content	= Security::remove_XSS($invitation['content'], STUDENT, true);
                $date		= api_convert_and_format_date($invitation['send_date'], DATE_TIME_FORMAT_LONG); 		               
				$social_right_content .= '	   	
					<table cellspacing="0" border="0">
					<tbody>
						<tr>
							<td class="invitation_image">
								<a href="profile.php?u=<?php echo $sender_user_id;?>">
								<img src="'.$friends_profile['file'].'" '.$friends_profile['style'].' /></a>
							</td>
							<td class="info">
									<a class="profile_link" href="profile.php?u='.$sender_user_id.'">'.api_get_person_name($user_info['firstName'], $user_info['lastName']).'</a>
									<div>
									'. $title.' : '.$content.'
									</div>
									<div>
									'. get_lang('DateSend').' : '.$date.'
									</div>		
							</td>
						</tr>
					</tbody>
					</table>
				</div>
			';
			}
		}
		
		if (count($pending_invitations) > 0) {					
			$social_right_content .= '<h2>'.get_lang('GroupsWaitingApproval').'</h2>';
			$new_invitation = array();				
			foreach ($pending_invitations as $invitation) {					
				$picture = GroupPortalManager::get_picture_group($invitation['id'], $invitation['picture_uri'],80);							
				$img = '<img class="social-groups-image" src="'.$picture['file'].'" hspace="4" height="50" border="2" align="left" width="50" />';
				
				$invitation['picture_uri'] = '<a href="groups.php?id='.$invitation['id'].'">'.$img.'</a>';		
				$invitation['name'] = '<a href="groups.php?id='.$invitation['id'].'">'.cut($invitation['name'],120,true).'</a>';
				$invitation['join'] = '<a href="invitations.php?accept='.$invitation['id'].'">'.Display::return_icon('accept_invitation.png', get_lang('AcceptInvitation')).'&nbsp;&nbsp;'.get_lang('AcceptInvitation').'</a>';
				$invitation['deny'] = '<a href="invitations.php?deny='.$invitation['id'].'">'.Display::return_icon('denied_invitation.png', get_lang('DenyInvitation')).'&nbsp;&nbsp;'.get_lang('DenyInvitation').'</a>';
				$invitation['description'] = cut($invitation['description'],220,true);
				$new_invitation[]=$invitation;
			}
			$social_right_content .= Display::display_return_grid('waiting_user', array(), $new_invitation, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false,false,true,true,true,true));
		}		
	$social_right_content .= '</div>';


$tpl = new Template($tool_name);
$tpl->assign('social_left_content', $social_left_content);
$tpl->assign('social_left_menu', $social_left_menu);
$tpl->assign('social_right_content', $social_right_content);
$social_layout = $tpl->get_template('layout/social_layout.tpl');
$content = $tpl->fetch($social_layout);
$tpl->assign('actions', $actions);
$tpl->assign('message', $show_message);
$tpl->assign('content', $content);
$tpl->display_one_col_template();