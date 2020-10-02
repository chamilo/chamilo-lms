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

if(api_is_anonymous()){
	
	$jsonlocal = '{"stats" :[';
	$jsonlocal .= '{';
	$jsonlocal .= '"title": "time",';
	$jsonlocal .= '"value": "6H00"';
	$jsonlocal .= '},';
	
	$jsonlocal .= '{';
	$jsonlocal .= '"title": "nblp",';
	$jsonlocal .= '"value": "12"';
	$jsonlocal .= '}';
	
	$jsonlocal .= ']}';
	
	echo $jsonlocal;
	
	exit;

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

function retrieveStats($iduser){
	
	$cluetime = intVal(intVal(time()/60)/5);
	
	$sql  = " SELECT SUM(total_time) as totaltime ";
	$sql .= " FROM `c_lp_item_view` where lp_view_id  ";
	$sql .= " IN (SELECT id FROM `c_lp_view` where user_id = $iduser );";

	$result = array();

	$resultset = Database::query($sql);

	while ($row = Database::fetch_array($resultset)){
		
		$time = intval($row['totaltime']);
		
		if($time==''){$labeltime = '00h00';}
		if($time<60){$labeltime = '00h00';}
		
		if($time<3600){
			$minutes = intval($time/60);
			if($minutes<10){
				$labeltime = '00h0'.$minutes;
			}else{
				$labeltime = '00h'.$minutes;
			}
		}
		
		if($time>3600){
			$hours = intval($time/60);
			$hours = intval($hours/60);
			$minutes = $time - intval($hours*60*60);
			$minutes = intval($minutes/60);
			if($minutes<10){
				$labeltime = $hours.'h0'.$minutes;
			}else{
				$labeltime = $hours.'h'.$minutes;
			}

		}
		
		$result['time'] = array(
			'title' => 'time',
			'value' => $labeltime
		);
	}
	
	$sql = "SELECT count(*) as NB FROM c_lp_view where user_id = $iduser;";
	
	$resultset = Database::query($sql);

	while ($row = Database::fetch_array($resultset)){
		$NB = $row['NB'];
		$result['nblp'] = array(
			'title' => 'nblp',
			'value' => $NB
		);
	}
	
	$sql  = " SELECT SUM(total_time) AS totaltime, ";
	$sql .= " AVG(c_lp_item_view.score) AS moyenneExo, ";
	$sql .= " course.directory AS directory,";
	$sql .= " course.title AS coursetitle ";
	$sql .= " FROM c_lp_item_view ";
	$sql .= " INNER JOIN course ON c_lp_item_view.c_id = course.id ";
	$sql .= " WHERE lp_view_id  ";
	$sql .= " IN (SELECT id FROM `c_lp_view` WHERE user_id = $iduser) GROUP BY c_id;";
	
	$resultset = Database::query($sql);
	
	$cntab = 0;

	$maxScore = 200;
	$eventCourseId = '';
	while ($row = Database::fetch_array($resultset)){
		
		$result['tab'.$cntab] = array(
			'title' => 'tab',
			'name' => $row['coursetitle'],
			'directory' => $row['directory'],
			'totaltime' => $row['totaltime'],
			'moyenneExo' => $row['moyenneExo'],
			'value' => "0"
		);
		
		if($row['moyenneExo']<$maxScore){
			$eventCourseId = $row['directory'];
		}

		$cntab = $cntab + 1;
	}
	
	$result['eventCourseId'] = array(
		'title' => 'eventCourseId',
		'value' => $eventCourseId
	);

	return $result;

}

$idu = getUserIdB();
$progress = retrieveStats($idu);
$cnt = 0;

$jsonlocal = '{"stats" :[';

	foreach ($progress as &$row){
		
		if($cnt>0){$jsonlocal .= ',';}
		
		if($row['title']!='tab'){
		
			$jsonlocal .= '{';
			$jsonlocal .= '"title": "'.$row['title'].'",';
			$jsonlocal .= '"value": "'.$row['value'].'"';
			$jsonlocal .= '}';
		
		}else{
			
			$jsonlocal .= '{';
			$jsonlocal .= '"title": "tab",';
			$jsonlocal .= '"name": "'.$row['name'].'",';
			$jsonlocal .= '"totaltime": "'.$row['totaltime'].'",';
			$jsonlocal .= '"moyenneExo": "'.$row['moyenneExo'].'"';
			$jsonlocal .= '}';
			
		}
		
		$cnt = $cnt + 1;

	}

$jsonlocal .= ']}';

//$filename = 'stats'-$idu.'-local.json';	

//$fd = fopen($filename,'w');	
//fwrite($fd,$jsonlocal);
//fclose($fd);

echo $jsonlocal;
