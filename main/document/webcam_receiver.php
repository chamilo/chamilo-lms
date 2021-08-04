<?php

/* JPEGCam Script *****UPDATED to lib webcamJS 2015-09-04***** */
/* Receives JPEG webcam submission and saves to local file. */
/* Make sure your directory has permission to write files as your web server user! */

//Changes on directory because move the proper script to the new lib upgrade directory
require_once __DIR__.'/../inc/global.inc.php';
////Add security from Chamilo
api_protect_course_script();
api_block_anonymous_users();
///
// Save the audio to a URL-accessible directory for playback.
parse_str($_SERVER['QUERY_STRING'], $params);

if (isset($params['webcamname']) && isset($params['webcamdir']) && isset($params['webcamuserid'])) {
    $webcamname = $params['webcamname'];
    $webcamdir = $params['webcamdir'];
    $webcamuserid = $params['webcamuserid'];
} else {
    api_not_allowed();
    exit();
}

if ($webcamuserid != api_get_user_id() || api_get_user_id() == 0 || $webcamuserid == 0) {
    api_not_allowed();
    exit();
}

//clean
$webcamname = Security::remove_XSS($webcamname);
$webcamname = Database::escape_string($webcamname);
$webcamname = addslashes(trim($webcamname));
$webcamname = api_replace_dangerous_char($webcamname);
$webcamname = disable_dangerous_file($webcamname);
$webcamdir = Security::remove_XSS($webcamdir);
$courseInfo = api_get_course_info();
//security extension
$ext = explode('.', $webcamname);
$ext = strtolower($ext[count($ext) - 1]);

if ($ext !== 'jpg') {
    exit;
}

//Do not use here check Fileinfo method because return: text/plain                //CHECK THIS BEFORE COMMIT
$dirBaseDocuments = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/document';
$saveDir = $dirBaseDocuments.$webcamdir;
$current_session_id = api_get_session_id();
$groupId = api_get_group_id();
$groupInfo = GroupManager::get_group_properties($groupId);

// Avoid duplicates.
$webcamname_to_save = $webcamname;
$title_to_save = str_replace('_', ' ', $webcamname);
$webcamname_noex = basename($webcamname, ".jpg");
if (file_exists($saveDir.'/'.$webcamname_noex.'.'.$ext)) {
    $i = 1;
    while (file_exists($saveDir.'/'.$webcamname_noex.'_'.$i.'.'.$ext)) {
        $i++;
    }
    $webcamname_to_save = $webcamname_noex.'_'.$i.'.'.$ext;
    $title_to_save = $webcamname_noex.'_'.$i.'.'.$ext;
    $title_to_save = str_replace('_', ' ', $title_to_save);
}

$documentPath = $saveDir.'/'.$webcamname_to_save;

//read content
//Change to move_uploaded_file() function instead file_get_contents() to adapt the new lib
$content = move_uploaded_file($_FILES['webcam']['tmp_name'], $documentPath);
if (!$content) {
    echo "PHP ERROR: Failed to read data\n";
    exit();
}

//add document to database
$doc_id = add_document(
    $courseInfo,
    $webcamdir.'/'.$webcamname_to_save,
    'file',
    filesize($documentPath),
    $title_to_save
);
api_item_property_update(
    $courseInfo,
    TOOL_DOCUMENT,
    $doc_id,
    'DocumentAdded',
    api_get_user_id(),
    $groupInfo,
    null,
    null,
    null,
    $current_session_id
);

$url = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI']).'/'.$documentPath;
echo get_lang('ClipSent');
