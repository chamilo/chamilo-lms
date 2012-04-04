<?php
require_once '../../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';
////Add security from Chamilo
api_protect_course_script();
api_block_anonymous_users();
//
# Save the audio to a URL-accessible directory for playback.
parse_str($_SERVER['QUERY_STRING'], $params);

if(isset($params['waminame']) && isset($params['wamidir']) && isset($params['wamiuserid'])) {
	$waminame = $params['waminame'];
	$wamidir = $params['wamidir'];
	$wamiuserid = $params['wamiuserid'];
}
else {
	api_not_allowed();
	die();
}

if ($wamiuserid!= api_get_user_id() || api_get_user_id()==0 || $wamiuserid==0) {
	api_not_allowed();
	die();
}
	

//clean
$waminame = Security::remove_XSS($waminame);
$waminame = addslashes(trim($waminame));
$waminame = replace_dangerous_char($waminame, 'strict');
$waminame = disable_dangerous_file($waminame);
$wamidir = Security::remove_XSS($wamidir);


$content = file_get_contents('php://input');

//security extension
$ext = explode('.', $waminame);
$ext = strtolower($ext[sizeof($ext) - 1]);

if($ext!= 'wav'){
	die();
}

//Do not use here check Fileinfo method because return: application/x-empty

$dirBaseDocuments = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$saveDir=$dirBaseDocuments.$wamidir;
$current_session_id = api_get_session_id();
$groupId=$_SESSION['_gid'];
$documentPath = $saveDir.'/'.$waminame;
$title=str_replace('_',' ',$waminame);
//$title=basename($waminame, ".wav");//save title whitout extension

//add to disk
$fh = fopen($documentPath, 'w') or die("can't open file");
fwrite($fh, $content);
fclose($fh);

//add document to database
	$doc_id = add_document($_course, $wamidir.'/'.$waminame, 'file', filesize($documentPath), $title);
	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId, null, null, null, $current_session_id);
?>