<?php
/* For licensing terms, see /license.txt */

// Add security from Chamilo
api_protect_course_script();
api_block_anonymous_users();

# Save the audio to a URL-accessible directory for playback.
parse_str($_SERVER['QUERY_STRING'], $params);

if (isset($params['waminame']) && isset($params['wamidir']) && isset($params['wamiuserid'])) {
    $waminame = $params['waminame'];
    $wamidir = $params['wamidir'];
    $wamiuserid = $params['wamiuserid'];
} else {
    api_not_allowed();
    die();
}

if ($wamiuserid != api_get_user_id() || api_get_user_id() == 0 || $wamiuserid == 0) {
    api_not_allowed();
    die();
}


//clean
$waminame = Security::remove_XSS($waminame);
$waminame = Database::escape_string($waminame);
$waminame = addslashes(trim($waminame));
$waminame = api_replace_dangerous_char($waminame, 'strict');
$waminame = FileManager::disable_dangerous_file($waminame);
$wamidir = Security::remove_XSS($wamidir);

$content = file_get_contents('php://input');

//security extension
$ext = explode('.', $waminame);
$ext = strtolower($ext[sizeof($ext) - 1]);

if ($ext != 'wav') {
    die();
}

//Do not use here check Fileinfo method because return: text/plain

$dirBaseDocuments = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
$saveDir = $dirBaseDocuments.$wamidir;
$current_session_id = api_get_session_id();
$groupId = $_SESSION['_gid'];

//avoid duplicates
$waminame_to_save = $waminame;
$title_to_save = str_replace('_', ' ', $waminame);
$waminame_noex = basename($waminame, ".wav");
if (file_exists($saveDir.'/'.$waminame_noex.'.'.$ext)) {
    $i = 1;
    while (file_exists($saveDir.'/'.$waminame_noex.'_'.$i.'.'.$ext)) {
        $i++;
    }
    $waminame_to_save = $waminame_noex.'_'.$i.'.'.$ext;
    $title_to_save = $waminame_noex.'_'.$i.'.'.$ext;
    $title_to_save = str_replace('_', ' ', $title_to_save);
}


$documentPath = $saveDir.'/'.$waminame_to_save;

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


// Add to disk
$fh = fopen($documentPath, 'w') or die("can't open file");
fwrite($fh, $content);
fclose($fh);

error_log($documentPath);
$fileInfo = pathinfo($documentPath);
$courseInfo = api_get_course_info();

$file = array(
    'file' => array(
        'name' => $fileInfo['basename'],
        'tmp_name' => $documentPath,
        'size' => filesize($documentPath),
        'from_file' => true
    )
);

$output = true;
$documentData = DocumentManager::upload_document($file, $wamidir, null, null, 0, 'overwrite', false, $output);

if (!empty($documentData)) {
    $newDocId = $documentData['id'];
    $newMp3DocumentId = DocumentManager::addAndConvertWavToMp3($documentData, $courseInfo, api_get_user_id());

    if ($newMp3DocumentId) {
        $newDocId = $newMp3DocumentId;
    }

    if (isset($_REQUEST['lp_item_id']) && !empty($_REQUEST['lp_item_id'])) {
        $lpItemId = $_REQUEST['lp_item_id'];
        /** @var learnpath $lp */
        $lp = isset($_SESSION['oLP']) ? $_SESSION['oLP'] : null;

        if (!empty($lp)) {
            $lp->set_modified_on();
            $lpItem = new learnpathItem($lpItemId);
            $lpItem->add_audio_from_documents($newDocId);
        }
    }
}
