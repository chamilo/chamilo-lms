<?php

/* JPEGCam Script */
/* Receives JPEG webcam submission and saves to local file. */
/* Make sure your directory has permission to write files as your web server user! */
require_once '../../../inc/global.inc.php';
require_once api_get_path(LIBRARY_PATH).'fileUpload.lib.php';
require_once api_get_path(LIBRARY_PATH).'document.lib.php';
////Add security from Chamilo
api_protect_course_script();
api_block_anonymous_users();
///
# Save the audio to a URL-accessible directory for playback.
parse_str($_SERVER['QUERY_STRING'], $params);

if(isset($params['webcamname']) && isset($params['webcamdir']) && isset($params['webcamuserid'])) {
	$webcamname = $params['webcamname'];
	$webcamdir = $params['webcamdir'];
	$webcamuserid = $params['webcamuserid'];
}
else {
	api_not_allowed();
	die();
}

if ($webcamuserid!= api_get_user_id() || api_get_user_id()==0 || $webcamuserid==0) {
	api_not_allowed();
	die();
}
	

//clean
$webcamname = Security::remove_XSS($webcamname);
$webcamname = Database::escape_string($webcamname);
$webcamname = addslashes(trim($webcamname));
$webcamname = replace_dangerous_char($webcamname, 'strict');
$webcamname = disable_dangerous_file($webcamname);
$webcamdir = Security::remove_XSS($webcamdir);

//security extension
$ext = explode('.', $webcamname);
$ext = strtolower($ext[sizeof($ext) - 1]);

if($ext!= 'jpg'){
	die();
}

//Do not use here check Fileinfo method because return: text/plain                //CHECK THIS BEFORE COMMIT

$dirBaseDocuments = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$saveDir=$dirBaseDocuments.$webcamdir;
$current_session_id = api_get_session_id();
$groupId=$_SESSION['_gid'];

//avoid duplicates
$webcamname_to_save=$webcamname;
$title_to_save=str_replace('_',' ',$webcamname);
$webcamname_noex=basename($webcamname, ".jpg");
if (file_exists($saveDir.'/'.$webcamname_noex.'.'.$ext)){ 
		$i = 1;		
		while (file_exists($saveDir.'/'.$webcamname_noex.'_'.$i.'.'.$ext)) $i++;
		$webcamname_to_save = $webcamname_noex . '_' . $i . '.'.$ext;
		$title_to_save = $webcamname_noex . '_' . $i . '.'.$ext;
		$title_to_save = str_replace('_',' ',$title_to_save);
}


$documentPath = $saveDir.'/'.$webcamname_to_save;


//read content
$content = file_get_contents('php://input');
if (!$content) {
	print "ERROR: Failed to read data\n";
	exit();
}


//make a temporal file for get the file size

$tmpfname = tempnam("/tmp", "CTF");
$handle = fopen($tmpfname, "w");
fwrite($handle, $content);
fclose($handle);

// Check if there is enough space in the course to save the file
if (!DocumentManager::enough_space(filesize($tmpfname), DocumentManager::get_course_quota())) {
	unlink($tmpfname);
	die(get_lang('UplNotEnoughSpace'));
}

//erase temporal file
unlink($tmpfname);

//add to disk
$fh = fopen($documentPath, 'w') or die("can't open file");
fwrite($fh, $content);
fclose($fh);

//add document to database
	$doc_id = add_document($_course, $webcamdir.'/'.$webcamname_to_save, 'file', filesize($documentPath), $title_to_save);
	api_item_property_update($_course, TOOL_DOCUMENT, $doc_id, 'DocumentAdded', $_user['user_id'], $groupId, null, null, null, $current_session_id);
///
$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/' . $documentPath;
print "$url\n";

?>
