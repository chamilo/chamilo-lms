<?php

/* For licensing terms, see /license.txt */

/**
 * Responses to AJAX calls.
 */

$cglobal = __DIR__.'/../../../../main/inc/global.inc.php';
if(file_exists($cglobal)){
	require_once __DIR__.'/../../../../main/inc/global.inc.php';
}else{
	$cglobal =  __DIR__.'/../../../../main/inc/global.inc.php';
	if(file_exists($cglobal)){
		require_once __DIR__.'/../../../../main/inc/global.inc.php';
	}else{
		echo '';
		exit;
	}
}

$action = $_GET['a'];

// Access restrictions.
//$is_allowedToTrack = api_is_platform_admin(true, true) ||
//api_is_allowed_to_create_course() || api_is_course_tutor();

//if (!$is_allowedToTrack) {
    //exit;
//}

function getUserIdB(){
	if(!api_is_anonymous()){
		$user = api_get_user_info();
		if(isset($user['id'])){
			return $user['id'];
		}
	}
	return '';
}

switch($action){

    case 'lp_global_report':

        $userId = getUserIdB();

        if (empty($userId)) {
            exit;
        }

        $cacheAvailable = api_get_configuration_value('apc');
        $table = null;
        $variable = 'lp_global_report_'.$userId;
        if ($cacheAvailable) {
            if (apcu_exists($variable)) {
                $table = apcu_fetch($variable);
            }
        }

        if (!empty($table)) {
            echo $table;
            exit;
        }

        $sessionCategoryList = UserManager::get_sessions_by_category($userId, false);
        $total = 0;
        $totalAverage = 0;
        $table = new HTML_Table(['class' => 'data_table']);
        $row = 0;
        $col = 0;
        foreach ($sessionCategoryList as $category) {
            $sessionList = $category['sessions'];
            foreach ($sessionList as $session) {
                $courses = $session['courses'];
                $sessionId = $session['session_id'];
                $session['session_name'];
                $totalCourse = 0;
                $totalSessionAverage = 0;
                foreach ($courses as &$course) {
                    $average = Tracking::get_avg_student_progress($userId, $course['course_code'], [], $sessionId);
                    $totalSessionAverage += $average;
                    $totalCourse++;
                    if (false !== $average) {
                        $average = $average.' %';
                    }
                    $course['average'] = $average;
                }

                $total++;
                $totalSessionAverage = round($totalSessionAverage / count($courses), 2);
                $totalAverage += $totalSessionAverage;

                $row++;
                $table->setCellContents($row, 0, $session['session_name']);
                $table->setCellContents($row, 1, $totalSessionAverage.' %');
                $table->setCellContents($row, 2, '');
                $row++;
                foreach ($courses as &$course) {
                    $table->setCellContents($row, 0, $session['session_name']);

                    if(!isset($course['title'])){
                        //print_r($course);
                        $course['title'] = $course['course_code'];
                    }

                    $table->setCellContents($row, 1, $course['title']);
                    $table->setCellContents($row, 2, $course['average']);
                    $row++;
                }
            }
        }

        $table->setCellContents(0, 0, get_lang('Global'));
        
        if($totalAverage>0||$total>0){
            $table->setCellContents(0,1,round($totalAverage / $total, 2).' %');
        }else{
            $table->setCellContents(0,1,'0 %');
        }
        
        $table->setCellAttributes(0,1, ['id' => 'globalscoreboost', 'style' => 'font-weight:bold']);

        $result = $table->toHtml();

        if($cacheAvailable){
            apcu_store($variable, $result, 60);
        }

        echo $result;

        break;
}
exit;