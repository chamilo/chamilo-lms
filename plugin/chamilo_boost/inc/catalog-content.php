<?php

function retrieveCatalogForJson(){
	
	$sql = " SELECT course.title as title, course.code as code, course.directory as directory,";
	$sql .= " 'overviewcourse' as type ";
	$sql .= " FROM course WHERE visibility = 2 OR visibility = 3 ";
	$sql .= " ORDER BY title ;";
	
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
	
	return $result;

}

function getCatalogProg2Json($interface){

	$progress = retrieveCatalogForJson();

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

