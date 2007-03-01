<?php
// $Id: course_list.php,v 1.15.2.1 2005/10/31 09:15:57 olivierb78 Exp $
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004 Dokeos S.A.
	Copyright (c) 2003 University of Ghent (UGent)
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
	@author Bart Mollet
*	@package dokeos.admin
============================================================================== 
*/
/*
==============================================================================
		INIT SECTION
==============================================================================
*/

// name of the language file that needs to be included
$language_file = 'admin';
$cidReset = true;
require ('../inc/global.inc.php');

// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script();
$tool_name = get_lang('SessionOverview');
$interbreadcrumb[]=array('url' => 'index.php',"name" => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => "session_list.php","name" => get_lang('SessionList'));

// Database Table Definitions
$tbl_session						= Database::get_main_table(TABLE_MAIN_SESSION);
$tbl_session_rel_class				= Database::get_main_table(TABLE_MAIN_SESSION_CLASS);
$tbl_session_rel_course				= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
$tbl_course							= Database::get_main_table(TABLE_MAIN_COURSE);
$tbl_user							= Database::get_main_table(TABLE_MAIN_USER);
$tbl_session_rel_user				= Database::get_main_table(TABLE_MAIN_SESSION_USER);
$tbl_session_rel_course_rel_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$tbl_class							= Database::get_main_table(TABLE_MAIN_CLASS);
$tbl_class_rel_user					= Database::get_main_table(TABLE_MAIN_CLASS_USER);

$id_session = $_GET['id_session'];



if($_GET['action'] == 'delete')
{
	$isChecked = $_GET['isChecked'];
	if(is_array($idChecked))
	{
		$idChecked="'".implode("','",$idChecked)."'";

		api_sql_query("DELETE FROM $tbl_session_rel_course WHERE id_session='$id_session' AND course_code IN($idChecked)",__FILE__,__LINE__);
		
		$nbr_affected_rows=mysql_affected_rows();

		api_sql_query("DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND course_code IN($idChecked)",__FILE__,__LINE__);

		api_sql_query("UPDATE $tbl_session SET nbr_courses=nbr_courses-$nbr_affected_rows WHERE id='$id_session'",__FILE__,__LINE__);
	}
	
	if(!empty($_GET['class'])){
		api_sql_query("DELETE FROM $tbl_session_rel_class WHERE session_id='$id_session' AND class_id=".$_GET['class'],__FILE__,__LINE__);
		
		$nbr_affected_rows=mysql_affected_rows();

		api_sql_query("UPDATE $tbl_session SET nbr_classes=nbr_classes-$nbr_affected_rows WHERE id='$id_session'",__FILE__,__LINE__);
		
	}
	
	if(!empty($_GET['user'])){
		api_sql_query("DELETE FROM $tbl_session_rel_user WHERE id_session='$id_session' AND id_user=".$_GET['user'],__FILE__,__LINE__);
		$nbr_affected_rows=mysql_affected_rows();
		api_sql_query("UPDATE $tbl_session SET nbr_users=nbr_users-$nbr_affected_rows WHERE id='$id_session'",__FILE__,__LINE__);
		
		api_sql_query("DELETE FROM $tbl_session_rel_course_rel_user WHERE id_session='$id_session' AND id_user=".$_GET['user'],__FILE__,__LINE__);
		$nbr_affected_rows=mysql_affected_rows();
		api_sql_query("UPDATE $tbl_session_rel_course SET nbr_users=nbr_users-$nbr_affected_rows WHERE id_session='$id_session'",__FILE__,__LINE__);
	}
}		

$sql = 'SELECT name, nbr_courses, nbr_users, nbr_classes, DATE_FORMAT(date_start,"%d-%m-%Y") as date_start, DATE_FORMAT(date_end,"%d-%m-%Y") as date_end, lastname, firstname, username
		FROM '.$tbl_session.' 
		LEFT JOIN '.$tbl_user.'
			ON id_coach = user_id
		WHERE '.$tbl_session.'.id='.$id_session;

$rs = api_sql_query($sql, __FILE__, __LINE__);
$session = api_store_result($rs);
$session = $session[0];



Display::display_header($tool_name);

api_display_tool_title($tool_name);
?>
<!-- General properties -->
<table class="data_table" width="100%">
<tr>
  <th colspan="2"><?php echo get_lang('GeneralProperties'); ?>
  	<a href="session_edit.php?page=resume_session.php&id=<?php echo $id_session; ?>"><img src="../img/edit.gif" border="0" align="absmiddle" title="Editer"></a></th>
  </th>
</tr>
<tr>
	<td><?php echo get_lang('SessionName');?> :</td>
	<td><?php echo $session['name'] ?></td>
</tr>
<tr>
	<td><?php echo get_lang('GeneralCoach'); ?> :</td>
	<td><?php echo $session['lastname'].' '.$session['firstname'].' ('.$session['username'].')' ?></td>
</tr>
<tr>
	<td><?php echo ('Dates'); ?> :</td>	
	<td>
	<?php 
		if($session['date_start']=='00-00-0000')
			echo get_lang('NoTimeLimits');
		else
			echo 'Du '.$session['date_start'].' au '.$session['date_end'];
		 ?>
	</td>
</tr>
</table>

<br />

<!--List of courses -->
<table class="data_table" width="100%">
<tr>
  <th colspan="4"><?php echo get_lang('CourseList'); ?>
  	<a href="add_courses_to_session.php?page=resume_session.php&id_session=<?php echo $id_session; ?>"><img src="../img/edit.gif" border="0" align="absmiddle" title="Editer"></a></th>
  </th>
</tr>
<tr>
  <tr>
  <th width="35%"><?php echo get_lang('CourseTitle'); ?></th>
  <th width="30%"><?php echo get_lang('CourseCoach'); ?></th>
  <th width="20%"><?php echo get_lang('UsersNumber'); ?></th>
  <th width="15%"><?php echo get_lang('Actions'); ?></th>
</tr>
</tr>
<?php
if($session['nbr_courses']==0){
	echo '
		<tr>
			<td colspan="4">Pas de cours pour cette session</td>
		</tr>';
}
else {
	$sql = "SELECT code,title,nbr_users, lastname, firstname, username
			FROM $tbl_course,$tbl_session_rel_course 
			LEFT JOIN $tbl_user
				ON $tbl_session_rel_course.id_coach = $tbl_user.user_id
			WHERE course_code=code
			AND id_session='$id_session'
			ORDER BY title";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$courses=api_store_result($result);
	foreach($courses as $course){
		if(empty($course['username']))
			$coach = 'Aucun';
		else
			$coach = $course['lastname'].' '.$course['firstname'].' ('.$course['username'].')';
		echo '
		<tr>
			<td>'.$course['title'].' ('.$course['code'].')</td>
			<td>'.$coach.'</td>
			<td>'.$course['nbr_users'].'</td>
			<td>
				<a href="../tracking/courseLog.php?cidReq='.$course['code'].'"><img src="../img/statistics.gif" border="0" style="vertical-align: middle;" title="'.get_lang('Tracking').'" alt="'.get_lang('Tracking').'"/></a>&nbsp;
				<a href="session_course_edit.php?id_session='.$id_session.'&page=resume_session.php&course_code='.$course['code'].'"><img src="../img/edit.gif" border="0" align="absmiddle" title="Editer"></a>
				<a href="'.$_SERVER['PHP_SELF'].'?id_session='.$id_session.'&action=delete&idChecked[]='.$course['code'].'" onclick="javascript:if(!confirm(\'Veuillez confirmer votre choix.\')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" title="Effacer"></a>
			</td>
		</tr>';
	}
}
?>
</table>

<br />

<!--List of courses -->
<table class="data_table" width="100%">
<tr>
  <th colspan="4"><?php echo get_lang('UserList'); ?>
  	<a href="add_users_to_session.php?page=resume_session.php&id_session=<?php echo $id_session; ?>"><img src="../img/edit.gif" border="0" align="absmiddle" title="Editer"></a></th>
  </th>
</tr>
</tr>
<?php
if($session['nbr_users']==0){
	echo '
		<tr>
			<td colspan="2">Pas d\'utilisateurs pour cette session</td>
		</tr>';
}
else {

	// classe development, obsolete for the moment
	
	$sql = 'SELECT '.$tbl_user.'.user_id, lastname, firstname, username
			FROM '.$tbl_user.'
			INNER JOIN '.$tbl_session_rel_user.'
				ON '.$tbl_user.'.user_id = '.$tbl_session_rel_user.'.id_user
				AND '.$tbl_session_rel_user.'.id_session = '.$id_session.'
			ORDER BY lastname, firstname';
	
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$users=api_store_result($result);
	foreach($users as $user){
		echo '<tr>
					<td width="90%">
						<b>'.$user['lastname'].' '.$user['firstname'].' ('.$user['username'].')</b>
					</td>
					<td>
						<a href="../mySpace/student.php?user_id='.$user['user_id'].'"><img src="../img/statistics.gif" border="0" style="vertical-align: middle;" title="'.get_lang('Reporting').'" alt="'.get_lang('Reporting').'"/></a>&nbsp;<a href="'.$_SERVER['PHP_SELF'].'?id_session='.$id_session.'&action=delete&user='.$user['user_id'].'" onclick="javascript:if(!confirm(\'Veuillez confirmer votre choix.\')) return false;"><img src="../img/delete.gif" border="0" align="absmiddle" title="Effacer"></a>
					</td>
				  </tr>';
	}
}
?>
</table>
<?php

/*
==============================================================================
		FOOTER 
==============================================================================
*/
Display :: display_footer();
?> 




