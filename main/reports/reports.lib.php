<?php

require_once '../inc/global.inc.php';


define ('REPORTS_PROGRESS_COMPLETED', 1);

$reports_modules = array();

$reports_enabled_modules = array('quiz','course','scorm');

$reports_enabled_templates = array('exercicesMultiCourses', 'courseTime');


// load templates 
function reports_loadTemplates() {
	global $reports_enabled_templates, $reports_template;
	foreach ($reports_enabled_templates as $t)
		require_once 'templates/'.$t.'.reports.php';
}


// clear all reporting data
function reports_clearAll() {
/*
	Database::query('DELETE FROM '.Database::get_main_table(TABLE_MAIN_REPORTS_KEYS));
	Database::query('DELETE FROM '.Database::get_main_table(TABLE_MAIN_REPORTS_VALUES));
*/	
	Database::query('DROP TABLE '.Database::get_main_table(TABLE_MAIN_REPORTS_KEYS));
	Database::query('DROP TABLE '.Database::get_main_table(TABLE_MAIN_REPORTS_VALUES));
	Database::query('
CREATE TABLE '.Database::get_main_table(TABLE_MAIN_REPORTS_KEYS).' (
  id int unsigned NOT NULL AUTO_INCREMENT primary key,
  course_id int unsigned DEFAULT NULL,
  tool_id int unsigned DEFAULT NULL,
  child_id int unsigned DEFAULT NULL,
  child_name varchar(64) DEFAULT NULL,
  subchild_id int unsigned DEFAULT NULL,
  subchild_name varchar(64) DEFAULT NULL,
  subsubchild_id int unsigned DEFAULT NULL,
  subsubchild_name varchar(64) DEFAULT NULL,
  link varchar(256) DEFAULT NULL)');

	Database::query('
CREATE TABLE '.Database::get_main_table(TABLE_MAIN_REPORTS_VALUES).' (
  key_id int unsigned NOT NULL,
  user_id int unsigned NOT NULL,
  session_id int unsigned NOT NULL,
  attempt int NOT NULL,
  score decimal(5,3) DEFAULT NULL,
  progress int DEFAULT NULL,
  report_time int DEFAULT NULL,
  ts datetime DEFAULT NULL)');
}

function reports_addDBKeys() {
	// cannot had this primary key here due to mysql restrction on auto_increment
//	Database::query('alter table '.Database::get_main_table(TABLE_MAIN_REPORTS_KEYS).
//		' primary key(id)');
	Database::query('alter ignore table '.
		Database::get_main_table(TABLE_MAIN_REPORTS_KEYS).
		' add index(course_id)');
	Database::query('alter table '.
		Database::get_main_table(TABLE_MAIN_REPORTS_KEYS).
		' add index(course_id,tool_id,child_id,subchild_id,subsubchild_id)');
	Database::query('alter ignore table '.
		Database::get_main_table(TABLE_MAIN_REPORTS_VALUES).
		' add index(user_id)');
	Database::query('alter ignore table '.
		Database::get_main_table(TABLE_MAIN_REPORTS_VALUES).
		' add primary key(key_id,user_id,session_id,attempt)');
}

// build all reporting data
function reports_build() {
	global $reports_enabled_modules, $reports_modules;

	// include + init
	foreach($reports_enabled_modules as $module) {
		require_once('modules/'.$module.'.php');
		$initFunc = 'reports_modules_'.$module.'_init';
		
		$initFunc();
	}
	
	// init For Each Courses
	foreach($reports_enabled_modules as $module) {
		$initFuncFEC = 'reports_modules_'.$module.'_init_forEachCourses';
		foreach(CourseManager::get_courses_list() as $course)
			$initFuncFEC($course['code'], $course['id'], 
				$course['db_name']);
	}			

	// fetch data
	foreach($reports_enabled_modules as $module) 
		foreach ($reports_modules[$module] as $keys)
			reports_automaticAdd($keys['keys_query'], 
				$keys['values_query_function']);
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
		($link == '' ? 'NULL' : "'$link'").')');
	return Database::insert_id();
}

// add a value
function reports_addValue($key, $session, $uid, $attempt, $score, 
			  $progress, $time, $ts) {
	Database::query('INSERT into '.
		Database::get_main_table(TABLE_MAIN_REPORTS_VALUES).
		' (key_id, user_id, session_id, attempt, score, '.
		'progress, report_time) values ('.$key.', '. 
		$uid.', '.
// -1 instead of null because of primary key limitation with null column
		($session == '' ? '-1' : $session).', '.
		($attempt == '' ? '-1' : $attempt).', '.
		($score == '' ? 'NULL' : $score).', '.
		($progress == '' ? 'NULL' : $progress).', '.
		($time == '' ? 'NULL' : $time).', '.
		($ts == '' ? 'NULL' : $ts).')');
}

// add a value using a sub query warning take care about the order of the fields
function reports_addValueQuery($query) {
	Database::query('INSERT into '.
		Database::get_main_table(TABLE_MAIN_REPORTS_VALUES).
		' (key_id, user_id, session_id, attempt, score, '.
		'progress, report_time, ts) ('.$query.')');
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

// this function execute keys_query (SQL statement)
// each rows may returns following fields course_id, tool_id, child_id, 
//  child_name, subchild_id, subchild_name, subsubchild_id, subsubchild_name,
//  link
// row may contains other fields.
// rows are parsed using fetch_assoc
// assoc array are then given to values_query_function
// this function must return a assoc array
// return["type"] should be either 'static' or 'sql'
// return["static"] should contains an array of assoc array which may
// includes following headers: session, user_id, attempt, score progress and
//  time.
// return["sql"] (when type==sql) an sql query returning the same fields.
//  this sql stateuement MUST include a field key_id with the value given
//  to the function as parametre. This statement will be passed to 
//  reports_addValueQuery
function reports_automaticAdd($keys_query, $values_query_function) {
	$keys_result = Database::query($keys_query);
	if (!$keys_query) {
		echo 'folowwing keys_query failed: '.$keys_query."\n";
		return;
	}
	$num = Database::num_rows($keys_result);
	for ($i = 0; $i < $num; $i++) { 
		$keys = Database::fetch_assoc($keys_result);
		$key_id = reports_addKey(
		  array_key_exists('course_id', $keys) ? $keys['course_id'] : '',
		  array_key_exists('tool_id', $keys) ? $keys['tool_id'] : '',
		  array_key_exists('child_id', $keys) ? $keys['child_id'] : '',
		  array_key_exists('child_name', $keys) ? $keys['child_name'] : '',
		  array_key_exists('subchild_id', $keys) ? $keys['subchild_id'] : '',
		  array_key_exists('subchild_name', $keys) ? $keys['subchild_name'] : '',
		  array_key_exists('subsubchild_id', $keys) ? $keys['subsubchild_id'] : '',
		  array_key_exists('subsubchild_name', $keys) ? $keys['subsubchild_name'] : '',
		  array_key_exists('link', $keys) ? $keys['link'] : '');
		$values = $values_query_function($keys, $key_id);
		if ($values['type'] == 'static')
			for ($j = 0; $j<sizeof($values['static']); $j++)
				reports_addValue($key_id,
				  array_key_exists('session', $values['static'][$j]) ? $values['static'][$j]['session'] : '',
				  array_key_exists('user_id', $values['static'][$j]) ? $values['static'][$j]['user_id'] : '',
				  array_key_exists('attempt', $values['static'][$j]) ? $values['static'][$j]['attempt'] : '',
				  array_key_exists('score', $values['static'][$j]) ? $values['static'][$j]['score'] : '',
				  array_key_exists('progress', $values['static'][$j]) ? $values['static'][$j]['progress'] : '',
				  array_key_exists('report_time', $values['static'][$j]) ? $values['static'][$j]['report_time'] : '',
				  array_key_exists('ts', $values['static'][$j]) ? $values['static'][$j]['ts'] : '');
		else
			reports_addValueQuery($values['sql']);
	}
}
