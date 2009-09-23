<?php
/*
==============================================================================
	Dokeos - elearning and course management software

	Copyright (c) 2008 Dokeos Latinoamerica SAC
	Copyright (c) 2006 Dokeos SPRL
	Copyright (c) 2006 Ghent University (UGent)
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
 * Various user related functions
 * @package dokeos.gradebook
 */
/**
 * returns users within a course given by param
 * @param $course_id
 */
function get_users_in_course($course_id) {
	$tbl_course_user = Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);

	$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname ASC' : ' ORDER BY lastname, firstname ASC';
	$sql = 'SELECT user.user_id,lastname,firstname'
			.' FROM '.$tbl_course_user.' as course_rel_user, '.$tbl_user.' as user'
			.' WHERE course_rel_user.user_id=user.user_id'
			.' AND course_rel_user.status='.STUDENT
			." AND course_rel_user.course_code='".$course_id."'"
			.$order_clause;
	$result = api_sql_query($sql, __FILE__, __LINE__);
	return get_user_array_from_sql_result($result);
}


function get_user_array_from_sql_result($result) {
	$a_students = array();
	while ($user = Database::fetch_array($result)) {
		if (!array_key_exists($user['user_id'],$a_students)) {
			$a_current_student = array ();
			$a_current_student[] = $user['user_id'];
			$a_current_student[] = $user['lastname'];
			$a_current_student[] = $user['firstname'];
			$a_students['STUD'.$user['user_id']] = $a_current_student;
		}
	}
	return $a_students;
}

function get_all_users ($evals = array(), $links = array()) {
	$coursecodes = array();
	$users = array();

	foreach ($evals as $eval) {
		$coursecode = $eval->get_course_code();
		// evaluation in course
		if (isset($coursecode) && !empty($coursecode)) {
			if (!array_key_exists($coursecode,$coursecodes)) {
				$coursecodes[$coursecode] = '1';
				$users = array_merge($users, get_users_in_course($coursecode));
			}
		} else {// course independent evaluation
			$tbl_user = Database :: get_main_table(TABLE_MAIN_USER);
			$tbl_res = Database :: get_main_table(TABLE_MAIN_GRADEBOOK_RESULT);

			$sql = 'SELECT user.user_id,lastname,firstname'
					.' FROM '.$tbl_res.' as res, '.$tbl_user.' as user'
					.' WHERE res.evaluation_id = '.$eval->get_id()
					.' AND res.user_id = user.user_id';
			$result = api_sql_query($sql, __FILE__, __LINE__);
			$users = array_merge($users,get_user_array_from_sql_result($result));
		}
	}

	foreach ($links as $link) {
		// links are always in a course
		$coursecode = $link->get_course_code();
		if (!array_key_exists($coursecode,$coursecodes)) {
			$coursecodes[$coursecode] = '1';
			$users = array_merge($users, get_users_in_course($coursecode));
		}
	}
	unset ($coursecodes);
	return $users;
}

/**
 * Search students matching a given last name and/or first name
 * @author Bert Stepp�
 */
function find_students($mask= '') {
	// students shouldn't be here // don't search if mask empty
	if (!api_is_allowed_to_create_course() || empty ($mask)) {
		return null;
	}
	$tbl_user= Database :: get_main_table(TABLE_MAIN_USER);
	$tbl_cru= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$sql= 'SELECT DISTINCT user.user_id, user.lastname, user.firstname, user.email' . ' FROM ' . $tbl_user . ' user';
	if (!api_is_platform_admin()) {
		$sql .= ', ' . $tbl_cru . ' cru';
	}

	$sql .= ' WHERE user.status = ' . STUDENT;
	$sql .= ' AND (user.lastname LIKE '."'%" . $mask . "%'";
	$sql .= ' OR user.firstname LIKE '."'%" . $mask . "%')";

	if (!api_is_platform_admin()) {
		$sql .= ' AND user.user_id = cru.user_id' . ' AND cru.course_code in' . ' (SELECT course_code' . ' FROM ' . $tbl_cru . ' WHERE user_id = ' . api_get_user_id() . ' AND status = ' . COURSEMANAGER . ')';
	}
	$sql .= ' ORDER BY lastname';
	$result= api_sql_query($sql, __FILE__, __LINE__);
	$db_users= api_store_result($result);
	return $db_users;
}

/**
 * Get user information from a given id
 * @param int $userid The userid
 * @return array All user information as an associative array
 */
function get_user_info_from_id($userid) {
	$user_table= Database :: get_main_table(TABLE_MAIN_USER);
	$sql= 'SELECT * FROM ' . $user_table . ' WHERE user_id=' . $userid;
	$res= api_sql_query($sql, __FILE__, __LINE__);
	$user= Database::fetch_array($res,ASSOC);
	return $user;
}