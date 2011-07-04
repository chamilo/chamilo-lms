<?php
/* For licensing terms, see /license.txt */
/**
 *	@package chamilo.chat
 */
 
/**
 * @param integer
 * @return void
 */
function exit_of_chat($user_id) {
	$user_id = intval($user_id);
	$list_course = array();
 	$list_course = CourseManager::get_courses_list_by_user_id($user_id);

	$group_id = intval($_SESSION['id_group']);
	$session_id = intval($_SESSION['id_session']);
	
	$extra_condition = '';
	if (!empty($group_id)) {
		$extra_condition = " AND to_group_id = '$group_id'";
	} else {
		$extra_condition = api_get_session_condition($session_id);
	}

 	foreach ($list_course as $courses) {
 		$response = user_connected_in_chat($user_id,$courses['db_name']);
 		if ($response === true) {
 			$tbl_chat_connected = Database::get_course_chat_connected_table($courses['db_name']);
 			$sql = 'DELETE FROM '.$tbl_chat_connected.' WHERE user_id='.$user_id.$extra_condition;
 			Database::query($sql);
 		}
 	}
}

/**
 * @author isaac flores paz
 * @param integer the user id
 * @param string the database name
 * @return boolean
 */
function user_connected_in_chat ($user_id, $database_name) {
 	$tbl_chat_connected = Database::get_course_chat_connected_table($database_name);

 	$group_id 	= intval($_SESSION['id_group']);
	$session_id = intval($_SESSION['id_session']);
	$user_id 	= intval($user_id);
	$extra_condition = '';
	
	if (!empty($group_id)) {
		$extra_condition = " AND to_group_id = '$group_id'";
	} else {
		$extra_condition = api_get_session_condition($session_id);
	}

 	$sql = 'SELECT COUNT(*) AS count FROM '.$tbl_chat_connected .' c WHERE user_id='.$user_id.$extra_condition;
 	$result = Database::query($sql);
 	$count  = Database::fetch_array($result,'ASSOC');
 	return $count['count'] == 1;
}

/**
 * @param string $database_name (optional)
 * @return void
 */
function disconnect_user_of_chat($database_name = '') {

	$list_info_user_in_chat = array();

	if (!empty($database_name)) {
		$list_info_user_in_chat = users_list_in_chat($database_name);
	} else {
		$list_info_user_in_chat = users_list_in_chat();
	}

	$cd_date           = date('Y-m-d',time());
	$cdate_h           = date('H',time());
	$cdate_m           = date('i',time());
	$cdate_s           = date('s',time());
	$cd_count_time_seconds = $cdate_h*3600 + $cdate_m*60 + $cdate_s;

	if (is_array($list_info_user_in_chat) && count($list_info_user_in_chat) > 0 ) {
		foreach ($list_info_user_in_chat as $list_info_user) {
			$date_db_date = date('Y-m-d', strtotime($list_info_user['last_connection']));
			$date_db_h  = date('H', strtotime($list_info_user['last_connection']));
			$date_db_m  = date('i', strtotime($list_info_user['last_connection']));
			$date_db_s  = date('s', strtotime($list_info_user['last_connection']));
			$date_count_time_seconds=$date_db_h*3600 + $date_db_m*60 + $date_db_s;
			if ($cd_date == $date_db_date) {
				if (($cd_count_time_seconds - $date_count_time_seconds) > 5) {

					$tbl_chat_connected = Database::get_course_chat_connected_table();
					if (!empty($database_name))	{
						$tbl_chat_connected = Database::get_course_chat_connected_table($database_name);
					}

			 		$sql = 'DELETE FROM '.$tbl_chat_connected.' WHERE user_id='.$list_info_user['user_id'];
			 		Database::query($sql);
				}
			}
		}
	}
}

/**
 * @param string $database_name (optional)
 * @return array user list in chat
 */
function users_list_in_chat ($database_name = '') {
	$list_users_in_chat = array();
 	$tbl_chat_connected = Database::get_course_chat_connected_table($database_name);
 	$group_id = intval($_SESSION['id_group']);
	$session_id = intval($_SESSION['id_session']);
	$extra_condition = '';
	if (!empty($group_id)) {
		$extra_condition = " WHERE to_group_id = '$group_id'";
	} else{
		$extra_condition = api_get_session_condition($session_id, false);
	}
 	$sql = 'SELECT user_id,last_connection FROM '.$tbl_chat_connected.$extra_condition;
 	$result = Database::query($sql);
 	while ($row = Database::fetch_array($result, 'ASSOC')) {
 		$list_users_in_chat[] = $row;
 	}
 	return $list_users_in_chat;
}
