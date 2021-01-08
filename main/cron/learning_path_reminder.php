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
if (!isset($activeMessageNewlp['default_value'])) {
    // field dont have default value
    exit();
}

// function SendMessage($courseName, $lpName, $link, $userName, $userEmail, $adminName, $adminEmail)
function SendMessage($toUser, $fromUser, $courseName, $lpName, $link)
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
    $tittle = $subjectTemplate->fetch($subjectLayout);
    $content = $bodyTemplate->fetch($bodyLayout);
    $now = api_get_utc_datetime();
    $params = [
        'user_sender_id' => $toUser['id'],
        'user_receiver_id' => $toUser['id'],
        'msg_status' => MESSAGE_STATUS_CONVERSATION,
        'send_date' => $now,
        'title' => $tittle,
        'content' => $content,
        'group_id' => 0,
        'parent_id' => 0,
        'update_date' => $now,
    ];
    $tbl_message = Database::get_main_table(TABLE_MESSAGE);
    $messageId = Database::insert($tbl_message, $params);
    $notification = new Notification();
    $notification->saveNotification(
        $messageId,
        20,
        [$toUser['id']],
        $tittle,
        $content,
        $toUser,
        [],
        [],
        true
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

function LearningPaths()
{
    $admin = [];

    $lpTable = Database::get_course_table(TABLE_LP_MAIN);
    $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
    $extraFieldValuesTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
    $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
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
	iid IN (
	SELECT
		item_id
	FROM
		$extraFieldValuesTable
	WHERE
		item_id IN ( SELECT iid FROM $lpTable )
		AND field_id IN ( SELECT id FROM $extraFieldTable WHERE variable = 'notify_student_and_hrm_when_available' )
	) AND
        publicated_on >= '$date 00:00:00' AND
        publicated_on <= '$date 23:59:59'
";
    $result = Database::query($sql);
    $data = Database::store_result($result);
    Database::free_result($result);
    foreach ($data as $row) {
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
            $href = api_get_path(WEB_CODE_PATH).
                "lp/lp_controller.php?cidReq=".htmlspecialchars($courseCode).
                "&id_session=$sessionId &action=view&lp_id=$lpId&gidReq=0&gradebook=0&origin=";

            $link = "<a href='$href'>$href</a>";

            SendMessage(
                $userInfo,
                $admin,
                $courseName,
                $lpName,
                $link
            );
            if (count($HrUsers) != 0) {
                foreach ($HrUsers as $userHr) {
                    SendMessage(
                        $userHr,
                        $admin,
                        $courseName,
                        $lpName,
                        $link
                    );
                }
            }
        }
    }
}
LearningPaths();

exit();
