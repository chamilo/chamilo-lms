<?php

/* For licensing terms, see /license.txt */

exit;

error_reporting(0);

require_once __DIR__.'/../../main/inc/global.inc.php';

$sql = 'SELECT id FROM course order by id';
$result = Database::query($sql);
$fix = true;

$table = Database::get_course_table(TABLE_QUIZ_TEST);
while ($row = Database::fetch_array($result, 'ASSOC')) {
    $courseId = $row['id'];
    $courseInfo = api_get_course_info_by_id($courseId);
    if (empty($courseInfo)) {
        continue;
    }

    // Not only active means visible and invisible NOT deleted (-2)
    $sql_active_exercises = "active IN (1, 0) AND ";

    $params = [
        $courseId,
    ];

    // All exercises
    $conditions = [
        'where' => ["$sql_active_exercises (session_id <> 0 ) AND c_id = ? " => $params],
        'order' => 'title',
    ];
    $exerciseList = Database::select('*', $table, $conditions);



    if (empty($exerciseList)) {
        continue;
    }

    $counter = 0;
    $emptyCounter = 0;
    $fixed = 0;
    $userId = 1;

    foreach ($exerciseList as $exerciseItem) {
        $oldId = $exerciseItem['id'];
        $exerciseId = $exerciseItem['iid'];

        $sessionId = $exerciseItem['session_id'];

        if ($oldId <>  $exerciseId) {
//		continue;
        }
        $oldVisibility = api_get_item_visibility(
            $courseInfo,
            TOOL_QUIZ,
            $oldId,
            $sessionId
        );

        $visibility = api_get_item_visibility(
            $courseInfo,
            TOOL_QUIZ,
            $exerciseId,
            $sessionId
        );

        if (-1 === $visibility && -1 === $oldVisibility) {
            if ($fix) {
                api_item_property_update(
                    $courseInfo,
                    TOOL_QUIZ,
                    $exerciseId,
                    'QuizUpdated',
                    $userId,
                    null,
                    null,
                    null,
                    null,
                    $sessionId
                );
                echo "Fix exercise iid = $exerciseId / ".$exerciseItem['title'].PHP_EOL;
                $fixed++;
            }

            echo "check session: $sessionId  exercise iid = $exerciseId / ".$exerciseItem['title'].PHP_EOL;


            $emptyCounter++;
        } else {

//	   echo "Ref exists: visibility: ".$visibility['visibility']."  session: $sessionId check exercise iid = $exerciseId / ".$exerciseItem['title'].PHP_EOL;

        }
        $counter++;
    }

    if ($emptyCounter > 0) {
        echo 'Course: '.$courseId.' - '.$courseInfo['title'].PHP_EOL.PHP_EOL;
        echo $emptyCounter.'/ ' . $counter.PHP_EOL;
        echo 'To be fix: ' . $emptyCounter.PHP_EOL;
        exit;
    }
}


