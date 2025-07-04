<?php

/* For licensing terms, see /license.txt */

use Chamilo\CoreBundle\Framework\Container;

$cidReset = true;

require_once __DIR__.'/../inc/global.inc.php';
$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

// Database table definitions
$course_table = Database::get_main_table(TABLE_MAIN_COURSE);
$user_table = Database::get_main_table(TABLE_MAIN_USER);
$course_user_table = Database::get_main_table(TABLE_MAIN_COURSE_USER);
$session_course_user_table = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);

$tool_name = get_lang('Export users list');
$interbreadcrumb[] = ["url" => 'index.php', "name" => get_lang('Administration')];

$accessUrl = Container::getAccessUrlUtil()->getCurrent();

set_time_limit(0);
$coursesSessions = [];
$coursesSessions[''] = '--';
$allCoursesFromSessions = SessionManager::getAllCoursesFromAllSessions();
$coursesSessions = array_merge($coursesSessions, $allCoursesFromSessions);

$courses = [];
$courses[''] = '--';
$sql = "SELECT code,visual_code,title FROM $course_table ORDER BY visual_code";

if (api_is_multiple_url_enabled()) {
    $tbl_course_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
    $access_url_id = api_get_current_access_url_id();
    if (-1 != $access_url_id) {
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
$form->addElement('radio', 'file_type', get_lang('Output file type'), 'XML', 'xml');
$form->addElement('radio', 'file_type', null, 'CSV', 'csv');
$form->addElement('radio', 'file_type', null, 'XLS', 'xls');
$form->addCheckBox(
    'addcsvheader',
    [
        get_lang('Include header line?'),
        get_lang(
            'This will put the fields names on the first line. It is necessary when you want to import the file later on in a Chamilo portal.'
        ),
    ],
    get_lang('Yes, add the headers')
);
$form->addSelect('course_code', get_lang('Only users from the course'), $courses);
$form->addSelect('course_session', get_lang('Only users from the courseSession'), $coursesSessions);
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
                u.id 	AS UserId,
                u.email 		AS Email,
                ".(('none' != api_get_configuration_value('password_encryption')) ? " " : "u.password AS Password, ")."
                u.status		AS Status,
                u.official_code	AS OfficialCode,
                u.phone		AS Phone,
                u.created_at AS CreatedAt";
    if (strlen($course_code) > 0) {
        $sql .= " FROM $user_table u, $course_user_table cu
                    WHERE
                        u.id = cu.user_id AND
                        cu.c_id = $courseId AND
                        cu.relation_type<>".COURSE_RELATION_TYPE_RRHH."
                    ORDER BY lastname,firstname";
        $filename = 'export_users_'.$course_code.'_'.api_get_local_time();
    } elseif (strlen($courseSessionCode) > 0) {
        $sql .= " FROM $user_table u, $session_course_user_table scu
                    WHERE
                        u.id = scu.user_id AND
                        scu.c_id = $courseSessionId AND
                        scu.session_id = $sessionId
                    ORDER BY lastname,firstname";
        $filename = 'export_users_'.$courseSessionCode.'_'.$sessionInfo['name'].'_'.api_get_local_time();
    } else {
        if (api_is_multiple_url_enabled()) {
            $tbl_user_rel_access_url = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_USER);
            $access_url_id = api_get_current_access_url_id();
            if (-1 != $access_url_id) {
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

    if (!empty($export['addcsvheader'])) {
        if ('1' == $export['addcsvheader'] && ('csv' == $export['file_type'] || 'xls' == $export['file_type'])) {
            if ('none' != api_get_configuration_value('password_encryption')) {
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
    }

    $res = Database::query($sql);
    while ($user = Database::fetch_assoc($res)) {
        $userEntity = api_get_user_entity($user['UserId']);
        $user['LastName'] = $userEntity->getLastname();
        $user['FirstName'] = $userEntity->getFirstname();
        $user['UserName'] = $userEntity->getUsername();
        $user['AuthSource'] = implode(', ', $userEntity->getAuthSourcesAuthentications($accessUrl));

        $student_data = UserManager:: get_extra_user_data(
            $user['UserId'],
            true,
            false
        );
        foreach ($student_data as $key => $value) {
            $key = substr($key, 6);
            if (is_array($value)) {
                $user[$key] = $value['extra_'.$key];
            } else {
                $user[$key] = $value;
            }
        }
        $data[] = $user;
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

Display :: display_header($tool_name);
$form->display();
Display :: display_footer();
