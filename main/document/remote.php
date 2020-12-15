<?php
/* See license terms in /license.txt */
/**
 * Script that allows download of a specific file from external applications.
 *
 * @author Arnaud Ligot <arnaud@cblue.be>, Based on work done for old videoconference application (I have about 30 minutes to write this peace of code so if somebody has more time, feel free to rewrite it...)
 *
 * @package chamilo.document
 */
/**
 * Script that allows remote download of a file.
 *
 * @param string Action parameter (action=...)
 * @param string Course code (cidReq=...)
 * @param string Current working directory (cwd=...)
 *
 * @return string JSON output
 */

/* FIX for IE cache when using https */
session_cache_limiter('none');
require_once __DIR__.'/../inc/global.inc.php';

api_protect_course_script(true);
api_block_anonymous_users();
/*==== Variables initialisation ====*/
$action = $_REQUEST['action']; //safe as only used in if()'s
$seek = ['/', '%2F', '..'];
$destroy = ['', '', ''];
$cidReq = str_replace($seek, $destroy, $_REQUEST["cidReq"]);
$cidReq = Security::remove_XSS($cidReq);
$user_id = api_get_user_id();
$coursePath = api_get_path(SYS_COURSE_PATH).$cidReq.'/document';
$_course = api_get_course_info($cidReq);
if (empty($_course)) {
    exit("problem when fetching course information");
}
// stupid variable initialisation for old version of DocumentManager functions.
$_course['path'] = $_course['directory'];
$is_manager = (CourseManager::getUserInCourseStatus($user_id, $_course['real_id']) == COURSEMANAGER);
if ($debug > 0) {
    error_log($coursePath, 0);
}
// FIXME: check security around $_REQUEST["cwd"]
$cwd = $_REQUEST['cwd'];
// treat /..
$nParent = 0; // the number of /.. into the url
while (substr($cwd, -3, 3) == '/..') {
    // go to parent directory
    $cwd = substr($cwd, 0, -3);
    if (strlen($cwd) == 0) {
        $cwd = '/';
    }
    $nParent++;
}
for (; $nParent > 0; $nParent--) {
    $cwd = (strrpos($cwd, '/') > -1 ? substr($cwd, 0, strrpos($cwd, '/')) : $cwd);
}
if (strlen($cwd) == 0) {
    $cwd = '/';
}
if (Security::check_abs_path($cwd, api_get_path(SYS_PATH))) {
    exit();
}
if ($action == 'list') {
    /*==== List files ====*/
    if ($debug > 0) {
        error_log("sending file list", 0);
    }

    // get files list
    $files = DocumentManager::getAllDocumentData($_course, $cwd, 0, null, false);

    // adding download link to files
    foreach ($files as $k => $f) {
        if ($f['filetype'] == 'file') {
            $files[$k]['download'] = api_get_path(WEB_COURSE_PATH).$cidReq."/document".$f['path'];
        }
        echo json_encode($files);
        exit;
    }
}
