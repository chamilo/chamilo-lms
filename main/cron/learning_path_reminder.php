<?php
/* For licensing terms, see /license.txt */
/**
 * New lp reminder.
 *
 * @package chamilo.cron
 *
 * @author Imanol Losada <carlos.alvarado@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

/**
 * Initialization.
 */
if (php_sapi_name() != 'cli') {
    exit; //do not run from browser
}

$field = new ExtraField('lp');
$activeMessageNewlp = $field->get_handler_field_info_by_field_variable('notify_student_and_hrm_when_available');

if ($activeMessageNewlp == false) {
    // field doesnt exist
    exit();
}
if (!isset($remedialField['default_value'])) {
    // field dont have default value
    exit();
}
if ($remedialField['default_value'] == 0) {
    // plugin is inactive
    exit;
}

function SendMessage($courseName, $lpName, $link, $userName, $userEmail, $adminName, $adminEmail)
{
    $subjectTemplate = new Template(
        null,
        false,
        false,
        false,
        false,
        false);

    $subjectLayout = $subjectTemplate->get_template(
        'mail/learning_path_reminder_subject.tpl'
    );

    $bodyTemplate = new Template(
        null,
        false,
        false,
        false,
        false,
        false);
    $bodyTemplate->assign('courseName', $courseName);
    $bodyTemplate->assign('lpName', $lpName);
    $bodyTemplate->assign('link', $link);

    $bodyLayout = $bodyTemplate->get_template(
        'mail/learning_path_reminder_body.tpl'
    );

    api_mail_html(
        $userName,
        $userEmail,
        $subjectTemplate->fetch($subjectLayout),
        $bodyTemplate->fetch($bodyLayout),
        $adminName,
        $adminEmail
    );

    return null;
}

function getHrUserOfUser($userId = 0)
{
    if ($userId == 0) {
        return [];
    }
    $relationStudenHRTable = Database::get_main_table(TABLE_MAIN_USER_REL_USER);
    $sql = "Select * from $relationStudenHRTable where user_id = $userId and relation_type = ".USER_RELATION_TYPE_RRHH;
    $Hr = [];
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $Hr[] = api_get_user_info($row['friend_user_id']);
    }

    return $Hr;
}

function Learingpaths()
{
    $administrator = [
        'completeName' => api_get_person_name(
            api_get_setting("administratorName"),
            api_get_setting("administratorSurname"),
            null,
            PERSON_NAME_EMAIL_ADDRESS
        ),
        'email' => api_get_setting("emailAdministrator"),
    ];
    $adminName = $administrator['completeName'];
    $adminEmail = $administrator['email'];

    $lpTable = Database::get_course_table(TABLE_LP_MAIN);
    $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
    $date = new DateTime();
    $date = $date->format('Y-m-d');

    $sql = "
SELECT
    $lpTable.id AS l_id,
    $lpTable.c_id AS c_id,
    $lpTable.session_id AS session_id,
    $lpTable.name AS name,
    $courseTable.title AS course_name,
    $courseTable.code AS code
FROM
    $lpTable
INNER JOIN $courseTable ON c_lp.c_id = course.id
WHERE
      publicated_on >= '$date 00:00:00' AND
      publicated_on <= '$date 23:59:59'
      ";
    $result = Database::query($sql);
    while ($row = Database::fetch_array($result)) {
        $lpId = $row['l_id'];
        $sessionId = $row['session_id'];
        $courseCode = $row['code'];
        $courseName = $row['course_name'];
        $lpName = $row['name'];

        $userlist = CourseManager::get_user_list_from_course_code(
            $courseCode,
            $sessionId
        );
        foreach ($userlist as $user) {
            $userInfo = api_get_user_info($user['id']);
            $HrUsers = getHrUserOfUser($user['id']);
            $userName = $userInfo['complete_name'];
            $email = $user['email'];
            $href = api_get_path(WEB_CODE_PATH).
                "lp/lp_controller.php?cidReq=".htmlspecialchars($courseCode).
                "&id_session=$sessionId &action=view&lp_id=$lpId&gidReq=0&gradebook=0&origin=";

            $link = "<a href='$href'>$href</a>";

            SendMessage(
                $courseName,
                $lpName,
                $link,
                $userName,
                $email,
                $adminName,
                $adminEmail
            );
            if (count($HrUsers) != 0) {
                foreach ($HrUsers as $userHr) {
                    SendMessage(
                        $courseName,
                        $lpName,
                        $link,
                        $userHr['complete_name'],
                        $userHr['email'],
                        $adminName,
                        $adminEmail
                    );
                }
            }
        }
    }
}
Learingpaths();

exit();
