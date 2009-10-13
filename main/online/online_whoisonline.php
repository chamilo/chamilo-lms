<?php // $Id: online_whoisonline.php 20467 2009-05-11 08:38:29Z ivantcholakov $
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
*	Shows the list of connected users
*
*	@author Olivier Brouckaert
*	@package dokeos.online
==============================================================================
*/

define('FRAME','online');

// name of the language file that needs to be included
$language_file='chat';

include('../inc/global.inc.php');
$this_section=SECTION_COURSES;

$showPic=intval($_GET['showPic']);

$tbl_user				= Database::get_main_table(TABLE_MAIN_USER);
$tbl_course_user 		= Database::get_main_table(TABLE_MAIN_COURSE_USER);
$tbl_online_connected	= Database::get_course_table(TABLE_ONLINE_CONNECTED);

$query="SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
$result=Database::query($query,__FILE__,__LINE__);

list($pseudoUser)=Database::fetch_row($result);

$isAllowed=(empty($pseudoUser) || !$_cid)?false:true;
$isMaster=$is_courseAdmin?true:false;

if(!$isAllowed)
{
	exit();
}

$pictureURL=api_get_path(WEB_CODE_PATH).'upload/users/';

$query="SELECT t1.user_id,t1.username,t1.firstname,t1.lastname,t1.picture_uri,t3.status FROM $tbl_user t1,$tbl_online_connected t2,$tbl_course_user t3 WHERE t1.user_id=t2.user_id AND t3.user_id=t1.user_id AND t3.course_code = '".$_course[sysCode]."'  AND t2.last_connection>'".date('Y-m-d H:i:s',time()-60*5)."' ORDER BY t1.username";
$result=Database::query($query,__FILE__,__LINE__);

$Users=Database::store_result($result);

include('header_frame.inc.php');
?>

<table border="0" cellpadding="0" cellspacing="0" width="100%">

<?php
foreach($Users as $enreg)
{
?>

<tr>
  <td width="1%" rowspan="2" valign="middle"><img src="../img/whoisonline.png" border="0" alt="" style="margin-right: 3px;"></td>
  <td width="99%"><a <?php if($enreg['status'] == 1) echo 'class="master"'; ?> name="user_<?php echo $enreg['user_id']; ?>" href="<?php echo api_get_self(); ?>?showPic=<?php if($showPic == $enreg['user_id']) echo '0'; else echo $enreg['user_id']; ?>#user_<?php echo $enreg['user_id']; ?>"><b><?php echo $enreg['username']; ?></b></a></td>
</tr>
<tr>
  <td width="99%"><small><?php echo api_get_person_name($enreg['firstname'], $enreg['lastname']); ?></small></td>
</tr>

<?php if($showPic == $enreg['user_id']): ?>
<tr>
  <td colspan="2" align="center"><img src="<?php if(empty($enreg['picture_uri'])) echo '../img/unknown.jpg'; else echo $pictureURL.$enreg['picture_uri']; ?>" border="0" width="100" alt="" style="margin-top: 5px;"></td>
</tr>
<?php endif; ?>

<tr>
  <td height="5"></td>
</tr>

<?php
}

unset($Users);
?>

</table>

<?php
include('footer_frame.inc.php');
?>
