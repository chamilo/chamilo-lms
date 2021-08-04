<?php
/* For licensing terms, see /license.txt */

session_cache_limiter('none');

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

// Protection
api_protect_course_script();

$courseCode = isset($_GET['code']) ? $_GET['code'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$file = isset($_GET['file']) ? $_GET['file'] : '';
$courseInfo = api_get_course_info($courseCode);
if (empty($courseInfo)) {
    $courseInfo = api_get_course_info();
}
$type = preg_replace("/[^a-zA-Z_]+/", '', $type);

if (empty($courseInfo) || empty($type) || empty($file)) {
    api_not_allowed(true);
}

$toolPath = api_get_path(SYS_COURSE_PATH).$courseInfo['path'].'/upload/'.$type.'/';

if (!is_dir($toolPath)) {
    api_not_allowed(true);
}

if (Security::check_abs_path($toolPath.$file, $toolPath.'/')) {
    $fullFilePath = $toolPath.$file;
    $result = DocumentManager::file_send_for_download($fullFilePath, false, '');
    if ($result === false) {
        api_not_allowed(true);
    }
}
exit;
