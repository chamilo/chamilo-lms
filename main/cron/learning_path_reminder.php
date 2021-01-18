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
    MessageManager::send_message_simple(
        $toUser,
        $tittle,
        $content,
        $fromUser,
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

function isUserSubscribeToLp($userid = 0, $courseId = 0, $lpItemId = 0)
{
    if ($userid == 0 || $courseId == 0 || $lpItemId == 0) {
        return 0;
    }
    $sql = " select
        *
 from c_item_property as a
 where
       a.to_user_id = $userid
   and c_id = $courseId
   and a.ref = $lpItemId
   and lastedit_type  ='LearnpathSubscription'";

    $result = Database::query($sql);
    $data = Database::fetch_array($result);
    Database::free_result($result);
    if ($data == false || count($data) == 0 || !isset($data['insert_user_id'])) {
        return 0;
    }

    return $data['insert_user_id'];
}

function LearningPaths()
{
    $lpTable = Database::get_course_table(TABLE_LP_MAIN);
    $courseTable = Database::get_main_table(TABLE_MAIN_COURSE);
    $extraFieldValuesTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
    $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
    $date = new DateTime();
    $date = $date->format('Y-m-d');

    $sql = "
SELECT
	a.id AS l_id,
	a.c_id AS c_id,
	a.session_id AS session_id,
	a.`name` AS `name`,
	d.title AS course_name,
	d.`code` AS `code`,
	d.id AS course_id
FROM
	$lpTable AS a

	INNER JOIN $courseTable as d ON a.c_id = d.id
	INNER JOIN $extraFieldValuesTable AS b ON ( b.item_id = a.iid )
	INNER JOIN $extraFieldTable AS c ON ( b.field_id = c.id AND c.variable = 'notify_student_and_hrm_when_available' )

WHERE
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
        $courseId = $row['course_id'];
        $userlist = CourseManager::get_user_list_from_course_code(
            $courseCode,
            $sessionId
        );
        foreach ($userlist as $user) {
            $fromUser = isUserSubscribeToLp($user['id'], $courseId, $lpId);
            if ($fromUser != 0) {
                $userInfo = api_get_user_info($user['id']);
                // $HrUsers = getHrUserOfUser($user['id']);
                $href = api_get_path(WEB_CODE_PATH).
                    "lp/lp_controller.php?cidReq=".htmlspecialchars($courseCode).
                    "&id_session=$sessionId &action=view&lp_id=$lpId&gidReq=0&gradebook=0&origin=";
                $link = "<a href='$href'>$href</a>";
                SendMessage(
                    $userInfo,
                    $fromUser,
                    $courseName,
                    $lpName,
                    $link
                );
                /*
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
                */
            }
        }
    }
}
LearningPaths();

exit();
