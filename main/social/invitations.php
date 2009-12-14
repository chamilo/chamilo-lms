<?php
/* For licensing terms, see /chamilo_license.txt */
/**
 * @package dokeos.social
 * @author Julio Montoya <gugli100@gmail.com>
 */
$language_file = array('messages','userInfo');
$cidReset=true;
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'image.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';

$this_section = SECTION_SOCIAL;

$interbreadcrumb[]= array ('url' =>'profile.php','name' => get_lang('Social'));
$interbreadcrumb[]= array ('url' =>'#','name' => get_lang('Invitations'));

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
$htmlHeadXtra[] = '<script type="text/javascript" src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.js"></script>';
$htmlHeadXtra[] = '<link rel="stylesheet" href="'.api_get_path(WEB_LIBRARY_PATH).'javascript/thickbox.css" type="text/css" media="projection, screen">';

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
		url: "../social/register_friend.php",
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
			url: "../social/register_friend.php",
			data: "friend_id="+user_friend_id+"&is_my_friend="+"friend",
			success: function(datos) {  $("div#"+name_div_id).hide("slow");
				$("form").submit()
			}
		});
 }
}

</script>';
api_block_anonymous_users();

Display :: display_header($tool_name, 'Groups');
SocialManager::show_social_menu();
echo '<div class="actions-title">';
echo get_lang('Invitations');
echo '</div>'; 
// easy links
if (is_array($_GET) && count($_GET)>0) {
	foreach($_GET as $key => $value) { 
		switch ($key) {
			case 'accept':				
				$user_role = GroupPortalManager::get_user_group_role(api_get_user_id(), $value);				
				if (in_array($user_role , array(GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER,GROUP_USER_PERMISSION_PENDING_INVITATION))) {				
					GroupPortalManager::update_user_role(api_get_user_id(), $value, GROUP_USER_PERMISSION_READER);
					$show_message = get_lang('UserIsSubscribedToThisGroup');
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
 
 if (! empty($show_message)){
	Display :: display_normal_message($show_message);
}


$language_variable = get_lang('PendingInvitations');
$language_comment  = get_lang('SocialInvitesComment');
//api_display_tool_title($language_variable);
?>
<div id="id_response" align="center"></div>
<?php
$list_get_invitation=array();
$user_id = api_get_user_id();

$list_get_invitation		= SocialManager::get_list_invitation_of_friends_by_user_id($user_id);
$list_get_invitation_sent	= SocialManager::get_list_invitation_sent_by_user_id($user_id);
$pending_invitations 		= GroupPortalManager::get_groups_by_user($user_id, GROUP_USER_PERMISSION_PENDING_INVITATION, true);
//$pending_invitations_by_me 	= GroupPortalManager::get_groups_by_user($user_id, GROUP_USER_PERMISSION_PENDING_INVITATION_SENT_BY_USER,true);

$number_loop=count($list_get_invitation);

//@todo move this to default.css
echo '<style>
.invitation_confirm {
	border-top:1px solid #D8DFEA;
}
.invitation_image {
	width:110px;
}
</style>';
if ($number_loop != 0) {
	echo '<h2>'.get_lang('InvitationReceived').'</h2>';	
	
	foreach ($list_get_invitation as $invitation) { 
		$sender_user_id = $invitation['user_sender_id']
		?>
		<div id="<?php echo 'id_'.$sender_user_id ?>" class="invitation_confirm">
		   	<?php 
		   		$picture = UserManager::get_user_picture_path_by_id($sender_user_id,'web',false,true);
		   		$friends_profile = SocialManager::get_picture_user($sender_user_id, $picture['file'], 92);
		        $user_info	= api_get_user_info($sender_user_id);	        
		        $title		= get_lang($invitation['title']);
				$content	= get_lang($invitation['content']);
		        $date		= $invitation['send_date'];                  
		    ?>	   	
			<table cellspacing="0" border="0">
			<tbody>
				<tr>
					<td class="invitation_image">
						<a href="profile.php?u=<?=$sender_user_id?>">
						<img src="<?php echo $friends_profile['file']; ?>" <?php echo $friends_profile['style']; ?> /></a>
					</td>
					<td class="info">
							<a class="profile_link" href="profile.php?u=<?=$sender_user_id?>"><?= api_get_person_name($user_info['firstName'], $user_info['lastName']);?></a>
							<div>
							<?= $title.' : '.$content;?>
							</div>
							<div>
							<?= get_lang('DateSend').' : '.$date;?>
							</div> 
							<div class="buttons">
		   						<button class="save" name="btn_accepted" type="submit" id="<?php echo "btn_accepted_".$sender_user_id ?>" value="<?php echo get_lang('Accept');?>"onclick="javascript:register_friend(this)">
		   						<?php echo get_lang('Accept') ?></button>
			     				<button class="cancel" name="btn_denied" type="submit" id="<?php echo "btn_deniedst_".$sender_user_id ?>" value="<?php echo get_lang('Deny'); ?>" onclick="javascript:denied_friend(this)" >
			     				<?php echo get_lang('Deny')?></button>
							</div>					
					</td>
				</tr>
			</tbody>
			</table>
		</div>
		<?php
	}
}
echo '<div class="clear"></div>';

if (count($list_get_invitation_sent) > 0 ){	
	echo '<h2>'.get_lang('InvitationSent').'</h2>';
	foreach ($list_get_invitation_sent as $invitation) { 
		$sender_user_id = $invitation['user_receiver_id'];?>
		<div id="<?php echo 'id_'.$sender_user_id ?>" class="invitation_confirm">
		   	<?php 
		   		$picture = UserManager::get_user_picture_path_by_id($sender_user_id,'web',false,true);
		   		$friends_profile = SocialManager::get_picture_user($sender_user_id, $picture['file'], 92);
		        $user_info	= api_get_user_info($sender_user_id);	  
		              
		        $title		= get_lang($invitation['title']);
				$content	= get_lang($invitation['content']);
		        $date		= $invitation['send_date'];                  
		    ?>	   	
			<table cellspacing="0" border="0">
			<tbody>
				<tr>
					<td class="invitation_image">
						<a href="profile.php?u=<?=$sender_user_id?>">
						<img src="<?php echo $friends_profile['file']; ?>" <?php echo $friends_profile['style']; ?> /></a>
					</td>
					<td class="info">
							<a class="profile_link" href="profile.php?u=<?=$sender_user_id?>"><?= api_get_person_name($user_info['firstName'], $user_info['lastName']);?></a>
							<div>
							<?= $title.' : '.$content;?>
							</div>
							<div>
							<?= get_lang('DateSend').' : '.$date;?>
							</div>		
					</td>
				</tr>
			</tbody>
			</table>
		</div>
	<?php
	}
}

if (count($pending_invitations) > 0) {	
	
	echo '<h2>'.get_lang('GroupsWaitingApproval').'</h2>';
	$new_invitation = array();
	
	foreach ($pending_invitations as $invitation) {
		$invitation['picture_uri'] = '<a href="groups.php?id='.$invitation['id'].'">'.$invitation['picture_uri'].'</a>';		
		$invitation['name'] = '<a href="groups.php?id='.$invitation['id'].'">'.$invitation['name'].'</a>'; 
		$invitation['join'] = '<a href="invitations.php?accept='.$invitation['id'].'">'.get_lang('AcceptInvitation').'</a>';
		$invitation['deny'] = '<a href="invitations.php?deny='.$invitation['id'].'">'.get_lang('DenyInvitation').'</a>';
		$invitation['send_message'] = '<a href="'.api_get_path(WEB_PATH).'main/messages/send_message_to_userfriend.inc.php?height=300&width=610&user_friend='.$invitation['id'].'&view=profile&view_panel=1" class="thickbox" title="'.get_lang('SendMessage').'">';
		$invitation['send_message'] .= Display::return_icon('message_new.png').'&nbsp;&nbsp;'.get_lang('SendMessage').'</a>';
		$new_invitation[]=$invitation;
	}	
	Display::display_sortable_grid('search_users', array(), $new_invitation, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false,false,true,true,true,true));
}
	
Display::display_footer();
?>