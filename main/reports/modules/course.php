<?php 

$reports_modules['course'] = array ();


function reports_modules_course_init() {
}

function reports_modules_course_init_forEachCourses($course_code, $course_id, $course_db) {
	global $reports_modules;
	
//	$reports_modules_course_toolid = reports_getToolId(TOOL_QUIZ);

	array_push($reports_modules['course'], 
	  array('keys_query' =>  
		'select '.$course_id.' as course_id, "'.$course_code.'" as course_code',
		'values_query_function' => 'reports_modules_course_val'));		
}

function reports_modules_course_val($course, $key_id) {
	return array('type'=> 'sql', 'sql' => 
			'select '.$key_id.', user_id as uid, '.
			'-1 as session_id, -1 as attempt, null as score, '.
			'NULL as progress, '.
			'(sum(logout_course_date) - sum(login_course_date)) as time, null as ts from '.
			Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS).
			' where course_code = '."'".$course['course_code']."'".
			' group by user_id');
}
