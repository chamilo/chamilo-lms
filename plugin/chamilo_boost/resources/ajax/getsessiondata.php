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

		$jsonlocal = '{"courses" :[';
		$jsonlocal .= '{}';
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

function retrieveProgress($idUser,$sessId){

	$cluetime = intVal(intVal(time()/60)/5);
	
$sql = <<<EOT
	SELECT
	course.id as cid,
	course.title as title,
	course.code as code,
	course.directory as directory
	FROM course
	INNER JOIN session_rel_course ON session_rel_course.c_id = course.id
	INNER JOIN session_rel_user ON session_rel_user.user_id = $idUser
	WHERE session_rel_course.session_id = $sessId
EOT;

	$result = array();
	$resultset = Database::query($sql);

	while($row = Database::fetch_array($resultset)){
		$lpid = $row['cid'];
		$result[$lpid] = array(
			'cid' => $row['cid'],
			'code' => $row['code'],
			'title' => $row['title'],
			'directory' => $row['directory']
		);
	}
	return $result;

}



$idu = getUserIdB();

$sessionId = '';

if(isset($_GET["s"])){$sessionId = $_GET["s"];}

$progress = retrieveProgress($idu,$sessionId);
$cnt = 0;

$jsonlocal = '{"courses" :[';

	foreach ($progress as &$row){

		if($row['cid']!=''){
			if($cnt>0){
				$jsonlocal .= ',';
			}

			$jsonlocal .= '{';
			$jsonlocal .= '"cid": "'.$row['cid'].'",';
			$jsonlocal .= '"code": "'.$row['code'].'",';
			$jsonlocal .= '"title": '.json_encode($row['title']).',';
			$jsonlocal .= '"directory": "'.$row['directory'].'"';
			$jsonlocal .= '}';
			$cnt = $cnt + 1;

		}

	}

$jsonlocal .= ']}';

//$filename = $idu.'-session.json';	

//$fd = fopen($filename,'w');	
//fwrite($fd,$jsonlocal);
//fclose($fd);

echo $jsonlocal;

