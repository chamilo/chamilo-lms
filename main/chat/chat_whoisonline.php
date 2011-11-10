<?php
/* For licensing terms, see /license.txt */
/**
 *	Shows the list of connected users
 *
 *	@author Olivier Brouckaert
 *	@package chamilo.chat
 */
/**
 * Code
 */
define('FRAME', 'online');
$language_file = array('chat');

require_once '../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'course.lib.php';
require_once api_get_path(LIBRARY_PATH).'usermanager.lib.php';

$course = api_get_course_id();
$group_id = intval($_SESSION['_gid']);
$session_id = intval($_SESSION['id_session']);
$session_condition = api_get_session_condition($session_id);
$group_condition = " AND to_group_id = '$group_id'";

$extra_condition = '';
if (!empty($group_id)) {
	$extra_condition = $group_condition;
} else {
	$extra_condition = $session_condition;
}

if (!empty($course)) {
	$showPic = intval($_GET['showPic']);
	$tbl_course_user			= Database::get_main_table(TABLE_MAIN_COURSE_USER);
	$tbl_session_course_user	= Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_session				= Database::get_main_table(TABLE_MAIN_SESSION);
	$tbl_session_course			= Database::get_main_table(TABLE_MAIN_SESSION_COURSE);
	$tbl_user					= Database::get_main_table(TABLE_MAIN_USER);
	$tbl_chat_connected			= Database::get_course_table(CHAT_CONNECTED_TABLE);

	$query = "SELECT username FROM $tbl_user WHERE user_id='".$_user['user_id']."'";
	$result = Database::query($query);

	list($pseudo_user) = Database::fetch_array($result);

	$isAllowed = !(empty($pseudo_user) || !$_cid);
	$isMaster = (bool)$is_courseAdmin;

	$date_inter = date('Y-m-d H:i:s', time() - 120);

	$users = array();
	$course_id = api_get_course_int_id();
	
	if (empty($session_id)) {
		$query = "SELECT DISTINCT t1.user_id,username,firstname,lastname,picture_uri,t3.status 
				  FROM $tbl_user t1,$tbl_chat_connected t2, $tbl_course_user t3 
				  WHERE t2.c_id = $course_id, 
				  		t1.user_id=t2.user_id AND 
				  		t3.user_id=t2.user_id AND 
						t3.relation_type<>".COURSE_RELATION_TYPE_RRHH." AND 
						t3.course_code = '".$_course['sysCode']."' AND 
						t2.last_connection>'".$date_inter."' $extra_condition 
						ORDER BY username";
		$result = Database::query($query);
		$users = Database::store_result($result);
	} else {
		// select learners
		$query = "SELECT DISTINCT t1.user_id,username,firstname,lastname,picture_uri FROM $tbl_user t1,$tbl_chat_connected t2,$tbl_session_course_user t3 WHERE t1.user_id=t2.user_id AND t3.id_user=t2.user_id AND t3.id_session = '".$session_id."' AND t3.course_code = '".$_course['sysCode']."' AND t2.last_connection>'".$date_inter."' $extra_condition ORDER BY username";
		$result = Database::query($query);
		while ($learner = Database::fetch_array($result)) {
			$users[$learner['user_id']] = $learner;
		}

		// select session coach
		$query = "SELECT DISTINCT t1.user_id,username,firstname,lastname,picture_uri FROM $tbl_user t1,$tbl_chat_connected t2,$tbl_session t3 WHERE t1.user_id=t2.user_id AND t3.id_coach=t2.user_id AND t3.id = '".$session_id."' AND t2.last_connection>'".$date_inter."' $extra_condition ORDER BY username";
		$result = Database::query($query);
		if ($coach = Database::fetch_array($result)) {
			$users[$coach['user_id']] = $coach;
		}

		// select session course coach
		$query = "SELECT DISTINCT t1.user_id,username,firstname,lastname,picture_uri
				FROM $tbl_user t1,$tbl_chat_connected t2,$tbl_session_course_user t3
				WHERE t1.user_id=t2.user_id
				AND t3.id_user=t2.user_id AND t3.status=2
				AND t3.id_session = '".$session_id."'
				AND t3.course_code = '".$_course['sysCode']."'
				AND t2.last_connection>'".$date_inter."' $extra_condition ORDER BY username";

		$result = Database::query($query);
		$course_coaches = array();
		while ($coaches = Database::fetch_array($result)) {
			//$course_coaches[] = $coaches['user_id'];
			$users[$coaches['user_id']] = $coaches;
		}

		//if ($coach = Database::fetch_array($result))
		//	$users[$coach['user_id']] = $coach;
	}

	$user_id = $enreg['user_id'];
	require 'header_frame.inc.php';
	
	?>
	<table border="0" cellpadding="0" cellspacing="0" width="100%" class="data_table">
	<tr><th colspan="2"><?php echo get_lang('Connected'); ?></th></tr>
	<?php
	foreach ($users as & $user) {
		if (empty($session_id)) {
			$status = $user['status'];
		} else {
			$status = CourseManager::is_course_teacher($user['user_id'], $_SESSION['_course']['id']) ? 1 : 5;
		}

		$user_image = UserManager::get_user_picture_path_by_id($user['user_id'], 'web', false, true);
		$file_url = $user_image['dir'].$user_image['file'];

	?>
    <tr>
	  <td width="1%" valign="top"><img src="<?php echo $file_url;?>" border="0" width="22" alt="" /></td>
	  <td width="99%"><?php if ($status == 1) echo Display::return_icon('teachers.gif', get_lang('Teacher'), array('height' => '11')).' '; else echo Display::return_icon('students.gif', get_lang('Student'), array('height' => '11')); ?><a <?php if ($status == 1) echo 'class="master"'; ?> name="user_<?php echo $user['user_id']; ?>" href="<?php echo api_get_self(); ?>?<?php echo api_get_cidreq(); ?>&showPic=<?php if ($showPic == $user['user_id']) echo '0'; else echo $user['user_id']; ?>#user_<?php echo $user['user_id']; ?>"><?php echo api_get_person_name($user['firstname'], $user['lastname']); ?></a></td>
	</tr>
	<?php

		if ($showPic == $user['user_id']) { ?>
	<tr>
	  <td colspan="2" align="center"><img src="<?php echo $file_url; ?>" border="0" width="100" alt="" /></td>
	</tr>
	<?php
		}
	}
	unset($users);
	?>
	</table>
	<?php
}

require 'footer_frame.inc.php';
