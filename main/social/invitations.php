<?php
/* For licensing terms, see /dokeos_license.txt */

$language_file = array('messages','userInfo','admin');
require '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'image.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
require_once api_get_path(LIBRARY_PATH).'group_portal_manager.lib.php';
$this_section = SECTION_SOCIAL;

$interbreadcrumb[]= array ('url' =>'home.php','name' => get_lang('Social'));

$htmlHeadXtra[] = '<script src="'.api_get_path(WEB_LIBRARY_PATH).'javascript/jquery.js" type="text/javascript" language="javascript"></script>'; //jQuery
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
	 

$request = api_is_xml_http_request();
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

$pending_invitations = GroupPortalManager::get_groups_by_user($user_id, GROUP_USER_PERMISSION_PENDING_INVITATION,true);

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
if ($number_loop==0) {
	Display::display_normal_message(get_lang('NoPendingInvitations'));
} else {
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
	Display::display_sortable_grid('search_users', array(), $pending_invitations, array('hide_navigation'=>true, 'per_page' => 100), $query_vars, false, array(true, true, true,false));
}
	
Display::display_footer();
?>