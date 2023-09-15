<?php

/* For licensing terms, see /license.txt */

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

$allowSessionAdmin = false;
if (api_get_configuration_value('allow_session_admin_extra_access')) {
    $allowSessionAdmin = true;
}

api_protect_admin_script($allowSessionAdmin);

$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
$user_table = Database::get_main_table(TABLE_MAIN_USER);
$course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$session_course_user_table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$tool_name = get_lang('ExportUserListXMLCSV');
$interbreadcrumb[] = ["url" => 'index.php', "name" => get_lang('PlatformAdmin')];

set_time_limit(0);
$coursesSessions = [];
$coursesSessions[''] = '--';
$allCoursesFromSessions = SessionManager::getAllCoursesFromAllSessions();
$coursesSessions = array_merge($coursesSessions, $allCoursesFromSessions);

$courses = [];
$courses[''] = '--';
$sql = "SELECT code,visual_code,title FROM $course_table ORDER BY visual_code";

global $_configuration;

if (api_is_multiple_url_enabled()) {
    $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
    $access_url_id = api_get_current_access_url_id();
    if ($access_url_id != -1) {
        $sql = "SELECT code,visual_code,title
                FROM $course_table as c
                INNER JOIN $tbl_course_rel_access_url as course_rel_url
                ON (c.id = course_rel_url.c_id)
                WHERE access_url_id = $access_url_id
                ORDER BY visual_code";
    }
}
$result = Database::query($sql);
while ($course = Database::fetch_object($result)) {
    $courses[$course->code] = $course->visual_code.' - '.$course->title;
}
$form = new FormValidator('export_users');
$form->addElement('header', $tool_name);
$form->addElement('radio', 'file_type', get_lang('OutputFileType'), 'XML', 'xml');
$form->addElement('radio', 'file_type', null, 'CSV', 'csv');
$form->addElement('radio', 'file_type', null, 'XLS', 'xls');
$form->addElement('checkbox', 'addcsvheader', get_lang('AddCSVHeader'), get_lang('YesAddCSVHeader'), '1');
$form->addElement('checkbox', 'addlastlogin', get_lang('IncludeLastLogin'), get_lang('IncludeLastLogin'), '1');
$form->addElement('select', 'course_code', get_lang('OnlyUsersFromCourse'), $courses);
$form->addElement('select', 'course_session', get_lang('OnlyUsersFromCourseSession'), $coursesSessions);
$form->addButtonExport(get_lang('Export'));
$form->setDefaults(['file_type' => 'csv']);

if ($form->validate()) {
    $export = $form->exportValues();
    $file_type = $export['file_type'];
    $course_code = Database::escape_string($export['course_code']);
    $courseInfo = api_get_course_info($course_code);
    $courseId = isset($courseInfo['real_id']) ? $courseInfo['real_id'] : 0;

    $courseSessionValue = explode(':', $export['course_session']);
    $courseSessionCode = '';
    $sessionId = 0;
    $courseSessionId = 0;
    $sessionInfo = [];

    if (is_array($courseSessionValue) && isset($courseSessionValue[1])) {
        $courseSessionCode = $courseSessionValue[0];
        $sessionId = $courseSessionValue[1];
        $courseSessionInfo = api_get_course_info($courseSessionCode);
        $courseSessionId = $courseSessionInfo['real_id'];
        $sessionInfo = api_get_session_info($sessionId);
    }

    $sql = "SELECT
                u.user_id 	AS UserId,
                u.lastname 	AS LastName,
                u.firstname 	AS FirstName,
                u.email 		AS Email,
                u.username	AS UserName,
                ".(($_configuration['password_encryption'] != 'none') ? " " : "u.password AS Password, ")."
                u.auth_source	AS AuthSource,
                u.status		AS Status,
                u.official_code	AS OfficialCode,
                u.phone		AS Phone,
                u.registration_date AS RegistrationDate,
                u.active    AS Active,
                u.expiration_date,
                u.last_login AS LastLogin
            ";
    if (strlen($course_code) > 0) {
        $sql .= " FROM $user_table u, $course_user_table cu
                    WHERE
                        u.user_id = cu.user_id AND
                        cu.c_id = $courseId AND
                        cu.relation_type<>".COURSE_RELATION_TYPE_RRHH."
                    ORDER BY lastname,firstname";
        $filename = 'export_users_'.$course_code.'_'.api_get_local_time();
    } elseif (strlen($courseSessionCode) > 0) {
        $sql .= " FROM $user_table u, $session_course_user_table scu
                    WHERE
                        u.user_id = scu.user_id AND
                        scu.c_id = $courseSessionId AND
                        scu.session_id = $sessionId
                    ORDER BY lastname,firstname";
        $filename = 'export_users_'.$courseSessionCode.'_'.$sessionInfo['name'].'_'.api_get_local_time();
    } else {
        if (api_is_multiple_url_enabled()) {
            $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();
            if ($access_url_id != -1) {
                $sql .= " FROM $user_table u
                          INNER JOIN $tbl_user_rel_access_url as user_rel_url
                          ON (u.user_id= user_rel_url.user_id)
                          WHERE access_url_id = $access_url_id
                          ORDER BY lastname,firstname";
            }
        } else {
            $sql .= " FROM $user_table u ORDER BY lastname,firstname";
        }
        $filename = 'export_users_'.api_get_local_time();
    }
    $data = [];
    $extra_fields = UserManager::get_extra_fields(0, 0, 5, 'ASC', false);

    $headers = false;
    if (!empty($export['addcsvheader'])) {
        if ($export['addcsvheader'] == '1' && ($export['file_type'] == 'csv' || $export['file_type'] == 'xls')) {
            $headers = true;
            if ($_configuration['password_encryption'] != 'none') {
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
                    'Active',
                    'ExpirationDate',
                ];
                if ($export['addlastlogin'] == '1') {
                    $data[0][] = 'LastLogin';
                }
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
                    'Active',
                    'ExpirationDate',
                ];
                if ($export['addlastlogin'] == '1') {
                    $data[0][] = 'LastLogin';
                }
            }

            foreach ($extra_fields as $extra) {
                $data[0][] = $extra[1];
            }
        }
    }

    $res = Database::query($sql);
    $now = time();
    while ($user = Database::fetch_array($res, 'ASSOC')) {
        if (!empty($user['expiration_date']) && '0000-00-00 00:00:00' !== $user['expiration_date']) {
            $expirationDate = api_strtotime($user['expiration_date'], 'UTC');
            if ($expirationDate < $now) {
                $user['Active'] = -1;
            }
        }

        $data[$user['UserId']] = $user;
    }

    foreach ($extra_fields as $fieldInfo) {
        $extraField = new ExtraField('user');
        $default = $extraField->getDefaultValueByFieldId($fieldInfo[0]);
        $fieldValues = $extraField->getAllValuesByFieldId($fieldInfo[0]);
        foreach ($data as $userId => &$values) {
            if ($headers === true && $userId === 0) {
                // Avoid the header line, if any
                continue;
            }

            if (isset($fieldValues[$userId])) {
                if (is_array($fieldValues[$userId])) {
                    $values['extra_'.$fieldInfo[1]] = $fieldValues[$userId];
                } else {
                    $values[$fieldInfo[1]] = $fieldValues[$userId];
                }
            } else {
                $values[$fieldInfo[1]] = $default;
            }
        }
    }

    switch ($file_type) {
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
}

Display::display_header($tool_name);
$form->display();
Display::display_footer();
