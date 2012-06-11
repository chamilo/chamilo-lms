<?php
/* For licensing terms, see /license.txt */
/**
 *	@package chamilo.chat
 */
 

/**
 * @author isaac flores paz
 * @param integer the user id
 * @param string the database name
 * @return boolean
 */
function user_connected_in_chat ($user_id) {
 	$tbl_chat_connected = Database::get_course_table(TABLE_CHAT_CONNECTED);

 	$session_id = api_get_session_id();
    $group_id   = api_get_group_id();	
    
	$user_id 	= intval($user_id);
    $course_id  = api_get_course_int_id();
    
	$extra_condition = '';
	
	if (!empty($group_id)) {
		$extra_condition = " AND to_group_id = '$group_id'";
	} else {
		$extra_condition = api_get_session_condition($session_id);
	}

 	$sql = 'SELECT COUNT(*) AS count FROM '.$tbl_chat_connected .' c WHERE c_id = '.$course_id.' AND user_id='.$user_id.$extra_condition;
 	$result = Database::query($sql);
 	$count  = Database::fetch_array($result,'ASSOC');
 	return $count['count'] == 1;
}

/**
 * @param integer
 * @return void
 */
function exit_of_chat($user_id) {
	$user_id = intval($user_id);
    $course_id = api_get_course_int_id();
	$list_course = array();
 	$list_course = CourseManager::get_courses_list_by_user_id($user_id);
    
    $session_id = api_get_session_id();
    $group_id   = api_get_group_id();	
	
	$extra_condition = '';
	if (!empty($group_id)) {
		$extra_condition = " AND to_group_id = '$group_id'";
	} else {
		$extra_condition = api_get_session_condition($session_id);
	}
    $extra_condition.= " AND course_id = $course_id";
    $tbl_chat_connected = Database::get_course_table(TABLE_CHAT_CONNECTED);
    
 	foreach ($list_course as $course) {
 		$response = user_connected_in_chat($user_id);
 		if ($response === true) { 			
 			$sql = 'DELETE FROM '.$tbl_chat_connected.' WHERE c_id = '.$course['real_id'].' AND user_id='.$user_id.$extra_condition;
 			Database::query($sql);
 		}
 	}
}

/**
 * @param string $database_name (optional)
 * @return void
 */
function disconnect_user_of_chat() {
	$list_info_user_in_chat = array();
    $course_id = api_get_course_int_id();
    $list_info_user_in_chat = users_list_in_chat();
    $course_id = api_get_course_int_id();
    
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
                    $tbl_chat_connected = Database::get_course_table(TABLE_CHAT_CONNECTED);					
			 		$sql = 'DELETE FROM '.$tbl_chat_connected.' WHERE c_id = '.$course_id.' AND user_id ='.$list_info_user['user_id'];
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
function users_list_in_chat() {
	$list_users_in_chat = array();
 	$tbl_chat_connected = Database::get_course_table(TABLE_CHAT_CONNECTED);
    $course_id = api_get_course_int_id();
    
 	$session_id = api_get_session_id();
    $group_id   = api_get_group_id();
    
	$extra_condition = '';
	if (!empty($group_id)) {
		$extra_condition = " WHERE to_group_id = '$group_id'";
	} else{
		$extra_condition = api_get_session_condition($session_id, false);
	}
    $extra_condition.= " AND c_id = $course_id ";
 	$sql = 'SELECT user_id, last_connection FROM '.$tbl_chat_connected.$extra_condition;
 	$result = Database::query($sql);
 	while ($row = Database::fetch_array($result, 'ASSOC')) {
 		$list_users_in_chat[] = $row;
 	}
 	return $list_users_in_chat;
}