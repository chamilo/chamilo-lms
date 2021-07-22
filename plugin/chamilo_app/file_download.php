<?php
require_once __DIR__ . '/../../main/inc/global.inc.php';
require_once 'webservices/WSApp.class.php';
require_once 'webservices/AppWebService.class.php';

use ChamiloSession as Session;

$username = isset($_GET['username']) ? Security::remove_XSS($_GET['username']) : null;
$apiKey = isset($_GET['api_key']) ? Security::remove_XSS($_GET['api_key']) : null;
$userId = isset($_GET['user_id']) ? Security::remove_XSS($_GET['user_id']) : null;
$courseId = isset($_GET['c_id']) ? Security::remove_XSS($_GET['c_id']) : null;
$sessionId= isset($_GET['c_id']) ? Security::remove_XSS($_GET['s_id']) : null;
$type = isset($_GET['type']) ? Security::remove_XSS($_GET['type']) : null;
$id = isset($_GET['id']) ? Security::remove_XSS($_GET['id']) : null;

if (AppWebService::isValidApiKey($username, $apiKey)) {
    /* LOGIN */
    $courseInfo = api_get_course_info_by_id($courseId);
    $platformUser = api_get_user_info($userId);
    $_user['user_id'] = $platformUser['user_id'];
    $_user['status'] = (isset($platformUser['status']) ? $platformUser['status'] : 5);
    $_user['uidReset'] = true;
    Session::write('_user', $_user);
    $uidReset = true;
    $logging_in = true;
    Login::init_user($userId, true);
    Login::init_course($courseInfo['code'], true);
    if ($sessionId > 0) {
        Session::write('id_session', $sessionId);
    } else {
        Session::erase('session_name');
        Session::erase('id_session');
    }

    $is_courseAdmin = false;
    $is_courseTutor = false;
    $is_courseMember = false;

    $course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $sql = "SELECT * FROM $course_user_table
            WHERE
            user_id  = '".$userId."' AND
                    relation_type <> ".COURSE_RELATION_TYPE_RRHH." AND
                    c_id = '$courseId'";
    $result = Database::query($sql);
    $cuData = null;
    if (Database::num_rows($result) > 0) { // this  user have a recorded state for this course
        $cuData = Database::fetch_array($result, 'ASSOC');
        $is_courseAdmin = $cuData['status'] == 1;
        $is_courseTutor = $cuData['is_tutor'] == 1;
        $is_courseMember = true;
    }

    if (isset($is_courseAdmin)) {
        Session::write('is_courseAdmin', $is_courseAdmin);
        if ($is_courseAdmin) {
            $is_allowed_in_course = true;
        }
    }
    if (isset($is_courseMember)) {
        Session::write('is_courseMember', $is_courseMember);
    }
    if (isset($is_courseTutor)) {
        Session::write('is_courseTutor', $is_courseTutor);
        if ($is_courseTutor) {
            $is_allowed_in_course = true;
        }
    }
    Session::write('is_allowed_in_course', $is_allowed_in_course);

    $courseCode = $courseInfo['code'];
    $params = api_get_cidreq_params($courseCode, $sessionId);

    require $includePath.'/local.inc.php';

    global $_configuration;
    $ruta = $_configuration['root_web'];
    if ((strripos($ruta, '/') + 1) != strlen($ruta)) {
        $ruta = $ruta.'/';
    }
    switch ($type) {
        case 'download_comment_file.php':
            if ($is_courseAdmin) {
                require_once api_get_path(SYS_CODE_PATH).'work/work.lib.php';

                // Course protection
                api_protect_course_script(true);

                $commentId = (int) $id;
                if (empty($commentId)) {
                    api_not_allowed(true);
                }
                $workData = getWorkComment($commentId);
                $courseInfo = api_get_course_info();

                if (!empty($workData)) {
                    if (empty($workData['file_path']) ||
                        (isset($workData['file_path']) && !file_exists($workData['file_path']))
                    ) {
                        api_not_allowed(true);
                    }
                            
                    $work = get_work_data_by_id($workData['work_id']);
                    
                    protectWork($courseInfo, $work['parent_id']);
                    
                    if (Security::check_abs_path(
                            $workData['file_path'],
                            api_get_path(SYS_COURSE_PATH).api_get_course_path().'/'
                        )
                    ) {
                        DocumentManager::file_send_for_download(
                            $workData['file_path'],
                            true,
                            $workData['file_name_to_show']
                        );
                        exit;
                    }
                            
                } else {
                    api_not_allowed(true);
                }
            } else {
                $url = $ruta."main/work/download_comment_file.php?comment_id=$id&$params";
            }
            break;
        case 'download_work.php':
            if (isset($_GET['correction'])) {
                $url = $ruta."main/work/download.php?id=$id&$params&correction=1";
            } else {
                $url = $ruta."main/work/download.php?id=$id&$params";
            }
            break;
        case 'downloadfolder.inc.php':
                $url = $ruta."main/work/downloadfolder.inc.php?id=$id&$params";
            break;
    }

    header('Location:'.$url);
} else {
    error_log("Not valid apiKey");
}

