<?php

$cglobal = __DIR__.'/../../../../main/inc/global.inc.php';
if(file_exists($cglobal)){
	require_once __DIR__.'/../../../../main/inc/global.inc.php';
}else{
	$cglobal =  __DIR__.'/../../../../main/inc/global.inc.php';
	if(file_exists($cglobal)){
		require_once __DIR__.'/../../../../main/inc/global.inc.php';
	}else{
		exit;
	}
}

$folderCodeCourse = isset($_GET['f']) ? Security::remove_XSS($_GET['f']) : '';

if(!api_is_anonymous()){
	echo getCoursesProg2Json($folderCodeCourse);
}

function getCoursesProg2Json($folderCode){

	$progress = retrieveCoursesForJson($folderCode);
	$cnt = 0;

	$jsonlocal = '{"tools" :[';

		foreach ($progress as &$row){
			if($row['name']!=''){
				
				if($cnt>0){
					$jsonlocal .= ',';
				}
				
				$name = $row['name'];
				$lik = $row['link'];
				$type = '';
				
				if(strtolower($name)=='survey'){
					$name = get_lang('ToolSurvey');
				}
				if(strtolower($name)=='wiki'){
					$name = get_lang('ToolWiki');
				}
				if(strtolower($name)=='notebook'){
					$name = get_lang('ToolNotebook');
				}
				if(strtolower($name)=='glossary'){
					$name = get_lang('ToolGlossary');
				}
				if(strtolower($name)=='announcement'){
					$name = get_lang('ToolAnnouncement');
				}
				if(strtolower($name)=='link'){
					$name = get_lang('ToolLink');
				}
				if(strtolower($name)=='dropbox'){
					$name = get_lang('ToolDropbox');
				}
				
				if(strrpos($lik,'k/work.php')!=false){
					$type = 'studentpublication';
					if(strtolower($name)=='student_publication'){
						$name = get_lang('ToolStudentPublication');
					}
				}

				if(strrpos($lik,'description/index.php')!=false){
					$type = 'description';
					if(strtolower($name)=='course_description'){
						$name = get_lang('CourseDesc');
					}
				}
				if(strrpos($lik,'agenda.php')!=false){
					$type = 'agenda';
					if(strtolower($name)=="calendar_event"){
						$name = get_lang('ToolCalendarEvent');
					}
				}
				if(strrpos($lik,"exercise.php")!=false){
					$type = 'quiz';
					if(strtolower($name)=='quiz'){
						$name = get_lang('ToolQuiz');
					}
				}

				if(strrpos($lik,"document.php")!=false){
					$type = 'document';
					if(strtolower($name)=='document'){
						$name = get_lang('ToolDocument');
					}
				}

				if(strrpos($lik,"lp_controller.php")!=false){
					$type = 'learnpath';
					if(strtolower($name)=='learnpath'){
						$name = get_lang('ToolLearnpath');
					}
				}
				
				if(strrpos($lik,"radebook/")!=false){
					$type = 'gradebook';
					if(strtolower($name)=='gradebook'){
						$name = get_lang('Gradebook');
					}
				}

				if(strrpos($lik,"chat.php")!=false){
					$type = 'chat';
					if(strtolower($name)=='chat'){
						$name = get_lang('ToolChat');
					}
				}

				if(strrpos($lik,"user.php")!=false){
					$type = 'user';
					if(strtolower($name)=='chat'){
						$name = get_lang('ToolChat');
					}
				}

				if(strrpos($lik,"orum/index.php")!=false){
					$type = 'forum';
					if(strtolower($name)=='forum'){
						$name = get_lang('ToolForum');
					}
				}

				if(strrpos($lik,"group.php")!=false){
					$type = 'group';
					if(strtolower($name)=='group'){
						$name = get_lang('ToolGroup');
					}
				}
				
				$jsonlocal .= '{';
				$jsonlocal .= '"link": "'.urlencode($lik).'",';
				$jsonlocal .= '"name": "'.urlencode($name).'",';
				$jsonlocal .= '"type": "'.urlencode($type).'",';
				$jsonlocal .= '"folder": "'.$folderCode.'",';
				$jsonlocal .= '"image": "'.urlencode($row['image']).'"';
				$jsonlocal .= '}';

				$cnt = $cnt + 1;

			}
		}

		$jsonlocal .= ',{';
		$jsonlocal .= '"link": "stats",';
		$jsonlocal .= '"name": "'.getTimeSpentOnCourse($folderCode,$row['courseId']).'",';
		$jsonlocal .= '"type": "stats",';
		$jsonlocal .= '"folder": "'.$folderCode.'",';
		$jsonlocal .= '"image": "stats"';
		$jsonlocal .= '}';
	
	$jsonlocal .= ']}';

	return $jsonlocal;
	
}

function retrieveCoursesForJson($folderCode){
	
	$sql  = " SELECT c_tool.name, c_tool.link as link, c_tool.image, course.id as c_id";
	$sql .= " FROM c_tool ";
	$sql .= " INNER JOIN course ON c_tool.c_id = course.id ";
	$sql .= " AND course.directory LIKE '".$folderCode."' " ;
	$sql .= " WHERE c_tool.visibility = 1 ;" ;

	//FORMATEST
	$result = array();
	$resultset = Database::query($sql);
	while ($row = Database::fetch_array($resultset)){
		$lpid = $row['name'];
		$result[$lpid] = array(
			'name' => $row['name'],
			'link' => $row['link'],
			'image' => $row['image'],
			'courseId' => $row['c_id'],
		);
	}
	return $result;
}

function  getTimeSpentOnCourse($folderCode,$courseId){

	$user = api_get_user_info();
	if($user === NULL ){
		return api_time_to_hms(0);
	}
	$userId = $user['id'];
	$nbSeconds = 0;

	$table = Database::get_main_table(TABLE_STATISTIC_TRACK_E_COURSE_ACCESS);
	$sql = "SELECT
	SUM(UNIX_TIMESTAMP(logout_course_date) - UNIX_TIMESTAMP(login_course_date)) as nb_seconds
	FROM $table
	WHERE 
		UNIX_TIMESTAMP(logout_course_date) > UNIX_TIMESTAMP(login_course_date) AND 
		c_id = $courseId  AND user_id = $userId;";
	
	$rs = Database::query($sql);
	$row = Database::fetch_array($rs);

	$nbSeconds = $row['nb_seconds'];

	if($nbSeconds === NULL ||$nbSeconds==0) {
		$sql2 = "SELECT SUM(total_time) as nb_seconds  FROM `c_lp_item_view`
		INNER JOIN c_lp_item ON c_lp_item_view.lp_item_id = c_lp_item.id
		WHERE c_lp_item.c_id = $courseId AND lp_view_id IN (SELECT id FROM `c_lp_view` WHERE user_id = $userId) 
		GROUP BY c_lp_item.id";
		
		$rs2 = Database::query($sql2);
		$row2 = Database::fetch_array($rs2);    
		$nbSeconds = $row2['nb_seconds'];
	}
	
	if($nbSeconds === NULL ||$nbSeconds==0){
		$sql3 = "SELECT COUNT(*) as nb_seconds  FROM `track_e_attempt` where c_id = $courseId AND user_id = $userId";
		$rs3 = Database::query($sql3);
		$row3 = Database::fetch_array($rs3);    
		$nbSeconds = $row3['nb_seconds'];
		if($nbSeconds === NULL ||$nbSeconds==0) {
			$nbSeconds = 30;
		}else{
			$nbSeconds = $nbSeconds * 300;
		}
	}
	if($nbSeconds === NULL ||$nbSeconds==0) {
		$nbSeconds = 30;
	}

	$sqlMinDate = "SELECT MIN(login_course_date) as mindate
	FROM $table WHERE c_id = $courseId AND user_id = $userId;";
	
	$rsMinDate = Database::query($sqlMinDate);
	$rowMinDate = Database::fetch_array($rsMinDate);

	$minDate = $rowMinDate['mindate'];
	$dateminR =  new DateTime($minDate);

	return getDateNormalBoost($dateminR).'@'.api_time_to_hms($nbSeconds);

}

function getDateNormalBoost($date){

	$year = $date->format("Y");
	$month = $date->format('m');
	$day = $date->format('d');
	$dateReturn = $day.'-'.$month.'-'.$year;

	return $dateReturn;

}
