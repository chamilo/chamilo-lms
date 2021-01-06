<?php
/* For licensing terms, see /license.txt */
/**
 * Course expiration reminder.
 *
 * @package chamilo.cron
 *
 * @author Imanol Losada <imanol.losada@beeznest.com>
 */
require_once __DIR__.'/../inc/global.inc.php';

/**
 * Initialization.
 */
if (php_sapi_name() != 'cli') {
    exit; //do not run from browser
};
// $isActive = api_get_setting('cron_remind_course_expiration_activate') === 'true';
$isActive = true;
if (!$isActive) {
    exit;
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

    $gradebookTable = Database::get_course_table(TABLE_LP_MAIN);
    $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
    $date = new DateTime();
    $date = $date->format('Y-m-d');

    $sql = "
SELECT
    $gradebookTable.id AS l_id,
    $gradebookTable.c_id AS c_id,
    $gradebookTable.session_id AS session_id,
    $gradebookTable.name AS name,
    $courseTable.title AS course_name,
    $courseTable.code AS code
FROM
    $gradebookTable
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
        foreach($userlist as $user){
            $userInfo = api_get_user_info($user['id']);
            $userName = $userInfo['complete_name'];
            $email = $user['email'];
            $href = api_get_path(WEB_CODE_PATH).
                "lp/lp_controller.php?cidReq=".htmlspecialchars($courseCode).
                "&id_session=$sessionId &action=view&lp_id=$lpId&gidReq=0&gradebook=0&origin=";

            $link = "<a href='$href'>$href</a>";
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
                $email,
                $subjectTemplate->fetch($subjectLayout),
                $bodyTemplate->fetch($bodyLayout),
                $administrator['completeName'],
                $administrator['email']
            );
        }

    };

}
Learingpaths();

exit();
