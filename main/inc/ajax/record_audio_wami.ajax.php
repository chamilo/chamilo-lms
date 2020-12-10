<?php
/* For licensing terms, see /license.txt */

use ChamiloSession as Session;

require_once __DIR__.'/../global.inc.php';

// Add security from Chamilo
api_block_anonymous_users();

$_course = api_get_course_info();

// Save the audio to a URL-accessible directory for playback.
parse_str($_SERVER['QUERY_STRING'], $params);

if (isset($params['waminame']) && isset($params['wamidir']) && isset($params['wamiuserid'])) {
    $waminame = $params['waminame'];
    $wamidir = $params['wamidir'];
    $wamiuserid = $params['wamiuserid'];
} else {
    api_not_allowed();
    exit();
}

if (empty($wamiuserid)) {
    api_not_allowed();
    exit();
}

$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'document'; // can be document or message

if ($type === 'document') {
    api_protect_course_script();
}

// Clean
$waminame = Security::remove_XSS($waminame);
$waminame = Database::escape_string($waminame);
$waminame = api_replace_dangerous_char($waminame);
$waminame = disable_dangerous_file($waminame);
$wamidir = Security::remove_XSS($wamidir);
$content = file_get_contents('php://input');

if (empty($content)) {
    exit;
}

$ext = explode('.', $waminame);
$ext = strtolower($ext[sizeof($ext) - 1]);

if ($ext != 'wav') {
    exit();
}

switch ($type) {
    case 'document':
        // Do not use here check Fileinfo method because return: text/plain
        $dirBaseDocuments = api_get_path(SYS_COURSE_PATH).$_course['path'].'/document';
        $saveDir = $dirBaseDocuments.$wamidir;

        if (!is_dir($saveDir)) {
            DocumentManager::createDefaultAudioFolder($_course);
        }

        // Avoid duplicates
        $waminame_to_save = $waminame;
        $documentPath = $saveDir.'/'.$waminame_to_save;

        // Add to disk
        $fh = fopen($documentPath, 'w') or exit("can't open file");
        fwrite($fh, $content);
        fclose($fh);

        $fileInfo = pathinfo($documentPath);
        $courseInfo = api_get_course_info();

        $file = [
            'file' => [
                'name' => $fileInfo['basename'],
                'tmp_name' => $documentPath,
                'size' => filesize($documentPath),
                'type' => 'audio/wav',
                'from_file' => true,
            ],
        ];
        $output = true;
        ob_start();

        // Strangely the file path changes with a double extension
        copy($documentPath, $documentPath.'.wav');

        $documentData = DocumentManager::upload_document(
            $file,
            $wamidir,
            $fileInfo['basename'],
            'wav',
            0,
            'overwrite',
            false,
            $output
        );
        $contents = ob_get_contents();

        if (!empty($documentData)) {
            $newDocId = $documentData['id'];
            $documentData['comment'] = 'mp3';
            $newMp3DocumentId = DocumentManager::addAndConvertWavToMp3(
                $documentData,
                $courseInfo,
                api_get_session_id(),
                api_get_user_id(),
                'overwrite',
                true
            );

            if ($newMp3DocumentId) {
                $newDocId = $newMp3DocumentId;
            }

            if (isset($_REQUEST['lp_item_id']) && !empty($_REQUEST['lp_item_id'])) {
                $lpItemId = $_REQUEST['lp_item_id'];
                /** @var learnpath $lp */
                $lp = Session::read('oLP');

                if (!empty($lp)) {
                    $lp->set_modified_on();
                    $lpItem = new learnpathItem($lpItemId);
                    $lpItem->add_audio_from_documents($newDocId);
                    echo Display::return_message(get_lang('Updated'), 'info');
                }
            }

            // Strangely the file path changes with a double extension
            // Remove file with one extension
            unlink($documentPath);
        } else {
            echo $contents;
        }

        break;
    case 'message':
        $tempFile = api_get_path(SYS_ARCHIVE_PATH).$waminame;
        file_put_contents($tempFile, $content);

        Session::write('current_audio_id', $waminame);
        $file = [
            'name' => basename($tempFile),
            'tmp_name' => $tempFile,
            'size' => filesize($tempFile),
            'type' => 'audio/wav',
            'move_file' => true,
        ];
        api_upload_file('audio_message', $file, api_get_user_id());
        break;
}
