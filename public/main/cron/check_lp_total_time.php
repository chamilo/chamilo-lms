<?php
/* For licensing terms, see /license.txt */

/**
 * This script checks and propose a query fix for LP items with high time values
 * Only if the total LP time is bigger than the total course time.
 */
exit;

require_once __DIR__.'/../../main/inc/global.inc.php';

api_protect_admin_script();

opcache_reset();

$testSessionId = 182;
$testCourseId = 97;
$max = 10;
$counter = 0;
// Check Sessions
$_configuration['access_url'] = 6;
$sessions = SessionManager::formatSessionsAdminForGrid();
foreach ($sessions as $session) {
    $sessionId = $session['id'];
    if (!empty($testSessionId)) {
        if ($sessionId != $testSessionId) {
            continue;
        }
    }
    $courses = SessionManager::getCoursesInSession($sessionId);

    foreach ($courses as $courseId) {
        if (!empty($testCourseId)) {
            if ($testCourseId != $courseId) {
                continue;
            }
        }

        $courseInfo = api_get_course_info_by_id($courseId);
        $courseCode = $courseInfo['code'];

        $users = CourseManager::get_user_list_from_course_code(
            $courseCode,
            $sessionId,
            null,
            null,
            0
        );

        foreach ($users as $user) {
            $result = compareLpTimeAndCourseTime($user, $courseInfo, $sessionId);
            if ($result) {
                $counter++;
            }

            if ($counter > $max) {
                break 3;
            }
        }
    }
}

// Courses
/*$courses = CourseManager::get_courses_list();
foreach($courses as $courseInfo) {
    $courseCode = $courseInfo['code'];
    $courseInfo['real_id'] = $courseInfo['id'];
    $users = CourseManager::get_user_list_from_course_code($courseCode);
    foreach ($users as $user) {
        $userId = $user['id'];
        compareLpTimeAndCourseTime($userId, $courseInfo);
    }
}*/

/**
 * @param array $user
 * @param array $courseInfo
 * @param int   $sessionId
 *
 * @return bool
 */
function compareLpTimeAndCourseTime($user, $courseInfo, $sessionId = 0)
{
    $userId = $user['user_id'];
    $defaultValue = 600; // 10 min
    $courseCode = $courseInfo['code'];
    $courseId = $courseInfo['real_id'];

    $totalLpTime = Tracking::get_time_spent_in_lp(
        $userId,
        $courseCode,
        [],
        $sessionId
    );

    if (empty($totalLpTime)) {
        return false;
    }

    $totalCourseTime = Tracking::get_time_spent_on_the_course(
        $userId,
        $courseId,
        $sessionId
    );
    $content = '';
    if ($totalLpTime > $totalCourseTime) {
        $totalCourseTimeFormatted = api_time_to_hms($totalCourseTime);
        $totalLpTimeFormatted = api_time_to_hms($totalLpTime);
        $diff = $totalLpTime - $totalCourseTime;

        $content = PHP_EOL."User: ".$user['user_id']." - Total course: $totalCourseTimeFormatted / Total LP: $totalLpTimeFormatted".PHP_EOL;
        $content .= PHP_EOL."Diff: ".api_time_to_hms($diff).PHP_EOL;
        $url = api_get_path(WEB_CODE_PATH).'mySpace/myStudents.php?student='.$userId.'&course='.$courseCode.'&id_session='.$sessionId;
        $content .= Display::url('Check', $url, ['target' => '_blank']);
        $content .= PHP_EOL;

        // Check possible records with high values
        $sql = "SELECT iv.iid, lp_id, total_time 
                FROM c_lp_view v 
                INNER JOIN c_lp_item_view iv
                ON (iv.c_id = v.c_id AND v.id = iv.lp_view_id)
                WHERE 
                    user_id = $userId AND 
                    v.c_id = $courseId AND 
                    session_id = $sessionId
                ORDER BY total_time desc
                LIMIT 1
                ";
        echo $sql.PHP_EOL;
        $result = Database::query($sql);
        $results = Database::store_result($result, 'ASSOC');
        if (!empty($results)) {
            $content .= 'Top 1 high lp item times'.PHP_EOL.PHP_EOL;
            foreach ($results as $item) {
                $lpId = $item['lp_id'];
                $link = api_get_path(WEB_CODE_PATH).'mySpace/lp_tracking.php?cidReq='.$courseCode.
                    '&course='.$courseCode.'&origin=&lp_id='.$lpId.'&student_id='.$userId.'&id_session='.$sessionId;
                $content .= "total_time to be reduced = ".api_time_to_hms($item['total_time']).PHP_EOL;
                $content .= Display::url('See report before update', $link, ['target' => '_blank']).PHP_EOL;
                $content .= "SQL with possible fix:".PHP_EOL;

                if ($item['total_time'] < $defaultValue) {
                    $content .= "Skip because total_time is too short. total_time: ".$item['total_time'].' value to rest'.$defaultValue.PHP_EOL;
                    continue;
                }
                $content .= "UPDATE c_lp_item_view SET total_time = total_time - '$defaultValue' WHERE iid = ".$item['iid'].";".PHP_EOL.PHP_EOL;
            }
        }
    }

    echo nl2br($content);

    return true;
}

exit;
