<?php // $Id: chat.php 13111 2007-09-20 01:10:41Z yannoo $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) Olivier Brouckaert

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact: Dokeos, 181 rue Royale, B-1000 Brussels, Belgium, info@dokeos.com
==============================================================================
*/
/**
==============================================================================
*	Frameset of the Chat tool
*
*	@author Olivier Brouckaert
*	@package dokeos.chat
==============================================================================
*/

$language_file = array ('chat');
include('../inc/global.inc.php');
$nameTools=get_lang('Chat');
if ($_GET["origin"] != 'whoisonline') {
	api_protect_course_script();
}
else
{
	$origin = $_SESSION['origin'];
	$target = $_SESSION['target'];
	$_SESSION['origin']=$_GET["origin"];
	$_SESSION['target']=$_GET["target"];
}
/* ============================================================================== 
  			TRACKING
==============================================================================  */
include('../inc/lib/events.lib.inc.php');
event_access_tool(TOOL_CHAT);

$used_stylesheet=api_get_setting('stylesheets');

switch($used_stylesheet){
	case 'default' : 
		$footer_size = 48;
		break;
	case 'academica' : 
		$footer_size = 140;
		break;
	case 'corporativa' : 
		$footer_size = 60;
		break;
	case 'baby' : 
		$footer_size = 50;
		break;
	default : 
		$footer_size = 48;
		break;
}

?>

<frameset rows="130,*,<?php echo $footer_size;?>" border="0" frameborder="0" framespacing="1">
	<frame src="chat_banner.php" name="chat_banner" scrolling="no">
	<frameset cols="200,*,0" border="1" frameborder="1" framespacing="1">
		<frame src="chat_whoisonline.php" name="chat_whoisonline" scrolling="auto">
		<frameset rows="75,15" border="1" frameborder="1" framespacing="1">
			<frame src="chat_chat.php?origin=<?php echo $_GET["origin"]; ?>&target=<?php echo $_GET["target"]; ?>" name="chat_chat" scrolling="auto">
			<frame src="chat_message.php" name="chat_message" scrolling="no">
		</frameset>
		<frame src="chat_hidden.php" name="chat_hidden" scrolling="no">
	</frameset>
	<frame src="chat_footer.php" name="chat_footer" scrolling="no">
</frameset>

</html>
