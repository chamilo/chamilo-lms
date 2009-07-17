<?php // $Id: online.php 22201 2009-07-17 19:57:03Z cfasanando $
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
*	Frameset of the Online conference tool
*
*	@author Olivier Brouckaert
*	@package dokeos.online
==============================================================================
*/

include('../inc/global.inc.php');
api_protect_course_script();
/* ============================================================================== 
  			TRACKING
==============================================================================  */
event_access_tool(TOOL_CONFERENCE);

?>

<!doctype html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
<title>Online Conference</title>
</head>

<frameset rows="115,*" border="1" frameborder="1" framespacing="1">
	<frame src="online_banner.php" name="online_banner" scrolling="no">
	<frameset cols="220,*,0" border="1" frameborder="1" framespacing="1">
		<frameset rows="230,*" border="1" frameborder="1" framespacing="1">
			<frame src="online_master.php?init=1" name="online_master" scrolling="auto">
			<frame src="online_whoisonline.php" name="online_whoisonline" scrolling="auto">
		</frameset>
		<frameset rows="*,100,40" border="1" frameborder="1" framespacing="1">
			<frame src="online_working_area.php" name="online_working_area" scrolling="auto">
			<frame src="online_chat.php" name="online_chat" scrolling="auto">
			<frame src="online_message.php" name="online_message" scrolling="no">
		</frameset>
		<frameset rows="0,0" border="0" frameborder="0" framespacing="0">
			<frame src="online_hidden1.php" name="online_hidden1" scrolling="no">
			<frame src="online_hidden2.php" name="online_hidden2" scrolling="no">
		</frameset>
	</frameset>
</frameset><noframes></noframes>

</html>
