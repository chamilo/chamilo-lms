<?php
// Storage API
// PHP Backend

require_once('../inc/global.inc.php');

switch ($_REQUEST['action']) {
	case "get":
		print storage_get($_REQUEST['svuser'], $_REQUEST['svcourse'], $_REQUEST['svsco'], $_REQUEST['svkey']);
		break;
	case "set":
		print storage_set($_REQUEST['svuser'], $_REQUEST['svcourse'], $_REQUEST['svsco'], $_REQUEST['svkey'], $_REQUEST['svvalue']);
		break;
	case "getall":
		print storage_getall($_REQUEST['svuser'], $_REQUEST['svcourse'], $_REQUEST['svsco']);
		break;
	default:
		// Do nothing
}

function storage_get($sv_user, $sv_course, $sv_sco, $sv_key) {
	$mainDB = Database::get_main_database();
	$sql = "select sv_value
		from $mainDB.stored_values
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
	$mainDB = Database::get_main_database();
	$sql = "replace into $mainDB.stored_values
		(user_id, sco_id, course_id, sv_key, sv_value)
		values
		('$sv_user','$sv_sco','$sv_course','$sv_key','$sv_value')";
	$res = Database::query($sql);
	return mysql_affected_rows();
}

function storage_getall($sv_user, $sv_course, $sv_sco) {
	$mainDB = Database::get_main_database();
	$sql = "select sv_key, sv_value
		from $mainDB.stored_values
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
?>
