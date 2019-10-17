<?php
/* For licensing terms, see /license.txt */

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_course_script();

$plugin = new AppPlugin();
$pluginList = $plugin->getInstalledPlugins();
$capturePluginInstalled = in_array('jcapture', $pluginList);
if (!$capturePluginInstalled) {
    exit;
}

if (!isset($_FILES['Filedata'])) {
    exit;
}

$courseInfo = api_get_course_info();
$folderName = 'captures';
$documentId = DocumentManager::get_document_id($courseInfo, '/'.$folderName);
$path = null;
if (empty($documentId)) {
    $course_dir = $courseInfo['path'].'/document';
    $sys_course_path = api_get_path(SYS_COURSE_PATH);
    $dir = $sys_course_path.$course_dir;
    $createdDir = create_unexisting_directory(
        $courseInfo,
        api_get_user_id(),
        api_get_session_id(),
        null,
        null,
        $dir,
        '/'.$folderName,
        $folderName
    );
    if ($createdDir) {
        $path = '/'.$folderName;
    }
} else {
    $data = DocumentManager::get_document_data_by_id($documentId, $courseInfo['code']);
    $path = $data['path'];
}

if (empty($path)) {
    exit;
}

$files = [
    'file' => $_FILES['Filedata'],
];

DocumentManager::upload_document(
    $files,
    $path,
    $_FILES['Filedata']['name'],
    null,
    false,
    'rename',
    false,
    true
);
