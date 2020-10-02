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
		echo '{"data" :[]}';
		exit;
	}
}

$userId = getUserIdB();

$cacheAvailable = api_get_configuration_value('apc');

$cluetime = intVal(intVal(time()/60)/5);
$variable = 'lp_global_report_boost_'.$userId;

if ($cacheAvailable) {
    if (apcu_exists($variable)) {
        $progressJson = apcu_fetch($variable);
        echo $progressJsonlocal;
        exit;
    }
}else{
    
    if(isset($_SESSION[$variable.$cluetime])){
        echo $_SESSION[$variable.$cluetime];
        exit;
    }

}

$progress = getSessionProgress($userId);

$jsonlocal = '{"data" :[';
$cnt = 0;

foreach ($progress as &$row){

    if($cnt>0){
        $jsonlocal .= ',';
    }

    $titleJson = $row['title'];
    $titleJson = json_encode($titleJson);

    $jsonlocal .= '{';
    $jsonlocal .= '"type": "'.$row['type'].'",';
    $jsonlocal .= '"title": '.$titleJson.',';
    $jsonlocal .= '"ref":  "'.$row['ref'].'",';
    $jsonlocal .= '"score": "'.$row['score'].'"';
    $jsonlocal .= '}';

    $cnt++;
}

$jsonlocal .= ']}';

if($cacheAvailable){
    apcu_store($variable, $jsonlocal,120);
}else{
    $_SESSION[$variable.$cluetime] = (string)$jsonlocal;
}

echo $jsonlocal;

function getSessionProgress($userId){

    if (empty($userId)) {
       return array();
    }

    $result = array();

    $sessionCategoryList = UserManager::get_sessions_by_category($userId, false);
    $total = 0;
    $totalAverage = 0;
    $index = 0;

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

            $result[$index] = array(
                'type' => 'session',
                'title' => $session['session_name'],
                'score' => $totalSessionAverage.' %',
                'ref' =>''
            );
            
            $index++;

            foreach ($courses as &$course){

                if(!isset($course['title'])){
                    $course['title'] = $course['course_code'];
                }

                $result[$index] = array(
                    'type' => 'session_course',
                    'title' =>  $course['title'],
                    'score' => $course['average'],
                    'ref' => $session['session_name']
                );
                $index++;

            }
        }
    }
    
    $globalAverage = '0%';
    if($totalAverage>0||$total>0){
        $globalAverage = round($totalAverage / $total, 2).' %';
    }
    
    $result[$index] = array(
        'type' => 'Global',
        'title' =>  get_lang('Global'),
        'score' => $globalAverage,
        'ref' =>''
    );
    $index++;

    return $result;

}

function getUserIdB(){
	if(!api_is_anonymous()){
		$user = api_get_user_info();
		if(isset($user['id'])){
			return $user['id'];
		}
	}
	return '';
}