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
    $toUserId = $toUser['user_id'];
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
        $toUserId,
        $tittle,
        $content,
        $fromUser,
        true
    );

    return null;
}

function LearningPaths()
{
    $lpTable = Database::get_course_table(TABLE_LP_MAIN);
    $extraFieldValuesTable = Database::get_main_table(TABLE_EXTRA_FIELD_VALUES);
    $extraFieldTable = Database::get_main_table(TABLE_EXTRA_FIELD);
    $tblCourse = Database::get_main_table(TABLE_MAIN_COURSE);
    $tblCourseRelUser = Database::get_main_table(TABLE_MAIN_COURSE_USER);
    $tblSessionCourseUser = Database::get_main_table(TABLE_MAIN_SESSION_COURSE_USER);
    $tblItempProperty = Database::get_course_table(TABLE_ITEM_PROPERTY);
    $date = new DateTime();
    $date = $date->format('Y-m-d');

    $sql = "
    SELECT
        tblItemProperty.session_id as session_id,
        tblItemProperty.to_user_id as user_id,
        tblItemProperty.insert_user_id as from_user_id,
        tblLp.id AS l_id,
        tblLp.c_id AS c_id,
        tblLp.`name` AS `name`,
        tblCourse.title AS course_name,
        tblCourse.`code` AS `code`,
        tblCourse.id AS course_id,
        tblItemProperty.lastedit_type
    FROM
        $tblItempProperty as tblItemProperty
        INNER JOIN $lpTable as tblLp ON tblLp.iid = tblItemProperty.ref
        INNER JOIN $tblCourse as tblCourse ON tblLp.c_id = tblCourse.id
        INNER JOIN $extraFieldValuesTable AS tblExtraFieldValues ON ( tblExtraFieldValues.item_id = tblLp.iid )
        INNER JOIN $extraFieldTable AS tblExtraField ON ( tblExtraFieldValues.field_id = tblExtraField.id
                                                   AND tblExtraField.variable = 'notify_student_and_hrm_when_available' )
    WHERE
        tblItemProperty.lastedit_type  ='LearnpathSubscription'
        AND publicated_on <= '$date 23:59:59'
        AND publicated_on >= '$date 00:00:00'
        AND tblItemProperty.to_user_id is not null
    ";
    $result = Database::query($sql);
    $data = Database::store_result($result);
    Database::free_result($result);
    $groupUsers = [];
    $alreadyInLp = [];
    foreach ($data as $row) {
        $lpId = (int)$row['l_id'];
        $sessionId = (int)$row['session_id'];
        $courseCode = $row['code'];
        $courseName = $row['course_name'];
        $toUser = (int)$row['user_id'];
        $fromUser = (int)$row['from_user_id'];
        $lpName = $row['name'];
        $userInfo = api_get_user_info($toUser);
        $href = api_get_path(WEB_CODE_PATH).
            "lp/lp_controller.php?cidReq=".htmlspecialchars($courseCode).
            "&id_session=$sessionId &action=view&lp_id=$lpId&gidReq=0&gradebook=0&origin=";
        $link = "<a href='$href'>$href</a>";
        $alreadyInLp[$lpId][$sessionId] = $toUser;
        $groupUsers[$lpId][$sessionId][$toUser] = [
            'userInfo' => $userInfo,
            'fromUser' => $fromUser,
            'courseName' => $courseName,
            'lpName' => $lpName,
            'link' => $link,
        ];
    }
    // For courses

    $sql = "
    SELECT
        tblCourseRelUser.user_id AS user_id,
        tblLp.id AS l_id,
        tblLp.c_id AS c_id,
        tblLp.`name` AS `name`,
        tblCourse.title AS course_name,
        tblCourse.`code` AS `code`,
        tblCourse.id AS course_id
    FROM
        $lpTable AS tblLp
        INNER JOIN $extraFieldValuesTable AS tblExtraFieldValues ON ( tblExtraFieldValues.item_id = tblLp.iid )
        INNER JOIN $extraFieldTable AS tblExtraField ON ( tblExtraFieldValues.field_id = tblExtraField.id
                                                   AND tblExtraField.variable = 'notify_student_and_hrm_when_available' )

        INNER JOIN $tblCourse AS tblCourse ON tblLp.c_id = tblCourse.id
        INNER JOIN $tblCourseRelUser AS tblCourseRelUser ON ( tblCourseRelUser.c_id = tblCourse.id)
    WHERE
        publicated_on <= '$date 23:59:59'
        AND publicated_on >= '$date 00:00:00'
        AND tblCourseRelUser.user_id  is not null
    ";

    $result = Database::query($sql);
    $data = Database::store_result($result);
    Database::free_result($result);
    foreach ($data as $row) {
        $lpId = (int)$row['l_id'];
        $sessionId = 0;
        $courseCode = $row['code'];
        $courseName = $row['course_name'];
        $toUser = (int)$row['user_id'];
        $fromUser = (int)0;
        $lpName = $row['name'];
        $userInfo = api_get_user_info($toUser);
        $href = api_get_path(WEB_CODE_PATH).
            'lp/lp_controller.php?cidReq='.htmlspecialchars($courseCode).
            "&id_session=$sessionId &action=view&lp_id=$lpId&gidReq=0&gradebook=0&origin=";
        $link = "<a href='$href'>$href</a>";
        if (!isset($alreadyInLp[$lpId][$sessionId])) {
            $groupUsers[$lpId][$sessionId][$toUser] = [
                'userInfo' => $userInfo,
                'fromUser' => $fromUser,
                'courseName' => $courseName,
                'lpName' => $lpName,
                'link' => $link
            ];
        }
    }
    // For sessions
    $sql = "
    SELECT
        tblCourseRelUser.user_id AS user_id,
        tblCourseRelUser.session_id AS session_id,
        tblLp.id AS l_id,
        tblLp.c_id AS c_id,
        tblLp.`name` AS `name`,
        tblCourse.title AS course_name,
        tblCourse.`code` AS `code`,
        tblCourse.id AS course_id
    FROM
        $lpTable AS tblLp
        INNER JOIN $extraFieldValuesTable AS tblExtraFieldValues ON ( tblExtraFieldValues.item_id = tblLp.iid )
        INNER JOIN $extraFieldTable AS tblExtraField ON ( tblExtraFieldValues.field_id = tblExtraField.id AND
                                               tblExtraField.variable = 'notify_student_and_hrm_when_available' )
        INNER JOIN $tblCourse AS tblCourse ON tblLp.c_id = tblCourse.id
        INNER JOIN $tblSessionCourseUser AS tblCourseRelUser ON ( tblCourseRelUser.c_id = tblCourse.id)
    WHERE
        publicated_on <= '$date 23:59:59'
        AND publicated_on >= '$date 00:00:00'
        AND tblCourseRelUser.user_id  is not null
    ";
    $result = Database::query($sql);
    $data = Database::store_result($result);
    Database::free_result($result);
    foreach ($data as $row) {
        $lpId = (int)$row['l_id'];
        $sessionId = (int)$row['session_id'];
        $courseCode = $row['code'];
        $courseName = $row['course_name'];
        $toUser = (int)$row['user_id'];
        $fromUser = (int)0;
        $lpName = $row['name'];
        $userInfo = api_get_user_info($toUser);
        $href = api_get_path(WEB_CODE_PATH).
            'lp/lp_controller.php?cidReq='.htmlspecialchars($courseCode).
            "&id_session=$sessionId &action=view&lp_id=$lpId&gidReq=0&gradebook=0&origin=";
        $link = "<a href='$href'>$href</a>";
        if (!isset($alreadyInLp[$lpId][$sessionId]) and !empty($toUser)) {
            $groupUsers[$lpId][$sessionId][$toUser] = [
                'userInfo' => $userInfo,
                'fromUser' => $fromUser,
                'courseName' => $courseName,
                'lpName' => $lpName,
                'link' => $link
            ];
        }
    }


    foreach ($groupUsers as $lpId => $sessions) {
        foreach ($sessions as $sessionId => $users) {
            foreach ($users as $user) {
                SendMessage(
                    $user['userInfo'],
                    $user['fromUser'],
                    $user['courseName'],
                    $user['lpName'],
                    $user['link']
                );
            }
        }
    }
}

LearningPaths();

exit();
