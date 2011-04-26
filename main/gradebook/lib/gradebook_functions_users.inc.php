<?php
/* For licensing terms, see /license.txt */
/**
 * Various user related functions
 * @author Julio Montoya <gugli100@gmail.com> adding security functions
 * @package chamilo.gradebook
 */
/**
 * returns users within a course given by param
 * @param $course_id
 */
function get_users_in_course($course_id) {
	$tbl_course_user 			= Database :: get_main_table(TABLE_MAIN_COURSE_USER);
	$tbl_session_course_user 	= Database :: get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
	$tbl_user 					= Database :: get_main_table(TABLE_MAIN_USER);

	$order_clause = api_sort_by_first_name() ? ' ORDER BY firstname, lastname ASC' : ' ORDER BY lastname, firstname ASC';

	$current_session = api_get_session_id();
	$course_id = Database::escape_string($course_id);

	if (!empty($current_session)) {
		$sql = "SELECT user.user_id, user.username, lastname, firstname, official_code
			 	FROM $tbl_session_course_user as scru, $tbl_user as user
			 	WHERE scru.id_user=user.user_id
			 	AND scru.status=0
			 	AND scru.course_code='$course_id' AND id_session ='$current_session' $order_clause ";
	} else {
		$sql = 'SELECT user.user_id, user.username, lastname, firstname, official_code'
			.' FROM '.$tbl_course_user.' as course_rel_user, '.$tbl_user.' as user'
			.' WHERE course_rel_user.user_id=user.user_id'
			.' AND course_rel_user.status='.STUDENT
			." AND course_rel_user.course_code='".$course_id."'"
			.$order_clause;
	}
	$result = Database::query($sql);
	return get_user_array_from_sql_result($result);
}


function get_user_array_from_sql_result($result) {
	$a_students = array();
	while ($user = Database::fetch_array($result)) {
		if (!array_key_exists($user['user_id'],$a_students)) {
			$a_current_student = array ();
			$a_current_student[] = $user['user_id'];
			$a_current_student[] = $user['username'];
			$a_current_student[] = $user['lastname'];
			$a_current_student[] = $user['firstname'];
			$a_current_student[] = $user['official_code'];
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
					.' WHERE res.evaluation_id = '.intval($eval->get_id())
					.' AND res.user_id = user.user_id';
			$result = Database::query($sql);
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
 * @author Bert Steppé
 */
function find_students($mask= '') {
	// students shouldn't be here // don't search if mask empty
	if (!api_is_allowed_to_create_course() || empty ($mask)) {
		return null;
	}
	$mask = Database::escape_string($mask);

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
		$sql .= ' AND user.user_id = cru.user_id AND cru.relation_type<>'.COURSE_RELATION_TYPE_RRHH.' ' . ' AND cru.course_code in' . ' (SELECT course_code' . ' FROM ' . $tbl_cru . ' WHERE user_id = ' . api_get_user_id() . ' AND status = ' . COURSEMANAGER . ')';
	}
	$sql .= ' ORDER BY lastname';
	$result= Database::query($sql);
	$db_users= Database::store_result($result);
	return $db_users;
}

/**
 * Get user information from a given id
 * @param int $userid The userid
 * @deprecated replace this function with the api_get_user_info()
 * @return array All user information as an associative array
 */
function get_user_info_from_id($userid) {
	$user_table= Database :: get_main_table(TABLE_MAIN_USER);
	$sql= 'SELECT * FROM ' . $user_table . ' WHERE user_id=' . intval($userid);
	$res= Database::query($sql);
	$user= Database::fetch_array($res,ASSOC);
	return $user;
}