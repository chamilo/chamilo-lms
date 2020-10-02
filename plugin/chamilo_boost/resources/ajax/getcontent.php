<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
$cglobal = __DIR__.'/../../../../main/inc/global.inc.php';
if(file_exists($cglobal)){
	require_once __DIR__.'/../../../../main/inc/global.inc.php';
}else{
	$cglobal =  __DIR__.'/../../../../main/inc/global.inc.php';
	if(file_exists($cglobal)){
		require_once __DIR__.'/../../../../main/inc/global.inc.php';
	}else{
		$jsonlocal = '{"modules" :[';
		$jsonlocal .= '{';
		$jsonlocal .= '"code": "",';
		$jsonlocal .= '"img": "",';
		$jsonlocal .= '"title":"Error"';
		$jsonlocal .= '}';
		$jsonlocal .= ']}';
		echo $jsonlocal;		
		exit;
	}
}
function getUserIdB(){
	if(!api_is_anonymous()){
		$user = api_get_user_info();
		if(isset($user['id'])){
			return $user['id'];
		}
	}
	return -1;
}
function retrieveParcours($iduser){
$sql = <<<EOT
	SELECT
	course.title as title,
	course.code as code,
	course.directory as directory,
	'course' as type
	FROM course
	INNER JOIN course_rel_user ON course_rel_user.c_id = course.id
	AND course_rel_user.user_id = $iduser
EOT;
	$result = array();
	$resultset = Database::query($sql);
	while ($row = Database::fetch_array($resultset)){
		$lpid = $row['code'];
		$result[$lpid] = array(
			'title' => $row['title'],
			'code' => $row['code'],
			'directory' => $row['directory'],
			'type' => $row['type']
		);
	}
$sql = <<<EOT
	SELECT
	session.name as title,
	CONCAT('SESSION-',session.id) as code,
	session.id as directory,
	'session' as type
	FROM session
	INNER JOIN session_rel_user ON session_rel_user.session_id = session.id
	AND session_rel_user.user_id = $iduser
EOT;
	$resultset = Database::query($sql);
	while ($row = Database::fetch_array($resultset)){
		$lpid = $row['code'];
		$result[$lpid] = array(
			'title' => $row['title'],
			'code' => $row['code'],
			'directory' => $row['directory'],
			'type' => $row['type']
		);
	}
	return $result;
}
$idu = getUserIdB();
$progress = retrieveParcours($idu);
$cnt = 0;
$jsonlocal = '{"modules" :[';
	foreach ($progress as &$row){
		if ($row['code']!=''){
			if($cnt>0){
				$jsonlocal .= ',';
			}
			$jsonlocal .= '{';
			$jsonlocal .= '"code": "'.$row['code'].'",';
			$jsonlocal .= '"type": "'.$row['type'].'",';
			if($row['type']=='session'){
				$jsonlocal .= '"idref": "'.$row['directory'].'",';
				$jsonlocal .= '"img": "session.jpg",';
			}else{
				$jsonlocal .= '"idref": "'.$row['directory'].'",';
				$jsonlocal .= '"img": "courses/'.$row['directory'].'/course-pic.png",';
			}
			$jsonlocal .= '"title": '.json_encode($row['title']);
			$jsonlocal .= '}';			
			$cnt = $cnt + 1;
		}
	}
$jsonlocal .= ']}';
$filename = $idu.'-local.json';	
$fd = fopen($filename,'w');	
fwrite($fd,$jsonlocal);
fclose($fd);
echo $jsonlocal;