<?php

function retrieveParcoursForJson($iduser){
	
	/*
	SELECT course.title as title, course.code as code, course.directory as directory,
	'course' as type , session_rel_course.id as sessionid 
	FROM course 
	INNER JOIN course_rel_user ON course_rel_user.c_id = course.id 
	LEFT JOIN session_rel_course ON session_rel_course.c_id = course.id
	AND course_rel_user.user_id = 1 GROUP BY course.code ORDER BY title
	*/

	$sql = " SELECT course.title as title, course.code as code, course.directory as directory,";
	$sql .= "'course' as type , session_rel_course.session_id as sessionid ";
	$sql .= "FROM course ";
	$sql .= "INNER JOIN course_rel_user ON course_rel_user.c_id = course.id ";
	$sql .= "LEFT JOIN session_rel_course ON session_rel_course.c_id = course.id ";
	$sql .= "WHERE course_rel_user.user_id = $iduser  GROUP BY course.code ORDER BY title";
	
	$result = array();

	$resultset = Database::query($sql);
	
	while ($row = Database::fetch_array($resultset)){

		$lpid = $row['code'];

		$result[$lpid] = array(
			'title' => $row['title'],
			'code' => $row['code'],
			'directory' => $row['directory'],
			'sessionid' => $row['sessionid'],
			'type' => $row['type']
		);

	}

	$sqlSe = " SELECT course.title as title, course.code as code, course.directory as directory,";
	$sqlSe .= "'course' as type , session_rel_course.session_id as sessionid FROM course ";
	$sqlSe .= "INNER JOIN session_rel_course ON session_rel_course.c_id = course.id ";
	$sqlSe .= "INNER JOIN session_rel_user ON session_rel_user.session_id = session_rel_course.session_id ";
	$sqlSe .= "WHERE session_rel_user.user_id = $iduser  GROUP BY course.code ORDER BY title";


	$resultsetR = Database::query($sqlSe);
	
	while ($rowR = Database::fetch_array($resultsetR)){

		$lpid = $rowR['code'];

		$result[$lpid] = array(
			'title' => $rowR['title'],
			'code' => $rowR['code'],
			'directory' => $rowR['directory'],
			'sessionid' => $rowR['sessionid'],
			'type' => $rowR['type']
		);

	}
	
	$sql = "SELECT session.name as title, CONCAT('SESSION-',session.id) as code, session.id as directory,";
	$sql .= "'session' as type FROM session ";
	$sql .= "INNER JOIN session_rel_user ON session_rel_user.session_id = session.id ";
	$sql .= "AND session_rel_user.user_id = $iduser ";
	
	$resultset = Database::query($sql);
	
	while ($row = Database::fetch_array($resultset)){

		$lpid = $row['code'];

		$result[$lpid] = array(
			'title' => $row['title'],
			'code' => $row['code'],
			'directory' => $row['directory'],
			'sessionid' => $row['directory'],
			'type' => $row['type']
		);

	}
	
	$sql = "SELECT session.name as title,";
	$sql .= "CONCAT('SESSION-',session.id) as code,";
	$sql .= "session.id as directory,";
	$sql .= "'session' as type ";
	$sql .= "FROM session ";
	$sql .= "INNER JOIN usergroup_rel_session ON usergroup_rel_session.session_id = session.id ";
	$sql .= "INNER JOIN usergroup_rel_user ON usergroup_rel_user.usergroup_id = usergroup_rel_session.usergroup_id ";
	$sql .= "AND usergroup_rel_user.user_id = $iduser ";
	
	$resultset2 = Database::query($sql);
	
	while ($row2 = Database::fetch_array($resultset2)){

		$lpid = $row2['code'];

		$result[$lpid] = array(
			'title' => $row2['title'],
			'code' => $row2['code'],
			'directory' => $row2['directory'],
			'sessionid' => $row2['directory'],
			'type' => $row2['type']
		);

	}
	
	return $result;

}

function getUserProg2Json($idu,$interface){

	$progress = retrieveParcoursForJson($idu);

	$_SESSION['CoursesProgressList'] = $progress;
	if(isset($_SESSION['RenderMenuBoost'])){
		unset($_SESSION['RenderMenuBoost']);
	}
	
	$cnt = 0;

	$jsonlocal = '{"modules" :[';

		foreach ($progress as &$row){

			if ($row['code']!=''){

				if($cnt>0){
					$jsonlocal .= ',';
				}
				
				if($row['sessionid']==null||$row['sessionid']==''){
					$row['sessionid'] = 0;
				}

				$jsonlocal .= '{';
				$jsonlocal .= '"code": "'.$row['code'].'",';
				$jsonlocal .= '"type": "'.$row['type'].'",';
				
				if($row['type']=='session'){
					$jsonlocal .= '"idref": "'.$row['directory'].'",';
					$jsonlocal .= '"sessionid": "'.$row['sessionid'].'",';
					$jsonlocal .= '"img": "session.jpg",';
				}else{
					$jsonlocal .= '"idref": "'.$row['directory'].'",';
					$jsonlocal .= '"sessionid": "'.$row['sessionid'].'",';
					$pathExtraImg = __DIR__.'/../resources/templates/'.$interface.'/'.'animated/'.$row['directory'].'.gif';
					if(file_exists($pathExtraImg)){
						$jsonlocal .= '"img": "templates/'.$interface.'/'.'animated/'.$row['directory'].'.gif",';
					}else{
						$jsonlocal .= '"img": "courses/'.$row['directory'].'/course-pic.png",';
					}
					
				}

				$jsonlocal .= '"title": '.json_encode($row['title']);
				$jsonlocal .= '}';
				
				$cnt = $cnt + 1;

			}

		}
	$jsonlocal .= ']}';
	return $jsonlocal;
	
}

