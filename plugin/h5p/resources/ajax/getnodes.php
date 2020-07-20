<?php

	require_once __DIR__.'/../../../../main/inc/global.inc.php';

	if(api_is_anonymous()){
		echo "<script>location.href = '../../index.php';</script>";
		exit;
	}

	$id = isset($_GET['id']) ? (int) $_GET['id']:0;

	$idurl = api_get_current_access_url_id();
	$UrlWhere = "";
	if(api_get_multiple_access_url()){
        $UrlWhere = " WHERE url_id = $idurl ";
	}

	$sql = "SELECT id,title,node_type FROM plugin_chamilo_h5p $UrlWhere ORDER BY title";

	$resultset = Database::query($sql);

	$h = '';

	while ($row = Database::fetch_array($resultset)){

		$id = $row['id'];
		$title = $row['title'];
		$nodeType = $row['node_type'];
		$h .= '<div class="bloch5pLine bloch5pLine'.$id.'" onClick="selectH5Pbase(\''.$id.'\',\''.$nodeType.'\');" >'.$title.'</div>';
	}

	echo $h;
