<?php

/*
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
*/

$cglobal = __DIR__.'/../../../../main/inc/global.inc.php';

if(file_exists($cglobal)){

	require_once __DIR__.'/../../../../main/inc/global.inc.php';

}else{

	$cglobal =  __DIR__.'/../../../../main/inc/global.inc.php';

	if(file_exists($cglobal)){

		require_once __DIR__.'/../../../../main/inc/global.inc.php';

	}else{

		$jsonlocal = '{"progress" :[';
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

function getLearningPathProgress($idUser,$directory){

	$dataLp = array();
	
	$result = array();

	$sqlCat = "SELECT '0' as lpid, '0' as code, c_lp_category.name as title,";
	$sqlCat .= " 0 as score,0 as inter, iid as category_id ";
	$sqlCat .= "FROM c_lp_category ";
	$sqlCat .= "INNER JOIN course ON c_lp_category.c_id = course.id  ";
	$sqlCat .= "WHERE course.directory = '$directory' ORDER BY position";
	
	$resultCat = Database::query($sqlCat);

	$sql = " SELECT c_lp_item.lp_id as lpid,
	course.code as code,
	c_lp.name as title,
	max(c_lp_item_view.score) as score,
	COUNT(i.id) as inter,
	category_id
	FROM c_lp_item
	INNER JOIN c_lp ON c_lp_item.lp_id = c_lp.id
	INNER JOIN course ON c_lp_item.c_id = course.id
	LEFT JOIN c_lp_item_view ON c_lp_item_view.lp_item_id = c_lp_item.id
	LEFT JOIN c_lp_iv_interaction AS i ON c_lp_item_view.iid = i.lp_iv_id
	LEFT JOIN c_lp_view ON c_lp_view.id = c_lp_item_view.lp_view_id AND c_lp_view.user_id = $idUser
	WHERE course.directory = '$directory'
	GROUP BY lpid";

	$resultLp = Database::query($sql);
	
	$session_id = 0;
	
	while($row = Database::fetch_array($resultLp)){
		$dataLp[] = $row;
	}

	$index = 0;

	foreach ($dataLp as $row){

		$lpid = $row['lpid'];
		$category = $row['category_id'];
		
		if($category==0){

			$scoreVal = intval($row['score']);

			$progress = Tracking::get_avg_student_progress(
				$idUser,$directory,
				[$lpid],$session_id
			);
			
			if($progress>$scoreVal){
				$scoreVal = $progress;
				$row['score'] = $progress;
			}

			if($scoreVal>97){
				$scoreVal = 100;
			}

			$result[$index] = array(
				'lpid' => $row['lpid'],
				'code' => $row['code'],
				'title' => $row['title'],
				'score' => $scoreVal,
				'progress' => $progress,
				'inter' => $row['inter']
			);
			$index++;
		}

	}
	
	while ($rowCat = Database::fetch_array($resultCat)){

		$catId = $rowCat['category_id'];

		$result[$index] = array(
			'lpid' => '0','code' => '0',
			'title' => $rowCat['title'],
			'progress' => '-1',
			'score' => '0','inter' => '0'
		);
		$index++;
		
		foreach ($dataLp as $row){

			$lpid = $row['lpid'];
			$category = $row['category_id'];

			if($category==$catId){

				$scoreVal = intval($row['score']);

				$progress = Tracking::get_avg_student_progress(
					$idUser,$directory,
					[$lpid],$session_id
				);
				
				if($progress>$scoreVal){
					$scoreVal = $progress;
					$row['score'] = $progress;
				}

				$result[$index] = array(
					'lpid' => $row['lpid'],
					'code' => $row['code'],
					'title' => $row['title'],
					'score' => $scoreVal,
					'inter' => $row['inter']
				);
				$index++;
			}
		}

	}
	
	$sqlQuizz = "SELECT '-2' as lpid , '-2' as code , c_quiz.title as title ,0 as score ,0 as inter,type as category_id
	FROM c_quiz
	INNER JOIN course ON c_quiz.c_id = course.id
	WHERE c_quiz.Active = 1 AND course.directory = '$directory' ORDER BY c_quiz.title";
	
	$resultQuizz = Database::query($sqlQuizz);

	while($rowQuizz = Database::fetch_array($resultQuizz)){

		$result[$index] = array(
			'lpid' => $rowQuizz['lpid'],
			'code' => $rowQuizz['code'],
			'title' => $rowQuizz['title'],
			'score' => $rowQuizz['score'],
			'progress' => '-1',
			'inter' => $rowQuizz['inter']
		);
		$index++;

	}

	return $result;

}

$idu = getUserIdB();
$directory = '';

if(isset($_GET["d"])){$directory = $_GET["d"];}

//$progress = array();

$progress = getLearningPathProgress($idu,$directory);

$cnt = 0;

$jsonlocal = '{"progress" :[';

foreach ($progress as &$row){

	if($row['lpid']!=''){

		if($cnt>0){
			$jsonlocal .= ',';
		}
		
		$titleJson = $row['title'];
		$titleJson = json_encode($titleJson);
		
		$jsonlocal .= '{';
		$jsonlocal .= '"lpid": "'.$row['lpid'].'",';
		$jsonlocal .= '"code": "'.$row['code'].'",';
		$jsonlocal .= '"title": '.$titleJson.',';
		$jsonlocal .= '"score": "'.$row['score'].'",';
		if(!isset($row['progress'])){
			$row['progress'] = '-1';
		}
		$jsonlocal .= '"progress": "'.$row['progress'].'",';
		$jsonlocal .= '"inter": "'.$row['inter'].'"';
		$jsonlocal .= '}';
		
		$cnt = $cnt + 1;

	}

}

$jsonlocal .= ']}';

/*
	$filename = $idu.'-lpdata.json';
	$fd = fopen($filename,'w');
	fwrite($fd,$jsonlocal);
	fclose($fd);
*/

echo $jsonlocal;
