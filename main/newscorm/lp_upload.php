<?php //$id: $
/**
 * Script managing the learnpath upload. To best treat the uploaded file, make sure we can identify it.
 * @package dokeos.learnpath
 * @author Yannick Warnier <ywarnier@beeznest.org>
 */
/**
 * Script initialisations
 */
//flag to allow for anonymous user - needs to be set before global.inc.php
$use_anonymous = true;
require_once('back_compat.inc.php');
$course_dir  = api_get_course_path().'/scorm';
$course_sys_dir = api_get_path(SYS_COURSE_PATH).$course_dir;
if ( empty($_POST['current_dir']) ) {
	$current_dir = '';
} else {
    $current_dir = replace_dangerous_char(trim($_POST['current_dir']),'strict');
}
$uncompress  = 1;

//error_log('New LP - lp_upload.php',0);
/*
 * check the request method in place of a variable from POST
 * because if the file size exceed the maximum file upload
 * size set in php.ini, all variables from POST are cleared !
 */

if ($_SERVER['REQUEST_METHOD'] == 'POST'
	&& count($_FILES)>0
	&& !empty($_FILES['user_file']['name'])
	)
{

	// A file upload has been detected, now deal with the file...
	//directory creation

	$stopping_error = false;

	$s=$_FILES['user_file']['name'];
	//get name of the zip file without the extension
	$info = pathinfo($s);
	$filename = $info['basename'];
	$extension = $info['extension'];
	$file_base_name = str_replace('.'.$extension,'',$filename);


	$new_dir = replace_dangerous_char(trim($file_base_name),'strict');

	require_once('learnpath.class.php');
	$type = learnpath::get_package_type($_FILES['user_file']['tmp_name'],$_FILES['user_file']['name']);
	switch($type){
		case 'scorm':
			require_once('scorm.class.php');
			$oScorm = new scorm();
			$manifest = $oScorm->import_package($_FILES['user_file'],$current_dir);
			if(!empty($manifest)){
				$oScorm->parse_manifest($manifest);
				$oScorm->import_manifest(api_get_course_id());
			}else{
				//show error message stored in $oScrom->error_msg
			}

			$proximity = '';
			if(!empty($_REQUEST['content_proximity'])){$proximity = Database::escape_string($_REQUEST['content_proximity']);}
			$maker = '';
			if(!empty($_REQUEST['content_maker'])){$maker = Database::escape_string($_REQUEST['content_maker']);}
			$oScorm->set_proximity($proximity);
			$oScorm->set_maker($maker);
			$oScorm->set_jslib('scorm_api.php');
			break;
		case 'aicc':
			require_once('aicc.class.php');
			$oAICC = new aicc();
			$config_dir = $oAICC->import_package($_FILES['user_file']);
			if(!empty($config_dir)){
				$oAICC->parse_config_files($config_dir);
				$oAICC->import_aicc(api_get_course_id());
			}
			$proximity = '';
			if(!empty($_REQUEST['content_proximity'])){$proximity = mysql_real_escape_string($_REQUEST['content_proximity']);}
			$maker = '';
			if(!empty($_REQUEST['content_maker'])){$maker = mysql_real_escape_string($_REQUEST['content_maker']);}
			$oAICC->set_proximity($proximity);
			$oAICC->set_maker($maker);
			$oAICC->set_jslib('aicc_api.php');
			break;
		case 'oogie':
			require_once('openoffice_presentation.class.php');
			$take_slide_name = empty($_POST['take_slide_name']) ? false : true;
			$o_ppt = new OpenofficePresentation($take_slide_name);
			$first_item_id = $o_ppt -> convert_document($_FILES['user_file']);
			break;
		case 'woogie':
			require_once('openoffice_text.class.php');
			$split_steps = $_POST['split_steps'];
			$o_doc = new OpenofficeText($split_steps);
			$first_item_id = $o_doc -> convert_document($_FILES['user_file']);
			break;
		case '':
		default:
			return api_failure::set_failure('not_a_learning_path');
	}
} // end if is_uploaded_file
elseif($_SERVER['REQUEST_METHOD'] == 'POST')
{
	//if file name given to get in claroline/upload/, try importing this way

	// A file upload has been detected, now deal with the file...
	//directory creation

	$stopping_error = false;

	//escape path with basename so it can only be directly into the claroline/upload directory
	$s=api_get_path(SYS_ARCHIVE_PATH).basename($_POST['file_name']);
	//get name of the zip file without the extension
	$info = pathinfo($s);
	$filename = $info['basename'];
	$extension = $info['extension'];
	$file_base_name = str_replace('.'.$extension,'',$filename);
	$new_dir = replace_dangerous_char(trim($file_base_name),'strict');

	require_once('learnpath.class.php');

	$type = learnpath::get_package_type($s,basename($s));
	switch($type){
		case 'scorm':
			require_once('scorm.class.php');
			$oScorm = new scorm();
			$manifest = $oScorm->import_local_package($s,$current_dir);
			if(!empty($manifest)){
				$oScorm->parse_manifest($manifest);
				$oScorm->import_manifest(api_get_course_id());
			}

			$proximity = '';
			if(!empty($_REQUEST['content_proximity'])){$proximity = mysql_real_escape_string($_REQUEST['content_proximity']);}
			$maker = '';
			if(!empty($_REQUEST['content_maker'])){$maker = mysql_real_escape_string($_REQUEST['content_maker']);}
			$oScorm->set_proximity($proximity);
			$oScorm->set_maker($maker);
			$oScorm->set_jslib('scorm_api.php');
			break;
		case 'aicc':
			require_once('aicc.class.php');
			$oAICC = new aicc();
			$config_dir = $oAICC->import_local_package($s,$current_dir);
			if(!empty($config_dir)){
				$oAICC->parse_config_files($config_dir);
				$oAICC->import_aicc(api_get_course_id());
			}
			$proximity = '';
			if(!empty($_REQUEST['content_proximity'])){$proximity = mysql_real_escape_string($_REQUEST['content_proximity']);}
			$maker = '';
			if(!empty($_REQUEST['content_maker'])){$maker = mysql_real_escape_string($_REQUEST['content_maker']);}
			$oAICC->set_proximity($proximity);
			$oAICC->set_maker($maker);
			$oAICC->set_jslib('aicc_api.php');
			break;
		case '':
		default:
			return api_failure::set_failure('not_a_learning_path');
	}
}
?>