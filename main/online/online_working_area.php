<?php // $Id: online_working_area.php 10204 2006-11-26 20:46:53Z pcool $
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
*	Welcome page of the working area (right frame)
*
*	@author Olivier Brouckaert
*	@package dokeos.online
==============================================================================
*/

define('FRAME','working_area');

// name of the language file that needs to be included 
$language_file='chat';

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$tbl_user = Database::get_main_table(TABLE_MAIN_USER);
$query="SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
$result=api_sql_query($query,__FILE__,__LINE__);

list($pseudoUser)=mysql_fetch_row($result);

$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
$isMaster=$is_courseAdmin?true:false;

if(!$isAllowed)
{
	api_not_allowed();
}

include('header_frame.inc.php');
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%" height="100%">
<tr>
  <td align="center" valign="middle">
    <img src="../img/whiteboard.gif" border="0"><br><br><br>
    <?php echo get_lang('WelcomeToOnlineConf'); ?> !
  </td>
</tr>
</table>

<?php
include('footer_frame.inc.php');
?>
