<?php

	/* For licensing terms, see /license.txt */
	require_once __DIR__.'/../../main/inc/global.inc.php';
	require_once __DIR__.'/h5p_plugin.class.php';

	if(api_is_anonymous()){
		echo "<script>setTimeout(function(){location.href = '../../index.php';},1000);</script>";
		exit;
	}

	$vers = 6;

	$htmlHeadXtra[] = '<script src="resources/js/interface.js?v='.$vers.'" type="text/javascript" language="javascript"></script>';

	$id = isset($_GET['id']) ? (int) $_GET['id']:0;
	$nodeType = isset($_GET['node_type']) ? Security::remove_XSS($_GET['node_type']) : '';

	$terma = "";
	$termb = "";
	$termc = "";
	$termd = "";
	$terme = "";
	$termf = "";

	$opt1 = "";
	$opt2 = "";
	$opt3 = "";

	$description = "";

	$term = null;

	$contentForm = '<p>Error</p>';

	if($id>0){

		$sql = "SELECT * FROM plugin_h5p WHERE id = $id ";
		$result = Database::query($sql);

		while($rowP=Database::fetch_array($result)){
			$nodeType = $rowP['node_type'];

			$terma = $rowP['terms_a'];
			$termb = $rowP['terms_b'];
			$termc = $rowP['terms_c'];

			$termd = $rowP['terms_d'];
			$terme = $rowP['terms_e'];
			$termf = $rowP['terms_f'];

			$opt1 = $rowP['opt_1'];
			$opt2 = $rowP['opt_2'];
			$opt3 = $rowP['opt_3'];

			$description = $rowP['descript'];
		}

		//copyr($source, $dest, $exclude = [], $copied_files = [])
		$fld_id = 'source-'.$id;

		$src_h5p ='cache-h5p/launch/source-'.$nodeType;
		$dest_h5p ='cache-h5p/launch/'.$fld_id;

		//mkdir($dest_h5p);

		recurse_copy($src_h5p, $dest_h5p);

		$content_src ='cache-h5p/launch/'.$fld_id.'/content/content.json';
		$content_flx = file_get_contents($content_src);

		if($nodeType=='dialogcard'||$nodeType=='memory'){

			if(controlSourceCards($terma)){
				$base_flx = getSourceCards($terma,$nodeType);
			}
			if(controlSourceCards($termb)){
				$base_flx .= ',';
				$base_flx .= getSourceCards($termb,$nodeType);
			}
			if(controlSourceCards($termc)){
				$base_flx .= ',';
				$base_flx .= getSourceCards($termc,$nodeType);
			}
			if(controlSourceCards($termd)){
				$base_flx .= ',';
				$base_flx .= getSourceCards($termd,$nodeType);
			}
			if(controlSourceCards($terme)){
				$base_flx .= ',';
				$base_flx .= getSourceCards($terme,$nodeType);
			}
			if(controlSourceCards($termf)){
				$base_flx .= ',';
				$base_flx .= getSourceCards($termf,$nodeType);
			}

			$content_flx = str_replace("\"@base_cards@\"",$base_flx,$content_flx);

		}

		if($nodeType=='guesstheanswer'){

			$extractImgData = "images/dialogcard.jpg";
			$path_parts = pathinfo($termb);
			$fileN = $path_parts['filename'];
			$fileE = $path_parts['extension'];
			if($fileN!=''){
				$fileN = $fileN.'.'.$fileE;
				$p1 = $termb;
				if($p1!=''&&$p1!='dialogcard.jpg'&&$p1!='img/dialogcard.jpg'&&$p1!='images/dialogcard.jpg'){
					$p2 = api_get_path(SYS_PATH).'plugin/h5p/cache-h5p/launch/img/'.$fileN;
					copy($p1,$p2);
					$extractImgData = "../../img/".$fileN;
				}else{
					$extractImgData = api_get_path(WEB_PATH).'plugin/h5p/cache-h5p/launch/img/dialogcard.jpg';
				}
			}

			$content_flx = str_replace("@image_b@",json_encode($extractImgData),$content_flx);

		}

		$content_flx = str_replace("@terms_a@",$terma,$content_flx);
		$content_flx = str_replace("@terms_b@",$termb,$content_flx);
		$content_flx = str_replace("@terms_c@",$termc,$content_flx);

		$content_flx = str_replace("@descript@",$description,$content_flx);

		$langUi = getLangUi();

		if($langUi=='fr'||$langUi=='french'){


			$content_flx = str_replace("\"solution label\"","\"Voir la solution\"",$content_flx);
			$content_flx = str_replace("\"Turn\"","\"Tourner\"",$content_flx);
			$content_flx = str_replace("\"Check\"","\"Vérifier\"",$content_flx);
			$content_flx = str_replace("\"Retry\"","\"Recommencer\"",$content_flx);
			$content_flx = str_replace("\"Correct!\"","\"Bravo!\"",$content_flx);
			$content_flx = str_replace("\"Incorrect!\"","\"Réponse incorrecte!\"",$content_flx);
			$content_flx = str_replace("\"Answer not found!\"","\"Réponse non trouvé!\"",$content_flx);
			$content_flx = str_replace("\"You got :num out of :total points\"","\"Votre score :num out sur :total points\"",$content_flx);
			$content_flx = str_replace("\"Show solution\"","\"Voir la solution\"",$content_flx);
		}

		$fp = fopen($content_src,'w');
		fwrite($fp,$content_flx);
		fclose($fp);

		$tar_htm ='cache-h5p/launch/'.$fld_id.'.html';
		$src_h5p = file_get_contents('cache-h5p/launch/source-h.html');

		$src_h5p = str_replace("{folder}",$fld_id,$src_h5p);

		//{folder}
		$fp = fopen($tar_htm,'w');
		fwrite($fp,$src_h5p);
		fclose($fp);

		$pathPlugH5P = api_get_path(WEB_PLUGIN_PATH).'h5p/';

		$contentForm = '<iframe frameborder=0 width="100%" height="600px" ';
		$contentForm .= ' style="width:100%;height:600px;" ';
		$contentForm .= ' src="'.$pathPlugH5P.$tar_htm.'" >';
		$contentForm .= '</iframe>';

	}

	$contentForm .= '<h3 style="text-align:center;" >Code embeded</h3>';
	$contentForm .= '<textarea rows=5 style="margin-left:10%;width:80%;margin-right:10%;" >';
	$contentForm .= $contentForm;
	$contentForm .= '</textarea>';
	$contentForm .= '<p style="text-align:center;" >';
	$contentForm .= '<a href="node_list.php" class="btn btn-primary">';
	$contentForm .= '<em class="fa"></em>'.get_lang('Close').'</a>';
	$contentForm .= '</p>';
	$tpl = new Template("H5P");

	$tpl->assign('form', $contentForm);

	$content = $tpl->fetch('/h5p/view/node_view.tpl');

	$tpl->assign('content', $content);

	$tpl->display_one_col_template();

	//echo "<script>setTimeout(function(){location.href = 'node_list.php';},1000);</script>";
	function recurse_copy($src,$dst) {
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				if ( is_dir($src . '/' . $file) ) {
					recurse_copy($src . '/' . $file,$dst . '/' . $file);
				}
				else {
					copy($src . '/' . $file,$dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}

	function controlSourceCards($termData){

		if($termData==''){
			return false;
		}
		$partTerm = explode("|", $termData);
		$txtWarp = $partTerm[0];
		$txtWarp = strip_tags($txtWarp);
		if($txtWarp!=''){
			return true;
		}else{
			return false;
		}

	}

	function getSourceCards($termData,$nodeType){

		$base_flx = '{"tips":{},
		"text": @text_a@ ,
		"answer": @answer_a@ ,
		"image":{"path":@image_a@,
		"mime":"image\/jpg",
		"copyright":{"license":"U"},
		"width":100,
		"height":100
		}}';

		if($nodeType=='memory'){
			$base_flx = '{"image":{"path":@image_a@,
			"mime":"image\/jpeg",
			"copyright":{"license":"U"},
			"width":100,"height":100},
			"description":@text_a@,"matchAlt":@text_a@,"imageAlt":@text_a@}';
		}

		$partTerm = explode("|", $termData);
		$base_flx = str_replace("@text_a@",json_encode($partTerm[0]),$base_flx);
		$base_flx = str_replace("@answer_a@",json_encode($partTerm[1]),$base_flx);

		$extractImg = "images/dialogcard.jpg";

		$path_parts = pathinfo($partTerm[2]);
		$fileN = $path_parts['filename'];
		$fileE = $path_parts['extension'];

		if($fileN!=''){
			$fileN = $fileN.'.'.$fileE;
			$p1 = $partTerm[2];
			if($p1!=''&&$p1!='dialogcard.jpg'&&$p1!='img/dialogcard.jpg'&&$p1!='images/dialogcard.jpg'){

				$p2 = api_get_path(SYS_PATH).'plugin/h5p/cache-h5p/launch/img/'.$fileN;

				copy($p1,$p2);

				$extractImg = "../../img/".$fileN;

			}else{
				$extractImg =  api_get_path(WEB_PATH).'plugin/h5p/cache-h5p/launch/img/dialogcard.jpg';
			}
		}

		$base_flx = str_replace("@image_a@",json_encode($extractImg),$base_flx);

		return $base_flx;

	}

	function getLangUi()
	{

		$language = 'en';

		global $language_interface;
		if(!empty($language_interface)){
			$language = $language_interface;
		}

		return $language;

	}
