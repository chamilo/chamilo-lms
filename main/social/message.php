<?php
/*
 * Created on 25/01/2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
$language_file = array('registration','messages');
require ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
$this_section = SECTION_MYPROFILE;
$_SESSION['this_section']=$this_section;
?>
<div id="id" class="actions">
Bienvenido a la herramienta mensajes...desde aqui usted puede comunicarse con todos los usuarios en linea
</div>
<?php
 if (api_get_setting('allow_message_tool')=='true') {
	
	include (api_get_path(LIBRARY_PATH).'message.lib.php');
	$number_of_new_messages = get_new_messages();
	$cant_msg = ' ('.$number_of_new_messages.')';
	if($number_of_new_messages==0) {		
		$cant_msg= ''; 
	}
	
	$number_of_new_messages_of_friend=UserFriend::get_message_number_invitation_by_user_id(api_get_user_id());
	
	echo '<div class="message-content">
			<h2 class="message-title">'.get_lang('Message').'</h2>
			<p>
				<a href="../messages/inbox.php"  class="message-body">'.get_lang('Inbox').$cant_msg.' </a><br />
				<a href="../messages/new_message.php" class="message-body">'.get_lang('Compose').'</a><br />
				<a href="../messages/outbox.php" class="message-body">'.get_lang('Outbox').'</a><br />
			</p>';		
	
echo '</div>';
}
 
?>
