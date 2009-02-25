<?php // $Id: resume_session.php 18673 2009-02-25 04:53:55Z yannoo $
/* For licensing terms, see /dokeos_license.txt */
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
require '../inc/global.inc.php';

// setting the section (for the tabs)
$this_section=SECTION_PLATFORM_ADMIN;

api_protect_admin_script(true);
$tool_name = get_lang('SessionOverview');
$interbreadcrumb[]=array('url' => 'index.php','name' => get_lang('PlatformAdmin'));
$interbreadcrumb[]=array('url' => 'session_list.php','name' => get_lang('SessionList'));

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

$id_session = (int)$_GET['id_session'];

$sql = 'SELECT name, nbr_courses, nbr_users, nbr_classes, DATE_FORMAT(date_start,"%d-%m-%Y") as date_start, DATE_FORMAT(date_end,"%d-%m-%Y") as date_end, lastname, firstname, username, session_admin_id, nb_days_access_before_beginning, nb_days_access_after_end
		FROM '.$tbl_session.'
		LEFT JOIN '.$tbl_user.'
			ON id_coach = user_id
		WHERE '.$tbl_session.'.id='.$id_session;

$rs = api_sql_query($sql, __FILE__, __LINE__);
$session = api_store_result($rs);
$session = $session[0];

if(!api_is_platform_admin() && $session['session_admin_id']!=$_user['user_id'])
{
	api_not_allowed(true);
}


if($_GET['action'] == 'delete')
{
	$idChecked = $_GET['idChecked'];
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




Display::display_header($tool_name);
if (!empty($_GET['warn'])) {
    Display::display_warning_message(urldecode($_GET['warn']));    
}

api_display_tool_title($tool_name);
?>
<!-- General properties -->
<table class="data_table" width="100%">
<tr>
  <th colspan="2"><?php echo get_lang('GeneralProperties'); ?>
  	<a href="session_edit.php?page=resume_session.php&id=<?php echo $id_session; ?>"><?php Display::display_icon('edit.gif', get_lang('Edit')); ?></a></th>
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
			echo get_lang('From').' '.$session['date_start'].' '.get_lang('To').' '.$session['date_end'];
		 ?>
	</td>
</tr>

<!-- show nb_days_before and nb_days_after only if they are different from 0 -->
<tr>
	<td>
		<?php echo ucfirst(get_lang('DaysBefore')) ?> :
	</td>
	<td>
		<?php echo intval($session['nb_days_access_before_beginning']) ?>
	</td>
</tr>
<tr>
	<td>
		<?php echo ucfirst(get_lang('DaysAfter')) ?> :
	</td>
	<td>
		<?php echo intval($session['nb_days_access_after_end']) ?>
	</td>
</tr>
</table>

<br />

<!--List of courses -->
<table class="data_table" width="100%">
<tr>
  <th colspan="4"><?php echo get_lang('CourseList'); ?>
  	<a href="add_courses_to_session.php?page=resume_session.php&id_session=<?php echo $id_session; ?>"><?php Display::display_icon('edit.gif', get_lang('Edit')); ?></a></th>
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
			<td colspan="4">'.get_lang('NoCoursesForThisSession').'</td>
		</tr>';
}
else {
	// select the courses
	$sql = "SELECT code,title,visual_code, nbr_users, lastname, firstname, username
			FROM $tbl_course,$tbl_session_rel_course
			LEFT JOIN $tbl_user
				ON $tbl_session_rel_course.id_coach = $tbl_user.user_id
			WHERE course_code=code
			AND id_session='$id_session'
			ORDER BY title";
	$result=api_sql_query($sql,__FILE__,__LINE__);
	$courses=api_store_result($result);
	foreach($courses as $course){
		//select the number of users
		$sql = 'SELECT COUNT(id_user) as nb_users FROM '.$tbl_session_rel_course_rel_user.' WHERE course_code="'.Database::escape_string($course['code']).'" AND id_session='.intval($id_session);
		$rs = api_sql_query($sql, __FILE__, __LINE__);
		$course['nbr_users'] = mysql_result($rs,0,0);
		if (empty($course['username'])) {
			$coach = get_lang('None');		
		} else {
			$coach = $course['lastname'].' '.$course['firstname'].' ('.$course['username'].')';
		}

		$orig_param = '&origin=resume_session';	
		//hide_course_breadcrumb the parameter has been added to hide the name of the course, that appeared in the default $interbreadcrumb
		echo '
		<tr>
			<td>'.$course['title'].' ('.$course['visual_code'].')</td>
			<td>'.$coach.'</td>
			<td>'.$course['nbr_users'].'</td>
			<td>
				<a href="../tracking/courseLog.php?id_session='.$id_session.'&cidReq='.$course['code'].$orig_param.'&hide_course_breadcrumb=1">'.Display::return_icon('statistics.gif', get_lang('Tracking')).'</a>&nbsp;
				<a href="session_course_edit.php?id_session='.$id_session.'&page=resume_session.php&course_code='.$course['code'].''.$orig_param.'">'.Display::return_icon('edit.gif', get_lang('Edit')).'</a>
				<a href="'.api_get_self().'?id_session='.$id_session.'&action=delete&idChecked[]='.$course['code'].'" onclick="javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>
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
  	<a href="add_users_to_session.php?page=resume_session.php&id_session=<?php echo $id_session; ?>"><?php Display::display_icon('edit.gif', get_lang('Edit')); ?></a></th>
  </th>
</tr>
</tr>
<?php
if($session['nbr_users']==0){
	echo '
		<tr>
			<td colspan="2">'.get_lang('NoUsersForThisSession').'</td>
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
	$orig_param = '&origin=resume_session&id_session='.$id_session; // change breadcrumb in destination page
	foreach($users as $user){
		echo '<tr>
					<td width="90%">
						<b>'.$user['lastname'].' '.$user['firstname'].' ('.$user['username'].')</b>
					</td>
					<td>
						<a href="../mySpace/myStudents.php?student='.$user['user_id'].''.$orig_param.'">'.Display::return_icon('statistics.gif', get_lang('Reporting')).'</a>&nbsp;<a href="'.api_get_self().'?id_session='.$id_session.'&action=delete&user='.$user['user_id'].'" onclick="javascript:if(!confirm(\''.get_lang('ConfirmYourChoice').'\')) return false;">'.Display::return_icon('delete.gif', get_lang('Delete')).'</a>
					</td>
				  </tr>';
	}
}
?>
</table>
<?php
// footer
Display :: display_footer();
?>