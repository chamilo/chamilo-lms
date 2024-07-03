<?php

/* For licensing terms, see /license.txt */

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_COURSES;

api_protect_admin_script(true, true, null, true);

$export = [];
$export['file_type'] = isset($_REQUEST['file_type']) ? $_REQUEST['file_type'] : null;
$export['course_code'] = isset($_REQUEST['course_code']) ? $_REQUEST['course_code'] : null;
$export['course_session'] = isset($_REQUEST['course_session']) ? $_REQUEST['course_session'] : null;
$export['addcsvheader'] = isset($_REQUEST['addcsvheader']) ? $_REQUEST['addcsvheader'] : null;
$export['session'] = isset($_REQUEST['session']) ? $_REQUEST['session'] : null;

// Database table definitions
$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
$userTable = Database::get_main_table(TABLE_MAIN_USER);
$course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$session_course_user_table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
$session_user_table = Database::get_main_table(TABLE_MAIN_SESSION_USER);

$fileType = $export['file_type'];
$course_code = Database::escape_string($export['course_code']);
$courseInfo = api_get_course_info($course_code);
$courseId = isset($courseInfo['real_id']) ? $courseInfo['real_id'] : 0;

$courseSessionValue = explode(':', $export['course_session']);
$courseSessionCode = '';
$sessionId = 0;
$courseSessionId = 0;
$sessionInfo = [];

if (!empty($export['session'])) {
    $sessionInfo = api_get_session_info($export['session']);
    $sessionId = isset($sessionInfo['id']) ? $sessionInfo['id'] : 0;
}

if (is_array($courseSessionValue) && isset($courseSessionValue[1])) {
    $courseSessionCode = $courseSessionValue[0];
    $sessionId = $courseSessionValue[1];
    $courseSessionInfo = api_get_course_info($courseSessionCode);
    $courseSessionId = $courseSessionInfo['real_id'];
    $sessionInfo = api_get_session_info($sessionId);
}

$extraUrlJoin = '';
$extraUrlCondition = '';
if (api_is_multiple_url_enabled()) {
    $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $extraUrlJoin .= " INNER JOIN $tbl_user_rel_access_url as user_rel_url
				           ON (u.id = user_rel_url.user_id) ";
        $extraUrlCondition = " AND access_url_id = $access_url_id";
    }
}

$sql = "SELECT
            u.id AS UserId,
            u.lastname 	AS LastName,
            u.firstname 	AS FirstName,
            u.email 		AS Email,
            u.username	AS UserName,
            ".(($_configuration['password_encryption'] != 'none') ? " " : "u.password AS Password, ")."
            u.auth_source	AS AuthSource,
            u.status		AS Status,
            u.official_code	AS OfficialCode,
            u.phone		AS Phone,
            u.registration_date AS RegistrationDate";
if (strlen($course_code) > 0) {
    $sql .= "   FROM $userTable u
                INNER JOIN $course_user_table cu
                ON (u.id = cu.user_id)
                $extraUrlJoin
                WHERE
                    cu.c_id = $courseId AND
                    cu.relation_type <> ".COURSE_RELATION_TYPE_RRHH."
                    $extraUrlCondition
                ORDER BY lastname,firstname";
    $filename = 'export_users_'.$course_code.'_'.api_get_local_time();
} elseif (strlen($courseSessionCode) > 0) {
    $sql .= "   FROM $userTable u
                INNER JOIN $session_course_user_table scu
                ON (u.id = scu.user_id)
                $extraUrlJoin
                WHERE
                    scu.c_id = $courseSessionId AND
                    scu.session_id = $sessionId
                    $extraUrlCondition
                ORDER BY lastname,firstname";
    $filename = 'export_users_'.$courseSessionCode.'_'.$sessionInfo['name'].'_'.api_get_local_time();
} elseif ($sessionId > 0) {
    $sql .= "   FROM $userTable u
                INNER JOIN $session_user_table su
                ON (u.id = su.user_id)
                $extraUrlJoin
                WHERE
                    su.session_id = $sessionId
                    $extraUrlCondition
                ORDER BY lastname,firstname";
    $filename = 'export_users_'.$sessionInfo['name'].'_'.api_get_local_time();
} else {
    if (api_is_multiple_url_enabled()) {
        $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
        $access_url_id = api_get_current_access_url_id();
        if ($access_url_id != -1) {
            $sql .= " FROM $userTable u
					INNER JOIN $tbl_user_rel_access_url as user_rel_url
                    ON (u.id = user_rel_url.user_id)
                    WHERE access_url_id = $access_url_id
                    ORDER BY lastname,firstname";
        }
    } else {
        $sql .= " FROM $userTable u ORDER BY lastname,firstname";
    }
    $filename = 'export_users_'.api_get_local_time();
}
$data = [];
$extra_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC', false);
if ($export['addcsvheader'] == '1' && $export['file_type'] === 'csv') {
    if ($_configuration['password_encryption'] !== 'none') {
        $data[] = [
            'UserId',
            'LastName',
            'FirstName',
            'Email',
            'UserName',
            'AuthSource',
            'Status',
            'OfficialCode',
            'PhoneNumber',
            'RegistrationDate',
        ];
    } else {
        $data[] = [
            'UserId',
            'LastName',
            'FirstName',
            'Email',
            'UserName',
            'Password',
            'AuthSource',
            'Status',
            'OfficialCode',
            'PhoneNumber',
            'RegistrationDate',
        ];
    }

    foreach ($extra_fields as $extra) {
        $data[0][] = $extra[1];
    }
}

$res = Database::query($sql);
while ($user = Database::fetch_array($res, 'ASSOC')) {
    $studentData = UserManager::get_extra_user_data(
        $user['UserId'],
        true,
        false
    );
    foreach ($studentData as $key => $value) {
        $key = substr($key, 6);
        if (is_array($value)) {
            $user[$key] = $value['extra_'.$key];
        } else {
            $user[$key] = $value;
        }
    }
    $data[] = $user;
}

switch ($fileType) {
    case 'xml':
        Export::arrayToXml($data, $filename, 'Contact', 'Contacts');
        exit;
        break;
    case 'csv':
        Export::arrayToCsv($data, $filename);
        exit;
    case 'xls':
        Export::arrayToXls($data, $filename);
        exit;
        break;
}
