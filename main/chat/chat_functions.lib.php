<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2004-2008 Dokeos SPRL
	Copyright (c) 2003 Ghent University (UGent)
	Copyright (c) 2001 Universite catholique de Louvain (UCL)
	Copyright (c) various contributors

	For a full list of contributors, see "credits.txt".
	The full license can be read in "license.txt".

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	See the GNU General Public License for more details.

	Contact address: Dokeos, rue du Corbeau, 108, B-1030 Brussels, Belgium
	Mail: info@dokeos.com
==============================================================================
*/
/**
 * @author isaac flores paz
 * @param integer
 * @return void
 */
function exit_of_chat ($user_id) {
	$list_course=array();
 	$list_course=CourseManager::get_courses_list_by_user_id($user_id);
 	foreach($list_course as $courses) {
 		$response=user_connected_in_chat($user_id,$courses['db_name']);
 		if ($response===true) {
 			$tbl_chat_connected = Database::get_course_chat_connected_table($courses['db_name']);;
 			$sql='DELETE FROM '.$tbl_chat_connected.' WHERE user_id='.$user_id;
 			api_sql_query($sql,__FILE__,__LINE__);
 		}
 	}

}
/**
 * @author isaac flores paz
 * @param integer the user id
 * @param string the database name
 * @return boolean
 */
function user_connected_in_chat ($user_id,$database_name) {
 	$tbl_chat_connected = Database::get_course_chat_connected_table($database_name);
 	$sql='SELECT COUNT(*) AS count FROM '.$tbl_chat_connected .' c WHERE user_id='.$user_id;
 	$result = api_sql_query($sql,__FILE__,__LINE__);
 	$count  = Database::fetch_array($result,'ASSOC');
 	if (1==$count['count']) {
 		return true;
 	} else {
 		return false;
 	}
}
/**
 * @param void
 * @return void
 */
function disconnect_user_of_chat () {
	$list_info_user_in_chat = array();
	$list_info_user_in_chat = users_list_in_chat ();

	$cd_date           = date('Y-m-d',time());
	$cdate_h           = date('H',time());
	$cdate_m           = date('i',time());
	$cdate_s           = date('s',time());
	$cd_count_time_seconds=$cdate_h*3600 + $cdate_m*60 + $cdate_s;

	foreach ($list_info_user_in_chat as $list_info_user) {
			$date_db_date = date('Y-m-d',strtotime($list_info_user['last_connection']));
			$date_db_h  = date('H',strtotime($list_info_user['last_connection']));
			$date_db_m  = date('i',strtotime($list_info_user['last_connection']));
			$date_db_s  = date('s',strtotime($list_info_user['last_connection']));
			$date_count_time_seconds=$date_db_h*3600 + $date_db_m*60 + $date_db_s;
			if ($cd_date==$date_db_date) {
				if (($cd_count_time_seconds - $date_count_time_seconds)>10) {
					$tbl_chat_connected = Database::get_course_chat_connected_table();
		 			$sql='DELETE FROM '.$tbl_chat_connected.' WHERE user_id='.$list_info_user['user_id'];
		 			api_sql_query($sql,__FILE__,__LINE__);
				}
			}


	}
}

/**
 * @param void
 * @return array user list in chat
 */
function users_list_in_chat () {
	$list_users_in_chat=array();
 	$tbl_chat_connected = Database::get_course_chat_connected_table();
 	$sql='SELECT user_id,last_connection FROM '.$tbl_chat_connected.' ;';
 	$result=api_sql_query($sql,__FILE__,__LINE__);
 	while ($row = Database::fetch_array($result,'ASSOC')) {
 		$list_users_in_chat[]=$row;
 	}
 	return $list_users_in_chat;
}
?>