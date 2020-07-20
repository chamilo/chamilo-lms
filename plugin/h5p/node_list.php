<?php

	/* For licensing terms, see /license.txt */

	require_once __DIR__.'/../../main/inc/global.inc.php';
	require_once __DIR__.'/h5p_plugin.class.php';

	$language = 'en';
	$platformLanguage = api_get_interface_language();
	$iso = api_get_language_isocode($platformLanguage);

	if(!api_is_anonymous()){

		$user = api_get_user_info();

		if(isset($user['status'])){
			if($user['status']==SESSIONADMIN
			||$user['status']==COURSEMANAGER
			||$user['status']==PLATFORM_ADMIN){
			}else{
				echo "<script>location.href = '../../index.php';</script>";
				exit;
			}
		}

	}else{
		echo "<script>location.href = '../../index.php';</script>";
		exit;
	}

	//Update 1.5 comment after first update
	$sqlMaj = "SELECT COUNT(*) as nb FROM information_schema.COLUMNS WHERE COLUMN_NAME = 'terms_d' and TABLE_NAME LIKE '%h5p%'";
	$rsMaj = Database::query($sqlMaj);
	$rowMaj = Database::fetch_array($rsMaj);
	$intMaj = intVal($rowMaj['nb']);
	if($intMaj==0){
		echo "<script>location.href = 'update-v.php?version=1-5';</script>";
		exit;
	}
	//Update 1.5

	$userId = $user['id'];

	$vers = 6;

	$plugin = H5PPlugin::create();

	//wordsmatch
	$id = isset($_GET['id']) ? (int) $_GET['id']:0;
	$nodeType = isset($_GET['node_type']) ? Security::remove_XSS($_GET['node_type']) : '';
	$action = isset($_GET['action']) ? Security::remove_XSS($_GET['action']):'add';

	$table = 'plugin_h5p';

	$idurl = api_get_current_access_url_id();
	$UrlWhere = "";
	if ((api_is_platform_admin() || api_is_session_admin()) && api_get_multiple_access_url()) {
        $UrlWhere = " AND url_id = $idurl ";
	}

	$sql = "SELECT * FROM $table WHERE user_id = $userId $UrlWhere ORDER BY title";
	if(isset($_GET['id'])){
		$sql = "SELECT * FROM $table  WHERE id <> $id AND user_id = $userId $UrlWhere LIMIT 2";
	}

	$result = Database::query($sql);
	$terms = Database::store_result($result,'ASSOC');
	$countData = count($terms);

	$term = null;

	if($id>0){
		if(!empty($id)){
			$sql = "SELECT * FROM $table WHERE id = $id AND user_id = $userId ";
			$result = Database::query($sql);
			$term = Database::fetch_array($result, 'ASSOC');
			if(empty($term)){
				api_not_allowed(true);
			}
		}
	}

	include("inc/trad-h5p.php");
	include("inc/form-h5p.php");

	$awp = api_get_path(WEB_PATH);

	$htmlHeadXtra[] = '<script type="text/javascript" src="'.$awp.'vendor/studio-42/elfinder/js/elfinder.full.js"></script>';
	$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.$awp.'vendor/studio-42/elfinder/css/elfinder.full.css">';
	$htmlHeadXtra[] = '<script type="text/javascript" src="'.$awp.'web/assets/jquery-ui/jquery-ui.min.js"></script>';
	$htmlHeadXtra[] = '<link rel="stylesheet" type="text/css" href="'.$awp.'web/assets/jquery-ui/themes/smoothness/jquery-ui.min.css">';

	$htmlHeadXtra[] = '<link href="resources/js/pell.min.css?v='.$vers.'"  rel="stylesheet" type="text/css" />';
	$htmlHeadXtra[] = '<script src="resources/js/pell.js?v='.$vers.'" type="text/javascript" language="javascript"></script>';

	$htmlHeadXtra[] = '<script src="resources/js/interface.js?v='.$vers.'" type="text/javascript" language="javascript"></script>';
	$htmlHeadXtra[] = '<script src="resources/js/jquery.dataTables.min.js?v='.$vers.'" type="text/javascript" language="javascript"></script>';
	$htmlHeadXtra[] = "<script>
	$(document).ready(function(){
		$('.data_table').DataTable({
			'iDisplayLength': 50
		});
	});
	var GlobalTypeNode = '".$nodeType."';
	</script>";

	if($nodeType!=''){
		$htmlHeadXtra[] = "<script>
			$(document).ready(function(){interface$nodeType();});
		</script>";
	}

	$htmlHeadXtra[] = "<style>
		.previous{
			margin-right:10px;
			cursor:pointer;
		}
		.next{
			cursor:pointer;
		}
		input[type='radio'] {
			-ms-transform: scale(1.5);
			-webkit-transform: scale(1.5);
			transform: scale(1.5);
		}

	</style>";

	include("inc/switchaction-h5p.php");

	$tpl = new Template("H5P");
	if($nodeType==''){
		$tpl->assign('terms', $terms);
	}

	$tpl->assign('tables', $tableOfnodes);
	$tpl->assign('form', $form->returnForm());

	$content = $tpl->fetch('/h5p/view/node_list-v13.tpl');

	$tpl->assign('content', $content);

	$tpl->display_one_col_template();
