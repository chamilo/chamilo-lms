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

		$cglobal = __DIR__.'/../../../../main/inc/global.inc.php';

		if(file_exists($cglobal)){
			require_once __DIR__.'/../../../../main/inc/global.inc.php';
		}else{
			echo "<div>Erreur</div>";
			exit;
		}

	}

	function getLearningPathInfos($directory){

		//1 description
		//2 objectifs

		$infosLp = array();

		$sql  = " SELECT content, description_type ";
		$sql .= " FROM c_course_description ";
		$sql .= " INNER JOIN course ON c_course_description.c_id = course.id ";
		$sql .= " WHERE course.directory = '$directory' ";

		$resultLp = Database::query($sql);
		while($row = Database::fetch_array($resultLp)){
			$infosLp[] = $row;
		}
		
		return $infosLp;

	}

	function getCourseInfos($infosLp,$i){

		$cont = "";

		foreach ($infosLp as $row){

			$content = $row['content'];
			$descriptionType = $row['description_type'];

			if($descriptionType==$i){
				$cont = $content;
			}


		}

		return $cont;

	}

	function getLearningPathProgress($directory){

		$dataLp = array();
		
		$result = array();
		$sqlCat = "SELECT '0' as lpid , '0' as code,c_lp_category.name as title, 0 as score,0 as inter,iid as category_id
		FROM c_lp_category
		INNER JOIN course ON c_lp_category.c_id = course.id 
		WHERE course.directory = '$directory' ORDER BY position";
		
		$resultCat = Database::query($sqlCat);

		$sql = " SELECT c_lp_item.lp_id as lpid,
		course.code as code,
		c_lp.name as title,
		category_id
		FROM c_lp_item
		INNER JOIN course ON c_lp_item.c_id = course.id
		INNER JOIN c_lp ON c_lp_item.lp_id = c_lp.id
		WHERE course.directory = '$directory'
		GROUP BY lpid";

		$resultLp = Database::query($sql);
		
		while($row = Database::fetch_array($resultLp)){
			$dataLp[] = $row;
		}

		$index = 0;

		foreach ($dataLp as $row){

			$lpid = $row['lpid'];
			$category = $row['category_id'];
			
			if($category==0){

				$result[$index] = array(
					'lpid' => $row['lpid'],
					'code' => $row['code'],
					'title' => $row['title'],
				);
				$index++;
			}

		}
		
		while ($rowCat = Database::fetch_array($resultCat)){

			$catId = $rowCat['category_id'];

			$result[$index] = array(
				'lpid' => '0','code' => '0',
				'title' => $rowCat['title']
			);
			$index++;

			foreach ($dataLp as $row){

				$lpid = $row['lpid'];
				$category = $row['category_id'];

				if($category==$catId){
					$result[$index] = array(
						'lpid' => $row['lpid'],
						'code' => $row['code'],
						'title' => $row['title']
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
				'title' => $rowQuizz['title']
			);
			$index++;

		}

		return $result;

	}

	$jsonlocal = '';
	$directory = '';

	if(isset($_GET["d"])){
		$directory = $_GET["d"];
	}

	$cnt = 0;

	$progress = getLearningPathProgress($directory);

	$infosCourse = getLearningPathInfos($directory);

	$listOfModules = '';
	
	foreach ($progress as &$row){
		$listOfModules .= ' -> '.$row['lpid'].' ';
		$listOfModules .= $row['code'].' ';
		$listOfModules .= $row['title'].'<br/>';
	}

	$finalOutH = '<div class="container-fluid" style="padding:15px;" >';
	$finalOutH .= '<div class="row">';
	$finalOutH .= '<div class="col-md-12">';
	
	//First part
	$finalOutH .= '<div class="row">';
	
	$finalOutH .= '<div class="col-md-12">';

	$infosDescr = getCourseInfos($infosCourse,1);

	if($infosDescr==''){

		$finalOutH .= '<p><br/>';
		$finalOutH .= "En s'appuyant sur le <b>matériel pédagogique</b> mis à votre disposition (documents, textes, exercices, vidéos, multimédias, extraits sonores...)";
		$finalOutH .= " le module de formation <strong>{namecourse}</strong> vous permettra d'enrichir vos connaissances rapidement.<br/>";
		$finalOutH .= "<br/>";
		$finalOutH .= "Le collaborative Learning matérialisé par la section <b>interactions</b> est de réunir les outils qui permettent une communication entre étudiants et formateurs.";
		$finalOutH .= '</p>';
	
	}else{
	
		$finalOutH .= '<p><br/>';
		$finalOutH .= $infosDescr;
		$finalOutH .= '</p>';
	
	}

	$finalOutH .= '</div>';
	$finalOutH .= '</div>';

	//Second part
	$finalOutH .= '<div class="row">';

	$finalOutH .= '<div class="col-md-12" >';

	$infosObjectifs = getCourseInfos($infosCourse,2);

	if($infosObjectifs==''){
		$finalOutH .= '<p style="padding-bottom:2px;" ><b><u>Objectifs</u></b></p>';
		$finalOutH .= "L'objectif pédagogique sert à conduire et à construire l'action de formation et à évaluer les compétences acquises.<br/>";
		$finalOutH .= "Les objectifs de formation énoncent les compétences recherchées, ils se définissent à partir de l'analyse de la situation de travail avec les intéressés et les responsables.";

	}else{
		$finalOutH .= $infosObjectifs;
	}
	
	$finalOutH .= '</div>';

	$finalOutH .= '</div>';

	$finalOutH .= '</div>';
	$finalOutH .= '</div>';
	$finalOutH .= '</div>';


	echo $finalOutH;
