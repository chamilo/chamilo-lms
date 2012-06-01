<?php 

$reports_modules['scorm'] = array ();


function reports_modules_scorm_init() {
}

function reports_modules_scorm_init_forEachCourses($course_code, $course_id, $course_db) {
	global $reports_modules;
	
	$reports_modules_scorm_toolid = reports_getToolId(TOOL_LEARNPATH);

	// package level
	array_push($reports_modules['scorm'], 
	  array('keys_query' =>  
		'select '.$course_id.' as course_id, '.
		$reports_modules_scorm_toolid.' as tool_id, '.
		'lp.id as child_id, lp.name as child_name, '.
		"'".$course_db."'".' as course_db from '.
		Database::get_course_table(TABLE_LP_MAIN).' lp',
		'values_query_function' => 'reports_modules_scorm_packageVal'));		

	// sco level
	array_push($reports_modules['scorm'], 
	  array('keys_query' =>  
		'select '.$course_id.' as course_id, '.
		$reports_modules_scorm_toolid.' as tool_id, '.
		'lp.id as child_id, lp.name as child_name, '.
		'lpi.id as subchild_id, lpi.title as subchild_name, '.
		"'".$course_db."'".' as course_db from '.
		Database::get_course_table(TABLE_LP_MAIN, $course_db).
		' lp,'.
		Database::get_course_table(TABLE_LP_ITEM, $course_db).
		' lpi where lp.id = lpi.lp_id',
		'values_query_function' => 'reports_modules_scorm_scoVal'));		

	// objectives level
	array_push($reports_modules['scorm'], 
	  array('keys_query' =>  
		'select distinct '.$course_id.' as course_id, '.
		$reports_modules_scorm_toolid.' as tool_id, '.
		'lp.id as child_id, lp.name as child_name, '.
		'lpi.id as subchild_id, '.
		'lpi.title as subchild_name, '.
		'null as subsubchild_id, '.
		'lpivo.objective_id as subsubchild_name, '.
		"'".$course_db."'".' as course_db from '.
		Database::get_course_table(TABLE_LP_MAIN, $course_db).
		' lp,'.
		Database::get_course_table(TABLE_LP_ITEM, $course_db).
		' lpi, '.
		Database::get_course_table(TABLE_LP_ITEM_VIEW, $course_db).
		' lpiv, '.
		Database::get_course_table(TABLE_LP_IV_OBJECTIVE, $course_db).
		' lpivo '.
		'where lp.id = lpi.lp_id '.
		'and lpi.id = lpiv.lp_item_id '.
		'and lpiv.id = lpivo.lp_iv_id ',
		'values_query_function' => 'reports_modules_scorm_objVal'));		
}

function reports_modules_scorm_packageVal($scorm, $key_id) {
	return array('type'=> 'sql', 'sql' => 
			'select '.$key_id.', user_id as uid, '.
			'session_id, view_count as attempt, null as score, '.
			'progress as progress, '.
			'null as time, null as ts from '.
			Database::get_course_table(TABLE_LP_VIEW, $scorm['course_db']).
			' where lp_id = '.$scorm['child_id']);
}

function reports_modules_scorm_scoVal($scorm, $key_id) {
	return array('type'=> 'sql', 'sql' => 
			'select '.$key_id.', lpv.user_id as uid, '.
			'lpv.session_id, lpiv.view_count as attempt, '.
			'lpiv.score as score, '.
			'(case lpiv.status '.
				'when "incomplete" then 0 '.
				'when "completed" then 1 '.
				'when "passed" then 2 '.
				'when "failed" then 3 '.
				'when "browsed" then 4 '.
				'when "not attempted" then 5 '.
				'else 6 '.
			'end) as progress, '.
			'lpiv.total_time as time, null as ts from '.
			Database::get_course_table(TABLE_LP_VIEW, $scorm['course_db']).
			' lpv, '.
			Database::get_course_table(TABLE_LP_ITEM_VIEW, $scorm['course_db']).
			' lpiv '.
			' where lpv.lp_id = '.$scorm['child_id'].
			' and lpiv.lp_item_id = '.$scorm['subchild_id'].
			' and lpiv.lp_view_id = lpv.id');
}

function reports_modules_scorm_objVal($scorm, $key_id) {
	return array('type'=> 'sql', 'sql' => 
			'select '.$key_id.', lpv.user_id as uid, '.
			'lpv.session_id, lpiv.view_count as attempt, '.
			'lpivo.score_raw as score, '.
			'(case lpivo.status '.
				'when "incomplete" then 0 '.
				'when "completed" then 1 '.
				'when "passed" then 2 '.
				'when "failed" then 3 '.
				'when "browsed" then 4 '.
				'when "not attempted" then 5 '.
				'else 6 '.
			'end) as progress, '.
			'null as time, null as ts from '.
			Database::get_course_table(TABLE_LP_VIEW, $scorm['course_db']).
			' lpv, '.
			Database::get_course_table(TABLE_LP_ITEM_VIEW, $scorm['course_db']).
			' lpiv, '.
			Database::get_course_table(TABLE_LP_IV_OBJECTIVE, $scorm['course_db']).
			' lpivo '.
			' where lpv.lp_id = '.$scorm['child_id'].
			' and lpiv.lp_item_id = '.$scorm['subchild_id'].
			' and lpivo.objective_id = "'.$scorm['subsubchild_name'].'" '.
			' and lpiv.lp_view_id = lpv.id'.
			' and lpivo.lp_iv_id=lpiv.id');
}
