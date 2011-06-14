<?php
// Storage API
// PHP Backend
// CBlue SPRL, Jean-Karim Bockstael, <jeankarim@cblue.be>

require_once('../inc/global.inc.php');

switch ($_REQUEST['action']) {
	case "get":
		print storage_get($_REQUEST['svuser'], $_REQUEST['svcourse'], $_REQUEST['svsco'], $_REQUEST['svkey']);
		break;
	case "set":
		if (storage_can_set($_REQUEST['svuser'])) {
			print storage_set($_REQUEST['svuser'], $_REQUEST['svcourse'], $_REQUEST['svsco'], $_REQUEST['svkey'], $_REQUEST['svvalue']);
		}
		break;
	case "getall":
		print storage_getall($_REQUEST['svuser'], $_REQUEST['svcourse'], $_REQUEST['svsco']);
		break;
	case "stackpush":
		if (storage_can_set($_REQUEST['svuser'])) {
			print storage_stack_push($_REQUEST['svuser'], $_REQUEST['svcourse'], $_REQUEST['svsco'], $_REQUEST['svkey'], $_REQUEST['svvalue']);
		}
		break;
	case "stackpop":
		if (storage_can_set($_REQUEST['svuser'])) {
			print storage_stack_pop($_REQUEST['svuser'], $_REQUEST['svcourse'], $_REQUEST['svsco'], $_REQUEST['svkey']);
		}
		break;
	case "stacklength":
		print storage_stack_length($_REQUEST['svuser'], $_REQUEST['svcourse'], $_REQUEST['svsco'], $_REQUEST['svkey']);
		break;
	case "stackclear":
		if (storage_can_set($_REQUEST['svuser'])) {
			print storage_stack_clear($_REQUEST['svuser'], $_REQUEST['svcourse'], $_REQUEST['svsco'], $_REQUEST['svkey']);
		}
		break;
	case "stackgetall":
		print storage_stack_getall($_REQUEST['svuser'], $_REQUEST['svcourse'], $_REQUEST['svsco'], $_REQUEST['svkey']);
		break;
	case "usersgetall":
		print storage_get_all_users();
		break;
	default:
		// Do nothing
}

function storage_can_set($sv_user) {
	// platform admin can change any user's stored values, other users can only change their own values
	$allowed = ((api_is_platform_admin()) || ($sv_user == api_get_user_id()));
	if (!$allowed) {
		print "ERROR : Not allowed";
	}
	return $allowed;
}

function storage_get($sv_user, $sv_course, $sv_sco, $sv_key) {
	$sql = "select sv_value
		from ".Database::get_main_table(TABLE_MAIN_STORED_VALUES)."
		where user_id= '$sv_user'
		and sco_id = '$sv_sco'
		and course_id = '$sv_course'
		and sv_key = '$sv_key'";
	$res = Database::query($sql);
	if (mysql_num_rows($res) > 0) {
		$row = Database::fetch_assoc($res);
		return $row['sv_value'];
	}
	else {
		return null;
	}
}

function storage_set($sv_user, $sv_course, $sv_sco, $sv_key, $sv_value) {
	$sv_value = mysql_real_escape_string($sv_value);
	$sql = "replace into ".Database::get_main_table(TABLE_MAIN_STORED_VALUES)."
		(user_id, sco_id, course_id, sv_key, sv_value)
		values
		('$sv_user','$sv_sco','$sv_course','$sv_key','$sv_value')";
	$res = Database::query($sql);
	return mysql_affected_rows();
}

function storage_getall($sv_user, $sv_course, $sv_sco) {
	$sql = "select sv_key, sv_value
		from ".Database::get_main_table(TABLE_MAIN_STORED_VALUES)."
		where user_id= '$sv_user'
		and sco_id = '$sv_sco'
		and course_id = '$sv_course'";
	$res = Database::query($sql);
	$data = array();
	while ($row = Database::fetch_assoc($res)) {
		$data[] = $row;
	}
	return json_encode($data);
}

function storage_stack_push($sv_user, $sv_course, $sv_sco, $sv_key, $sv_value) {
	$sv_value = mysql_real_escape_string($sv_value);
	Database::query("start transaction");
	$sqlorder = "select ifnull((select max(stack_order) 
		from ".Database::get_main_table(TABLE_MAIN_STORED_VALUES_STACK)."
		where user_id= '$sv_user'
		and sco_id='$sv_sco'
		and course_id='$sv_course'
		and sv_key='$sv_key'
		), 0) as stack_order";
	$resorder = Database::query($sqlorder);
	$row = Database::fetch_assoc($resorder);
	$stack_order = (1 + $row['stack_order']);
	$sqlinsert = "insert into ".Database::get_main_table(TABLE_MAIN_STORED_VALUES_STACK)."
		(user_id, sco_id, course_id, sv_key, stack_order, sv_value)
		values
		('$sv_user', '$sv_sco', '$sv_course', '$sv_key', '$stack_order', '$sv_value')";
	$resinsert = Database::query($sqlinsert);
	if ($resorder && $resinsert) {
		Database::query("commit");
		return 1;
	}
	else {
		Database::query("rollback");
		return 0;
	}
}

function storage_stack_pop($sv_user, $sv_course, $sv_sco, $sv_key) {
	Database::query("start transaction");
	$sqlselect = "select sv_value, stack_order
		from ".Database::get_main_table(TABLE_MAIN_STORED_VALUES_STACK)."
		where user_id= '$sv_user'
		and sco_id='$sv_sco'
		and course_id='$sv_course'
		and sv_key='$sv_key'
		order by stack_order desc
		limit 1";
	$resselect = Database::query($sqlselect);
	$rowselect = Database::fetch_assoc($resselect);
	$stack_order = $rowselect['stack_order'];
	$sqldelete = "delete
		from ".Database::get_main_table(TABLE_MAIN_STORED_VALUES_STACK)."
		where user_id= '$sv_user'
		and sco_id='$sv_sco'
		and course_id='$sv_course'
		and sv_key='$sv_key'
		and stack_order='$stack_order'";
	$resdelete = Database::query($sqldelete);
	if ($resselect && $resdelete) {
		Database::query("commit");
		return $rowselect['sv_value'];
	}
	else {
		Database::query("rollback");
		return null;
	}
}

function storage_stack_length($sv_user, $sv_course, $sv_sco, $sv_key) {
	$sql = "select count(*) as length
		from ".Database::get_main_table(TABLE_MAIN_STORED_VALUES_STACK)."
		where user_id= '$sv_user'
		and sco_id='$sv_sco'
		and course_id='$sv_course'
		and sv_key='$sv_key'";
	$res = Database::query($sql);
	$row = Database::fetch_assoc($res);
	return $row['length'];
}

function storage_stack_clear($sv_user, $sv_course, $sv_sco, $sv_key) {
	$sql = "delete
		from ".Database::get_main_table(TABLE_MAIN_STORED_VALUES_STACK)."
		where user_id= '$sv_user'
		and sco_id='$sv_sco'
		and course_id='$sv_course'
		and sv_key='$sv_key'";
	$res = Database::query($sql);
	return mysql_num_rows($res);
}

function storage_stack_getall($sv_user, $sv_course, $sv_sco, $sv_key) {
	$sql = "select stack_order as stack_order, sv_value as value
		from ".Database::get_main_table(TABLE_MAIN_STORED_VALUES_STACK)."
		where user_id= '$sv_user'
		and sco_id='$sv_sco'
		and course_id='$sv_course'
		and sv_key='$sv_key'";
	$res = Database::query($sql);
	$results = array();
	while ($row = Database::fetch_assoc($res)) {
		$results[] = $row;
	}
	return json_encode($results);
}

function storage_get_all_users() {
	$sql = "select user_id, username, firstname, lastname
		from ".Database::get_main_table(TABLE_MAIN_USER)."
		order by user_id asc";
	$res = Database::query($sql);
	$results = array();
	while ($row = Database::fetch_assoc($res)) {
		$results[] = $row;
	}
	return json_encode($results);
}
?>
