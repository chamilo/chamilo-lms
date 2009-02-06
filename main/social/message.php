<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
    Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, 108 rue du Corbeau, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
$language_file = array('registration','messages');
require ('../inc/global.inc.php');
require_once (api_get_path(LIBRARY_PATH).'usermanager.lib.php');
require_once api_get_path(LIBRARY_PATH).'social.lib.php';
$this_section = SECTION_MYPROFILE;
$_SESSION['this_section']=$this_section;
?>
<div id="id" class="actions">
<?php
echo get_lang('WelcomeMessageTool');
?>
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