<?php 

$reports_modules['quiz'] = array ();


function reports_modules_quiz_init() {
}

function reports_modules_quiz_init_forEachCourses($course_code, $course_id, $course_db) {
	global $reports_modules;
	
	$reports_modules_quiz_toolid = reports_getToolId(TOOL_QUIZ);

	array_push($reports_modules['quiz'], 
	  array('keys_query' =>  
		'select '.$course_id.' as course_id, '.
		$reports_modules_quiz_toolid.' as tool_id, '.
		'q.id as child_id, q.title as child_name, '.
		"'".$course_code."'".' as course_code from '.
		Database::get_course_table(TABLE_QUIZ_TEST, $course_db).
		' q',
		'values_query_function' => 'reports_modules_quiz_quizVal'));		
}

function reports_modules_quiz_quizVal($quiz, $key_id) {
	return array('type'=> 'sql', 'sql' => 
			'select '.$key_id.', exe_user_id as uid, '.
			'session_id, -1 as attempt, exe_result as score, '.
			REPORTS_PROGRESS_COMPLETED.' as progress, '.
			'exe_duration as time, exe_date as ts from '.
			Database::get_main_table(TABLE_STATISTIC_TRACK_E_EXERCICES).
			' where exe_cours_id = '."'".$quiz['course_code']."'".
			' and exe_exo_id='.$quiz['child_id']);
}
