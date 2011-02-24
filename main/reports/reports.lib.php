<?php

require_once '../inc/global.inc.php';

// FIXME chamilo upgrade table creation

// clear all reporting data
function reports_clearAll() {
	Database::query('DELETE FROM '.Database::get_main_table(TABLE_MAIN_REPORTS_KEYS));
	Database::query('DELETE FROM '.Database::get_main_table(TABLE_MAIN_REPORTS_VALUES));
}

// build all reporting data
function reports_build() {
	// FIXME
}

// add a key and returns his id
// field are not checked for insertion since this function is for internal
// use only
function reports_addKey($course_id, $tool_id,
			$child_id, $child_name,
			$subchild_id, $subchild_name,
			$subsubchild_id, $subsubchild_name,
			$link) {
	Database::query('INSERT into '.
		Database::get_main_table(TABLE_MAIN_REPORTS_KEYS).
		' (id, course_id, tool_id, child_id, child_name, '.
		'subchild_id, subchild_name, subsubchild_id, subsubchild_name,'.
		'link ) values (null, '.
		($course_id == '' ? 'NULL' :$course_id).', '.
		($tool_id == '' ? 'NULL' :$tool_id).', '.
		($child_id == '' ? 'NULL' :$child_id).', '.
		($child_name == '' ? 'NULL' :"'$child_name'").', '.
		($subchild_id == '' ? 'NULL' :$subchild_id).', '.
		($subchild_name == '' ? 'NULL' : "'$subchild_name'").', '.
		($subsubchild_id == '' ? 'NULL' : $subsubchild_id).', '.
		($subsubchild_name == '' ? 'NULL' : "'$subsubchild_name'").', '.
		($link == '' ? 'NULL' : "'$lin'").')');
	return Database::insert_id();
}

// add a value
function reports_addValue($key, $session, $uid, $attempt, $score, 
			  $progress, $time) {
	Database::query('INSERT into '.
		Database::get_main_table(TABLE_MAIN_REPORTS_VALUES).
		' (key_id, uid, session_id, attempt, score, '.
		'progress, time) values ('.$key.', '. 
		$uid.', '.
// -1 instead of null because of primary key limitation with null column
		($session == '' ? '-1' : $session).', '.
		($attempt == '' ? '-1' : $attempt).', '.
		($score == '' ? 'NULL' : $score).', '.
		($progress == '' ? 'NULL' : $progress).', '.
		($time == '' ? 'NULL' : $time).')');
}

// return tools ID (parametre is a constant from main_api
function reports_getToolId($tool) {
	$tools = array_flip(api_get_tools_lists());
	if (array_key_exists($tool, $tools))
		return $tools[$tool];
	else
		return null;
}

// return a sql clause returning triplet of (course, $session, $uid) the
// current user is authorized to reed
function reports_getVisibilitySQL () {
	return "select cru.user_id, c.id, null from course c, course_rel_user cru where cru.course_code = c.code";
	// fixme sessions
}

